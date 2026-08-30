import fs from 'node:fs';
import path from 'node:path';
import { config } from '../config.js';

/**
 * Transport log worker: stdout tetap tampil, sambil di-append ke
 * writable/logs/ai_worker-YYYY-MM-DD.log agar output mode popen bisa diaudit.
 */

function currentLogFile() {
  const now = new Date();
  const pad = (v) => String(v).padStart(2, '0');
  const date = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
  return path.join(config.paths.logsDir, `ai_worker-${date}.log`);
}

function timestamp() {
  const now = new Date();
  const pad = (v) => String(v).padStart(2, '0');
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ` +
    `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
}

function formatMessage(args) {
  return args.map((a) => (a instanceof Error ? (a.stack || a.message) : String(a))).join(' ');
}

function appendToDailyFile(line) {
  try {
    fs.mkdirSync(config.paths.logsDir, { recursive: true });
    fs.appendFileSync(currentLogFile(), line + '\n', 'utf-8');
  } catch {
    // Log file tidak boleh menggagalkan worker
  }
}

function write(level, args) {
  const message = formatMessage(args);
  if (level === 'ERROR') {
    console.error(message);
  } else if (level === 'WARN') {
    console.warn(message);
  } else {
    console.log(message);
  }
  appendToDailyFile(`[${timestamp()}] [${level}] ${message}`);
}

export function log(...args) {
  write('INFO', args);
}

export function warn(...args) {
  write('WARN', args);
}

export function error(...args) {
  write('ERROR', args);
}
