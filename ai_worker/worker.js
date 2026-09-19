import { config } from './config.js';
import { getDbPool, closeDbPool } from './services/db.js';
import { acquireDaemonLock, releaseDaemonLock } from './services/daemonLock.js';
import { interruptibleSleep, JobCancelledError } from './services/throttler.js';
import { log, warn, error } from './services/logger.js';
import {
  getJobById,
  markJobFailure,
  nextQueuedJobCandidate,
  performStartupReset,
} from './services/jobRepository.js';
import { processJob } from './services/pipeline.js';

// Abaikan error EPIPE jika worker dijalankan asinkron tanpa pipe terminal aktif (popen PHP)
process.stdout?.on('error', (err) => {
  if (err.code === 'EPIPE') return;
});
process.stderr?.on('error', (err) => {
  if (err.code === 'EPIPE') return;
});

/**
 * Runner Utama Worker
 */
async function main() {
  const args = process.argv.slice(2);
  const isDaemon = args.includes('--daemon') || Boolean(process.env.pm_id);
  const jobIdArg = args.find((a) => a.startsWith('--job-id='));

  log('------------------------------------------------------------');
  log('[Worker] DPRD Signage AI Background Worker Engine');
  log(`Model Chain: ${config.gemini.modelChain.join(' -> ')}`);
  log(`Database: ${config.db.user}@${config.db.host}:${config.db.port}/${config.db.database}`);
  log('------------------------------------------------------------');

  const pool = await getDbPool();

  let sweepTimer = null;

  // Registrasi handler sinyal penghentian proses (Ctrl+C / Kill)
  const handleExitSignal = async (signal) => {
    log(`\n[Worker] Menerima sinyal ${signal} (Ctrl+C). Menghentikan worker segera...`);
    releaseDaemonLock();
    if (sweepTimer) {
      clearInterval(sweepTimer);
    }
    try {
      await closeDbPool();
    } catch {
      // Koneksi bisa saja sudah tertutup saat shutdown
    }
    process.exit(0);
  };

  process.on('SIGINT', () => handleExitSignal('SIGINT'));
  process.on('SIGTERM', () => handleExitSignal('SIGTERM'));
  process.on('exit', releaseDaemonLock);

  // Mode 1: Single Job manual
  if (jobIdArg) {
    const targetJobId = parseInt(jobIdArg.split('=')[1], 10);
    const lock = acquireDaemonLock();
    if (!lock.acquired) {
      log(`[Worker] Daemon sedang aktif (PID ${lock.pid}). Job #${targetJobId} sudah diantrekan dan akan diproses otomatis oleh daemon.`);
      process.exit(0);
    }

    const job = await getJobById(pool, targetJobId);
    if (!job) {
      error(`[Worker] Job ID #${targetJobId} tidak ditemukan di database.`);
      process.exit(1);
    }
    try {
      await processJob(pool, job);
      process.exit(0);
    } catch (err) {
      error(`[Worker] Job #${targetJobId} selesai dengan error:`, err);
      const isCancelled = err instanceof JobCancelledError;
      await markJobFailure(pool, targetJobId, err);
      process.exit(isCancelled ? 0 : 1);
    }
  }

  // Mode 2: Daemon Worker Loop (PM2 / background service)
  const lock = acquireDaemonLock();
  if (!lock.acquired) {
    error(`[Worker] Daemon sudah berjalan (PID ${lock.pid}). Matikan daemon tersebut dulu agar tidak terjadi double-claim antrean.`);
    process.exit(1);
  }

  await performStartupReset(pool, null, true);
  log(`[Worker] Daemon aktif. Memantau antrean task (polling interval ${config.worker.pollIntervalMs / 1000}s)...`);

  let isRunning = true;
  let activeJobId = null;

  // Sweep berkala: pulihkan job macet (mis. worker mati saat memproses lalu
  // restart terjadi < threshold kemudian) tanpa menunggu restart berikutnya.
  const STALE_SWEEP_INTERVAL_MS = 60_000;
  sweepTimer = setInterval(() => {
    performStartupReset(pool, activeJobId).catch((err) => {
      warn('[Worker] Sweep job basi gagal:', err.message);
    });
  }, STALE_SWEEP_INTERVAL_MS);

  while (isRunning) {
    try {
      const job = await nextQueuedJobCandidate(pool);
      if (job) {
        activeJobId = job.id;
        try {
          await processJob(pool, job);
        } catch (jobErr) {
          error(`[Worker] Error saat memproses Job #${job.id}:`, jobErr);
          await markJobFailure(pool, job.id, jobErr);
        } finally {
          activeJobId = null;
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

  await closeDbPool();
  log('[Worker] Worker telah berhenti.');
}

const isCliEntry = Boolean(process.argv[1]) && process.argv[1].endsWith('worker.js');
const isPm2Entry = Boolean(process.env.pm_id) || (Boolean(process.env.pm_exec_path) && process.env.pm_exec_path.endsWith('worker.js'));

if (isCliEntry || isPm2Entry) {
  main().catch((err) => {
    error('[Worker] Fatal Error:', err);
    process.exit(1);
  });
}
