import { execFile } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { promisify } from 'node:util';
import ffmpegInstaller from '@ffmpeg-installer/ffmpeg';
import { config } from '../config.js';
import { probeDuration } from './audioSlicer.js';

const execFileAsync = promisify(execFile);

/**
 * Voice Activity Detection ringan berbasis ffmpeg silencedetect.
 * Hasil analisis disimpan sebagai job_{id}/vad.json sebelum slicing agar
 * rencana chunk deterministik saat job di-resume.
 */

function ffmpegBinary() {
  return ffmpegInstaller && ffmpegInstaller.path ? ffmpegInstaller.path : 'ffmpeg';
}

/**
 * Parse output stderr silencedetect menjadi daftar rentang hening.
 * silence_start tanpa pasangan silence_end berarti hening sampai akhir rekaman.
 */
export function parseSilencedetectOutput(stderr, totalDuration) {
  const silences = [];
  const starts = [...stderr.matchAll(/silence_start:\s*([\d.]+)/g)].map((m) => parseFloat(m[1]));
  const ends = [...stderr.matchAll(/silence_end:\s*([\d.]+)/g)].map((m) => parseFloat(m[1]));

  for (let i = 0; i < starts.length; i++) {
    const start = starts[i];
    const end = i < ends.length ? ends[i] : totalDuration;
    if (end > start) {
      silences.push({ start, end });
    }
  }

  return silences;
}

/**
 * Segmen bicara = komplemen rentang hening di atas [0, duration].
 */
export function buildSpeechSegments(silences, duration) {
  const speech = [];
  let cursor = 0;

  for (const silence of silences) {
    const start = Math.min(silence.start, duration);
    const end = Math.min(silence.end, duration);
    if (start > cursor) {
      speech.push({ start: cursor, end: start });
    }
    cursor = Math.max(cursor, end);
  }

  if (cursor < duration) {
    speech.push({ start: cursor, end: duration });
  }

  return speech.filter((segment) => segment.end - segment.start > 0.001);
}

/**
 * Laporan per jendela chunk (durasi tetap): berapa detik bicara dan rasionya.
 */
export function analyzeChunks(speech, duration, chunkDurationSeconds) {
  const totalChunks = Math.max(1, Math.ceil(duration / chunkDurationSeconds));
  const reports = [];

  for (let i = 1; i <= totalChunks; i++) {
    const start = (i - 1) * chunkDurationSeconds;
    const end = Math.min(i * chunkDurationSeconds, duration);
    const windowDuration = end - start;
    let speechSeconds = 0;

    for (const segment of speech) {
      const overlapStart = Math.max(segment.start, start);
      const overlapEnd = Math.min(segment.end, end);
      if (overlapEnd > overlapStart) {
        speechSeconds += overlapEnd - overlapStart;
      }
    }

    reports.push({
      index: i,
      start,
      end,
      duration: windowDuration,
      speech_seconds: Math.round(speechSeconds * 100) / 100,
      ratio: windowDuration > 0 ? Math.round((speechSeconds / windowDuration) * 10000) / 10000 : 0,
    });
  }

  return reports;
}

/**
 * Jalankan pass VAD: decode sekali via silencedetect, kembalikan rentang hening.
 */
export async function detectSilences(inputPath, totalDuration, { onLog = () => {} } = {}) {
  const args = [
    '-hide_banner',
    '-nostats',
    '-i', inputPath,
    '-af', `silencedetect=noise=${config.vad.silenceDb}dB:d=${config.vad.minSilenceSeconds}`,
    '-f', 'null',
    '-',
  ];

  onLog(`[VAD] Menganalisis hening/bicara (${config.vad.silenceDb}dB, min ${config.vad.minSilenceSeconds}s)...`);
  const started = Date.now();

  try {
    const { stderr } = await execFileAsync(ffmpegBinary(), args, { maxBuffer: 10 * 1024 * 1024 });
    const silences = parseSilencedetectOutput(stderr, totalDuration);
    onLog(`[VAD] Selesai dalam ${((Date.now() - started) / 1000).toFixed(1)}s: ${silences.length} rentang hening terdeteksi.`);
    return silences;
  } catch (err) {
    if (err.stderr) {
      // ffmpeg kadang keluar non-zero namun tetap menulis hasil deteksi
      const silences = parseSilencedetectOutput(err.stderr, totalDuration);
      onLog(`[VAD] ffmpeg keluar dengan kode ${err.code}, ${silences.length} rentang hening tetap terbaca.`);
      return silences;
    }
    throw new Error(`Gagal menjalankan silencedetect: ${err.message}`);
  }
}

/**
 * Menyusun rencana chunk sadar-hening: batas antar chunk digeser ke titik
 * hening terdekat dari target kelipatan durasi chunk (dalam toleransi).
 * Rencana disimpan ke vad.json sehingga resume mereproduksi batas yang sama.
 */
export function planChunks(duration, silences, chunkDurationSeconds, toleranceSeconds) {
  const count = Math.max(1, Math.ceil(duration / chunkDurationSeconds));
  const cuts = [];
  let prevCut = 0;

  for (let k = 1; k < count; k++) {
    const target = k * chunkDurationSeconds;
    if (target >= duration) {
      break;
    }
    const cut = nearestSilencePoint(target, silences, toleranceSeconds);
    // Jaga agar chunk tetap berurutan dan tidak degeneratif
    const bounded = Math.min(Math.max(cut, prevCut + 1), duration - 1);
    cuts.push(bounded);
    prevCut = bounded;
  }

  const plan = [];
  let start = 0;
  for (const cut of cuts) {
    plan.push({ index: plan.length + 1, start, duration: cut - start });
    start = cut;
  }
  plan.push({ index: plan.length + 1, start, duration: duration - start });

  return plan;
}

function nearestSilencePoint(target, silences, toleranceSeconds) {
  let best = target;
  let bestDist = Infinity;

  for (const silence of silences) {
    const clamped = Math.min(Math.max(target, silence.start), silence.end);
    const dist = Math.abs(clamped - target);
    if (dist < bestDist) {
      bestDist = dist;
      best = clamped;
    }
  }

  return bestDist <= toleranceSeconds ? best : target;
}

/**
 * Analisis lengkap + rencana chunk + penulisan vad.json.
 */
export async function runVadAnalysis(inputPath, outputDir, { onLog = () => {} } = {}) {
  const totalDuration = await probeDuration(inputPath);
  const silences = await detectSilences(inputPath, totalDuration, { onLog });
  const speech = buildSpeechSegments(silences, totalDuration);
  const chunkReports = analyzeChunks(speech, totalDuration, config.audio.chunkDurationSeconds);
  const totalSpeech = chunkReports.reduce((sum, report) => sum + report.speech_seconds, 0);

  const analysis = {
    generated_at: new Date().toISOString(),
    duration: totalDuration,
    params: {
      silence_db: config.vad.silenceDb,
      min_silence_seconds: config.vad.minSilenceSeconds,
      chunk_duration_seconds: config.audio.chunkDurationSeconds,
      tolerance_seconds: config.vad.toleranceSeconds,
      skip_speech_ratio: config.vad.skipSpeechRatio,
    },
    speech_ratio: totalDuration > 0 ? Math.round((totalSpeech / totalDuration) * 10000) / 10000 : 0,
    silences,
    speech,
    plan: planChunks(totalDuration, silences, config.audio.chunkDurationSeconds, config.vad.toleranceSeconds),
    chunks: chunkReports,
  };

  fs.mkdirSync(outputDir, { recursive: true });
  const vadPath = path.join(outputDir, 'vad.json');
  fs.writeFileSync(vadPath + '.part', JSON.stringify(analysis));
  fs.renameSync(vadPath + '.part', vadPath);

  for (const report of chunkReports) {
    onLog(`[VAD] Bagian ${report.index}: rasio bicara ${(report.ratio * 100).toFixed(1)}% (${report.speech_seconds.toFixed(0)}s / ${report.duration.toFixed(0)}s).`);
  }

  return analysis;
}

/**
 * Muat vad.json bila masih valid untuk file dan parameter saat ini;
 * jika tidak, jalankan analisis ulang. Memastikan resume reproduksi
 * batas chunk yang sama persis.
 */
export async function loadOrAnalyze(inputPath, outputDir, { onLog = () => {} } = {}) {
  const vadPath = path.join(outputDir, 'vad.json');
  const totalDuration = await probeDuration(inputPath);
  const currentParams = {
    silence_db: config.vad.silenceDb,
    min_silence_seconds: config.vad.minSilenceSeconds,
    chunk_duration_seconds: config.audio.chunkDurationSeconds,
    tolerance_seconds: config.vad.toleranceSeconds,
    skip_speech_ratio: config.vad.skipSpeechRatio,
  };

  if (fs.existsSync(vadPath)) {
    try {
      const existing = JSON.parse(fs.readFileSync(vadPath, 'utf-8'));
      if (existing.duration === totalDuration && JSON.stringify(existing.params) === JSON.stringify(currentParams)) {
        onLog(`[VAD] Memakai ulang analisis yang tersimpan (vad.json).`);
        return existing;
      }
      onLog(`[VAD] vad.json tidak cocok dengan parameter/durasi saat ini, menganalisis ulang...`);
    } catch {
      onLog(`[VAD] vad.json rusak, menganalisis ulang...`);
    }
  }

  return runVadAnalysis(inputPath, outputDir, { onLog });
}
