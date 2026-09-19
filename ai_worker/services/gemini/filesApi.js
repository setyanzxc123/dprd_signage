import { getAiClient } from './client.js';
import { sleep } from '../throttler.js';
import { log as workerLog, warn as workerWarn } from '../logger.js';

/**
 * Unggah file audio potongan ke Google Gemini Files API.
 * Wajib digunakan untuk file berukuran > 20 MB (batas inline).
 */
export async function uploadToFilesApi(filePath, mimeType = 'audio/mp3') {
  const ai = getAiClient();
  const fileUpload = await ai.files.upload({
    file: filePath,
    config: { mimeType },
  });

  return fileUpload;
}

/**
 * Hapus file dari Google Gemini Files API seketika setelah selesai diproses.
 */
export async function deleteFromFilesApi(fileResource) {
  if (!fileResource) return;
  try {
    const ai = getAiClient();
    const fileName = typeof fileResource === 'string' ? fileResource : fileResource.name;
    if (fileName) {
      await ai.files.delete({ name: fileName });
    }
  } catch (err) {
    // Log kegagalan cleanup tanpa menghentikan pipeline
    workerWarn(`[GeminiService] Peringatan: Gagal menghapus file cloud ${fileResource?.name || fileResource}:`, err.message);
  }
}

/**
 * Menunggu file Files API mencapai state ACTIVE sebelum direferensikan
 * generateContent. FAILED mengembalikan error ingest.
 */
export async function waitForFileActive(getFile, nameOrFile, {
  intervalMs = 2000,
  timeoutMs = 120000,
  onLog = workerLog,
} = {}) {
  if (nameOrFile && typeof nameOrFile === 'object' && nameOrFile.state === 'ACTIVE') {
    return nameOrFile;
  }
  const name = typeof nameOrFile === 'string' ? nameOrFile : (nameOrFile?.name || nameOrFile?.uri);
  const deadline = Date.now() + timeoutMs;
  for (;;) {
    const file = await getFile(name);
    if (file.state === 'ACTIVE') {
      return file;
    }
    if (file.state === 'FAILED') {
      throw new Error(`Gagal ingest file ${name} di Files API (state FAILED).`);
    }
    if (Date.now() >= deadline) {
      throw new Error(`Timeout menunggu file ${name} ACTIVE di Files API (state terakhir: ${file.state}).`);
    }
    onLog(`[Files API] ${name} masih ${file.state}, menunggu hingga ACTIVE...`);
    await sleep(intervalMs);
  }
}
