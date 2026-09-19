import fs from 'node:fs';
import path from 'node:path';
import { config } from '../config.js';
import { sliceAudio, probeDuration } from './audioSlicer.js';
import { transcribeChunkWithFallback } from './gemini/transcriber.js';
import { generateMeetingMinutesWithFallback } from './gemini/minutes.js';
import { interruptibleSleep, JobCancelledError } from './throttler.js';
import { loadOrAnalyze, isChunkSilent } from './vad.js';
import { log, warn } from './logger.js';
import {
  claimJob,
  createCancelChecker,
  fetchMeetingContext,
  markJobCancelled,
  markJobCompleted,
  saveMeetingMinutes,
  updateJobProgress,
} from './jobRepository.js';

/**
 * Pembersihan file potongan audio lokal setelah job completed (Retensi Disk).
 */
function cleanLocalAudioChunks(audioDir) {
  try {
    if (!fs.existsSync(audioDir)) return;
    const files = fs.readdirSync(audioDir);
    for (const file of files) {
      if (file.startsWith('chunk_') && file.endsWith('.mp3')) {
        fs.unlinkSync(path.join(audioDir, file));
      }
    }
    log(`[Worker] Retensi Disk: File potongan chunk audio lokal di ${audioDir} berhasil dibersihkan.`);
  } catch (err) {
    warn(`[Worker] Peringatan saat membersihkan chunk lokal:`, err.message);
  }
}

/**
 * Menentukan path file input rekaman dari kolom audio_path job:
 * relatif terhadap writable/uploads atau root proyek, dengan fallback
 * ke lokasi standard audio/original.mp3.
 */
function resolveInputAudioPath(job, audioDir) {
  let inputAudioPath = job.audio_path || '';
  if (!path.isAbsolute(inputAudioPath)) {
    const writableUploadsPath = path.resolve(config.paths.root, 'writable/uploads', inputAudioPath);
    const directRootPath = path.resolve(config.paths.root, inputAudioPath);
    if (fs.existsSync(writableUploadsPath)) {
      inputAudioPath = writableUploadsPath;
    } else if (fs.existsSync(directRootPath)) {
      inputAudioPath = directRootPath;
    } else {
      inputAudioPath = writableUploadsPath;
    }
  }

  if (!fs.existsSync(inputAudioPath)) {
    const standardPath = path.join(audioDir, 'original.mp3');
    if (fs.existsSync(standardPath)) {
      inputAudioPath = standardPath;
    } else {
      throw new Error(`Berkas rekaman audio tidak ditemukan di path: ${inputAudioPath}`);
    }
  }

  return inputAudioPath;
}

/**
 * Eksekutor pemrosesan satu job rapat.
 */
export async function processJob(pool, job) {
  const jobId = job.id;
  const isCancelled = createCancelChecker(pool, jobId);

  // Klaim atomik: hanya worker yang berhasil mengubah status dari 'queued'
  // yang boleh memproses job ini.
  const claimed = await claimJob(pool, jobId);
  if (!claimed) {
    log(`[Worker] Job #${jobId} dilewati: tidak berstatus 'queued' (sedang dipegang worker lain atau sudah selesai).`);
    return;
  }

  try {
    log(`\n========================================================`);
    log(`[Worker] Memulai Pemrosesan Job ID #${jobId} (${job.audio_filename})`);
    log(`========================================================`);

    const jobDir = path.join(config.paths.recordingsBaseDir, `job_${jobId}`);
    const audioDir = path.join(jobDir, 'audio');
    const transcriptsDir = path.join(jobDir, 'transcripts');

    // Pastikan struktur folder tersedia
    fs.mkdirSync(audioDir, { recursive: true });
    fs.mkdirSync(transcriptsDir, { recursive: true });

    const inputAudioPath = resolveInputAudioPath(job, audioDir);

    // Periksa apakah ada permintaan pembatalan awal
    if (await isCancelled()) {
      await markJobCancelled(pool, jobId, 'Dibatalkan oleh admin sebelum pemrosesan dimulai');
      log(`[Worker] Job #${jobId} dibatalkan oleh admin sebelum pemrosesan dimulai.`);
      return;
    }

    // 1. Tahap Probe Durasi & Analisis VAD & Chunking Audio
    log(`[Worker] Mengukur durasi audio via ffprobe...`);
    const totalDuration = await probeDuration(inputAudioPath);

    // Pass VAD: rencana chunk sadar-hening dipersistenkan sebelum slicing
    // agar resume mereproduksi batas yang sama persis.
    let vad = null;
    try {
      vad = await loadOrAnalyze(inputAudioPath, jobDir, { onLog: (msg) => log(`[Job #${jobId}] ${msg}`) });
    } catch (vadErr) {
      warn(`[Worker] VAD dilewati untuk Job #${jobId}, memakai potongan seragam: ${vadErr.message}`);
    }
    const plan = vad && Array.isArray(vad.plan) && vad.plan.length > 0
      ? vad.plan
      : null;
    const totalChunks = plan ? plan.length : Math.max(1, Math.ceil(totalDuration / config.audio.chunkDurationSeconds));

    await updateJobProgress(pool, jobId, {
      audioDuration: totalDuration,
      totalChunks,
      currentStep: `Memotong rekaman menjadi ${totalChunks} segmen...`,
    });

    log(`[Worker] Durasi total: ${totalDuration}s (${Math.round(totalDuration / 60)} menit), Total chunk: ${totalChunks}`);

    const sliceResult = await sliceAudio(
      inputAudioPath,
      audioDir,
      plan || config.audio.chunkDurationSeconds,
      isCancelled,
      (msg) => log(`[Job #${jobId}] ${msg}`),
      async (current, total, filename, durationMin) => {
        try {
          await updateJobProgress(pool, jobId, {
            currentStep: `Memotong segmen ${current} dari ${total} (~${durationMin} menit)...`,
          });
        } catch {
          // Pembaruan progres pemotongan bersifat best effort
        }
      }
    );

    // 2. Tahap Transkripsi Sekuensial per Chunk
    await updateJobProgress(pool, jobId, {
      status: 'transcribing',
      currentStep: 'Memulai transkripsi audio per bagian...',
    });

    const chunkFiles = sliceResult.chunkFiles;
    const planStats = plan && Array.isArray(plan) ? plan : [];
    const statsFor = (index) => planStats.find((entry) => entry.index === index) || null;

    for (let i = 0; i < chunkFiles.length; i++) {
      const chunk = chunkFiles[i];
      const stats = statsFor(chunk.index);

      // Cek pembatalan sebelum setiap chunk
      if (await isCancelled()) {
        await markJobCancelled(pool, jobId, 'Dibatalkan saat transkripsi berlangsung');
        log(`[Worker] Job #${jobId} dibatalkan kooperatif pada chunk ke-${chunk.index}.`);
        return;
      }

      // Chunk hening dilewati hanya bila VAD_SKIP_SILENT_CHUNKS diaktifkan
      if (config.vad.skipSilentChunks && stats && isChunkSilent(stats, config.vad.skipSpeechRatio)) {
        log(`[Job #${jobId}] [VAD] Bagian ${chunk.index} hening (${(stats.ratio * 100).toFixed(1)}% bicara), dilewati atas konfigurasi VAD_SKIP_SILENT_CHUNKS.`);
        const progressSkipped = Math.round((chunk.index / totalChunks) * 75);
        await updateJobProgress(pool, jobId, {
          completedChunks: chunk.index,
          progressPercent: progressSkipped,
          currentStep: `Bagian ${chunk.index} dari ${totalChunks} hening, dilewati.`,
        });
        continue;
      }

      const chunkProgressBase = Math.round(((chunk.index - 1) / totalChunks) * 75);
      await updateJobProgress(pool, jobId, {
        progressPercent: chunkProgressBase,
        currentStep: `Mentranskripsikan bagian ${chunk.index} dari ${totalChunks}...`,
      });

      // Jalankan transkripsi chunk (dengan Files API, model fallback, dan penulisan atomik .part -> .txt)
      await transcribeChunkWithFallback({
        chunkPath: chunk.path,
        chunkIndex: chunk.index,
        totalChunks,
        durationSeconds: chunk.durationSeconds,
        speechSeconds: stats ? stats.speech_seconds : null,
        isLastChunk: (chunk.index === totalChunks),
        transcriptsDir,
        cancelChecker: isCancelled,
        onLog: (msg) => log(`[Job #${jobId}] ${msg}`),
      });

      const chunkProgressDone = Math.round((chunk.index / totalChunks) * 75);
      await updateJobProgress(pool, jobId, {
        completedChunks: chunk.index,
        progressPercent: chunkProgressDone,
        currentStep: `Bagian ${chunk.index} dari ${totalChunks} selesai.`,
      });

      // Jeda keamanan antar chunk jika diaktifkan dan bukan chunk terakhir
      if (config.audio.safetyDelayMs > 0 && i < chunkFiles.length - 1) {
        log(`[Job #${jobId}] Jeda keamanan ${config.audio.safetyDelayMs / 1000}s sebelum memproses chunk berikutnya...`);
        await interruptibleSleep(config.audio.safetyDelayMs, isCancelled);
      }
    }

    // 3. Tahap Penggabungan Transkrip & Penyusunan Risalah Rapat
    if (await isCancelled()) {
      await markJobCancelled(pool, jobId, 'Dibatalkan sebelum penyusunan risalah');
      return;
    }

    if (config.audio.safetyDelayMs > 0 && chunkFiles.length > 0) {
      log(`[Job #${jobId}] Jeda keamanan ${config.audio.safetyDelayMs / 1000}s sebelum menyusun risalah...`);
      await interruptibleSleep(config.audio.safetyDelayMs, isCancelled);
    }

    await updateJobProgress(pool, jobId, {
      status: 'summarizing',
      progressPercent: 80,
      currentStep: 'Membaca transkrip lengkap dan menyusun Risalah Rapat resmi via AI...',
    });

    // Baca seluruh file chunk_NNN.txt secara terurut alfabetis/numerik
    const transcriptFiles = fs.readdirSync(transcriptsDir)
      .filter((f) => f.startsWith('chunk_') && f.endsWith('.txt') && !f.endsWith('.part'))
      .sort();

    let fullTranscriptParts = [];
    for (const tFile of transcriptFiles) {
      const content = fs.readFileSync(path.join(transcriptsDir, tFile), 'utf-8').trim();
      if (content.length > 0) {
        fullTranscriptParts.push(`=== ${tFile.replace('.txt', '').toUpperCase()} ===\n${content}`);
      }
    }

    const fullTranscript = fullTranscriptParts.join('\n\n');
    if (fullTranscript.length === 0) {
      throw new Error('Seluruh berkas transkrip kosong atau tidak ditemukan.');
    }

    // Ambil metadata rapat (judul & tanggal)
    const meetingContext = await fetchMeetingContext(pool, job);

    // Generate draft Risalah Rapat via Gemini
    const minutesResult = await generateMeetingMinutesWithFallback({
      fullTranscript,
      metadata: {
        judul_rapat: meetingContext.judulRapat,
        tanggal_rapat: meetingContext.tanggalRapat,
        jadwal_type: job.jadwal_type,
      },
      cancelChecker: isCancelled,
      onLog: (msg) => log(`[Job #${jobId}] ${msg}`),
    });

    // 4. Simpan Hasil Risalah ke Database MySQL (meeting_minutes)
    const relativeTranscriptsDir = `recordings/job_${jobId}/transcripts`;
    await saveMeetingMinutes(pool, jobId, relativeTranscriptsDir, minutesResult);

    // 5. Tandai Job Completed & Bersihkan Chunk Audio Lokal
    await markJobCompleted(
      pool,
      jobId,
      'Selesai: Transkrip dan draft risalah siap ditinjau.',
      minutesResult.usedModel || null
    );

    cleanLocalAudioChunks(audioDir);

    log(`\n[Worker] SUKSES: Job #${jobId} (${meetingContext.judulRapat}) telah selesai 100%!\n`);
  } catch (err) {
    if (err instanceof JobCancelledError || (await isCancelled())) {
      log(`[Worker] Job #${jobId} berhasil dihentikan atas permintaan admin.`);
      await markJobCancelled(pool, jobId, 'Proses dihentikan oleh admin (dapat dilanjutkan kembali)');
      return;
    }
    throw err;
  }
}
