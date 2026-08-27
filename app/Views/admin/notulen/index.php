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

<!-- Filter Status & Pencarian -->
<div class="card card-border mb-4 bg-base-100 shadow-sm">
    <div class="card-body p-4 sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <!-- Tabs Filter Status -->
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="<?= base_url('admin/notulen') ?>" class="btn btn-xs <?= empty($currentStatus) || $currentStatus === 'all' ? 'btn-primary' : 'btn-ghost' ?>">
                    Semua <span class="badge badge-sm ml-1"><?= ($statusCounts['in_progress'] + $statusCounts['completed'] + $statusCounts['queued'] + $statusCounts['failed'] + $statusCounts['cancelled']) ?></span>
                </a>
                <a href="<?= base_url('admin/notulen?status=in_progress') ?>" class="btn btn-xs <?= $currentStatus === 'in_progress' ? 'btn-warning' : 'btn-ghost' ?>">
                    Memproses <span class="badge badge-sm ml-1"><?= $statusCounts['in_progress'] ?></span>
                </a>
                <a href="<?= base_url('admin/notulen?status=completed') ?>" class="btn btn-xs <?= $currentStatus === 'completed' ? 'btn-success' : 'btn-ghost' ?>">
                    Selesai <span class="badge badge-sm ml-1"><?= $statusCounts['completed'] ?></span>
                </a>
                <a href="<?= base_url('admin/notulen?status=queued') ?>" class="btn btn-xs <?= $currentStatus === 'queued' ? 'btn-info' : 'btn-ghost' ?>">
                    Antrean <span class="badge badge-sm ml-1"><?= $statusCounts['queued'] ?></span>
                </a>
                <a href="<?= base_url('admin/notulen?status=failed') ?>" class="btn btn-xs <?= $currentStatus === 'failed' ? 'btn-error' : 'btn-ghost' ?>">
                    Gagal <span class="badge badge-sm ml-1"><?= $statusCounts['failed'] ?></span>
                </a>
                <a href="<?= base_url('admin/notulen?status=cancelled') ?>" class="btn btn-xs <?= $currentStatus === 'cancelled' ? 'btn-neutral' : 'btn-ghost' ?>">
                    Dibatalkan <span class="badge badge-sm ml-1"><?= $statusCounts['cancelled'] ?></span>
                </a>
            </div>

            <!-- Form Pencarian -->
            <form method="get" action="<?= base_url('admin/notulen') ?>" class="flex w-full items-center gap-2 lg:w-72">
                <?php if (! empty($currentStatus)): ?>
                    <input type="hidden" name="status" value="<?= esc($currentStatus) ?>">
                <?php endif; ?>
                <div class="relative w-full">
                    <input type="text" name="q" value="<?= esc($searchQuery) ?>" placeholder="Cari nama rekaman..." class="input input-sm input-bordered w-full pr-8" />
                    <?php if (! empty($searchQuery)): ?>
                        <a href="<?= base_url('admin/notulen' . (! empty($currentStatus) ? '?status=' . esc($currentStatus) : '')) ?>" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-base-content/40 hover:text-base-content">✕</a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-square btn-sm btn-ghost" title="Cari">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Tabel Daftar Notulen & Antrean -->
<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-3 sm:px-5">
        <h2 class="card-title text-base font-bold">
            <i data-lucide="mic" class="h-5 w-5 text-primary"></i>
            Daftar Rekaman & Risalah Rapat
        </h2>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto">
            <table class="table table-zebra table-md w-full">
                <thead>
                    <tr class="bg-base-200">
                        <th class="w-12 text-center">No</th>
                        <th>Rapat & Rekaman</th>
                        <th class="w-64">Status & Progres AI</th>
                        <th>Status Risalah</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jobs)): ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-base-content/60">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i data-lucide="inbox" class="h-8 w-8 text-base-content/30"></i>
                                    <span>Belum ada rekaman notulensi rapat. Silakan unggah file rekaman baru.</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jobs as $idx => $job): ?>
                            <?php
                            $minutes = $minutesMap[$job['id']] ?? null;
                            $judulRapat = ! empty($minutes['judul_rapat']) ? $minutes['judul_rapat'] : $job['audio_filename'];
                            $tanggalRapat = ! empty($minutes['tanggal_rapat']) ? $minutes['tanggal_rapat'] : substr((string) $job['created_at'], 0, 10);
                            $isInProgress = in_array($job['status'], ['chunking', 'transcribing', 'summarizing'], true);
                            ?>
                            <tr class="transition-colors hover:bg-base-200/40">
                                <td class="text-center text-xs text-base-content/60">
                                    <?= (int) ($idx + 1) ?>
                                </td>
                                <td>
                                    <div class="font-bold text-base-content">
                                        <a href="<?= base_url('admin/notulen/' . $job['id']) ?>" class="hover:text-primary hover:underline">
                                            <?= esc($judulRapat) ?>
                                        </a>
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-base-content/60">
                                        <span><?= esc($tanggalRapat) ?></span>
                                        <span>•</span>
                                        <span class="font-mono text-xs"><?= esc($job['audio_filename']) ?></span>
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
                                <td>
                                    <?php if ($job['status'] === 'completed'): ?>
                                        <div class="flex items-center gap-1.5">
                                            <span class="badge badge-success badge-sm font-semibold">Selesai</span>
                                            <span class="text-xs text-base-content/60"><?= (int) $job['total_chunks'] ?> segmen</span>
                                        </div>
                                    <?php elseif ($isInProgress || $job['status'] === 'queued'): ?>
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-semibold <?= $job['status'] === 'queued' ? 'text-info' : 'text-warning' ?>">
                                                    <?= $job['status'] === 'chunking' ? 'Memotong Audio' : ($job['status'] === 'transcribing' ? 'Transkripsi' : ($job['status'] === 'summarizing' ? 'Menyusun Risalah' : 'Dalam Antrean AI')) ?>
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
                                <td>
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
                                        <span class="text-xs text-base-content/40">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="<?= base_url('admin/notulen/' . $job['id']) ?>" class="btn btn-ghost btn-xs gap-1" title="Buka Detail">
                                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                            Buka
                                        </a>

                                        <div class="dropdown dropdown-end">
                                            <button tabindex="0" class="btn btn-ghost btn-xs btn-square">
                                                <i data-lucide="more-vertical" class="h-4 w-4"></i>
                                            </button>
                                            <ul tabindex="0" class="dropdown-content menu rounded-box z-10 w-48 bg-base-100 p-2 shadow-lg border border-base-300 text-xs">
                                                <?php if (in_array($job['status'], ['failed', 'cancelled'], true)): ?>
                                                    <li>
                                                        <form method="post" action="<?= base_url('admin/notulen/retry/' . $job['id']) ?>">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="text-primary gap-2">
                                                                <i data-lucide="rotate-cw" class="h-3.5 w-3.5"></i> Proses Ulang (Resume)
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($isInProgress || $job['status'] === 'queued'): ?>
                                                    <li>
                                                        <form method="post" action="<?= base_url('admin/notulen/cancel/' . $job['id']) ?>" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan proses job ini?')">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="text-warning gap-2">
                                                                <i data-lucide="ban" class="h-3.5 w-3.5"></i> Batalkan Proses
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($job['status'] === 'completed'): ?>
                                                    <li>
                                                        <form method="post" action="<?= base_url('admin/notulen/delete-audio/' . $job['id']) ?>" onsubmit="return confirm('Hapus berkas audio rekaman untuk menghemat ruang disk? Transkrip dan risalah tetap tersimpan.')">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="text-base-content/80 gap-2">
                                                                <i data-lucide="trash" class="h-3.5 w-3.5"></i> Bersihkan Audio Lokal
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <li>
                                                    <form method="post" action="<?= base_url('admin/notulen/destroy/' . $job['id']) ?>" onsubmit="return confirm('Hapus permanen notulen rapat ini beserta seluruh transkrip dan berkasnya?')">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="text-error gap-2">
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
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pager): ?>
            <div class="border-t border-base-300 p-4">
                <?= $pager->links('default', 'default_full') ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal Upload DaisyUI -->
<dialog id="modal_upload_notulen" class="modal"
    data-upload-token="<?= esc($audioUploadToken) ?>"
    data-start-url="<?= base_url('admin/notulen/audio-upload/start') ?>"
    data-chunk-url="<?= base_url('admin/notulen/audio-upload/chunk') ?>"
    data-cancel-url="<?= base_url('admin/notulen/audio-upload/cancel') ?>"
    data-commit-url="<?= base_url('admin/notulen/upload') ?>"
    data-chunk-size="<?= (int) $audioChunkSize ?>"
    data-csrf-name="<?= csrf_token() ?>"
    data-csrf-value="<?= csrf_hash() ?>">
    <div class="modal-box w-full max-w-lg">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="text-lg font-bold">Unggah Rekaman Rapat Baru</h3>
        <p class="mt-1 text-xs text-base-content/60">Pilih berkas audio rekaman rapat untuk ditranskripsikan dan disusun risalahnya secara otomatis oleh AI.</p>

        <div class="mt-4 space-y-4">

            <div id="um_error_box" class="hidden alert alert-error py-2 text-xs"></div>

            <div class="form-control">
                <label class="label"><span class="label-text font-semibold text-xs">Hubungkan dengan Agenda Terjadwal (Opsional)</span></label>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <select id="modal_jadwal_type" class="select select-bordered select-sm w-full text-xs">
                        <option value="umum">Jadwal Umum</option>
                        <option value="banmus">Jadwal Banmus</option>
                    </select>
                    <select id="modal_jadwal_id" class="select select-bordered select-sm w-full text-xs">
                        <option value="">-- Tanpa Relasi Agenda --</option>
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

            <div class="form-control">
                <label class="label"><span class="label-text font-semibold text-xs">Judul / Topik Rapat</span></label>
                <input type="text" id="modal_judul_rapat" placeholder="Contoh: Rapat Dengar Pendapat Komisi I" class="input input-bordered input-sm w-full" />
                <label class="label"><span class="label-text-alt text-base-content/50">Kosongkan untuk menggunakan judul agenda atau nama file.</span></label>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-xs">Berkas Rekaman Audio (.mp3, .m4a, .wav, .ogg) <span class="text-error">*</span></span>
                    <span class="label-text-alt text-base-content/60">Maks. 300 MB</span>
                </label>
                <input type="file" id="modal_audio_file" accept=".mp3,.m4a,.wav,.ogg,.aac,.flac,.mp4,audio/*" class="file-input file-input-bordered file-input-sm w-full" />
            </div>

            <div id="audio_preview_container" class="hidden rounded-lg bg-base-200 p-3">
                <div class="mb-1.5 flex items-center justify-between text-xs font-semibold">
                    <span>Pratinjau Audio</span>
                    <span id="audio_preview_info" class="font-mono text-[11px] text-base-content/60"></span>
                </div>
                <audio id="audio_preview_player" controls class="w-full h-8"></audio>
            </div>

            <div id="upload_progress_box" class="hidden rounded-box bg-base-200 border border-primary/30 p-4 space-y-2">
                <div class="flex items-center justify-between text-xs font-bold">
                    <div class="flex items-center gap-2 text-primary">
                        <span class="loading loading-spinner loading-xs"></span>
                        <span id="upload_status_text">Mengunggah rekaman audio ke server...</span>
                    </div>
                    <span id="upload_progress_percent" class="font-mono text-primary font-bold">0%</span>
                </div>
                <progress id="upload_progress_bar" class="progress progress-primary w-full h-2.5" value="0" max="100"></progress>
                <div class="flex items-center justify-between text-[11px] text-base-content/60 font-mono">
                    <span id="upload_transfer_info">0 MB / 0 MB</span>
                    <span id="upload_speed_info">0 MB/s</span>
                </div>
            </div>

            <div class="alert alert-info py-2 text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-4 w-4 shrink-0 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Setelah unggahan 100%, Anda akan otomatis dialihkan ke halaman kerja notulensi.</span>
            </div>

        </div>

        <div class="modal-action mt-4">
            <button type="button" id="um_cancel_btn" class="btn btn-ghost btn-sm">Batal</button>
            <button type="button" id="um_submit_btn" class="btn btn-primary btn-sm gap-1.5">
                <span id="um_spinner" class="loading loading-spinner loading-xs hidden"></span>
                <span id="um_btn_label">Mulai Proses AI</span>
            </button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>tutup</button></form>
</dialog>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script {csp-script-nonce}>
(function () {
    var modal      = document.getElementById('modal_upload_notulen');
    var submitBtn  = document.getElementById('um_submit_btn');
    var cancelBtn  = document.getElementById('um_cancel_btn');
    var fileInput  = document.getElementById('modal_audio_file');
    var jadwalType = document.getElementById('modal_jadwal_type');
    var jadwalId   = document.getElementById('modal_jadwal_id');

    var UPLOAD_TOKEN  = modal.dataset.uploadToken  || '';
    var START_URL     = modal.dataset.startUrl     || '';
    var CHUNK_URL     = modal.dataset.chunkUrl     || '';
    var CANCEL_URL    = modal.dataset.cancelUrl    || '';
    var COMMIT_URL    = modal.dataset.commitUrl    || '';
    var CHUNK_SIZE    = parseInt(modal.dataset.chunkSize, 10) || 524288;
    var CSRF_NAME     = modal.dataset.csrfName     || '';
    var CSRF_VALUE    = modal.dataset.csrfValue    || '';

    var retryDelays = [0, 1500, 4000, 8000];
    var activeUploadId = null;
    var uploadStartedAt = 0;
    var uploadInitialOffset = 0;
    var currentAcceptedOffset = 0;
    var isCancelling = false;

    // ── Buka / tutup modal ────────────────────────────────────────────────
    document.getElementById('btn_open_upload_modal').addEventListener('click', function () {
        modal.showModal();
    });

    cancelBtn.addEventListener('click', function () {
        if (activeUploadId) {
            isCancelling = true;
            doCancel(activeUploadId);
        }
        modal.close();
    });

    modal.addEventListener('close', function () {
        if (activeUploadId && !isCancelling) {
            doCancel(activeUploadId);
        }
        resetForm();
    });

    function resetForm() {
        activeUploadId = null;
        isCancelling   = false;
        document.getElementById('upload_progress_box').classList.add('hidden');
        document.getElementById('um_error_box').classList.add('hidden');
        document.getElementById('audio_preview_container').classList.add('hidden');
        fileInput.value = '';
        document.getElementById('modal_judul_rapat').value = '';
        jadwalId.value = '';
        submitBtn.disabled = false;
        document.getElementById('um_spinner').classList.add('hidden');
        document.getElementById('um_btn_label').textContent = 'Mulai Proses AI';
        setProgress(0, 'Mengunggah rekaman audio ke server...');
    }

    // ── Preview audio ─────────────────────────────────────────────────────
    fileInput.addEventListener('change', function () {
        var container = document.getElementById('audio_preview_container');
        var player    = document.getElementById('audio_preview_player');
        var info      = document.getElementById('audio_preview_info');
        if (fileInput.files && fileInput.files[0]) {
            var f = fileInput.files[0];
            info.textContent = f.name + ' (' + (f.size / 1048576).toFixed(1) + ' MB)';
            player.src = URL.createObjectURL(f);
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
            player.src = '';
        }
    });

    // ── Toggle jadwal ─────────────────────────────────────────────────────
    jadwalType.addEventListener('change', function () {
        document.getElementById('group_umum').classList.toggle('hidden', jadwalType.value === 'banmus');
        document.getElementById('group_banmus').classList.toggle('hidden', jadwalType.value !== 'banmus');
        jadwalId.value = '';
    });

    jadwalId.addEventListener('change', function () {
        var opt   = jadwalId.options[jadwalId.selectedIndex];
        var title = opt.getAttribute('data-title');
        var input = document.getElementById('modal_judul_rapat');
        if (title && input && !input.value) input.value = title;
    });

    // ── UI helpers ────────────────────────────────────────────────────────
    function setProgress(pct, msg) {
        document.getElementById('upload_progress_bar').value        = pct;
        document.getElementById('upload_progress_percent').textContent = Math.round(pct) + '%';
        if (msg !== undefined) document.getElementById('upload_status_text').textContent = msg;
    }

    function showError(msg) {
        submitBtn.disabled = false;
        document.getElementById('um_spinner').classList.add('hidden');
        document.getElementById('um_btn_label').textContent = 'Mulai Proses AI';
        var ebox = document.getElementById('um_error_box');
        ebox.textContent = msg;
        ebox.classList.remove('hidden');
    }

    function updateProgress(fileSize, acceptedOffset, chunkLoaded) {
        chunkLoaded = chunkLoaded || 0;
        var uploaded = Math.min(fileSize, acceptedOffset + chunkLoaded);
        var pct      = fileSize > 0 ? (uploaded / fileSize) * 100 : 0;
        var elapsed  = (performance.now() - uploadStartedAt) / 1000;
        var transferred = Math.max(0, uploaded - uploadInitialOffset);

        if (elapsed >= 0.5 && transferred > 0) {
            document.getElementById('upload_speed_info').textContent =
                (transferred / elapsed / 1048576).toFixed(1) + ' MB/s';
        }
        document.getElementById('upload_transfer_info').textContent =
            (uploaded / 1048576).toFixed(1) + ' MB / ' + (fileSize / 1048576).toFixed(1) + ' MB';

        setProgress(pct, pct >= 100 ? 'Berkas diterima! Menginisialisasi antrean AI...' : 'Mengunggah rekaman audio ke server...');
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
        var first = file.slice(0, Math.min(sampleSize, file.size)).arrayBuffer();
        var lastStart = Math.max(0, file.size - sampleSize);
        var last  = file.slice(lastStart, file.size).arrayBuffer();
        var meta  = new TextEncoder().encode(file.name + '\n' + file.type + '\n' + file.size + '\n' + file.lastModified + '\n');

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
        var attempt = 0;

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

    // ── Chunked upload logic ───────────────────────────────────────────────
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
        setProgress(0, 'Memeriksa file dan mencari upload sebelumnya...');

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
                        state  = newState;
                        offset = Number(newState.offset) || 0;
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
        fd.append('judul_rapat', document.getElementById('modal_judul_rapat').value);
        fd.append(CSRF_NAME, CSRF_VALUE);
        return postJson(COMMIT_URL, fd);
    }

    // ── Submit ────────────────────────────────────────────────────────────
    submitBtn.addEventListener('click', function () {
        if (!fileInput.files || !fileInput.files[0]) {
            alert('Silakan pilih berkas rekaman audio terlebih dahulu.');
            return;
        }

        isCancelling = false;
        document.getElementById('um_error_box').classList.add('hidden');
        document.getElementById('upload_progress_box').classList.remove('hidden');
        submitBtn.disabled = true;
        document.getElementById('um_spinner').classList.remove('hidden');
        document.getElementById('um_btn_label').textContent = 'Mengunggah...';

        var file = fileInput.files[0];

        uploadInChunks(file)
            .then(function (uploadId) { return commitUpload(uploadId); })
            .then(function (res) {
                activeUploadId = null;
                if (res.redirect) {
                    setTimeout(function () { window.location.href = res.redirect; }, 300);
                }
            })
            .catch(function (err) {
                activeUploadId = null;
                if (!isCancelling) {
                    var msg = (err && err.message) ? err.message : 'Gagal mengunggah rekaman. Periksa koneksi dan coba lagi.';
                    showError(msg);
                }
            });
    });
})();
</script>
<?= $this->endSection() ?>
