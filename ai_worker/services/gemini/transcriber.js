import fs from 'node:fs';
import path from 'node:path';
import { config } from '../../config.js';
import { callWithRetry, JobCancelledError, describeError } from '../throttler.js';
import { runWithModelChain } from '../modelChainRunner.js';
import { formatChunkIndex, probeDuration } from '../audioSlicer.js';
import { log as workerLog } from '../logger.js';
import { getAiClient, resolveThinkingLevel } from './client.js';
import { uploadToFilesApi, deleteFromFilesApi, waitForFileActive } from './filesApi.js';
import { streamText } from './streamCall.js';
import { TRANSCRIPTION_PROMPT } from './prompts.js';

/**
 * Memvalidasi hasil teks transkrip dari model AI.
 * Melempar Error jika transkrip tidak memenuhi syarat kualitas (hard fail).
 * Mencatat peringatan jika ada indikasi masalah ringan (soft warn).
 *
 * @param {string} text          Teks transkrip yang sudah di-trim
 * @param {string} chunkNum      Label chunk untuk pesan log (misal "001")
 * @param {number} minWords      Minimum jumlah kata yang diharapkan
 * @param {Function} onLog       Callback log
 * @throws {Error}               Jika validasi hard-fail tidak terpenuhi
 */
function validateTranscriptQuality(text, chunkNum, minWords, onLog) {
  const words = text.split(/\s+/).filter(Boolean).length;

  // Hard fail 1: densitas kata terlalu rendah. Indikasi output terpotong,
  // hanya konfirmasi, atau audio tidak terbaca.
  if (words < minWords) {
    throw new Error(
      `Transkrip chunk_${chunkNum} terlalu pendek: ${words} kata (minimum ${minWords} kata). ` +
      `Kemungkinan output terpotong atau audio tidak dapat ditranskripsikan.`
    );
  }

  // Hard fail 2: tidak ada newline DAN tidak ada label speaker. Indikasi model
  // mengabaikan format prompt dan mengembalikan satu blok teks panjang.
  const hasNewlines = text.includes('\n');
  const hasSpeakerLabel = /\[.+?\]/.test(text);
  if (!hasNewlines && !hasSpeakerLabel) {
    throw new Error(
      `Transkrip chunk_${chunkNum} tidak berstruktur: tidak ada newline atau label speaker. ` +
      `Model mengabaikan format prompt diarization.`
    );
  }

  // Soft warn: kemungkinan terpotong di tengah kalimat. Cek hanya jika teks
  // cukup panjang agar tidak false positive pada transkrip pendek.
  if (text.length >= config.validation.abruptCutMinLength) {
    const tail = text.slice(-150);
    const endsAbruptly = !/[.!?\]"']/.test(tail);
    if (endsAbruptly) {
      onLog(
        `[Transcribe] Peringatan: chunk_${chunkNum} mungkin terpotong di tengah kalimat ` +
        `(tidak ada tanda baca penutup di 150 karakter terakhir).`
      );
    }
  }

  onLog(
    `[Transcribe] Validasi chunk_${chunkNum} lulus: ${words} kata, ` +
    `${hasNewlines ? 'ada newline' : 'tanpa newline'}, ` +
    `${hasSpeakerLabel ? 'ada label speaker' : 'tanpa label speaker'}.`
  );
}

/**
 * Minimum kata yang diharapkan dari transkrip, proporsional terhadap durasi
 * bicara (bukan durasi total) chunk. Chunk final mendapat toleransi lebih longgar.
 */
export function minExpectedWordsFor(speechSeconds, isFinalChunk) {
  const speechMinutes = Math.max(0, speechSeconds || 0) / 60;
  if (isFinalChunk) {
    return Math.min(30, Math.max(10, Math.floor(speechMinutes * config.validation.minWordsPerMinute)));
  }
  return Math.max(50, Math.floor(speechMinutes * config.validation.minWordsPerMinute));
}

/**
 * Menjalankan transkripsi audio per chunk dengan rantai model fallback (Primary -> Fallbacks)
 * dan proteksi penulisan atomik (.part -> rename).
 *
 * @param {Object} params Parameter transkripsi
 * @param {string} params.chunkPath Path absolut file audio chunk
 * @param {number} params.chunkIndex Indeks potongan (1, 2, 3...)
 * @param {number} params.totalChunks Jumlah total potongan
 * @param {string} params.transcriptsDir Direktori target transkrip (folder `transcripts/`)
 * @param {Function} params.cancelChecker Fungsi pengecek apakah job dibatalkan oleh admin
 * @param {Function} params.onLog Callback pencatat log progres
 * @returns {Promise<string>} Teks hasil transkripsi chunk
 */
export async function transcribeChunkWithFallback({
  chunkPath,
  chunkIndex,
  totalChunks,
  durationSeconds = null,
  speechSeconds = null,
  isLastChunk = false,
  transcriptsDir,
  cancelChecker = null,
  onLog = workerLog,
}) {
  const chunkNum = formatChunkIndex(chunkIndex);
  const finalFilePath = path.join(transcriptsDir, `chunk_${chunkNum}.txt`);
  const partFilePath = path.join(transcriptsDir, `chunk_${chunkNum}.txt.part`);

  // Pastikan folder transcripts/ tersedia
  if (!fs.existsSync(transcriptsDir)) {
    fs.mkdirSync(transcriptsDir, { recursive: true });
  }

  // Hitung durasi aktual potongan audio (dalam detik)
  let actualDurationSeconds = durationSeconds;
  if (typeof actualDurationSeconds !== 'number' || actualDurationSeconds <= 0) {
    try {
      if (fs.existsSync(chunkPath)) {
        actualDurationSeconds = await probeDuration(chunkPath);
      }
    } catch {
      actualDurationSeconds = config.audio.chunkDurationSeconds;
    }
  }
  if (!actualDurationSeconds || actualDurationSeconds <= 0) {
    actualDurationSeconds = config.audio.chunkDurationSeconds;
  }

  const isFinalChunk = isLastChunk || (chunkIndex === totalChunks);

  // Minimum kata proporsional terhadap durasi bicara chunk (VAD), bukan durasi total
  const minExpectedWords = minExpectedWordsFor(
    speechSeconds ?? actualDurationSeconds,
    isFinalChunk
  );

  // 1. Cek Checkpoint: jika chunk_NNN.txt final sudah ada, validasi dulu sebelum lewati
  if (fs.existsSync(finalFilePath)) {
    const existingContent = fs.readFileSync(finalFilePath, 'utf-8').trim();
    if (existingContent.length > 0) {
      const existingWords = existingContent.split(/\s+/).filter(Boolean).length;
      if (existingWords >= minExpectedWords) {
        onLog(`[Transcribe] Checkpoint valid: chunk_${chunkNum}.txt (${existingWords} kata). Melewati...`);
        return existingContent;
      }
      // Checkpoint ada tapi tidak memenuhi densitas minimum — hapus dan proses ulang
      onLog(`[Transcribe] Checkpoint chunk_${chunkNum}.txt tidak valid (${existingWords} kata, minimum ${minExpectedWords}). Memproses ulang...`);
      fs.unlinkSync(finalFilePath);
    }
  }

  onLog(`[Transcribe] Memulai upload & transkripsi chunk_${chunkNum} (${chunkIndex}/${totalChunks})...`);

  // 2. Unggah file audio chunk ke Google Files API
  let uploadedFile = null;
  try {
    uploadedFile = await uploadToFilesApi(chunkPath, 'audio/mp3');
    onLog(`[Files API] File chunk_${chunkNum} berhasil diunggah ke Google Cloud (URI: ${uploadedFile.uri})`);
    if (uploadedFile.state !== 'ACTIVE') {
      uploadedFile = await waitForFileActive(
        (name) => getAiClient().files.get({ name }),
        uploadedFile,
        { onLog }
      );
    }
  } catch (uploadErr) {
    if (uploadErr instanceof JobCancelledError) throw uploadErr;
    throw new Error(`Gagal menyiapkan chunk_${chunkNum} di Files API: ${uploadErr.message}`, { cause: uploadErr });
  }

  try {
    const { result: transcriptText } = await runWithModelChain({
      taskLabel: 'Transcribe',
      cancelChecker,
      onLog,
      attemptMessage: (modelName, mIdx, totalModels, passSuffix) =>
        `[Transcribe] Mencoba model: ${modelName} (Model ke-${mIdx + 1}/${totalModels}${passSuffix}) untuk chunk_${chunkNum}...`,
      stallMessage: (pass, maxPasses, delaySec) =>
        `[Transcribe] Seluruh model mengalami gangguan sementara pada putaran ${pass}/${maxPasses}. Menunggu ${delaySec}s sebelum mengulang rantai...`,
      failureMessage: (lastError) =>
        `Seluruh rantai model AI gagal mentranskripsikan chunk_${chunkNum}. Error terakhir: ${describeError(lastError)}`,
      runForModel: async (modelName) =>
        await callWithRetry(
          async (attempt) => {
            onLog(`[Transcribe] Memanggil model ${modelName} (Percobaan ${attempt}/${config.worker.maxRetriesPerModel})...`);

            const startTime = Date.now();

            const accumulated = await streamText({
              modelName,
              contents: [
                {
                  role: 'user',
                  parts: [
                    {
                      fileData: {
                        fileUri: uploadedFile.uri,
                        mimeType: 'audio/mp3',
                      },
                    },
                    { text: TRANSCRIPTION_PROMPT },
                  ],
                },
              ],
              requestConfig: {
                temperature: 0.1,
                thinkingConfig: {
                  thinkingLevel: resolveThinkingLevel(config.gemini.thinkingLevel),
                },
              },
              cancelChecker,
              onSilence: (sec, isConnected) => {
                if (isConnected) {
                  onLog(`[Transcribe] chunk_${chunkNum} tersambung ke ${modelName}, model sedang memproses audio... [${sec}s tanpa keluaran teks]`);
                } else {
                  onLog(`[Transcribe] chunk_${chunkNum} belum mendapat respons dari server ${modelName} (antrean/kapasitas)... [${sec}s]`);
                }
              },
              onConnected: () => onLog(`[Transcribe] Stream tersambung ke ${modelName}, menunggu keluaran pertama...`),
              onProgress: (text) => {
                const elapsedSec = Math.round((Date.now() - startTime) / 1000);
                const words = text.split(/\s+/).filter(Boolean).length;
                onLog(`[Transcribe] Menerima output chunk_${chunkNum}... [${elapsedSec}s | ~${words.toLocaleString('id-ID')} kata]`);
              },
            });

            if (!accumulated || accumulated.trim().length === 0) {
              throw new Error(`Respons model ${modelName} kosong.`);
            }

            const trimmed = accumulated.trim();

            // Validasi kualitas transkrip
            validateTranscriptQuality(trimmed, chunkNum, minExpectedWords, onLog);

            const wordCount = trimmed.split(/\s+/).filter(Boolean).length;
            const elapsedTotal = ((Date.now() - startTime) / 1000).toFixed(1);
            onLog(`[Transcribe] Stream selesai: chunk_${chunkNum} via ${modelName} [${elapsedTotal}s | ~${wordCount.toLocaleString('id-ID')} kata | ${trimmed.length.toLocaleString('id-ID')} karakter]`);
            return trimmed;
          },
          {
            maxRetries: config.worker.maxRetriesPerModel,
            initialDelayMs: 10000,
            backoffFactor: 2.5,
            failoverOn503: true,
            cancelChecker,
            onRetry: ({ attempt, waitTimeMs, error, isTpm }) => {
              const reason = isTpm ? 'Rate limit TPM' : describeError(error);
              onLog(`[Throttler] ${reason} saat transkripsi chunk_${chunkNum}. Menunggu ${Math.round(waitTimeMs / 1000)}s sebelum retry ${attempt + 1}...`);
            },
          }
        ),
    });

    // Penulisan Transkrip Atomik: tulis .part dahulu -> rename ke file final
    fs.writeFileSync(partFilePath, transcriptText, 'utf-8');
    if (fs.existsSync(finalFilePath)) {
      fs.unlinkSync(finalFilePath);
    }
    fs.renameSync(partFilePath, finalFilePath);
    onLog(`[Transcribe] Berkas transkrip atomik tersimpan: chunk_${chunkNum}.txt`);

    return transcriptText;
  } finally {
    // Instant Cleanup: Hapus file dari Google Files API segera setelah selesai
    if (uploadedFile) {
      onLog(`[Files API] Menghapus file sementara di Google Cloud Files...`);
      await deleteFromFilesApi(uploadedFile);
    }
  }
}
