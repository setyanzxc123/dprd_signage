<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;700&display=swap" rel="stylesheet">

<style>
/* ══════════════════════════════════════════════════════════════════
   Open Design v2 — Jadwal Rapat Semesteran
   Prefix "bx-" untuk semua class agar tidak bentrok Bootstrap
   Color system: sky-blue accent, cool bg, analytic surfaces
   ══════════════════════════════════════════════════════════════════ */
#jadwal-od {
  --od-bg: #f4f7fb;
  --od-surface: #ffffff;
  --od-surface-warm: #eef6ff;
  --od-fg: #111827;
  --od-fg2: #334155;
  --od-muted: #64748b;
  --od-meta: #0ea5e9;
  --od-border: #d8e2ee;
  --od-border-soft: #edf3f8;
  --od-accent: #0ea5e9;
  --od-accent-on: #04131d;
  --od-accent-hover: color-mix(in oklab, #0ea5e9, black 8%);
  --od-success: #10b981;
  --od-warn: #f59e0b;
  --od-danger: #ef4444;
  --od-font: "Inter", system-ui, sans-serif;
  --od-mono: "IBM Plex Mono", ui-monospace, Menlo, monospace;
  --od-text-xs: 11px;
  --od-text-sm: 13px;
  --od-text-base: 15px;
  --od-text-lg: 17px;
  --od-text-xl: 22px;
  --od-text-2xl: 30px;
  --od-leading-tight: 1.1;
  --od-tracking: -0.015em;
  --od-radius-sm: 8px;
  --od-radius-md: 12px;
  --od-radius-lg: 18px;
  --od-radius-pill: 9999px;
  --od-shadow: 0 8px 32px rgba(15, 23, 42, 0.09);
  --od-focus: 0 0 0 4px rgba(14, 165, 233, 0.22);
  --od-ease: cubic-bezier(0.2, 0, 0, 1);

  font-family: var(--od-font);
  color: var(--od-fg);
  display: grid;
  gap: 20px;
  background: var(--od-bg);
  padding: 4px 0;
}

/* ── Base resets scoped ────────────────────────────────────────── */
#jadwal-od h1, #jadwal-od h2, #jadwal-od h3 {
  font-family: var(--od-font);
  line-height: var(--od-leading-tight);
  margin: 0;
}
#jadwal-od p { margin: 0; }
#jadwal-od svg { width: 16px; height: 16px; flex: 0 0 auto; }

/* ── Card / Panel base ─────────────────────────────────────────── */
.bx-panel {
  background: var(--od-surface);
  border: 1px solid var(--od-border);
  border-radius: var(--od-radius-lg);
  box-shadow: var(--od-shadow);
}
.bx-panel-body { padding: 20px 24px; }

/* ── Hero (2 kolom) ────────────────────────────────────────────── */
.bx-hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(300px, 400px);
  gap: 20px;
  align-items: stretch;
}
.bx-hero-main { padding: 24px; }
.bx-kicker {
  color: var(--od-meta);
  font-family: var(--od-mono);
  font-size: var(--od-text-xs);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 12px;
}
.bx-hero-title {
  font-size: clamp(22px, 3vw, 28px);
  font-weight: 700;
  letter-spacing: var(--od-tracking);
  max-width: 18ch;
  margin-bottom: 10px;
}
.bx-lead { color: var(--od-muted); font-size: var(--od-text-sm); line-height: 1.6; max-width: 62ch; }
.bx-hero-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 20px; }

/* Semester Health Card */
.bx-semester-card { padding: 20px; display: grid; gap: 14px; }
.bx-semester-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
.bx-semester-title { font-size: var(--od-text-xl); font-weight: 700; }
.bx-progress-track {
  height: 9px;
  overflow: hidden;
  border-radius: var(--od-radius-pill);
  background: var(--od-border-soft);
}
.bx-progress-fill {
  height: 100%;
  border-radius: inherit;
  background: var(--od-accent);
  transition: width 0.4s var(--od-ease);
}
.bx-compact-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}
.bx-mini-stat {
  padding: 10px 12px;
  border: 1px solid var(--od-border-soft);
  border-radius: var(--od-radius-md);
  background: color-mix(in oklab, var(--od-surface), var(--od-surface-warm) 42%);
}
.bx-mini-stat strong {
  display: block;
  font-family: var(--od-mono);
  font-size: var(--od-text-xl);
  font-weight: 800;
  line-height: var(--od-leading-tight);
  font-variant-numeric: tabular-nums;
}
.bx-mini-stat span { color: var(--od-muted); font-size: var(--od-text-xs); }

/* ── Buttons ─────────────────────────────────────────────────────── */
.bx-btn {
  min-height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 6px 14px;
  border: 1px solid var(--od-border);
  border-radius: var(--od-radius-md);
  background: var(--od-surface);
  color: var(--od-fg);
  font-size: var(--od-text-sm);
  font-weight: 600;
  font-family: var(--od-font);
  cursor: pointer;
  text-decoration: none;
  transition: background 120ms var(--od-ease), border-color 120ms var(--od-ease), transform 120ms var(--od-ease);
  white-space: nowrap;
}
.bx-btn:hover { border-color: color-mix(in oklab, var(--od-accent), var(--od-border) 40%); color: var(--od-fg); }
.bx-btn:active { transform: translateY(1px); }
.bx-btn:focus-visible { outline: 0; box-shadow: var(--od-focus); }
.bx-btn:disabled { cursor: not-allowed; opacity: .52; }
.bx-btn-primary { background: var(--od-accent); border-color: var(--od-accent); color: var(--od-accent-on); }
.bx-btn-primary:hover { background: var(--od-accent-hover); border-color: var(--od-accent-hover); color: var(--od-accent-on); }
.bx-btn-subtle { background: color-mix(in oklab, var(--od-surface-warm), transparent 28%); }
.bx-btn-icon { width: 38px; padding-inline: 6px; }

/* ── Controls bar ────────────────────────────────────────────────── */
.bx-controls {
  display: grid;
  grid-template-columns: 1fr 1fr 1.5fr auto;
  gap: 12px;
  align-items: end;
}
.bx-field { display: grid; gap: 6px; }
.bx-field label { color: var(--od-muted); font-family: var(--od-mono); font-size: var(--od-text-xs); }
.bx-select, .bx-input {
  width: 100%;
  min-height: 38px;
  border: 1px solid var(--od-border);
  border-radius: var(--od-radius-md);
  padding: 6px 10px;
  background: var(--od-surface);
  color: var(--od-fg);
  font-size: var(--od-text-sm);
  font-family: var(--od-font);
  transition: border-color 120ms;
}
.bx-select:focus, .bx-input:focus { outline: 0; border-color: var(--od-accent); box-shadow: var(--od-focus); }

/* ── Summary tiles ───────────────────────────────────────────────── */
.bx-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}
.bx-tile {
  min-height: 118px;
  padding: 20px;
  display: grid;
  gap: 6px;
}
.bx-tile p { color: var(--od-muted); font-size: var(--od-text-xs); line-height: 1.4; }
.bx-tile-label { color: var(--od-fg2); font-size: var(--od-text-sm); font-weight: 700; }
.bx-tile-value {
  font-family: var(--od-mono);
  font-size: var(--od-text-2xl);
  font-weight: 800;
  line-height: var(--od-leading-tight);
  font-variant-numeric: tabular-nums;
}

/* ── Main grid: Kalender + Detail ────────────────────────────────── */
.bx-main-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 368px;
  gap: 20px;
  align-items: start;
}
.bx-calendar-panel { overflow: hidden; }
.bx-panel-head {
  padding: 16px 20px;
  border-bottom: 1px solid var(--od-border);
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}
.bx-panel-head h2 { font-size: var(--od-text-xl); font-weight: 700; }
.bx-panel-head p   { color: var(--od-muted); font-size: var(--od-text-sm); margin-top: 3px; }
.bx-toolbar { display: flex; align-items: center; gap: 6px; }

/* Month strip */
.bx-month-strip {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 8px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--od-border);
  background: color-mix(in oklab, var(--od-surface-warm), transparent 26%);
}
.bx-month-node {
  min-height: 72px;
  border: 1px solid var(--od-border);
  border-radius: var(--od-radius-md);
  background: var(--od-surface);
  padding: 10px;
  display: grid;
  align-content: space-between;
  gap: 4px;
  text-align: left;
  cursor: pointer;
  font-family: var(--od-font);
  transition: border-color 120ms var(--od-ease), background 120ms var(--od-ease);
}
.bx-month-node:hover { border-color: var(--od-accent); }
.bx-month-node.active {
  border-color: var(--od-accent);
  background: color-mix(in oklab, var(--od-accent), var(--od-surface) 90%);
}
.bx-month-node > span { color: var(--od-muted); font-size: var(--od-text-xs); }
.bx-month-node > strong {
  display: block;
  font-family: var(--od-mono);
  font-size: var(--od-text-xl);
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  line-height: 1;
}
.bx-month-node > .bx-mn-todo { color: var(--od-warn); font-size: var(--od-text-xs); font-family: var(--od-mono); }

/* Calendar grid */
.bx-cal-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(96px, 1fr));
  overflow-x: auto;
}
.bx-weekday, .bx-day-cell {
  min-width: 96px;
  border-right: 1px solid var(--od-border-soft);
  border-bottom: 1px solid var(--od-border-soft);
}
.bx-weekday {
  padding: 10px;
  color: var(--od-muted);
  font-family: var(--od-mono);
  font-size: var(--od-text-xs);
  background: var(--od-surface);
}
.bx-day-cell {
  min-height: 110px;
  padding: 8px;
  background: color-mix(in oklab, var(--od-surface), transparent 3%);
}
.bx-day-cell.outside { background: color-mix(in oklab, var(--od-bg), var(--od-surface) 44%); color: var(--od-muted); }
.bx-date-row {
  min-height: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
  color: var(--od-fg2);
  font-family: var(--od-mono);
  font-size: var(--od-text-xs);
}
.bx-today-mark { width: 7px; height: 7px; border-radius: var(--od-radius-pill); background: var(--od-accent); }
.bx-event-card {
  width: 100%;
  margin-bottom: 6px;
  padding: 6px 7px;
  border: 1px solid var(--od-border);
  border-left-width: 3px;
  border-left-color: var(--od-muted);
  border-radius: var(--od-radius-sm);
  background: var(--od-surface);
  text-align: left;
  display: grid;
  gap: 3px;
  cursor: pointer;
  font-family: var(--od-font);
  transition: border-color 120ms, background 120ms;
}
.bx-event-card:hover,
.bx-event-card.selected {
  border-color: color-mix(in oklab, var(--od-accent), var(--od-border) 35%);
  background: color-mix(in oklab, var(--od-surface-warm), transparent 24%);
}
.bx-event-time { color: var(--od-muted); font-family: var(--od-mono); font-size: 10px; }
.bx-event-title { color: var(--od-fg); font-size: var(--od-text-xs); font-weight: 700; line-height: 1.25; }

/* +N overflow chip */
.bx-more-chip {
  display: block;
  width: 100%;
  margin-bottom: 4px;
  padding: 3px 7px;
  border: 1px dashed var(--od-border);
  border-radius: var(--od-radius-sm);
  background: color-mix(in oklab, var(--od-surface-warm), transparent 20%);
  color: var(--od-accent);
  font-size: 10px;
  font-weight: 700;
  font-family: var(--od-mono);
  cursor: pointer;
  text-align: center;
  transition: background 120ms, border-color 120ms;
}
.bx-more-chip:hover {
  background: color-mix(in oklab, var(--od-accent), transparent 88%);
  border-color: var(--od-accent);
}

/* Day filter active indicator */
.bx-day-cell.day-selected .bx-date-row {
  color: var(--od-accent);
}
.bx-day-cell.day-selected .bx-date-row > span {
  background: var(--od-accent);
  color: white;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  display: grid;
  place-items: center;
  font-size: 10px;
}

/* View toggle pills */
.bx-view-toggle {
  display: inline-flex;
  border: 1px solid var(--od-border);
  border-radius: var(--od-radius-md);
  overflow: hidden;
  background: var(--od-bg);
}
.bx-view-toggle button {
  min-height: 32px;
  padding: 0 12px;
  border: none;
  background: none;
  color: var(--od-muted);
  font-size: var(--od-text-xs);
  font-weight: 600;
  font-family: var(--od-mono);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 5px;
  transition: background 120ms, color 120ms;
}
.bx-view-toggle button.active {
  background: var(--od-surface);
  color: var(--od-fg);
  box-shadow: var(--od-ring);
}
.bx-view-toggle button:hover:not(.active) {
  background: color-mix(in oklab, var(--od-accent), transparent 92%);
  color: var(--od-fg);
}

/* Day filter reset chip di table head */
.bx-day-filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border: 1px solid var(--od-accent);
  border-radius: var(--od-radius-pill);
  background: color-mix(in oklab, var(--od-accent), transparent 88%);
  color: var(--od-accent);
  font-size: var(--od-text-xs);
  font-weight: 700;
  cursor: pointer;
}
.bx-day-filter-chip:hover { background: color-mix(in oklab, var(--od-accent), transparent 80%); }

/* Agenda list view */
.bx-agenda-list { display: grid; gap: 2px; }
.bx-agenda-day-group { }
.bx-agenda-date-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px var(--od-space-5);
  background: color-mix(in oklab, var(--od-surface-warm), transparent 26%);
  border-bottom: 1px solid var(--od-border-soft);
  border-top: 1px solid var(--od-border-soft);
  position: sticky;
  top: 0;
  z-index: 2;
}
.bx-agenda-date-num {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--od-surface);
  border: 1px solid var(--od-border);
  font-family: var(--od-mono);
  font-size: var(--od-text-sm);
  font-weight: 700;
  flex-shrink: 0;
}
.bx-agenda-date-num.today {
  background: var(--od-accent);
  border-color: var(--od-accent);
  color: white;
}
.bx-agenda-date-label { font-size: var(--od-text-sm); font-weight: 600; }
.bx-agenda-date-sub { font-size: var(--od-text-xs); color: var(--od-muted); font-family: var(--od-mono); }
.bx-agenda-row {
  display: grid;
  grid-template-columns: 90px 1fr auto;
  align-items: center;
  gap: var(--od-space-4);
  padding: 10px var(--od-space-5) 10px calc(var(--od-space-5) + 46px);
  border-bottom: 1px solid var(--od-border-soft);
  background: var(--od-surface);
  cursor: pointer;
  transition: background 120ms;
}
.bx-agenda-row:hover { background: color-mix(in oklab, var(--od-surface-warm), transparent 24%); }
.bx-agenda-row.selected { background: color-mix(in oklab, var(--od-surface-warm), transparent 10%); }
.bx-agenda-time {
  font-family: var(--od-mono);
  font-size: var(--od-text-xs);
  color: var(--od-muted);
  line-height: 1.4;
}
.bx-agenda-time strong { display: block; color: var(--od-fg); font-size: var(--od-text-sm); }
.bx-agenda-info strong { display: block; font-size: var(--od-text-sm); }
.bx-agenda-info span { font-size: var(--od-text-xs); color: var(--od-muted); }
.bx-agenda-badges { display: flex; gap: 5px; flex-wrap: wrap; align-items: center; justify-content: flex-end; }
.bx-agenda-empty {
  padding: 40px;
  text-align: center;
  color: var(--od-muted);
  font-size: var(--od-text-sm);
}
.bx-badge-row { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.bx-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-height: 20px;
  padding: 2px 7px;
  border: 1px solid var(--od-border);
  border-radius: var(--od-radius-pill);
  background: var(--od-surface);
  color: var(--od-fg2);
  font-size: var(--od-text-xs);
  font-weight: 700;
  white-space: nowrap;
  font-family: var(--od-mono);
}
.bx-badge::before { content: ""; width: 6px; height: 6px; border-radius: var(--od-radius-pill); background: var(--od-muted); flex: 0 0 auto; }
.bx-badge[data-tone="success"]::before { background: var(--od-success); }
.bx-badge[data-tone="warn"]::before    { background: var(--od-warn); }
.bx-badge[data-tone="danger"]::before  { background: var(--od-danger); }
.bx-badge[data-tone="accent"]::before  { background: var(--od-accent); }

/* ── Detail panel ─────────────────────────────────────────────────── */
.bx-detail-panel {
  position: sticky;
  top: 80px;
  overflow: hidden;
}
.bx-detail-body { padding: 18px 20px; display: grid; gap: 16px; }
.bx-detail-title { font-size: var(--od-text-xl); font-weight: 800; letter-spacing: var(--od-tracking); }
.bx-info-list {
  display: grid;
  border: 1px solid var(--od-border);
  border-radius: var(--od-radius-md);
  overflow: hidden;
}
.bx-info-row {
  display: grid;
  grid-template-columns: 100px 1fr;
  gap: 10px;
  padding: 10px 12px;
  border-bottom: 1px solid var(--od-border-soft);
  background: var(--od-surface);
  font-size: var(--od-text-sm);
}
.bx-info-row:last-child { border-bottom: 0; }
.bx-info-row span:first-child { color: var(--od-muted); font-family: var(--od-mono); font-size: var(--od-text-xs); align-self: center; }
.bx-info-row strong { color: var(--od-fg); }

/* Status stack (toggle + actions) */
.bx-status-stack {
  display: grid;
  gap: 12px;
  padding: 14px 16px;
  border: 1px solid var(--od-border);
  border-radius: var(--od-radius-md);
  background: color-mix(in oklab, var(--od-surface-warm), transparent 22%);
}
.bx-toggle-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.bx-toggle-row strong { font-size: var(--od-text-sm); }
.bx-toggle-row p { color: var(--od-muted); font-size: var(--od-text-xs); margin-top: 2px; }
.bx-switch {
  position: relative;
  flex: 0 0 auto;
  width: 42px;
  height: 24px;
  border: 1px solid var(--od-border);
  border-radius: var(--od-radius-pill);
  background: var(--od-border-soft);
  cursor: pointer;
  transition: background 200ms var(--od-ease), border-color 200ms var(--od-ease);
}
.bx-switch::after {
  content: "";
  position: absolute;
  top: 3px; left: 3px;
  width: 16px; height: 16px;
  border-radius: var(--od-radius-pill);
  background: var(--od-surface);
  box-shadow: 0 0 0 1px var(--od-border);
  transition: transform 200ms var(--od-ease);
}
.bx-switch.on { background: var(--od-accent); border-color: var(--od-accent); }
.bx-switch.on::after { transform: translateX(18px); }
.bx-row-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

/* Action dock */
.bx-action-dock { display: grid; gap: 8px; }
.bx-action-item {
  display: grid;
  grid-template-columns: 32px 1fr auto;
  gap: 10px;
  align-items: center;
  padding: 10px 12px;
  border: 1px solid var(--od-border);
  border-radius: var(--od-radius-md);
  background: var(--od-surface);
}
.bx-action-icon {
  width: 32px; height: 32px;
  display: grid; place-items: center;
  border-radius: var(--od-radius-sm);
  background: color-mix(in oklab, var(--od-accent), transparent 88%);
  color: var(--od-fg);
  font-family: var(--od-mono);
  font-size: var(--od-text-xs);
  font-weight: 700;
}
.bx-action-item strong { font-size: var(--od-text-sm); color: var(--od-fg); }
.bx-action-item p { color: var(--od-muted); font-size: var(--od-text-xs); margin-top: 2px; }

/* ── Timeline table ───────────────────────────────────────────────── */
.bx-timeline { padding: 20px; }
.bx-timeline-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.bx-timeline-head h2 { font-size: var(--od-text-xl); font-weight: 700; }
.bx-timeline-head p  { color: var(--od-muted); font-size: var(--od-text-sm); }
.bx-table-wrap {
  overflow-x: auto;
  border: 1px solid var(--od-border);
  border-radius: var(--od-radius-lg);
  background: var(--od-surface);
}
.bx-table {
  width: 100%;
  min-width: 760px;
  border-collapse: collapse;
  font-size: var(--od-text-sm);
  font-family: var(--od-font);
}
.bx-table th, .bx-table td {
  padding: 10px 14px;
  border-bottom: 1px solid var(--od-border-soft);
  text-align: left;
  vertical-align: middle;
}
.bx-table th {
  color: var(--od-muted);
  font-family: var(--od-mono);
  font-size: var(--od-text-xs);
  font-weight: 700;
  text-transform: uppercase;
  background: color-mix(in oklab, var(--od-surface-warm), transparent 32%);
}
.bx-table tbody tr:hover { background: color-mix(in oklab, var(--od-surface-warm), transparent 44%); }
.bx-table td strong { display: block; color: var(--od-fg); font-weight: 700; }
.bx-table td .bx-desc { color: var(--od-muted); font-size: var(--od-text-xs); display: block; margin-top: 2px; }
.bx-mono { font-family: var(--od-mono); font-variant-numeric: tabular-nums; font-size: var(--od-text-xs); }

/* ── Empty state ────────────────────────────────────────────────── */
.bx-empty {
  padding: 20px;
  color: var(--od-muted);
  text-align: center;
  border: 1px dashed var(--od-border);
  border-radius: var(--od-radius-md);
  background: color-mix(in oklab, var(--od-surface-warm), transparent 38%);
  font-size: var(--od-text-sm);
}

/* ── Responsive ──────────────────────────────────────────────────── */
@media (max-width: 1180px) {
  .bx-main-grid { grid-template-columns: 1fr; }
  .bx-detail-panel { position: static; }
}
@media (max-width: 960px) {
  .bx-hero { grid-template-columns: 1fr; }
  .bx-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .bx-controls { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
  .bx-summary-grid { grid-template-columns: 1fr; }
  .bx-controls { grid-template-columns: 1fr; }
  .bx-month-strip { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .bx-compact-grid { grid-template-columns: 1fr 1fr 1fr; }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div id="jadwal-od">

  <!-- ─────────────────────────────────────────────────────────────
       HERO — 2 kolom: deskripsi + semester health card
  ──────────────────────────────────────────────────────────────── -->
  <section class="bx-hero">

    <div class="bx-panel">
      <div class="bx-hero-main">
        <p class="bx-kicker">Kalender Semester · Semua Jenis Rapat · Tahun <?= $tahun ?></p>
        <h1 class="bx-hero-title">Peta kerja semester — semua rapat dalam satu tampilan.</h1>
        <p class="bx-lead">Lihat seluruh jadwal rapat semester ini dalam satu kalender — rapat Bamus (biru) dan rapat insidental (oranye) ditampilkan bersama dengan pembeda warna. Klik agenda untuk detail dan aksi cepat.</p>
        <div class="bx-hero-actions">
          <!-- Filter tahun (reload) -->
          <form method="get" style="display:inline-flex; gap:6px; align-items:center;">
            <input type="hidden" name="semester" id="hidden-semester" value="<?= $semester ?>">
            <select name="tahun" class="bx-select" style="width:auto; min-height:38px;" onchange="this.form.submit()">
              <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </form>
          <a href="<?= base_url('admin/jadwal/create') ?>" class="bx-btn bx-btn-primary" id="newScheduleBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
            Tambah jadwal
          </a>
        </div>
      </div>
    </div>

    <!-- Semester Health Card -->
    <aside class="bx-panel bx-semester-card">
      <div class="bx-semester-head">
        <div>
          <p class="bx-kicker">Semester aktif</p>
          <div class="bx-semester-title" id="semesterLabel">—</div>
        </div>
        <span class="bx-badge" data-tone="accent" id="progressLabel">—</span>
      </div>
      <div class="bx-progress-track" aria-label="Progres agenda selesai">
        <div class="bx-progress-fill" id="progressFill" style="width:0%"></div>
      </div>
      <div class="bx-compact-grid">
        <div class="bx-mini-stat"><strong id="miniTotal">0</strong><span>Total</span></div>
        <div class="bx-mini-stat"><strong id="miniPublic">0</strong><span>Publik</span></div>
        <div class="bx-mini-stat"><strong id="miniStream">0</strong><span>Nonton</span></div>
      </div>
      <p style="color: var(--od-muted); font-size: var(--od-text-xs); line-height: 1.5;">Kontrol rapat memisahkan "boleh tampil publik" dari "siap ditonton" — keduanya bukan status yang sama.</p>
    </aside>

  </section>

  <!-- ─────────────────────────────────────────────────────────────
       CONTROLS — Semester / Status / Search / Reset
  ──────────────────────────────────────────────────────────────── -->
  <section class="bx-panel bx-panel-body">
    <div class="bx-controls">
      <div class="bx-field">
        <label for="semesterFilter">Semester</label>
        <select class="bx-select" id="semesterFilter">
          <option value="1">Semester I &mdash; Jan sampai Jun</option>
          <option value="2">Semester II &mdash; Jul sampai Des</option>
        </select>
      </div>
      <div class="bx-field">
        <label for="jenisFilter">Jenis rapat</label>
        <select class="bx-select" id="jenisFilter">
          <option value="all">Semua jenis</option>
          <option value="bamus">Bamus</option>
          <option value="insidental">Insidental</option>
        </select>
      </div>
      <div class="bx-field">
        <label for="statusFilter">Status rapat</label>
        <select class="bx-select" id="statusFilter">
          <option value="all">Semua status</option>
          <option value="selesai">Selesai</option>
          <option value="persiapan">Persiapan</option>
          <option value="berlangsung">Berlangsung</option>
          <option value="menunggu">Menunggu</option>
        </select>
      </div>
      <div class="bx-field">
        <label for="searchInput">Cari agenda, ruangan, komisi</label>
        <input class="bx-input" id="searchInput" type="search" placeholder="Contoh: Paripurna, Komisi II">
      </div>
      <button class="bx-btn" type="button" id="resetBtn" style="align-self:end;">Reset</button>
    </div>
    <!-- Legenda warna jenis -->
    <div style="display:flex; gap:14px; margin-top:10px; align-items:center; flex-wrap:wrap;">
      <span style="font-size:11px; color:var(--od-muted); font-family:var(--od-mono);">LEGENDA:</span>
      <span style="display:inline-flex; align-items:center; gap:5px; font-size:12px;">
        <span style="width:10px; height:10px; border-radius:50%; background:#0ea5e9; flex:0 0 auto;"></span> Bamus
      </span>
      <span style="display:inline-flex; align-items:center; gap:5px; font-size:12px;">
        <span style="width:10px; height:10px; border-radius:50%; background:#f97316; flex:0 0 auto;"></span> Insidental
      </span>
    </div>
  </section>

  <!-- ─────────────────────────────────────────────────────────────
       SUMMARY TILES — 4 kartu statistik
  ──────────────────────────────────────────────────────────────── -->
  <section class="bx-summary-grid">
    <article class="bx-panel bx-tile">
      <span class="bx-tile-label">Total agenda</span>
      <strong class="bx-tile-value" id="totalCount">0</strong>
      <p>Semua rapat dalam semester terpilih.</p>
    </article>
    <article class="bx-panel bx-tile">
      <span class="bx-tile-label" style="display:flex;align-items:center;gap:6px;">
        <span style="width:8px;height:8px;border-radius:50%;background:#0ea5e9;flex:0 0 auto;"></span> Rapat Bamus
      </span>
      <strong class="bx-tile-value" id="bamusCount">0</strong>
      <p>Jadwal ditetapkan Badan Musyawarah.</p>
    </article>
    <article class="bx-panel bx-tile">
      <span class="bx-tile-label" style="display:flex;align-items:center;gap:6px;">
        <span style="width:8px;height:8px;border-radius:50%;background:#f97316;flex:0 0 auto;"></span> Rapat Insidental
      </span>
      <strong class="bx-tile-value" id="insidentalCount">0</strong>
      <p>Rapat di luar jadwal Bamus.</p>
    </article>
    <article class="bx-panel bx-tile">
      <span class="bx-tile-label">Tampil publik</span>
      <strong class="bx-tile-value" id="publicCount">0</strong>
      <p>Aman muncul di tautan publik/signage.</p>
    </article>
  </section>

  <!-- ─────────────────────────────────────────────────────────────
       MAIN GRID — Kalender + Detail Panel
  ──────────────────────────────────────────────────────────────── -->
  <section class="bx-main-grid">

    <!-- Kalender -->
    <div class="bx-panel bx-calendar-panel">
      <div class="bx-panel-head">
        <div>
          <h2 id="calendarTitle">Memuat…</h2>
          <p id="calendarSubtitle">Kalender rapat bulan aktif.</p>
        </div>
        <div class="bx-toolbar">
          <!-- View toggle: Kalender | Agenda -->
          <div class="bx-view-toggle" id="viewToggle">
            <button type="button" id="btnViewCal" class="active">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              Kalender
            </button>
            <button type="button" id="btnViewAgenda">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
              Agenda
            </button>
          </div>
          <button class="bx-btn bx-btn-icon" id="prevMonth" type="button" aria-label="Bulan sebelumnya">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/></svg>
          </button>
          <button class="bx-btn bx-btn-icon" id="nextMonth" type="button" aria-label="Bulan berikutnya">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m9 18 6-6-6-6"/></svg>
          </button>
        </div>
      </div>
      <div class="bx-month-strip" id="monthStrip"></div>
      <!-- Calendar view -->
      <div class="bx-cal-grid" id="calendarGrid" aria-live="polite"></div>
      <!-- Agenda list view (hidden by default) -->
      <div id="agendaListView" style="display:none;"></div>
    </div>

    <!-- Detail panel -->
    <aside class="bx-panel bx-detail-panel" aria-live="polite">
      <div class="bx-panel-head">
        <div>
          <h2>Detail agenda</h2>
          <p>Pilih agenda di kalender.</p>
        </div>
      </div>
      <div class="bx-detail-body">
        <div class="bx-badge-row" id="detailBadges"></div>
        <div>
          <h3 class="bx-detail-title" id="detailTitle">Belum ada agenda dipilih</h3>
          <p style="color:var(--od-muted); font-size:var(--od-text-sm); margin-top:6px;" id="detailDesc">Klik agenda untuk melihat ruangan, peserta, status publik, dan tombol nonton.</p>
        </div>
        <div class="bx-info-list" id="detailInfo"></div>
        <div class="bx-status-stack">
          <div class="bx-toggle-row">
            <div>
              <strong>Status publik</strong>
              <p id="publicText">—</p>
            </div>
            <button class="bx-switch" type="button" id="publicToggle" aria-label="Ubah status publik"></button>
          </div>
          <div class="bx-row-actions">
            <button class="bx-btn bx-btn-primary" type="button" id="watchBtn" disabled>Nonton</button>
            <button class="bx-btn" type="button" id="editBtn">Edit</button>
            <button class="bx-btn" type="button" id="deleteBtn" style="color:var(--od-danger); border-color:var(--od-danger);">Hapus</button>
          </div>
        </div>
        <div class="bx-action-dock" id="actionDock"></div>
      </div>
    </aside>

  </section>

  <!-- ─────────────────────────────────────────────────────────────
       TIMELINE TABLE — tabel agenda bulan aktif
  ──────────────────────────────────────────────────────────────── -->
  <section class="bx-panel">
    <div class="bx-timeline">
      <div class="bx-timeline-head">
        <div>
          <h2>Agenda bulan aktif</h2>
          <p id="tableSubtitle">Daftar operasional dari kalender.</p>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
          <button class="bx-day-filter-chip" id="dayFilterChip" style="display:none;" title="Klik untuk hapus filter hari">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><path d="M18 6 6 18M6 6l12 12"/></svg>
            <span id="dayFilterLabel"></span>
          </button>
          <span class="bx-badge" data-tone="accent" id="tableCount">0 agenda</span>
        </div>
      </div>
      <div class="bx-table-wrap">
        <table class="bx-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Agenda</th>
              <th>Ruangan</th>
              <th>Peserta</th>
              <th>Status</th>
              <th>Aksi cepat</th>
            </tr>
          </thead>
          <tbody id="agendaTable"></tbody>
        </table>
      </div>
    </div>
  </section>

</div><!-- /#jadwal-od -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
/* ── Data dari PHP ──────────────────────────────────────────────── */
const meetings = <?= $jadwalJson ?>;
const YEAR     = <?= $tahun ?>;
const INIT_SEM = <?= $semester ?>;
const TODAY    = new Date().toISOString().slice(0, 10);

/* ── Label konstan ──────────────────────────────────────────────── */
const monthNames  = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
const shortMonths = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agt","Sep","Okt","Nov","Des"];
const weekdays    = ["Min","Sen","Sel","Rab","Kam","Jum","Sab"];
const statusLabel = { selesai:"Selesai", persiapan:"Persiapan", berlangsung:"Berlangsung", menunggu:"Menunggu" };
const statusTone  = { selesai:"success", persiapan:"warn", berlangsung:"danger", menunggu:"neutral" };
// Warna border kiri per jenis rapat
const jenisColor  = { bamus:"#0ea5e9", insidental:"#f97316" };
const jenisLabel  = { bamus:"Bamus", insidental:"Insidental" };

/* ── State ─────────────────────────────────────────────────────── */
let state = {
  semester:   String(INIT_SEM),
  month:      INIT_SEM === 1 ? 0 : 6,
  jenis:      "all",
  status:     "all",
  search:     "",
  selectedId: null,
  view:       "cal",  // "cal" | "agenda"
  dayFilter:  null    // ISO date string | null — filter tabel ke 1 hari
};
// Mutasi publik untuk toggle (client-side only preview)
const publicOverrides = {};

/* ── Element refs ──────────────────────────────────────────────── */
const $ = id => document.getElementById(id);
const els = {
  semester:      $("semesterFilter"),
  jenis:         $("jenisFilter"),
  status:        $("statusFilter"),
  search:        $("searchInput"),
  monthStrip:    $("monthStrip"),
  calendarGrid:  $("calendarGrid"),
  calendarTitle: $("calendarTitle"),
  calendarSub:   $("calendarSubtitle"),
  detailBadges:  $("detailBadges"),
  detailTitle:   $("detailTitle"),
  detailDesc:    $("detailDesc"),
  detailInfo:    $("detailInfo"),
  publicText:    $("publicText"),
  publicToggle:  $("publicToggle"),
  watchBtn:      $("watchBtn"),
  editBtn:       $("editBtn"),
  deleteBtn:     $("deleteBtn"),
  actionDock:    $("actionDock"),
  agendaTable:   $("agendaTable"),
  tableSubtitle: $("tableSubtitle"),
  tableCount:    $("tableCount"),
  totalCount:    $("totalCount"),
  bamusCount:    $("bamusCount"),
  insidentalCount: $("insidentalCount"),
  publicCount:   $("publicCount"),
  miniTotal:     $("miniTotal"),
  miniPublic:    $("miniPublic"),
  miniStream:    $("miniStream"),
  semesterLabel:   $("semesterLabel"),
  progressFill:    $("progressFill"),
  progressLabel:   $("progressLabel"),
  hiddenSem:       $("hidden-semester"),
  agendaListView:  $("agendaListView"),
  btnViewCal:      $("btnViewCal"),
  btnViewAgenda:   $("btnViewAgenda"),
  dayFilterChip:   $("dayFilterChip"),
  dayFilterLabel:  $("dayFilterLabel"),
};

/* ── Helpers ───────────────────────────────────────────────────── */
function semesterMonths() { return state.semester === "1" ? [0,1,2,3,4,5] : [6,7,8,9,10,11]; }
function itemMonth(item)  { return new Date(item.date + "T00:00:00").getMonth(); }
function inSemester(item) { return semesterMonths().includes(itemMonth(item)); }
function isPublic(item)   { return publicOverrides.hasOwnProperty(item.id) ? publicOverrides[item.id] : item.public; }
function isTodo(item)     { return !isPublic(item) || !item.stream; }
function filteredMeetings() {
  const q = state.search.trim().toLowerCase();
  return meetings.filter(item => {
    const txt = `${item.title} ${item.description} ${item.room} ${item.group}`.toLowerCase();
    return inSemester(item)
      && (state.jenis  === "all" || item.jenis  === state.jenis)
      && (state.status === "all" || item.status === state.status)
      && (!q || txt.includes(q));
  });
}
function monthItems(month) {
  return filteredMeetings().filter(item => itemMonth(item) === month)
    .sort((a, b) => `${a.date} ${a.start}`.localeCompare(`${b.date} ${b.start}`));
}
function selectedItem() { return meetings.find(item => item.id === state.selectedId); }
function formatDate(iso) {
  const d = new Date(iso + "T00:00:00");
  return `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}`;
}
function esc(str) {
  const d = document.createElement("div");
  d.appendChild(document.createTextNode(str));
  return d.innerHTML;
}

/* ── Render: Summary ────────────────────────────────────────────── */
function renderSummary() {
  const items     = filteredMeetings();
  const bamus     = items.filter(i => i.jenis === "bamus");
  const insid     = items.filter(i => i.jenis === "insidental");
  const pubItems  = items.filter(isPublic);
  const doneItems = items.filter(i => i.status === "selesai");
  const pct       = items.length ? Math.round((doneItems.length / items.length) * 100) : 0;
  const sem       = state.semester === "1" ? "Jan\u2013Jun" : "Jul\u2013Des";

  els.totalCount.textContent       = items.length;
  els.bamusCount.textContent       = bamus.length;
  els.insidentalCount.textContent  = insid.length;
  els.publicCount.textContent      = pubItems.length;
  els.miniTotal.textContent        = items.length;
  els.miniPublic.textContent       = pubItems.length;
  els.miniStream.textContent       = items.filter(i => i.stream).length;
  els.semesterLabel.textContent    = `Semester ${state.semester === "1" ? "I" : "II"} \u00b7 ${sem} ${YEAR}`;
  els.progressFill.style.width     = pct + "%";
  els.progressLabel.textContent    = pct + "% selesai";
}

/* ── Render: Month strip ────────────────────────────────────────── */
function renderMonths() {
  els.monthStrip.innerHTML = semesterMonths().map(month => {
    const items  = monthItems(month);
    const todos  = items.filter(isTodo).length;
    const active = month === state.month ? " active" : "";
    return `<button class="bx-month-node${active}" type="button" data-month="${month}">
      <span>${shortMonths[month]} ${YEAR}</span>
      <strong>${items.length}</strong>
      <span class="bx-mn-todo">${todos ? todos + " tindak lanjut" : "✓ siap"}</span>
    </button>`;
  }).join("");
}

/* ── Render: Calendar grid ──────────────────────────────────────── */
const MAX_VISIBLE = 2; // max card per hari sebelum collapse

function renderCalendar() {
  const month = state.month;
  const first = new Date(YEAR, month, 1);
  const cursor = new Date(first);
  cursor.setDate(first.getDate() - first.getDay());
  const items = monthItems(month);

  els.calendarTitle.textContent = `${monthNames[month]} ${YEAR}`;
  els.calendarSub.textContent   = `${items.length} agenda rapat \u00b7 Klik kartu untuk detail, klik "+N lagi" untuk filter tabel.`;

  const header = weekdays.map(d => `<div class="bx-weekday">${d}</div>`).join("");
  const cells  = [];
  for (let i = 0; i < 42; i++) {
    const date     = new Date(cursor);
    date.setDate(cursor.getDate() + i);
    const iso      = date.toISOString().slice(0, 10);
    const outside  = date.getMonth() !== month ? " outside" : "";
    const dayItems = items.filter(item => item.date === iso);
    const todayDot = iso === TODAY ? `<span class="bx-today-mark"></span>` : "";
    const isDaySelected = state.dayFilter === iso ? " day-selected" : "";

    // Visible cards: max MAX_VISIBLE, rest collapse ke +N chip
    const visible = dayItems.slice(0, MAX_VISIBLE);
    const extra   = dayItems.length - MAX_VISIBLE;

    const cards = visible.map(item =>
      `<button class="bx-event-card${item.id === state.selectedId ? " selected" : ""}" type="button" data-id="${item.id}" style="border-left:3px solid ${jenisColor[item.jenis] || '#64748b'}">
        <span class="bx-event-time">${item.start}\u2013${item.end}</span>
        <span class="bx-event-title">${esc(item.title)}</span>
        <span class="bx-badge-row"><span class="bx-badge" data-tone="${statusTone[item.status]}">${statusLabel[item.status]}</span></span>
      </button>`
    ).join("");

    const moreChip = extra > 0
      ? `<button class="bx-more-chip" type="button" data-date="${iso}">+${extra} lagi \u2192 lihat tabel</button>`
      : "";

    cells.push(`<div class="bx-day-cell${outside}${isDaySelected}">
      <div class="bx-date-row"><span>${date.getDate()}</span>${todayDot}</div>
      ${cards}${moreChip}
    </div>`);
  }
  els.calendarGrid.innerHTML = header + cells.join("");
}

/* ── Render: Agenda list view ───────────────────────────────────── */
function renderAgendaList() {
  const items = filteredMeetings().filter(item => {
    const m = new Date(item.date + "T00:00:00").getMonth();
    return semesterMonths().includes(m);
  }).sort((a, b) => (a.date + a.start).localeCompare(b.date + b.start));

  els.calendarTitle.textContent = `Semester ${state.semester === "1" ? "I" : "II"} ${YEAR}`;
  els.calendarSub.textContent   = `${items.length} agenda rapat — tampilan kronologis.`;

  if (!items.length) {
    els.agendaListView.innerHTML = `<div class="bx-agenda-empty">Tidak ada agenda sesuai filter.</div>`;
    return;
  }

  // Kelompokkan per hari
  const byDate = {};
  items.forEach(item => {
    if (!byDate[item.date]) byDate[item.date] = [];
    byDate[item.date].push(item);
  });

  const html = Object.entries(byDate).map(([iso, dayItems]) => {
    const d = new Date(iso + "T00:00:00");
    const dayNum  = d.getDate();
    const dayName = ["Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"][d.getDay()];
    const monName = monthNames[d.getMonth()];
    const isToday = iso === TODAY;
    const rows = dayItems.map(item =>
      `<div class="bx-agenda-row${item.id === state.selectedId ? " selected" : ""}" data-id="${item.id}">
        <div class="bx-agenda-time">
          <strong>${item.start}</strong>\u2013${item.end}
        </div>
        <div class="bx-agenda-info">
          <strong>${esc(item.title)}</strong>
          <span>${esc(item.room)}${item.group ? " \u00b7 " + esc(item.group) : ""}</span>
        </div>
        <div class="bx-agenda-badges">
          <span class="bx-badge" style="border-left:3px solid ${jenisColor[item.jenis] || '#64748b'}">${jenisLabel[item.jenis] || item.jenis}</span>
          <span class="bx-badge" data-tone="${statusTone[item.status]}">${statusLabel[item.status]}</span>
        </div>
      </div>`
    ).join("");
    return `<div class="bx-agenda-day-group">
      <div class="bx-agenda-date-header">
        <div class="bx-agenda-date-num${isToday ? " today" : ""}">${dayNum}</div>
        <div>
          <div class="bx-agenda-date-label">${dayName}, ${dayNum} ${monName} ${YEAR}</div>
          <div class="bx-agenda-date-sub">${dayItems.length} agenda</div>
        </div>
      </div>
      ${rows}
    </div>`;
  }).join("");

  els.agendaListView.innerHTML = `<div class="bx-agenda-list">${html}</div>`;
}

/* ── Render: Detail panel ───────────────────────────────────────── */
function renderDetail() {
  const visible = filteredMeetings();
  let item = selectedItem();
  if (!item || !visible.some(e => e.id === item.id)) {
    item = monthItems(state.month)[0] || visible[0] || null;
    state.selectedId = item ? item.id : null;
  }
  if (!item) {
    els.detailBadges.innerHTML = `<span class="bx-badge">Kosong</span>`;
    els.detailTitle.textContent = "Tidak ada agenda sesuai filter";
    els.detailDesc.textContent  = "Ubah semester, status, atau kata kunci.";
    els.detailInfo.innerHTML    = "";
    els.publicText.textContent  = "—";
    els.publicToggle.classList.remove("on");
    els.watchBtn.disabled = true;
    els.editBtn.href = "#";
    els.actionDock.innerHTML = `<div class="bx-empty">Tidak ada tindak lanjut.</div>`;
    return;
  }

  const pub = isPublic(item);
  els.detailBadges.innerHTML = `
    <span class="bx-badge" data-tone="${statusTone[item.status]}">${statusLabel[item.status]}</span>
    <span class="bx-badge" style="border-left:3px solid ${jenisColor[item.jenis] || '#64748b'}; padding-left:6px;">${jenisLabel[item.jenis] || item.jenis}</span>
    <span class="bx-badge" data-tone="${pub ? "accent" : "neutral"}">${pub ? "Tayang publik" : "Internal saja"}</span>
    <span class="bx-badge" data-tone="${item.stream ? "success" : "neutral"}">${item.stream ? "Nonton aktif" : "Nonton belum ada"}</span>`;
  els.detailTitle.textContent = item.title;
  els.detailDesc.textContent  = item.description || "—";
  els.detailInfo.innerHTML    = `
    <div class="bx-info-row"><span>Tanggal</span><strong>${formatDate(item.date)}</strong></div>
    <div class="bx-info-row"><span>Waktu</span><strong>${item.start}–${item.end}</strong></div>
    <div class="bx-info-row"><span>Ruangan</span><strong>${esc(item.room)}</strong></div>
    <div class="bx-info-row"><span>Peserta</span><strong>${esc(item.group) || "—"}</strong></div>`;
  els.publicText.textContent = pub ? "Agenda ini boleh tampil di tautan publik." : "Agenda ini tertahan di ruang internal.";
  els.publicToggle.classList.toggle("on", pub);
  els.watchBtn.disabled = !item.stream;
  if (item.stream && item.stream_url) {
    els.watchBtn.onclick = () => window.open(item.stream_url, "_blank");
  }
  els.editBtn.onclick = () => { window.location.href = item.edit_url; };
  els.deleteBtn.onclick = () => {
    if (confirm('Hapus jadwal "' + item.title + '"?')) {
      window.location.href = item.delete_url;
    }
  };

  // Action dock: tindak lanjut otomatis
  const actions = [];
  if (!item.stream) actions.push(actionItem("Tambahkan tautan nonton", "Agar tombol Nonton aktif di card harian.", "Vi"));
  if (!pub)         actions.push(actionItem("Cek kelayakan publik", "Pastikan rapat boleh dikonsumsi publik.", "Pu"));
  els.actionDock.innerHTML = actions.join("") ||
    `<div class="bx-empty">Agenda ini sudah siap dari sisi publik dan nonton. ✓</div>`;
}

function actionItem(title, text, abbr) {
  return `<div class="bx-action-item">
    <div class="bx-action-icon">${abbr}</div>
    <div><strong>${title}</strong><p>${text}</p></div>
    <button class="bx-btn bx-btn-icon" type="button">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m9 18 6-6-6-6"/></svg>
    </button>
  </div>`;
}

/* ── Render: Timeline table ──────────────────────────────────────── */
function renderTable() {
  // Kalau ada dayFilter, tampilkan hanya hari itu; kalau tidak, bulan aktif
  const items = state.dayFilter
    ? filteredMeetings().filter(item => item.date === state.dayFilter)
    : monthItems(state.month);

  // Day filter chip
  if (state.dayFilter) {
    const d = new Date(state.dayFilter + "T00:00:00");
    const label = `${d.getDate()} ${monthNames[d.getMonth()]} ${YEAR}`;
    els.dayFilterLabel.textContent = label;
    els.dayFilterChip.style.display = "inline-flex";
  } else {
    els.dayFilterChip.style.display = "none";
  }

  const subtitle = state.dayFilter
    ? `Menampilkan ${items.length} agenda pada tanggal terpilih.`
    : `${monthNames[state.month]} ${YEAR} — agenda sesuai filter aktif.`;
  els.tableCount.textContent    = `${items.length} agenda`;
  els.tableSubtitle.textContent = subtitle;
  if (!items.length) {
    els.agendaTable.innerHTML = `<tr><td colspan="6"><div class="bx-empty">Belum ada jadwal rapat pada periode ini.</div></td></tr>`;
    return;
  }
  els.agendaTable.innerHTML = items.map(item => `<tr>
    <td class="bx-mono">${formatDate(item.date)}<br>${item.start}\u2013${item.end}</td>
    <td><strong>${esc(item.title)}</strong><span class="bx-desc">${esc(item.description || "")}</span></td>
    <td>${esc(item.room)}</td>
    <td>${esc(item.group) || "\u2014"}</td>
    <td>
      <span class="bx-badge" data-tone="${statusTone[item.status]}">${statusLabel[item.status]}</span>
      <span class="bx-badge" style="border-left:3px solid ${jenisColor[item.jenis] || '#64748b'};">${jenisLabel[item.jenis] || item.jenis}</span>
    </td>
    <td>
      <div class="bx-row-actions">
        <button class="bx-btn bx-btn-icon" type="button" data-id="${item.id}" title="Lihat detail">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
        </button>
        <button class="bx-btn bx-btn-icon" type="button" ${item.stream ? "" : "disabled"} title="Nonton"
          ${item.stream ? `onclick="window.open('${item.stream_url}','_blank')"` : ""}>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m10 8 6 4-6 4V8Z"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
        </button>
        <a href="${item.edit_url}" class="bx-btn bx-btn-icon" title="Edit jadwal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
        </a>
        <a href="${item.delete_url}" class="bx-btn bx-btn-icon" title="Hapus jadwal" onclick="return confirm('Hapus jadwal ini?')" style="color:var(--od-danger);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </a>
      </div>
    </td>
  </tr>`).join("");
}

/* ── Switch view ────────────────────────────────────────────────── */
function setView(v) {
  state.view = v;
  const isAgenda = v === "agenda";
  els.calendarGrid.style.display    = isAgenda ? "none" : "";
  els.agendaListView.style.display  = isAgenda ? "block" : "none";
  // month strip — sembunyikan saat agenda (full semester view)
  $("monthStrip").style.display     = isAgenda ? "none" : "";
  $("prevMonth").style.display      = isAgenda ? "none" : "";
  $("nextMonth").style.display      = isAgenda ? "none" : "";
  els.btnViewCal.classList.toggle("active", !isAgenda);
  els.btnViewAgenda.classList.toggle("active", isAgenda);
}

/* ── Render all ─────────────────────────────────────────────────── */
function renderAll() {
  renderSummary();
  renderMonths();
  if (state.view === "cal") {
    renderCalendar();
  } else {
    renderAgendaList();
  }
  renderTable();
  renderDetail();
}

/* ── Move month ─────────────────────────────────────────────────── */
function moveMonth(delta) {
  const months = semesterMonths();
  const idx = months.indexOf(state.month);
  const next = months[Math.max(0, Math.min(months.length - 1, idx + delta))];
  if (next !== state.month) { state.month = next; state.selectedId = null; renderAll(); }
}

/* ── Event listeners ────────────────────────────────────────────── */
els.semester.addEventListener("change", e => {
  state.semester = e.target.value; state.month = semesterMonths()[0]; state.selectedId = null;
  if (els.hiddenSem) els.hiddenSem.value = e.target.value;
  renderAll();
});
els.jenis.addEventListener("change",  e => { state.jenis  = e.target.value; state.selectedId = null; renderAll(); });
els.status.addEventListener("change", e => { state.status = e.target.value; state.selectedId = null; renderAll(); });
els.search.addEventListener("input",  e => { state.search = e.target.value; state.selectedId = null; renderAll(); });
els.monthStrip.addEventListener("click", e => {
  const btn = e.target.closest("[data-month]");
  if (!btn) return;
  state.month = Number(btn.dataset.month); state.selectedId = null; renderAll();
});
els.calendarGrid.addEventListener("click", e => {
  // +N chip — filter tabel ke hari itu
  const chip = e.target.closest(".bx-more-chip");
  if (chip) {
    state.dayFilter = chip.dataset.date;
    renderAll();
    document.querySelector(".bx-timeline")?.scrollIntoView({ behavior: "smooth", block: "start" });
    return;
  }
  const btn = e.target.closest("[data-id]");
  if (!btn) return;
  state.selectedId = Number(btn.dataset.id);
  state.dayFilter = null; // buka kartu → hapus day filter
  renderAll();
});
els.agendaListView.addEventListener("click", e => {
  const row = e.target.closest("[data-id]");
  if (!row) return;
  state.selectedId = Number(row.dataset.id); renderAll();
});
els.agendaTable.addEventListener("click", e => {
  const btn = e.target.closest("[data-id]");
  if (!btn) return;
  state.selectedId = Number(btn.dataset.id); renderAll();
  document.querySelector(".bx-main-grid")?.scrollIntoView({ behavior: "smooth", block: "start" });
});
// Day filter chip — klik untuk hapus filter
els.dayFilterChip.addEventListener("click", () => {
  state.dayFilter = null;
  renderAll();
});
// View toggle
els.btnViewCal.addEventListener("click", () => {
  setView("cal"); renderAll();
});
els.btnViewAgenda.addEventListener("click", () => {
  setView("agenda"); renderAll();
});
$("prevMonth").addEventListener("click", () => moveMonth(-1));
$("nextMonth").addEventListener("click", () => moveMonth(1));
$("resetBtn").addEventListener("click", () => {
  state.jenis = "all"; state.status = "all"; state.search = "";
  els.jenis.value  = "all";
  els.status.value = "all";
  els.search.value = "";
  renderAll();
});
els.publicToggle.addEventListener("click", () => {
  const item = selectedItem(); if (!item) return;
  publicOverrides[item.id] = !isPublic(item);
  renderAll();
});

/* ── Init ───────────────────────────────────────────────────────── */
els.semester.value = state.semester;
setView("cal");
renderAll();
</script>
<?= $this->endSection() ?>
