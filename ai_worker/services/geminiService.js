import fs from 'node:fs';
import path from 'node:path';
import { GoogleGenAI } from '@google/genai';
import { config } from '../config.js';
import { callWithRetry } from './throttler.js';
import { formatChunkIndex } from './audioSlicer.js';

// Inisialisasi Google GenAI Client
let aiClient = null;

export function getAiClient() {
  if (!aiClient) {
    if (!config.gemini.apiKey) {
      throw new Error('GEMINI_API_KEY tidak ditemukan di environment (.env).');
    }
    aiClient = new GoogleGenAI({ apiKey: config.gemini.apiKey });
  }
  return aiClient;
}

/**
 * Unggah file audio potongan ke Google Gemini Files API.
 * Wajib digunakan untuk file berukuran > 20 MB (batas inline).
 */
export async function uploadToFilesApi(filePath, mimeType = 'audio/mp3') {
  const ai = getAiClient();
  const fileUpload = await ai.files.upload({
    file: filePath,
    config: { mimeType },
  });

  return fileUpload;
}

/**
 * Hapus file dari Google Gemini Files API seketika setelah selesai diproses.
 */
export async function deleteFromFilesApi(fileResource) {
  if (!fileResource) return;
  try {
    const ai = getAiClient();
    const fileName = typeof fileResource === 'string' ? fileResource : fileResource.name;
    if (fileName) {
      await ai.files.delete({ name: fileName });
    }
  } catch (err) {
    // Log kegagalan cleanup tanpa menghentikan pipeline
    console.warn(`[GeminiService] Peringatan: Gagal menghapus file cloud ${fileResource?.name || fileResource}:`, err.message);
  }
}

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

  // --- Hard fail 1: Densitas kata terlalu rendah ---
  // Indikasi: model mengembalikan output sangat singkat, mungkin hanya
  // konfirmasi, pernyataan error, atau audio tidak terbaca.
  if (words < minWords) {
    throw new Error(
      `Transkrip chunk_${chunkNum} terlalu pendek: ${words} kata (minimum ${minWords} kata). ` +
      `Kemungkinan output terpotong atau audio tidak dapat ditranskripsikan.`
    );
  }

  // --- Hard fail 2: Tidak ada struktur (tidak ada newline DAN tidak ada label speaker) ---
  // Indikasi: model mengabaikan format prompt dan mengembalikan satu blok
  // teks panjang tanpa pembagian pembicara — tidak berguna untuk risalah.
  const hasNewlines = text.includes('\n');
  const hasSpeakerLabel = /\[.+?\]/.test(text);
  if (!hasNewlines && !hasSpeakerLabel) {
    throw new Error(
      `Transkrip chunk_${chunkNum} tidak berstruktur: tidak ada newline atau label speaker. ` +
      `Model mengabaikan format prompt diarization.`
    );
  }

  // --- Soft warn: Kemungkinan terpotong di tengah kalimat ---
  // Periksa hanya jika teks cukup panjang agar tidak false-positive pada
  // transkrip pendek yang memang berakhir natural.
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
  transcriptsDir,
  cancelChecker = null,
  onLog = console.log,
}) {
  const chunkNum = formatChunkIndex(chunkIndex);
  const finalFilePath = path.join(transcriptsDir, `chunk_${chunkNum}.txt`);
  const partFilePath = path.join(transcriptsDir, `chunk_${chunkNum}.txt.part`);

  // Pastikan folder transcripts/ tersedia
  if (!fs.existsSync(transcriptsDir)) {
    fs.mkdirSync(transcriptsDir, { recursive: true });
  }

  // Hitung minimum kata yang diharapkan berdasarkan durasi chunk
  const chunkDurationMin = config.audio.chunkDurationSeconds / 60;
  const minExpectedWords = Math.floor(chunkDurationMin * config.validation.minWordsPerMinute);

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
  } catch (uploadErr) {
    throw new Error(`Gagal mengunggah chunk_${chunkNum} ke Files API: ${uploadErr.message}`);
  }

  const promptText = `Transkripsikan seluruh isi percakapan rekaman audio rapat DPRD Provinsi Sulawesi Tengah ini dalam Bahasa Indonesia secara verbatim, rapi, dan terstruktur.
Gunakan label speaker diarization per pembicara (misalnya: [Pimpinan Sidang], [Anggota Fraksi/Komisi], [Narasumber], dll.), pisahkan setiap pergantian pembicara dengan baris baru, serta gunakan tanda baca yang tepat dan ejaan resmi istilah pemerintahan.
Hanya kembalikan teks transkrip percakapan tanpa komentar pembuka atau penutup tambahan.`;

  let transcriptText = '';
  let modelSuccess = false;
  let lastError = null;

  const models = config.gemini.modelChain;
  const ai = getAiClient();

  try {
    // 3. Iterasi rantai model AI (Primary -> Fallback 1 -> Fallback 2 -> Fallback 3)
    for (let mIdx = 0; mIdx < models.length; mIdx++) {
      const modelName = models[mIdx];
      onLog(`[Transcribe] Mencoba model: ${modelName} (Model ke-${mIdx + 1}/${models.length}) untuk chunk_${chunkNum}...`);

      try {
        transcriptText = await callWithRetry(
          async (attempt) => {
            onLog(`[Transcribe] Memanggil model ${modelName} (Percobaan ${attempt}/${config.worker.maxRetriesPerModel})...`);

            const startTime = Date.now();
            let accumulated = '';
            let wordCount = 0;
            let lastLogTime = startTime;
            const LOG_INTERVAL_MS = 10_000; // log progress tiap 10 detik

            const stream = await ai.models.generateContentStream({
              model: modelName,
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
                    { text: promptText },
                  ],
                },
              ],
            });

            for await (const chunk of stream) {
              const textChunk = chunk.text ?? '';
              accumulated += textChunk;

              // Hitung kata secara efisien: tambah jumlah kata dari chunk baru
              if (textChunk.trim()) {
                wordCount += textChunk.trim().split(/\s+/).length;
              }

              // Log progress tiap LOG_INTERVAL_MS agar tidak spam
              const now = Date.now();
              if (now - lastLogTime >= LOG_INTERVAL_MS) {
                const elapsedSec = Math.round((now - startTime) / 1000);
                onLog(`[Transcribe] chunk_${chunkNum} streaming... [${elapsedSec}s | ~${wordCount.toLocaleString('id-ID')} kata | ${accumulated.length.toLocaleString('id-ID')} karakter]`);
                lastLogTime = now;
              }
            }

            const elapsedTotal = ((Date.now() - startTime) / 1000).toFixed(1);
            if (!accumulated || accumulated.trim().length === 0) {
              throw new Error(`Respons model ${modelName} kosong.`);
            }

            const trimmed = accumulated.trim();

            // Validasi kualitas transkrip — melempar Error jika tidak memenuhi syarat
            validateTranscriptQuality(trimmed, chunkNum, minExpectedWords, onLog);

            onLog(`[Transcribe] Stream selesai: chunk_${chunkNum} via ${modelName} [${elapsedTotal}s | ~${wordCount.toLocaleString('id-ID')} kata | ${trimmed.length.toLocaleString('id-ID')} karakter]`);
            return trimmed;
          },
          {
            maxRetries: config.worker.maxRetriesPerModel,
            initialDelayMs: 10000,
            backoffFactor: 2.5,
            cancelChecker,
            onRetry: ({ attempt, waitTimeMs, error }) => {
              onLog(`[Throttler] Error saat transkripsi chunk_${chunkNum} (${error.message}). Menunggu ${Math.round(waitTimeMs / 1000)}s sebelum retry ${attempt + 1}...`);
            },
          }
        );

        modelSuccess = true;
        break;
      } catch (err) {
        lastError = err;
        if (err.message === 'JOB_CANCELLED_BY_ADMIN') {
          throw err;
        }
        onLog(`[Transcribe] Model ${modelName} gagal setelah seluruh retry: ${err.message}. Mencoba model fallback berikutnya...`);
      }
    }

    if (!modelSuccess || !transcriptText) {
      throw new Error(`Seluruh rantai model AI gagal mentranskripsikan chunk_${chunkNum}. Error terakhir: ${lastError?.message}`);
    }

    // 4. Penulisan Transkrip Atomik: tulis .part dahulu -> rename ke file final
    fs.writeFileSync(partFilePath, transcriptText, 'utf-8');
    if (fs.existsSync(finalFilePath)) {
      fs.unlinkSync(finalFilePath);
    }
    fs.renameSync(partFilePath, finalFilePath);
    onLog(`[Transcribe] Berkas transkrip atomik tersimpan: chunk_${chunkNum}.txt`);

    return transcriptText;
  } finally {
    // 5. Instant Cleanup: Hapus file dari Google Files API segera setelah selesai
    if (uploadedFile) {
      onLog(`[Files API] Menghapus file sementara di Google Cloud Files...`);
      await deleteFromFilesApi(uploadedFile);
    }
  }
}

/**
 * Menyusun draft Risalah Rapat Resmi DPRD Provinsi Sulawesi Tengah
 * dari kumpulan transkrip lengkap menggunakan rantai model AI.
 *
 * @param {Object} params
 * @param {string} params.fullTranscript Teks transkrip gabungan seluruh chunk
 * @param {Object} params.metadata Metadata rapat (judul_rapat, tanggal_rapat, jadwal_type)
 * @param {Function} params.cancelChecker Fungsi cek cancel
 * @param {Function} params.onLog Callback log
 * @returns {Promise<Object>} Data risalah terstruktur (ringkasan_eksekutif, agenda_pembahasan, kesimpulan, tindak_lanjut, peserta_terdeteksi)
 */
export async function generateMeetingMinutesWithFallback({
  fullTranscript,
  metadata = {},
  cancelChecker = null,
  onLog = console.log,
}) {
  onLog(`[Minutes] Memulai penyusunan Risalah Rapat resmi dari transkrip (${fullTranscript.length} karakter)...`);

  const promptText = `Anda adalah Sekretaris Notulis Ahli untuk DPRD (Dewan Perwakilan Rakyat Daerah) Provinsi Sulawesi Tengah.
Tugas Anda adalah membaca dan menganalisis transkrip rekaman rapat berikut, kemudian menyusun DRAFT RISALAH RAPAT RESMI dalam format JSON terstruktur yang profesional, rapi, dan sesuai tata naskah dinas legislatif daerah.

Informasi Konteks Rapat:
- Judul Rapat: ${metadata.judul_rapat || 'Rapat DPRD Provinsi Sulawesi Tengah'}
- Tanggal Rapat: ${metadata.tanggal_rapat || 'Sesuai agenda'}
- Jenis Agenda: ${metadata.jadwal_type || 'Umum/Banmus'}

KONTEN TRANSKRIP RAPAT LENGKAP:
---
${fullTranscript}
---

INSTRUKSI OUTPUT:
Kembalikan HANYA sebuah objek JSON valid dengan struktur kunci persis sebagai berikut (tanpa blok markdown formatting pembungkus seperti \`\`\`json):
{
  "ringkasan_eksekutif": "Ringkasan eksekutif komprehensif rapat dalam 2-4 paragraf yang merangkum pokok bahasan, perdebatan utama, dan hasil akhir.",
  "agenda_pembahasan": [
    {
      "topik": "Judul pokok bahasan 1",
      "uraian": "Penjelasan rinci mengenai pembahasan materi, saran, kritik, atau catatan fraksi/komisi",
      "pembicara": "Nama/jabatan pihak atau fraksi terkait yang memberikan pandangan"
    }
  ],
  "kesimpulan": [
    "Poin kesimpulan atau keputusan rapat butir 1",
    "Poin kesimpulan atau keputusan rapat butir 2"
  ],
  "tindak_lanjut": [
    "Poin instruksi, rekomendasi, atau rencana aksi tindak lanjut 1",
    "Poin instruksi, rekomendasi, atau rencana aksi tindak lanjut 2"
  ],
  "peserta_terdeteksi": [
    "Nama/Jabatan peserta yang teridentifikasi berbicara atau hadir dalam rekaman"
  ]
}`;

  const models = config.gemini.modelChain;
  const ai = getAiClient();
  let minutesJson = null;
  let lastError = null;

  for (let mIdx = 0; mIdx < models.length; mIdx++) {
    const modelName = models[mIdx];
    onLog(`[Minutes] Mencoba model risalah: ${modelName} (Model ke-${mIdx + 1}/${models.length})...`);

    try {
      const rawResponse = await callWithRetry(
        async (attempt) => {
          onLog(`[Minutes] Memanggil model ${modelName} untuk menyusun risalah (Percobaan ${attempt}/${config.worker.maxRetriesPerModel})...`);

          const startTime = Date.now();
          let accumulated = '';
          let lastLogTime = startTime;
          const LOG_INTERVAL_MS = 10_000;

          const stream = await ai.models.generateContentStream({
            model: modelName,
            contents: promptText,
            config: {
              responseMimeType: 'application/json',
            },
          });

          for await (const chunk of stream) {
            accumulated += chunk.text ?? '';

            const now = Date.now();
            if (now - lastLogTime >= LOG_INTERVAL_MS) {
              const elapsedSec = Math.round((now - startTime) / 1000);
              onLog(`[Minutes] Menyusun risalah... [${elapsedSec}s | ${accumulated.length.toLocaleString('id-ID')} karakter terkumpul]`);
              lastLogTime = now;
            }
          }

          const elapsedTotal = ((Date.now() - startTime) / 1000).toFixed(1);
          if (!accumulated || accumulated.trim().length === 0) {
            throw new Error(`Respons risalah model ${modelName} kosong.`);
          }

          onLog(`[Minutes] Stream selesai: risalah via ${modelName} [${elapsedTotal}s | ${accumulated.trim().length.toLocaleString('id-ID')} karakter]`);
          return accumulated.trim();
        },
        {
          maxRetries: config.worker.maxRetriesPerModel,
          initialDelayMs: 10000,
          backoffFactor: 2.5,
          cancelChecker,
          onRetry: ({ attempt, waitTimeMs, error }) => {
            onLog(`[Throttler] Error saat generate risalah (${error.message}). Menunggu ${Math.round(waitTimeMs / 1000)}s sebelum retry ${attempt + 1}...`);
          },
        }
      );

      // Bersihkan kemungkinan markdown wrapping ```json ... ```
      let cleanedJson = rawResponse.replace(/^```json\s*/i, '').replace(/^```\s*/i, '').replace(/\s*```$/i, '').trim();

      try {
        minutesJson = JSON.parse(cleanedJson);
      } catch (parseErr) {
        throw new Error(`Gagal mem-parse JSON hasil risalah dari model ${modelName}: ${parseErr.message}`);
      }

      onLog(`[Minutes] Risalah rapat berhasil disusun via model ${modelName}!`);
      break;
    } catch (err) {
      lastError = err;
      if (err.message === 'JOB_CANCELLED_BY_ADMIN') {
        throw err;
      }
      onLog(`[Minutes] Model ${modelName} gagal menyusun risalah: ${err.message}. Mencoba model fallback berikutnya...`);
    }
  }

  if (!minutesJson) {
    throw new Error(`Seluruh rantai model AI gagal menyusun risalah rapat. Error terakhir: ${lastError?.message}`);
  }

  return {
    ringkasan_eksekutif: minutesJson.ringkasan_eksekutif || '',
    agenda_pembahasan: Array.isArray(minutesJson.agenda_pembahasan) ? minutesJson.agenda_pembahasan : [],
    kesimpulan: Array.isArray(minutesJson.kesimpulan) ? minutesJson.kesimpulan : [],
    tindak_lanjut: Array.isArray(minutesJson.tindak_lanjut) ? minutesJson.tindak_lanjut : [],
    peserta_terdeteksi: Array.isArray(minutesJson.peserta_terdeteksi) ? minutesJson.peserta_terdeteksi : [],
  };
}
