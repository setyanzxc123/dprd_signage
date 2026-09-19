export const DEFAULT_TTFT_TIMEOUT_MS = 120_000;
export const DEFAULT_INTER_CHUNK_TIMEOUT_MS = 45_000;
const STREAM_SILENCE_LOG_MS = 30_000;
const CANCEL_POLL_MS = 2_000;

/**
 * Watchdog untuk stream Gemini dengan mekanisme dual-timeout (TTFT & Inter-Chunk)
 * serta pemantauan cancel berkala.
 */
export function createStreamWatchdog({
  cancelChecker,
  onSilence,
  ttftTimeoutMs = DEFAULT_TTFT_TIMEOUT_MS,
  interChunkTimeoutMs = DEFAULT_INTER_CHUNK_TIMEOUT_MS,
  silenceLogMs = STREAM_SILENCE_LOG_MS,
  pollIntervalMs = CANCEL_POLL_MS,
}) {
  const controller = new AbortController();
  let cancelled = false;
  let connected = false;
  let hasReceivedFirstToken = false;
  let timedOut = false;
  let timeoutReason = null;
  const startedAt = Date.now();
  let lastActivityAt = startedAt;
  let lastSilenceLogAt = startedAt;

  const timer = setInterval(() => {
    if (cancelled || timedOut) return;
    (async () => {
      if (cancelChecker && typeof cancelChecker === 'function') {
        try {
          if (await cancelChecker()) {
            cancelled = true;
            controller.abort();
            return;
          }
        } catch {
          // Kegagalan cek cancel tidak mematikan watchdog
        }
      }

      const now = Date.now();

      if (!hasReceivedFirstToken) {
        const timeWaitingFirstToken = now - startedAt;
        if (timeWaitingFirstToken >= ttftTimeoutMs) {
          timedOut = true;
          timeoutReason = {
            phase: 'ttft',
            idleDurationMs: timeWaitingFirstToken,
            limitMs: ttftTimeoutMs,
          };
          controller.abort();
          return;
        }
      } else {
        const idleBetweenChunks = now - lastActivityAt;
        if (idleBetweenChunks >= interChunkTimeoutMs) {
          timedOut = true;
          timeoutReason = {
            phase: 'inter_chunk',
            idleDurationMs: idleBetweenChunks,
            limitMs: interChunkTimeoutMs,
          };
          controller.abort();
          return;
        }
      }

      const silentForMs = now - (hasReceivedFirstToken ? lastActivityAt : startedAt);
      if (onSilence && now - lastSilenceLogAt >= silenceLogMs) {
        lastSilenceLogAt = now;
        onSilence(Math.round(silentForMs / 1000), connected);
      }
    })();
  }, pollIntervalMs);

  return {
    signal: controller.signal,
    isCancelled: () => cancelled,
    isTimedOut: () => timedOut,
    getTimeoutReason: () => timeoutReason,
    markConnected: () => { connected = true; },
    touch: () => {
      hasReceivedFirstToken = true;
      lastActivityAt = Date.now();
    },
    stop: () => clearInterval(timer),
  };
}
