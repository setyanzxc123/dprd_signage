import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import dotenv from 'dotenv';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Baca file .env dari root proyek
const rootEnvPath = path.resolve(__dirname, '../.env');
dotenv.config({ path: rootEnvPath });

function getEnv(key, defaultValue = '') {
  return process.env[key] !== undefined && process.env[key] !== ''
    ? process.env[key]
    : defaultValue;
}

function toInt(raw, fallback) {
  const parsed = parseInt(String(raw), 10);
  return Number.isNaN(parsed) ? fallback : parsed;
}

function toFloat(raw, fallback) {
  const parsed = parseFloat(String(raw));
  return Number.isNaN(parsed) ? fallback : parsed;
}

const rawHost = getEnv('database.default.hostname', getEnv('DB_HOST', '127.0.0.1'));
const configuredSocket = getEnv('database.default.socket', getEnv('DB_SOCKET', ''));

const candidateSockets = [
  configuredSocket,
  rawHost.startsWith('/') ? rawHost : '',
  '/run/mysqld/mysqld.sock',
  '/var/run/mysqld/mysqld.sock',
  '/var/lib/mysql/mysql.sock',
  '/tmp/mysql.sock',
].filter(Boolean);

let detectedSocket = '';
if (configuredSocket && fs.existsSync(configuredSocket)) {
  detectedSocket = configuredSocket;
} else if (rawHost.startsWith('/') && fs.existsSync(rawHost)) {
  detectedSocket = rawHost;
} else if (process.platform === 'linux' && (rawHost === 'localhost' || configuredSocket !== '')) {
  detectedSocket = candidateSockets.find((p) => fs.existsSync(p)) || '';
}

// Database config (kompatibel dengan format CI4 .env maupun variabel standar)
const dbConfig = {
  user: getEnv('database.default.username', getEnv('DB_USER', 'root')),
  password: getEnv('database.default.password', getEnv('DB_PASS', '')),
  database: getEnv('database.default.database', getEnv('DB_NAME', 'dprd_signage')),
  waitForConnections: true,
  connectionLimit: 5,
  queueLimit: 0,
};

if (detectedSocket) {
  dbConfig.socketPath = detectedSocket;
} else {
  dbConfig.host = rawHost;
  dbConfig.port = toInt(getEnv('database.default.port', getEnv('DB_PORT', '3306')), 3306);
}

// Gemini API Key & Model Chain
const geminiApiKey = getEnv('GEMINI_API_KEY', getEnv('GOOGLE_AI_API_KEY', getEnv('AI_GEMINI_KEY', '')));

const DEFAULT_MODEL_CHAIN = [
  'gemini-3.7-flash',
  'gemini-3.5-flash',
];

const modelChainRaw = getEnv('GEMINI_MODEL_CHAIN', '');
const parsedModelChain = modelChainRaw
  ? modelChainRaw.split(',').map((m) => m.trim()).filter((m) => m.length > 0)
  : [];

const geminiThinkingLevel = getEnv('GEMINI_THINKING_LEVEL', 'LOW').toUpperCase();

export const config = {
  db: dbConfig,
  gemini: {
    apiKey: geminiApiKey,
    modelChain: parsedModelChain.length > 0 ? parsedModelChain : DEFAULT_MODEL_CHAIN,
    thinkingLevel: geminiThinkingLevel,
  },
  audio: {
    chunkDurationSeconds: toInt(getEnv('CHUNK_DURATION_SECONDS', '1800'), 1800),
    safetyDelayMs: toInt(getEnv('SAFETY_DELAY_MS', '0'), 0),
  },
  vad: {
    // Ambang kebisingan (dB) dan durasi minimum hening (detik) untuk silencedetect
    silenceDb: toInt(getEnv('VAD_SILENCE_DB', '-35'), -35),
    minSilenceSeconds: toInt(getEnv('VAD_MIN_SILENCE_S', '2'), 2),
    // Batas pergeseran titik potong chunk mencari titik hening (detik)
    toleranceSeconds: toInt(getEnv('VAD_TOLERANCE_S', '180'), 180),
    // Pengabaian chunk hening
    skipSilentChunks: ['true', '1', 'yes'].includes(String(getEnv('VAD_SKIP_SILENT_CHUNKS', 'false')).toLowerCase().trim()),
    // Chunk dengan rasio bicara di bawah nilai ini dilewati jika skipSilentChunks aktif
    skipSpeechRatio: toFloat(getEnv('VAD_SKIP_SPEECH_RATIO', '0.05'), 0.05),
  },
  worker: {
    pollIntervalMs: toInt(getEnv('WORKER_POLL_INTERVAL_MS', '5000'), 5000), // 5 detik
    maxRetriesPerModel: toInt(getEnv('MAX_RETRIES_PER_MODEL', '4'), 4),
    // Job in-progress lebih tua dari nilai ini dianggap basi saat startup reset
    staleThresholdMinutes: toInt(getEnv('WORKER_STALE_THRESHOLD_MIN', '15'), 15),
  },
  validation: {
    // Minimum kata per menit audio — threshold konservatif untuk percakapan rapat
    // Rata-rata manusia berbicara 120-150 kata/menit; 30 kata/menit adalah batas bawah
    // yang mencakup kondisi audio buruk, banyak jeda, atau rapat formal lambat
    minWordsPerMinute: toInt(getEnv('VALIDATION_MIN_WORDS_PER_MINUTE', '30'), 30),
    // Jumlah karakter minimum agar cek abrupt-cut dijalankan
    // (transkrip sangat pendek tidak perlu dicek ending-nya)
    abruptCutMinLength: toInt(getEnv('VALIDATION_ABRUPT_CUT_MIN_LENGTH', '500'), 500),
  },
  paths: {
    root: path.resolve(__dirname, '..'),
    recordingsBaseDir: path.resolve(__dirname, '../writable/uploads/recordings'),
    logsDir: path.resolve(__dirname, '../writable/logs'),
  },
};
