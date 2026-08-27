import fs from 'node:fs';
import path from 'node:path';
import mysql from 'mysql2/promise';
import { config } from './config.js';
import { sliceAudio, probeDuration, formatChunkIndex } from './services/audioSlicer.js';
import { transcribeChunkWithFallback, generateMeetingMinutesWithFallback } from './services/geminiService.js';
import { interruptibleSleep } from './services/throttler.js';

let dbPool = null;

export async function getDbPool() {
  if (!dbPool) {
    dbPool = mysql.createPool(config.db);
  }
  return dbPool;
}

/**
 * Startup Reset: Mengembalikan seluruh job in-progress yang menggantung saat server restart ke status 'queued'.
 */
export async function performStartupReset(pool) {
  const [result] = await pool.execute(
    `UPDATE meeting_transcription_jobs
     SET status = 'queued', cancel_requested = 0, current_step = 'Menunggu antrean (dipulihkan dari restart)'
     WHERE status IN ('chunking', 'transcribing', 'summarizing')`
  );

  if (result.affectedRows > 0) {
    console.log(`[Worker] Startup Reset: ${result.affectedRows} job in-progress berhasil di-reset ke 'queued'.`);
  }
}

/**
 * Klaim 1 job dari antrean secara atomik (Strict Concurrency = 1 FIFO).
 */
export async function claimNextQueuedJob(pool) {
  const [rows] = await pool.query(
    `SELECT id FROM meeting_transcription_jobs
     WHERE status = 'queued' AND cancel_requested = 0
     ORDER BY id ASC
     LIMIT 1`
  );

  if (!rows || rows.length === 0) {
    return null;
  }

  const candidateId = rows[0].id;
  const [updateResult] = await pool.execute(
    `UPDATE meeting_transcription_jobs
     SET status = 'chunking', current_step = 'Menginisialisasi pemrosesan audio...', updated_at = NOW()
     WHERE id = ? AND status = 'queued'`,
    [candidateId]
  );

  if (updateResult.affectedRows === 1) {
    const [claimedRows] = await pool.query(
      `SELECT * FROM meeting_transcription_jobs WHERE id = ?`,
      [candidateId]
    );
    return claimedRows[0] || null;
  }

  return null;
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
        `SELECT agenda, tanggal FROM jadwal_banmus_item WHERE id = ?`,
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
    console.warn(`[Worker] Peringatan membaca konteks jadwal #${job.jadwal_id}:`, err.message);
  }

  return { judulRapat, tanggalRapat };
}

/**
 * Membuat cancel-checker yang mengecek kolom cancel_requested secara langsung di DB.
 */
function createCancelChecker(pool, jobId) {
  return async () => {
    try {
      const [rows] = await pool.query(
        `SELECT cancel_requested FROM meeting_transcription_jobs WHERE id = ?`,
        [jobId]
      );
      return Boolean(rows?.[0]?.cancel_requested);
    } catch {
      return false;
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
    console.log(`[Worker] Retensi Disk: File potongan chunk audio lokal di ${audioDir} berhasil dibersihkan.`);
  } catch (err) {
    console.warn(`[Worker] Peringatan saat membersihkan chunk lokal:`, err.message);
  }
}

/**
 * Eksekutor pemrosesan satu job rapat.
 */
export async function processJob(pool, job) {
  const jobId = job.id;
  const isCancelled = createCancelChecker(pool, jobId);

  console.log(`\n========================================================`);
  console.log(`[Worker] Memulai Pemrosesan Job ID #${jobId} (${job.audio_filename})`);
  console.log(`========================================================`);

  const jobDir = path.join(config.paths.recordingsBaseDir, `job_${jobId}`);
  const audioDir = path.join(jobDir, 'audio');
  const transcriptsDir = path.join(jobDir, 'transcripts');

  // Pastikan struktur folder tersedia
  fs.mkdirSync(audioDir, { recursive: true });
  fs.mkdirSync(transcriptsDir, { recursive: true });

  // Tentukan path file input rekaman
  let inputAudioPath = job.audio_path;
  if (!path.isAbsolute(inputAudioPath)) {
    inputAudioPath = path.resolve(config.paths.root, inputAudioPath);
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
      `UPDATE meeting_transcription_jobs SET status = 'cancelled', current_step = 'Dibatalkan oleh admin' WHERE id = ?`,
      [jobId]
    );
    console.log(`[Worker] Job #${jobId} dibatalkan oleh admin sebelum pemrosesan dimulai.`);
    return;
  }

  // 1. Tahap Probe Durasi & Chunking Audio
  console.log(`[Worker] Mengukur durasi audio via ffprobe...`);
  const totalDuration = await probeDuration(inputAudioPath);
  const totalChunks = Math.max(1, Math.ceil(totalDuration / config.audio.chunkDurationSeconds));

  await pool.execute(
    `UPDATE meeting_transcription_jobs
     SET audio_duration = ?, total_chunks = ?, current_step = 'Memotong rekaman menjadi ${totalChunks} segmen (per 30 menit)...', updated_at = NOW()
     WHERE id = ?`,
    [totalDuration, totalChunks, jobId]
  );

  console.log(`[Worker] Durasi total: ${totalDuration}s (${Math.round(totalDuration / 60)} menit), Total chunk: ${totalChunks}`);
  const sliceResult = await sliceAudio(inputAudioPath, audioDir, config.audio.chunkDurationSeconds);

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
        `UPDATE meeting_transcription_jobs SET status = 'cancelled', current_step = 'Dibatalkan saat transkripsi berlangsung' WHERE id = ?`,
        [jobId]
      );
      console.log(`[Worker] Job #${jobId} dibatalkan kooperatif pada chunk ke-${chunk.index}.`);
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
      transcriptsDir,
      cancelChecker: isCancelled,
      onLog: (msg) => console.log(`[Job #${jobId}] ${msg}`),
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
      console.log(`[Job #${jobId}] Jeda keamanan ${config.audio.safetyDelayMs / 1000}s sebelum memproses chunk berikutnya...`);
      await interruptibleSleep(config.audio.safetyDelayMs, isCancelled);
    }
  }

  // 3. Tahap Penggabungan Transkrip & Penyusunan Risalah Rapat
  if (await isCancelled()) {
    await pool.execute(
      `UPDATE meeting_transcription_jobs SET status = 'cancelled', current_step = 'Dibatalkan sebelum penyusunan risalah' WHERE id = ?`,
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
    onLog: (msg) => console.log(`[Job #${jobId}] ${msg}`),
  });

  // 4. Simpan Hasil Risalah ke Database MySQL (meeting_minutes)
  const relativeTranscriptsDir = `recordings/job_${jobId}/transcripts`;

  // Cek apakah row minutes sudah ada sebelumnya untuk job ini
  const [existingMinutes] = await pool.query(
    `SELECT id FROM meeting_minutes WHERE job_id = ?`,
    [jobId]
  );

  if (existingMinutes && existingMinutes.length > 0) {
    await pool.execute(
      `UPDATE meeting_minutes
       SET judul_rapat = ?, tanggal_rapat = ?, transcripts_dir = ?,
           ringkasan_eksekutif = ?, agenda_pembahasan = ?, kesimpulan = ?,
           tindak_lanjut = ?, peserta_terdeteksi = ?, updated_at = NOW()
       WHERE id = ?`,
      [
        meetingContext.judulRapat,
        meetingContext.tanggalRapat,
        relativeTranscriptsDir,
        minutesResult.ringkasan_eksekutif,
        JSON.stringify(minutesResult.agenda_pembahasan),
        JSON.stringify(minutesResult.kesimpulan),
        JSON.stringify(minutesResult.tindak_lanjut),
        JSON.stringify(minutesResult.peserta_terdeteksi),
        existingMinutes[0].id,
      ]
    );
  } else {
    await pool.execute(
      `INSERT INTO meeting_minutes
       (job_id, jadwal_type, jadwal_id, judul_rapat, tanggal_rapat, transcripts_dir,
        ringkasan_eksekutif, agenda_pembahasan, kesimpulan, tindak_lanjut, peserta_terdeteksi,
        status_verifikasi, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW(), NOW())`,
      [
        jobId,
        job.jadwal_type || 'umum',
        job.jadwal_id || null,
        meetingContext.judulRapat,
        meetingContext.tanggalRapat,
        relativeTranscriptsDir,
        minutesResult.ringkasan_eksekutif,
        JSON.stringify(minutesResult.agenda_pembahasan),
        JSON.stringify(minutesResult.kesimpulan),
        JSON.stringify(minutesResult.tindak_lanjut),
        JSON.stringify(minutesResult.peserta_terdeteksi),
      ]
    );
  }

  // 5. Tandai Job Completed & Bersihkan Chunk Audio Lokal
  await pool.execute(
    `UPDATE meeting_transcription_jobs
     SET status = 'completed', progress_percent = 100, current_step = 'Selesai: Transkrip dan draft risalah siap ditinjau.', error_message = NULL, updated_at = NOW()
     WHERE id = ?`,
    [jobId]
  );

  cleanLocalAudioChunks(audioDir);

  console.log(`\n[Worker] SUKSES: Job #${jobId} (${meetingContext.judulRapat}) telah selesai 100%!\n`);
}

/**
 * Runner Utama Worker
 */
async function main() {
  const args = process.argv.slice(2);
  const isDaemon = args.includes('--daemon');
  const jobIdArg = args.find((a) => a.startsWith('--job-id='));

  console.log('------------------------------------------------------------');
  console.log('[Worker] DPRD Signage AI Background Worker Engine');
  console.log(`Model Chain: ${config.gemini.modelChain.join(' -> ')}`);
  console.log(`Database: ${config.db.user}@${config.db.host}:${config.db.port}/${config.db.database}`);
  console.log('------------------------------------------------------------');

  const pool = await getDbPool();

  // Mode 1: Single Job manual
  if (jobIdArg) {
    const targetJobId = parseInt(jobIdArg.split('=')[1], 10);
    const [rows] = await pool.query(
      `SELECT * FROM meeting_transcription_jobs WHERE id = ?`,
      [targetJobId]
    );
    if (!rows || rows.length === 0) {
      console.error(`[Worker] Job ID #${targetJobId} tidak ditemukan di database.`);
      process.exit(1);
    }
    try {
      await processJob(pool, rows[0]);
      process.exit(0);
    } catch (err) {
      console.error(`[Worker] Job #${targetJobId} gagal:`, err);
      await pool.execute(
        `UPDATE meeting_transcription_jobs SET status = 'failed', error_message = ?, current_step = 'Gagal: ' + ? WHERE id = ?`,
        [err.message, err.message, targetJobId]
      );
      process.exit(1);
    }
  }

  // Mode 2: Daemon Worker Loop (PM2 / background service)
  await performStartupReset(pool);
  console.log(`[Worker] Daemon aktif. Memantau antrean task (polling interval ${config.worker.pollIntervalMs / 1000}s)...`);

  let isRunning = true;

  process.on('SIGINT', async () => {
    console.log('\n[Worker] Menerima sinyal SIGINT. Menghentikan worker secara bersih...');
    isRunning = false;
  });
  process.on('SIGTERM', async () => {
    console.log('\n[Worker] Menerima sinyal SIGTERM. Menghentikan worker secara bersih...');
    isRunning = false;
  });

  while (isRunning) {
    try {
      const job = await claimNextQueuedJob(pool);
      if (job) {
        try {
          await processJob(pool, job);
        } catch (jobErr) {
          console.error(`[Worker] Error saat memproses Job #${job.id}:`, jobErr);
          const errorMsg = String(jobErr.message || jobErr);
          const finalStatus = errorMsg.includes('JOB_CANCELLED_BY_ADMIN') ? 'cancelled' : 'failed';
          await pool.execute(
            `UPDATE meeting_transcription_jobs
             SET status = ?, error_message = ?, current_step = CONCAT('Gagal: ', SUBSTRING(?, 1, 200)), updated_at = NOW()
             WHERE id = ?`,
            [finalStatus, errorMsg, errorMsg, job.id]
          );
        }
      } else {
        if (!isDaemon) {
          console.log('[Worker] Tidak ada job dalam antrean. Selesai.');
          break;
        }
        await interruptibleSleep(config.worker.pollIntervalMs);
      }
    } catch (loopErr) {
      console.error('[Worker] Terjadi kesalahan pada loop worker:', loopErr.message);
      await interruptibleSleep(config.worker.pollIntervalMs);
    }
  }

  if (dbPool) {
    await dbPool.end();
  }
  console.log('[Worker] Worker telah berhenti.');
}

if (process.argv[1] && process.argv[1].endsWith('worker.js')) {
  main().catch((err) => {
    console.error('[Worker] Fatal Error:', err);
    process.exit(1);
  });
}
