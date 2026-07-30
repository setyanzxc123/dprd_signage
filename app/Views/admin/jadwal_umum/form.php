<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isEdit = is_array($schedule);
$otherLocation = trim((string) ($schedule['lokasi_lainnya'] ?? ''));
$locationMode = $otherLocation !== '' ? 'lainnya' : 'ruangan';
$targetUnitIds = array_map('intval', $schedule['target_unit_ids'] ?? []);
?>

<div class="page-header">
    <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    <p class="page-subtitle">
        <?= $isEdit
            ? 'Perbarui kegiatan non-Banmus beserta peserta dan publikasinya.'
            : 'Catat rapat insidental, audiensi, kunjungan, atau kegiatan non-Banmus lainnya.' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="post" class="schedule-form" data-require-targets="false" data-turbo="true">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div role="alert" class="alert alert-error mb-3 shadow-sm">
            <i data-lucide="triangle-alert" class="h-4 w-4"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="card card-border max-w-5xl bg-base-100 shadow-sm">
        <div class="card-body gap-5 p-4 sm:p-5">
            <fieldset class="fieldset">
                <legend class="fieldset-legend">Data agenda</legend>

                <label class="label" for="judul">Judul <span class="text-error">*</span></label>
                <input class="input w-full" id="judul" name="judul" type="text" maxlength="255" required
                    value="<?= esc($schedule['judul'] ?? '') ?>"
                    placeholder="Contoh: Audiensi Forum Pemuda bersama Komisi I" />

                <label class="label mt-2" for="pihak_eksternal">Pihak eksternal</label>
                <input class="input w-full" id="pihak_eksternal" name="pihak_eksternal" type="text" maxlength="255"
                    value="<?= esc($schedule['pihak_eksternal'] ?? '') ?>"
                    placeholder="Opsional: nama masyarakat, organisasi, atau instansi luar" />
                <p class="label">Boleh dikosongkan untuk kegiatan yang tidak melibatkan pihak luar.</p>

                <label class="label mt-2" for="keterangan">Keterangan</label>
                <textarea class="textarea w-full" id="keterangan" name="keterangan" rows="4" maxlength="5000"
                    placeholder="Tambahkan informasi operasional bila diperlukan."><?= esc($schedule['keterangan'] ?? '') ?></textarea>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Pelaksanaan</legend>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="label" for="tanggal">Tanggal <span class="text-error">*</span></label>
                        <input class="input w-full" id="tanggal" name="tanggal" type="date" required
                            value="<?= esc($schedule['tanggal'] ?? date('Y-m-d')) ?>" />
                    </div>
                    <div>
                        <label class="label" for="waktu_mulai">Jam mulai</label>
                        <input class="input w-full" id="waktu_mulai" name="waktu_mulai" type="time" step="60"
                            value="<?= esc(substr((string) ($schedule['waktu_mulai'] ?? ''), 0, 5)) ?>" />
                    </div>
                    <div>
                        <label class="label" for="waktu_selesai">Jam selesai</label>
                        <input class="input w-full" id="waktu_selesai" name="waktu_selesai" type="time" step="60"
                            value="<?= esc(substr((string) ($schedule['waktu_selesai'] ?? ''), 0, 5)) ?>" />
                    </div>
                </div>
                <p class="label">Kosongkan kedua jam untuk kegiatan sepanjang hari. Pemakaian ruangan DPRD memerlukan jam lengkap.</p>
                <p class="hidden text-xs text-error" id="waktu-rapat-error">Jam selesai harus setelah jam mulai.</p>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Lokasi <span class="text-error">*</span></legend>
                <div class="flex flex-wrap gap-4">
                    <label class="label cursor-pointer gap-2" for="lokasi-ruangan">
                        <input class="radio radio-sm" id="lokasi-ruangan" name="lokasi_mode" type="radio"
                            value="ruangan" <?= $locationMode === 'ruangan' ? 'checked' : '' ?> />
                        <span>Ruangan DPRD</span>
                    </label>
                    <label class="label cursor-pointer gap-2" for="lokasi-lainnya">
                        <input class="radio radio-sm" id="lokasi-lainnya" name="lokasi_mode" type="radio"
                            value="lainnya" <?= $locationMode === 'lainnya' ? 'checked' : '' ?> />
                        <span>Lokasi lainnya</span>
                    </label>
                </div>
                <div id="ruangan-panel">
                    <select class="select w-full" id="ruangan_id" name="ruangan_id">
                        <option value="">Pilih ruangan</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= (int) $room['id'] ?>"
                                <?= (int) ($schedule['ruangan_id'] ?? 0) === (int) $room['id'] ? 'selected' : '' ?>>
                                <?= esc($room['name']) ?>
                                <?= isset($room['kapasitas']) ? ' (kapasitas ' . (int) $room['kapasitas'] . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="lokasi-lainnya-panel" hidden>
                    <input class="input w-full" id="lokasi_lainnya" name="lokasi_lainnya" type="text" maxlength="255"
                        value="<?= esc($otherLocation) ?>" placeholder="Masukkan nama lokasi" />
                </div>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Kelompok peserta</legend>
                <div class="flex items-center gap-2">
                    <input class="input input-sm w-full" id="target-search" type="search"
                        placeholder="Cari kelompok peserta..." autocomplete="off" />
                    <span class="badge badge-ghost whitespace-nowrap" id="target-selected-count">0 dipilih</span>
                </div>
                <div class="grid max-h-64 grid-cols-1 overflow-y-auto rounded-box border border-base-300 sm:grid-cols-2"
                    id="target-list">
                    <?php foreach ($unit_rapat_list as $unit):
                        $unitId = (int) $unit['id'];
                        $memberCount = (int) ($unit['active_member_count'] ?? 0);
                        $unavailable = $memberCount <= 0;
                        $targetId = 'unit-rapat-' . $unitId;
                    ?>
                        <label class="target-option flex cursor-pointer items-center gap-2 border-b border-base-300 px-3 py-2 text-sm sm:border-r"
                            for="<?= esc($targetId, 'attr') ?>"
                            data-name="<?= esc(strtolower((string) $unit['nama']), 'attr') ?>">
                            <input class="checkbox checkbox-sm" id="<?= esc($targetId, 'attr') ?>"
                                name="target_unit_rapat[]" type="checkbox" value="<?= $unitId ?>"
                                <?= in_array($unitId, $targetUnitIds, true) ? 'checked' : '' ?>
                                <?= $unavailable ? 'disabled' : '' ?> />
                            <span class="min-w-0 flex-1 truncate"><?= esc($unit['nama']) ?></span>
                            <?php if ($unavailable): ?>
                                <span class="badge badge-warning badge-sm">0 anggota</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                    <div class="col-span-full hidden py-3 text-center text-sm text-base-content/55" id="target-empty">
                        Kelompok peserta tidak ditemukan.
                    </div>
                </div>
                <p class="label">Opsional. Jadwal akan menjadi “Jadwal Saya” bagi anggota kelompok yang dipilih setelah fase integrasi portal.</p>
                <p class="hidden text-xs text-error" id="target-peserta-error">Kelompok peserta tidak valid.</p>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Publikasi</legend>
                <label class="flex cursor-pointer items-start gap-3 rounded-box border border-base-300 bg-base-200 p-3"
                    for="is_publik">
                    <input class="checkbox checkbox-sm mt-0.5" id="is_publik" name="is_publik" type="checkbox"
                        value="1" <?= (int) ($schedule['is_publik'] ?? 0) === 1 ? 'checked' : '' ?> />
                    <span>
                        <span class="block text-sm font-bold">Tampilkan kepada publik</span>
                        <span class="mt-1 block text-xs text-base-content/55" id="publik-label">
                            <?= (int) ($schedule['is_publik'] ?? 0) === 1
                                ? 'Agenda dapat tampil pada kanal publik setelah fase integrasi.'
                                : 'Default internal, hanya terlihat oleh pengguna berwenang.' ?>
                        </span>
                    </span>
                </label>
            </fieldset>
        </div>
    </div>

    <div class="mt-4 flex gap-2">
        <a href="<?= base_url('admin/jadwal-umum') ?>" class="btn btn-outline flex-1 sm:btn-sm sm:flex-none">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Batal
        </a>
        <button type="submit" class="btn btn-primary flex-1 sm:btn-sm sm:flex-none">
            <i data-lucide="check" class="h-4 w-4"></i>
            <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Jadwal' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
