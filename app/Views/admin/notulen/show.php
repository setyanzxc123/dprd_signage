<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isInProgress = in_array($job['status'], ['chunking', 'transcribing', 'summarizing'], true);
$judulRapat   = ! empty($schedule['judul']) ? $schedule['judul'] : $job['audio_filename'];
$tanggalRapat = ! empty($schedule['tanggal']) ? $schedule['tanggal'] : substr((string) $job['created_at'], 0, 10);
$durationMin  = ! empty($job['audio_duration']) ? round($job['audio_duration'] / 60) : null;
$isCompleted  = $job['status'] === 'completed';
$isFinal      = ($minutes && $minutes['status_verifikasi'] === 'final');

// Cek ketersediaan file audio fisik
$hasAudioFile = is_file(WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'recordings' . DIRECTORY_SEPARATOR . 'job_' . $job['id'] . DIRECTORY_SEPARATOR . 'audio' . DIRECTORY_SEPARATOR . 'original.mp3')
    || (! empty($job['audio_path']) && is_file(WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $job['audio_path']));

// Terjemahan label status AI
$statusLabels = [
    'queued'       => 'Dalam Antrean',
    'chunking'     => 'Memotong Audio',
    'transcribing' => 'Transkripsi Berjalan',
    'summarizing'  => 'Menyusun Risalah',
    'completed'    => 'Selesai',
    'failed'       => 'Gagal',
    'cancelled'    => 'Dibatalkan',
];
$statusLabel = $statusLabels[$job['status']] ?? strtoupper($job['status']);
?>

<!-- Header Halaman & Aksi -->
<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-5">
    <div class="flex min-w-0 items-start gap-2">
        <a href="<?= base_url('admin/notulen') ?>"
           class="btn btn-ghost btn-sm shrink-0 gap-1.5"
           title="Kembali ke daftar notulen">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            <span class="hidden sm:inline">Kembali</span>
        </a>
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="page-title truncate text-lg sm:text-xl font-bold"><?= esc($judulRapat) ?></h1>
                <?php if ($isFinal): ?>
                    <span class="badge badge-success badge-sm gap-1">
                        <i data-lucide="check-check" class="h-3 w-3"></i> Risalah Final Resmi
                    </span>
                <?php elseif ($minutes): ?>
                    <span class="badge badge-warning badge-sm gap-1">
                        <i data-lucide="file-edit" class="h-3 w-3"></i> Draft Risalah
                    </span>
                <?php else: ?>
                    <span class="badge badge-info badge-sm gap-1">
                        <i data-lucide="clock" class="h-3 w-3"></i> <?= esc($statusLabel) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-base-content/60">
                <span>Tanggal: <strong><?= esc($tanggalRapat) ?></strong></span>
                <span>•</span>
                <span>Berkas: <span class="font-mono"><?= esc($job['audio_filename']) ?></span></span>
                <?php if ($job['jadwal_type'] === 'banmus'): ?>
                    <span class="badge badge-secondary badge-xs">Banmus</span>
                <?php else: ?>
                    <span class="badge badge-primary badge-xs">Jadwal Umum</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi Utama di Header -->
    <div class="flex flex-wrap items-center gap-2">
        <?php if ($minutes && ! empty($minutes['id'])): ?>
            <a href="<?= base_url('admin/notulen/export-pdf/' . $minutes['id']) ?>" target="_blank" class="btn btn-outline btn-sm gap-1.5">
                <i data-lucide="printer" class="h-4 w-4"></i>
                Cetak / Ekspor PDF
            </a>

            <?php if (! $isFinal): ?>
                <button type="button"
                        class="btn btn-success btn-sm gap-1.5 text-white"
                        onclick="document.getElementById('modal_finalisasi').showModal()">
                    <i data-lucide="check-circle" class="h-4 w-4"></i>
                    Finalisasi Risalah
                </button>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (! empty($transcripts['full_text'])): ?>
            <a href="<?= base_url('admin/notulen/download-transcript/' . $job['id']) ?>" class="btn btn-ghost btn-sm gap-1.5" title="Unduh transkrip percakapan utuh (.txt)">
                <i data-lucide="download" class="h-4 w-4"></i>
                Unduh .txt
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Visual Lifecycle Stepper (Alur 4 Langkah) -->
<div class="card card-border bg-base-100 shadow-sm mb-6">
    <div class="card-body p-4 sm:p-5">
        <ul class="steps steps-vertical sm:steps-horizontal w-full text-xs font-medium">
            <!-- Langkah 1: Unggah Rekaman -->
            <li class="step step-primary" data-content="✓">
                <span class="font-semibold text-xs text-base-content">1. Rekaman Diunggah</span>
            </li>

            <!-- Langkah 2: Pemrosesan AI -->
            <?php if ($isCompleted): ?>
                <li class="step step-primary" data-content="✓">
                    <span class="font-semibold text-xs text-base-content">2. Transkripsi & AI Selesai</span>
                </li>
            <?php elseif ($isInProgress || $job['status'] === 'queued'): ?>
                <li class="step step-primary" data-content="●">
                    <span class="font-semibold text-xs text-primary animate-pulse">2. Pemrosesan AI (Berjalan)</span>
                </li>
            <?php elseif ($job['status'] === 'failed'): ?>
                <li class="step step-error" data-content="✕">
                    <span class="font-semibold text-xs text-error">2. Pemrosesan Gagal</span>
                </li>
            <?php else: ?>
                <li class="step" data-content="✕">
                    <span class="text-xs text-base-content/60">2. Dibatalkan</span>
                </li>
            <?php endif; ?>

            <!-- Langkah 3: Tinjau & Edit Draft -->
            <?php if ($isFinal): ?>
                <li class="step step-primary" data-content="✓">
                    <span class="font-semibold text-xs text-base-content">3. Ditinjau & Diedit</span>
                </li>
            <?php elseif ($isCompleted): ?>
                <li class="step step-primary" data-content="3">
                    <span class="font-semibold text-xs text-warning">3. Tinjau & Edit Draft (Aktif)</span>
                </li>
            <?php else: ?>
                <li class="step" data-content="3">
                    <span class="text-xs text-base-content/50">3. Tinjau & Edit Draft</span>
                </li>
            <?php endif; ?>

            <!-- Langkah 4: Risalah Final Resmi -->
            <?php if ($isFinal): ?>
                <li class="step step-primary" data-content="★">
                    <span class="font-bold text-xs text-success">4. Risalah Final Resmi</span>
                </li>
            <?php else: ?>
                <li class="step" data-content="4">
                    <span class="text-xs text-base-content/50">4. Risalah Final Resmi</span>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Banner Panduan Kontekstual -->
        <div class="mt-4 pt-3 border-t border-base-200">
            <?php if ($isFinal): ?>
                <div class="alert alert-success/15 border border-success/30 py-2.5 px-3.5 text-xs flex items-center gap-2 text-success-content">
                    <i data-lucide="shield-check" class="h-4 w-4 shrink-0 text-success"></i>
                    <span><strong>Risalah Resmi Terkunci:</strong> Dokumen ini telah disahkan. Anggota dewan dapat membacanya langsung melalui aplikasi mobile, atau Anda dapat mencetak lembar PDF resmi.</span>
                </div>
            <?php elseif ($isCompleted): ?>
                <div class="alert alert-warning/15 border border-warning/30 py-2.5 px-3.5 text-xs flex items-center justify-between gap-2 text-warning-content">
                    <div class="flex items-center gap-2">
                        <i data-lucide="sparkles" class="h-4 w-4 shrink-0 text-warning"></i>
                        <span><strong>Draft 3 Pilar Siap Ditinjau:</strong> AI telah menyusun intisari rapat. Silakan baca dan sesuaikan teks di bawah jika ada istilah atau angka yang perlu disempurnakan, lalu klik <strong>Simpan</strong> atau <strong>Finalisasi</strong>.</span>
                    </div>
                </div>
            <?php elseif ($isInProgress || $job['status'] === 'queued'): ?>
                <div class="alert alert-info/15 border border-info/30 py-2.5 px-3.5 text-xs flex items-center gap-2 text-info-content">
                    <span class="loading loading-spinner loading-xs text-info shrink-0"></span>
                    <span><strong>Worker AI Sedang Bekerja:</strong> Rekaman sedang dipotong dan ditranskripsikan. Halaman ini akan otomatis memuat ulang begitu penyusunan risalah selesai.</span>
                </div>
            <?php elseif ($job['status'] === 'failed'): ?>
                <div class="alert alert-error/15 border border-error/30 py-2.5 px-3.5 text-xs flex items-center justify-between gap-2 text-error-content">
                    <div class="flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="h-4 w-4 shrink-0 text-error"></i>
                        <span><strong>Pemrosesan Mengalami Kendala:</strong> <?= esc($job['error_message']) ?: 'Terjadi kesalahan saat memproses audio.' ?></span>
                    </div>
                    <form method="post" action="<?= base_url('admin/notulen/retry/' . $job['id']) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-error btn-xs text-white">Proses Ulang</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Progress Card saat Job Masih Berjalan -->
<?php if (! $isCompleted): ?>
<div id="live_progress_card" class="card card-border mb-6 bg-base-100 shadow-sm border-warning/40">
    <div class="card-body p-4 sm:p-5 space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="loading loading-spinner loading-sm text-warning"></span>
                <span class="font-bold text-sm text-base-content" id="live_status_title">
                    <?= esc($job['current_step']) ?: 'Sedang Memproses Audio...' ?>
                </span>
            </div>
            <span class="font-mono font-bold text-sm text-warning" id="live_progress_percent"><?= (int) $job['progress_percent'] ?>%</span>
        </div>

        <progress id="live_progress_bar"
                  class="progress progress-warning w-full h-2.5"
                  value="<?= (int) $job['progress_percent'] ?>"
                  max="100"
                  aria-label="Progres AI"></progress>

        <div class="flex items-center justify-between text-xs text-base-content/60">
            <span id="live_current_step"><?= esc($job['current_step']) ?: 'Menunggu worker...' ?></span>
            <span id="live_chunk_info" class="font-mono"><?= (int) $job['completed_chunks'] ?> / <?= (int) $job['total_chunks'] ?> segmen</span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Konten Utama: 2 Kolom (Sidebar Kiri & Editor Kanan) -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <!-- Kolom Kiri: Sidebar Informasi & Audio Player (1 Kolom) -->
    <div class="lg:col-span-1 space-y-5">

        <!-- Card 1: Informasi Rekaman -->
        <div class="card card-border bg-base-100 shadow-sm">
            <div class="card-body p-4 space-y-3">
                <h2 class="card-title text-sm font-bold border-b border-base-200 pb-2 flex items-center gap-1.5">
                    <i data-lucide="file-audio" class="h-4 w-4 text-primary"></i>
                    Informasi Rekaman
                </h2>

                <dl class="space-y-2 text-xs">
                    <div>
                        <dt class="text-base-content/50">Nama Berkas:</dt>
                        <dd class="font-mono font-medium truncate mt-0.5" title="<?= esc($job['audio_filename']) ?>">
                            <?= esc($job['audio_filename']) ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-base-content/50">Ukuran Berkas:</dt>
                        <dd class="font-medium mt-0.5">
                            <?= round($job['audio_size'] / (1024 * 1024), 2) ?> MB
                        </dd>
                    </div>
                    <div>
                        <dt class="text-base-content/50">Estimasi Durasi:</dt>
                        <dd class="font-medium mt-0.5">
                            <?= $durationMin ? "{$durationMin} Menit" : 'Mengukur...' ?>
                            <?php if ($job['total_chunks'] > 0): ?>
                                <span class="text-base-content/50">(<?= (int) $job['total_chunks'] ?> bagian)</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-base-content/50">Waktu Unggah:</dt>
                        <dd class="font-medium mt-0.5"><?= esc($job['created_at']) ?></dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Card 2: Pemutar Audio Rapat -->
        <div class="card card-border bg-base-100 shadow-sm">
            <div class="card-body p-4 space-y-3">
                <h2 class="card-title text-sm font-bold border-b border-base-200 pb-2 flex items-center gap-1.5">
                    <i data-lucide="headphones" class="h-4 w-4 text-primary"></i>
                    Putar Audio Rapat
                </h2>

                <?php if ($hasAudioFile): ?>
                    <p class="text-xs text-base-content/60">Dengarkan audio sambil memeriksa draf risalah di samping:</p>
                    <audio controls class="w-full mt-1 focus:outline-none" preload="metadata">
                        <source src="<?= base_url('admin/notulen/audio/' . $job['id']) ?>" type="audio/mpeg">
                        Peramban Anda tidak mendukung pemutar audio HTML5.
                    </audio>
                <?php else: ?>
                    <div class="rounded-lg bg-base-200 p-3 text-center text-xs text-base-content/60">
                        <i data-lucide="hard-drive" class="h-5 w-5 mx-auto mb-1 text-base-content/40"></i>
                        Berkas audio telah dibersihkan dari server untuk retensi penyimpanan. Teks transkrip tetap aman.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card 3: Status & Aksi Dokumen -->
        <div class="card card-border bg-base-100 shadow-sm">
            <div class="card-body p-4 space-y-3">
                <h2 class="card-title text-sm font-bold border-b border-base-200 pb-2 flex items-center gap-1.5">
                    <i data-lucide="check-square" class="h-4 w-4 text-primary"></i>
                    Aksi Dokumen
                </h2>

                <div class="space-y-2">
                    <?php if ($isFinal): ?>
                        <div class="rounded-lg bg-success/10 border border-success/20 p-3 text-xs text-success-content space-y-1">
                            <span class="font-bold flex items-center gap-1">
                                <i data-lucide="lock" class="h-3.5 w-3.5"></i> Status: Dokumen Final
                            </span>
                            <p class="text-[11px] text-base-content/70">Divalidasi pada: <?= esc($minutes['verified_at'] ?? $minutes['updated_at']) ?></p>
                        </div>

                        <a href="<?= base_url('admin/notulen/export-pdf/' . $minutes['id']) ?>" target="_blank" class="btn btn-primary btn-sm w-full gap-1.5">
                            <i data-lucide="printer" class="h-4 w-4"></i> Cetak / Ekspor PDF
                        </a>
                    <?php elseif ($minutes): ?>
                        <div class="rounded-lg bg-warning/10 border border-warning/20 p-3 text-xs text-warning-content space-y-1">
                            <span class="font-bold flex items-center gap-1">
                                <i data-lucide="edit-3" class="h-3.5 w-3.5"></i> Status: Draf Risalah
                            </span>
                            <p class="text-[11px] text-base-content/70">Dokumen masih dapat disunting sebelum difinalisasi.</p>
                        </div>

                        <button type="button"
                                class="btn btn-success btn-sm w-full gap-1.5 text-white"
                                onclick="document.getElementById('modal_finalisasi').showModal()">
                            <i data-lucide="check-circle" class="h-4 w-4"></i> Finalisasi Risalah
                        </button>
                    <?php endif; ?>

                    <?php if (! empty($transcripts['full_text'])): ?>
                        <a href="<?= base_url('admin/notulen/download-transcript/' . $job['id']) ?>" class="btn btn-ghost btn-sm w-full gap-1.5">
                            <i data-lucide="file-text" class="h-4 w-4"></i> Unduh Transkrip (.txt)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Kolom Kanan: Editor Risalah & Transkrip (3 Kolom) -->
    <div class="lg:col-span-3 space-y-4">

        <!-- Tab Navigasi Bersih -->
        <div class="tabs tabs-boxed bg-base-200 p-1 rounded-lg">
            <input type="radio" name="notulen_view_tab" class="tab font-semibold text-xs sm:text-sm !h-9" aria-label="📑 Naskah Risalah Rapat (3 Pilar)" checked />
            <div class="tab-content bg-base-100 border-base-300 rounded-box p-4 sm:p-6 mt-2 border shadow-sm">

                <?php if ($minutes && ! empty($minutes['id'])): ?>

                    <?php if (! $isFinal): ?>
                        <!-- ================= MODE DRAFT: FORMULIR EDITOR 1 FIELD TUNGGAL ================= -->
                        <form method="post" action="<?= base_url('admin/notulen/update-minutes/' . $minutes['id']) ?>" class="space-y-5">
                            <?= csrf_field() ?>

                            <!-- Info Jadwal Terkait (Single Source of Truth) -->
                            <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 bg-base-200/60 rounded-xl border border-base-200">
                                <div>
                                    <span class="text-xs text-base-content/60 block font-medium">Agenda Rapat:</span>
                                    <strong class="text-sm font-bold text-base-content"><?= esc($judulRapat) ?></strong>
                                </div>
                                <div class="text-left sm:text-right">
                                    <span class="text-xs text-base-content/60 block font-medium">Tanggal Pelaksanaan:</span>
                                    <strong class="text-xs font-semibold text-base-content"><?= esc($tanggalRapat) ?></strong>
                                </div>
                            </div>

                            <!-- Petunjuk 3 Pilar -->
                            <div class="bg-base-200/60 rounded-lg p-3 text-xs text-base-content/80 flex items-start gap-2">
                                <i data-lucide="info" class="h-4 w-4 text-info shrink-0 mt-0.5"></i>
                                <div>
                                    <span class="font-bold block text-base-content">Format Naskah Risalah Resmi (3 Pilar):</span>
                                    <span class="text-base-content/70">Naskah mencakup <strong>I. Ringkasan Utama</strong>, <strong>II. Poin-Poin Pembahasan</strong>, dan <strong>III. Kesimpulan & Keputusan Akhir</strong>. Anda dapat mengoreksi nama pembicara, data pembahasan, atau keputusan rapat langsung pada kotak teks di bawah ini.</span>
                                </div>
                            </div>

                            <!-- Textarea Editor Tunggal (Satu Lembar Naskah Utuh) -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="label-text font-bold text-xs text-base-content flex items-center gap-1.5">
                                        <i data-lucide="file-text" class="h-4 w-4 text-primary"></i>
                                        Isi Naskah Risalah Rapat
                                    </label>
                                    <span class="text-[11px] text-base-content/50">Simpan perubahan draf kapan saja</span>
                                </div>

                                <textarea name="ringkasan_eksekutif"
                                          rows="22"
                                          class="textarea textarea-bordered w-full font-mono sm:font-sans text-sm leading-relaxed p-4 bg-base-100 focus:border-primary focus:ring-1 focus:ring-primary shadow-inner"
                                          placeholder="I. RINGKASAN UTAMA&#10;...&#10;&#10;II. POIN-POIN PEMBAHASAN&#10;1. Topik:...&#10;&#10;III. KESIMPULAN & KEPUTUSAN AKHIR&#10;1. ..."><?= esc($minutes['ringkasan_eksekutif'] ?? '') ?></textarea>
                            </div>

                            <!-- Baris Tombol Simpan -->
                            <div class="flex items-center justify-between pt-4 border-t border-base-200">
                                <div class="text-xs text-base-content/60">
                                    <span>Terakhir disimpan: <strong><?= esc($minutes['updated_at'] ?? '-') ?></strong></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                                        <i data-lucide="save" class="h-4 w-4"></i>
                                        Simpan Perubahan Draft
                                    </button>
                                </div>
                            </div>
                        </form>

                    <?php else: ?>
                        <!-- ================= MODE FINAL: PRATINJAU DOKUMEN RESMI (READ-ONLY) ================= -->
                        <div class="space-y-6">

                            <!-- Banner Dokumen Resmi -->
                            <div class="flex items-center justify-between p-4 rounded-xl bg-success/10 border border-success/20">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-success/20 flex items-center justify-center text-success shrink-0">
                                        <i data-lucide="stamp" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-sm text-success">DOKUMEN RISALAH RESMI</h3>
                                        <p class="text-xs text-base-content/70">Risalah telah difinalisasi dan terkunci sebagai rekaman dinas resmi.</p>
                                    </div>
                                </div>
                                <a href="<?= base_url('admin/notulen/export-pdf/' . $minutes['id']) ?>" target="_blank" class="btn btn-success btn-sm text-white gap-1.5">
                                    <i data-lucide="printer" class="h-4 w-4"></i> Cetak Dokumen Resmi
                                </a>
                            </div>

                            <!-- Lembar Pratinjau Kertas Naskah Dinas -->
                            <div class="rounded-xl border border-base-200 bg-base-100 p-6 sm:p-10 shadow-sm space-y-6">
                                <!-- Header Lembar Rapat -->
                                <div class="text-center border-b-2 border-base-content/20 pb-4 space-y-1">
                                    <p class="text-xs font-bold uppercase tracking-widest text-base-content/60">Dewan Perwakilan Rakyat Daerah Provinsi Sulawesi Tengah</p>
                                    <h2 class="text-lg sm:text-xl font-black uppercase text-base-content tracking-wide">RISALAH RAPAT RESMI</h2>
                                    <p class="text-sm font-semibold text-primary"><?= esc($judulRapat) ?></p>
                                    <p class="text-xs text-base-content/60">Tanggal Pelaksanaan: <?= esc($tanggalRapat) ?></p>
                                </div>

                                <!-- Isi Naskah 3 Pilar -->
                                <div class="prose max-w-none text-sm text-base-content leading-relaxed whitespace-pre-wrap font-sans">
<?= esc($minutes['ringkasan_eksekutif'] ?? '') ?>
                                </div>

                                <!-- Kolom Pengesahan -->
                                <div class="pt-8 border-t border-base-200 grid grid-cols-2 gap-6 text-center text-xs">
                                    <div>
                                        <p class="text-base-content/60">Mengetahui,</p>
                                        <p class="font-bold mt-1">Pimpinan Rapat / Sidang</p>
                                        <div class="h-16"></div>
                                        <p class="font-semibold text-base-content/80">( ..................................................... )</p>
                                    </div>
                                    <div>
                                        <p class="text-base-content/60">Palu, <?= esc($tanggalRapat) ?></p>
                                        <p class="font-bold mt-1">Sekretariat / Notulis Rapat</p>
                                        <div class="h-16"></div>
                                        <p class="font-semibold text-base-content/80">( ..................................................... )</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- State Belum Ada Risalah (Masih Proses AI) -->
                    <div class="py-12 text-center text-base-content/60 space-y-3">
                        <span class="loading loading-spinner loading-lg text-primary"></span>
                        <p class="font-medium text-sm">Sedang memproses transkrip dan menyusun naskah risalah 3 pilar...</p>
                        <p class="text-xs text-base-content/40">Hasil risalah akan otomatis muncul di sini begitu worker AI selesai bekerja.</p>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Tab 2: Transkrip Percakapan Lengkap -->
            <input type="radio" name="notulen_view_tab" class="tab font-semibold text-xs sm:text-sm !h-9" aria-label="🎙 Transkrip Lengkap (<?= count($transcripts['chunks']) ?> Bagian)" />
            <div class="tab-content bg-base-100 border-base-300 rounded-box p-4 sm:p-6 mt-2 border shadow-sm space-y-4">

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-base-200">
                    <div>
                        <h3 class="font-bold text-sm text-base-content flex items-center gap-1.5">
                            <i data-lucide="mic" class="h-4 w-4 text-primary"></i>
                            Transkrip Verbatim Percakapan Audio
                        </h3>
                        <p class="text-xs text-base-content/60">Rekaman dipotong per 30 menit dan ditranskripsikan secara otomatis.</p>
                    </div>
                    <?php if (! empty($transcripts['full_text'])): ?>
                        <a href="<?= base_url('admin/notulen/download-transcript/' . $job['id']) ?>" class="btn btn-outline btn-xs gap-1">
                            <i data-lucide="download" class="h-3.5 w-3.5"></i> Unduh Transkrip Lengkap (.txt)
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($transcripts['chunks'])): ?>
                    <div class="py-10 text-center text-xs text-base-content/50">
                        <i data-lucide="file-x" class="h-8 w-8 mx-auto mb-2 opacity-40"></i>
                        Berkas potongan transkrip belum tersedia.
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($transcripts['chunks'] as $chunk): ?>
                            <div class="collapse collapse-arrow bg-base-200/50 border border-base-200 rounded-lg">
                                <input type="checkbox" <?= $chunk['index'] === 1 ? 'checked' : '' ?> />
                                <div class="collapse-title font-semibold text-xs flex items-center justify-between pr-10">
                                    <span class="flex items-center gap-2">
                                        <span class="badge badge-primary badge-sm font-mono">Bagian <?= (int) $chunk['index'] ?></span>
                                        <span><?= esc($chunk['time_label']) ?></span>
                                    </span>
                                    <span class="text-[11px] text-base-content/50 font-normal">
                                        <?= number_format(str_word_count($chunk['text']), 0, ',', '.') ?> kata
                                    </span>
                                </div>
                                <div class="collapse-content text-xs bg-base-100 pt-3 border-t border-base-200">
                                    <pre class="font-mono text-xs whitespace-pre-wrap leading-relaxed max-h-96 overflow-y-auto p-2 bg-base-200/30 rounded"><?= esc($chunk['text']) ?></pre>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>

</div>

<!-- Modal Konfirmasi Finalisasi Risalah -->
<?php if ($minutes && ! empty($minutes['id']) && (! $isFinal)): ?>
<dialog id="modal_finalisasi" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box max-w-md">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-warning/15 flex items-center justify-center text-warning shrink-0">
                <i data-lucide="alert-triangle" class="h-5 w-5"></i>
            </div>
            <div>
                <h3 class="font-bold text-base">Finalisasi Risalah Rapat?</h3>
                <p class="text-xs text-base-content/60 mt-0.5">Dokumen akan disahkan dan dikunci sebagai rekaman resmi.</p>
            </div>
        </div>

        <div class="bg-warning/10 border border-warning/30 rounded-lg p-3.5 mb-4 text-xs space-y-2 text-base-content/90">
            <p class="font-bold text-base-content">Poin penting setelah finalisasi:</p>
            <ul class="space-y-1.5 list-disc pl-4">
                <li>Risalah akan <strong>langsung dapat dibaca anggota dewan</strong> pada aplikasi mobile.</li>
                <li>Status dokumen berubah menjadi <strong>Final (Terkunci)</strong>.</li>
            </ul>
        </div>

        <div class="modal-action mt-0 gap-2">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modal_finalisasi').close()">
                Batal
            </button>
            <form method="post" action="<?= base_url('admin/notulen/finalize/' . $minutes['id']) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success btn-sm gap-1.5 text-white">
                    <i data-lucide="check-circle" class="h-4 w-4"></i>
                    Ya, Finalisasi Sekarang
                </button>
            </form>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>tutup</button>
    </form>
</dialog>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script {csp-script-nonce}>
(function() {
    var JOB_ID         = <?= (int) $job['id'] ?>;
    var STATUS_URL     = '<?= base_url('admin/notulen/status/') ?>' + JOB_ID;
    var INITIAL_STATUS = '<?= esc($job['status']) ?>';
    var POLL_MS        = 3500;
    var ACTIVE         = ['queued', 'chunking', 'transcribing', 'summarizing'];
    var timerId        = null;

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

                if (pct)     pct.textContent    = d.progress_percent + '%';
                if (bar)     bar.value          = d.progress_percent;
                if (step)    step.textContent   = d.current_step || '-';
                if (chunks)  chunks.textContent = d.completed_chunks + ' / ' + d.total_chunks + ' segmen';
                if (title && d.current_step) title.textContent = d.current_step;

                if (d.status === 'completed' || d.status === 'failed' || d.status === 'cancelled') {
                    clearInterval(timerId);
                    timerId = null;
                    setTimeout(function() { window.location.reload(); }, 1200);
                }
            })
            .catch(function(e) { console.warn('Poll error:', e); });
    }

    if (ACTIVE.indexOf(INITIAL_STATUS) !== -1) {
        timerId = setInterval(poll, POLL_MS);
    }
})();
</script>
<?= $this->endSection() ?>this->endSection() ?>
