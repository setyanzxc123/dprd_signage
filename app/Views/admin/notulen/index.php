<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="page-title">Notulensi & Risalah AI</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Transkripsi rekaman rapat otomatis dan penyusunan risalah resmi menggunakan Google Gemini AI.</p>
    </div>
    <button type="button" id="btn_open_upload_modal" data-hs-overlay="#modal_upload_notulen" class="py-2.5 px-4 inline-flex items-center justify-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer w-full sm:w-auto">
        <i data-lucide="upload" class="size-4"></i>
        Unggah Rekaman Rapat
    </button>
</div>

<!-- Tabel Daftar Notulen & Antrean -->
<section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden min-w-0">
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 px-4 py-3.5 sm:px-5">
        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
            <i data-lucide="mic" class="size-4 text-emerald-600 dark:text-emerald-400"></i>
            Daftar Rekaman & Risalah Rapat
        </h2>
        <span class="py-0.5 px-2.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 whitespace-nowrap"><?= count($jobs) ?> rekaman</span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto">
            <table class="notulen-table table-md w-full admin-data-table"
                id="table-notulen"
                data-admin-datatable
                data-dt-order='[[1,"desc"]]'
                data-dt-col-filters='[{"col":2,"label":"Status AI"},{"col":3,"label":"Risalah"}]'>
                <thead>
                    <tr class="bg-slate-50/75 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Rapat &amp; Rekaman</th>
                        <th>Status AI</th>
                        <th>Risalah</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($jobs as $job): ?>
                        <?php
                        $minutes      = $minutesMap[$job['id']] ?? null;
                        $scheduleInfo = $schedulesMap[$job['jadwal_type'] ?? 'umum'][$job['jadwal_id'] ?? 0] ?? null;
                        $judulRapat   = ! empty($scheduleInfo['judul']) ? $scheduleInfo['judul'] : pathinfo($job['audio_filename'], PATHINFO_FILENAME);
                        $tanggalRapat = ! empty($scheduleInfo['tanggal']) ? $scheduleInfo['tanggal'] : substr((string) $job['created_at'], 0, 10);
                        $isInProgress = in_array($job['status'], ['chunking', 'transcribing', 'summarizing'], true);

                        $statusLabel = match ($job['status']) {
                            'completed'   => 'Selesai',
                            'chunking'    => 'Memotong Audio',
                            'transcribing'=> 'Transkripsi',
                            'summarizing' => 'Menyusun Risalah',
                            'queued'      => 'Antrean',
                            'failed'      => 'Gagal',
                            'cancelled'   => 'Dibatalkan',
                            default       => ucfirst($job['status']),
                        };

                        $statusTextClass = match ($job['status']) {
                            'completed'   => 'text-emerald-600 dark:text-emerald-400',
                            'chunking', 'transcribing', 'summarizing' => 'text-amber-600 dark:text-amber-400 font-semibold',
                            'queued'      => 'text-blue-600 dark:text-blue-400',
                            'failed'      => 'text-rose-600 dark:text-rose-400 font-semibold',
                            'cancelled'   => 'text-slate-500 dark:text-slate-400',
                            default       => 'text-slate-500 dark:text-slate-400',
                        };

                        $risalahFilter = 'Belum Ada';
                        if ($minutes && ! empty($minutes['ringkasan_eksekutif'])) {
                            $risalahFilter = $minutes['status_verifikasi'] === 'final' ? 'Final' : 'Draft';
                        }
                        ?>
                        <tr class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Rapat & Rekaman" data-order="<?= esc($job['created_at'] ?? $tanggalRapat) ?>">
                                <div class="font-bold text-slate-900 dark:text-white">
                                    <a href="<?= base_url('admin/notulen/' . $job['id']) ?>" class="hover:text-emerald-600 dark:hover:text-emerald-400 hover:underline">
                                        <?= esc($judulRapat) ?>
                                    </a>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                    <span><?= esc(date('d/m/Y', strtotime($tanggalRapat))) ?></span>
                                    <span>&bull;</span>
                                    <span class="font-mono"><?= esc($job['audio_filename']) ?></span>
                                    <?php if ($job['audio_size'] > 0): ?>
                                        <span>&bull;</span>
                                        <span><?= round($job['audio_size'] / (1024 * 1024), 1) ?> MB</span>
                                    <?php endif; ?>
                                    <?php if ($job['jadwal_type'] === 'banmus'): ?>
                                        <span class="font-semibold text-purple-600 dark:text-purple-400">Banmus</span>
                                    <?php else: ?>
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">Umum</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Status AI" data-filter="<?= esc($statusLabel) ?>">
                                <span class="text-xs font-semibold whitespace-nowrap <?= $statusTextClass ?>">
                                    <?= esc($statusLabel) ?>
                                </span>
                            </td>
                            <td data-label="Risalah" data-filter="<?= esc($risalahFilter) ?>">
                                <?php if ($minutes && ! empty($minutes['ringkasan_eksekutif'])): ?>
                                    <?php if ($minutes['status_verifikasi'] === 'final'): ?>
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                            <i data-lucide="check-check" class="size-3.5"></i> Final
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                            <i data-lucide="file-edit" class="size-3.5"></i> Draft
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 dark:text-slate-500">Belum Ada</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="notulen-actions flex flex-wrap items-center justify-end gap-1.5">
                                    <a href="<?= base_url('admin/notulen/' . $job['id']) ?>" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-indigo-200/80 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 hover:text-indigo-800 hover:border-indigo-300 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-600 dark:hover:text-white dark:hover:border-indigo-600 shadow-2xs transition" title="Buka Detail" aria-label="Buka notulen <?= esc($judulRapat) ?>">
                                        <i data-lucide="eye" class="size-3.5"></i>
                                        Buka
                                    </a>
                                    <form method="post" action="<?= base_url('admin/notulen/destroy/' . $job['id']) ?>"
                                         class="m-0 inline-flex" data-confirm-message="Hapus notulen ini beserta seluruh transkrip dan risalahnya?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="p-1.5 inline-flex items-center justify-center rounded-lg border border-rose-200/80 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 hover:border-rose-300 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-600 dark:hover:text-white dark:hover:border-rose-600 shadow-2xs transition cursor-pointer" title="Hapus" aria-label="Hapus notulen <?= esc($judulRapat) ?>">
                                            <i data-lucide="trash-2" class="size-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<!-- Modal Upload Rekaman Rapat -->
<div id="modal_upload_notulen" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="modal_upload_notulen_label"
    data-upload-token="<?= esc($audioUploadToken) ?>"
    data-start-url="<?= base_url('admin/notulen/audio-upload/start') ?>"
    data-chunk-url="<?= base_url('admin/notulen/audio-upload/chunk') ?>"
    data-cancel-url="<?= base_url('admin/notulen/audio-upload/cancel') ?>"
    data-commit-url="<?= base_url('admin/notulen/upload') ?>"
    data-chunk-size="<?= (int) $audioChunkSize ?>"
    data-csrf-name="<?= csrf_token() ?>"
    data-csrf-value="<?= csrf_hash() ?>"
    data-max-size="314572800"
    data-preset-type="<?= esc($presetSchedule['type'] ?? '') ?>"
    data-preset-id="<?= (int) ($presetSchedule['id'] ?? 0) ?>"
    data-preset-title="<?= esc($presetSchedule['judul'] ?? '', 'attr') ?>"
    data-preset-label="<?= esc($presetSchedule['label'] ?? '', 'attr') ?>">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-slate-200 shadow-xl rounded-2xl pointer-events-auto dark:bg-slate-900 dark:border-slate-800">

            <!-- Header modal -->
            <div class="flex justify-between items-center py-3.5 px-4 sm:px-6 border-b border-slate-200 dark:border-slate-800">
                <h3 id="modal_upload_notulen_label" class="text-base font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="upload" class="size-5 text-emerald-600 dark:text-emerald-400"></i>
                    Unggah Rekaman Rapat
                </h3>
                <button id="um_close_btn" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-400" aria-label="Tutup dialog" data-hs-overlay="#modal_upload_notulen">
                    <span class="sr-only">Tutup</span>
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-4">
                <!-- Error box dengan aria-live agar screen reader mengumumkannya -->
                <div id="um_error_box" class="hidden" role="alert" aria-live="assertive">
                    <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl flex items-center justify-between gap-2.5 dark:bg-rose-950/30 dark:border-rose-800 dark:text-rose-400">
                        <div class="flex items-center gap-2">
                            <i data-lucide="alert-circle" class="size-4 shrink-0"></i>
                            <span id="um_error_text"></span>
                        </div>
                        <button type="button" id="um_retry_btn" class="hidden py-1 px-2 rounded-md bg-rose-600 text-white hover:bg-rose-700 text-xs font-semibold shrink-0">
                            Coba Lagi
                        </button>
                    </div>
                </div>

                <!-- Kelompok 1: Data Rapat -->
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 p-3.5 space-y-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Data Agenda &amp; Topik <span class="normal-case font-normal text-slate-400">(SSOT)</span></p>

                    <?php if (! empty($presetSchedule)): ?>
                        <div class="py-2 px-3 text-xs flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-300">
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="link" class="size-4 text-blue-600 dark:text-blue-400 shrink-0"></i>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900 dark:text-white truncate"><?= esc($presetSchedule['judul']) ?></p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono"><?= esc(strtoupper($presetSchedule['type'])) ?> &bull; <?= esc($presetSchedule['tanggal']) ?></p>
                                </div>
                            </div>
                            <span class="py-0.5 px-2 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300 shrink-0">Terkunci</span>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <div>
                            <label for="modal_jadwal_type" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Jadwal</label>
                            <select id="modal_jadwal_type" class="py-2 px-3 pe-9 block w-full border border-slate-200 rounded-lg text-xs focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200"
                                title="Umum: rapat komisi, paripurna, dan fraksi. Banmus: rapat Badan Musyawarah.">
                                <option value="umum">Jadwal Umum</option>
                                <option value="banmus">Jadwal Banmus</option>
                            </select>
                        </div>
                        <div class="relative">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Pilih Agenda</label>
                            <?php
                            $generalJson = array_map(static fn ($g): array => [
                                'id'    => (string) $g['id'],
                                'title' => $g['judul'],
                                'date'  => date('d/m/Y', strtotime($g['tanggal'])),
                                'label' => date('d/m/Y', strtotime($g['tanggal'])) . ' — ' . $g['judul'],
                            ], $generalSchedules);

                            $banmusJson = array_map(static fn ($b): array => [
                                'id'    => (string) $b['id'],
                                'title' => $b['agenda'],
                                'date'  => date('d/m/Y', strtotime($b['tanggal'])),
                                'label' => date('d/m/Y', strtotime($b['tanggal'])) . ' — ' . $b['agenda'],
                            ], $banmusItems);
                            ?>
                            <input type="hidden" id="modal_jadwal_id" name="jadwal_id" value=""
                                data-general-options="<?= esc(json_encode($generalJson), 'attr') ?>"
                                data-banmus-options="<?= esc(json_encode($banmusJson), 'attr') ?>" />

                            <div class="hs-dropdown relative w-full" id="um_agenda_dropdown">
                                <button type="button" id="um_agenda_trigger" tabindex="0"
                                    class="hs-dropdown-toggle py-2 px-3 block w-full border border-slate-200 rounded-lg text-xs flex items-center justify-between text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900 cursor-pointer focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700">
                                    <span id="um_agenda_selected_label" class="truncate text-left flex-1">— Tanpa Relasi Agenda —</span>
                                    <i data-lucide="chevron-down" class="size-4 shrink-0 text-slate-400 ms-1"></i>
                                </button>
                                <div class="hs-dropdown-menu hs-dropdown-open:opacity-100 hs-dropdown-open:block opacity-0 hidden transition-[opacity,margin] duration-200 z-50 mt-1 w-full rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-800">
                                    <div class="relative mb-1.5">
                                        <input type="text" id="um_agenda_search_input"
                                            placeholder="Cari nama agenda atau tanggal..."
                                            class="py-1 px-2.5 ps-7 block w-full border border-slate-200 rounded-lg text-xs placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200"
                                            autocomplete="off" aria-label="Cari agenda" />
                                        <i data-lucide="search" class="size-3.5 text-slate-400 absolute start-2 top-2"></i>
                                    </div>
                                    <ul id="um_agenda_options_list" class="max-h-44 overflow-y-auto space-y-0.5">
                                        <!-- Opsi agenda dinamis via JavaScript -->
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="modal_judul_rapat" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Judul / Topik Rapat</label>
                            <span class="text-slate-400 text-[11px]">Otomatis dari Agenda</span>
                        </div>
                        <input type="text" id="modal_judul_rapat"
                            placeholder="Otomatis terisi saat agenda dipilih"
                            class="py-2 px-3 block w-full border border-slate-200 rounded-lg text-xs bg-slate-100 text-slate-500 cursor-not-allowed dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400"
                            disabled
                            readonly
                            autocomplete="off" />
                    </div>
                </div>

                <!-- Kelompok 2: Berkas Audio -->
                <div>
                    <input type="file" id="modal_audio_file"
                        accept=".mp3,.m4a,.wav,.ogg,.aac,.flac,.mp4,audio/*"
                        class="sr-only"
                        aria-describedby="audio_file_hint" />

                    <!-- Dropzone -->
                    <div id="um_dropzone"
                        class="rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 px-4 py-5 text-center cursor-pointer transition-all duration-200 hover:border-emerald-500 hover:bg-emerald-50/30 dark:hover:bg-emerald-950/20"
                        role="button"
                        tabindex="0"
                        aria-label="Pilih atau seret berkas rekaman audio">
                        <div id="um_dz_idle" class="flex flex-col items-center gap-1.5 py-1">
                            <div id="um_dz_icon_wrap" class="flex size-10 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 transition-all duration-200 mb-1">
                                <i data-lucide="upload-cloud" class="size-5 text-slate-500 dark:text-slate-400"></i>
                            </div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <span class="text-emerald-600 dark:text-emerald-400">Pilih berkas rekaman</span> atau seret ke sini
                            </p>
                            <p id="audio_file_hint" class="text-xs text-slate-500 dark:text-slate-400">MP3 &bull; M4A &bull; WAV &bull; OGG &bull; AAC &bull; FLAC &bull; Maks. 300 MB</p>
                        </div>

                        <!-- File dipilih state -->
                        <div id="um_dz_selected" class="hidden items-center gap-3 text-left">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                <i data-lucide="file-audio" class="size-5"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p id="um_dz_filename" class="truncate text-sm font-semibold text-slate-900 dark:text-white"></p>
                                <p id="um_dz_filemeta" class="mt-0.5 text-xs font-mono text-slate-500 dark:text-slate-400"></p>
                            </div>
                            <button type="button" id="um_dz_change_btn"
                                class="py-1 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                <i data-lucide="refresh-cw" class="size-3"></i>
                                Ganti Berkas
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview audio -->
                <div id="audio_preview_container" class="hidden rounded-xl bg-slate-100 dark:bg-slate-800/60 p-3">
                    <div class="mb-1.5 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span class="font-semibold flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                            <i data-lucide="volume-2" class="size-3.5 text-emerald-500"></i>
                            Pratinjau Rekaman
                        </span>
                        <span id="audio_preview_info" class="font-mono text-xs text-slate-400"></span>
                    </div>
                    <audio id="audio_preview_player" controls preload="metadata" class="w-full h-10" aria-label="Pemutar pratinjau audio rekaman"></audio>
                </div>

                <!-- Progress upload -->
                <div id="upload_progress_box" class="hidden rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-emerald-200 dark:border-emerald-800 p-3.5 space-y-2"
                    role="status" aria-live="polite" aria-label="Status unggahan">
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
                            <span class="animate-spin inline-block size-3.5 border-2 border-current border-t-transparent rounded-full"></span>
                            <span id="upload_status_text">Mengunggah rekaman ke server...</span>
                        </div>
                        <span id="upload_progress_percent" class="font-mono text-emerald-600 dark:text-emerald-400">0%</span>
                    </div>
                    <div class="flex w-full h-2 bg-slate-200 rounded-full overflow-hidden dark:bg-slate-700">
                        <div id="upload_progress_bar" class="flex flex-col justify-center overflow-hidden bg-emerald-600 text-xs text-white text-center whitespace-nowrap transition duration-500" style="width: 0%"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-mono">
                        <div class="flex items-center gap-2.5">
                            <span id="upload_transfer_info">0 MB / 0 MB</span>
                            <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                            <span id="upload_speed_info">— MB/s</span>
                        </div>
                        <span id="upload_eta_info" class="text-slate-500 dark:text-slate-400"></span>
                    </div>
                    <div id="upload_warning_banner" class="hidden flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400 pt-0.5">
                        <i data-lucide="shield-alert" class="size-3.5 shrink-0"></i>
                        <span>Jangan tutup atau berpindah dari halaman ini selama proses unggahan berlangsung.</span>
                    </div>
                </div>

                <div id="um_info_note" class="hidden flex items-start gap-1.5 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    <i data-lucide="info" class="size-3.5 shrink-0 mt-0.5 text-blue-500"></i>
                    <span>Anda akan otomatis dialihkan ke halaman monitoring setelah unggahan selesai.</span>
                </div>
            </div>

            <!-- Footer actions -->
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 sm:px-6 border-t border-slate-200 dark:border-slate-800">
                <button type="button" id="um_cancel_btn" class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition" data-hs-overlay="#modal_upload_notulen">Batal</button>
                <button type="button" id="um_submit_btn" class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer">
                    <span id="um_spinner" class="animate-spin inline-block size-3.5 border-2 border-current border-t-transparent rounded-full hidden"></span>
                    <i data-lucide="upload" id="um_btn_icon" class="size-3.5"></i>
                    <span id="um_btn_label">Unggah Rekaman</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Dialog konfirmasi batalkan upload -->
<div id="um_confirm_dialog" class="hs-overlay hidden size-full fixed top-0 start-0 z-[90] overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="um_confirm_dialog_label">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-md sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-slate-200 shadow-xl rounded-2xl pointer-events-auto dark:bg-slate-900 dark:border-slate-800">
            <div class="p-4 sm:p-6 space-y-4">
                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <i data-lucide="triangle-alert" class="size-4"></i>
                    </div>
                    <div>
                        <h4 id="um_confirm_dialog_label" class="text-sm font-bold text-slate-800 dark:text-slate-200">Batalkan unggahan rekaman?</h4>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Unggahan sedang berjalan. Jika dibatalkan, proses tidak akan tersimpan dan Anda perlu mengulang dari awal.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 sm:px-6 border-t border-slate-200 dark:border-slate-800">
                <button type="button" id="um_confirm_keep_btn" class="py-2 px-3 inline-flex items-center text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition" data-hs-overlay="#um_confirm_dialog">
                    Lanjutkan Unggahan
                </button>
                <button type="button" id="um_confirm_cancel_btn" class="py-2 px-3 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg bg-amber-600 text-white hover:bg-amber-700 shadow-xs transition cursor-pointer">
                    <i data-lucide="x" class="size-3.5"></i>
                    Ya, Batalkan
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>


