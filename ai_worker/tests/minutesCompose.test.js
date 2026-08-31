import test from 'node:test';
import assert from 'node:assert/strict';
import { composeMinutesText, MINUTES_RESPONSE_SCHEMA } from '../services/geminiService.js';

test('schema risalah memuat tiga pilar dengan tipe yang benar', () => {
  assert.equal(MINUTES_RESPONSE_SCHEMA.type, 'OBJECT');
  assert.ok(MINUTES_RESPONSE_SCHEMA.properties.ringkasan_utama);
  assert.equal(MINUTES_RESPONSE_SCHEMA.properties.poin_pembahasan.type, 'ARRAY');
  assert.deepEqual(MINUTES_RESPONSE_SCHEMA.required, ['ringkasan_utama', 'poin_pembahasan', 'kesimpulan_akhir']);
  const point = MINUTES_RESPONSE_SCHEMA.properties.poin_pembahasan.items;
  assert.ok(point.properties.topik);
  assert.ok(point.properties.uraian);
});

test('composeMinutesText menyusun naskah tiga bagian dari pilar', () => {
  const text = composeMinutesText({
    ringkasan_utama: 'Rapat membahas APBD.',
    poin_pembahasan: [
      { waktu: '10:30', topik: 'Anggaran', pembicara: 'Ketua Komisi', uraian: 'Pendapat umum fraksi.' },
      { topik: 'Pembangunan', uraian: 'Paparan dinas PU.' },
    ],
    kesimpulan_akhir: ['Disetujui fraksi A', 'Tindak lanjut hari Jumat'],
  });

  assert.ok(text.includes('I. RINGKASAN UTAMA'));
  assert.ok(text.includes('II. POIN-POIN PEMBAHASAN'));
  assert.ok(text.includes('III. KESIMPULAN & KEPUTUSAN AKHIR'));
  assert.ok(text.includes('1. [10:30] Topik: Anggaran'));
  assert.ok(text.includes('   - Pembicara: Ketua Komisi'));
  assert.ok(text.includes('   - Uraian: Pendapat umum fraksi.'));
  assert.ok(text.includes('2. Topik: Pembangunan'));
  assert.ok(text.includes('1. Disetujui fraksi A'));
  assert.ok(!text.includes('undefined'));
});

test('composeMinutesText menangani pilar kosong tanpa error', () => {
  const text = composeMinutesText({ ringkasan_utama: '', poin_pembahasan: [], kesimpulan_akhir: [] });
  assert.ok(text.includes('Tidak ada poin pembahasan'));
  assert.ok(text.includes('Tidak ada kesimpulan'));
});
