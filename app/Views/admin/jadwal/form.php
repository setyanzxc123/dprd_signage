<?= $this->extend('admin/layouts/main') ?>


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
