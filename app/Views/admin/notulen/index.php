<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="page-title">Notulensi & Risalah AI</h1>
        <p class="mt-1 text-sm text-base-content/60">Transkripsi rekaman rapat otomatis dan penyusunan risalah resmi menggunakan Google Gemini AI.</p>
    </div>
    <button type="button" onclick="upload_modal.showModal()" class="btn btn-primary btn-sm w-full gap-1.5 sm:w-auto">
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
                                    <?php elseif ($isInProgress): ?>
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-semibold text-warning">
                                                    <?= $job['status'] === 'chunking' ? 'Memotong Audio' : ($job['status'] === 'transcribing' ? 'Transkripsi' : 'Menyusun Risalah') ?>
                                                </span>
                                                <span class="font-mono"><?= (int) $job['progress_percent'] ?>%</span>
                                            </div>
                                            <progress class="progress progress-warning w-full" value="<?= (int) $job['progress_percent'] ?>" max="100"></progress>
                                            <div class="truncate text-[11px] text-base-content/60" title="<?= esc($job['current_step']) ?>">
                                                <?= esc($job['current_step']) ?>
                                            </div>
                                        </div>
                                    <?php elseif ($job['status'] === 'queued'): ?>
                                        <span class="badge badge-info badge-sm font-semibold">Dalam Antrean</span>
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

<!-- Modal Unggah Rekaman Rapat -->
<dialog id="upload_modal" class="modal">
    <div class="modal-box max-w-xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
        </form>

        <h3 class="text-lg font-bold">Unggah Rekaman Rapat Baru</h3>
        <p class="mt-1 text-xs text-base-content/60">Pilih berkas audio rekaman rapat untuk ditranskripsikan dan disusun risalahnya secara otomatis oleh AI.</p>

        <form method="post" action="<?= base_url('admin/notulen/upload') ?>" enctype="multipart/form-data" class="mt-4 space-y-4">
            <?= csrf_field() ?>

            <!-- Pilihan Agenda Terkait (Opsional) -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-xs">Hubungkan dengan Agenda Terjadwal (Opsional)</span>
                </label>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <div>
                        <select name="jadwal_type" id="modal_jadwal_type" class="select select-bordered select-sm w-full text-xs" onchange="toggleScheduleOptions()">
                            <option value="umum">Jadwal Umum</option>
                            <option value="banmus">Jadwal Banmus</option>
                        </select>
                    </div>
                    <div>
                        <select name="jadwal_id" id="modal_jadwal_id" class="select select-bordered select-sm w-full text-xs" onchange="onScheduleSelected(this)">
                            <option value="">-- Tanpa Relasi Agenda --</option>
                            <optgroup label="Jadwal Umum Terdekat" id="group_umum">
                                <?php foreach ($generalSchedules as $gs): ?>
                                    <option value="<?= $gs['id'] ?>" data-title="<?= esc($gs['judul']) ?>" data-date="<?= esc($gs['tanggal']) ?>">
                                        <?= esc($gs['tanggal']) ?> - <?= esc($gs['judul']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Agenda Banmus Terdekat" id="group_banmus" class="hidden">
                                <?php foreach ($banmusItems as $bi): ?>
                                    <option value="<?= $bi['id'] ?>" data-title="<?= esc($bi['agenda']) ?>" data-date="<?= esc($bi['tanggal']) ?>">
                                        <?= esc($bi['tanggal']) ?> - <?= esc($bi['agenda']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Judul Rapat -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-xs">Judul / Topik Rapat</span>
                </label>
                <input type="text" name="judul_rapat" id="modal_judul_rapat" placeholder="Contoh: Rapat Dengar Pendapat Komisi I terkait Evaluasi Kinerja OPD" class="input input-bordered input-sm w-full" />
                <label class="label">
                    <span class="label-text-alt text-base-content/50">Kosongkan jika ingin menggunakan judul dari agenda yang dipilih atau nama file.</span>
                </label>
            </div>

            <!-- Input File Rekaman Audio -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold text-xs">Berkas Rekaman Audio (.mp3, .m4a, .wav, .ogg) <span class="text-error">*</span></span>
                    <span class="label-text-alt text-base-content/60">Maks. 300 MB</span>
                </label>
                <input type="file" name="audio_file" id="modal_audio_file" accept=".mp3,.m4a,.wav,.ogg,.aac,.flac,.mp4,audio/*" required class="file-input file-input-bordered file-input-sm w-full" onchange="previewAudioClientSide(this)" />
            </div>

            <!-- Client-Side Audio Player Preview -->
            <div id="audio_preview_container" class="hidden rounded-lg bg-base-200 p-3">
                <div class="mb-1.5 flex items-center justify-between text-xs font-semibold">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="volume-2" class="h-4 w-4 text-primary"></i> Pratinjau Audio
                    </span>
                    <span id="audio_preview_info" class="font-mono text-[11px] text-base-content/60"></span>
                </div>
                <audio id="audio_preview_player" controls class="w-full h-8"></audio>
            </div>

            <!-- Info Alert -->
            <div class="alert alert-info py-2 text-xs">
                <i data-lucide="info" class="h-4 w-4 shrink-0"></i>
                <span>Proses pemotongan 30 menit dan transkripsi AI berjalan di latar belakang secara sekuensial. Anda dapat menutup halaman ini setelah unggahan selesai.</span>
            </div>

            <div class="modal-action mt-6">
                <button type="button" onclick="upload_modal.close()" class="btn btn-ghost btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                    <i data-lucide="upload" class="h-4 w-4"></i>
                    Mulai Proses AI
                </button>
            </div>
        </form>
    </div>
</dialog>

<script>
function toggleScheduleOptions() {
    const type = document.getElementById('modal_jadwal_type').value;
    const groupUmum = document.getElementById('group_umum');
    const groupBanmus = document.getElementById('group_banmus');
    const select = document.getElementById('modal_jadwal_id');

    select.value = '';

    if (type === 'banmus') {
        groupUmum.classList.add('hidden');
        groupBanmus.classList.remove('hidden');
    } else {
        groupUmum.classList.remove('hidden');
        groupBanmus.classList.add('hidden');
    }
}

function onScheduleSelected(select) {
    const selectedOption = select.options[select.selectedIndex];
    const title = selectedOption.getAttribute('data-title');
    const inputTitle = document.getElementById('modal_judul_rapat');
    if (title && inputTitle && !inputTitle.value) {
        inputTitle.value = title;
    }
}

function previewAudioClientSide(input) {
    const container = document.getElementById('audio_preview_container');
    const player = document.getElementById('audio_preview_player');
    const info = document.getElementById('audio_preview_info');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
        info.textContent = `${file.name} (${sizeMb} MB)`;

        const objectUrl = URL.createObjectURL(file);
        player.src = objectUrl;
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
        player.src = '';
    }
}
</script>

<?= $this->endSection() ?>
