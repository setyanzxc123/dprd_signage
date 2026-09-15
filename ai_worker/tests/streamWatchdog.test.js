import test from 'node:test';
import assert from 'node:assert/strict';
import { createStreamWatchdog } from '../services/geminiService.js';
import { sleep } from '../services/throttler.js';

test('watchdog membatalkan stream jika TTFT melebihi batas waktu', async () => {
  const watchdog = createStreamWatchdog({
    ttftTimeoutMs: 50,
    interChunkTimeoutMs: 100,
    pollIntervalMs: 10,
  });

  try {
    assert.equal(watchdog.isTimedOut(), false);
    assert.equal(watchdog.signal.aborted, false);

    await sleep(80);

    assert.equal(watchdog.isTimedOut(), true);
    assert.equal(watchdog.signal.aborted, true);
    const reason = watchdog.getTimeoutReason();
    assert.equal(reason?.phase, 'ttft');
    assert.ok(reason?.idleDurationMs >= 50);
  } finally {
    watchdog.stop();
  }
});

test('watchdog membatalkan stream jika jeda antar token melebihi batas waktu', async () => {
  const watchdog = createStreamWatchdog({
    ttftTimeoutMs: 200,
    interChunkTimeoutMs: 50,
    pollIntervalMs: 10,
  });

  try {
    await sleep(20);
    watchdog.touch();

    assert.equal(watchdog.isTimedOut(), false);
    assert.equal(watchdog.signal.aborted, false);

    await sleep(80);

    assert.equal(watchdog.isTimedOut(), true);
    assert.equal(watchdog.signal.aborted, true);
    const reason = watchdog.getTimeoutReason();
    assert.equal(reason?.phase, 'inter_chunk');
    assert.ok(reason?.idleDurationMs >= 50);
  } finally {
    watchdog.stop();
  }
});

test('watchdog tidak timeout saat token terus mengalir secara berkala', async () => {
  const watchdog = createStreamWatchdog({
    ttftTimeoutMs: 100,
    interChunkTimeoutMs: 100,
    pollIntervalMs: 10,
  });

  try {
    await sleep(25);
    watchdog.touch();
    await sleep(25);
    watchdog.touch();
    await sleep(25);
    watchdog.touch();

    assert.equal(watchdog.isTimedOut(), false);
    assert.equal(watchdog.signal.aborted, false);
  } finally {
    watchdog.stop();
  }
});

test('watchdog membatalkan stream saat cancelChecker mengembalikan true', async () => {
  let isCancelled = false;
  const watchdog = createStreamWatchdog({
    cancelChecker: async () => isCancelled,
    ttftTimeoutMs: 500,
    pollIntervalMs: 10,
  });

  try {
    assert.equal(watchdog.isCancelled(), false);
    isCancelled = true;
    await sleep(30);

    assert.equal(watchdog.isCancelled(), true);
    assert.equal(watchdog.signal.aborted, true);
  } finally {
    watchdog.stop();
  }
});
