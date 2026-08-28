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
                data-admin-datatable
                data-dt-order='[[1,"desc"]]'
                data-dt-col-filters='[{"column":2,"label":"Status AI"},{"column":3,"label":"Risalah"}]'>
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
                        $judulRapat   = ! empty($minutes['judul_rapat']) ? $minutes['judul_rapat'] : $job['audio_filename'];
                        $tanggalRapat = ! empty($minutes['tanggal_rapat']) ? $minutes['tanggal_rapat'] : substr((string) $job['created_at'], 0, 10);
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
                        ?>
                        <tr class="transition-colors hover:bg-base-200/40">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Rapat & Rekaman" data-order="<?= esc($tanggalRapat) ?>">
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
                            <td data-label="Status AI">
                                <?php if ($job['status'] === 'completed'): ?>
                                    <div class="flex items-center gap-1.5">
                                        <span class="badge badge-success badge-sm font-semibold">Selesai</span>
                                        <span class="text-xs text-base-content/60"><?= (int) $job['total_chunks'] ?> segmen</span>
                                    </div>
                                <?php elseif ($isInProgress || $job['status'] === 'queued'): ?>
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-semibold <?= $job['status'] === 'queued' ? 'text-info' : 'text-warning' ?>">
                                                <?= esc($statusLabel) ?>
                                            </span>
                                            <span class="font-mono"><?= (int) $job['progress_percent'] ?>%</span>
                                        </div>
                                        <progress class="progress <?= $job['status'] === 'queued' ? 'progress-info' : 'progress-warning' ?> w-full" value="<?= (int) $job['progress_percent'] ?>" max="100"></progress>
                                        <div class="truncate text-[11px] text-base-content/60" title="<?= esc($job['current_step']) ?>">
                                            <?= esc($job['current_step']) ?>
                                        </div>
                                    </div>
                                <?php elseif ($job['status'] === 'failed'): ?>
                                    <div>
                                        <span class="badge badge-error badge-sm font-semibold">Gagal</span>
                                        <?php if (! empty($job['error_message'])): ?>
                                            <p class="mt-0.5 max-w-xs truncate text-[11px] text-error" title="<?= esc($job['error_message']) ?>">
                                                <?= esc($job['error_message']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($job['status'] === 'cancelled'): ?>
                                    <span class="badge badge-neutral badge-sm font-semibold">Dibatalkan</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Risalah">
                                <?php if ($minutes && ! empty($minutes['ringkasan_eksekutif'])): ?>
                                    <?php if ($minutes['status_verifikasi'] === 'final'): ?>
                                        <span class="badge badge-success badge-sm gap-1">
                                            <i data-lucide="check-check" class="h-3 w-3"></i> Final
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning badge-sm gap-1">
                                            <i data-lucide="file-edit" class="h-3 w-3"></i> Draft
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-base-content/40">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= base_url('admin/notulen/' . $job['id']) ?>" class="btn btn-ghost btn-xs gap-1" title="Buka Detail">
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                        Buka
                                    </a>

                                    <div class="dropdown dropdown-end">
                                        <button tabindex="0" class="btn btn-ghost btn-xs btn-square">
                                            <i data-lucide="more-vertical" class="h-4 w-4"></i>
                                        </button>
                                        <ul tabindex="0" class="dropdown-content menu rounded-box z-10 w-48 border border-base-300 bg-base-100 p-2 text-xs shadow-lg">
                                            <?php if (in_array($job['status'], ['failed', 'cancelled'], true)): ?>
                                                <li>
                                                    <form method="post" action="<?= base_url('admin/notulen/retry/' . $job['id']) ?>">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="gap-2 text-primary">
                                                            <i data-lucide="rotate-cw" class="h-3.5 w-3.5"></i> Proses Ulang (Resume)
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>

                                            <?php if ($isInProgress || $job['status'] === 'queued'): ?>
                                                <li>
                                                    <form method="post" action="<?= base_url('admin/notulen/cancel/' . $job['id']) ?>"
                                                        data-confirm-message="Batalkan proses AI untuk rekaman ini?">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="gap-2 text-warning">
                                                            <i data-lucide="ban" class="h-3.5 w-3.5"></i> Batalkan Proses
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>

                                            <?php if ($job['status'] === 'completed'): ?>
                                                <li>
                                                    <form method="post" action="<?= base_url('admin/notulen/delete-audio/' . $job['id']) ?>"
                                                        data-confirm-message="Hapus berkas audio lokal? Transkrip dan risalah tetap tersimpan.">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="gap-2 text-base-content/80">
                                                            <i data-lucide="trash" class="h-3.5 w-3.5"></i> Bersihkan Audio Lokal
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>

                                            <li>
                                                <form method="post" action="<?= base_url('admin/notulen/destroy/' . $job['id']) ?>"
                                                    data-confirm-message="Hapus permanen notulen ini beserta seluruh transkrip dan berkasnya?">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="gap-2 text-error">
                                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Hapus Permanen
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
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
<dialog id="modal_upload_notulen" class="modal"
    data-upload-token="<?= esc($audioUploadToken) ?>"
    data-start-url="<?= base_url('admin/notulen/audio-upload/start') ?>"
    data-chunk-url="<?= base_url('admin/notulen/audio-upload/chunk') ?>"
    data-cancel-url="<?= base_url('admin/notulen/audio-upload/cancel') ?>"
    data-commit-url="<?= base_url('admin/notulen/upload') ?>"
    data-chunk-size="<?= (int) $audioChunkSize ?>"
    data-csrf-name="<?= csrf_token() ?>"
    data-csrf-value="<?= csrf_hash() ?>"
    data-max-size="314572800">
    <div class="modal-box w-full max-w-lg">

        <!-- Header modal -->
        <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="text-base font-bold leading-tight">Kirim Rekaman Rapat</h3>
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
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-base-content/40 mb-2.5">Data Rapat <span class="normal-case font-normal text-base-content/40">(opsional)</span></p>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 mb-2">
                        <div>
                            <label for="modal_jadwal_type" class="label py-0 mb-1">
                                <span class="label-text text-xs font-semibold">Jenis Jadwal</span>
                            </label>
                            <select id="modal_jadwal_type" class="select select-bordered select-sm w-full text-xs"
                                title="Umum: rapat komisi dan paripurna. Banmus: rapat Badan Musyawarah.">
                                <option value="umum">Jadwal Umum</option>
                                <option value="banmus">Jadwal Banmus</option>
                            </select>
                        </div>
                        <div>
                            <label for="modal_jadwal_id" class="label py-0 mb-1">
                                <span class="label-text text-xs font-semibold">Agenda</span>
                            </label>
                            <select id="modal_jadwal_id" class="select select-bordered select-sm w-full text-xs">
                                <option value="">— Tanpa Relasi Agenda —</option>
                                <optgroup label="Jadwal Umum Terdekat" id="group_umum">
                                    <?php foreach ($generalSchedules as $g): ?>
                                        <option value="<?= $g['id'] ?>" data-title="<?= esc($g['judul']) ?>">
                                            <?= esc($g['tanggal']) ?> — <?= esc($g['judul']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Agenda Banmus Terdekat" id="group_banmus" class="hidden">
                                    <?php foreach ($banmusItems as $b): ?>
                                        <option value="<?= $b['id'] ?>" data-title="<?= esc($b['agenda']) ?>">
                                            <?= esc($b['tanggal']) ?> — <?= esc($b['agenda']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="modal_judul_rapat" class="label py-0 mb-1">
                            <span class="label-text text-xs font-semibold">Judul / Topik Rapat</span>
                        </label>
                        <input type="text" id="modal_judul_rapat"
                            placeholder="Judul rapat (opsional)"
                            class="input input-bordered input-sm w-full"
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

                <!-- [FIX D] Dropzone -->
                <div id="um_dropzone"
                    class="rounded-box border-2 border-dashed border-base-300 bg-base-200/50 px-4 py-5 text-center cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5"
                    role="button"
                    tabindex="0"
                    aria-label="Pilih atau seret berkas rekaman audio">
                    <div id="um_dz_idle" class="flex flex-col items-center gap-2.5">
                        <div id="um_dz_icon_wrap" class="flex h-10 w-10 items-center justify-center rounded-xl bg-base-300 transition-all duration-200">
                            <i data-lucide="mic" class="h-5 w-5 text-base-content/50"></i>
                        </div>
                        <button type="button" id="um_dz_pick_btn"
                            class="btn btn-sm btn-outline btn-primary gap-1.5">
                            <i data-lucide="folder-open" class="h-3.5 w-3.5"></i>
                            Pilih File…
                        </button>
                        <p id="audio_file_hint" class="text-[10px] text-base-content/30">MP3 · M4A · WAV · OGG · AAC · FLAC · Maks. 300 MB</p>
                    </div>

                    <!-- File dipilih state -->
                    <div id="um_dz_selected" class="hidden items-center gap-3 text-left">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-success/15">
                            <i data-lucide="file-audio" class="h-5 w-5 text-success"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p id="um_dz_filename" class="truncate text-sm font-semibold text-base-content"></p>
                            <p id="um_dz_filemeta" class="mt-0.5 text-[10px] font-mono text-base-content/50"></p>
                        </div>
                        <button type="button" id="um_dz_change_btn"
                            class="btn btn-xs btn-ghost shrink-0 gap-1 text-base-content/50 hover:text-base-content">
                            <i data-lucide="refresh-cw" class="h-3 w-3"></i>
                            Ganti
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview audio — muncul setelah file dipilih -->
            <div id="audio_preview_container" class="hidden mt-2 rounded-lg bg-base-200 p-3">
                <div class="mb-1.5 flex items-center justify-between text-xs text-base-content/50">
                    <span id="audio_preview_info" class="font-mono text-[10px]"></span>
                </div>
                <audio id="audio_preview_player" controls class="w-full h-10"></audio>
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
                <div class="flex items-center justify-between text-[10px] text-base-content/50 font-mono">
                    <div class="flex items-center gap-2.5">
                        <span id="upload_transfer_info">0 MB / 0 MB</span>
                        <span class="text-base-content/30">·</span>
                        <span id="upload_speed_info">— MB/s</span>
                    </div>
                    <span id="upload_eta_info" class="text-base-content/50"></span>
                </div>
                <!-- Banner jangan tutup — muncul saat upload aktif -->
                <div id="upload_warning_banner" class="hidden flex items-center gap-2 text-[10px] text-warning/80 pt-0.5">
                    <i data-lucide="shield-alert" class="h-3 w-3 shrink-0"></i>
                    <span>Jangan tutup atau navigasi dari halaman ini selama proses unggahan berlangsung.</span>
                </div>
            </div>

            <!-- Info redirect — muncul setelah file dipilih -->
            <div id="um_info_note" class="hidden mt-2 flex items-start gap-1.5 text-[10px] text-base-content/40 leading-relaxed">
                <i data-lucide="info" class="h-3 w-3 shrink-0 mt-0.5 text-info/60"></i>
                <span>Anda akan diarahkan ke halaman notulensi setelah unggahan selesai.</span>
            </div>

        </div>

        <!-- Action buttons -->
        <div class="modal-action mt-4">
            <button type="button" id="um_cancel_btn" class="btn btn-ghost btn-sm">Batal</button>
            <button type="button" id="um_submit_btn" class="btn btn-primary btn-sm gap-1.5">
                <span id="um_spinner" class="loading loading-spinner loading-xs hidden"></span>
                <i data-lucide="upload" id="um_btn_icon" class="h-3.5 w-3.5"></i>
                <span id="um_btn_label">Kirim Rekaman</span>
            </button>
        </div>

    </div>

    <!-- Backdrop: dikelola via JS — diblokir saat upload aktif -->
    <div id="um_backdrop" class="modal-backdrop" aria-hidden="true">
        <button type="button" id="um_backdrop_btn" tabindex="-1">tutup</button>
    </div>
</dialog>

<!-- [FIX E] Dialog konfirmasi batalkan upload — DaisyUI, menggantikan confirm() browser -->
<dialog id="um_confirm_dialog" class="modal">
    <div class="modal-box max-w-sm">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-warning/15">
                <i data-lucide="triangle-alert" class="h-4 w-4 text-warning"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold">Batalkan upload?</h4>
                <p class="mt-1 text-xs text-base-content/60 leading-relaxed">
                    Unggahan sedang berjalan. Jika dibatalkan, file tidak akan tersimpan dan Anda perlu memulai dari awal.
                </p>
            </div>
        </div>
        <div class="modal-action mt-4 gap-2">
            <button type="button" id="um_confirm_keep_btn" class="btn btn-sm btn-ghost">
                Lanjutkan Upload
            </button>
            <button type="button" id="um_confirm_cancel_btn" class="btn btn-sm btn-warning gap-1.5">
                <i data-lucide="x" class="h-3.5 w-3.5"></i>
                Ya, Batalkan
            </button>
        </div>
    </div>
</dialog>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script {csp-script-nonce}>
(function () {
    'use strict';

    // ── Element refs ──────────────────────────────────────────────────────
    var modal       = document.getElementById('modal_upload_notulen');
    var submitBtn   = document.getElementById('um_submit_btn');
    var cancelBtn   = document.getElementById('um_cancel_btn');
    var closeBtn    = document.getElementById('um_close_btn');
    var backdropBtn = document.getElementById('um_backdrop_btn');
    var retryBtn    = document.getElementById('um_retry_btn');
    var fileInput   = document.getElementById('modal_audio_file');
    var judulInput  = document.getElementById('modal_judul_rapat');
    var jadwalType  = document.getElementById('modal_jadwal_type');
    var jadwalId    = document.getElementById('modal_jadwal_id');

    // ── Dropzone refs ─────────────────────────────────────────────────────
    var dropzone      = document.getElementById('um_dropzone');
    var dzIdle        = document.getElementById('um_dz_idle');
    var dzSelected    = document.getElementById('um_dz_selected');
    var dzFilename    = document.getElementById('um_dz_filename');
    var dzFilemeta    = document.getElementById('um_dz_filemeta');
    var dzPickBtn     = document.getElementById('um_dz_pick_btn');
    var dzChangeBtn   = document.getElementById('um_dz_change_btn');
    var dzIconWrap    = document.getElementById('um_dz_icon_wrap');

    // ── Confirm dialog refs ───────────────────────────────────────────────
    var confirmDialog    = document.getElementById('um_confirm_dialog');
    var confirmKeepBtn   = document.getElementById('um_confirm_keep_btn');
    var confirmCancelBtn = document.getElementById('um_confirm_cancel_btn');
    var pendingCloseAction = null; // callback yang menunggu konfirmasi

    // ── Confirm dialog: buka & tutup ──────────────────────────────────────
    function openConfirmDialog(onConfirm) {
        pendingCloseAction = onConfirm;
        confirmDialog.showModal();
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons({ attrs: { 'stroke-width': 1.75 } });
        }
    }

    confirmKeepBtn.addEventListener('click', function () {
        pendingCloseAction = null;
        confirmDialog.close();
    });

    confirmCancelBtn.addEventListener('click', function () {
        confirmDialog.close();
        if (typeof pendingCloseAction === 'function') {
            pendingCloseAction();
            pendingCloseAction = null;
        }
    });

    // ── Dropzone: tampilkan state idle atau file-dipilih ──────────────────
    function showDropzoneIdle() {
        dzIdle.classList.remove('hidden');
        dzIdle.classList.add('flex');
        dzSelected.classList.remove('flex');
        dzSelected.classList.add('hidden');
        dropzone.classList.remove('border-success', 'bg-success/5');
        dropzone.classList.add('border-dashed', 'border-base-300', 'bg-base-200/50');
    }

    function showDropzoneSelected(file) {
        var mb  = (file.size / 1048576).toFixed(1);
        var ext = file.name.split('.').pop().toUpperCase();
        dzFilename.textContent = file.name;
        dzFilemeta.textContent = mb + ' MB · ' + ext;
        dzIdle.classList.remove('flex');
        dzIdle.classList.add('hidden');
        dzSelected.classList.remove('hidden');
        dzSelected.classList.add('flex');
        dropzone.classList.remove('border-dashed', 'border-base-300', 'bg-base-200/50');
        dropzone.classList.add('border-success', 'bg-success/5');
    }

    // ── Dropzone: drag-over visual ────────────────────────────────────────
    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (fileInput.files && fileInput.files[0]) return; // sudah ada file, abaikan
        dropzone.classList.add('border-primary', 'bg-primary/5', 'scale-[1.01]');
        dropzone.classList.remove('border-base-300');
        dzIconWrap.classList.add('bg-primary/20');
    });

    dropzone.addEventListener('dragleave', function (e) {
        if (dropzone.contains(e.relatedTarget)) return;
        dropzone.classList.remove('border-primary', 'bg-primary/5', 'scale-[1.01]');
        dropzone.classList.add('border-base-300');
        dzIconWrap.classList.remove('bg-primary/20');
    });

    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('border-primary', 'bg-primary/5', 'scale-[1.01]');
        dropzone.classList.add('border-base-300');
        dzIconWrap.classList.remove('bg-primary/20');
        var files = e.dataTransfer ? e.dataTransfer.files : null;
        if (files && files[0]) {
            // Inject ke file input via DataTransfer
            try {
                var dt = new DataTransfer();
                dt.items.add(files[0]);
                fileInput.files = dt.files;
            } catch (err) { /* browser lama — fallback skip */ }
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    // ── Dropzone: klik area / tombol pilih / tombol ganti ─────────────────
    dropzone.addEventListener('click', function (e) {
        // Jangan trigger kalau klik tombol Ganti (punya handler sendiri)
        if (dzChangeBtn && dzChangeBtn.contains(e.target)) return;
        fileInput.click();
    });

    dropzone.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            fileInput.click();
        }
    });

    dzChangeBtn && dzChangeBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        fileInput.click();
    });

    // ── Server config dari data attributes ───────────────────────────────
    var UPLOAD_TOKEN = modal.dataset.uploadToken || '';
    var START_URL    = modal.dataset.startUrl    || '';
    var CHUNK_URL    = modal.dataset.chunkUrl    || '';
    var CANCEL_URL   = modal.dataset.cancelUrl   || '';
    var COMMIT_URL   = modal.dataset.commitUrl   || '';
    var CHUNK_SIZE   = parseInt(modal.dataset.chunkSize, 10) || 524288;
    var CSRF_NAME    = modal.dataset.csrfName    || '';
    var CSRF_VALUE   = modal.dataset.csrfValue   || '';
    var MAX_SIZE     = parseInt(modal.dataset.maxSize, 10) || 314572800; // 300 MB

    // Format MIME yang diterima
    var ALLOWED_TYPES = ['audio/mpeg', 'audio/mp4', 'audio/x-m4a', 'audio/wav', 'audio/wave',
        'audio/ogg', 'audio/aac', 'audio/flac', 'audio/x-flac', 'video/mp4'];
    var ALLOWED_EXT   = /\.(mp3|m4a|wav|ogg|aac|flac|mp4)$/i;

    // ── Upload state ──────────────────────────────────────────────────────
    var retryDelays           = [0, 1500, 4000, 8000];
    var activeUploadId        = null;
    var uploadStartedAt       = 0;
    var uploadInitialOffset   = 0;
    var currentAcceptedOffset = 0;
    var isCancelling          = false;
    var isUploading           = false;  // blokir backdrop saat aktif
    var lastFile              = null;   // simpan untuk retry

    // ── Lucide icons (dipastikan dirender ulang saat modal dibuka) ────────
    function rerenderIcons() {
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons({ attrs: { 'stroke-width': 1.75 } });
        }
    }

    // ── Buka modal ────────────────────────────────────────────────────────
    var openBtn = document.getElementById('btn_open_upload_modal');
    if (openBtn) {
        openBtn.addEventListener('click', function () {
            resetForm();
            modal.showModal();
            rerenderIcons();
        });
    }

    // ── Fungsi konfirmasi batalkan (menggantikan confirm() browser) ────────
    function confirmCancelUpload(onConfirmed) {
        if (isUploading) {
            openConfirmDialog(function () {
                isCancelling = true;
                if (activeUploadId) doCancel(activeUploadId);
                onConfirmed();
            });
        } else {
            onConfirmed();
        }
    }

    // ── Tutup via tombol X (header) ───────────────────────────────────────
    closeBtn.addEventListener('click', function () {
        confirmCancelUpload(function () { modal.close(); });
    });

    // ── Tutup via tombol Batal ────────────────────────────────────────────
    cancelBtn.addEventListener('click', function () {
        confirmCancelUpload(function () { modal.close(); });
    });

    // ── Backdrop: diblokir saat upload aktif ──────────────────────────────
    backdropBtn.addEventListener('click', function () {
        if (isUploading) return; // blokir senyap — backdrop tidak menutup
        modal.close();
    });

    // ── Tombol Coba Lagi (retry setelah error) ────────────────────────────
    retryBtn.addEventListener('click', function () {
        if (!lastFile) return;
        startUpload(lastFile);
    });

    // ── Cleanup saat dialog ditutup ───────────────────────────────────────
    modal.addEventListener('close', function () {
        if (activeUploadId && !isCancelling) {
            doCancel(activeUploadId);
        }
        resetForm();
    });

    // ── Reset form ke state awal ──────────────────────────────────────────
    function resetForm() {
        activeUploadId        = null;
        isCancelling          = false;
        isUploading           = false;
        lastFile              = null;
        uploadStartedAt       = 0;
        uploadInitialOffset   = 0;
        currentAcceptedOffset = 0;

        document.getElementById('upload_progress_box').classList.add('hidden');
        document.getElementById('upload_warning_banner').classList.add('hidden');
        document.getElementById('um_error_box').classList.add('hidden');
        document.getElementById('audio_preview_container').classList.add('hidden');
        document.getElementById('um_info_note').classList.add('hidden');
        document.getElementById('um_retry_btn').classList.add('hidden');

        fileInput.value    = '';
        judulInput.value   = '';
        jadwalId.value     = '';

        // Reset dropzone ke idle
        showDropzoneIdle();

        submitBtn.disabled = false;
        document.getElementById('um_spinner').classList.add('hidden');
        document.getElementById('um_btn_icon').classList.remove('hidden');
        document.getElementById('um_btn_label').textContent = 'Kirim Rekaman';

        setProgress(0, 'Mengunggah rekaman ke server...');
        document.getElementById('upload_transfer_info').textContent = '0 MB / 0 MB';
        document.getElementById('upload_speed_info').textContent    = '— MB/s';
        document.getElementById('upload_eta_info').textContent      = '';
    }

    // ── Preview audio + update dropzone state — setelah file dipilih ─────
    fileInput.addEventListener('change', function () {
        var container = document.getElementById('audio_preview_container');
        var player    = document.getElementById('audio_preview_player');
        var info      = document.getElementById('audio_preview_info');
        var infoNote  = document.getElementById('um_info_note');

        // Sembunyikan error sebelumnya
        document.getElementById('um_error_box').classList.add('hidden');
        document.getElementById('um_retry_btn').classList.add('hidden');

        if (fileInput.files && fileInput.files[0]) {
            var f = fileInput.files[0];

            // Update dropzone ke state file-dipilih
            showDropzoneSelected(f);

            info.textContent = f.name + ' · ' + (f.size / 1048576).toFixed(1) + ' MB';
            player.src = URL.createObjectURL(f);
            container.classList.remove('hidden');
            infoNote.classList.remove('hidden');
            rerenderIcons();
        } else {
            showDropzoneIdle();
            container.classList.add('hidden');
            infoNote.classList.add('hidden');
            if (player.src) {
                URL.revokeObjectURL(player.src);
                player.src = '';
            }
        }
    });

    // ── Toggle jadwal — reset title auto-fill saat ganti jenis ───────────
    jadwalType.addEventListener('change', function () {
        document.getElementById('group_umum').classList.toggle('hidden', jadwalType.value === 'banmus');
        document.getElementById('group_banmus').classList.toggle('hidden', jadwalType.value !== 'banmus');
        // Reset pilihan dan hapus judul yang mungkin di-auto-fill sebelumnya
        jadwalId.value  = '';
        judulInput.value = '';
    });

    jadwalId.addEventListener('change', function () {
        var opt   = jadwalId.options[jadwalId.selectedIndex];
        var title = opt ? opt.getAttribute('data-title') : null;
        if (title && !judulInput.value) judulInput.value = title;
    });

    // ── Validasi file sebelum upload ──────────────────────────────────────
    function validateFile(file) {
        if (!file) {
            return 'Pilih berkas rekaman audio terlebih dahulu.';
        }
        if (file.size > MAX_SIZE) {
            return 'Berkas terlalu besar (' + (file.size / 1048576).toFixed(1) +
                ' MB). Maksimum 300 MB.';
        }
        var extOk  = ALLOWED_EXT.test(file.name);
        var typeOk = file.type === '' || ALLOWED_TYPES.indexOf(file.type) !== -1;
        if (!extOk && !typeOk) {
            return 'Format berkas tidak didukung. Gunakan MP3, M4A, WAV, OGG, AAC, FLAC, atau MP4.';
        }
        return null; // valid
    }

    // ── Kategorisasi pesan error ──────────────────────────────────────────
    function categorizeError(err) {
        if (!err) return 'Terjadi kesalahan tidak diketahui. Coba lagi.';
        var status = err.status || 0;
        var msg    = err.message || '';

        if (status === 0 || msg.indexOf('Koneksi') !== -1) {
            return 'Koneksi terputus. Periksa jaringan Anda, lalu coba lagi.';
        }
        if (status === 413) {
            return 'Berkas terlalu besar untuk diterima server. Kompres rekaman dan coba lagi.';
        }
        if (status === 422 || status === 400) {
            return msg || 'Format berkas tidak diterima server. Pastikan format audio valid.';
        }
        if (status >= 500) {
            return 'Server sedang mengalami gangguan (' + status + '). Coba lagi dalam beberapa menit.';
        }
        if (msg) return msg;
        return 'Gagal mengunggah rekaman (HTTP ' + status + '). Coba lagi.';
    }

    // ── UI helpers ────────────────────────────────────────────────────────
    function setProgress(pct, msg) {
        document.getElementById('upload_progress_bar').value             = pct;
        document.getElementById('upload_progress_percent').textContent   = Math.round(pct) + '%';
        if (msg !== undefined) document.getElementById('upload_status_text').textContent = msg;
    }

    function showError(msg, allowRetry) {
        isUploading        = false;
        submitBtn.disabled = false;
        document.getElementById('um_spinner').classList.add('hidden');
        document.getElementById('um_btn_icon').classList.remove('hidden');
        document.getElementById('um_btn_label').textContent = 'Kirim Rekaman';
        document.getElementById('upload_warning_banner').classList.add('hidden');

        var retryEl = document.getElementById('um_retry_btn');
        if (allowRetry && lastFile) {
            retryEl.classList.remove('hidden');
        } else {
            retryEl.classList.add('hidden');
        }

        document.getElementById('um_error_text').textContent = msg;
        document.getElementById('um_error_box').classList.remove('hidden');
    }

    function updateProgress(fileSize, acceptedOffset, chunkLoaded) {
        chunkLoaded = chunkLoaded || 0;
        var uploaded    = Math.min(fileSize, acceptedOffset + chunkLoaded);
        var pct         = fileSize > 0 ? (uploaded / fileSize) * 100 : 0;
        var elapsedSec  = (performance.now() - uploadStartedAt) / 1000;
        var transferred = Math.max(0, uploaded - uploadInitialOffset);

        // Kecepatan
        if (elapsedSec >= 0.5 && transferred > 0) {
            var speedMBs = transferred / elapsedSec / 1048576;
            document.getElementById('upload_speed_info').textContent = speedMBs.toFixed(1) + ' MB/s';

            // ETA
            var remaining = fileSize - uploaded;
            if (remaining > 0 && speedMBs > 0) {
                var etaSec = remaining / (speedMBs * 1048576);
                var etaStr = etaSec >= 60
                    ? Math.ceil(etaSec / 60) + ' mnt tersisa'
                    : Math.ceil(etaSec) + ' dtk tersisa';
                document.getElementById('upload_eta_info').textContent = '~' + etaStr;
            } else {
                document.getElementById('upload_eta_info').textContent = '';
            }
        }

        document.getElementById('upload_transfer_info').textContent =
            (uploaded / 1048576).toFixed(1) + ' MB / ' + (fileSize / 1048576).toFixed(1) + ' MB';

        var statusMsg = pct >= 100
            ? 'Berkas diterima! Mendaftarkan ke antrean AI...'
            : 'Mengunggah rekaman ke server...';
        setProgress(pct, statusMsg);
    }

    // ── Crypto helpers ────────────────────────────────────────────────────
    function bytesToHex(buffer) {
        return Array.from(new Uint8Array(buffer))
            .map(function (b) { return b.toString(16).padStart(2, '0'); })
            .join('');
    }

    function sha256(value) {
        if (!window.crypto || !window.crypto.subtle) {
            return Promise.reject(new Error('Browser tidak mendukung checksum upload yang aman.'));
        }
        var p = value instanceof ArrayBuffer ? Promise.resolve(value) : value.arrayBuffer();
        return p.then(function (buf) {
            return window.crypto.subtle.digest('SHA-256', buf);
        }).then(bytesToHex);
    }

    function fileFingerprint(file) {
        var sampleSize = 65536;
        var first      = file.slice(0, Math.min(sampleSize, file.size)).arrayBuffer();
        var lastStart  = Math.max(0, file.size - sampleSize);
        var last       = file.slice(lastStart, file.size).arrayBuffer();
        var meta       = new TextEncoder().encode(
            file.name + '\n' + file.type + '\n' + file.size + '\n' + file.lastModified + '\n'
        );

        return Promise.all([first, last]).then(function (results) {
            var f = results[0], l = results[1];
            var combined = new Uint8Array(meta.byteLength + f.byteLength + l.byteLength);
            combined.set(meta, 0);
            combined.set(new Uint8Array(f), meta.byteLength);
            combined.set(new Uint8Array(l), meta.byteLength + f.byteLength);
            return sha256(combined.buffer);
        });
    }

    // ── XHR helper ────────────────────────────────────────────────────────
    function postJson(url, formData, onProgress) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Accept', 'application/json');
            if (onProgress) xhr.upload.addEventListener('progress', onProgress);

            xhr.addEventListener('load', function () {
                var payload = null;
                try { payload = JSON.parse(xhr.responseText); } catch (e) { /* noop */ }
                if (xhr.status >= 200 && xhr.status < 300 && payload && payload.status === 'success') {
                    resolve(payload);
                } else {
                    reject({ status: xhr.status, payload: payload, message: (payload && payload.message) || '' });
                }
            });
            xhr.addEventListener('error', function () {
                reject({ status: 0, payload: null, message: 'Koneksi terputus saat mengunggah file.' });
            });
            xhr.send(formData);
        });
    }

    function sleep(ms) {
        return new Promise(function (res) { setTimeout(res, ms); });
    }

    function postWithRetry(url, createFormData, onProgress) {
        var lastError = null;
        var attempt   = 0;

        function tryOnce() {
            if (isCancelling) return Promise.reject({ status: 0, message: 'Upload dibatalkan.' });
            var delay = retryDelays[attempt] || 0;
            var p = delay > 0
                ? sleep(delay).then(function () { return postJson(url, createFormData(), onProgress); })
                : postJson(url, createFormData(), onProgress);

            return p.catch(function (err) {
                lastError = err;
                attempt++;
                if (attempt >= retryDelays.length) throw lastError;
                if (err.status > 0 && err.status < 500 && err.status !== 409) throw err;
                return tryOnce();
            });
        }

        return tryOnce();
    }

    // ── Chunked upload ────────────────────────────────────────────────────
    function beginUpload(file, clientKey) {
        return postWithRetry(START_URL, function () {
            var fd = new FormData();
            fd.append('upload_token', UPLOAD_TOKEN);
            fd.append(CSRF_NAME, CSRF_VALUE);
            fd.append('client_key', clientKey);
            fd.append('file_name', file.name);
            fd.append('file_size', String(file.size));
            fd.append('file_type', file.type);
            return fd;
        });
    }

    function uploadInChunks(file) {
        setProgress(0, 'Memeriksa berkas dan mencari sesi upload sebelumnya...');

        return fileFingerprint(file).then(function (clientKey) {
            return beginUpload(file, clientKey).then(function (state) {
                activeUploadId = state.upload_id;
                var offset     = Number(state.offset) || 0;
                var chunkSize  = Math.min(Number(state.chunk_size) || CHUNK_SIZE, CHUNK_SIZE);

                uploadInitialOffset   = offset;
                currentAcceptedOffset = offset;
                uploadStartedAt       = performance.now();
                updateProgress(file.size, offset);

                function nextChunk() {
                    if (isCancelling) return Promise.reject({ status: 0, message: 'Upload dibatalkan.' });
                    if (offset >= file.size) return Promise.resolve(state);

                    var chunk       = file.slice(offset, Math.min(offset + chunkSize, file.size));
                    var chunkOffset = offset;

                    return sha256(chunk).then(function (checksum) {
                        return postWithRetry(CHUNK_URL, function () {
                            var fd = new FormData();
                            fd.append('upload_token', UPLOAD_TOKEN);
                            fd.append(CSRF_NAME, CSRF_VALUE);
                            fd.append('upload_id', state.upload_id);
                            fd.append('offset', String(chunkOffset));
                            fd.append('checksum', checksum);
                            fd.append('chunk', chunk, 'chunk.bin');
                            return fd;
                        }, function (ev) {
                            updateProgress(file.size, currentAcceptedOffset,
                                ev.lengthComputable ? Math.min(ev.loaded, chunk.size) : 0);
                        }).catch(function (err) {
                            if (err.status !== 409) throw err;
                            return beginUpload(file, clientKey).then(function (s) { state = s; return s; });
                        });
                    }).then(function (newState) {
                        state                 = newState;
                        offset                = Number(newState.offset) || 0;
                        currentAcceptedOffset = offset;
                        updateProgress(file.size, offset);
                        return nextChunk();
                    });
                }

                return nextChunk().then(function () {
                    if (!state.completed) {
                        return beginUpload(file, clientKey).then(function (s) { state = s; return state; });
                    }
                    return state;
                }).then(function (finalState) {
                    if (!finalState.completed) {
                        throw { status: 409, message: 'Server belum menandai upload sebagai selesai.' };
                    }
                    return finalState.upload_id;
                });
            });
        });
    }

    function doCancel(uploadId) {
        var fd = new FormData();
        fd.append('upload_token', UPLOAD_TOKEN);
        fd.append(CSRF_NAME, CSRF_VALUE);
        fd.append('upload_id', uploadId);
        postJson(CANCEL_URL, fd).catch(function () { /* best effort */ });
    }

    function commitUpload(uploadId) {
        setProgress(100, 'Berhasil! Mendaftarkan job ke antrean AI...');
        var fd = new FormData();
        fd.append('upload_id', uploadId);
        fd.append('jadwal_type', jadwalType.value);
        fd.append('jadwal_id', jadwalId.value);
        fd.append('judul_rapat', judulInput.value);
        fd.append(CSRF_NAME, CSRF_VALUE);
        return postJson(COMMIT_URL, fd);
    }

    // ── Mulai proses upload (dipanggil dari submit dan retry) ─────────────
    function startUpload(file) {
        // Validasi client-side sebelum menyentuh server
        var validationError = validateFile(file);
        if (validationError) {
            showError(validationError, false);
            return;
        }

        lastFile     = file;
        isCancelling = false;
        isUploading  = true;
        activeUploadId = null;

        document.getElementById('um_error_box').classList.add('hidden');
        document.getElementById('um_retry_btn').classList.add('hidden');
        document.getElementById('upload_progress_box').classList.remove('hidden');
        document.getElementById('upload_warning_banner').classList.remove('hidden');

        submitBtn.disabled = true;
        document.getElementById('um_spinner').classList.remove('hidden');
        document.getElementById('um_btn_icon').classList.add('hidden');
        document.getElementById('um_btn_label').textContent = 'Mengunggah...';

        uploadInChunks(file)
            .then(function (uploadId) { return commitUpload(uploadId); })
            .then(function (res) {
                isUploading    = false;
                activeUploadId = null;
                document.getElementById('upload_warning_banner').classList.add('hidden');
                setProgress(100, 'Selesai! Mengalihkan ke halaman notulensi...');
                if (res.redirect) {
                    setTimeout(function () { window.location.href = res.redirect; }, 500);
                }
            })
            .catch(function (err) {
                activeUploadId = null;
                if (!isCancelling) {
                    showError(categorizeError(err), true);
                } else {
                    isUploading = false;
                    document.getElementById('upload_warning_banner').classList.add('hidden');
                }
            });
    }

    // ── Submit ────────────────────────────────────────────────────────────
    submitBtn.addEventListener('click', function () {
        if (!fileInput.files || !fileInput.files[0]) {
            showError('Pilih berkas rekaman audio terlebih dahulu.', false);
            return;
        }
        startUpload(fileInput.files[0]);
    });
})();
</script>
<?= $this->endSection() ?>
