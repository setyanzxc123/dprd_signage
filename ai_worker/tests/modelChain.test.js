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
