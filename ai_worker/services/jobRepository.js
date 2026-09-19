import { config } from '../config.js';
import { JobCancelledError } from './throttler.js';
import { log, warn } from './logger.js';

/**
 * Akses data job transkripsi (meeting_transcription_jobs, meeting_minutes).
 * Seluruh pernyataan SQL pekerjaan terkonsentrasi di sini; pipeline hanya
 * memanggil fungsi-fungsi modul ini.
 */

/**
 * Pemulihan job in-progress yang menggantung (worker mati/crash saat
 * memproses) ke status 'queued'. Dijalankan saat startup dan secara berkala
 * oleh daemon. Job yang sedang diproses worker ini dikecualikan agar tidak
 * di-reset di tengah proses yang masih berjalan.
 *
 * Saat kunci instance tunggal menjamin tidak ada worker lain
 * (ignoreStaleness = true), semua job in-progress dipulihkan tanpa
 * menunggu ambang kebasian - resume langsung setelah restart.
 */
export async function performStartupReset(pool, excludeJobId = null, ignoreStaleness = false) {
  const params = [];
  let sql =
    `UPDATE meeting_transcription_jobs
     SET status = 'queued', cancel_requested = 0, current_step = 'Menunggu antrean (dipulihkan dari kondisi macet)'
     WHERE status IN ('chunking', 'transcribing', 'summarizing')`;
  if (!ignoreStaleness) {
    sql += ' AND updated_at < ?';
    params.push(new Date(Date.now() - config.worker.staleThresholdMinutes * 60 * 1000));
  }
  if (excludeJobId) {
    sql += ' AND id <> ?';
    params.push(excludeJobId);
  }
  const [result] = await pool.execute(sql, params);

  if (result.affectedRows > 0) {
    log(`[Worker] Pemulihan job basi: ${result.affectedRows} job in-progress di-reset ke 'queued'.`);
  }
}

/**
 * Penanda job gagal dari handler error worker. Dijaga agar tidak menurunkan
 * status 'completed'/'cancelled' yang mungkin sudah ditulis worker lain.
 */
export async function markJobFailure(pool, jobId, err) {
  const message = String(err.message || err);
  const isCancelled = err instanceof JobCancelledError;
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
 * Mengambil satu job berdasarkan ID, atau null bila tidak ada.
 */
export async function getJobById(pool, jobId) {
  const [rows] = await pool.query(
    `SELECT * FROM meeting_transcription_jobs WHERE id = ?`,
    [jobId]
  );
  return rows[0] || null;
}

/**
 * Klaim atomik: hanya worker yang berhasil mengubah status dari 'queued'
 * yang boleh memproses job ini. Mengembalikan true bila klaim berhasil.
 */
export async function claimJob(pool, jobId) {
  const [claimResult] = await pool.execute(
    `UPDATE meeting_transcription_jobs
     SET status = 'chunking', current_step = ?, updated_at = NOW()
     WHERE id = ? AND status = 'queued'`,
    ['Menginisialisasi pemrosesan audio...', jobId]
  );
  return claimResult.affectedRows > 0;
}

/**
 * Pembaruan progres job sebagian. Hanya field yang dikirim yang di-set;
 * updated_at selalu diperbarui.
 */
export async function updateJobProgress(pool, jobId, fields = {}) {
  const sets = [];
  const params = [];

  if (fields.status !== undefined) {
    sets.push('status = ?');
    params.push(fields.status);
  }
  if (fields.currentStep !== undefined) {
    sets.push('current_step = ?');
    params.push(fields.currentStep);
  }
  if (fields.progressPercent !== undefined) {
    sets.push('progress_percent = ?');
    params.push(fields.progressPercent);
  }
  if (fields.completedChunks !== undefined) {
    sets.push('completed_chunks = ?');
    params.push(fields.completedChunks);
  }
  if (fields.audioDuration !== undefined) {
    sets.push('audio_duration = ?');
    params.push(fields.audioDuration);
  }
  if (fields.totalChunks !== undefined) {
    sets.push('total_chunks = ?');
    params.push(fields.totalChunks);
  }
  if (fields.aiModel !== undefined) {
    sets.push('ai_model = ?');
    params.push(fields.aiModel);
  }

  sets.push('updated_at = NOW()');
  params.push(jobId);

  await pool.execute(
    `UPDATE meeting_transcription_jobs SET ${sets.join(', ')} WHERE id = ?`,
    params
  );
}

/**
 * Menandai job dibatalkan admin dan membersihkan flag cancel_requested.
 * Dijaga agar tidak menurunkan status 'completed'.
 */
export async function markJobCancelled(pool, jobId, currentStep) {
  await pool.execute(
    `UPDATE meeting_transcription_jobs
     SET status = 'cancelled', cancel_requested = 0, error_message = NULL, current_step = ?, updated_at = NOW()
     WHERE id = ? AND status NOT IN ('completed')`,
    [currentStep, jobId]
  );
}

/**
 * Menandai job selesai dengan model AI yang dipakai. Tidak menurunkan
 * status 'cancelled' yang mungkin sudah ditulis.
 */
export async function markJobCompleted(pool, jobId, currentStep, aiModel) {
  await pool.execute(
    `UPDATE meeting_transcription_jobs
     SET status = 'completed', progress_percent = 100, current_step = ?, ai_model = ?, error_message = NULL, updated_at = NOW()
     WHERE id = ? AND status NOT IN ('cancelled')`,
    [currentStep, aiModel, jobId]
  );
}

/**
 * Membaca metadata konteks rapat dari database (judul rapat & tanggal).
 */
export async function fetchMeetingContext(pool, job) {
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
export function createCancelChecker(pool, jobId) {
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
 * Menyimpan (insert atau update) draft risalah untuk satu job.
 */
export async function saveMeetingMinutes(pool, jobId, transcriptsDirRelative, minutesResult) {
  // Cek apakah row minutes sudah ada sebelumnya untuk job ini
  const [existingMinutes] = await pool.query(
    `SELECT id FROM meeting_minutes WHERE job_id = ?`,
    [jobId]
  );

  // Struktur 3 pilar dijamin responseSchema dari model, bukan hasil parsing regex
  const strukturJsonStr = JSON.stringify(minutesResult.pillars);

  if (existingMinutes && existingMinutes.length > 0) {
    await pool.execute(
      `UPDATE meeting_minutes
       SET transcripts_dir = ?, ringkasan_eksekutif = ?, struktur_json = ?, updated_at = NOW()
       WHERE id = ?`,
      [
        transcriptsDirRelative,
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
        transcriptsDirRelative,
        minutesResult.ringkasan_eksekutif,
        strukturJsonStr,
      ]
    );
  }
}
