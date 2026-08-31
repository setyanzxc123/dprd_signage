<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="page-title">Notulensi & Risalah AI</h1>
        <p class="mt-1 text-sm text-base-content/60">Transkripsi rekaman rapat otomatis dan penyusunan risalah resmi menggunakan Google Gemini AI.</p>
    </div>
    <button type="button" id="btn_open_upload_modal" class="btn btn-primary btn-sm w-full gap-1.5 sm:w-auto">
        <i data-lucide="upload" class="h-4 w-4"></i>
        Unggah Rekaman Rapat
    </button>
</div>

<!-- Tabel Daftar Notulen & Antrean -->
<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-5">
        <h2 class="card-title text-base">
            <i data-lucide="mic" class="h-5 w-5 text-primary"></i>
            Daftar Rekaman & Risalah Rapat
        </h2>
        <span class="badge badge-ghost whitespace-nowrap"><?= count($jobs) ?> rekaman</span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto">
            <table class="notulen-table table table-zebra table-md w-full admin-data-table"
                id="table-notulen"
                data-admin-datatable
                data-dt-order='[[1,"desc"]]'
                data-dt-col-filters='[{"col":2,"label":"Status AI"},{"col":3,"label":"Risalah"}]'>
                <thead>
                    <tr class="bg-base-200">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Rapat &amp; Rekaman</th>
                        <th>Status AI</th>
                        <th>Risalah</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
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

                        $statusClass = match ($job['status']) {
                            'completed'   => 'badge-success text-white',
                            'chunking', 'transcribing', 'summarizing' => 'badge-warning text-base-content font-bold',
                            'queued'      => 'badge-info text-white',
                            'failed'      => 'badge-error text-white font-bold',
                            'cancelled'   => 'badge-ghost text-base-content/80',
                            default       => 'badge-ghost text-base-content/80',
                        };

                        $risalahFilter = 'Belum Ada';
                        if ($minutes && ! empty($minutes['ringkasan_eksekutif'])) {
                            $risalahFilter = $minutes['status_verifikasi'] === 'final' ? 'Final' : 'Draft';
                        }
                        ?>
                        <tr class="transition-colors hover:bg-base-200/40">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Rapat & Rekaman" data-order="<?= esc($job['created_at'] ?? $tanggalRapat) ?>">
                                <div class="font-bold text-base-content">
                                    <a href="<?= base_url('admin/notulen/' . $job['id']) ?>" class="hover:text-primary hover:underline">
                                        <?= esc($judulRapat) ?>
                                    </a>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-base-content/60">
                                    <span><?= esc(date('d/m/Y', strtotime($tanggalRapat))) ?></span>
                                    <span>•</span>
                                    <span class="font-mono"><?= esc($job['audio_filename']) ?></span>
                                    <?php if ($job['audio_size'] > 0): ?>
                                        <span>•</span>
                                        <span><?= round($job['audio_size'] / (1024 * 1024), 1) ?> MB</span>
                                    <?php endif; ?>
                                    <?php if ($job['jadwal_type'] === 'banmus'): ?>
                                        <span class="badge badge-secondary badge-xs">Banmus</span>
                                    <?php else: ?>
                                        <span class="badge badge-primary badge-xs">Umum</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Status AI" data-filter="<?= esc($statusLabel) ?>">
                                <span class="badge badge-sm <?= $statusClass ?> font-semibold">
                                    <?= esc($statusLabel) ?>
                                </span>
                            </td>
                            <td data-label="Risalah" data-filter="<?= esc($risalahFilter) ?>">
                                <?php if ($minutes && ! empty($minutes['ringkasan_eksekutif'])): ?>
                                    <?php if ($minutes['status_verifikasi'] === 'final'): ?>
                                        <span class="badge badge-success text-white badge-sm font-semibold gap-1">
                                            <i data-lucide="check-check" class="h-3 w-3"></i> Final
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning text-base-content font-bold badge-sm gap-1">
                                            <i data-lucide="file-edit" class="h-3 w-3"></i> Draft
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-ghost badge-sm text-base-content/75 font-medium">Belum Ada</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="notulen-actions flex flex-wrap items-center justify-end gap-1.5">
                                    <a href="<?= base_url('admin/notulen/' . $job['id']) ?>" class="btn btn-xs w-16 gap-1" title="Buka Detail">
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                        Buka
                                    </a>
                                    <form method="post" action="<?= base_url('admin/notulen/destroy/' . $job['id']) ?>"
                                        class="m-0 inline-flex" data-confirm-message="Hapus notulen ini beserta seluruh transkrip dan risalahnya?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-error btn-xs w-20 gap-1">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            Hapus
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
</section><!-- Modal Upload Rekaman Rapat -->
<dialog id="modal_upload_notulen" class="modal"
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
    <div class="modal-box w-full max-w-lg">

        <!-- Header modal -->
        <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="text-base font-bold leading-tight">Unggah Rekaman Rapat</h3>
            <button id="um_close_btn" type="button" aria-label="Tutup dialog"
                class="btn btn-sm btn-circle btn-ghost shrink-0 -mr-0.5">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- Error box dengan aria-live agar screen reader mengumumkannya -->
        <div id="um_error_box" class="hidden mb-3" role="alert" aria-live="assertive">
            <div class="alert alert-error py-2.5 text-xs gap-2.5">
                <i data-lucide="alert-circle" class="h-4 w-4 shrink-0"></i>
                <span id="um_error_text"></span>
                <button type="button" id="um_retry_btn" class="hidden btn btn-xs btn-error ml-auto shrink-0">
                    Coba Lagi
                </button>
            </div>
        </div>

        <div class="space-y-0">

            <!-- ── Kelompok 1: Data Rapat ──────────────────────────────── -->
            <div class="rounded-box border border-base-300 bg-base-100 divide-y divide-base-200">

                <div class="px-3.5 py-2.5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-base-content/50 mb-2.5">Data Agenda &amp; Topik <span class="normal-case font-normal text-base-content/40">(SSOT)</span></p>

                    <?php if (! empty($presetSchedule)): ?>
                        <div class="alert alert-info/10 border border-info/30 py-2 px-3 text-xs flex items-center justify-between rounded-lg mb-2.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="link" class="h-4 w-4 text-info shrink-0"></i>
                                <div class="min-w-0">
                                    <p class="font-bold text-base-content truncate"><?= esc($presetSchedule['judul']) ?></p>
                                    <p class="text-[10px] text-base-content/60 font-mono"><?= esc(strtoupper($presetSchedule['type'])) ?> &bull; <?= esc($presetSchedule['tanggal']) ?></p>
                                </div>
                            </div>
                            <span class="badge badge-info badge-xs shrink-0 font-semibold">Terkunci</span>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 mb-2">
                        <div>
                            <label for="modal_jadwal_type" class="label py-0 mb-1">
                                <span class="label-text text-xs font-semibold">Jenis Jadwal</span>
                            </label>
                            <select id="modal_jadwal_type" class="select select-bordered select-sm w-full text-xs"
                                title="Umum: rapat komisi, paripurna, dan fraksi. Banmus: rapat Badan Musyawarah.">
                                <option value="umum">Jadwal Umum</option>
                                <option value="banmus">Jadwal Banmus</option>
                            </select>
                        </div>
                        <div class="relative">
                            <label class="label py-0 mb-1">
                                <span class="label-text text-xs font-semibold">Pilih Agenda</span>
                            </label>
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

                            <div class="dropdown w-full" id="um_agenda_dropdown">
                                <button type="button" id="um_agenda_trigger" tabindex="0"
                                    class="input input-bordered input-sm w-full flex items-center justify-between font-normal text-xs px-3 bg-base-100 cursor-pointer hover:border-base-content/40 focus:border-primary focus:outline-none">
                                    <span id="um_agenda_selected_label" class="truncate text-left flex-1 text-base-content">— Tanpa Relasi Agenda —</span>
                                    <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-base-content/50 ml-1"></i>
                                </button>
                                <div tabindex="0" class="dropdown-content z-50 mt-1 w-full rounded-box border border-base-300 bg-base-100 p-2 shadow-xl">
                                    <div class="relative mb-1.5">
                                        <input type="text" id="um_agenda_search_input"
                                            placeholder="Cari nama agenda atau tanggal..."
                                            class="input input-bordered input-xs w-full pl-7 pr-2 text-xs"
                                            autocomplete="off" />
                                        <i data-lucide="search" class="absolute left-2 top-1.5 h-3.5 w-3.5 text-base-content/40"></i>
                                    </div>
                                    <ul id="um_agenda_options_list" class="menu menu-xs max-h-44 overflow-y-auto flex-nowrap p-0 space-y-0.5">
                                        <!-- Opsi agenda dinamis via JavaScript -->
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="modal_judul_rapat" class="label py-0 mb-1 flex items-center justify-between">
                            <span class="label-text text-xs font-semibold">Judul / Topik Rapat</span>
                            <span class="label-text-alt text-base-content/40 text-[11px]">Otomatis dari Agenda</span>
                        </label>
                        <input type="text" id="modal_judul_rapat"
                            placeholder="Otomatis terisi saat agenda dipilih"
                            class="input input-bordered input-sm w-full bg-base-200/60 text-base-content/70 cursor-not-allowed"
                            disabled
                            readonly
                            autocomplete="off" />
                    </div>

                </div>

            </div>

            <!-- ── Kelompok 2: Berkas Audio ────────────────────────────── -->
            <div class="mt-3">
                <!-- Input file asli — tersembunyi, dikontrol via dropzone -->
                <input type="file" id="modal_audio_file"
                    accept=".mp3,.m4a,.wav,.ogg,.aac,.flac,.mp4,audio/*"
                    class="sr-only"
                    aria-describedby="audio_file_hint" />

                <!-- Dropzone -->
                <div id="um_dropzone"
                    class="rounded-box border-2 border-dashed border-base-300 bg-base-200/50 px-4 py-5 text-center cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5"
                    role="button"
                    tabindex="0"
                    aria-label="Pilih atau seret berkas rekaman audio">
                    <div id="um_dz_idle" class="flex flex-col items-center gap-1.5 py-1">
                        <div id="um_dz_icon_wrap" class="flex h-10 w-10 items-center justify-center rounded-xl bg-base-300 transition-all duration-200 mb-1">
                            <i data-lucide="upload-cloud" class="h-5 w-5 text-base-content/60"></i>
                        </div>
                        <p class="text-sm font-semibold text-base-content">
                            <span class="text-primary">Pilih berkas rekaman</span> atau seret ke sini
                        </p>
                        <p id="audio_file_hint" class="text-xs text-base-content/50">MP3 · M4A · WAV · OGG · AAC · FLAC · Maks. 300 MB</p>
                    </div>


                    <!-- File dipilih state -->
                    <div id="um_dz_selected" class="hidden items-center gap-3 text-left">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-success/15">
                            <i data-lucide="file-audio" class="h-5 w-5 text-success"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p id="um_dz_filename" class="truncate text-sm font-semibold text-base-content"></p>
                            <p id="um_dz_filemeta" class="mt-0.5 text-xs font-mono text-base-content/60"></p>
                        </div>
                        <button type="button" id="um_dz_change_btn"
                            class="btn btn-xs btn-ghost shrink-0 gap-1 text-base-content/60 hover:text-base-content">
                            <i data-lucide="refresh-cw" class="h-3 w-3"></i>
                            Ganti Berkas
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview audio — muncul setelah file dipilih -->
            <div id="audio_preview_container" class="hidden mt-2 rounded-lg bg-base-200 p-3">
                <div class="mb-1.5 flex items-center justify-between text-xs text-base-content/60">
                    <span class="font-semibold flex items-center gap-1.5">
                        <i data-lucide="volume-2" class="h-3.5 w-3.5 text-primary"></i>
                        Pratinjau Rekaman
                    </span>
                    <span id="audio_preview_info" class="font-mono text-xs text-base-content/50"></span>
                </div>
                <audio id="audio_preview_player" controls preload="metadata" class="w-full h-10" aria-label="Pemutar pratinjau audio rekaman"></audio>
            </div>

            <!-- Progress upload — muncul setelah submit -->
            <div id="upload_progress_box" class="hidden mt-3 rounded-box bg-base-200 border border-primary/20 p-3.5 space-y-2"
                role="status" aria-live="polite" aria-label="Status unggahan">
                <!-- Baris 1: status + persen -->
                <div class="flex items-center justify-between text-xs font-semibold">
                    <div class="flex items-center gap-2 text-primary">
                        <span class="loading loading-spinner loading-xs"></span>
                        <span id="upload_status_text">Mengunggah rekaman ke server...</span>
                    </div>
                    <span id="upload_progress_percent" class="font-mono text-primary">0%</span>
                </div>
                <!-- Progress bar -->
                <progress id="upload_progress_bar" class="progress progress-primary w-full h-2" value="0" max="100"></progress>
                <!-- Baris 2: byte counter + speed + ETA -->
                <div class="flex items-center justify-between text-xs text-base-content/60 font-mono">
                    <div class="flex items-center gap-2.5">
                        <span id="upload_transfer_info">0 MB / 0 MB</span>
                        <span class="text-base-content/30">·</span>
                        <span id="upload_speed_info">— MB/s</span>
                    </div>
                    <span id="upload_eta_info" class="text-base-content/60"></span>
                </div>
                <!-- Banner jangan tutup — muncul saat upload aktif -->
                <div id="upload_warning_banner" class="hidden flex items-center gap-2 text-xs text-warning/90 pt-0.5">
                    <i data-lucide="shield-alert" class="h-3.5 w-3.5 shrink-0"></i>
                    <span>Jangan tutup atau berpindah dari halaman ini selama proses unggahan berlangsung.</span>
                </div>
            </div>

            <!-- Info redirect — muncul setelah file dipilih -->
            <div id="um_info_note" class="hidden mt-2 flex items-start gap-1.5 text-xs text-base-content/50 leading-relaxed">
                <i data-lucide="info" class="h-3.5 w-3.5 shrink-0 mt-0.5 text-info/70"></i>
                <span>Anda akan otomatis dialihkan ke halaman monitoring setelah unggahan selesai.</span>
            </div>

        </div>

        <!-- Action buttons -->
        <div class="modal-action mt-4">
            <button type="button" id="um_cancel_btn" class="btn btn-ghost btn-sm">Batal</button>
            <button type="button" id="um_submit_btn" class="btn btn-primary btn-sm gap-1.5">
                <span id="um_spinner" class="loading loading-spinner loading-xs hidden"></span>
                <i data-lucide="upload" id="um_btn_icon" class="h-3.5 w-3.5"></i>
                <span id="um_btn_label">Unggah Rekaman</span>
            </button>
        </div>

    </div>

    <!-- Backdrop: dikelola via JS — diblokir saat upload aktif -->
    <div id="um_backdrop" class="modal-backdrop" aria-hidden="true">
        <button type="button" id="um_backdrop_btn" tabindex="-1">tutup</button>
    </div>
</dialog>

<!-- Dialog konfirmasi batalkan upload — DaisyUI -->
<dialog id="um_confirm_dialog" class="modal">
    <div class="modal-box max-w-sm">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-warning/15">
                <i data-lucide="triangle-alert" class="h-4 w-4 text-warning"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold">Batalkan unggahan rekaman?</h4>
                <p class="mt-1 text-xs text-base-content/60 leading-relaxed">
                    Unggahan sedang berjalan. Jika dibatalkan, proses tidak akan tersimpan dan Anda perlu mengulang dari awal.
                </p>
            </div>
        </div>
        <div class="modal-action mt-4 gap-2">
            <button type="button" id="um_confirm_keep_btn" class="btn btn-sm btn-ghost">
                Lanjutkan Unggahan
            </button>
            <button type="button" id="um_confirm_cancel_btn" class="btn btn-sm btn-warning gap-1.5">
                <i data-lucide="x" class="h-3.5 w-3.5"></i>
                Ya, Batalkan
            </button>
        </div>
    </div>
</dialog>

<?= $this->endSection() ?>


