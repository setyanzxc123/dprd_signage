<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        gap: 12px;
        min-height: 74px;
        padding: 18px 24px;
        border-bottom: 1px solid var(--od-border-soft);
        background: #fbfdff;
    }

    .form-card-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 36px;
        background: var(--od-surface-warm);
        color: var(--od-accent);
        font-size: 1rem;
    }

    .form-card-title {
        margin: 0;
        color: var(--od-fg);
        font-size: 0.98rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .form-card-body {
        padding: 24px;
    }

    .form-section {
        padding-top: 20px;
        margin-top: 22px;
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
        margin-bottom: 16px;
        color: var(--od-fg2);
        font-size: 0.8rem;
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

    .form-label {
        color: var(--od-fg2);
        font-size: var(--od-text-sm);
        margin-bottom: 7px;
    }

    .required-mark {
        color: var(--od-danger);
    }

    .input-group-text {
        min-width: 43px;
        justify-content: center;
        background-color: #f8fafc;
        border-color: var(--od-border);
        color: var(--od-muted);
    }

    .form-control,
    .form-select {
        border-color: var(--od-border);
        color: var(--od-fg);
    }

    .form-control:focus,
    .form-select:focus {
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
        gap: 10px;
        min-height: 68px;
        padding: 12px;
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
        width: 32px;
        height: 32px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 32px;
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
        gap: 14px;
        align-items: center;
        padding: 12px;
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

    .visibility-row .form-check-input {
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

    .target-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .target-option {
        display: flex;
        align-items: center;
        min-height: 42px;
        padding: 9px 11px;
        border: 1px solid var(--od-border);
        border-radius: var(--od-radius-sm);
        background: var(--od-surface);
        color: var(--od-fg2);
        font-size: var(--od-text-sm);
        font-weight: 500;
        cursor: pointer;
        margin-bottom: 0;
        user-select: none;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    .target-option:hover {
        background: #fbfdff;
        border-color: var(--od-accent);
    }

    .target-option.is-selected {
        border-color: var(--od-accent);
        background-color: var(--od-surface-warm);
        color: var(--od-fg);
        font-weight: 700;
    }

    .target-option.is-disabled {
        cursor: not-allowed;
        opacity: 0.72;
    }

    .target-option.is-disabled .form-check-input {
        pointer-events: none;
    }

    .target-option .form-check-input {
        margin-top: 0;
    }

    .side-stack {
        position: sticky;
        top: 88px;
    }

    .form-action-bar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        margin-top: 18px;
        padding: 16px 18px;
        border: 1px solid var(--od-border);
        border-radius: var(--od-radius-lg);
        background: var(--od-surface);
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: space-between;
        width: 100%;
    }

    .action-buttons .btn {
        min-width: 148px;
    }

    @media (max-width: 991.98px) {
        .side-stack {
            position: static;
        }
    }

    @media (max-width: 575.98px) {
        .form-card-header,
        .form-card-body {
            padding-left: 18px;
            padding-right: 18px;
        }

        .option-group.two-col,
        .target-grid {
            grid-template-columns: 1fr;
        }

        .form-action-bar {
            align-items: stretch;
            flex-direction: column;
        }

        .visibility-row {
            align-items: center;
        }

        .action-buttons,
        .action-buttons .btn {
            width: 100%;
        }

        .action-buttons .btn {
            justify-content: center;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    <p class="page-subtitle text-muted">
        <?= $meeting ? 'Perbarui jadwal rapat' : 'Buat jadwal rapat baru' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="POST" class="schedule-form">
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="form-card-icon"><i class="bi bi-calendar2-week"></i></span>
                    <h2 class="form-card-title">Detail Rapat</h2>
                </div>

                <div class="form-card-body">
                    <div class="form-section">
                        <div class="form-section-title">Informasi Dasar</div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="judul">
                                    Judul Rapat <span class="required-mark">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-file-text"></i></span>
                                    <input type="text" class="form-control" id="judul" name="judul"
                                        value="<?= esc($meeting['judul'] ?? '') ?>"
                                        placeholder="Contoh: Rapat Paripurna Pembahasan APBD 2026" required />
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="ruangan_id">
                                    Ruangan <span class="required-mark">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                    <select class="form-select" id="ruangan_id" name="ruangan_id" required>
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

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="waktu_mulai">
                                    Waktu Mulai <span class="required-mark">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                    <input type="text" class="form-control" id="waktu_mulai" name="waktu_mulai"
                                        placeholder="Pilih tanggal dan waktu"
                                        value="<?= esc(isset($meeting['tanggal'], $meeting['waktu_mulai'])
                                            ? $meeting['tanggal'] . 'T' . $meeting['waktu_mulai']
                                            : '') ?>" required />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="waktu_selesai">
                                    Waktu Selesai <span class="required-mark">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                                    <input type="text" class="form-control" id="waktu_selesai" name="waktu_selesai"
                                        placeholder="Pilih tanggal dan waktu"
                                        value="<?= esc(isset($meeting['tanggal'], $meeting['waktu_selesai'])
                                            ? $meeting['tanggal'] . 'T' . $meeting['waktu_selesai']
                                            : '') ?>" required />
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="keterangan">Keterangan / Agenda</label>
                                <textarea class="form-control" id="keterangan" name="keterangan" rows="4"
                                    placeholder="Uraian singkat agenda rapat..."><?= esc($meeting['keterangan'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Publikasi & Materi</div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="materi_url">
                                    Link Materi Rapat <span class="text-muted fw-normal">(Opsional)</span>
                                    <span class="badge bg-primary-subtle text-primary ms-1">QR Code</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-qr-code"></i></span>
                                    <input type="url" class="form-control" id="materi_url" name="materi_url"
                                        value="<?= esc($meeting['materi_url'] ?? '') ?>"
                                        placeholder="https://drive.google.com/... atau link dokumen lainnya" />
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="stream_url">
                                    Link Live Streaming / Arsip Video <span class="text-muted fw-normal">(Opsional)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-play-btn"></i></span>
                                    <input type="url" class="form-control" id="stream_url" name="stream_url"
                                        value="<?= esc($meeting['stream_url'] ?? '') ?>"
                                        placeholder="https://youtube.com/live/... atau link streaming lainnya" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="side-stack">
                <div class="form-card">
                    <div class="form-card-header">
                        <span class="form-card-icon"><i class="bi bi-sliders"></i></span>
                        <h2 class="form-card-title">Pengaturan</h2>
                    </div>

                    <div class="form-card-body">
                        <div class="form-section">
                            <div class="form-section-title">Klasifikasi</div>
                            <?php $jenis = $meeting['jenis'] ?? 'insidental'; ?>

                            <label class="form-label fw-semibold d-block">Jenis Rapat</label>

                            <div class="option-group two-col">
                                <label class="choice-option" for="jenis-reguler">
                                    <input type="radio" name="jenis" id="jenis-reguler" value="reguler"
                                        <?= $jenis === 'reguler' ? 'checked' : '' ?> />
                                    <span class="choice-option-body">
                                        <span class="choice-icon"><i class="bi bi-calendar-week"></i></span>
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
                                        <span class="choice-icon"><i class="bi bi-lightning-charge"></i></span>
                                        <span>
                                            <span class="choice-title">Insidental</span>
                                            <span class="choice-desc">Agenda mendadak</span>
                                        </span>
                                    </span>
                                </label>
                            </div>

                            <div class="visibility-row mt-3">
                                <label class="visibility-title" for="is_publik">Visibilitas Publik</label>
                                <div class="form-check form-switch visibility-control">
                                    <input class="form-check-input" type="checkbox" role="switch"
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

                            <label class="form-label fw-semibold">Target Peserta / Unit Rapat</label>

                            <div class="target-grid">
                                <?php
                                $targetUnitIds = $meeting['target_unit_ids'] ?? [];
                                foreach ($unit_rapat_list as $unit):
                                    $unitId = (int) $unit['id'];
                                    $unitName = $unit['nama'];
                                    $checked = in_array($unitId, $targetUnitIds, true) ? 'checked' : '';
                                    $selectedClass = $checked ? ' is-selected' : '';
                                    $targetId = 'unit-rapat-' . $unitId;
                                ?>
                                    <label class="target-option<?= $selectedClass ?>" for="<?= esc($targetId, 'attr') ?>">
                                        <input class="form-check-input me-2" type="checkbox" name="target_unit_rapat[]"
                                            value="<?= $unitId ?>" data-name="<?= esc($unitName, 'attr') ?>"
                                            id="<?= esc($targetId, 'attr') ?>" <?= $checked ?> />
                                        <span><?= esc($unitName) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-3">
                                <label class="form-label fw-semibold" for="blast_before">
                                    <i class="bi bi-whatsapp text-success me-1"></i> Jadwal Blast WA
                                </label>
                                <select class="form-select" id="blast_before" name="blast_before">
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

    <div class="form-action-bar">
        <div class="action-buttons">
            <a href="<?= base_url('admin/jadwal') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Batal</span>
            </a>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-calendar-check"></i>
                <span><?= $meeting ? 'Simpan Perubahan' : 'Simpan & Jadwalkan Notifikasi' ?></span>
            </button>
        </div>
    </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
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

        const targetInputs = Array.from(document.querySelectorAll('.target-option input[type="checkbox"]'));
        const allKomisiInput = targetInputs.find(function(input) {
            return input.dataset.name === 'All Komisi' || input.dataset.name === 'Seluruh Anggota';
        });

        const syncTargetVisual = function(input) {
            const option = input.closest('.target-option');
            if (!option) return;

            option.classList.toggle('is-selected', input.checked);
            option.classList.toggle('is-disabled', input.disabled);
        };

        const syncAllKomisi = function(savePreviousState) {
            if (!allKomisiInput) return;

            const isAllKomisi = allKomisiInput.checked;
            targetInputs.forEach(function(input) {
                if (input === allKomisiInput) {
                    input.disabled = false;
                    syncTargetVisual(input);
                    return;
                }

                if (isAllKomisi) {
                    if (savePreviousState) {
                        input.dataset.beforeAllKomisi = input.checked ? '1' : '0';
                    }
                    input.checked = true;
                    input.disabled = true;
                } else {
                    input.disabled = false;
                    if (input.dataset.beforeAllKomisi !== undefined) {
                        input.checked = input.dataset.beforeAllKomisi === '1';
                        delete input.dataset.beforeAllKomisi;
                    }
                }

                syncTargetVisual(input);
            });
        };

        targetInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                if (this === allKomisiInput) {
                    syncAllKomisi(true);
                    return;
                }

                syncTargetVisual(this);
            });
        });

        syncAllKomisi(true);
    });
</script>
<?= $this->endSection() ?>
