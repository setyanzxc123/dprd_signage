import test from 'node:test';
import assert from 'node:assert/strict';
import {
  parseApiError,
  isDailyQuotaExhausted,
  isTpmRateLimit,
  isServerOverloaded,
  isRetryableError,
  getRetryDelayMs,
  describeError,
  callWithRetry,
  interruptibleSleep,
  JobCancelledError,
} from '../services/throttler.js';

// Fixture struktur nyata dari log job #11: SDK membungkus body API dalam JSON
// bersarang dua lapis ({error:{message:"{\"error\":{...}"}}).
const FIXTURE_429_DAILY = JSON.stringify({
  error: {
    message: JSON.stringify({
      error: {
        code: 429,
        message: 'You exceeded your current quota, please check your plan and billing details.',
        status: 'RESOURCE_EXHAUSTED',
        details: [
          {
            '@type': 'type.googleapis.com/google.rpc.QuotaFailure',
            violations: [{
              quotaMetric: 'generativelanguage.googleapis.com/generate_content_free_tier_requests',
              quotaId: 'GenerateRequestsPerDayPerProjectPerModel-FreeTier',
              quotaDimensions: { location: 'global', model: 'gemini-3.6-flash' },
              quotaValue: '20',
            }],
          },
          { '@type': 'type.googleapis.com/google.rpc.RetryInfo', retryDelay: '14s' },
        ],
      },
    }),
    code: 429,
    status: 'Too Many Requests',
  },
});

const FIXTURE_429_PERMINUTE = JSON.stringify({
  error: {
    message: JSON.stringify({
      error: {
        code: 429,
        message: 'Resource has been exhausted (e.g. check quota).',
        status: 'RESOURCE_EXHAUSTED',
        details: [
          {
            '@type': 'type.googleapis.com/google.rpc.QuotaFailure',
            violations: [{ quotaId: 'GenerateRequestsPerMinutePerProjectPerModel' }],
          },
          { '@type': 'type.googleapis.com/google.rpc.RetryInfo', retryDelay: '30s' },
        ],
      },
    }),
  },
});

const FIXTURE_429_FLAT_QUOTAID = JSON.stringify({
  error: {
    code: 429,
    message: 'Quota exceeded.',
    status: 'RESOURCE_EXHAUSTED',
    details: [{
      '@type': 'type.googleapis.com/google.rpc.QuotaFailure',
      quotaId: 'GenerateRequestsPerDayPerProjectPerModel-FreeTier',
      quotaDimensions: { model: 'gemini-3.7-flash' },
    }],
  },
});

test('parseApiError membaca payload bersarang dua lapis dari SDK', () => {
  const err = new Error(FIXTURE_429_DAILY);
  err.code = 429;
  const parsed = parseApiError(err);
  assert.equal(parsed.code, 429);
  assert.equal(parsed.status, 'RESOURCE_EXHAUSTED');
  assert.ok(parsed.message.startsWith('You exceeded your current quota'));
  assert.equal(parsed.quotaId, 'GenerateRequestsPerDayPerProjectPerModel-FreeTier');
  assert.equal(parsed.retryDelayMs, 14000);
});

test('parseApiError membaca payload satu lapis dengan quotaId langsung di QuotaFailure', () => {
  const parsed = parseApiError(new Error(FIXTURE_429_FLAT_QUOTAID));
  assert.equal(parsed.code, 429);
  assert.equal(parsed.quotaId, 'GenerateRequestsPerDayPerProjectPerModel-FreeTier');
});

test('parseApiError mengembalikan null untuk error non-JSON', () => {
  const parsed = parseApiError(new Error('ECONNRESET'));
  assert.equal(parsed.quotaId, null);
  assert.equal(parsed.retryDelayMs, 0);
  assert.equal(parseApiError(null), null);
});

test('kuota harian terdeteksi dan tidak retryable', () => {
  const err = new Error(FIXTURE_429_DAILY);
  assert.equal(isDailyQuotaExhausted(err), true);
  assert.equal(isRetryableError(err), false);
});

test('429 per-menit tetap retryable dengan delay dari RetryInfo', () => {
  const err = new Error(FIXTURE_429_PERMINUTE);
  assert.equal(isDailyQuotaExhausted(err), false);
  assert.equal(isRetryableError(err), true);
  assert.equal(getRetryDelayMs(err), 30000);
});

test('describeError merapikan pesan dan menyertakan quotaId', () => {
  const text = describeError(new Error(FIXTURE_429_DAILY));
  assert.ok(text.includes('429'));
  assert.ok(text.includes('RESOURCE_EXHAUSTED'));
  assert.ok(text.includes('[GenerateRequestsPerDayPerProjectPerModel-FreeTier]'));
  assert.ok(!text.includes('\\n'));
});

test('callWithRetry melempar kuota harian segera tanpa retry', async () => {
  let attempts = 0;
  const err = new Error(FIXTURE_429_DAILY);
  await assert.rejects(
    callWithRetry(async () => {
      attempts++;
      throw err;
    }, { maxRetries: 4, initialDelayMs: 1 }),
    (thrown) => thrown === err
  );
  assert.equal(attempts, 1);
});

test('callWithRetry me-redirect error non-quota sesuai maxRetries', async () => {
  let attempts = 0;
  await assert.rejects(
    callWithRetry(async () => {
      attempts++;
      throw new Error('503 service unavailable');
    }, { maxRetries: 3, initialDelayMs: 1 })
  );
  assert.equal(attempts, 3);
});

test('callWithRetry tidak me-redirect error yang tidak retryable', async () => {
  let attempts = 0;
  await assert.rejects(
    callWithRetry(async () => {
      attempts++;
      throw new Error('400 BAD_REQUEST invalid argument');
    }, { maxRetries: 3, initialDelayMs: 1 })
  );
  assert.equal(attempts, 1);
});

test('callWithRetry menghormati delay minimum RetryInfo server', async () => {
  let observedWait = 0;
  let attempts = 0;
  await assert.rejects(
    callWithRetry(async () => {
      attempts++;
      if (attempts === 1) {
        const err = new Error(FIXTURE_429_PERMINUTE);
        throw err;
      }
      throw new Error('fatal stop');
    }, {
      maxRetries: 3,
      initialDelayMs: 10,
      sleepFn: async () => {},
      onRetry: ({ waitTimeMs }) => { observedWait = waitTimeMs; },
    })
  );
  assert.ok(observedWait >= 30000, `wait ${observedWait} harus >= 30000 (RetryInfo server)`);
  assert.equal(attempts, 2);
});

test('JobCancelledError terdeteksi via instanceof dan berpesan tetap', () => {
  const err = new JobCancelledError();
  assert.ok(err instanceof JobCancelledError);
  assert.ok(err instanceof Error);
  assert.equal(err.name, 'JobCancelledError');
  assert.equal(err.message, 'JOB_CANCELLED_BY_ADMIN');
});

test('callWithRetry melempar JobCancelledError tanpa retry', async () => {
  let attempts = 0;
  await assert.rejects(
    callWithRetry(async () => {
      attempts++;
      throw new JobCancelledError();
    }, { maxRetries: 4, initialDelayMs: 1 })
  );
  assert.equal(attempts, 1);
});

test('callWithRetry memeriksa cancelChecker sebelum setiap attempt', async () => {
  let attempts = 0;
  await assert.rejects(
    callWithRetry(async () => {
      attempts++;
      if (attempts === 1) {
        throw new Error('503 service unavailable');
      }
      return 'ok';
    }, {
      maxRetries: 3,
      initialDelayMs: 1,
      cancelChecker: async () => attempts >= 1,
    }),
    JobCancelledError
  );
  assert.equal(attempts, 1);
});

test('interruptibleSleep melempar JobCancelledError saat cancel', async () => {
  await assert.rejects(
    interruptibleSleep(5000, async () => true, 100),
    JobCancelledError
  );
});

test('interruptibleSleep selesai normal tanpa cancel', async () => {
  await assert.doesNotReject(interruptibleSleep(50, async () => false, 20));
});

test('isTpmRateLimit membedakan TPM per-menit dari kuota harian', () => {
  const dailyErr = new Error(FIXTURE_429_DAILY);
  const tpmErr = new Error(FIXTURE_429_PERMINUTE);
  const generic429 = new Error('Resource exhausted 429');
  const generic500 = new Error('Internal error 500');

  assert.equal(isTpmRateLimit(dailyErr), false);
  assert.equal(isTpmRateLimit(tpmErr), true);
  assert.equal(isTpmRateLimit(generic429), true);
  assert.equal(isTpmRateLimit(generic500), false);
});

test('isServerOverloaded mendeteksi 503 UNAVAILABLE dan overload', () => {
  const err503 = new Error('503 Service Unavailable');
  const errUnavailable = new Error('Model is overloaded. Please try again later.');
  const err429 = new Error('429 Resource Exhausted');

  assert.equal(isServerOverloaded(err503), true);
  assert.equal(isServerOverloaded(errUnavailable), true);
  assert.equal(isServerOverloaded(err429), false);
});

test('callWithRetry melempar segera pada 503 jika failoverOn503 aktif', async () => {
  let attempts = 0;
  await assert.rejects(
    callWithRetry(
      async () => {
        attempts++;
        throw new Error('503 UNAVAILABLE');
      },
      {
        maxRetries: 3,
        initialDelayMs: 10,
        failoverOn503: true,
        sleepFn: async () => {},
      }
    )
  );
  assert.equal(attempts, 1);
});

test('callWithRetry melakukan retry pada 503 jika failoverOn503 tidak aktif', async () => {
  let attempts = 0;
  await assert.rejects(
    callWithRetry(
      async () => {
        attempts++;
        throw new Error('503 UNAVAILABLE');
      },
      {
        maxRetries: 3,
        initialDelayMs: 10,
        failoverOn503: false,
        sleepFn: async () => {},
      }
    )
  );
  assert.equal(attempts, 3);
});

test('callWithRetry menggunakan tpmResetWaitMs untuk error TPM', async () => {
  let observedWait = 0;
  let attempts = 0;
  await assert.rejects(
    callWithRetry(
      async () => {
        attempts++;
        if (attempts === 1) {
          throw new Error('429 Resource Exhausted');
        }
        throw new Error('stop');
      },
      {
        maxRetries: 2,
        initialDelayMs: 10,
        tpmResetWaitMs: 60000,
        sleepFn: async () => {},
        onRetry: ({ waitTimeMs, isTpm }) => {
          observedWait = waitTimeMs;
          assert.equal(isTpm, true);
        },
      }
    )
  );
  assert.ok(observedWait >= 60000 && observedWait <= 61000);
  assert.equal(attempts, 2);
});
