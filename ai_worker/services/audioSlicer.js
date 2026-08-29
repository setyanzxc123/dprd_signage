import fs from 'node:fs';
import path from 'node:path';
import ffmpeg from 'fluent-ffmpeg';
import ffmpegInstaller from '@ffmpeg-installer/ffmpeg';
import ffprobeInstaller from '@ffprobe-installer/ffprobe';

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
 * Memotong file rekaman rapat menjadi beberapa potongan per 30 menit (1.800 detik).
 *
 * @param {string} inputPath Path absolut rekaman asli
 * @param {string} outputDir Direktori target potongan (folder `audio/`)
 * @param {number} chunkDurationSeconds Durasi potongan dalam detik (default: 1800)
 * @returns {Promise<Object>} Metadata pemotongan ({ totalDuration, totalChunks, chunkFiles })
 */
export async function sliceAudio(inputPath, outputDir, chunkDurationSeconds = 1800, cancelChecker = null) {
  if (!fs.existsSync(inputPath)) {
    throw new Error(`File input rekaman tidak ditemukan: ${inputPath}`);
  }

  // Pastikan direktori output tersedia
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  const totalDuration = await probeDuration(inputPath);
  const totalChunks = Math.max(1, Math.ceil(totalDuration / chunkDurationSeconds));
  const chunkFiles = [];

  for (let i = 1; i <= totalChunks; i++) {
    if (cancelChecker && typeof cancelChecker === 'function') {
      const isCancelled = await cancelChecker();
      if (isCancelled) {
        throw new Error('JOB_CANCELLED_BY_ADMIN');
      }
    }

    const chunkNum = formatChunkIndex(i);
    const chunkFileName = `chunk_${chunkNum}.mp3`;
    const chunkFilePath = path.join(outputDir, chunkFileName);
    const startTime = (i - 1) * chunkDurationSeconds;
    const duration = Math.min(chunkDurationSeconds, totalDuration - startTime);

    // Jika file chunk sudah ada dari proses sebelumnya, lewati pembuatan ulang
    if (!fs.existsSync(chunkFilePath)) {
      await sliceSingleSegment(inputPath, chunkFilePath, startTime, duration);
    }

    chunkFiles.push({
      index: i,
      chunkName: `chunk_${chunkNum}`,
      filename: chunkFileName,
      path: chunkFilePath,
      startTimeSeconds: startTime,
      durationSeconds: duration,
    });
  }

  return {
    totalDuration,
    totalChunks,
    chunkFiles,
  };
}
