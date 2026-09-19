import { JobCancelledError, StreamTimeoutError } from '../throttler.js';
import { getAiClient } from './client.js';
import { createStreamWatchdog } from './streamWatchdog.js';

/**
 * Mengonsumsi satu stream generateContentStream dengan proteksi watchdog:
 * injeksi abortSignal, akumulasi teks, pemetaan cancel/timeout ke error
 * terklasifikasi. Pesan log (tersambung, progres, silence) diserahkan ke
 * pemanggil melalui callback.
 *
 * @param {Object} options
 * @param {string} options.modelName Nama model Gemini
 * @param {string|Array} options.contents Isi request generateContentStream
 * @param {Object} options.requestConfig Config tanpa abortSignal (diinjeksi watchdog)
 * @param {Function} options.cancelChecker Pengecek pembatalan job
 * @param {Function} options.onConnected Dipanggil setelah stream tersambung
 * @param {Function} options.onSilence (sec, isConnected) => void saat stream diam
 * @param {Function} options.onProgress (accumulated) => void tiap progressIntervalMs
 * @param {number} options.progressIntervalMs Jeda antar callback progres
 * @returns {Promise<string>} Teks mentah hasil akumulasi stream
 */
export async function streamText({
  modelName,
  contents,
  requestConfig = {},
  cancelChecker = null,
  onConnected = null,
  onSilence = null,
  onProgress = null,
  progressIntervalMs = 10_000,
}) {
  const startTime = Date.now();
  let lastProgressAt = startTime;
  let accumulated = '';

  const watchdog = createStreamWatchdog({ cancelChecker, onSilence });

  try {
    const stream = await getAiClient().models.generateContentStream({
      model: modelName,
      contents,
      config: {
        ...requestConfig,
        abortSignal: watchdog.signal,
      },
    });
    watchdog.markConnected();
    if (onConnected) {
      onConnected();
    }

    for await (const chunk of stream) {
      watchdog.touch();

      accumulated += chunk.text ?? '';

      if (onProgress && Date.now() - lastProgressAt >= progressIntervalMs) {
        onProgress(accumulated);
        lastProgressAt = Date.now();
      }
    }
  } catch (err) {
    if (watchdog.isCancelled() || err instanceof JobCancelledError) {
      throw new JobCancelledError();
    }
    if (watchdog.isTimedOut()) {
      const reason = watchdog.getTimeoutReason();
      const phaseLabel = reason?.phase === 'ttft'
        ? `Time-to-first-token timeout (${Math.round(reason.idleDurationMs / 1000)}s tanpa respons pertama)`
        : `Inter-chunk idle timeout (${Math.round(reason.idleDurationMs / 1000)}s jeda antar token)`;
      throw new StreamTimeoutError(`Stream ${modelName} terhenti: ${phaseLabel}`, reason);
    }
    throw err;
  } finally {
    watchdog.stop();
  }

  return accumulated;
}
