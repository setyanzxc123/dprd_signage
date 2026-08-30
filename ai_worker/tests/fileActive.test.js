import test from 'node:test';
import assert from 'node:assert/strict';
import { waitForFileActive } from '../services/geminiService.js';

test('file sudah ACTIVE langsung lolos tanpa poll tambahan', async () => {
  let calls = 0;
  const file = await waitForFileActive(
    async () => {
      calls++;
      return { state: 'ACTIVE' };
    },
    'files/abc',
    { intervalMs: 1, timeoutMs: 100 }
  );
  assert.equal(calls, 1);
  assert.equal(file.state, 'ACTIVE');
});

test('PROCESSING di-polling hingga ACTIVE', async () => {
  const states = ['PROCESSING', 'PROCESSING', 'ACTIVE'];
  let calls = 0;
  const logs = [];
  const file = await waitForFileActive(
    async () => ({ state: states[calls++] }),
    'files/abc',
    { intervalMs: 1, timeoutMs: 5000, onLog: (m) => logs.push(m) }
  );
  assert.equal(calls, 3);
  assert.equal(file.state, 'ACTIVE');
  assert.equal(logs.length, 2);
});

test('state FAILED menghasilkan error ingest tanpa poll lanjutan', async () => {
  let calls = 0;
  await assert.rejects(
    waitForFileActive(
      async () => {
        calls++;
        return { state: 'FAILED' };
      },
      'files/abc',
      { intervalMs: 1, timeoutMs: 5000 }
    ),
    /Gagal ingest file files\/abc/
  );
  assert.equal(calls, 1);
});

test('timeout menghasilkan error dengan state terakhir', async () => {
  await assert.rejects(
    waitForFileActive(
      async () => ({ state: 'PROCESSING' }),
      'files/abc',
      { intervalMs: 1, timeoutMs: 30 }
    ),
    /Timeout.*PROCESSING/
  );
});
