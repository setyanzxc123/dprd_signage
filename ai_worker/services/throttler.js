/**
 * Modul Throttler & Exponential Backoff
 * Menangani 429 RESOURCE_EXHAUSTED dan 503 UNAVAILABLE secara bertahap,
 * dengan klasifikasi kuota harian (PerDay) agar tidak retry sia-sia.
 */

export function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Error pembatalan job oleh admin. Deteksi memakai instanceof, bukan
 * perbandingan pesan, agar tidak tertukar dengan error lain yang kebetulan
 * mengandung teks serupa.
 */
export class JobCancelledError extends Error {
  constructor() {
    super('JOB_CANCELLED_BY_ADMIN');
    this.name = 'JobCancelledError';
  }
}

function tryParseJson(text) {
  if (typeof text !== 'string') return null;
  const trimmed = text.trim();
  if (!trimmed.startsWith('{')) return null;
  try {
    return JSON.parse(trimmed);
  } catch {
    return null;
  }
}

function parseDurationToMs(text) {
  if (typeof text !== 'string') return 0;
  let total = 0;
  const pattern = /(\d+(?:\.\d+)?)\s*([smh])/gi;
  let match;
  while ((match = pattern.exec(text)) !== null) {
    const value = parseFloat(match[1]);
    const unit = match[2].toLowerCase();
    if (unit === 's') total += value * 1000;
    else if (unit === 'm') total += value * 60 * 1000;
    else if (unit === 'h') total += value * 60 * 60 * 1000;
  }
  return Math.round(total);
}

/**
 * Mem-parsing error Gemini API yang payload-nya bisa berupa JSON mentah di
 * dalam message, termasuk bentuk bersarang dua lapis dari SDK
 * ({error:{message:"{\"error\":{...}}"}}).
 *
 * @returns {Object|null} { code, status, message, quotaId, retryDelayMs }
 */
export function parseApiError(error) {
  if (!error) return null;

  const out = {
    code: null,
    status: null,
    message: typeof error.message === 'string' ? error.message : String(error.message ?? ''),
    quotaId: null,
    retryDelayMs: 0,
  };

  let payload = tryParseJson(error.message);
  if (payload && payload.error && typeof payload.error.message === 'string') {
    const inner = tryParseJson(payload.error.message);
    if (inner && inner.error) {
      payload = inner;
    }
  }
  const apiError = payload ? payload.error : null;

  if (apiError) {
    out.code = apiError.code ?? out.code;
    out.status = apiError.status ?? out.status;
    out.message = apiError.message ?? out.message;

    const details = Array.isArray(apiError.details) ? apiError.details : [];
    for (const detail of details) {
      const type = typeof detail['@type'] === 'string' ? detail['@type'] : '';
      if (type.includes('QuotaFailure') && !out.quotaId) {
        if (typeof detail.quotaId === 'string' && detail.quotaId) {
          out.quotaId = detail.quotaId;
        } else {
          const violations = Array.isArray(detail.violations) ? detail.violations : [];
          for (const violation of violations) {
            if (violation && typeof violation.quotaId === 'string' && violation.quotaId) {
              out.quotaId = violation.quotaId;
              break;
            }
          }
        }
      }
      if (type.includes('RetryInfo') && !out.retryDelayMs && typeof detail.retryDelay === 'string') {
        out.retryDelayMs = parseDurationToMs(detail.retryDelay);
      }
    }
  }

  return out;
}

/**
 * True bila error 429 menyatakan kuota harian (PerDay) habis - retry
 * kapan pun tidak akan menyelamatkan panggilan ini sampai reset besok.
 */
export function isDailyQuotaExhausted(error) {
  const parsed = parseApiError(error);
  return Boolean(parsed && parsed.quotaId && /perday/i.test(parsed.quotaId));
}

/**
 * Delay minimum yang disarankan server via RetryInfo (mis. "14s").
 */
export function getRetryDelayMs(error) {
  const parsed = parseApiError(error);
  return parsed ? parsed.retryDelayMs || 0 : 0;
}

/**
 * Pesan error ringkas untuk log: pesan ter-parsing + quotaId bila ada.
 */
export function describeError(error) {
  const parsed = parseApiError(error);
  if (parsed && parsed.message && parsed.message !== String(error?.message)) {
    const code = parsed.code ?? '';
    const status = parsed.status ?? '';
    const quota = parsed.quotaId ? ` [${parsed.quotaId}]` : '';
    const text = `${code} ${status}: ${parsed.message}${quota}`.replace(/\s+/g, ' ').trim();
    return text.length > 220 ? text.slice(0, 220) + '...' : text;
  }
  return String((error && error.message) || error);
}

/**
 * Deteksi apakah error termasuk kategori retryable (Rate Limit 429 / Server Overload 503 / Network Glitch).
 * Kuota harian (PerDay) sengaja tidak retryable.
 */
export function isRetryableError(error) {
  if (!error) return false;
  if (isDailyQuotaExhausted(error)) return false;

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
 * Tidur yang dapat dibatalkan (interruptible sleep) dengan mengecek status cancel secara periodik.
 */
export async function interruptibleSleep(ms, cancelChecker = null, checkIntervalMs = 500) {
  const startTime = Date.now();
  while (Date.now() - startTime < ms) {
    if (cancelChecker && typeof cancelChecker === 'function') {
      const isCancelled = await cancelChecker();
      if (isCancelled) {
        throw new JobCancelledError();
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
 * Menjalankan fungsi async dengan proteksi retry exponential backoff.
 * Error kuota harian (PerDay) langsung dilempar tanpa retry.
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
        throw new JobCancelledError();
      }
    }

    try {
      return await fn(attempt);
    } catch (err) {
      if (err instanceof JobCancelledError) {
        throw err;
      }

      // Kuota harian habis: percobaan ulang tidak akan berhasil, lempar segera
      if (isDailyQuotaExhausted(err)) {
        throw err;
      }

      const retryable = isRetryableError(err);
      const isLastAttempt = attempt >= maxRetries;

      if (!retryable || isLastAttempt) {
        throw err;
      }

      // Hormati delay yang disarankan server (RetryInfo) bila lebih panjang
      const jitter = Math.floor(Math.random() * 1000);
      const waitTime = Math.round(Math.max(currentDelay, getRetryDelayMs(err)) + jitter);

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
