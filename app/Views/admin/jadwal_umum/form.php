<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isEdit = is_array($schedule);
$otherLocation = trim((string) ($schedule['lokasi_lainnya'] ?? ''));
$locationMode = $otherLocation !== '' ? 'lainnya' : 'ruangan';
$targetUnitIds = array_map('intval', $schedule['target_unit_ids'] ?? []);
?>

<div class="mb-4">
    <h1 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white"><?= esc($pageTitle) ?></h1>
</div>

<form action="<?= esc($action_url) ?>" method="post" enctype="multipart/form-data" class="schedule-form min-w-0 w-full" data-require-targets="false">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300 flex items-center gap-2.5 text-xs font-medium" role="alert">
            <i data-lucide="triangle-alert" class="size-4 shrink-0 text-rose-500"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 p-4 sm:p-6 space-y-6 w-full">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 sm:pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar-days" class="size-4 text-blue-500"></i>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Jenis Agenda</span>
            </div>

            <div class="grid grid-cols-2 sm:inline-flex p-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 w-full sm:w-auto shrink-0" role="radiogroup" aria-label="Kategori Agenda">
                <label class="inline-flex items-center justify-center sm:justify-start gap-1.5 py-1.5 sm:py-1 px-3 rounded-lg text-xs font-semibold cursor-pointer transition text-slate-600 dark:text-slate-400 has-checked:bg-white has-checked:text-blue-600 has-checked:shadow-xs dark:has-checked:bg-slate-900 dark:has-checked:text-blue-400">
                    <input type="radio" name="jenis_agenda" value="rapat" id="jenis_agenda_rapat" class="sr-only" <?= ($schedule['jenis_agenda'] ?? 'rapat') === 'rapat' ? 'checked' : '' ?> required />
                    <i data-lucide="users" class="size-3.5"></i>
                    <span>Rapat / Audiensi</span>
                </label>
                <label class="inline-flex items-center justify-center sm:justify-start gap-1.5 py-1.5 sm:py-1 px-3 rounded-lg text-xs font-semibold cursor-pointer transition text-slate-600 dark:text-slate-400 has-checked:bg-white has-checked:text-purple-600 has-checked:shadow-xs dark:has-checked:bg-slate-900 dark:has-checked:text-purple-400">
                    <input type="radio" name="jenis_agenda" value="non_rapat" id="jenis_agenda_non_rapat" class="sr-only" <?= ($schedule['jenis_agenda'] ?? 'rapat') === 'non_rapat' ? 'checked' : '' ?> required />
                    <i data-lucide="calendar" class="size-3.5"></i>
                    <span>Kegiatan / Acara Umum</span>
                </label>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="judul">
                    Judul <span class="text-rose-500">*</span>
                </label>
                <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="judul" name="judul" type="text" maxlength="255" required
                    value="<?= esc($schedule['judul'] ?? '') ?>"
                    placeholder="Contoh: Audiensi Forum Pemuda bersama Komisi I" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="pihak_eksternal">
                        Pihak Eksternal <span class="text-[10px] font-normal text-slate-400 dark:text-slate-500">(opsional)</span>
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="pihak_eksternal" name="pihak_eksternal" type="text" maxlength="255"
                        value="<?= esc($schedule['pihak_eksternal'] ?? '') ?>"
                        placeholder="Masyarakat / instansi luar" />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="keterangan">
                        Keterangan <span class="text-[10px] font-normal text-slate-400 dark:text-slate-500">(opsional)</span>
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="keterangan" name="keterangan" type="text" maxlength="5000"
                        value="<?= esc($schedule['keterangan'] ?? '') ?>"
                        placeholder="Catatan tambahan" />
                </div>
            </div>
        </div>

        <div class="space-y-3 pt-1">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i data-lucide="clock" class="size-4 text-blue-500"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Pelaksanaan</h2>
            </div>

            <div id="rapat-waktu-grid" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="tanggal">
                        Tanggal <span class="text-rose-500">*</span>
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 font-medium" id="tanggal" name="tanggal" type="date"
                        value="<?= esc($schedule['tanggal'] ?? date('Y-m-d')) ?>" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="waktu_mulai">
                        Mulai (WITA)
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="waktu_mulai" name="waktu_mulai" type="time" step="60"
                        value="<?= esc(substr((string) ($schedule['waktu_mulai'] ?? ''), 0, 5)) ?>" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="waktu_selesai">
                        Selesai (WITA)
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="waktu_selesai" name="waktu_selesai" type="time" step="60"
                        value="<?= esc(substr((string) ($schedule['waktu_selesai'] ?? ''), 0, 5)) ?>" />
                </div>
            </div>

            <div id="non-rapat-waktu-grid" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="tanggal_mulai">
                        Tanggal Mulai <span class="text-rose-500">*</span>
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 font-medium" id="tanggal_mulai" name="tanggal_mulai" type="date"
                        value="<?= esc($schedule['tanggal_mulai'] ?? $schedule['tanggal'] ?? date('Y-m-d')) ?>" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="tanggal_selesai">
                        Tanggal Selesai <span class="text-[10px] font-normal text-slate-400 dark:text-slate-500">(opsional)</span>
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 font-medium" id="tanggal_selesai" name="tanggal_selesai" type="date"
                        value="<?= esc($schedule['tanggal_selesai'] ?? $schedule['tanggal'] ?? '') ?>" />
                </div>
            </div>

            <p class="hidden text-xs font-semibold text-rose-600 dark:text-rose-400" id="waktu-rapat-error" role="alert" aria-live="polite">Jam selesai harus setelah jam mulai pada tanggal yang sama.</p>
            <p id="rapat-waktu-desc" class="hidden"></p>
        </div>

        <div class="space-y-3 pt-1" id="lokasi-section">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <i data-lucide="map-pin" class="size-4 text-blue-500"></i>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        <span id="lokasi-heading">Lokasi</span> <span class="text-rose-500" id="lokasi-required-star">*</span>
                    </h2>
                </div>

                <div class="grid grid-cols-2 sm:inline-flex p-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 w-full sm:w-auto shrink-0" id="lokasi-mode-wrapper" role="radiogroup" aria-label="Mode Lokasi">
                    <label class="inline-flex items-center justify-center sm:justify-start py-1 sm:py-0.5 px-3 sm:px-2.5 rounded-md text-xs font-medium cursor-pointer transition text-slate-600 dark:text-slate-400 has-checked:bg-white has-checked:text-blue-600 has-checked:font-semibold has-checked:shadow-xs dark:has-checked:bg-slate-900 dark:has-checked:text-blue-400">
                        <input class="sr-only" id="lokasi-ruangan" name="lokasi_mode" type="radio" value="ruangan" <?= $locationMode === 'ruangan' ? 'checked' : '' ?> />
                        <span>Ruangan DPRD</span>
                    </label>
                    <label class="inline-flex items-center justify-center sm:justify-start py-1 sm:py-0.5 px-3 sm:px-2.5 rounded-md text-xs font-medium cursor-pointer transition text-slate-600 dark:text-slate-400 has-checked:bg-white has-checked:text-blue-600 has-checked:font-semibold has-checked:shadow-xs dark:has-checked:bg-slate-900 dark:has-checked:text-blue-400">
                        <input class="sr-only" id="lokasi-lainnya" name="lokasi_mode" type="radio" value="lainnya" <?= $locationMode === 'lainnya' ? 'checked' : '' ?> />
                        <span>Lokasi Lainnya</span>
                    </label>
                </div>
            </div>

            <div id="ruangan-panel">
                <label class="sr-only" for="ruangan_id">Pilih Ruangan</label>
                <select class="py-2.5 px-3.5 pe-9 block w-full border border-slate-200 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="ruangan_id" name="ruangan_id">
                    <option value="">Pilih ruangan rapat</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= (int) $room['id'] ?>"
                            <?= (int) ($schedule['ruangan_id'] ?? 0) === (int) $room['id'] ? 'selected' : '' ?>>
                            <?= esc($room['name']) ?>
                            <?= isset($room['kapasitas']) ? ' (' . (int) $room['kapasitas'] . ' orang)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="lokasi-lainnya-panel" hidden>
                <label class="sr-only" for="lokasi_lainnya">Nama Lokasi</label>
                <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="lokasi_lainnya" name="lokasi_lainnya" type="text" maxlength="255"
                    value="<?= esc($otherLocation) ?>" placeholder="Masukkan nama lokasi / gedung pelaksanaan..." />
            </div>
        </div>

        <div class="space-y-3 pt-1" id="kelompok-peserta-section">
            <div class="flex items-center justify-between gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="size-4 text-blue-500"></i>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Peserta</h2>
                </div>
                <span class="py-0.5 px-2.5 rounded-md text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400" id="target-selected-count">0 dipilih</span>
            </div>

            <div class="relative">
                <input class="py-2 px-3.5 ps-9 block w-full border border-slate-200 rounded-xl text-xs sm:text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="target-search" type="search"
                    placeholder="Cari kelompok peserta..." autocomplete="off" aria-label="Cari kelompok peserta" />
                <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3">
                    <i data-lucide="search" class="size-4 text-slate-400"></i>
                </div>
            </div>

            <div class="grid max-h-56 w-full grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-1.5 gap-1.5"
                id="target-list">
                <?php foreach ($unit_rapat_list as $unit):
                    $unitId = (int) $unit['id'];
                    $memberCount = (int) ($unit['active_member_count'] ?? 0);
                    $unavailable = $memberCount <= 0;
                    $targetId = 'unit-rapat-' . $unitId;
                ?>
                    <label class="target-option flex cursor-pointer items-center gap-2.5 p-2 rounded-lg text-xs hover:bg-slate-50 dark:hover:bg-slate-800/50 transition border border-slate-100 dark:border-slate-800/60"
                        for="<?= esc($targetId, 'attr') ?>"
                        data-name="<?= esc(strtolower((string) $unit['nama']), 'attr') ?>">
                        <input class="size-4 text-blue-600 rounded focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700" id="<?= esc($targetId, 'attr') ?>"
                            name="target_unit_rapat[]" type="checkbox" value="<?= $unitId ?>"
                            <?= in_array($unitId, $targetUnitIds, true) ? 'checked' : '' ?>
                            <?= $unavailable ? 'disabled' : '' ?> />
                        <span class="min-w-0 flex-1 truncate text-slate-800 dark:text-slate-200 font-medium"><?= esc($unit['nama']) ?></span>
                        <?php if ($unavailable): ?>
                            <span class="py-0.5 px-1.5 rounded text-[10px] font-medium bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">0</span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
                <div class="col-span-full hidden py-4 text-center text-xs text-slate-400 dark:text-slate-500" id="target-empty">
                    Kelompok peserta tidak ditemukan.
                </div>
            </div>
            <p class="hidden text-xs font-semibold text-rose-600 dark:text-rose-400" id="target-peserta-error" role="alert" aria-live="polite">Kelompok peserta tidak valid.</p>
        </div>

        <div class="space-y-3 pt-1" id="bahan-stream-section">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i data-lucide="share-2" class="size-4 text-blue-500"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Materi &amp; Streaming</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="materi_url">
                        Tautan Materi
                    </label>
                    <div class="flex gap-2">
                        <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="materi_url" name="materi_url" type="url"
                            value="<?= esc($schedule['materi_url'] ?? '') ?>" placeholder="https://..." />
                        <select class="py-2.5 px-3 pe-8 shrink-0 border border-slate-200 rounded-xl text-xs sm:text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="materi_akses" name="materi_akses" aria-label="Akses Materi">
                            <?php foreach (['peserta' => 'Peserta', 'anggota' => 'Anggota', 'publik' => 'Publik'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($schedule['materi_akses'] ?? 'peserta') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="stream_url">
                        Live Streaming
                    </label>
                    <div class="flex gap-2">
                        <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="stream_url" name="stream_url" type="url"
                            value="<?= esc($schedule['stream_url'] ?? '') ?>" placeholder="https://..." />
                        <select class="py-2.5 px-3 pe-8 shrink-0 border border-slate-200 rounded-xl text-xs sm:text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="stream_akses" name="stream_akses" aria-label="Akses Streaming">
                            <?php foreach (['anggota' => 'Anggota', 'peserta' => 'Peserta', 'publik' => 'Publik'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($schedule['stream_akses'] ?? 'anggota') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-2 pt-1" id="undangan-section">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300" for="undangan_file">
                Undangan PDF <span class="text-[10px] font-normal text-slate-400 dark:text-slate-500">(opsional, maks 10 MB)</span>
            </label>
            <input class="block w-full border border-slate-200 shadow-xs rounded-xl text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-400 file:bg-slate-50 file:border-0 file:me-4 file:py-2.5 file:px-4 dark:file:bg-slate-800 dark:file:text-slate-400" id="undangan_file" name="undangan_file" type="file"
                accept="application/pdf,.pdf" />

            <?php if (! empty($schedule['undangan_file'])): ?>
                <div class="flex items-center gap-2 p-2.5 sm:p-3 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm">
                    <i data-lucide="file-check-2" class="size-4 shrink-0"></i>
                    <span class="font-medium truncate flex-1"><?= esc($schedule['undangan_nama_asli'] ?: 'undangan-rapat.pdf') ?></span>
                    <label class="flex items-center gap-1.5 text-xs text-rose-600 dark:text-rose-400 cursor-pointer" for="hapus_undangan">
                        <input class="size-4 text-rose-600 rounded focus:ring-rose-500" id="hapus_undangan" name="hapus_undangan" type="checkbox" value="1" />
                        <span>Hapus</span>
                    </label>
                </div>
            <?php endif; ?>
        </div>

        <div class="space-y-3 pt-1">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i data-lucide="sliders" class="size-4 text-blue-500"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Status &amp; Publikasi</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div id="general-status-wrapper">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="status_override">
                        Status Agenda
                    </label>
                    <select class="py-2.5 px-3.5 pe-9 block w-full border border-slate-200 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="status_override" name="status_override">
                        <option value="">Otomatis (Sesuai Waktu)</option>
                        <option value="ditunda" <?= in_array(($schedule['status'] ?? ''), ['ditunda'], true) ? 'selected' : '' ?>>Ditunda</option>
                        <option value="dibatalkan" <?= in_array(($schedule['status'] ?? ''), ['dibatalkan'], true) ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>

                <div>
                    <span class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Akses Publikasi
                    </span>
                    <label class="flex cursor-pointer items-center gap-3 px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/30 min-h-[42px]"
                        for="is_publik">
                        <input class="size-4 text-blue-600 rounded focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700" id="is_publik" name="is_publik" type="checkbox"
                            value="1" <?= (int) ($schedule['is_publik'] ?? 0) === 1 ? 'checked' : '' ?> />
                        <span class="text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-200" id="publik-label">
                            <?= (int) ($schedule['is_publik'] ?? 0) === 1
                                ? 'Tampilkan kepada publik'
                                : 'Internal DPRD saja' ?>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2.5 sm:gap-3 w-full">
        <a href="<?= base_url('admin/jadwal-umum') ?>" class="py-2.5 px-4 inline-flex items-center justify-center text-xs sm:text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition w-full sm:w-auto">
            Batal
        </a>
        <button type="submit" class="py-2.5 px-4 inline-flex items-center justify-center gap-x-2 text-xs sm:text-sm font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition cursor-pointer w-full sm:w-auto">
            <i data-lucide="check" class="size-4"></i>
            <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Jadwal' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
