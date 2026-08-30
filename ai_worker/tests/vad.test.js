import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import ffmpeg from 'fluent-ffmpeg';
import ffmpegInstaller from '@ffmpeg-installer/ffmpeg';
import { parseSilencedetectOutput, buildSpeechSegments, analyzeChunks, runVadAnalysis } from '../services/vad.js';

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

test('runVadAnalysis menulis vad.json dari audio nyata dengan jeda hening', async () => {
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

  fs.rmSync(dir, { recursive: true, force: true });
});
