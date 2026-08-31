/**
 * Memori model per proses worker: model terakhir yang sukses dan model
 * yang kuota hariannya habis. Keduanya in-memory; restart daemon berarti
 * belajar ulang sekali di chunk pertama.
 */

const deadToday = new Map();
let stickyModel = null;

function todayKey() {
  const now = new Date();
  const pad = (v) => String(v).padStart(2, '0');
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

export function setStickyModel(model) {
  stickyModel = model;
}

export function markDeadToday(model) {
  deadToday.set(model, todayKey());
}

export function isDeadToday(model) {
  return deadToday.get(model) === todayKey();
}

/**
 * Urutan rantai efektif: model sticky dipindah ke depan, model yang
 * kuota hariannya habis disaring keluar.
 */
export function effectiveModelChain(chain) {
  const alive = (chain || []).filter((model) => !isDeadToday(model));
  if (stickyModel && alive.includes(stickyModel)) {
    return [stickyModel, ...alive.filter((model) => model !== stickyModel)];
  }
  return alive;
}

export function resetModelState() {
  deadToday.clear();
  stickyModel = null;
}
