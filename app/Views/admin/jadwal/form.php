<?php
$flatpickrCssVersion = is_file(FCPATH . 'assets/vendor/flatpickr/flatpickr.min.css') ? filemtime(FCPATH . 'assets/vendor/flatpickr/flatpickr.min.css') : time();
$flatpickrJsVersion  = is_file(FCPATH . 'assets/vendor/flatpickr/flatpickr.min.js') ? filemtime(FCPATH . 'assets/vendor/flatpickr/flatpickr.min.js') : time();
$flatpickrIdVersion  = is_file(FCPATH . 'assets/vendor/flatpickr/l10n/id.js') ? filemtime(FCPATH . 'assets/vendor/flatpickr/l10n/id.js') : time();
?>
<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendor/flatpickr/flatpickr.min.css?v=' . $flatpickrCssVersion) ?>">
<style>
    .schedule-form {
        --form-gap: 18px;
    }

    .flatpickr-input[readonly] {
        background-color: #fff !important;
    }

    .flatpickr-input[readonly]:focus {
        border-color: var(--od-accent);
        box-shadow: var(--od-focus);
    }

    .form-card {
        background: var(--od-surface);
        border: 1px solid var(--od-border);
        border-radius: var(--od-radius-lg);
        padding: 0;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }

    .form-card-header {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 56px;
        padding: 12px 18px;
        border-bottom: 1px solid var(--od-border-soft);
        background: #fbfdff;
    }

    .form-card-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 32px;
        background: var(--od-surface-warm);
        color: var(--od-accent);
        font-size: 0.95rem;
    }

    .form-card-title {
        margin: 0;
        color: var(--od-fg);
        font-size: 0.92rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .form-card-body {
        padding: 18px;
    }

    .form-section {
        padding-top: 16px;
        margin-top: 16px;
        border-top: 1px solid var(--od-border-soft);
    }

    .form-section:first-child {
        padding-top: 0;
        margin-top: 0;
        border-top: 0;
    }

    .form-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        color: var(--od-fg2);
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .form-section-title::before {
        content: "";
        width: 4px;
        height: 16px;
        border-radius: 999px;
        background: var(--od-accent);
    }

    .ta-label {
        color: var(--od-fg2);
        font-size: 0.82rem;
        margin-bottom: 5px;
    }

    .required-mark {
        color: var(--od-danger);
    }

    .ta-input-addon {
        min-width: 43px;
        justify-content: center;
        background-color: #f8fafc;
        border-color: var(--od-border);
        color: var(--od-muted);
    }

    .ta-input,
    .ta-select {
        border-color: var(--od-border);
        color: var(--od-fg);
    }

    .ta-input:focus,
    .ta-select:focus {
        border-color: var(--od-accent);
        box-shadow: var(--od-focus);
    }

    .option-group {
        display: grid;
        gap: 10px;
    }

    .option-group.two-col {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .choice-option {
        position: relative;
        margin: 0;
    }

    .choice-option input {
        position: absolute;
        inset: 0;
        opacity: 0;
        pointer-events: none;
    }

    .choice-option-body {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 54px;
        padding: 9px;
        border: 1px solid var(--od-border);
        border-radius: var(--od-radius-md);
        background: var(--od-surface);
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
    }

    .choice-option-body:hover {
        border-color: var(--od-accent);
        background: #fbfdff;
    }

    .choice-option input:checked + .choice-option-body {
        border-color: var(--od-accent);
        background: var(--od-surface-warm);
        box-shadow: 0 0 0 1px var(--od-accent);
    }

    .choice-option input:focus-visible + .choice-option-body {
        box-shadow: var(--od-focus);
    }

    .choice-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 28px;
        background: #f1f5f9;
        color: var(--od-muted);
    }

    .choice-title {
        display: block;
        color: var(--od-fg);
        font-size: var(--od-text-sm);
        font-weight: 700;
        line-height: 1.2;
    }

    .choice-desc {
        display: block;
        margin-top: 3px;
        color: var(--od-muted);
        font-size: var(--od-text-xs);
        line-height: 1.35;
    }

    .visibility-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        padding: 10px;
        border: 1px solid var(--od-border-soft);
        border-radius: var(--od-radius-md);
        background: #fbfdff;
    }

    .visibility-title {
        color: var(--od-fg2);
        font-size: var(--od-text-sm);
        font-weight: 700;
        line-height: 1.2;
        margin: 0;
    }

    .visibility-control {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        white-space: nowrap;
    }

    .visibility-row .ta-check-input {
        width: 2.55em;
        height: 1.35em;
        margin: 0;
    }

    .visibility-label {
        min-width: 64px;
        padding: 4px 9px;
        border: 1px solid var(--od-border);
        border-radius: var(--od-radius-pill);
        background: var(--od-surface);
        color: var(--od-fg);
        font-size: var(--od-text-xs);
        font-weight: 700;
        line-height: 1;
        text-align: center;
    }

    .location-fields {
        margin-top: 10px;
    }

    .location-panel[hidden] {
        display: none;
    }

    .target-picker {
        border: 1px solid var(--od-border);
        border-radius: var(--od-radius-md);
        overflow: hidden;
        background: var(--od-surface);
    }

    .target-picker-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 7px 10px;
        border-bottom: 1px solid var(--od-border-soft);
        background: #f8fafc;
    }

    .target-picker-title {
        color: var(--od-muted);
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.45px;
        text-transform: uppercase;
    }

    .target-picker-meta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }

    .target-picker-search {
        padding: 8px;
        border-bottom: 1px solid var(--od-border-soft);
    }

    .target-list {
        max-height: 190px;
        overflow-y: auto;
    }

    .target-option {
        display: flex;
        align-items: center;
        min-height: 36px;
        padding: 7px 10px;
        border-bottom: 1px solid var(--od-border-soft);
        background: var(--od-surface);
        color: var(--od-fg2);
        font-size: var(--od-text-sm);
        font-weight: 500;
        cursor: pointer;
        margin-bottom: 0;
        user-select: none;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    .target-option:last-child {
        border-bottom: 0;
    }

    .target-option:hover {
        background: #fbfdff;
    }

    .target-option.is-selected {
        background-color: var(--od-surface-warm);
        color: var(--od-fg);
        font-weight: 700;
    }

    .target-option.is-disabled {
        cursor: not-allowed;
        opacity: 0.72;
    }

    .target-option.is-disabled .ta-check-input {
        pointer-events: none;
    }

    .target-option .ta-check-input {
        margin-top: 0;
    }

    .side-stack {
        position: sticky;
        top: 88px;
    }

    @media (max-width: 991.98px) {
        .side-stack {
            position: static;
        }
    }

    @media (max-width: 575.98px) {
        .form-card-header {
            padding-left: 14px;
            padding-right: 14px;
        }

        .form-card-body {
            padding-left: 14px;
            padding-right: 14px;
        }

        .option-group.two-col {
            grid-template-columns: 1fr;
        }

        .visibility-row {
            align-items: center;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $lokasiLainnya = trim((string) ($meeting['lokasi_lainnya'] ?? ''));
    $lokasiMode = $lokasiLainnya !== '' ? 'lainnya' : 'ruangan';
?>

<div class="page-header">
    <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    <p class="page-subtitle text-gray-500">
        <?= $meeting ? 'Perbarui jadwal rapat' : 'Buat jadwal rapat baru' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="POST" class="schedule-form">
    <?= csrf_field() ?>

    <div class="grid grid-cols-12 gap-3">
        <div class="lg:col-span-8">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="form-card-icon"><i data-lucide="calendar-days"></i></span>
                    <h2 class="form-card-title">Detail Rapat</h2>
                </div>

                <div class="form-card-body">
                    <div class="form-section">
                        <div class="form-section-title">Informasi Dasar</div>

                        <div class="grid grid-cols-12 gap-3">
                            <div class="col-span-12">
                                <label class="ta-label font-semibold" for="judul">
                                    Judul Rapat <span class="required-mark">*</span>
                                </label>
                                <div class="ta-input-group">
                                    <span class="ta-input-addon"><i data-lucide="file-text"></i></span>
                                    <input type="text" class="ta-input" id="judul" name="judul"
                                        value="<?= esc($meeting['judul'] ?? '') ?>"
                                        placeholder="Contoh: Rapat Paripurna Pembahasan APBD 2026" required />
                                </div>
                            </div>

                            <div class="col-span-12">
                                <label class="ta-label font-semibold block">
                                    Lokasi Rapat <span class="required-mark">*</span>
                                </label>

                                <div class="option-group two-col">
                                    <label class="choice-option" for="lokasi-ruangan">
                                        <input type="radio" name="lokasi_mode" id="lokasi-ruangan" value="ruangan"
                                            <?= $lokasiMode === 'ruangan' ? 'checked' : '' ?> />
                                        <span class="choice-option-body">
                                            <span class="choice-icon"><i data-lucide="building-2"></i></span>
                                            <span>
                                                <span class="choice-title">Ruangan DPRD</span>
                                                <span class="choice-desc">Pilih dari master ruangan</span>
                                            </span>
                                        </span>
                                    </label>

                                    <label class="choice-option" for="lokasi-lainnya">
                                        <input type="radio" name="lokasi_mode" id="lokasi-lainnya" value="lainnya"
                                            <?= $lokasiMode === 'lainnya' ? 'checked' : '' ?> />
                                        <span class="choice-option-body">
                                            <span class="choice-icon"><i data-lucide="map-pin"></i></span>
                                            <span>
                                                <span class="choice-title">Lokasi Lainnya</span>
                                                <span class="choice-desc">Isi nama/tempat rapat</span>
                                            </span>
                                        </span>
                                    </label>
                                </div>

                                <div class="location-fields">
                                    <div class="location-panel" id="ruangan-panel">
                                        <div class="ta-input-group">
                                            <span class="ta-input-addon"><i data-lucide="map-pin"></i></span>
                                            <select class="ta-select" id="ruangan_id" name="ruangan_id">
                                                <option value="">-- Pilih Ruangan --</option>
                                                <?php if (empty($rooms)): ?>
                                                    <option disabled>Belum ada ruangan - tambah di Master Data dulu</option>
                                                <?php else: ?>
                                                    <?php foreach ($rooms as $r):
                                                        $selected = ($meeting['ruangan_id'] ?? '') == $r['id'] ? 'selected' : '';
                                                    ?>
                                                        <option value="<?= $r['id'] ?>" <?= $selected ?>>
                                                            <?= esc($r['name'] ?? '') ?><?= isset($r['kapasitas']) ? ' (Kap. ' . $r['kapasitas'] . ' orang)' : '' ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="location-panel" id="lokasi-lainnya-panel" hidden>
                                        <div class="ta-input-group">
                                            <span class="ta-input-addon"><i data-lucide="map-pinned"></i></span>
                                            <input type="text" class="ta-input" id="lokasi_lainnya" name="lokasi_lainnya"
                                                value="<?= esc($lokasiLainnya) ?>"
                                                placeholder="Contoh: Aula Kantor Gubernur, Gedung Serbaguna, atau tempat rapat lainnya" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-6">
                                <label class="ta-label font-semibold" for="waktu_mulai">
                                    Waktu Mulai <span class="required-mark">*</span>
                                </label>
                                <div class="ta-input-group">
                                    <span class="ta-input-addon"><i data-lucide="calendar-clock"></i></span>
                                    <input type="text" class="ta-input" id="waktu_mulai" name="waktu_mulai"
                                        placeholder="Pilih tanggal dan waktu"
                                        value="<?= esc(isset($meeting['tanggal'], $meeting['waktu_mulai'])
                                            ? $meeting['tanggal'] . 'T' . $meeting['waktu_mulai']
                                            : '') ?>" required />
                                </div>
                            </div>

                            <div class="md:col-span-6">
                                <label class="ta-label font-semibold" for="waktu_selesai">
                                    Waktu Selesai <span class="required-mark">*</span>
                                </label>
                                <div class="ta-input-group">
                                    <span class="ta-input-addon"><i data-lucide="calendar-check"></i></span>
                                    <input type="text" class="ta-input" id="waktu_selesai" name="waktu_selesai"
                                        placeholder="Pilih tanggal dan waktu"
                                        value="<?= esc(isset($meeting['tanggal'], $meeting['waktu_selesai'])
                                            ? $meeting['tanggal'] . 'T' . $meeting['waktu_selesai']
                                            : '') ?>" required />
                                </div>
                            </div>

                            <div class="col-span-12">
                                <label class="ta-label font-semibold" for="keterangan">Keterangan / Agenda</label>
                                <textarea class="ta-input" id="keterangan" name="keterangan" rows="4"
                                    placeholder="Uraian singkat agenda rapat..."><?= esc($meeting['keterangan'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Publikasi & Materi</div>

                        <div class="grid grid-cols-12 gap-3">
                            <div class="col-span-12">
                                <label class="ta-label font-semibold" for="materi_url">
                                    Link Materi Rapat <span class="text-gray-500 font-normal">(Opsional)</span>
                                    <span class="ta-badge bg-brand-50 text-brand-600 ml-1">QR Code</span>
                                </label>
                                <div class="ta-input-group">
                                    <span class="ta-input-addon"><i data-lucide="qr-code"></i></span>
                                    <input type="url" class="ta-input" id="materi_url" name="materi_url"
                                        value="<?= esc($meeting['materi_url'] ?? '') ?>"
                                        placeholder="https://drive.google.com/... atau link dokumen lainnya" />
                                </div>
                            </div>

                            <div class="col-span-12">
                                <label class="ta-label font-semibold" for="stream_url">
                                    Link Live Streaming / Arsip Video <span class="text-gray-500 font-normal">(Opsional)</span>
                                </label>
                                <div class="ta-input-group">
                                    <span class="ta-input-addon"><i data-lucide="square-play"></i></span>
                                    <input type="url" class="ta-input" id="stream_url" name="stream_url"
                                        value="<?= esc($meeting['stream_url'] ?? '') ?>"
                                        placeholder="https://youtube.com/live/... atau link streaming lainnya" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4">
            <div class="side-stack">
                <div class="form-card">
                    <div class="form-card-header">
                        <span class="form-card-icon"><i data-lucide="sliders-horizontal"></i></span>
                        <h2 class="form-card-title">Pengaturan</h2>
                    </div>

                    <div class="form-card-body">
                        <div class="form-section">
                            <div class="form-section-title">Klasifikasi</div>
                            <?php $jenis = $meeting['jenis'] ?? 'insidental'; ?>

                            <label class="ta-label font-semibold block">Jenis Rapat</label>

                            <div class="option-group two-col">
                                <label class="choice-option" for="jenis-reguler">
                                    <input type="radio" name="jenis" id="jenis-reguler" value="reguler"
                                        <?= $jenis === 'reguler' ? 'checked' : '' ?> />
                                    <span class="choice-option-body">
                                        <span class="choice-icon"><i data-lucide="calendar-range"></i></span>
                                        <span>
                                            <span class="choice-title">Reguler</span>
                                            <span class="choice-desc">Agenda terencana</span>
                                        </span>
                                    </span>
                                </label>

                                <label class="choice-option" for="jenis-insidental">
                                    <input type="radio" name="jenis" id="jenis-insidental" value="insidental"
                                        <?= $jenis === 'insidental' ? 'checked' : '' ?> />
                                    <span class="choice-option-body">
                                        <span class="choice-icon"><i data-lucide="zap"></i></span>
                                        <span>
                                            <span class="choice-title">Insidental</span>
                                            <span class="choice-desc">Agenda mendadak</span>
                                        </span>
                                    </span>
                                </label>
                            </div>

                            <div class="visibility-row mt-3">
                                <label class="visibility-title" for="is_publik">Visibilitas Publik</label>
                                <div class="ta-check ta-switch visibility-control">
                                    <input class="ta-check-input" type="checkbox" role="switch"
                                        id="is_publik" name="is_publik" value="1"
                                        <?= ($meeting === null || ($meeting['is_publik'] ?? 1)) ? 'checked' : '' ?> />
                                    <label class="visibility-label" for="is_publik">
                                        <span id="publik-label"><?= ($meeting === null || ($meeting['is_publik'] ?? 1)) ? 'Publik' : 'Internal' ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">Peserta & WA</div>

                            <div class="flex items-center justify-between gap-2 mb-2">
                                <label class="ta-label font-semibold mb-0">Peserta Rapat</label>
                                <span class="ta-badge bg-brand-50 text-brand-600" id="target-selected-count">0 dipilih</span>
                            </div>

                            <div class="target-picker">
                                <div class="target-picker-head">
                                    <span class="target-picker-title">Kelompok Peserta</span>
                                    <span class="target-picker-meta">
                                        <span class="ta-badge bg-gray-100 rounded-full" id="target-visible-count"><?= count($unit_rapat_list) ?></span>
                                    </span>
                                </div>
                                <div class="target-picker-search">
                                    <div class="relative">
                                        <span class="absolute left-0 top-1/2 -translate-y-1/2 pl-3 text-gray-500">
                                            <i data-lucide="search"></i>
                                        </span>
                                        <input type="text" class="ta-input ta-input-sm pl-10"
                                            id="target-search" placeholder="Cari kelompok peserta..." autocomplete="off" />
                                    </div>
                                </div>
                                <div class="target-list" id="target-list">
                                <?php
                                $targetUnitIds = $meeting['target_unit_ids'] ?? [];
                                foreach ($unit_rapat_list as $unit):
                                    $unitId = (int) $unit['id'];
                                    $unitName = $unit['nama'];
                                    $checked = in_array($unitId, $targetUnitIds, true) ? 'checked' : '';
                                    $selectedClass = $checked ? ' is-selected' : '';
                                    $targetId = 'unit-rapat-' . $unitId;
                                ?>
                                    <label class="target-option<?= $selectedClass ?>" for="<?= esc($targetId, 'attr') ?>"
                                        data-name="<?= esc(strtolower($unitName), 'attr') ?>">
                                        <input class="ta-check-input mr-2" type="checkbox" name="target_unit_rapat[]"
                                            value="<?= $unitId ?>" data-name="<?= esc($unitName, 'attr') ?>"
                                            id="<?= esc($targetId, 'attr') ?>" <?= $checked ?> />
                                        <span><?= esc($unitName) ?></span>
                                    </label>
                                <?php endforeach; ?>
                                    <div class="text-gray-500 text-center py-3 hidden" id="target-empty">
                                        <small>Kelompok peserta tidak ditemukan.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="ta-label font-semibold" for="blast_before">
                                    <i data-lucide="message-circle" class="text-emerald-600 mr-1"></i> Jadwal Blast WA
                                </label>
                                <select class="ta-select" id="blast_before" name="blast_before">
                                    <option value="1440" <?= ($meeting['blast_before'] ?? '') == 1440 ? 'selected' : '' ?>>H-1 hari sebelum rapat</option>
                                    <option value="120"  <?= ($meeting['blast_before'] ?? '') == 120  ? 'selected' : '' ?>>H-2 jam sebelum rapat</option>
                                    <option value="60"   <?= ($meeting['blast_before'] ?? '') == 60   ? 'selected' : '' ?>>H-1 jam sebelum rapat</option>
                                    <option value="30"   <?= ($meeting['blast_before'] ?? '') == 30   ? 'selected' : '' ?>>H-30 menit sebelum rapat</option>
                                    <option value="0"    <?= ($meeting['blast_before'] ?? '') == 0    ? 'selected' : '' ?>>Tepat saat rapat dimulai</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-2 mt-3">
        <a href="<?= base_url('admin/jadwal') ?>" class="ta-btn ta-btn-outline-gray">
            <i data-lucide="arrow-left" class="mr-1"></i>Batal
        </a>
        <button type="submit" class="ta-btn ta-btn-primary">
            <i data-lucide="calendar-check" class="mr-1"></i>
            <?= $meeting ? 'Simpan Perubahan' : 'Simpan & Jadwalkan Notifikasi' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/flatpickr/flatpickr.min.js?v=' . $flatpickrJsVersion) ?>"></script>
<script src="<?= base_url('assets/vendor/flatpickr/l10n/id.js?v=' . $flatpickrIdVersion) ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr("#waktu_mulai", {
            enableTime: true,
            dateFormat: "Y-m-d\\TH:i",
            altInput: true,
            altFormat: "d-m-Y H:i",
            time_24hr: true,
            locale: "id"
        });

        flatpickr("#waktu_selesai", {
            enableTime: true,
            dateFormat: "Y-m-d\\TH:i",
            altInput: true,
            altFormat: "d-m-Y H:i",
            time_24hr: true,
            locale: "id"
        });

        const toggle = document.getElementById('is_publik');
        const label = document.getElementById('publik-label');
        if (toggle && label) {
            toggle.addEventListener('change', function() {
                label.textContent = this.checked ? 'Publik' : 'Internal';
            });
        }

        const lokasiModeInputs = Array.from(document.querySelectorAll('input[name="lokasi_mode"]'));
        const ruanganPanel = document.getElementById('ruangan-panel');
        const lokasiLainnyaPanel = document.getElementById('lokasi-lainnya-panel');
        const ruanganSelect = document.getElementById('ruangan_id');
        const lokasiLainnyaInput = document.getElementById('lokasi_lainnya');

        const syncLocationMode = function() {
            const mode = lokasiModeInputs.find(function(input) {
                return input.checked;
            })?.value || 'ruangan';

            const isOther = mode === 'lainnya';

            if (ruanganPanel) ruanganPanel.hidden = isOther;
            if (lokasiLainnyaPanel) lokasiLainnyaPanel.hidden = !isOther;

            if (ruanganSelect) {
                ruanganSelect.required = !isOther;
                ruanganSelect.disabled = isOther;
            }

            if (lokasiLainnyaInput) {
                lokasiLainnyaInput.required = isOther;
                lokasiLainnyaInput.disabled = !isOther;
            }
        };

        lokasiModeInputs.forEach(function(input) {
            input.addEventListener('change', syncLocationMode);
        });
        syncLocationMode();

        const targetInputs = Array.from(document.querySelectorAll('.target-option input[type="checkbox"]'));
        const targetOptions = Array.from(document.querySelectorAll('.target-option'));
        const targetSearch = document.getElementById('target-search');
        const targetSelectedCount = document.getElementById('target-selected-count');
        const targetVisibleCount = document.getElementById('target-visible-count');
        const targetEmpty = document.getElementById('target-empty');

        const syncTargetVisual = function(input) {
            const option = input.closest('.target-option');
            if (!option) return;

            option.classList.toggle('is-selected', input.checked);
        };

        const syncTargetCount = function() {
            const count = targetInputs.filter(function(input) {
                return input.checked;
            }).length;

            if (targetSelectedCount) {
                targetSelectedCount.textContent = count + ' dipilih';
            }
        };

        targetInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                syncTargetVisual(this);
                syncTargetCount();
            });

            syncTargetVisual(input);
        });

        targetSearch?.addEventListener('input', function() {
            const q = (this.value || '').trim().toLowerCase();
            let shown = 0;

            targetOptions.forEach(function(option) {
                const match = (option.getAttribute('data-name') || '').includes(q);
                option.style.display = match ? '' : 'none';
                if (match) shown++;
            });

            if (targetVisibleCount) {
                targetVisibleCount.textContent = shown;
            }
            if (targetEmpty) {
                targetEmpty.classList.toggle('hidden', shown > 0);
            }
        });

        syncTargetCount();
    });
</script>
<?= $this->endSection() ?>
