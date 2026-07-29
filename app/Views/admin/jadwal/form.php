<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isEdit = is_array($meeting);
$lokasiLainnya = trim((string) ($meeting['lokasi_lainnya'] ?? ''));
$lokasiMode = $lokasiLainnya !== '' ? 'lainnya' : 'ruangan';
$targetUnitIds = array_map('intval', $meeting['target_unit_ids'] ?? []);
?>

<div class="page-header">
    <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    <p class="page-subtitle">
        <?= $isEdit ? 'Perbarui data agenda insidental internal.' : 'Tambahkan agenda internal di luar SK Banmus.' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="post" class="schedule-form" data-turbo="true">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div role="alert" class="alert alert-error mb-3 shadow-sm">
            <i data-lucide="triangle-alert" class="h-4 w-4"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="form-card max-w-5xl p-[18px]">
        <div class="form-section-title mb-3 pb-2">Data Agenda</div>

        <div class="grid grid-cols-12 gap-3">
            <div class="col-span-12">
                <label class="label-text mb-1 block text-sm font-bold" for="judul">
                    Judul <span class="text-error">*</span>
                </label>
                <input class="input w-full" id="judul" name="judul" type="text" maxlength="255" required
                    value="<?= esc($meeting['judul'] ?? '') ?>"
                    placeholder="Contoh: Rapat koordinasi pimpinan" />
            </div>

            <div class="col-span-12 sm:col-span-4">
                <label class="label-text mb-1 block text-sm font-bold" for="tanggal">
                    Tanggal <span class="text-error">*</span>
                </label>
                <input class="input w-full" id="tanggal" name="tanggal" type="date" required
                    value="<?= esc($meeting['tanggal'] ?? date('Y-m-d')) ?>" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <label class="label-text mb-1 block text-sm font-bold" for="waktu_mulai">
                    Jam mulai <span class="text-error">*</span>
                </label>
                <input class="input w-full" id="waktu_mulai" name="waktu_mulai" type="time" step="60" required
                    value="<?= esc(substr((string) ($meeting['waktu_mulai'] ?? ''), 0, 5)) ?>" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <label class="label-text mb-1 block text-sm font-bold" for="waktu_selesai">
                    Jam selesai <span class="text-error">*</span>
                </label>
                <input class="input w-full" id="waktu_selesai" name="waktu_selesai" type="time" step="60" required
                    value="<?= esc(substr((string) ($meeting['waktu_selesai'] ?? ''), 0, 5)) ?>" />
            </div>

            <div class="col-span-12">
                <p class="hidden text-xs text-error" id="waktu-rapat-error">
                    Jam selesai harus setelah jam mulai.
                </p>
            </div>

            <fieldset class="fieldset col-span-12">
                <legend class="fieldset-legend">
                    Lokasi <span class="text-error">*</span>
                </legend>

                <div class="flex flex-wrap gap-4">
                    <label class="label cursor-pointer gap-2" for="lokasi-ruangan">
                        <input class="radio radio-sm" id="lokasi-ruangan" name="lokasi_mode" type="radio"
                            value="ruangan" <?= $lokasiMode === 'ruangan' ? 'checked' : '' ?> />
                        <span>Ruangan DPRD</span>
                    </label>
                    <label class="label cursor-pointer gap-2" for="lokasi-lainnya">
                        <input class="radio radio-sm" id="lokasi-lainnya" name="lokasi_mode" type="radio"
                            value="lainnya" <?= $lokasiMode === 'lainnya' ? 'checked' : '' ?> />
                        <span>Lokasi lainnya</span>
                    </label>
                </div>

                <div id="ruangan-panel">
                    <select class="select w-full" id="ruangan_id" name="ruangan_id">
                        <option value="">Pilih ruangan</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= (int) $room['id'] ?>"
                                <?= (int) ($meeting['ruangan_id'] ?? 0) === (int) $room['id'] ? 'selected' : '' ?>>
                                <?= esc($room['name']) ?>
                                <?= isset($room['kapasitas']) ? ' (kapasitas ' . (int) $room['kapasitas'] . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="lokasi-lainnya-panel" hidden>
                    <input class="input w-full" id="lokasi_lainnya" name="lokasi_lainnya" type="text" maxlength="255"
                        value="<?= esc($lokasiLainnya) ?>" placeholder="Masukkan nama lokasi" />
                </div>
            </fieldset>

            <fieldset class="fieldset col-span-12">
                <legend class="fieldset-legend">
                    Unit/Peserta <span class="text-error">*</span>
                </legend>

                <input class="input input-sm w-full" id="target-search" type="search"
                    placeholder="Cari kelompok peserta..." autocomplete="off" />

                <div class="grid max-h-64 grid-cols-1 overflow-y-auto rounded-box border border-base-300 sm:grid-cols-2"
                    id="target-list">
                    <?php foreach ($unit_rapat_list as $unit):
                        $unitId = (int) $unit['id'];
                        $activeMemberCount = (int) ($unit['active_member_count'] ?? 0);
                        $isUnavailable = $activeMemberCount <= 0;
                        $checked = ! $isUnavailable && in_array($unitId, $targetUnitIds, true);
                        $targetId = 'unit-rapat-' . $unitId;
                    ?>
                        <label class="target-option flex cursor-pointer items-center gap-2 border-b border-base-300 px-3 py-2 text-sm sm:border-r"
                            for="<?= esc($targetId, 'attr') ?>"
                            data-name="<?= esc(strtolower((string) $unit['nama']), 'attr') ?>">
                            <input class="checkbox checkbox-sm" id="<?= esc($targetId, 'attr') ?>"
                                name="target_unit_rapat[]" type="checkbox" value="<?= $unitId ?>"
                                <?= $checked ? 'checked' : '' ?> <?= $isUnavailable ? 'disabled' : '' ?> />
                            <span class="min-w-0 flex-1 truncate"><?= esc($unit['nama']) ?></span>
                            <?php if ($isUnavailable): ?>
                                <span class="badge badge-warning badge-sm">0 anggota</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                    <div class="col-span-full hidden py-3 text-center text-sm text-base-content/55" id="target-empty">
                        Kelompok peserta tidak ditemukan.
                    </div>
                </div>
                <p class="hidden text-xs text-error" id="target-peserta-error">
                    Pilih minimal satu kelompok peserta yang memiliki anggota aktif.
                </p>
            </fieldset>

            <div class="col-span-12">
                <label class="label-text mb-1 block text-sm font-bold" for="keterangan">Keterangan</label>
                <textarea class="textarea w-full" id="keterangan" name="keterangan" rows="4"
                    placeholder="Tambahkan keterangan bila diperlukan."><?= esc($meeting['keterangan'] ?? '') ?></textarea>
            </div>

            <div class="col-span-12">
                <label class="flex cursor-pointer items-start gap-3 rounded-box border border-base-300 bg-base-200 p-3"
                    for="is_publik">
                    <input class="checkbox checkbox-sm mt-0.5" id="is_publik" name="is_publik" type="checkbox"
                        value="1" <?= ($meeting['is_publik'] ?? 0) ? 'checked' : '' ?> />
                    <span>
                        <span class="block text-sm font-bold">Publikasikan agenda</span>
                        <span class="mt-1 block text-xs text-base-content/55" id="publik-label">
                            <?= ($meeting['is_publik'] ?? 0) ? 'Agenda dapat tampil pada kanal publik.' : 'Default internal, hanya terlihat oleh pengguna berwenang.' ?>
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <div class="mt-4 flex gap-2">
        <a href="<?= base_url('admin/jadwal') ?>" class="btn btn-outline flex-1 sm:btn-sm sm:flex-none">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Batal
        </a>
        <button type="submit" class="btn btn-primary flex-1 sm:btn-sm sm:flex-none">
            <i data-lucide="check" class="h-4 w-4"></i>
            <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Agenda' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
