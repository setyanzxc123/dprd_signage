<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isEdit = is_array($schedule);
$otherLocation = trim((string) ($schedule['lokasi_lainnya'] ?? ''));
$locationMode = $otherLocation !== '' ? 'lainnya' : 'ruangan';
$targetUnitIds = array_map('intval', $schedule['target_unit_ids'] ?? []);
?>

<div class="mb-5">
    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?= esc($pageTitle) ?></h1>
</div>

<form action="<?= esc($action_url) ?>" method="post" enctype="multipart/form-data" class="schedule-form min-w-0 max-w-full" data-require-targets="false">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300 flex items-center gap-3">
            <i data-lucide="triangle-alert" class="size-5 shrink-0 text-rose-500"></i>
            <span class="text-sm font-medium"><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 p-5 sm:p-6 space-y-6 max-w-5xl">
        <!-- Informasi Agenda Dasar -->
        <div class="space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i data-lucide="file-text" class="size-4 text-emerald-500"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Data Agenda</h2>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="judul">
                    Judul <span class="text-rose-500">*</span>
                </label>
                <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="judul" name="judul" type="text" maxlength="255" required
                    value="<?= esc($schedule['judul'] ?? '') ?>"
                    placeholder="Contoh: Audiensi Forum Pemuda bersama Komisi I" />
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="pihak_eksternal">
                    Pihak Eksternal
                </label>
                <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="pihak_eksternal" name="pihak_eksternal" type="text" maxlength="255"
                    value="<?= esc($schedule['pihak_eksternal'] ?? '') ?>"
                    placeholder="Opsional: nama masyarakat, organisasi, atau instansi luar" />
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Boleh dikosongkan untuk kegiatan internal yang tidak melibatkan pihak luar.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="keterangan">
                    Keterangan Operasional
                </label>
                <textarea class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 resize-none min-h-24" id="keterangan" name="keterangan" rows="4" maxlength="5000"
                    placeholder="Tambahkan informasi operasional bila diperlukan."><?= esc($schedule['keterangan'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Pelaksanaan & Waktu -->
        <div class="space-y-4 pt-2">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i data-lucide="calendar" class="size-4 text-emerald-500"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Pelaksanaan &amp; Waktu</h2>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="tanggal">
                        Tanggal <span class="text-rose-500">*</span>
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 font-semibold" id="tanggal" name="tanggal" type="date" required
                        value="<?= esc($schedule['tanggal'] ?? date('Y-m-d')) ?>" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="waktu_mulai">
                        Jam Mulai
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="waktu_mulai" name="waktu_mulai" type="time" step="60"
                        value="<?= esc(substr((string) ($schedule['waktu_mulai'] ?? ''), 0, 5)) ?>" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="waktu_selesai">
                        Jam Selesai
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="waktu_selesai" name="waktu_selesai" type="time" step="60"
                        value="<?= esc(substr((string) ($schedule['waktu_selesai'] ?? ''), 0, 5)) ?>" />
                </div>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">Kosongkan kedua jam untuk kegiatan sepanjang hari. Pemakaian ruangan DPRD memerlukan jam lengkap.</p>
            <p class="hidden text-xs font-semibold text-rose-600 dark:text-rose-400" id="waktu-rapat-error">Jam selesai harus setelah jam mulai pada tanggal yang sama.</p>
        </div>

        <!-- Lokasi -->
        <div class="space-y-4 pt-2">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i data-lucide="map-pin" class="size-4 text-emerald-500"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Lokasi <span class="text-rose-500">*</span></h2>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:max-w-md">
                <label class="flex items-center gap-x-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer" for="lokasi-ruangan">
                    <input class="size-4 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700" id="lokasi-ruangan" name="lokasi_mode" type="radio"
                        value="ruangan" <?= $locationMode === 'ruangan' ? 'checked' : '' ?> />
                    <div class="leading-tight">
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Ruangan DPRD</span>
                        <span class="block text-[10px] text-slate-400">Gedung / Ruang Rapat</span>
                    </div>
                </label>
                <label class="flex items-center gap-x-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer" for="lokasi-lainnya">
                    <input class="size-4 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700" id="lokasi-lainnya" name="lokasi_mode" type="radio"
                        value="lainnya" <?= $locationMode === 'lainnya' ? 'checked' : '' ?> />
                    <div class="leading-tight">
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Lokasi Lainnya</span>
                        <span class="block text-[10px] text-slate-400">Luar kantor / Hotel</span>
                    </div>
                </label>
            </div>

            <div id="ruangan-panel">
                <select class="py-2.5 px-3.5 pe-9 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="ruangan_id" name="ruangan_id">
                    <option value="">Pilih ruangan rapat</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= (int) $room['id'] ?>"
                            <?= (int) ($schedule['ruangan_id'] ?? 0) === (int) $room['id'] ? 'selected' : '' ?>>
                            <?= esc($room['name']) ?>
                            <?= isset($room['kapasitas']) ? ' (kapasitas ' . (int) $room['kapasitas'] . ' orang)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="lokasi-lainnya-panel" hidden>
                <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="lokasi_lainnya" name="lokasi_lainnya" type="text" maxlength="255"
                    value="<?= esc($otherLocation) ?>" placeholder="Masukkan nama lokasi (misal: Hotel Santika Palu)" />
            </div>
        </div>

        <!-- Kelompok Peserta -->
        <div class="space-y-4 pt-2">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i data-lucide="users" class="size-4 text-emerald-500"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Kelompok Peserta</h2>
            </div>

            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <input class="py-2 px-3 ps-9 block w-full border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="target-search" type="search"
                        placeholder="Cari kelompok peserta..." autocomplete="off" aria-label="Cari kelompok peserta" />
                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3">
                        <i data-lucide="search" class="size-3.5 text-slate-400"></i>
                    </div>
                </div>
                <span class="py-1 px-2.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 shrink-0" id="target-selected-count">0 dipilih</span>
            </div>

            <div class="grid max-h-64 w-full grid-cols-1 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 sm:grid-cols-2 divide-y divide-slate-100 dark:divide-slate-800 sm:divide-y-0"
                id="target-list">
                <?php foreach ($unit_rapat_list as $unit):
                    $unitId = (int) $unit['id'];
                    $memberCount = (int) ($unit['active_member_count'] ?? 0);
                    $unavailable = $memberCount <= 0;
                    $targetId = 'unit-rapat-' . $unitId;
                ?>
                    <label class="target-option flex cursor-pointer items-center gap-2.5 p-3 text-xs hover:bg-slate-50 dark:hover:bg-slate-800/50 transition border-b sm:border-r border-slate-100 dark:border-slate-800/60"
                        for="<?= esc($targetId, 'attr') ?>"
                        data-name="<?= esc(strtolower((string) $unit['nama']), 'attr') ?>">
                        <input class="size-4 text-emerald-600 rounded focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700" id="<?= esc($targetId, 'attr') ?>"
                            name="target_unit_rapat[]" type="checkbox" value="<?= $unitId ?>"
                            <?= in_array($unitId, $targetUnitIds, true) ? 'checked' : '' ?>
                            <?= $unavailable ? 'disabled' : '' ?> />
                        <span class="min-w-0 flex-1 truncate text-slate-800 dark:text-slate-200 font-medium"><?= esc($unit['nama']) ?></span>
                        <?php if ($unavailable): ?>
                            <span class="py-0.5 px-1.5 rounded text-[10px] font-medium bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">0 anggota</span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
                <div class="col-span-full hidden py-4 text-center text-xs text-slate-400 dark:text-slate-500" id="target-empty">
                    Kelompok peserta tidak ditemukan.
                </div>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">Opsional. Jadwal akan menjadi agenda prioritas bagi anggota kelompok yang dipilih.</p>
            <p class="hidden text-xs font-semibold text-rose-600 dark:text-rose-400" id="target-peserta-error">Kelompok peserta tidak valid.</p>
        </div>

        <!-- Bahan dan Streaming -->
        <div class="space-y-4 pt-2">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i data-lucide="share-2" class="size-4 text-emerald-500"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Bahan &amp; Live Streaming</h2>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="materi_url">
                        Tautan Bahan Rapat
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="materi_url" name="materi_url" type="url"
                        value="<?= esc($schedule['materi_url'] ?? '') ?>" placeholder="https://..." />
                    <div class="mt-2">
                        <label class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-1" for="materi_akses">Akses Bahan</label>
                        <select class="py-2 px-3 pe-9 block w-full border border-slate-200 rounded-lg text-xs focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="materi_akses" name="materi_akses">
                            <?php foreach (['peserta' => 'Peserta rapat saja', 'anggota' => 'Seluruh anggota DPRD', 'publik' => 'Publik'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($schedule['materi_akses'] ?? 'peserta') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="stream_url">
                        Tautan Live / Video
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="stream_url" name="stream_url" type="url"
                        value="<?= esc($schedule['stream_url'] ?? '') ?>" placeholder="https://..." />
                    <div class="mt-2">
                        <label class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-1" for="stream_akses">Akses Streaming</label>
                        <select class="py-2 px-3 pe-9 block w-full border border-slate-200 rounded-lg text-xs focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="stream_akses" name="stream_akses">
                            <?php foreach (['anggota' => 'Seluruh anggota DPRD', 'peserta' => 'Peserta rapat saja', 'publik' => 'Publik'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($schedule['stream_akses'] ?? 'anggota') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Undangan Rapat -->
        <div class="space-y-4 pt-2">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i data-lucide="file-check-2" class="size-4 text-emerald-500"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Undangan Rapat (PDF)</h2>
            </div>

            <input class="block w-full border border-slate-200 shadow-xs rounded-xl text-sm focus:z-10 focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-400 file:bg-slate-50 file:border-0 file:me-4 file:py-2.5 file:px-4 dark:file:bg-slate-800 dark:file:text-slate-400" id="undangan_file" name="undangan_file" type="file"
                accept="application/pdf,.pdf" />
            <p class="text-[11px] text-slate-500 dark:text-slate-400">PDF maksimal 10 MB. Undangan hanya dapat diakses oleh anggota yang sudah login.</p>

            <?php if (! empty($schedule['undangan_file'])): ?>
                <div class="flex items-center gap-2 p-3 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300">
                    <i data-lucide="file-check-2" class="size-4 shrink-0"></i>
                    <span class="text-xs font-semibold truncate flex-1"><?= esc($schedule['undangan_nama_asli'] ?: 'undangan-rapat.pdf') ?></span>
                    <label class="flex items-center gap-1.5 text-xs text-rose-600 dark:text-rose-400 cursor-pointer" for="hapus_undangan">
                        <input class="size-3.5 text-rose-600 rounded focus:ring-rose-500" id="hapus_undangan" name="hapus_undangan" type="checkbox" value="1" />
                        <span>Hapus file</span>
                    </label>
                </div>
            <?php endif; ?>
        </div>

        <!-- Publikasi -->
        <div class="space-y-4 pt-2">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i data-lucide="eye" class="size-4 text-emerald-500"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Akses Publikasi</h2>
            </div>

            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30 p-4"
                for="is_publik">
                <input class="size-4 mt-0.5 text-emerald-600 rounded focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700" id="is_publik" name="is_publik" type="checkbox"
                    value="1" <?= (int) ($schedule['is_publik'] ?? 0) === 1 ? 'checked' : '' ?> />
                <span class="min-w-0">
                    <span class="block text-sm font-bold text-slate-900 dark:text-white">Tampilkan kepada publik</span>
                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400" id="publik-label">
                        <?= (int) ($schedule['is_publik'] ?? 0) === 1
                            ? 'Agenda dapat tampil pada kanal publik setelah fase integrasi.'
                            : 'Default internal, hanya terlihat oleh pengguna berwenang.' ?>
                    </span>
                </span>
            </label>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3 max-w-5xl">
        <a href="<?= base_url('admin/jadwal-umum') ?>" class="py-2.5 px-4 inline-flex items-center text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition">
            Batal
        </a>
        <button type="submit" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer">
            <i data-lucide="check" class="size-4"></i>
            <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Jadwal' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
