import { Type } from '@google/genai';
import { config } from '../../config.js';
import { callWithRetry, describeError } from '../throttler.js';
import { runWithModelChain } from '../modelChainRunner.js';
import { log as workerLog } from '../logger.js';
import { resolveThinkingLevel } from './client.js';
import { streamText } from './streamCall.js';
import { buildMinutesPrompt } from './prompts.js';

// Skema output risalah (structured output resmi) - bentuknya identik dengan
// struktur_json yang dikonsumsi aplikasi (3 Pilar).
export const MINUTES_RESPONSE_SCHEMA = {
  type: Type.OBJECT,
  properties: {
    ringkasan_utama: {
      type: Type.STRING,
      description: 'Intisari komprehensif rapat dalam 3-4 paragraf terpisah. Setiap paragraf WAJIB dipisahkan dengan karakter dua baris baru (\\n\\n). Dilarang menggabungkan seluruh teks menjadi satu paragraf panjang.',
    },
    poin_pembahasan: {
      type: Type.ARRAY,
      description: 'Rincian pembahasan per pokok bahasan.',
      items: {
        type: Type.OBJECT,
        properties: {
          topik: { type: Type.STRING, description: 'Judul pokok bahasan.' },
          pembicara: {
            type: Type.STRING,
            nullable: true,
            description: 'Nama lengkap pembicara dan jabatan/fraksi/instansi resminya (sesuai perkenalan diri dalam rapat dan konteks DPRD/Pemprov Sulawesi Tengah).',
          },
          uraian: { type: Type.STRING, description: 'Penjelasan materi, pertanyaan, tanggapan, atau catatan kritis.' },
        },
        required: ['topik', 'uraian'],
      },
    },
    kesimpulan_akhir: {
      type: Type.ARRAY,
      description: 'Butir kesepakatan, keputusan resmi, rekomendasi, dan instruksi tindak lanjut.',
      items: { type: Type.STRING },
    },
  },
  required: ['ringkasan_utama', 'poin_pembahasan', 'kesimpulan_akhir'],
};

export function stripTimestamps(text) {
  if (typeof text !== 'string') return '';
  return text.replace(/\[\d{1,2}:\d{2}(?::\d{2})?\]\s*/g, '').trim();
}

/**
 * Menyusun naskah risalah 3 bagian (I/II/III) dari struktur pilar yang
 * dihasilkan model. Format harus tetap kompatibel dengan parser fallback
 * di NotulenService::parsePillarsFromText (PHP).
 */
export function composeMinutesText(pillars) {
  const points = Array.isArray(pillars.poin_pembahasan) ? pillars.poin_pembahasan : [];
  const conclusions = Array.isArray(pillars.kesimpulan_akhir) ? pillars.kesimpulan_akhir : [];
  const rawSummary = stripTimestamps(String(pillars.ringkasan_utama || '')).trim();
  const cleanSummary = rawSummary.replace(/\r\n/g, '\n').replace(/\n{3,}/g, '\n\n');
  const lines = ['I. RINGKASAN UTAMA', cleanSummary, '', 'II. POIN-POIN PEMBAHASAN'];

  points.forEach((point, i) => {
    const no = i + 1;
    const topik = stripTimestamps(String(point.topik || '')).trim();
    lines.push(`${no}. Topik: ${topik}`);
    if (point.pembicara) {
      lines.push(`   - Pembicara: ${stripTimestamps(String(point.pembicara)).trim()}`);
    }
    lines.push(`   - Uraian: ${stripTimestamps(String(point.uraian || '')).trim()}`);
  });

  if (points.length === 0) {
    lines.push('(Tidak ada poin pembahasan yang terdeteksi.)');
  }

  lines.push('', 'III. KESIMPULAN & KEPUTUSAN AKHIR');
  conclusions.forEach((item, i) => {
    lines.push(`${i + 1}. ${stripTimestamps(String(item)).trim()}`);
  });
  if (conclusions.length === 0) {
    lines.push('(Tidak ada kesimpulan yang terdeteksi.)');
  }

  return lines.join('\n').trim();
}

/**
 * Menyusun draft Risalah Rapat Resmi DPRD Provinsi Sulawesi Tengah
 * dari kumpulan transkrip lengkap menggunakan rantai model AI dan
 * structured output (responseSchema).
 *
 * @param {Object} params
 * @param {string} params.fullTranscript Teks transkrip gabungan seluruh chunk
 * @param {Object} params.metadata Metadata rapat (judul_rapat, tanggal_rapat, jadwal_type)
 * @param {Function} params.cancelChecker Fungsi cek cancel
 * @param {Function} params.onLog Callback log
 * @returns {Promise<Object>} { ringkasan_eksekutif, pillars, usedModel }
 */
export async function generateMeetingMinutesWithFallback({
  fullTranscript,
  metadata = {},
  cancelChecker = null,
  onLog = workerLog,
}) {
  onLog(`[Minutes] Memulai penyusunan Risalah Rapat resmi dari transkrip (${fullTranscript.length} karakter)...`);

  const promptText = buildMinutesPrompt(metadata, fullTranscript);

  const { result: minutesJson, usedModel } = await runWithModelChain({
    taskLabel: 'Minutes',
    cancelChecker,
    onLog,
    attemptMessage: (modelName, mIdx, totalModels, passSuffix) =>
      `[Minutes] Mencoba model risalah: ${modelName} (Model ke-${mIdx + 1}/${totalModels}${passSuffix})...`,
    stallMessage: (pass, maxPasses, delaySec) =>
      `[Minutes] Seluruh model mengalami gangguan sementara pada putaran risalah ${pass}/${maxPasses}. Menunggu ${delaySec}s sebelum mengulang rantai...`,
    failureMessage: (lastError) =>
      `Seluruh rantai model AI gagal menyusun risalah rapat. Error terakhir: ${describeError(lastError)}`,
    runForModel: async (modelName) =>
      await callWithRetry(
        async (attempt) => {
          onLog(`[Minutes] Memanggil model ${modelName} untuk menyusun risalah (Percobaan ${attempt}/${config.worker.maxRetriesPerModel})...`);

          const startTime = Date.now();

          const accumulated = await streamText({
            modelName,
            contents: promptText,
            requestConfig: {
              responseMimeType: 'application/json',
              responseSchema: MINUTES_RESPONSE_SCHEMA,
              thinkingConfig: {
                thinkingLevel: resolveThinkingLevel(config.gemini.thinkingLevel),
              },
            },
            cancelChecker,
            onSilence: (sec, isConnected) => {
              if (isConnected) {
                onLog(`[Minutes] Tersambung ke ${modelName}, model sedang memproses transkrip (prefill/thinking)... [${sec}s tanpa keluaran teks]`);
              } else {
                onLog(`[Minutes] Belum mendapat respons dari server ${modelName} (antrean/kapasitas)... [${sec}s]`);
              }
            },
            onConnected: () => onLog(`[Minutes] Stream tersambung ke ${modelName}, menunggu keluaran pertama...`),
            onProgress: (text) => {
              const elapsedSec = Math.round((Date.now() - startTime) / 1000);
              onLog(`[Minutes] Menyusun risalah... [${elapsedSec}s | ${text.length.toLocaleString('id-ID')} karakter terkumpul]`);
            },
          });

          if (!accumulated || accumulated.trim().length === 0) {
            throw new Error(`Respons risalah model ${modelName} kosong.`);
          }

          onLog(`[Minutes] Stream selesai: risalah via ${modelName} [${((Date.now() - startTime) / 1000).toFixed(1)}s | ${accumulated.trim().length.toLocaleString('id-ID')} karakter]`);

          // Bersihkan kemungkinan markdown wrapping ```json ... ``` (antisipasi model noncompliant)
          let cleanedJson = accumulated.trim().replace(/^```json\s*/i, '').replace(/^```\s*/i, '').replace(/\s*```$/i, '').trim();

          let parsed;
          try {
            parsed = JSON.parse(cleanedJson);
          } catch (parseErr) {
            throw new Error(`Gagal mem-parse JSON hasil risalah dari model ${modelName}: ${parseErr.message}`, { cause: parseErr });
          }

          if (!parsed || typeof parsed !== 'object' || !('ringkasan_utama' in parsed)) {
            throw new Error(`Struktur risalah dari model ${modelName} tidak memenuhi skema (ringkasan_utama tidak ditemukan).`);
          }

          onLog(`[Minutes] Risalah rapat berhasil disusun via model ${modelName}!`);
          return parsed;
        },
        {
          maxRetries: config.worker.maxRetriesPerModel,
          initialDelayMs: 10000,
          backoffFactor: 2.5,
          failoverOn503: true,
          cancelChecker,
          onRetry: ({ attempt, waitTimeMs, error, isTpm }) => {
            const reason = isTpm ? 'Rate limit TPM' : describeError(error);
            onLog(`[Throttler] ${reason} saat generate risalah. Menunggu ${Math.round(waitTimeMs / 1000)}s sebelum retry ${attempt + 1}...`);
          },
        }
      ),
  });

  const rawPoints = Array.isArray(minutesJson.poin_pembahasan) ? minutesJson.poin_pembahasan : [];
  const cleanPoints = rawPoints.map((p, i) => ({
    topik: stripTimestamps(p.topik || ''),
    pembicara: p.pembicara ? stripTimestamps(p.pembicara) : null,
    uraian: stripTimestamps(p.uraian || ''),
    // Dikonsumsi parser fallback NotulenService::parsePillarsFromText (PHP)
    full_text: `${i + 1}. Topik: ${stripTimestamps(p.topik || '')}`,
  }));
  const rawConclusions = Array.isArray(minutesJson.kesimpulan_akhir) ? minutesJson.kesimpulan_akhir : [];
  const cleanConclusions = rawConclusions.map((c) => stripTimestamps(String(c)));
  const pillars = {
    ringkasan_utama: stripTimestamps(String(minutesJson.ringkasan_utama || '')),
    poin_pembahasan: cleanPoints,
    kesimpulan_akhir: cleanConclusions,
  };

  return {
    ringkasan_eksekutif: composeMinutesText(pillars),
    pillars,
    usedModel: usedModel || null,
  };
}
