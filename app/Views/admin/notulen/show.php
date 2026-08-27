<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isInProgress = in_array($job['status'], ['chunking', 'transcribing', 'summarizing'], true);
$judulRapat = ! empty($minutes['judul_rapat']) ? $minutes['judul_rapat'] : $job['audio_filename'];
$tanggalRapat = ! empty($minutes['tanggal_rapat']) ? $minutes['tanggal_rapat'] : substr((string) $job['created_at'], 0, 10);
$durationMin = ! empty($job['audio_duration']) ? round($job['audio_duration'] / 60) : null;
?>

<!-- Header Halaman & Aksi -->
<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div class="flex min-w-0 items-start gap-2">
        <a href="<?= base_url('admin/notulen') ?>" class="btn btn-ghost btn-sm btn-square shrink-0" title="Kembali ke daftar notulen">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
        </a>
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="page-title truncate"><?= esc($judulRapat) ?></h1>
                <?php if ($minutes && $minutes['status_verifikasi'] === 'final'): ?>
                    <span class="badge badge-success badge-sm gap-1">
                        <i data-lucide="check-check" class="h-3 w-3"></i> Final
                    </span>
                <?php elseif ($minutes): ?>
                    <span class="badge badge-warning badge-sm gap-1">
                        <i data-lucide="file-edit" class="h-3 w-3"></i> Draft
                    </span>
                <?php endif; ?>
            </div>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-base-content/60">
                <span>Tanggal Rapat: <?= esc($tanggalRapat) ?></span>
                <span>•</span>
                <span>File: <span class="font-mono"><?= esc($job['audio_filename']) ?></span></span>
                <?php if ($job['jadwal_type'] === 'banmus'): ?>
                    <span class="badge badge-secondary badge-xs">Banmus</span>
                <?php else: ?>
                    <span class="badge badge-primary badge-xs">Jadwal Umum</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi Header -->
    <div class="flex flex-wrap items-center gap-2">
        <?php if ($minutes && ! empty($minutes['id'])): ?>
            <a href="<?= base_url('admin/notulen/export-pdf/' . $minutes['id']) ?>" target="_blank" class="btn btn-outline btn-sm gap-1.5">
                <i data-lucide="printer" class="h-4 w-4"></i>
                Cetak / Ekspor PDF
            </a>

            <?php if ($minutes['status_verifikasi'] !== 'final'): ?>
                <form method="post" action="<?= base_url('admin/notulen/finalize/' . $minutes['id']) ?>" onsubmit="return confirm('Finalisasi risalah rapat? Risalah yang sudah final akan dapat dibaca langsung oleh anggota dewan melalui aplikasi mobile.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success btn-sm gap-1.5 text-white">
                        <i data-lucide="check-circle" class="h-4 w-4"></i>
                        Finalisasi Risalah
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (! empty($transcripts['full_text'])): ?>
            <a href="<?= base_url('admin/notulen/download-transcript/' . $job['id']) ?>" class="btn btn-ghost btn-sm gap-1.5" title="Unduh transkrip percakapan utuh dalam format .txt">
                <i data-lucide="download" class="h-4 w-4"></i>
                Unduh Transkrip (.txt)
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Widget Pemantau Progres Real-time & Live Log (Selalu Ditampilkan) -->
<div id="live_progress_card" class="card card-border mb-5 bg-base-100 shadow-sm <?= $job['status'] === 'completed' ? 'border-success/40' : ($job['status'] === 'failed' ? 'border-error/40' : ($job['status'] === 'cancelled' ? 'border-neutral/40' : 'border-warning/40')) ?>">
    <div class="card-body p-4 sm:p-5">
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <?php if ($isInProgress || $job['status'] === 'queued'): ?>
                        <span class="loading loading-spinner loading-sm text-warning"></span>
                    <?php elseif ($job['status'] === 'completed'): ?>
                        <i data-lucide="check-circle-2" class="h-5 w-5 text-success"></i>
                    <?php elseif ($job['status'] === 'failed'): ?>
                        <i data-lucide="alert-triangle" class="h-5 w-5 text-error"></i>
                    <?php else: ?>
                        <i data-lucide="ban" class="h-5 w-5 text-base-content/50"></i>
                    <?php endif; ?>

                    <span class="font-bold text-sm text-base-content" id="live_status_title">
                        <?= $job['status'] === 'completed' ? 'Transkripsi & Risalah Selesai' : ($job['status'] === 'failed' ? 'Pemrosesan Mengalami Kendala' : ($job['status'] === 'cancelled' ? 'Proses Dibatalkan' : ($job['status'] === 'chunking' ? 'Memotong Audio Rekaman...' : ($job['status'] === 'transcribing' ? 'Transkripsi Audio Berjalan...' : ($job['status'] === 'summarizing' ? 'Menyusun Risalah Rapat...' : 'Dalam Antrean Pemrosesan...'))))) ?>
                    </span>
                </div>
                <span class="font-mono font-bold text-sm <?= $job['status'] === 'completed' ? 'text-success' : ($job['status'] === 'failed' ? 'text-error' : 'text-warning') ?>" id="live_progress_percent"><?= (int) $job['progress_percent'] ?>%</span>
            </div>

            <progress id="live_progress_bar" class="progress <?= $job['status'] === 'completed' ? 'progress-success' : ($job['status'] === 'failed' ? 'progress-error' : ($job['status'] === 'cancelled' ? 'progress-neutral' : 'progress-warning')) ?> w-full h-2.5" value="<?= (int) $job['progress_percent'] ?>" max="100"></progress>

            <div class="flex items-center justify-between text-xs text-base-content/60">
                <span id="live_current_step" class="truncate"><?= esc($job['current_step']) ?: '-' ?></span>
                <span id="live_chunk_info" class="font-mono shrink-0 ml-2">
                    <?= (int) $job['completed_chunks'] ?> / <?= (int) $job['total_chunks'] ?> segmen
                </span>
            </div>

            <!-- Live Process Log Console -->
            <div class="rounded-box bg-neutral text-neutral-content p-3 font-mono text-xs space-y-1 overflow-hidden">
                <div class="flex items-center justify-between border-b border-neutral-content/15 pb-2 text-[11px] text-neutral-content/70">
                    <span class="flex items-center gap-1.5">
                        <span id="live_log_dot" class="h-2 w-2 rounded-full <?= $isInProgress || $job['status'] === 'queued' ? 'bg-warning animate-pulse' : ($job['status'] === 'completed' ? 'bg-success' : 'bg-error') ?>"></span>
                        LOG AKTIVITAS PROSES AI
                    </span>
                    <span id="live_log_time" class="font-mono"><?= date('H:i:s') ?> WITA</span>
                </div>
                <div id="live_log_stream" class="space-y-1 pt-1 max-h-32 overflow-y-auto leading-relaxed text-[11.5px]">
                    <div class="text-neutral-content/60">[<?= date('H:i:s') ?>] Berkas: <?= esc($job['audio_filename']) ?> (<?= round($job['audio_size'] / (1024 * 1024), 2) ?> MB)</div>
                    <?php if ($job['status'] === 'completed'): ?>
                        <div class="text-success">[<?= date('H:i:s') ?>] Transkripsi <?= (int) $job['total_chunks'] ?> segmen dan risalah selesai.</div>
                    <?php elseif ($job['status'] === 'failed'): ?>
                        <div class="text-error">[<?= date('H:i:s') ?>] Error: <?= esc($job['error_message'] ?? 'Proses gagal.') ?></div>
                    <?php elseif (! empty($job['current_step'])): ?>
                        <div class="text-info">[<?= date('H:i:s') ?>] <?= esc($job['current_step']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grid Metadata & Konten Utama -->
<div class="grid grid-cols-1 gap-5 lg:grid-cols-4">
    <!-- Kolom Kiri: Metadata Rekaman & Status -->
    <div class="space-y-4 lg:col-span-1">
        <div class="card card-border bg-base-100 shadow-sm">
            <div class="card-body p-4">
                <h3 class="font-bold text-xs uppercase tracking-wider text-base-content/50 mb-3">Informasi Rekaman</h3>
                <div class="space-y-3 text-xs">
                    <div>
                        <span class="block text-base-content/50">Nama Berkas:</span>
                        <span class="font-mono font-semibold break-all"><?= esc($job['audio_filename']) ?></span>
                    </div>
                    <div>
                        <span class="block text-base-content/50">Ukuran Berkas:</span>
                        <span class="font-semibold"><?= round($job['audio_size'] / (1024 * 1024), 2) ?> MB</span>
                    </div>
                    <div>
                        <span class="block text-base-content/50">Estimasi Durasi:</span>
                        <span class="font-semibold">
                            <?= $durationMin !== null ? "{$durationMin} menit (" . gmdate('H:i:s', (int) $job['audio_duration']) . ')' : 'Dihitung saat pemrosesan' ?>
                        </span>
                    </div>
                    <div>
                        <span class="block text-base-content/50">Jumlah Potongan (per 30m):</span>
                        <span class="font-semibold"><?= (int) $job['total_chunks'] ?> segmen</span>
                    </div>
                    <div>
                        <span class="block text-base-content/50">Status AI:</span>
                        <span class="badge badge-sm mt-1 <?= $job['status'] === 'completed' ? 'badge-success' : ($isInProgress ? 'badge-warning' : ($job['status'] === 'failed' ? 'badge-error' : 'badge-neutral')) ?>">
                            <?= esc(strtoupper($job['status'])) ?>
                        </span>
                    </div>
                </div>

                <?php if (in_array($job['status'], ['failed', 'cancelled'], true)): ?>
                    <div class="mt-4 pt-3 border-t border-base-300">
                        <form method="post" action="<?= base_url('admin/notulen/retry/' . $job['id']) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary btn-sm w-full gap-1.5">
                                <i data-lucide="rotate-cw" class="h-4 w-4"></i>
                                Proses Ulang (Resume)
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Tabs Editor Risalah & Transkrip Percakapan -->
    <div class="space-y-4 lg:col-span-3">
        <div class="card card-border bg-base-100 shadow-sm">
            <!-- Tab Headers -->
            <div class="border-b border-base-300 px-4 pt-3">
                <div role="tablist" class="tabs tabs-bordered font-semibold text-sm">
                    <input type="radio" name="notulen_tabs" role="tab" class="tab gap-2" aria-label="Draft Risalah Rapat" checked />
                    <div role="tabpanel" class="tab-content py-5">
                        <!-- Form Editor Risalah -->
                        <?php if ($minutes && ! empty($minutes['id'])): ?>
                            <form method="post" action="<?= base_url('admin/notulen/update-minutes/' . $minutes['id']) ?>" class="space-y-5">
                                <?= csrf_field() ?>

                                <!-- Judul & Tanggal Rapat -->
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div class="sm:col-span-2 form-control">
                                        <label class="label"><span class="label-text font-bold text-xs">Judul Rapat</span></label>
                                        <input type="text" name="judul_rapat" value="<?= esc($minutes['judul_rapat']) ?>" required class="input input-bordered input-sm w-full font-semibold" />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text font-bold text-xs">Tanggal Rapat</span></label>
                                        <input type="date" name="tanggal_rapat" value="<?= esc($minutes['tanggal_rapat']) ?>" class="input input-bordered input-sm w-full" />
                                    </div>
                                </div>

                                <!-- Ringkasan Eksekutif -->
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-bold text-xs">Ringkasan Eksekutif</span>
                                        <span class="label-text-alt text-base-content/50">Intisari pokok bahasan dan hasil rapat</span>
                                    </label>
                                    <textarea name="ringkasan_eksekutif" rows="6" class="textarea textarea-bordered w-full text-sm leading-relaxed" placeholder="Tuliskan ringkasan eksekutif rapat..."><?= esc($minutes['ringkasan_eksekutif']) ?></textarea>
                                </div>

                                <!-- Agenda Pembahasan -->
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-bold text-xs">Agenda & Pokok Pembahasan (JSON / Butir)</span>
                                    </label>
                                    <div class="space-y-3">
                                        <?php if (! empty($agendaItems)): ?>
                                            <?php foreach ($agendaItems as $aIdx => $item): ?>
                                                <div class="rounded-lg bg-base-200/50 p-3.5 border border-base-300/80">
                                                    <div class="font-bold text-xs text-primary mb-1">
                                                        <?= esc($item['topik'] ?? "Pokok Bahasan " . ($aIdx + 1)) ?>
                                                        <?php if (! empty($item['pembicara'])): ?>
                                                            <span class="text-base-content/50 font-normal ml-1">(<?= esc($item['pembicara']) ?>)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-xs text-base-content/80 leading-relaxed"><?= nl2br(esc($item['uraian'] ?? (is_string($item) ? $item : ''))) ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <textarea name="agenda_pembahasan" rows="4" class="textarea textarea-bordered w-full text-xs font-mono" placeholder="Edit data mentah JSON agenda pembahasan..."><?= esc($minutes['agenda_pembahasan']) ?></textarea>
                                    </div>
                                </div>

                                <!-- Kesimpulan / Keputusan -->
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-bold text-xs">Kesimpulan & Keputusan Rapat</span>
                                    </label>
                                    <?php if (! empty($kesimpulanItems)): ?>
                                        <ul class="list-disc list-inside space-y-1 mb-2 text-xs text-base-content/90 bg-base-200/40 p-3 rounded-lg">
                                            <?php foreach ($kesimpulanItems as $kItem): ?>
                                                <li><?= esc(is_string($kItem) ? $kItem : json_encode($kItem)) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <textarea name="kesimpulan" rows="4" class="textarea textarea-bordered w-full text-xs font-mono" placeholder="JSON atau teks poin kesimpulan..."><?= esc($minutes['kesimpulan']) ?></textarea>
                                </div>

                                <!-- Tindak Lanjut / Rekomendasi -->
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-bold text-xs">Rekomendasi & Rencana Tindak Lanjut</span>
                                    </label>
                                    <?php if (! empty($tindakLanjutItems)): ?>
                                        <ul class="list-disc list-inside space-y-1 mb-2 text-xs text-base-content/90 bg-base-200/40 p-3 rounded-lg">
                                            <?php foreach ($tindakLanjutItems as $tItem): ?>
                                                <li><?= esc(is_string($tItem) ? $tItem : json_encode($tItem)) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <textarea name="tindak_lanjut" rows="4" class="textarea textarea-bordered w-full text-xs font-mono" placeholder="JSON atau teks poin tindak lanjut..."><?= esc($minutes['tindak_lanjut']) ?></textarea>
                                </div>

                                <!-- Tombol Simpan -->
                                <div class="flex justify-end pt-3 border-t border-base-300">
                                    <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                                        <i data-lucide="save" class="h-4 w-4"></i>
                                        Simpan Perubahan Risalah
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="py-12 text-center text-sm text-base-content/60">
                                <i data-lucide="loader" class="h-8 w-8 text-base-content/30 mx-auto mb-2 animate-spin"></i>
                                <p>Draft risalah sedang diproses oleh worker AI. Halaman ini akan diperbarui secara otomatis setelah selesai.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 2: Transkrip Percakapan Mentah (Segmented by 30-min Chunks) -->
                    <input type="radio" name="notulen_tabs" role="tab" class="tab gap-2" aria-label="Transkrip Percakapan (<?= (int) ($transcripts['total_chunks'] ?? 0) ?> Bagian)" />
                    <div role="tabpanel" class="tab-content py-5">
                        <?php if (empty($transcripts['chunks'])): ?>
                            <div class="py-12 text-center text-sm text-base-content/60">
                                <i data-lucide="file-text" class="h-8 w-8 text-base-content/30 mx-auto mb-2"></i>
                                <p>Belum ada potongan transkrip yang selesai diproses.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($transcripts['chunks'] as $chunk): ?>
                                    <div class="collapse collapse-arrow bg-base-200/50 border border-base-300">
                                        <input type="checkbox" checked />
                                        <div class="collapse-title flex items-center justify-between font-bold text-xs sm:text-sm">
                                            <span class="flex items-center gap-2">
                                                <i data-lucide="clock" class="h-4 w-4 text-primary"></i>
                                                Bagian <?= (int) $chunk['index'] ?> (<?= esc($chunk['time_label']) ?>)
                                            </span>
                                            <span class="font-mono text-xs font-normal text-base-content/60 mr-4">
                                                <?= esc($chunk['filename']) ?>
                                            </span>
                                        </div>
                                        <div class="collapse-content border-t border-base-300/50 pt-3">
                                            <div class="bg-base-100 p-3.5 rounded-md font-sans text-xs leading-relaxed whitespace-pre-wrap select-all text-base-content/90 max-h-96 overflow-y-auto">
                                                <?= esc($chunk['text']) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script Polling Real-time & Live Log (selalu dimuat, cek via JS) -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script {csp-script-nonce}>
(function() {
    var JOB_ID = <?= (int) $job['id'] ?>;
    var STATUS_URL = '<?= base_url('admin/notulen/status/') ?>' + JOB_ID;
    var INITIAL_STATUS = '<?= esc($job['status']) ?>';
    var POLL_MS = 3500;
    var ACTIVE = ['queued', 'chunking', 'transcribing', 'summarizing'];
    var lastStep = '<?= esc($job['current_step'] ?? '', 'js') ?>';
    var timerId = null;

    var STATUS_LABELS = {
        chunking: 'Memotong Audio Rekaman...',
        transcribing: 'Transkripsi Audio Berjalan...',
        summarizing: 'Menyusun Risalah Rapat...',
        completed: 'Transkripsi & Risalah Selesai',
        failed: 'Pemrosesan Mengalami Kendala',
        cancelled: 'Proses Dibatalkan'
    };

    function timeStr() {
        return new Date().toLocaleTimeString('id-ID', { hour12: false });
    }

    function addLog(msg, cls) {
        var el = document.getElementById('live_log_stream');
        if (!el) return;
        var d = document.createElement('div');
        d.className = cls || 'text-warning';
        d.textContent = '[' + timeStr() + '] ' + msg;
        el.appendChild(d);
        el.scrollTop = el.scrollHeight;
    }

    function poll() {
        fetch(STATUS_URL)
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(json) {
                if (!json || json.status !== 'success' || !json.data) return;
                var d = json.data;

                var pct    = document.getElementById('live_progress_percent');
                var bar    = document.getElementById('live_progress_bar');
                var step   = document.getElementById('live_current_step');
                var chunks = document.getElementById('live_chunk_info');
                var title  = document.getElementById('live_status_title');

                if (pct)    pct.textContent    = d.progress_percent + '%';
                if (bar)    bar.value          = d.progress_percent;
                if (step)   step.textContent   = d.current_step || '-';
                if (chunks) chunks.textContent = d.completed_chunks + ' / ' + d.total_chunks + ' segmen';
                if (title && STATUS_LABELS[d.status]) title.textContent = STATUS_LABELS[d.status];

                if (d.current_step && d.current_step !== lastStep) {
                    lastStep = d.current_step;
                    addLog(d.current_step + ' (' + d.progress_percent + '%)', 'text-warning');
                }

                if (d.status === 'completed' || d.status === 'failed' || d.status === 'cancelled') {
                    clearInterval(timerId);
                    timerId = null;
                    var cls = d.status === 'completed' ? 'text-success font-bold' : 'text-error font-bold';
                    addLog('Proses ' + d.status.toUpperCase() + '! Memuat ulang halaman...', cls);
                    setTimeout(function() { window.location.reload(); }, 1500);
                }
            })
            .catch(function(e) { console.warn('Poll error:', e); });
    }

    if (ACTIVE.indexOf(INITIAL_STATUS) !== -1) {
        poll();
        timerId = setInterval(poll, POLL_MS);
    }
})();
</script>
<?= $this->endSection() ?>
