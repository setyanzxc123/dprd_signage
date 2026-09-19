import { config } from '../config.js';
import { JobCancelledError, isDailyQuotaExhausted, describeError, interruptibleSleep } from './throttler.js';
import { effectiveModelChain, setStickyModel, markDeadToday } from './modelChain.js';
import { log as workerLog } from './logger.js';

export const MAX_CHAIN_PASSES = 3;
export const CHAIN_RETRY_DELAY_MS = 15_000;

const QUOTA_EXHAUSTED_MESSAGE = 'Kuota harian seluruh model chain habis. Coba lagi setelah kuota reset atau perbarui GEMINI_MODEL_CHAIN.';

/**
 * Menjalankan satu pekerjaan model AI di atas rantai model fallback:
 * iterasi model efektif (sticky dulu, model kuota-habis disaring) selama
 * maksimal maxPasses putaran, dengan penanganan cancel, kuota harian
 * (markDeadToday), dan jeda antar putaran yang seragam.
 *
 * @param {Object} options
 * @param {string} options.taskLabel Label log (mis. 'Transcribe', 'Minutes')
 * @param {Function} options.runForModel async (modelName) => hasil pekerjaan;
 *   melempar error berarti model gagal dan rantai berlanjut ke model berikutnya
 * @param {Function} options.attemptMessage (modelName, modelIndex, totalModels, passSuffix) => string
 * @param {Function} options.stallMessage (pass, maxPasses, delaySeconds) => string
 * @param {Function} options.failureMessage (lastError) => string pesan gagal total
 * @param {Function} options.cancelChecker Pengecek pembatalan job
 * @param {Function} options.onLog Callback log
 * @returns {Promise<{result: *, usedModel: string|null}>}
 */
export async function runWithModelChain({
  taskLabel,
  runForModel,
  attemptMessage,
  stallMessage,
  failureMessage,
  cancelChecker = null,
  onLog = workerLog,
  maxPasses = MAX_CHAIN_PASSES,
  passDelayMs = CHAIN_RETRY_DELAY_MS,
}) {
  let result = null;
  let usedModel = null;
  let lastError = null;
  let succeeded = false;

  for (let pass = 1; pass <= maxPasses; pass++) {
    const models = effectiveModelChain(config.gemini.modelChain);
    if (models.length === 0) {
      throw new Error(QUOTA_EXHAUSTED_MESSAGE);
    }

    for (let mIdx = 0; mIdx < models.length; mIdx++) {
      const modelName = models[mIdx];
      const passSuffix = pass > 1 ? ` (Putaran ${pass}/${maxPasses})` : '';
      onLog(attemptMessage(modelName, mIdx, models.length, passSuffix));

      try {
        result = await runForModel(modelName);
        setStickyModel(modelName);
        usedModel = modelName;
        succeeded = true;
        break;
      } catch (err) {
        lastError = err;
        if (err instanceof JobCancelledError) {
          throw err;
        }
        if (isDailyQuotaExhausted(err)) {
          markDeadToday(modelName);
          onLog(`[${taskLabel}] Model ${modelName} dilewati untuk sisa hari ini: kuota harian habis (${describeError(err)}).`);
        } else {
          onLog(`[${taskLabel}] Model ${modelName} dialihkan: ${describeError(err)}. Mencoba model fallback berikutnya...`);
        }
      }
    }

    if (succeeded) {
      break;
    }

    const remainingModels = effectiveModelChain(config.gemini.modelChain);
    if (remainingModels.length === 0) {
      throw new Error(QUOTA_EXHAUSTED_MESSAGE);
    }

    if (pass < maxPasses) {
      onLog(stallMessage(pass, maxPasses, passDelayMs / 1000));
      await interruptibleSleep(passDelayMs, cancelChecker);
    }
  }

  if (!succeeded) {
    throw new Error(failureMessage(lastError));
  }

  return { result, usedModel };
}
