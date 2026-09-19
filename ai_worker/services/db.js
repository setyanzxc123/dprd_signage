import mysql from 'mysql2/promise';
import { config } from '../config.js';

let dbPool = null;

export async function getDbPool() {
  if (!dbPool) {
    dbPool = mysql.createPool(config.db);
  }
  return dbPool;
}

export async function closeDbPool() {
  if (dbPool) {
    await dbPool.end();
    dbPool = null;
  }
}
