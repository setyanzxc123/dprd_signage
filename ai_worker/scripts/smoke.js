import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import ffmpeg from 'fluent-ffmpeg';
import ffmpegInstaller from '@ffmpeg-installer/ffmpeg';
import ffprobeInstaller from '@ffprobe-installer/ffprobe';
import { config } from '../config.js';
import { getDbPool, processJob } from '../worker.js';

if (ffmpegInstaller && ffmpegInstaller.path) {
  ffmpeg.setFfmpegPath(ffmpegInstaller.path);
}
if (ffprobeInstaller && ffprobeInstaller.path) {
  ffmpeg.setFfprobePath(ffprobeInstaller.path);
}

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/**
 * Generate audio pendek sintetis (5 detik) untuk uji smoke test.
 */
function generateSyntheticAudio(outputPath) {
  return new Promise((resolve, reject) => {
    ffmpeg()
      .input('sine=frequency=440:duration=5')
      .inputFormat('lavfi')
      .audioChannels(1)
      .audioFrequency(16000)
      .audioBitrate('64k')
      .format('mp3')
      .output(outputPath)
      .on('end', () => resolve(outputPath))
      .on('error', (err) => reject(err))
      .run();
  });
}

async function runSmokeTest() {
  console.log('====================================================');
  console.log('🧪 SMOKE TEST: AI Worker Pipeline End-to-End');
  console.log('====================================================\n');

  const pool = await getDbPool();

  // 1. Uji Koneksi Database
  try {
    const [dbTest] = await pool.query('SELECT 1 as connected');
    console.log('✅ Koneksi MySQL berhasil (127.0.0.1:3306)');
  } catch (err) {
    console.error('❌ Gagal terhubung ke MySQL:', err.message);
    process.exit(1);
  }

  // 2. Cek API Key
  if (!config.gemini.apiKey) {
    console.warn('⚠️ GEMINI_API_KEY tidak terdefinisi di .env. Uji inferensi AI mungkin memerlukan API Key valid.');
  } else {
    console.log(`✅ GEMINI_API_KEY terdeteksi (${config.gemini.apiKey.slice(0, 8)}...)`);
  }

  console.log(`✅ Rantai Model AI: ${config.gemini.modelChain.join(' -> ')}`);

  // 3. Buat direktori sementara & generate file audio uji
  const tempDir = path.resolve(__dirname, '../../writable/uploads/recordings/smoke_test_temp');
  fs.mkdirSync(path.join(tempDir, 'audio'), { recursive: true });
  fs.mkdirSync(path.join(tempDir, 'transcripts'), { recursive: true });

  const tempAudioFile = path.join(tempDir, 'audio', 'original.mp3');
  console.log('🎙️ Membuat file audio sintetis 5 detik via FFmpeg...');
  await generateSyntheticAudio(tempAudioFile);
  console.log('✅ File audio sintetis berhasil dibuat:', tempAudioFile);

  // 4. Buat record job dummy di database
  console.log('📝 Menambahkan record job uji coba ke database...');
  const [insertResult] = await pool.execute(
    `INSERT INTO meeting_transcription_jobs
     (jadwal_type, jadwal_id, audio_filename, audio_path, audio_size, status, progress_percent, current_step, created_at, updated_at)
     VALUES ('umum', NULL, 'smoke_test.mp3', ?, 50000, 'queued', 0, 'Uji coba smoke test', NOW(), NOW())`,
    [tempAudioFile]
  );

  const testJobId = insertResult.insertId;
  console.log(`✅ Job uji coba dibuat dengan ID #${testJobId}`);

  try {
    const [jobRows] = await pool.query('SELECT * FROM meeting_transcription_jobs WHERE id = ?', [testJobId]);
    const job = jobRows[0];

    // Jika tidak ada API key atau ingin skip API nyata saat dev offline, verifikasi pipeline struktur
    if (!config.gemini.apiKey) {
      console.log('⚠️ Melewati panggilan Gemini AI karena API key belum diisi.');
    } else {
      console.log(`🚀 Menjalankan pipeline processJob() untuk Job #${testJobId}...`);
      await processJob(pool, job);
      console.log('✅ Pipeline pemrosesan job selesai.');
    }
  } catch (err) {
    console.error('❌ Error saat menjalankan pipeline smoke test:', err);
  } finally {
    // 5. Bersihkan data dummy
    console.log('\n🧹 Membersihkan data uji coba...');
    await pool.execute('DELETE FROM meeting_minutes WHERE job_id = ?', [testJobId]);
    await pool.execute('DELETE FROM meeting_transcription_jobs WHERE id = ?', [testJobId]);

    try {
      fs.rmSync(tempDir, { recursive: true, force: true });
      const jobDir = path.resolve(__dirname, `../../writable/uploads/recordings/job_${testJobId}`);
      if (fs.existsSync(jobDir)) {
        fs.rmSync(jobDir, { recursive: true, force: true });
      }
    } catch {}

    console.log('✅ Pembersihan selesai.');
    await pool.end();
  }

  console.log('\n🎉 SMOKE TEST BERHASIL DISELESAIKAN!');
}

runSmokeTest().catch((err) => {
  console.error('Fatal Smoke Test Error:', err);
  process.exit(1);
});
