import fs from 'node:fs';
import path from 'node:path';
import { config } from '../config.js';

export const LOCK_PATH = path.resolve(config.paths.logsDir, '..', 'worker.lock');

export function isPidAlive(pid) {
  try {
    process.kill(pid, 0);
    return true;
  } catch (err) {
    return err.code === 'EPERM';
  }
}

/**
 * Kunci instance tunggal daemon: mencegah dua daemon berjalan bersamaan
 * (double-claim antrean). Lock basi (PID sudah mati) otomatis diambil alih.
 */
export function acquireDaemonLock() {
  let existing = null;
  try {
    existing = JSON.parse(fs.readFileSync(LOCK_PATH, 'utf8'));
  } catch {
    // lock tidak ada / rusak: aman untuk diambil
  }
  if (existing && existing.pid !== process.pid && isPidAlive(existing.pid)) {
    return { acquired: false, pid: existing.pid };
  }
  fs.writeFileSync(LOCK_PATH, JSON.stringify({ pid: process.pid, startedAt: new Date().toISOString() }));
  return { acquired: true, pid: process.pid };
}

export function releaseDaemonLock() {
  try {
    const existing = JSON.parse(fs.readFileSync(LOCK_PATH, 'utf8'));
    if (existing && existing.pid === process.pid) {
      fs.unlinkSync(LOCK_PATH);
    }
  } catch {
    // lock sudah tidak ada: tidak ada yang perlu dibersihkan
  }
}
