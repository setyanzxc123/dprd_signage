<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('styles') ?>
<style {csp-style-nonce}>
    .schedule-form {
        --form-gap: 18px;
    }

    .choice-option {
        position: relative;
        display: block;
    }
    
    .choice-option-body {
        display: grid;
        align-items: center;
        gap: 8px;
        grid-template-columns: auto minmax(0, 1fr);
        min-height: 38px;
        padding: 8px 10px;
        border: 1px solid var(--od-border);
        border-radius: 6px;
        background: var(--od-surface);
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
    }
    
    .choice-option-body:hover {
        border-color: var(--od-accent);
        background: var(--od-border-soft);
    }
    
    .choice-option-body:has(input:checked) {
        border-color: var(--od-accent);
        background: var(--od-surface-warm);
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--od-accent) 12%, transparent);
    }
    
    .choice-option-body:has(input:focus-visible) {
        box-shadow: var(--od-focus);
    }

    .choice-title {
        display: block;
        color: var(--od-fg);
        font-size: .82rem;
        font-weight: 700;
        line-height: 1.15;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .choice-copy {
        min-width: 0;
    }

    .choice-desc {
        color: var(--od-muted);
        display: block;
        font-size: .72rem;
        line-height: 1.25;
        margin-top: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .choice-option-body.has-desc {
        align-items: start;
        gap: 10px;
        min-height: 56px;
        padding: 8px 10px;
    }

    .choice-option-body.has-desc .radio {
        margin-top: 2px;
    }

    .choice-option-body.has-desc .choice-title {
        font-size: .9rem;
        line-height: 1.2;
    }

    .choice-option-body.has-desc .choice-desc {
        color: var(--od-fg2);
        font-size: .8rem;
        line-height: 1.4;
        margin-top: 4px;
        overflow: visible;
        text-overflow: clip;
        white-space: normal;
    }

    .settings-subgroup + .settings-subgroup {
        border-top: 1px solid var(--od-border-soft);
        margin-top: 14px;
        padding-top: 14px;
    }
    
    .visibility-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        padding: 10px;
        border: 1px solid var(--od-border-soft);
        border-radius: var(--od-radius-md);
        background: var(--od-surface);
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
        background: var(--od-border-soft);
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
        background: var(--od-border-soft);
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
    
    .target-option.is-disabled .checkbox {
        pointer-events: none;
    }
    
    .target-option .checkbox {
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
    
    .option-group.two-col {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-top: 8px;
    }

    @media (max-width: 575.98px) {
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
    <p class="page-subtitle">
        <?= $meeting ? 'Perbarui jadwal rapat' : 'Buat jadwal rapat baru' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="POST" class="schedule-form" data-turbo="true">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="alert alert-error shadow-sm mb-3" role="alert">
            <i data-lucide="triangle-alert" class="w-4 h-4"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-12 gap-3">
        <div class="col-span-12 lg:col-span-8">
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
                                <label class="label-text font-bold text-sm mb-1 block" for="judul">
                                    Judul Rapat <span class="text-error">*</span>
                                </label>
                                <div class="join w-full">
                                    <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                                    <input type="text" class="input input-bordered join-item flex-1 w-full" id="judul" name="judul"
                                        value="<?= esc($meeting['judul'] ?? '') ?>"
                                        placeholder="Contoh: Rapat Paripurna Pembahasan APBD 2026" required />
                                </div>
                            </div>

                            <div class="col-span-12">
                                <label class="label-text font-bold text-sm mb-1 block">
                                    Lokasi Rapat <span class="text-error">*</span>
                                </label>

                                <div class="option-group two-col">
                                    <label class="choice-option" for="lokasi-ruangan">
                                        <span class="choice-option-body">
                                            <input type="radio" name="lokasi_mode" id="lokasi-ruangan" value="ruangan"
                                                class="radio radio-primary radio-sm"
                                                <?= $lokasiMode === 'ruangan' ? 'checked' : '' ?> />
                                            <span class="choice-title">Ruangan DPRD</span>
                                        </span>
                                    </label>

                                    <label class="choice-option" for="lokasi-lainnya">
                                        <span class="choice-option-body">
                                            <input type="radio" name="lokasi_mode" id="lokasi-lainnya" value="lainnya"
                                                class="radio radio-primary radio-sm"
                                                <?= $lokasiMode === 'lainnya' ? 'checked' : '' ?> />
                                            <span class="choice-title">Lokasi Lainnya</span>
                                        </span>
                                    </label>
                                </div>

                                <div class="location-fields">
                                    <div class="location-panel" id="ruangan-panel">
                                        <div class="join w-full">
                                            <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                            <select class="select select-bordered join-item flex-1 w-full" id="ruangan_id" name="ruangan_id">
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
                                        <div class="join w-full">
                                            <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="map-pinned" class="w-4 h-4"></i></span>
                                            <input type="text" class="input input-bordered join-item flex-1 w-full" id="lokasi_lainnya" name="lokasi_lainnya"
                                                value="<?= esc($lokasiLainnya) ?>"
                                                placeholder="Contoh: Aula Kantor Gubernur, Gedung Serbaguna, atau tempat rapat lainnya" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 md:col-span-6">
                                <label class="label-text font-bold text-sm mb-1 block" for="waktu_mulai">
                                    Waktu Mulai <span class="text-error">*</span>
                                </label>
                                <div class="join w-full">
                                    <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="calendar-clock" class="w-4 h-4"></i></span>
                                    <input type="datetime-local" class="input input-bordered join-item flex-1 w-full" id="waktu_mulai" name="waktu_mulai"
                                        value="<?= esc(isset($meeting['tanggal'], $meeting['waktu_mulai'])
                                            ? $meeting['tanggal'] . 'T' . $meeting['waktu_mulai']
                                            : '') ?>" step="60" required />
                                </div>
                            </div>

                            <div class="col-span-12 md:col-span-6">
                                <label class="label-text font-bold text-sm mb-1 block" for="waktu_selesai">
                                    Waktu Selesai <span class="text-error">*</span>
                                </label>
                                <div class="join w-full">
                                    <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="calendar-check" class="w-4 h-4"></i></span>
                                    <input type="datetime-local" class="input input-bordered join-item flex-1 w-full" id="waktu_selesai" name="waktu_selesai"
                                        value="<?= esc(isset($meeting['tanggal'], $meeting['waktu_selesai'])
                                            ? $meeting['tanggal'] . 'T' . $meeting['waktu_selesai']
                                            : '') ?>" step="60" required />
                                </div>
                            </div>

                            <div class="col-span-12">
                                <div class="text-error text-xs hidden" id="waktu-rapat-error">
                                    Waktu selesai harus setelah waktu mulai pada tanggal yang sama.
                                </div>
                            </div>

                            <div class="col-span-12">
                                <label class="label-text font-bold text-sm mb-1 block" for="keterangan">Keterangan / Agenda</label>
                                <textarea class="textarea textarea-bordered w-full" id="keterangan" name="keterangan" rows="4"
                                    placeholder="Uraian singkat agenda rapat..."><?= esc($meeting['keterangan'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Publikasi & Materi</div>

                        <div class="grid grid-cols-12 gap-3">
                            <div class="col-span-12">
                                <label class="label-text font-bold text-sm mb-1 block" for="materi_url">
                                    Link Materi Rapat <span class="text-base-content/60 font-normal">(Opsional)</span>
                                    <span class="badge badge-primary badge-xs ml-1">QR Code</span>
                                </label>
                                <div class="join w-full">
                                    <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="qr-code" class="w-4 h-4"></i></span>
                                    <input type="url" class="input input-bordered join-item flex-1 w-full" id="materi_url" name="materi_url"
                                        value="<?= esc($meeting['materi_url'] ?? '') ?>"
                                        placeholder="https://drive.google.com/... atau link dokumen lainnya" />
                                </div>
                            </div>

                            <div class="col-span-12">
                                <label class="label-text font-bold text-sm mb-1 block" for="stream_url">
                                    Link Live Streaming / Arsip Video <span class="text-base-content/60 font-normal">(Opsional)</span>
                                </label>
                                <div class="join w-full">
                                    <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="square-play" class="w-4 h-4"></i></span>
                                    <input type="url" class="input input-bordered join-item flex-1 w-full" id="stream_url" name="stream_url"
                                        value="<?= esc($meeting['stream_url'] ?? '') ?>"
                                        placeholder="https://youtube.com/live/... atau link streaming lainnya" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
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

                            <div class="settings-subgroup">
                                <label class="label-text font-bold text-sm mb-1 block">Jenis Rapat</label>

                                <div class="option-group two-col">
                                    <label class="choice-option" for="jenis-reguler">
                                        <span class="choice-option-body has-desc">
                                            <input type="radio" name="jenis" id="jenis-reguler" value="reguler"
                                                class="radio radio-primary radio-xs"
                                                <?= $jenis === 'reguler' ? 'checked' : '' ?> />
                                            <span class="choice-copy">
                                                <span class="choice-title">Rapat Reguler</span>
                                                <span class="choice-desc">Rapat resmi yang terencana atau rutin</span>
                                            </span>
                                        </span>
                                    </label>

                                    <label class="choice-option" for="jenis-insidental">
                                        <span class="choice-option-body has-desc">
                                            <input type="radio" name="jenis" id="jenis-insidental" value="insidental"
                                                class="radio radio-primary radio-xs"
                                                <?= $jenis === 'insidental' ? 'checked' : '' ?> />
                                            <span class="choice-copy">
                                                <span class="choice-title">Rapat Insidental</span>
                                                <span class="choice-desc">Rapat resmi di luar rencana rutin atau bersifat mendesak</span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="settings-subgroup">
                                <div class="visibility-row">
                                    <label class="visibility-title" for="is_publik">Publikasi</label>
                                    <div class="visibility-control">
                                        <input class="toggle toggle-primary" type="checkbox" role="switch"
                                            id="is_publik" name="is_publik" value="1"
                                            <?= ($meeting === null || ($meeting['is_publik'] ?? 1)) ? 'checked' : '' ?> />
                                        <label class="visibility-label ml-2 cursor-pointer" for="is_publik">
                                            <span id="publik-label"><?= ($meeting === null || ($meeting['is_publik'] ?? 1)) ? 'Publik' : 'Internal' ?></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">Peserta & WA</div>

                            <div class="flex items-center justify-between gap-2 mb-2">
                                <label class="label-text font-bold text-sm mb-0">Peserta Rapat</label>
                                <span class="badge badge-primary badge-sm" id="target-selected-count">0 dipilih</span>
                             </div>

                             <div class="target-picker border border-base-300 rounded overflow-hidden">
                                 <div class="target-picker-head flex items-center justify-between px-3 py-1.5 border-b border-base-300 bg-base-200">
                                     <span class="text-xs font-bold text-base-content/60 uppercase">Kelompok Peserta</span>
                                     <span class="target-picker-meta">
                                         <span class="badge badge-neutral badge-sm rounded-full" id="target-visible-count"><?= count($unit_rapat_list) ?></span>
                                     </span>
                                 </div>
                                 <div class="target-picker-search p-2 border-b border-base-300 bg-base-100">
                                     <div class="relative">
                                         <span class="absolute left-0 top-1/2 -translate-y-1/2 pl-3 text-base-content/40">
                                             <i data-lucide="search" class="w-4 h-4"></i>
                                         </span>
                                         <input type="text" class="input input-sm input-bordered w-full pl-10"
                                             id="target-search" placeholder="Cari kelompok peserta..." autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="target-list" id="target-list">
                                 <?php
                                 $targetUnitIds = $meeting['target_unit_ids'] ?? [];
                                 foreach ($unit_rapat_list as $unit):
                                     $unitId = (int) $unit['id'];
                                     $unitName = $unit['nama'];
                                     $activeMemberCount = (int) ($unit['active_member_count'] ?? 0);
                                     $isUnavailable = $activeMemberCount <= 0;
                                     $checked = (!$isUnavailable && in_array($unitId, $targetUnitIds, true)) ? 'checked' : '';
                                     $selectedClass = $checked ? ' is-selected bg-primary/10 text-primary font-semibold' : ' hover:bg-base-200';
                                     $disabledClass = $isUnavailable ? ' is-disabled opacity-60' : '';
                                     $targetId = 'unit-rapat-' . $unitId;
                                 ?>
                                     <label class="target-option flex items-center gap-2 px-3 py-1.5 border-b border-base-300 mb-0 cursor-pointer<?= $selectedClass ?><?= $disabledClass ?>" for="<?= esc($targetId, 'attr') ?>"
                                         data-name="<?= esc(strtolower($unitName), 'attr') ?>"
                                         data-member-count="<?= $activeMemberCount ?>">
                                         <input class="checkbox checkbox-primary checkbox-xs" type="checkbox" name="target_unit_rapat[]"
                                             value="<?= $unitId ?>" data-name="<?= esc($unitName, 'attr') ?>"
                                             id="<?= esc($targetId, 'attr') ?>" <?= $checked ?> <?= $isUnavailable ? 'disabled' : '' ?> />
                                         <span class="flex-1 min-w-0 truncate"><?= esc($unitName) ?></span>
                                         <?php if ($isUnavailable): ?>
                                             <span class="badge badge-warning badge-xs">0 anggota</span>
                                         <?php endif; ?>
                                     </label>
                                 <?php endforeach; ?>
                                     <div class="text-base-content/50 text-center py-3 hidden" id="target-empty">
                                         <small>Kelompok peserta tidak ditemukan.</small>
                                     </div>
                                 </div>
                             </div>
                             <div class="text-error text-xs mt-2 hidden" id="target-peserta-error">
                                 Pilih minimal satu kelompok peserta yang memiliki anggota aktif.
                             </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions-sticky">
        <a href="<?= base_url('admin/jadwal') ?>" class="btn btn-outline sm:btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary sm:btn-sm">
            <i data-lucide="calendar-check" class="w-4 h-4"></i>
            <span class="sm:hidden">Simpan</span>
            <span class="hidden sm:inline">
                <?= $meeting ? 'Simpan Perubahan' : 'Simpan Jadwal' ?>
            </span>
        </button>
    </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script {csp-script-nonce}>
    (function() {
    const initScheduleForm = function() {
        const form = document.querySelector('.schedule-form');
        if (!form || form.dataset.formBootstrapped === '1') return;
        form.dataset.formBootstrapped = '1';
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

        const waktuMulaiInput = document.getElementById('waktu_mulai');
        const waktuSelesaiInput = document.getElementById('waktu_selesai');
        const waktuError = document.getElementById('waktu-rapat-error');

        const syncTimeValidity = function() {
            if (!waktuMulaiInput || !waktuSelesaiInput) return true;

            const start = waktuMulaiInput.value ? new Date(waktuMulaiInput.value) : null;
            const end = waktuSelesaiInput.value ? new Date(waktuSelesaiInput.value) : null;
            if (!start || !end) {
                waktuMulaiInput.classList.remove('input-error');
                waktuSelesaiInput.classList.remove('input-error');
                waktuSelesaiInput.setCustomValidity('');
                if (waktuError) waktuError.classList.add('hidden');
                return true;
            }

            const valid = !!(start && end && end > start && waktuMulaiInput.value.slice(0, 10) === waktuSelesaiInput.value.slice(0, 10));

            waktuMulaiInput.classList.toggle('input-error', !valid && !!waktuMulaiInput.value);
            waktuSelesaiInput.classList.toggle('input-error', !valid && !!waktuSelesaiInput.value);
            if (waktuError) waktuError.classList.toggle('hidden', valid || !waktuMulaiInput.value || !waktuSelesaiInput.value);

            waktuSelesaiInput.setCustomValidity(valid ? '' : 'Waktu selesai harus setelah waktu mulai pada tanggal yang sama.');

            return valid;
        };

        waktuMulaiInput?.addEventListener('change', syncTimeValidity);
        waktuSelesaiInput?.addEventListener('change', syncTimeValidity);

        const targetInputs = Array.from(document.querySelectorAll('.target-option input[type="checkbox"]'));
        const targetOptions = Array.from(document.querySelectorAll('.target-option'));
        const targetSearch = document.getElementById('target-search');
        const targetSelectedCount = document.getElementById('target-selected-count');
        const targetVisibleCount = document.getElementById('target-visible-count');
        const targetEmpty = document.getElementById('target-empty');
        const targetError = document.getElementById('target-peserta-error');

        const syncTargetVisual = function(input) {
            const option = input.closest('.target-option');
            if (!option) return;

            option.classList.toggle('is-selected', input.checked);
            option.classList.toggle('bg-primary/10', input.checked);
            option.classList.toggle('text-primary', input.checked);
            option.classList.toggle('font-semibold', input.checked);
        };

        const syncTargetCount = function() {
            const count = targetInputs.filter(function(input) {
                return input.checked && !input.disabled;
            }).length;

            if (targetSelectedCount) {
                targetSelectedCount.textContent = count + ' dipilih';
            }

            return count;
        };

        const syncTargetValidity = function() {
            const count = syncTargetCount();
            const valid = count > 0;

            if (targetError) targetError.classList.toggle('hidden', valid);
            targetInputs.forEach(function(input) {
                if (!input.disabled) input.classList.toggle('checkbox-error', !valid);
            });

            return valid;
        };

        targetInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                syncTargetVisual(this);
                syncTargetValidity();
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

        form?.addEventListener('submit', function(event) {
            const timeValid = syncTimeValidity();
            const targetValid = syncTargetValidity();

            if (!timeValid || !targetValid) {
                event.preventDefault();
                if (!timeValid) {
                    waktuSelesaiInput?.focus();
                } else {
                    targetInputs.find(function(input) { return !input.disabled; })?.focus();
                }
            }
        });

        syncTimeValidity();
        syncTargetCount();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScheduleForm, { once: true });
    } else {
        initScheduleForm();
    }
    })();
</script>
<?= $this->endSection() ?>
