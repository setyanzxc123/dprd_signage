import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import ffmpeg from 'fluent-ffmpeg';
import ffmpegInstaller from '@ffmpeg-installer/ffmpeg';
import { parseSilencedetectOutput, buildSpeechSegments, analyzeChunks, planChunks, attachSpeechStats, isChunkSilent, runVadAnalysis, loadOrAnalyze } from '../services/vad.js';

test.beforeEach(() => {
  if (ffmpegInstaller && ffmpegInstaller.path) {
    ffmpeg.setFfmpegPath(ffmpegInstaller.path);
  }
});

test('parse output silencedetect menjadi rentang hening', () => {
  const stderr = [
    '[silencedetect @ 0x1] silence_start: 20.036',
    '[silencedetect @ 0x1] silence_end: 40.036 | silence_duration: 20',
    '[silencedetect @ 0x1] silence_start: 50',
  ].join('\n');

  const silences = parseSilencedetectOutput(stderr, 60);
  assert.deepEqual(silences, [
    { start: 20.036, end: 40.036 },
    { start: 50, end: 60 },
  ]);
});

test('parse stderr tanpa hening menghasilkan daftar kosong', () => {
  assert.deepEqual(parseSilencedetectOutput('no silence here', 30), []);
});

test('segmen bicara adalah komplemen rentang hening', () => {
  const speech = buildSpeechSegments(
    [{ start: 10, end: 20 }],
    30
  );
  assert.deepEqual(speech, [
    { start: 0, end: 10 },
    { start: 20, end: 30 },
  ]);
});

test('analisis per chunk menghitung rasio bicara proporsional', () => {
  const speech = [
    { start: 0, end: 900 },
    { start: 3600, end: 4000 },
  ];
  const reports = analyzeChunks(speech, 7200, 1800);

  assert.equal(reports.length, 4);
  assert.equal(reports[0].ratio, 0.5);
  assert.equal(reports[0].speech_seconds, 900);
  assert.equal(reports[1].ratio, 0);
  assert.equal(reports[2].ratio, Math.round((400 / 1800) * 10000) / 10000);
  assert.equal(reports[3].duration, 1800);
});

test('planChunks menggeser batas ke titik hening dalam toleransi', () => {
  const plan = planChunks(190, [{ start: 88, end: 92 }], 100, 30);
  assert.equal(plan.length, 2);
  assert.equal(plan[0].start, 0);
  assert.equal(plan[0].duration, 92, 'batas bergeser ke akhir hening (92s)');
  assert.equal(plan[1].start, 92);
  assert.equal(plan[1].duration, 98);
});

test('planChunks memotong tepat di tengah hening yang membentang target', () => {
  const plan = planChunks(190, [{ start: 88, end: 112 }], 100, 30);
  assert.equal(plan[0].duration, 95, 'target merata 95 ada di dalam hening, tidak digeser');
});

test('planChunks mengabaikan hening di luar toleransi', () => {
  const plan = planChunks(190, [{ start: 50, end: 55 }], 100, 30);
  assert.equal(plan[0].duration, 95, 'hening 55s jaraknya 40s > toleransi 30s');
});

test('planChunks tanpa hening menghasilkan potongan merata', () => {
  const plan = planChunks(190, [], 100, 30);
  assert.deepEqual(plan.map((entry) => entry.duration), [95, 95]);
});

test('planChunks membagi merata sehingga tidak ada chunk ekor kecil', () => {
  // Motivasi C7.4: 125 menit dengan chunk 60 menit -> 60+60+5 (3 request)
  // harusnya 2 chunk merata 62.5 menit (2 request)
  const plan = planChunks(125 * 60, [], 60 * 60, 0);
  assert.equal(plan.length, 2);
  assert.deepEqual(plan.map((entry) => entry.duration), [62.5 * 60, 62.5 * 60]);

  // 165 menit -> round(2.75)=3 chunk merata @55 menit (semua di bawah target)
  const plan165 = planChunks(165 * 60, [], 60 * 60, 0);
  assert.equal(plan165.length, 3);
  assert.deepEqual(plan165.map((entry) => entry.duration), [55 * 60, 55 * 60, 55 * 60]);
});

test('planChunks menjumlah durasi persis durasi total dan berurutan', () => {
  const plan = planChunks(7200, [{ start: 1790, end: 1815 }, { start: 3585, end: 3610 }], 1800, 180);
  const total = plan.reduce((sum, entry) => sum + entry.duration, 0);
  assert.equal(total, 7200);
  for (let i = 1; i < plan.length; i++) {
    assert.ok(plan[i].start >= plan[i - 1].start + plan[i - 1].duration - 0.001);
  }
});

test('attachSpeechStats menambahkan rasio bicara per entri rencana', () => {
  const plan = [
    { index: 1, start: 0, duration: 100 },
    { index: 2, start: 100, duration: 90 },
  ];
  const speech = [{ start: 0, end: 50 }, { start: 100, end: 190 }];
  const stats = attachSpeechStats(plan, speech);

  assert.equal(stats[0].speech_seconds, 50);
  assert.equal(stats[0].ratio, 0.5);
  assert.equal(stats[1].ratio, 1);
});

test('isChunkSilent sesuai ambang rasio bicara', () => {
  assert.equal(isChunkSilent({ ratio: 0.03 }, 0.05), true);
  assert.equal(isChunkSilent({ ratio: 0.1 }, 0.05), false);
  assert.equal(isChunkSilent(null, 0.05), false, 'tanpa statistik, chunk tidak dianggap hening');
});

test('vad.json memuat rencana chunk yang durasinya menjumlah durasi total', async () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'vad-test-'));
  const input = path.join(dir, 'input.mp3');
  await new Promise((resolve, reject) => {
    ffmpeg()
      .input('sine=440:d=20').inputFormat('lavfi')
      .input('aevalsrc=0:s=16000:d=20').inputFormat('lavfi')
      .input('sine=440:d=20').inputFormat('lavfi')
      .complexFilter('[0:a][1:a][2:a]concat=n=3:v=0:a=1[out]')
      .outputOptions(['-map', '[out]'])
      .audioChannels(1)
      .audioFrequency(16000)
      .format('mp3')
      .output(input)
      .on('end', resolve)
      .on('error', reject)
      .run();
  });

  const logs = [];
  const analysis = await runVadAnalysis(input, dir, { onLog: (m) => logs.push(m) });

  assert.equal(analysis.duration, 60);
  assert.ok(analysis.speech_ratio > 0.3 && analysis.speech_ratio < 0.75, `rasio bicara ${analysis.speech_ratio}`);
  assert.ok(analysis.silences.length >= 1, 'jeda hening 20s terdeteksi');
  const silence = analysis.silences.find((s) => s.end - s.start >= 15);
  assert.ok(silence, `ada rentang hening >= 15s: ${JSON.stringify(analysis.silences)}`);
  assert.ok(fs.existsSync(path.join(dir, 'vad.json')));
  assert.ok(!fs.existsSync(path.join(dir, 'vad.json.part')));

  const saved = JSON.parse(fs.readFileSync(path.join(dir, 'vad.json'), 'utf-8'));
  assert.ok(Array.isArray(saved.plan) && saved.plan.length >= 1);
  const planTotal = saved.plan.reduce((sum, entry) => sum + entry.duration, 0);
  assert.ok(Math.abs(planTotal - 60) <= 1, `rencana menjumlah ~60s: ${planTotal}`);

  // loadOrAnalyze memakai ulang file yang sama tanpa analisis ulang
  const reused = await loadOrAnalyze(input, dir, { onLog: () => {} });
  assert.equal(reused.generated_at, saved.generated_at, 'analisis tidak diulang saat parameter sama');

  fs.rmSync(dir, { recursive: true, force: true });
});
