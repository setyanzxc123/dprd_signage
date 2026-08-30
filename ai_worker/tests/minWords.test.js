import test from 'node:test';
import assert from 'node:assert/strict';
import { minExpectedWordsFor } from '../services/geminiService.js';

test('minimum kata non-final proporsional durasi bicara', () => {
  // 30 menit bicara penuh, 30 kata/menit -> 900 kata
  assert.equal(minExpectedWordsFor(1800, false), 900);
  // 1 menit bicara -> 30 kata, tapi floor non-final 50
  assert.equal(minExpectedWordsFor(60, false), 50);
});

test('minimum kata chunk final lebih toleran', () => {
  // 1 menit bicara: floor final adalah 30
  assert.equal(minExpectedWordsFor(60, true), 30);
  // 3 detik bicara: floor final 10 kata
  assert.equal(minExpectedWordsFor(3, true), 10);
});

test('durasi bicara nol tetap punya floor minimum', () => {
  assert.equal(minExpectedWordsFor(0, false), 50);
  assert.equal(minExpectedWordsFor(0, true), 10);
});
