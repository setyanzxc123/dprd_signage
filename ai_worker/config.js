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

// Database config (kompatibel dengan format CI4 .env maupun variabel standar)
const dbConfig = {
  host: getEnv('database.default.hostname', getEnv('DB_HOST', '127.0.0.1')),
  user: getEnv('database.default.username', getEnv('DB_USER', 'root')),
  password: getEnv('database.default.password', getEnv('DB_PASS', '')),
  database: getEnv('database.default.database', getEnv('DB_NAME', 'dprd_signage')),
  port: parseInt(getEnv('database.default.port', getEnv('DB_PORT', '3306')), 10),
  waitForConnections: true,
  connectionLimit: 5,
  queueLimit: 0,
};

// Gemini API Key & Model Chain
const geminiApiKey = getEnv('GEMINI_API_KEY', getEnv('GOOGLE_AI_API_KEY', getEnv('AI_GEMINI_KEY', '')));

// Rantai model default: primary hemat token -> fallback berurutan
// Nama model valid: gemini-2.5-flash-lite, gemini-2.5-flash, gemini-2.0-flash, gemini-1.5-flash
const defaultModelChainStr = 'gemini-2.5-flash-lite-preview-06-17,gemini-2.5-flash,gemini-2.0-flash,gemini-1.5-flash';
const modelChainRaw = getEnv('GEMINI_MODEL_CHAIN', defaultModelChainStr);
const modelChain = modelChainRaw
  .split(',')
  .map((m) => m.trim())
  .filter((m) => m.length > 0);

export const config = {
  db: dbConfig,
  gemini: {
    apiKey: geminiApiKey,
    modelChain: modelChain.length > 0 ? modelChain : ['gemini-2.5-flash-lite-preview-06-17', 'gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash'],
  },
  audio: {
    chunkDurationSeconds: parseInt(getEnv('CHUNK_DURATION_SECONDS', '1800'), 10), // 30 menit
    safetyDelayMs: parseInt(getEnv('SAFETY_DELAY_MS', '8000'), 10), // 8 detik
  },
  worker: {
    pollIntervalMs: parseInt(getEnv('WORKER_POLL_INTERVAL_MS', '5000'), 10), // 5 detik
    maxRetriesPerModel: parseInt(getEnv('MAX_RETRIES_PER_MODEL', '4'), 10),
  },
  paths: {
    root: path.resolve(__dirname, '..'),
    recordingsBaseDir: path.resolve(__dirname, '../writable/uploads/recordings'),
  },
};
