import fs from 'node:fs';
import path from 'node:path';
import mysql from 'mysql2/promise';
import { config } from './config.js';
import { sliceAudio, probeDuration, formatChunkIndex } from './services/audioSlicer.js';
import { transcribeChunkWithFallback, generateMeetingMinutesWithFallback, parsePillarsFromText } from './services/geminiService.js';
import { interruptibleSleep } from './services/throttler.js';
import { log, warn, error } from './services/logger.js';

// Abaikan error EPIPE jika worker dijalankan asinkron tanpa pipe terminal aktif (popen PHP)
process.stdout?.on('error', (err) => {
  if (err.code === 'EPIPE') return;
});
process.stderr?.on('error', (err) => {
  if (err.code === 'EPIPE') return;
});

let dbPool = null;

export async function getDbPool() {
  if (!dbPool) {
    dbPool = mysql.createPool(config.db);
  }
  return dbPool;
}

/**
 * Startup Reset: Mengembalikan job in-progress yang menggantung saat server restart
 * ke status 'queued'. Hanya job basi (updated_at lebih tua dari threshold) yang
 * di-reset, agar tidak membatalkan job yang sedang diproses worker lain.
 */
export async function performStartupReset(pool) {
  const cutoff = new Date(Date.now() - config.worker.staleThresholdMinutes * 60 * 1000);
  const [result] = await pool.execute(
    `UPDATE meeting_transcription_jobs
     SET status = 'queued', cancel_requested = 0, current_step = 'Menunggu antrean (dipulihkan dari restart)'
     WHERE status IN ('chunking', 'transcribing', 'summarizing') AND updated_at < ?`,
    [cutoff]
  );

  if (result.affectedRows > 0) {
    log(`[Worker] Startup Reset: ${result.affectedRows} job in-progress basi berhasil di-reset ke 'queued'.`);
  }
}

/**
 * Penanda job gagal dari handler error worker. Dijaga agar tidak menurunkan
 * status 'completed'/'cancelled' yang mungkin sudah ditulis worker lain.
 */
export async function markJobFailure(pool, jobId, err) {
  const message = String(err.message || err);
  const isCancelled = message.includes('JOB_CANCELLED_BY_ADMIN');
  const [result] = await pool.execute(
    `UPDATE meeting_transcription_jobs
     SET status = ?, cancel_requested = 0, error_message = ?, current_step = ?, updated_at = NOW()
     WHERE id = ? AND status NOT IN ('completed', 'cancelled')`,
    [
      isCancelled ? 'cancelled' : 'failed',
      isCancelled ? null : message,
      isCancelled ? 'Proses dihentikan oleh admin' : ('Gagal: ' + message.slice(0, 150)),
      jobId
    ]
  );
  return result.affectedRows;
}

/**
 * Mencari 1 kandidat job dari antrean (FIFO). Klaim atomik dilakukan
 * di dalam processJob agar mode single-job dan daemon memakai jalur yang sama.
 */
export async function nextQueuedJobCandidate(pool) {
  const [rows] = await pool.query(
    `SELECT * FROM meeting_transcription_jobs
     WHERE status = 'queued' AND cancel_requested = 0
     ORDER BY id ASC
     LIMIT 1`
  );

  return rows[0] || null;
}

/**
 * Membaca metadata konteks rapat dari database (judul rapat & tanggal).
 */
async function fetchMeetingContext(pool, job) {
  let judulRapat = job.audio_filename || `Rapat ID #${job.id}`;
  let tanggalRapat = new Date().toISOString().slice(0, 10);

  try {
    if (job.jadwal_type === 'umum' && job.jadwal_id) {
      const [rows] = await pool.query(
        `SELECT judul, tanggal FROM jadwal_umum WHERE id = ?`,
        [job.jadwal_id]
      );
      if (rows && rows.length > 0) {
        judulRapat = rows[0].judul;
        tanggalRapat = rows[0].tanggal instanceof Date
          ? rows[0].tanggal.toISOString().slice(0, 10)
          : String(rows[0].tanggal);
      }
    } else if (job.jadwal_type === 'banmus' && job.jadwal_id) {
      const [rows] = await pool.query(
        `SELECT agenda, tanggal FROM jadwal_banmus WHERE id = ?`,
        [job.jadwal_id]
      );
      if (rows && rows.length > 0) {
        judulRapat = rows[0].agenda;
        tanggalRapat = rows[0].tanggal instanceof Date
          ? rows[0].tanggal.toISOString().slice(0, 10)
          : String(rows[0].tanggal);
      }
    }
  } catch (err) {
    warn(`[Worker] Peringatan membaca konteks jadwal #${job.jadwal_id}:`, err.message);
  }

  return { judulRapat, tanggalRapat };
}

/**
 * Membuat cancel-checker yang mengecek kolom cancel_requested secara langsung di DB.
 */
function createCancelChecker(pool, jobId) {
  let lastCheckTime = 0;
  let cachedValue = false;
  return async () => {
    const now = Date.now();
    if (now - lastCheckTime < 500) {
      return cachedValue;
    }
    lastCheckTime = now;
    try {
      const [rows] = await pool.query(
        `SELECT cancel_requested FROM meeting_transcription_jobs WHERE id = ?`,
        [jobId]
      );
      cachedValue = Boolean(rows?.[0]?.cancel_requested);
      return cachedValue;
    } catch {
      return cachedValue;
    }
  };
}

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
 * Eksekutor pemrosesan satu job rapat.
 */
export async function processJob(pool, job) {
  const jobId = job.id;
  const isCancelled = createCancelChecker(pool, jobId);

  // Klaim atomik: hanya worker yang berhasil mengubah status dari 'queued'
  // yang boleh memproses job ini.
  const [claimResult] = await pool.execute(
    `UPDATE meeting_transcription_jobs
     SET status = 'chunking', current_step = 'Menginisialisasi pemrosesan audio...', updated_at = NOW()
     WHERE id = ? AND status = 'queued'`,
    [jobId]
  );

  if (claimResult.affectedRows === 0) {
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

    // Tentukan path file input rekaman
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
      // Coba fallback ke lokasi standard audio/original.mp3
      const standardPath = path.join(audioDir, 'original.mp3');
      if (fs.existsSync(standardPath)) {
        inputAudioPath = standardPath;
      } else {
        throw new Error(`Berkas rekaman audio tidak ditemukan di path: ${inputAudioPath}`);
      }
    }

    // Periksa apakah ada permintaan pembatalan awal
    if (await isCancelled()) {
      await pool.execute(
        `UPDATE meeting_transcription_jobs SET status = 'cancelled', cancel_requested = 0, error_message = NULL, current_step = 'Dibatalkan oleh admin', updated_at = NOW() WHERE id = ?`,
        [jobId]
      );
      log(`[Worker] Job #${jobId} dibatalkan oleh admin sebelum pemrosesan dimulai.`);
      return;
    }

    // 1. Tahap Probe Durasi & Chunking Audio
    log(`[Worker] Mengukur durasi audio via ffprobe...`);
    const totalDuration = await probeDuration(inputAudioPath);
    const totalChunks = Math.max(1, Math.ceil(totalDuration / config.audio.chunkDurationSeconds));

    await pool.execute(
      `UPDATE meeting_transcription_jobs
       SET audio_duration = ?, total_chunks = ?, current_step = 'Memotong rekaman menjadi ${totalChunks} segmen (per 30 menit)...', updated_at = NOW()
       WHERE id = ?`,
      [totalDuration, totalChunks, jobId]
    );

    log(`[Worker] Durasi total: ${totalDuration}s (${Math.round(totalDuration / 60)} menit), Total chunk: ${totalChunks}`);
    const sliceResult = await sliceAudio(inputAudioPath, audioDir, config.audio.chunkDurationSeconds, isCancelled);

    // 2. Tahap Transkripsi Sekuensial per Chunk
    await pool.execute(
      `UPDATE meeting_transcription_jobs
       SET status = 'transcribing', current_step = 'Memulai transkripsi audio per bagian...', updated_at = NOW()
       WHERE id = ?`,
      [jobId]
    );

    const chunkFiles = sliceResult.chunkFiles;
    for (let i = 0; i < chunkFiles.length; i++) {
      const chunk = chunkFiles[i];

      // Cek pembatalan sebelum setiap chunk
      if (await isCancelled()) {
        await pool.execute(
          `UPDATE meeting_transcription_jobs SET status = 'cancelled', cancel_requested = 0, error_message = NULL, current_step = 'Dibatalkan saat transkripsi berlangsung', updated_at = NOW() WHERE id = ?`,
          [jobId]
        );
        log(`[Worker] Job #${jobId} dibatalkan kooperatif pada chunk ke-${chunk.index}.`);
        return;
      }

      const chunkProgressBase = Math.round(((chunk.index - 1) / totalChunks) * 75);
      await pool.execute(
        `UPDATE meeting_transcription_jobs
         SET current_step = 'Mentranskripsikan bagian ${chunk.index} dari ${totalChunks}...', progress_percent = ?, updated_at = NOW()
         WHERE id = ?`,
        [chunkProgressBase, jobId]
      );

      // Jalankan transkripsi chunk (dengan Files API, model fallback, dan penulisan atomik .part -> .txt)
      await transcribeChunkWithFallback({
        chunkPath: chunk.path,
        chunkIndex: chunk.index,
        totalChunks,
        durationSeconds: chunk.durationSeconds,
        isLastChunk: (chunk.index === totalChunks),
        transcriptsDir,
        cancelChecker: isCancelled,
        onLog: (msg) => log(`[Job #${jobId}] ${msg}`),
      });

      const chunkProgressDone = Math.round((chunk.index / totalChunks) * 75);
      await pool.execute(
        `UPDATE meeting_transcription_jobs
         SET completed_chunks = ?, progress_percent = ?, current_step = 'Bagian ${chunk.index} dari ${totalChunks} selesai.', updated_at = NOW()
         WHERE id = ?`,
        [chunk.index, chunkProgressDone, jobId]
      );

      // Jeda keamanan antar chunk (Safety Delay 8 detik) jika bukan chunk terakhir
      if (i < chunkFiles.length - 1) {
        log(`[Job #${jobId}] Jeda keamanan ${config.audio.safetyDelayMs / 1000}s sebelum memproses chunk berikutnya...`);
        await interruptibleSleep(config.audio.safetyDelayMs, isCancelled);
      }
    }

    // 3. Tahap Penggabungan Transkrip & Penyusunan Risalah Rapat
    if (await isCancelled()) {
      await pool.execute(
        `UPDATE meeting_transcription_jobs SET status = 'cancelled', cancel_requested = 0, error_message = NULL, current_step = 'Dibatalkan sebelum penyusunan risalah', updated_at = NOW() WHERE id = ?`,
        [jobId]
      );
      return;
    }

    await pool.execute(
      `UPDATE meeting_transcription_jobs
       SET status = 'summarizing', progress_percent = 80, current_step = 'Membaca transkrip lengkap dan menyusun Risalah Rapat resmi via AI...', updated_at = NOW()
       WHERE id = ?`,
      [jobId]
    );

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

    // Cek apakah row minutes sudah ada sebelumnya untuk job ini
    const [existingMinutes] = await pool.query(
      `SELECT id FROM meeting_minutes WHERE job_id = ?`,
      [jobId]
    );

    const pillars = parsePillarsFromText(minutesResult.ringkasan_eksekutif);
    const strukturJsonStr = JSON.stringify(pillars);

    if (existingMinutes && existingMinutes.length > 0) {
      await pool.execute(
        `UPDATE meeting_minutes
         SET transcripts_dir = ?, ringkasan_eksekutif = ?, struktur_json = ?, updated_at = NOW()
         WHERE id = ?`,
        [
          relativeTranscriptsDir,
          minutesResult.ringkasan_eksekutif,
          strukturJsonStr,
          existingMinutes[0].id,
        ]
      );
    } else {
      await pool.execute(
        `INSERT INTO meeting_minutes
         (job_id, transcripts_dir, ringkasan_eksekutif, struktur_json, status_verifikasi, created_at, updated_at)
         VALUES (?, ?, ?, ?, 'draft', NOW(), NOW())`,
        [
          jobId,
          relativeTranscriptsDir,
          minutesResult.ringkasan_eksekutif,
          strukturJsonStr,
        ]
      );
    }

    // 5. Tandai Job Completed & Bersihkan Chunk Audio Lokal
    await pool.execute(
      `UPDATE meeting_transcription_jobs
       SET status = 'completed', progress_percent = 100, current_step = 'Selesai: Transkrip dan draft risalah siap ditinjau.', ai_model = ?, error_message = NULL, updated_at = NOW()
       WHERE id = ? AND status NOT IN ('cancelled')`,
      [minutesResult.usedModel || null, jobId]
    );

    cleanLocalAudioChunks(audioDir);

    log(`\n[Worker] SUKSES: Job #${jobId} (${meetingContext.judulRapat}) telah selesai 100%!\n`);
  } catch (err) {
    if (err.message === 'JOB_CANCELLED_BY_ADMIN' || (await isCancelled())) {
      log(`[Worker] Job #${jobId} berhasil dihentikan atas permintaan admin.`);
      await pool.execute(
        `UPDATE meeting_transcription_jobs
         SET status = 'cancelled', cancel_requested = 0, error_message = NULL, current_step = 'Proses dihentikan oleh admin (dapat dilanjutkan kembali)', updated_at = NOW()
         WHERE id = ?`,
        [jobId]
      );
      return;
    }
    throw err;
  }
}

/**
 * Runner Utama Worker
 */
async function main() {
  const args = process.argv.slice(2);
  const isDaemon = args.includes('--daemon');
  const jobIdArg = args.find((a) => a.startsWith('--job-id='));

  log('------------------------------------------------------------');
  log('[Worker] DPRD Signage AI Background Worker Engine');
  log(`Model Chain: ${config.gemini.modelChain.join(' -> ')}`);
  log(`Database: ${config.db.user}@${config.db.host}:${config.db.port}/${config.db.database}`);
  log('------------------------------------------------------------');

  const pool = await getDbPool();

  // Registrasi handler sinyal penghentian proses (Ctrl+C / Kill)
  const handleExitSignal = async (signal) => {
    log(`\n[Worker] Menerima sinyal ${signal} (Ctrl+C). Menghentikan worker segera...`);
    if (pool) {
      try {
        await pool.end();
      } catch {}
    }
    process.exit(0);
  };

  process.on('SIGINT', () => handleExitSignal('SIGINT'));
  process.on('SIGTERM', () => handleExitSignal('SIGTERM'));

  // Mode 1: Single Job manual
  if (jobIdArg) {
    const targetJobId = parseInt(jobIdArg.split('=')[1], 10);
    const [rows] = await pool.query(
      `SELECT * FROM meeting_transcription_jobs WHERE id = ?`,
      [targetJobId]
    );
    if (!rows || rows.length === 0) {
      error(`[Worker] Job ID #${targetJobId} tidak ditemukan di database.`);
      process.exit(1);
    }
    try {
      await processJob(pool, rows[0]);
      process.exit(0);
    } catch (err) {
      error(`[Worker] Job #${targetJobId} selesai dengan error:`, err);
      const isCancelled = String(err.message || err).includes('JOB_CANCELLED_BY_ADMIN');
      await markJobFailure(pool, targetJobId, err);
      process.exit(isCancelled ? 0 : 1);
    }
  }

  // Mode 2: Daemon Worker Loop (PM2 / background service)
  await performStartupReset(pool);
  log(`[Worker] Daemon aktif. Memantau antrean task (polling interval ${config.worker.pollIntervalMs / 1000}s)...`);

  let isRunning = true;

  while (isRunning) {
    try {
      const job = await nextQueuedJobCandidate(pool);
      if (job) {
        try {
          await processJob(pool, job);
        } catch (jobErr) {
          error(`[Worker] Error saat memproses Job #${job.id}:`, jobErr);
          await markJobFailure(pool, job.id, jobErr);
        }
      } else {
        if (!isDaemon) {
          log('[Worker] Tidak ada job dalam antrean. Selesai.');
          break;
        }
        await interruptibleSleep(config.worker.pollIntervalMs);
      }
    } catch (loopErr) {
      error('[Worker] Terjadi kesalahan pada loop worker:', loopErr.message);
      await interruptibleSleep(config.worker.pollIntervalMs);
    }
  }

  if (dbPool) {
    await dbPool.end();
  }
  log('[Worker] Worker telah berhenti.');
}

if (process.argv[1] && process.argv[1].endsWith('worker.js')) {
  main().catch((err) => {
    error('[Worker] Fatal Error:', err);
    process.exit(1);
  });
}
