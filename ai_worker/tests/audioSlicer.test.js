import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import ffmpeg from 'fluent-ffmpeg';
import ffmpegInstaller from '@ffmpeg-installer/ffmpeg';
import { sliceAudio, probeDuration } from '../services/audioSlicer.js';

ffmpeg.setFfmpegPath(ffmpegInstaller.path);

function makeTempDir() {
  return fs.mkdtempSync(path.join(os.tmpdir(), 'slicer-test-'));
}

function generateSine(outputPath, seconds) {
  return new Promise((resolve, reject) => {
    ffmpeg()
      .input(`sine=frequency=440:duration=${seconds}`)
      .inputFormat('lavfi')
      .audioChannels(1)
      .audioFrequency(16000)
      .format('mp3')
      .output(outputPath)
      .on('end', resolve)
      .on('error', reject)
      .run();
  });
}

test('sliceAudio memotong sesuai durasi dan tidak menyisakan file .part', async () => {
  const dir = makeTempDir();
  const input = path.join(dir, 'input.mp3');
  const out = path.join(dir, 'audio');
  await generateSine(input, 60);

  const result = await sliceAudio(input, out, 30);

  assert.equal(result.totalChunks, 2);
  assert.ok(fs.existsSync(path.join(out, 'chunk_001.mp3')));
  assert.ok(fs.existsSync(path.join(out, 'chunk_002.mp3')));
  assert.ok(!fs.existsSync(path.join(out, 'chunk_001.mp3.part')));
  assert.equal(result.chunkFiles[1].durationSeconds, 30);
  fs.rmSync(dir, { recursive: true, force: true });
});

test('chunk rusak terdeteksi dan dibuat ulang, chunk sah dilewati', async () => {
  const dir = makeTempDir();
  const input = path.join(dir, 'input.mp3');
  const out = path.join(dir, 'audio');
  fs.mkdirSync(out);
  await generateSine(input, 60);

  // Pre-run untuk menghasilkan chunk sah
  await sliceAudio(input, out, 30);
  const validMtime = fs.statSync(path.join(out, 'chunk_001.mp3')).mtimeMs;

  // Rusak chunk_002 dengan audio 10s (target 30s)
  const wrong = path.join(dir, 'wrong.mp3');
  await generateSine(wrong, 10);
  fs.copyFileSync(wrong, path.join(out, 'chunk_002.mp3'));

  // Tinggalkan file .part basi
  fs.writeFileSync(path.join(out, 'chunk_002.mp3.part'), 'junk');

  const logs = [];
  const result = await sliceAudio(input, out, 30, null, (m) => logs.push(m));
  const joined = logs.join('\n');

  assert.ok(joined.includes('chunk_002.mp3 rusak/tidak lengkap'));
  assert.ok(joined.includes('Dibuat ulang'));
  assert.ok(joined.includes('chunk_001.mp3 sudah ada dan durasinya sah'));
  assert.equal(result.chunkFiles[1].durationSeconds, 30);

  const rebuilt = await probeDuration(path.join(out, 'chunk_002.mp3'));
  assert.ok(Math.abs(rebuilt - 30) <= 1, `durasi chunk_002 dibangun ulang: ${rebuilt}`);

  const newMtime = fs.statSync(path.join(out, 'chunk_001.mp3')).mtimeMs;
  assert.equal(newMtime, validMtime, 'chunk sah tidak boleh ditulis ulang');
  assert.ok(!fs.existsSync(path.join(out, 'chunk_002.mp3.part')), 'file .part basi dibersihkan');

  fs.rmSync(dir, { recursive: true, force: true });
});
