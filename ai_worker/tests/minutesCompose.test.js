import test from 'node:test';
import assert from 'node:assert/strict';
import { composeMinutesText, MINUTES_RESPONSE_SCHEMA, stripTimestamps } from '../services/geminiService.js';

test('schema risalah memuat tiga pilar dengan tipe yang benar', () => {
  assert.equal(MINUTES_RESPONSE_SCHEMA.type, 'OBJECT');
  assert.ok(MINUTES_RESPONSE_SCHEMA.properties.ringkasan_utama);
  assert.equal(MINUTES_RESPONSE_SCHEMA.properties.poin_pembahasan.type, 'ARRAY');
  assert.deepEqual(MINUTES_RESPONSE_SCHEMA.required, ['ringkasan_utama', 'poin_pembahasan', 'kesimpulan_akhir']);
  const point = MINUTES_RESPONSE_SCHEMA.properties.poin_pembahasan.items;
  assert.ok(point.properties.topik);
  assert.ok(point.properties.uraian);
  assert.equal(point.properties.waktu, undefined);
});

test('stripTimestamps membersihkan cap waktu audio dari teks', () => {
  assert.equal(stripTimestamps('[10:30] Pembahasan anggaran'), 'Pembahasan anggaran');
  assert.equal(stripTimestamps('Rapat dibuka [01:23:45] oleh pimpinan.'), 'Rapat dibuka oleh pimpinan.');
  assert.equal(stripTimestamps('Teks tanpa cap waktu.'), 'Teks tanpa cap waktu.');
});

test('composeMinutesText menyusun naskah tiga bagian dari pilar', () => {
  const text = composeMinutesText({
    ringkasan_utama: 'Rapat membahas APBD.',
    poin_pembahasan: [
      { topik: 'Anggaran', pembicara: 'Ketua Komisi', uraian: 'Pendapat umum fraksi.' },
      { topik: 'Pembangunan', uraian: 'Paparan dinas PU.' },
    ],
    kesimpulan_akhir: ['Disetujui fraksi A', 'Tindak lanjut hari Jumat'],
  });

  assert.ok(text.includes('I. RINGKASAN UTAMA'));
  assert.ok(text.includes('II. POIN-POIN PEMBAHASAN'));
  assert.ok(text.includes('III. KESIMPULAN & KEPUTUSAN AKHIR'));
  assert.ok(text.includes('1. Topik: Anggaran'));
  assert.ok(text.includes('   - Pembicara: Ketua Komisi'));
  assert.ok(text.includes('   - Uraian: Pendapat umum fraksi.'));
  assert.ok(text.includes('2. Topik: Pembangunan'));
  assert.ok(text.includes('1. Disetujui fraksi A'));
  assert.ok(!text.includes('undefined'));
});

test('composeMinutesText membersihkan timestamp yang tersisa di pilar', () => {
  const text = composeMinutesText({
    ringkasan_utama: '[00:05] Pembukaan rapat APBD.',
    poin_pembahasan: [
      { topik: '[10:30] Anggaran', pembicara: 'Ketua Komisi [10:35]', uraian: '[10:40] Pendapat umum fraksi.' },
    ],
    kesimpulan_akhir: ['[50:12] Disetujui bersama'],
  });

  assert.ok(!text.includes('[10:30]'));
  assert.ok(!text.includes('[00:05]'));
  assert.ok(!text.includes('[10:35]'));
  assert.ok(!text.includes('[10:40]'));
  assert.ok(!text.includes('[50:12]'));
  assert.ok(text.includes('1. Topik: Anggaran'));
});

test('composeMinutesText menangani pilar kosong tanpa error', () => {
  const text = composeMinutesText({ ringkasan_utama: '', poin_pembahasan: [], kesimpulan_akhir: [] });
  assert.ok(text.includes('Tidak ada poin pembahasan'));
  assert.ok(text.includes('Tidak ada kesimpulan'));
});

test('composeMinutesText menjaga pemisahan paragraf pada ringkasan utama', () => {
  const text = composeMinutesText({
    ringkasan_utama: "Paragraf pembuka.\n\nParagraf pembahasan.\n\nParagraf kesimpulan.",
    poin_pembahasan: [],
    kesimpulan_akhir: [],
  });
  assert.ok(text.includes("Paragraf pembuka.\n\nParagraf pembahasan.\n\nParagraf kesimpulan."));
});

