/**
 * Modul Throttler & Exponential Backoff
 * Menangani 429 RESOURCE_EXHAUSTED dan 503 UNAVAILABLE secara bertahap.
 */

export function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Tidur yang dapat dibatalkan (interruptible sleep) dengan mengecek status cancel secara periodik.
 */
export async function interruptibleSleep(ms, cancelChecker = null, checkIntervalMs = 500) {
  const startTime = Date.now();
  while (Date.now() - startTime < ms) {
    if (cancelChecker && typeof cancelChecker === 'function') {
      const isCancelled = await cancelChecker();
      if (isCancelled) {
        throw new Error('JOB_CANCELLED_BY_ADMIN');
      }
    }
    const remaining = ms - (Date.now() - startTime);
    const step = Math.min(remaining, checkIntervalMs);
    if (step > 0) {
      await sleep(step);
    }
  }
}

/**
 * Deteksi apakah error termasuk kategori retryable (Rate Limit 429 / Server Overload 503 / Network Glitch).
 */
export function isRetryableError(error) {
  if (!error) return false;
  const message = String(error.message || error).toLowerCase();
  const status = error.status || error.code || error.statusCode;

  if (status === 429 || status === 503 || status === 500 || status === 'RESOURCE_EXHAUSTED' || status === 'UNAVAILABLE') {
    return true;
  }

  if (
    message.includes('429') ||
    message.includes('resource_exhausted') ||
    message.includes('quota') ||
    message.includes('rate limit') ||
    message.includes('503') ||
    message.includes('service unavailable') ||
    message.includes('overloaded') ||
    message.includes('econnreset') ||
    message.includes('etimedout') ||
    message.includes('fetch failed')
  ) {
    return true;
  }

  return false;
}

/**
 * Menjalankan fungsi async dengan proteksi retry exponential backoff.
 *
 * @param {Function} fn Fungsi async yang dieksekusi: `async (attempt) => result`
 * @param {Object} options Opsi konfigurasi
 * @param {number} options.maxRetries Maksimal percobaan ulang per model (default: 4)
 * @param {number} options.initialDelayMs Jeda awal dalam ms (default: 10000 = 10s)
 * @param {number} options.backoffFactor Faktor pengali jeda (default: 2.5 -> 10s, 25s, 62.5s)
 * @param {Function} options.cancelChecker Fungsi pengecek apakah job dibatalkan
 * @param {Function} options.onRetry Callback log saat retry terjadi
 */
export async function callWithRetry(fn, options = {}) {
  const maxRetries = options.maxRetries ?? 4;
  const initialDelayMs = options.initialDelayMs ?? 10000;
  const backoffFactor = options.backoffFactor ?? 2.5;
  const cancelChecker = options.cancelChecker ?? null;
  const onRetry = options.onRetry ?? null;

  let currentDelay = initialDelayMs;

  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    // Periksa pembatalan sebelum menjalankan aksi
    if (cancelChecker && typeof cancelChecker === 'function') {
      const isCancelled = await cancelChecker();
      if (isCancelled) {
        throw new Error('JOB_CANCELLED_BY_ADMIN');
      }
    }

    try {
      return await fn(attempt);
    } catch (err) {
      if (err.message === 'JOB_CANCELLED_BY_ADMIN') {
        throw err;
      }

      const retryable = isRetryableError(err);
      const isLastAttempt = attempt >= maxRetries;

      if (!retryable || isLastAttempt) {
        throw err;
      }

      // Tambahkan jitter acak (0 - 1000ms) untuk mencegah collision
      const jitter = Math.floor(Math.random() * 1000);
      const waitTime = Math.round(currentDelay + jitter);

      if (onRetry && typeof onRetry === 'function') {
        onRetry({
          attempt,
          maxRetries,
          waitTimeMs: waitTime,
          error: err,
        });
      }

      await interruptibleSleep(waitTime, cancelChecker);
      currentDelay *= backoffFactor;
    }
  }
}
