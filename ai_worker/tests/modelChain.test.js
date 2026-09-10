import test from 'node:test';
import assert from 'node:assert/strict';
import {
  effectiveModelChain,
  setStickyModel,
  markDeadToday,
  isDeadToday,
  resetModelState,
} from '../services/modelChain.js';

const CHAIN = ['gemini-3.1-flash-lite', 'gemini-3.5-flash', 'gemini-3.6-flash'];

test.beforeEach(() => resetModelState());

test('rantai efektif sama dengan rantai asli saat tanpa state', () => {
  assert.deepEqual(effectiveModelChain(CHAIN), CHAIN);
});

test('model yang kuota hariannya habis disaring keluar', () => {
  markDeadToday('gemini-3.5-flash');
  assert.equal(isDeadToday('gemini-3.5-flash'), true);
  assert.deepEqual(
    effectiveModelChain(CHAIN),
    ['gemini-3.1-flash-lite', 'gemini-3.6-flash']
  );
});

test('model sticky dipindah ke depan', () => {
  setStickyModel('gemini-3.6-flash');
  assert.deepEqual(
    effectiveModelChain(CHAIN),
    ['gemini-3.6-flash', 'gemini-3.1-flash-lite', 'gemini-3.5-flash']
  );
});

test('model sticky yang habis kuotanya tidak masuk rantai efektif', () => {
  setStickyModel('gemini-3.1-flash-lite');
  markDeadToday('gemini-3.1-flash-lite');
  assert.deepEqual(
    effectiveModelChain(CHAIN),
    ['gemini-3.5-flash', 'gemini-3.6-flash']
  );
});

test('semua model habis kuota menghasilkan rantai kosong', () => {
  for (const model of CHAIN) markDeadToday(model);
  assert.deepEqual(effectiveModelChain(CHAIN), []);
});

test('resetModelState menghapus sticky dan daftar mati', () => {
  setStickyModel('gemini-3.5-flash');
  markDeadToday('gemini-3.6-flash');
  resetModelState();
  assert.deepEqual(effectiveModelChain(CHAIN), CHAIN);
});

test('resolveThinkingLevel memetakan enum yang valid dan fallback ke LOW', async () => {
  const { resolveThinkingLevel } = await import('../services/geminiService.js');
  const { ThinkingLevel } = await import('@google/genai');

  assert.equal(resolveThinkingLevel('LOW'), ThinkingLevel.LOW);
  assert.equal(resolveThinkingLevel('MEDIUM'), ThinkingLevel.MEDIUM);
  assert.equal(resolveThinkingLevel('HIGH'), ThinkingLevel.HIGH);
  assert.equal(resolveThinkingLevel('UNKNOWN_VAL'), ThinkingLevel.LOW);
  assert.equal(resolveThinkingLevel(null), ThinkingLevel.LOW);
});

test('config gemini menyediakan thinkingLevel default LOW', async () => {
  const { config } = await import('../config.js');
  assert.equal(typeof config.gemini.thinkingLevel, 'string');
  assert.ok(['LOW', 'MEDIUM', 'HIGH'].includes(config.gemini.thinkingLevel));
});
