import fs from 'node:fs';
import path from 'node:path';
import ffmpeg from 'fluent-ffmpeg';
import ffmpegInstaller from '@ffmpeg-installer/ffmpeg';
import ffprobeInstaller from '@ffprobe-installer/ffprobe';
import { JobCancelledError } from './throttler.js';

// Set path binary ffmpeg dan ffprobe otomatis sesuai platform (Windows/Linux/Mac)
if (ffmpegInstaller && ffmpegInstaller.path) {
  ffmpeg.setFfmpegPath(ffmpegInstaller.path);
}
if (ffprobeInstaller && ffprobeInstaller.path) {
  ffmpeg.setFfprobePath(ffprobeInstaller.path);
}

/**
 * Format angka zero-padded 3 digit (contoh: 1 -> '001', 12 -> '012')
 */
export function formatChunkIndex(index) {
  return String(index).padStart(3, '0');
}

/**
 * Membaca durasi total file audio dalam detik menggunakan ffprobe.
 *
 * @param {string} filePath Path absolut file audio
 * @returns {Promise<number>} Durasi total dalam detik
 */
export function probeDuration(filePath) {
  return new Promise((resolve, reject) => {
    if (!fs.existsSync(filePath)) {
      return reject(new Error(`File audio tidak ditemukan: ${filePath}`));
    }

    ffmpeg.ffprobe(filePath, (err, metadata) => {
      if (err) {
        return reject(new Error(`Gagal membaca durasi audio via ffprobe: ${err.message}`));
      }

      const duration = metadata?.format?.duration;
      if (duration === undefined || isNaN(duration)) {
        return reject(new Error('Format durasi audio tidak valid atau tidak terbaca.'));
      }

      resolve(Math.round(parseFloat(duration)));
    });
  });
}

/**
 * Memotong satu segmen audio menggunakan FFmpeg.
 */
function sliceSingleSegment(inputPath, outputPath, startTimeSeconds, durationSeconds) {
  return new Promise((resolve, reject) => {
    ffmpeg(inputPath)
      .setStartTime(startTimeSeconds)
      .setDuration(durationSeconds)
      .audioChannels(1) // Mono (menghemat ukuran file)
      .audioFrequency(16000) // 16kHz (optimal untuk STT Google)
      .audioBitrate('64k') // 64kbps CBR (sangat hemat bandwidth ~14MB per 30 menit)
      .format('mp3')
      .output(outputPath)
      .on('end', () => resolve(outputPath))
      .on('error', (err) => reject(new Error(`Gagal memotong chunk ${path.basename(outputPath)}: ${err.message}`)))
      .run();
  });
}

/**
 * Memotong file rekaman rapat sesuai rencana chunk sadar-hening.
 * `plan` berupa array [{index, start, duration}] dari vad.json, atau angka
 * durasi tetap (perilaku potongan seragam lama) untuk kompatibilitas.
 * Potongan ditulis ke file .part lalu di-rename agar job yang mati di tengah
 * slicing tidak meninggalkan chunk setengah jadi yang dianggap checkpoint sah.
 * Chunk yang sudah ada diverifikasi durasinya sebelum dipakai ulang.
 *
 * @param {string} inputPath Path absolut rekaman asli
 * @param {string} outputDir Direktori target potongan (folder `audio/`)
 * @param {Array|number} plan Rencana chunk atau durasi potongan tetap (detik)
 * @returns {Promise<Object>} Metadata pemotongan ({ totalDuration, totalChunks, chunkFiles })
 */
export async function sliceAudio(inputPath, outputDir, plan, cancelChecker = null, onLog = () => {}) {
  if (!fs.existsSync(inputPath)) {
    throw new Error(`File input rekaman tidak ditemukan: ${inputPath}`);
  }

  // Pastikan direktori output tersedia
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  const totalDuration = await probeDuration(inputPath);
  const chunkPlan = Array.isArray(plan)
    ? plan
    : fixedPlan(totalDuration, plan);
  const chunkFiles = [];

  for (const entry of chunkPlan) {
    if (cancelChecker && typeof cancelChecker === 'function') {
      const isCancelled = await cancelChecker();
      if (isCancelled) {
        throw new JobCancelledError();
      }
    }

    const chunkNum = formatChunkIndex(entry.index);
    const chunkFileName = `chunk_${chunkNum}.mp3`;
    const chunkFilePath = path.join(outputDir, chunkFileName);
    const partFilePath = path.join(outputDir, `${chunkFileName}.part`);
    const startTime = entry.start;
    const duration = Math.min(entry.duration, totalDuration - startTime);

    if (fs.existsSync(chunkFilePath)) {
      const isUsable = await verifyChunkDuration(chunkFilePath, duration);
      if (isUsable) {
        onLog(`[Slice] Chunk ${chunkFileName} sudah ada dan durasinya sah, melewati...`);
        chunkFiles.push(buildChunkEntry(entry.index, chunkFileName, chunkFilePath, startTime, duration));
        continue;
      }
      onLog(`[Slice] Chunk ${chunkFileName} rusak/tidak lengkap (durasi tidak sesuai target ${duration}s). Dibuat ulang...`);
      fs.unlinkSync(chunkFilePath);
    }

    if (fs.existsSync(partFilePath)) {
      fs.unlinkSync(partFilePath);
    }
    await sliceSingleSegment(inputPath, partFilePath, startTime, duration);
    fs.renameSync(partFilePath, chunkFilePath);

    chunkFiles.push(buildChunkEntry(entry.index, chunkFileName, chunkFilePath, startTime, duration));
  }

  return {
    totalDuration,
    totalChunks: chunkPlan.length,
    chunkFiles,
  };
}

function fixedPlan(totalDuration, chunkDurationSeconds) {
  // Pembagian merata (pembulatan terdekat) agar tidak ada chunk ekor kecil
  // yang tetap membayar satu request penuh. Pengaman TPM: chunk tunggal
  // maksimal ~110 menit audio meski pembulatan menghasilkan lebih sedikit.
  const MAX_CHUNK_SECONDS = 6600;
  const count = Math.max(
    Math.round(totalDuration / chunkDurationSeconds),
    Math.ceil(totalDuration / MAX_CHUNK_SECONDS),
    1
  );
  const base = totalDuration / count;
  return Array.from({ length: count }, (_, i) => ({
    index: i + 1,
    start: i * base,
    duration: Math.min(base, totalDuration - i * base),
  }));
}

function buildChunkEntry(index, filename, filePath, startTimeSeconds, durationSeconds) {
  return {
    index,
    chunkName: filename.replace('.mp3', ''),
    filename,
    path: filePath,
    startTimeSeconds,
    durationSeconds,
  };
}

async function verifyChunkDuration(filePath, expectedSeconds) {
  try {
    const actual = await probeDuration(filePath);
    return Math.abs(actual - expectedSeconds) <= 1;
  } catch {
    return false;
  }
}
