<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isInProgress = in_array($job['status'], ['chunking', 'transcribing', 'summarizing'], true);
$judulRapat   = ! empty($schedule['judul']) ? $schedule['judul'] : $job['audio_filename'];
$tanggalRapat = ! empty($schedule['tanggal']) ? $schedule['tanggal'] : substr((string) $job['created_at'], 0, 10);
$durationMin  = ! empty($job['audio_duration']) ? round($job['audio_duration'] / 60) : null;
$durationFormatted = ! empty($job['audio_duration'])
    ? sprintf('%02d:%02d:%02d', floor($job['audio_duration'] / 3600), floor(($job['audio_duration'] % 3600) / 60), $job['audio_duration'] % 60)
    : '00:00:00';

$isCompleted  = $job['status'] === 'completed';
$isFailed     = $job['status'] === 'failed';
$isCancelled  = $job['status'] === 'cancelled';
$isFinal      = ($minutes && $minutes['status_verifikasi'] === 'final');

// Cek ketersediaan file master audio fisik asli
$hasAudioFile = isset($hasAudioFile)
    ? (bool) $hasAudioFile
    : ((new \App\Libraries\Notulen\NotulenService())->resolveAudioPath((int) $job['id']) !== null);

$badgeKategori = 'RAPAT UMUM';
if ($job['jadwal_type'] === 'banmus') {
    $badgeKategori = 'BADAN MUSYAWARAH';
} elseif (stripos($judulRapat, 'paripurna') !== false) {
    $badgeKategori = 'PARIPURNA';
} elseif (stripos($judulRapat, 'komisi') !== false) {
    $badgeKategori = 'RAPAT KOMISI';
}
?>

<div class="space-y-5">

    <!-- 1. Alur proses AI (4 langkah ringkas dan non-teknis) -->
    <div id="live_progress_card"
         role="status"
         aria-live="polite"
         data-notulen-poll
         data-job-id="<?= (int) $job['id'] ?>"
         data-status="<?= esc($job['status']) ?>"
         data-status-url="<?= base_url('admin/notulen/status/' . $job['id']) ?>"
         class="card card-border bg-base-100 shadow-sm border-base-200">
        <div class="card-body p-4 sm:p-5 space-y-4">
            
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-base-200 pb-3">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-base-content/80">Alur Proses AI</span>
                    <span id="ai_model_badge" class="badge badge-sm badge-ghost gap-1 border border-base-300 font-semibold text-[11px] text-base-content/90">
                        <i data-lucide="sparkles" class="h-3 w-3 text-base-content/70"></i>
                        <span id="ai_model_label_text"><?= esc($aiModelLabel ?? \App\Libraries\Notulen\NotulenService::formatAiModelLabel($job['ai_model'] ?? null)) ?></span>
                    </span>
                </div>

                <!-- Kontrol Proses AI: Stop / Resume / Badge Status -->
                <div class="flex items-center gap-2" id="notulen_process_controls">
                    <?php if ($isCancelled): ?>
                        <span class="badge badge-sm bg-warning/15 border border-warning/40 text-base-content font-semibold text-[11px] gap-1.5 py-1 px-2.5">
                            <i data-lucide="pause" class="h-3 w-3 fill-current text-warning"></i> Dihentikan
                        </span>
                        <form method="post" action="<?= base_url('admin/notulen/retry/' . $job['id']) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-xs btn-primary gap-1.5 text-white font-bold shadow-xs">
                                <i data-lucide="play" class="h-3.5 w-3.5 fill-current"></i>
                                Lanjutkan Proses
                            </button>
                        </form>
                    <?php elseif ($isFailed): ?>
                        <span class="badge badge-sm bg-error/15 border border-error/40 text-error font-semibold text-[11px] gap-1.5 py-1 px-2.5">
                            <i data-lucide="alert-triangle" class="h-3.5 w-3.5"></i> Gagal
                        </span>
                        <form method="post" action="<?= base_url('admin/notulen/retry/' . $job['id']) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-xs btn-error text-white font-bold gap-1.5 shadow-xs">
                                <i data-lucide="rotate-cw" class="h-3.5 w-3.5"></i>
                                Coba Ulang
                            </button>
                        </form>
                    <?php elseif ($isCompleted): ?>
                        <?php if ($isFinal): ?>
                            <span class="badge badge-sm bg-success/15 border border-success/40 text-success font-semibold text-[11px] gap-1.5 py-1 px-2.5">
                                <i data-lucide="check-check" class="h-3.5 w-3.5"></i> Risalah Final &amp; Sah
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg text-xs font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">
                                <i data-lucide="file-edit" class="size-3.5 text-amber-500"></i> Draf Siap Ditinjau
                            </span>
                        <?php endif; ?>
                    <?php elseif ($isInProgress || $job['status'] === 'queued'): ?>
                        <?php if (! empty($job['cancel_requested'])): ?>
                            <button type="button" disabled class="py-1 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 cursor-not-allowed shadow-xs opacity-90">
                                <span class="animate-spin inline-block size-3 border-2 border-current border-t-transparent rounded-full"></span>
                                Menghentikan...
                            </button>
                        <?php else: ?>
                            <form method="post" action="<?= base_url('admin/notulen/cancel/' . $job['id']) ?>" data-confirm-message="Hentikan proses AI sekarang? Bagian transkrip yang telah selesai akan tetap tersimpan aman." class="m-0 inline-flex">
                                <?= csrf_field() ?>
                                <button type="submit" class="py-1 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/50 shadow-xs transition cursor-pointer" title="Hentikan sementara proses AI">
                                    <i data-lucide="square" class="size-3 fill-current"></i>
                                    Hentikan Proses
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stepper Ringkas 5 Langkah -->
            <div class="overflow-x-auto py-2.5 px-1">
                <div class="notulen-stepper-track min-w-[600px]">
                    <div class="notulen-stepper-line"></div>

                    <!-- Step 1: Unggah Rekaman -->
                    <div class="notulen-step-item" id="step_upload_item">
                        <div class="notulen-step-circle done">
                            <i data-lucide="cloud-upload" class="h-5 w-5"></i>
                        </div>
                        <span class="font-bold text-xs text-base-content mt-2">1. Unggah Rekaman</span>
                        <span class="text-[11px] text-base-content/80 font-medium mt-0.5 flex items-center gap-1">
                            <i data-lucide="check-circle-2" class="h-3 w-3 text-success"></i>
                            <?= substr((string) $job['created_at'], 8, 2) . ' ' . date('M H:i', strtotime((string) $job['created_at'])) ?>
                        </span>
                    </div>

                    <!-- Step 2: Pemrosesan Rekaman (Abstraksi Chunking Audio) -->
                    <?php
                    $isStep2Done = in_array($job['status'], ['transcribing', 'summarizing', 'completed'], true);
                    $isStep2Active = in_array($job['status'], ['chunking', 'queued'], true);
                    $step2CircleClass = $isStep2Done ? 'done' : ($isStep2Active ? 'active' : '');
                    ?>
                    <div class="notulen-step-item" id="step_chunking_item">
                        <div class="notulen-step-circle <?= $step2CircleClass ?>" id="step_chunking_circle">
                            <i data-lucide="sliders" class="h-5 w-5"></i>
                        </div>
                        <span class="font-bold text-xs text-base-content mt-2">2. Pemrosesan Rekaman</span>
                        <span class="text-[11px] text-base-content/80 font-medium mt-0.5 flex items-center gap-1" id="step_chunking_status">
                            <?php if ($isStep2Done): ?>
                                <i data-lucide="check-circle-2" class="h-3 w-3 text-success"></i>
                                Selesai
                            <?php elseif ($isStep2Active): ?>
                                <span class="loading loading-spinner loading-xs text-base-content"></span>
                                Menyiapkan audio...
                            <?php else: ?>
                                Menunggu
                            <?php endif; ?>
                        </span>
                        <?php if ($isStep2Active): ?><div class="notulen-active-pill" id="step_chunking_pill"></div><?php endif; ?>
                    </div>

                    <!-- Step 3: Transkripsi Suara -->
                    <?php
                    $isStep3Done = in_array($job['status'], ['summarizing', 'completed'], true);
                    $isStep3Active = $job['status'] === 'transcribing';
                    $step3CircleClass = $isStep3Done ? 'done' : ($isStep3Active ? 'active' : '');
                    ?>
                    <div class="notulen-step-item" id="step_transcribing_item">
                        <div class="notulen-step-circle <?= $step3CircleClass ?>" id="step_transcribing_circle">
                            <i data-lucide="mic" class="h-5 w-5"></i>
                        </div>
                        <span class="font-bold text-xs text-base-content mt-2">3. Transkripsi Suara</span>
                        <span class="text-[11px] text-base-content/80 font-medium mt-0.5 flex items-center gap-1" id="step_transcribing_status">
                            <?php if ($isStep3Done): ?>
                                <i data-lucide="check-circle-2" class="h-3 w-3 text-success"></i>
                                Selesai
                            <?php elseif ($isStep3Active): ?>
                                <span class="loading loading-spinner loading-xs text-base-content"></span>
                                Mentranskripsi (<?= (int) $job['progress_percent'] ?>%)
                            <?php else: ?>
                                Menunggu
                            <?php endif; ?>
                        </span>
                        <?php if ($isStep3Active): ?><div class="notulen-active-pill" id="step_transcribing_pill"></div><?php endif; ?>
                    </div>

                    <!-- Step 4: Penyusunan Risalah -->
                    <?php
                    $isStep4Done = $isCompleted;
                    $isStep4Active = $job['status'] === 'summarizing';
                    $step4CircleClass = $isStep4Done ? 'done' : ($isStep4Active ? 'active' : '');
                    ?>
                    <div class="notulen-step-item" id="step_summarizing_item">
                        <div class="notulen-step-circle <?= $step4CircleClass ?>" id="step_summarizing_circle">
                            <i data-lucide="sparkles" class="h-5 w-5"></i>
                        </div>
                        <span class="font-bold text-xs text-base-content mt-2">4. Penyusunan Risalah</span>
                        <span class="text-[11px] text-base-content/80 font-medium mt-0.5 flex items-center gap-1" id="step_summarizing_status">
                            <?php if ($isStep4Done): ?>
                                <i data-lucide="check-circle-2" class="h-3 w-3 text-success"></i>
                                Selesai
                            <?php elseif ($isStep4Active): ?>
                                <span class="loading loading-spinner loading-xs text-base-content"></span>
                                Menyusun risalah...
                            <?php else: ?>
                                Menunggu
                            <?php endif; ?>
                        </span>
                        <?php if ($isStep4Active): ?><div class="notulen-active-pill" id="step_summarizing_pill"></div><?php endif; ?>
                    </div>

                    <!-- Step 5: Risalah Siap / Sah -->
                    <?php
                    $step5CircleClass = $isCompleted ? 'done' : '';
                    ?>
                    <div class="notulen-step-item" id="step_completed_item">
                        <div class="notulen-step-circle <?= $step5CircleClass ?>" id="step_completed_circle">
                            <?php if ($isFinal): ?>
                                <i data-lucide="check-check" class="h-5 w-5"></i>
                            <?php else: ?>
                                <i data-lucide="file-check" class="h-5 w-5"></i>
                            <?php endif; ?>
                        </div>
                        <span class="font-bold text-xs text-base-content mt-2" id="step_completed_title">
                            <?= $isFinal ? '5. Risalah Sah' : '5. Risalah Siap' ?>
                        </span>
                        <span class="text-[11px] text-base-content/80 font-medium mt-0.5 flex items-center gap-1" id="step_completed_status">
                            <?php if ($isCompleted): ?>
                                <?php if ($isFinal): ?>
                                    <i data-lucide="check-check" class="h-3 w-3 text-success"></i>
                                    Final &amp; Sah
                                <?php else: ?>
                                    <i data-lucide="file-edit" class="h-3 w-3 text-warning"></i>
                                    Siap Ditinjau
                                <?php endif; ?>
                            <?php else: ?>
                                Menunggu
                            <?php endif; ?>
                        </span>
                    </div>

                </div>
            </div>

            <!-- Info / Alert Bar Bawah Stepper -->
            <?php if ($isInProgress || $job['status'] === 'queued'): ?>
                <div class="alert bg-base-200/60 border border-base-300 py-2.5 px-3.5 text-xs flex items-center gap-2 text-base-content/80 mt-2 rounded-lg">
                    <i data-lucide="info" class="h-4 w-4 shrink-0 text-base-content/60"></i>
                    <span>Proses berjalan di background. Anda dapat menutup halaman ini. Sistem akan otomatis memuat hasil begitu selesai.</span>
                </div>
            <?php elseif ($isCancelled): ?>
                <div class="alert bg-warning/15 border border-warning/30 py-2.5 px-3.5 text-xs flex items-center gap-2 text-base-content mt-2 rounded-lg">
                    <i data-lucide="pause" class="h-4 w-4 shrink-0 text-warning fill-current"></i>
                    <span>Proses AI dihentikan sementara. Klik tombol <strong>Lanjutkan Proses</strong> di bagian atas untuk melanjutkan dari checkpoint terakhir.</span>
                </div>
            <?php elseif ($isFailed): ?>
                <div class="alert bg-error/15 border border-error/30 py-2.5 px-3.5 text-xs flex items-center gap-2 text-base-content mt-2 rounded-lg">
                    <i data-lucide="alert-triangle" class="h-4 w-4 shrink-0 text-error"></i>
                    <span>Pemrosesan mengalami kendala: <strong class="text-error font-semibold"><?= esc($job['error_message']) ?: 'Koneksi AI timeout.' ?></strong></span>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- 2. Card identitas rapat -->
    <div class="card card-border bg-base-100 shadow-sm border-base-200">
        <div class="card-body p-4 sm:p-5 flex flex-col md:flex-row items-start justify-between gap-4">
            
            <!-- Metadata Rapat -->
            <div class="space-y-1.5 min-w-0 flex-1">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        <?= esc($badgeKategori) ?>
                    </span>
                </div>

                <h1 class="text-base sm:text-lg font-bold text-base-content leading-snug" title="<?= esc($judulRapat) ?>">
                    <?= esc($judulRapat) ?>
                </h1>

                <div class="flex flex-wrap items-center gap-y-1.5 gap-x-5 text-xs text-base-content/80">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="calendar" class="h-3.5 w-3.5 text-base-content/60"></i>
                        <?= esc($tanggalRapat) ?>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="clock" class="h-3.5 w-3.5 text-base-content/60"></i>
                        <?= ! empty($schedule['waktu_mulai']) ? substr((string) $schedule['waktu_mulai'], 0, 5) : '09:00' ?> WITA
                        <span class="font-mono text-base-content/75 font-medium">(<?= esc($durationFormatted) ?>)</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="h-3.5 w-3.5 text-base-content/60"></i>
                        <?= esc($schedule['ruangan'] ?? 'Ruang Rapat Paripurna DPRD Provinsi Sulawesi Tengah') ?>
                    </span>
                </div>
            </div>

            <!-- Tombol Kembali -->
            <div class="flex items-center gap-2 shrink-0 self-start">
                <a href="<?= base_url('admin/notulen') ?>" class="btn btn-ghost btn-sm gap-1.5 text-xs border border-base-300">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Kembali
                </a>
            </div>

        </div>
    </div>

    <!-- 3. Dua tab utama: Ringkasan & Risalah -->
    <div class="space-y-3 w-full">

        <div role="tablist" class="inline-flex items-center gap-1 bg-base-200/80 p-1 rounded-xl border border-base-300">
            <button type="button"
                    role="tab"
                    id="tab_btn_ringkasan"
                    data-tab-target="tab_panel_ringkasan"
                    class="notulen-main-tab-btn px-6 py-2 rounded-lg text-xs sm:text-sm font-bold transition-colors"
                    aria-selected="true">
                Ringkasan
            </button>
            <button type="button"
                    role="tab"
                    id="tab_btn_risalah"
                    data-tab-target="tab_panel_risalah"
                    class="notulen-main-tab-btn px-6 py-2 rounded-lg text-xs sm:text-sm font-bold transition-colors"
                    aria-selected="false">
                Risalah
            </button>
        </div>

        <!-- Tab 1: Ringkasan -->
        <div id="tab_panel_ringkasan" role="tabpanel" class="w-full bg-base-100 border border-base-200 rounded-box p-4 sm:p-6 shadow-xs space-y-4">
            
            <!-- Dashboard 3 Kartu Sejajar Sesuai Mockup -->
            <?php if ($minutes && ! empty($minutes['ringkasan_eksekutif'])): ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                    <!-- KARTU 1: 1. Ringkasan Utama (Ungu/Indigo Tint) -->
                    <div class="card card-border bg-indigo-50/40 dark:bg-indigo-950/20 shadow-2xs border-indigo-200/80 dark:border-indigo-800/40 flex flex-col rounded-xl">
                        <div class="card-body p-4 sm:p-5 space-y-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                                    <i data-lucide="sparkles" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold text-base-content leading-none">1. Ringkasan Utama</h3>
                                    <p class="text-[10px] text-base-content/70 mt-0.5">Inti dari seluruh rapat</p>
                                </div>
                            </div>

                            <div class="max-h-[460px] overflow-y-auto pr-2 space-y-2 text-xs text-base-content leading-relaxed text-justify font-sans">
                                <?= nl2br(esc(! empty($pillars['ringkasan_utama']) ? $pillars['ringkasan_utama'] : $minutes['ringkasan_eksekutif'])) ?>
                            </div>
                        </div>
                    </div>

                    <!-- KARTU 2: 2. Poin-Poin Pembahasan (Oranye/Amber Tint) -->
                    <div class="card card-border bg-amber-50/40 dark:bg-amber-950/20 shadow-2xs border-amber-200/80 dark:border-amber-800/40 flex flex-col rounded-xl">
                        <div class="card-body p-4 sm:p-5 space-y-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/60 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                                    <i data-lucide="list-ordered" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold text-base-content leading-none">2. Poin-Poin Pembahasan</h3>
                                    <p class="text-[10px] text-base-content/70 mt-0.5">Rincian dinamika &amp; pandangan rapat</p>
                                </div>
                            </div>

                            <div class="max-h-[460px] overflow-y-auto pr-1 space-y-2.5">
                                <?php if (! empty($pillars['poin_pembahasan'])): ?>
                                    <?php foreach ($pillars['poin_pembahasan'] as $poin): ?>
                                        <div class="rounded-lg bg-base-100/90 dark:bg-base-900/50 border border-amber-200/70 dark:border-amber-800/50 p-2.5 space-y-1 text-xs text-base-content shadow-2xs">
                                            <div class="flex items-start gap-1.5 font-bold text-base-content leading-snug">
                                                <?php if (! empty($poin['waktu'])): ?>
                                                    <span class="badge badge-xs bg-amber-100 dark:bg-amber-900/50 border border-amber-300 dark:border-amber-700/60 font-mono text-[10px] shrink-0 mt-0.5 text-amber-900 dark:text-amber-200 font-semibold">
                                                        <?= esc($poin['waktu']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 shrink-0 mt-1.5"></span>
                                                <?php endif; ?>
                                                <span><?= esc($poin['topik']) ?></span>
                                            </div>

                                            <?php if (! empty($poin['pembicara'])): ?>
                                                <div class="flex items-center gap-1.5 text-[11px] text-amber-700 dark:text-amber-300 font-semibold pl-3">
                                                    <i data-lucide="user" class="h-3 w-3 shrink-0"></i>
                                                    <span><?= esc($poin['pembicara']) ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (! empty($poin['uraian'])): ?>
                                                <p class="text-[11px] text-base-content/80 leading-relaxed pl-3 whitespace-pre-wrap">
                                                    <?= esc($poin['uraian']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-xs text-base-content/70 italic">Poin pembahasan terangkum dalam naskah dinas resmi.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- KARTU 3: 3. Kesimpulan & Keputusan Akhir (Hijau/Emerald Tint) -->
                    <div class="card card-border bg-emerald-50/40 dark:bg-emerald-950/20 shadow-2xs border-emerald-200/80 dark:border-emerald-800/40 flex flex-col rounded-xl">
                        <div class="card-body p-4 sm:p-5 space-y-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                                    <i data-lucide="check-square" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold text-base-content leading-none">3. Kesimpulan & Keputusan Akhir</h3>
                                    <p class="text-[10px] text-base-content/70 mt-0.5">Hasil akhir yang disepakati</p>
                                </div>
                            </div>

                            <div class="max-h-[460px] overflow-y-auto pr-1 space-y-2.5">
                                <?php if (! empty($pillars['kesimpulan_akhir'])): ?>
                                    <?php foreach ($pillars['kesimpulan_akhir'] as $butir): ?>
                                        <div class="rounded-lg bg-base-100/90 dark:bg-base-900/50 border border-emerald-200/70 dark:border-emerald-800/50 p-2.5 flex items-start gap-2 text-xs text-base-content shadow-2xs">
                                            <i data-lucide="check" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5"></i>
                                            <span class="leading-relaxed"><?= esc($butir) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-xs text-base-content/70 italic">Kesimpulan terangkum dalam naskah dinas resmi.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            <?php else: ?>
                <!-- Placeholder saat belum tersedia -->
                <div class="py-12 text-center text-base-content/60 space-y-1">
                    <p class="font-semibold text-xs sm:text-sm text-base-content">Ringkasan Rapat Belum Tersedia</p>
                    <p class="text-[11px] sm:text-xs text-base-content/70">Data intisari rapat akan otomatis ditampilkan di sini setelah proses AI selesai.</p>
                </div>
            <?php endif; ?>

        </div>

        <!-- Tab 2: Risalah (format naskah dinas) -->
        <div id="tab_panel_risalah" role="tabpanel" class="w-full bg-base-100 border border-base-200 rounded-box p-6 sm:p-10 shadow-xs space-y-6 hidden">
            
            <?php if ($minutes && ! empty($minutes['ringkasan_eksekutif'])): ?>
                <!-- Action Bar & Verification Status -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-200 pb-4">
                    <!-- Status Verifikasi -->
                    <div class="flex items-center gap-2">
                        <?php if ($isFinal): ?>
                            <span class="badge badge-sm bg-success/15 border border-success/40 text-success font-semibold text-[11px] gap-1.5 py-1 px-2.5">
                                <i data-lucide="check-check" class="h-3.5 w-3.5"></i> Naskah Final &amp; Sah
                            </span>
                            <span class="text-[11px] text-base-content/60">Diverifikasi pada <?= esc(date('d/m/Y H:i', strtotime((string) ($minutes['verified_at'] ?? $minutes['updated_at'])))) ?> WITA</span>
                        <?php else: ?>
                            <span class="badge badge-sm bg-warning/15 border border-warning/40 text-base-content font-semibold text-[11px] gap-1.5 py-1 px-2.5">
                                <i data-lucide="file-edit" class="h-3.5 w-3.5 text-warning"></i> Draf Risalah
                            </span>
                            <span id="dirty_badge" class="badge badge-xs badge-info text-white font-mono text-[10px] hidden">Ada Perubahan</span>
                            <span class="text-[11px] text-base-content/60">Perlu peninjauan &amp; verifikasi</span>
                        <?php endif; ?>
                    </div>

                    <!-- Action Controls -->
                    <div class="flex items-center gap-2">
                        <?php if (! $isFinal): ?>
                            <button type="button" id="btn_toggle_edit_risalah" class="btn btn-xs btn-outline gap-1.5 font-semibold hover:bg-base-200">
                                <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                                <span id="btn_toggle_edit_text">Sunting Naskah</span>
                            </button>

                            <form method="post" action="<?= base_url('admin/notulen/finalize/' . $minutes['id']) ?>" onsubmit="return confirm('Sahkan dan finalisasi naskah risalah rapat ini? Setelah difinalisasi, risalah berstatus resmi dan siap diakses anggota.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-xs btn-success text-white font-bold gap-1.5 shadow-xs">
                                    <i data-lucide="check-check" class="h-3.5 w-3.5"></i>
                                    Sahkan / Finalisasi
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= base_url('admin/notulen/unfinalize/' . $minutes['id']) ?>" onsubmit="return confirm('Buka kunci naskah risalah untuk melakukan revisi atau penyuntingan ulang?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-xs btn-ghost text-warning hover:bg-warning/15 font-semibold gap-1.5">
                                    <i data-lucide="lock-open" class="h-3.5 w-3.5"></i>
                                    Buka Kunci Revisi
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 1. Mode Tampilan Normal (Naskah Dinas) -->
                <div id="risalah_view_mode" class="space-y-6">
                    <!-- Kop Naskah Dinas DPRD Sulteng -->
                    <div class="text-center border-b-2 border-base-content/20 pb-5 space-y-1.5">
                        <p class="text-xs font-bold uppercase tracking-widest text-base-content/75">Dewan Perwakilan Rakyat Daerah Provinsi Sulawesi Tengah</p>
                        <h2 class="text-lg sm:text-xl font-bold uppercase text-base-content tracking-wide">RISALAH RAPAT</h2>
                        <p class="text-sm font-bold text-base-content"><?= esc($judulRapat) ?></p>
                        <p class="text-xs text-base-content/80">Hari/Tanggal: <strong><?= esc($tanggalRapat) ?></strong> &nbsp;|&nbsp; Waktu: <strong><?= ! empty($schedule['waktu_mulai']) ? substr((string) $schedule['waktu_mulai'], 0, 5) : '09:00' ?> WITA</strong></p>
                    </div>

                    <!-- Konten Naskah Lengkap -->
                    <div id="risalah_preview_text" class="prose max-w-none text-xs sm:text-sm text-base-content leading-relaxed whitespace-pre-wrap font-sans text-justify pl-2">
<?= esc($minutes['ringkasan_eksekutif']) ?>
                    </div>
                </div>

                <!-- 2. Mode Sunting / Editor 3 Seksi Bersambung (Hidden by Default) -->
                <?php
                $p1Text = $pillars['ringkasan_utama'] ?? '';
                $p2Text = '';
                $p3Text = '';

                if (! empty($minutes['ringkasan_eksekutif'])) {
                    $rawMinutesText = trim((string) $minutes['ringkasan_eksekutif']);
                    $p2Pattern = '/(?:^|\n)(?:(?:II|\b2)\.\s*POIN[^\n]*\n|#+\s*2\.\s*POIN[^\n]*\n)([\s\S]*?)(?=(?:\n(?:III|\b3)\.\s*KESIMPULAN|\n#+\s*3\.\s*KESIMPULAN|$))/i';
                    $p3Pattern = '/(?:^|\n)(?:(?:III|\b3)\.\s*KESIMPULAN[^\n]*\n|#+\s*3\.\s*KESIMPULAN[^\n]*\n)([\s\S]*?)$/i';
                    
                    if (preg_match($p2Pattern, $rawMinutesText, $m2)) {
                        $p2Text = trim($m2[1]);
                    }
                    if (preg_match($p3Pattern, $rawMinutesText, $m3)) {
                        $p3Text = trim($m3[1]);
                    }
                }

                if (empty($p2Text) && ! empty($pillars['poin_pembahasan'])) {
                    $pLines = [];
                    foreach ($pillars['poin_pembahasan'] as $i => $poin) {
                        $timeStr = ! empty($poin['waktu']) ? "[{$poin['waktu']}] " : "";
                        $itemLines = [($i + 1) . ". {$timeStr}Topik: " . ($poin['topik'] ?? '')];
                        if (! empty($poin['pembicara'])) {
                            $itemLines[] = "   - Pembicara: " . $poin['pembicara'];
                        }
                        if (! empty($poin['uraian'])) {
                            $itemLines[] = "   - Uraian: " . $poin['uraian'];
                        }
                        $pLines[] = implode("\n", $itemLines);
                    }
                    $p2Text = implode("\n\n", $pLines);
                }

                if (empty($p3Text) && ! empty($pillars['kesimpulan_akhir'])) {
                    $cLines = [];
                    foreach ($pillars['kesimpulan_akhir'] as $i => $c) {
                        $cLines[] = ($i + 1) . ". " . $c;
                    }
                    $p3Text = implode("\n", $cLines);
                }
                ?>
                <div id="risalah_edit_mode" class="space-y-4 hidden">
                    <form id="notulen_minutes_form" method="post" action="<?= base_url('admin/notulen/update-minutes/' . $minutes['id']) ?>" class="space-y-4">
                        <?= csrf_field() ?>
                        
                        <div class="flex items-center justify-between border-b border-base-200 pb-2.5">
                            <div class="flex items-center gap-2">
                                <i data-lucide="file-edit" class="h-4 w-4 text-primary"></i>
                                <span class="text-xs font-bold uppercase tracking-wider text-base-content/80">Editor Risalah (3 Seksi Terkunci)</span>
                            </div>
                            <span class="text-[11px] text-base-content/60">Shortcut: Tekan <kbd class="kbd kbd-xs font-mono">Ctrl + S</kbd> untuk simpan cepat</span>
                        </div>

                        <!-- Seksi 1: Ringkasan Utama -->
                        <div class="space-y-0">
                            <div class="flex items-center justify-between text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/40 py-2 px-3.5 rounded-t-xl border border-b-0 border-indigo-200/80 dark:border-indigo-800/40">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="sparkles" class="h-3.5 w-3.5"></i>
                                    <span>I. RINGKASAN UTAMA</span>
                                </div>
                                <span class="text-[10px] font-normal opacity-75">Latar belakang, postur anggaran, dan gambaran umum rapat</span>
                            </div>
                            <textarea id="section_ringkasan"
                                      name="section_ringkasan"
                                      rows="6"
                                      class="notulen-editor-section textarea textarea-bordered w-full font-sans text-xs sm:text-sm leading-relaxed p-3.5 bg-base-200/20 border-indigo-200/80 dark:border-indigo-800/40 rounded-b-xl rounded-t-none focus:border-primary focus:outline-none"
                                      placeholder="Tuliskan intisari komprehensif rapat di sini..."><?= esc($p1Text) ?></textarea>
                        </div>

                        <!-- Seksi 2: Poin-Poin Pembahasan -->
                        <div class="space-y-0">
                            <div class="flex items-center justify-between text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 py-2 px-3.5 rounded-t-xl border border-b-0 border-amber-200/80 dark:border-amber-800/40">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="list-ordered" class="h-3.5 w-3.5"></i>
                                    <span>II. POIN-POIN PEMBAHASAN</span>
                                </div>
                                <span class="text-[10px] font-normal opacity-75">Rincian pokok bahasan, pandangan fraksi/komisi, dan tanggapan</span>
                            </div>
                            <textarea id="section_pembahasan"
                                      name="section_pembahasan"
                                      rows="12"
                                      class="notulen-editor-section textarea textarea-bordered w-full font-sans text-xs sm:text-sm leading-relaxed p-3.5 bg-base-200/20 border-amber-200/80 dark:border-amber-800/40 rounded-b-xl rounded-t-none focus:border-primary focus:outline-none"
                                      placeholder="1. Topik: ...&#10;   - Pembicara: ...&#10;   - Uraian: ..."><?= esc($p2Text) ?></textarea>
                        </div>

                        <!-- Seksi 3: Kesimpulan & Keputusan Akhir -->
                        <div class="space-y-0">
                            <div class="flex items-center justify-between text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 py-2 px-3.5 rounded-t-xl border border-b-0 border-emerald-200/80 dark:border-emerald-800/40">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="check-square" class="h-3.5 w-3.5"></i>
                                    <span>III. KESIMPULAN &amp; KEPUTUSAN AKHIR</span>
                                </div>
                                <span class="text-[10px] font-normal opacity-75">Butir-butir kesepakatan dan rekomendasi resmi yang disahkan</span>
                            </div>
                            <textarea id="section_kesimpulan"
                                      name="section_kesimpulan"
                                      rows="6"
                                      class="notulen-editor-section textarea textarea-bordered w-full font-sans text-xs sm:text-sm leading-relaxed p-3.5 bg-base-200/20 border-emerald-200/80 dark:border-emerald-800/40 rounded-b-xl rounded-t-none focus:border-primary focus:outline-none"
                                      placeholder="1. Kesimpulan pertama...&#10;2. Kesimpulan kedua..."><?= esc($p3Text) ?></textarea>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-base-200">
                            <div class="flex items-center gap-2 text-xs text-base-content/60">
                                <span id="last_saved_time"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btn_cancel_edit_risalah" class="btn btn-ghost btn-sm text-xs">
                                    Tutup / Batal
                                </button>
                                <button type="submit" id="btn_save_draft" class="btn btn-primary btn-sm text-white font-bold gap-1.5 shadow-xs">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="py-14 text-center text-base-content/60 space-y-1">
                    <p class="font-semibold text-xs sm:text-sm text-base-content">Naskah Risalah Belum Tersedia</p>
                    <p class="text-[11px] sm:text-xs text-base-content/70">Format naskah risalah resmi rapat akan ditampilkan di sini setelah proses AI selesai.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- 4. Bagian bawah: audio asli dan aksi cepat -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- Kiri: Hub Pemutar Audio Rapat Asli (7 Kolom) -->
        <div class="lg:col-span-7 card card-border bg-base-100 shadow-sm border-base-200 rounded-2xl">
            <div class="card-body p-5 space-y-3.5">
                <div class="flex items-center justify-between border-b border-base-200 pb-2.5">
                    <div class="flex items-center gap-2">
                        <i data-lucide="headphones" class="h-4 w-4 text-primary"></i>
                        <h3 class="text-xs font-black text-base-content">Audio Rapat (Asli)</h3>
                    </div>
                    <span class="badge badge-xs badge-ghost font-mono text-[10px] px-2 py-0.5">
                        <?= round($job['audio_size'] / (1024 * 1024), 2) ?> MB · <?= esc(pathinfo((string) $job['audio_filename'], PATHINFO_EXTENSION) ?: 'WAV') ?>
                    </span>
                </div>

                <?php if ($hasAudioFile): ?>
                    <audio id="audio_player" controls class="w-full focus:outline-none" preload="metadata" aria-label="Pemutar Audio Rekaman Rapat">
                        <source src="<?= base_url('admin/notulen/audio/' . $job['id']) ?>" type="audio/mpeg">
                        Peramban Anda tidak mendukung pemutar audio HTML5.
                    </audio>
                <?php else: ?>
                    <div class="rounded-xl bg-base-200 p-4 text-center text-xs text-base-content/60">
                        <i data-lucide="hard-drive" class="h-6 w-6 mx-auto mb-1.5 text-base-content/40"></i>
                        Berkas audio telah dibersihkan untuk retensi penyimpanan server.
                    </div>
                <?php endif; ?>

                <div class="alert alert-info/10 border border-info/20 py-2 px-3 text-[11px] flex items-center gap-2 text-info-content rounded-lg">
                    <i data-lucide="info" class="h-3.5 w-3.5 shrink-0 text-info"></i>
                    <span>Audio asli tidak dapat diubah dan digunakan sebagai sumber kebenaran utama.</span>
                </div>
            </div>
        </div>

        <!-- Kanan: Panel 4 Tombol Aksi Cepat (5 Kolom) -->
        <div class="lg:col-span-5 card card-border bg-base-100 shadow-sm border-base-200 rounded-2xl">
            <div class="card-body p-5 space-y-3.5">
                <div class="flex items-center gap-2 border-b border-base-200 pb-2.5">
                    <i data-lucide="zap" class="h-4 w-4 text-primary"></i>
                    <h3 class="text-xs font-black text-base-content uppercase tracking-wider">Aksi Cepat</h3>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    
                    <!-- 1. Unduh Audio Asli -->
                    <?php if ($hasAudioFile): ?>
                        <a href="<?= base_url('admin/notulen/audio/' . $job['id']) ?>" download="<?= esc($job['audio_filename']) ?>"
                           class="notulen-action-card border border-base-300 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-base-100 hover:bg-base-200/50">
                            <i data-lucide="download" class="h-5 w-5 text-primary"></i>
                            <span class="text-xs font-bold text-base-content">Unduh Audio Asli</span>
                            <span class="text-[10px] text-base-content/50 font-medium">(mp3 / wav)</span>
                        </a>
                    <?php else: ?>
                        <div class="border border-base-200 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-base-200/30 opacity-40">
                            <i data-lucide="download" class="h-5 w-5"></i>
                            <span class="text-xs font-bold">Unduh Audio</span>
                            <span class="text-[10px]">(Tidak tersedia)</span>
                        </div>
                    <?php endif; ?>

                    <!-- 2. Unduh Transkrip -->
                    <?php if ($isCompleted && ! empty($transcripts['full_text'])): ?>
                        <a href="<?= base_url('admin/notulen/download-transcript/' . $job['id']) ?>"
                           class="notulen-action-card border border-base-300 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-base-100 hover:bg-base-200/50">
                            <i data-lucide="file-text" class="h-5 w-5 text-secondary"></i>
                            <span class="text-xs font-bold text-base-content">Unduh Transkrip</span>
                            <span class="text-[10px] text-base-content/50 font-medium">(.txt utuh)</span>
                        </a>
                    <?php else: ?>
                        <div class="border border-base-300 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-base-200/50 cursor-not-allowed select-none" title="Transkrip dapat diunduh setelah proses AI selesai">
                            <i data-lucide="file-text" class="h-5 w-5 text-base-content/60"></i>
                            <span class="text-xs font-bold text-base-content">Unduh Transkrip</span>
                            <span class="text-[10px] text-base-content/60 font-medium">(Setelah proses selesai)</span>
                        </div>
                    <?php endif; ?>

                    <!-- 3. Cetak Risalah PDF -->
                    <?php if ($isCompleted && $minutes && ! empty($minutes['ringkasan_eksekutif'])): ?>
                        <a href="<?= base_url('admin/notulen/export-pdf/' . $minutes['id']) ?>" target="_blank"
                           class="notulen-action-card border border-base-300 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-base-100 hover:bg-base-200/50">
                            <i data-lucide="printer" class="h-5 w-5 text-success"></i>
                            <span class="text-xs font-bold text-base-content">Cetak Risalah</span>
                            <span class="text-[10px] text-base-content/50 font-medium">(Naskah Resmi PDF)</span>
                        </a>
                    <?php else: ?>
                        <div class="border border-base-300 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-base-200/50 cursor-not-allowed select-none" title="Risalah dapat dicetak setelah proses AI selesai">
                            <i data-lucide="printer" class="h-5 w-5 text-base-content/60"></i>
                            <span class="text-xs font-bold text-base-content">Cetak Risalah</span>
                            <span class="text-[10px] text-base-content/60 font-medium">(Setelah proses selesai)</span>
                        </div>
                    <?php endif; ?>

                    <!-- 4. Riwayat Proses & Versi -->
                    <button type="button" data-hs-overlay="#modal_riwayat_proses"
                            class="notulen-action-card border border-slate-200 dark:border-slate-800 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition">
                        <i data-lucide="history" class="size-5 text-amber-500"></i>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Riwayat Proses</span>
                        <span class="text-[10px] text-slate-400 font-mono">Audit Log & Info</span>
                    </button>

                </div>
            </div>
        </div>

    </div>

    <!-- 5. Banner footer resmi -->
    <div class="py-3 px-4 text-xs flex items-center gap-3 rounded-xl border border-amber-200 dark:border-amber-900/40 bg-amber-50/60 dark:bg-amber-950/20 text-slate-700 dark:text-slate-300">
        <i data-lucide="shield-alert" class="size-5 shrink-0 text-amber-500"></i>
        <span><strong>Dokumen ini bersifat resmi.</strong> Rekaman transkripsi dan intisari risalah AI bersumber langsung dari rekaman audio rapat asli untuk memitigasi manipulasi dan menjamin akuntabilitas data kedewanan.</span>
    </div>

</div>

<!-- 6. Modal riwayat proses dan audit log -->
<div id="modal_riwayat_proses" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="modal_riwayat_proses_label">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-slate-200 shadow-xl rounded-2xl pointer-events-auto dark:bg-slate-900 dark:border-slate-800">
            <div class="flex justify-between items-center py-3.5 px-4 sm:px-6 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="size-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-300 shrink-0">
                        <i data-lucide="history" class="size-4"></i>
                    </div>
                    <div>
                        <h3 id="modal_riwayat_proses_label" class="font-bold text-sm text-slate-800 dark:text-slate-200">Riwayat Proses & Audit Log AI</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Jejak eksekusi pipeline kecerdasan buatan</p>
                    </div>
                </div>
                <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-400" data-hs-overlay="#modal_riwayat_proses">
                    <span class="sr-only">Tutup</span>
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-3 text-xs">
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-slate-500 dark:text-slate-400">Job ID:</span>
                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">#<?= (int) $job['id'] ?></span>
                </div>
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-slate-500 dark:text-slate-400">Waktu Mulai Unggah:</span>
                    <span class="font-mono text-slate-800 dark:text-slate-200"><?= esc($job['created_at']) ?></span>
                </div>
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-slate-500 dark:text-slate-400">Durasi Rekaman Audio:</span>
                    <span class="font-mono text-slate-800 dark:text-slate-200"><?= esc($durationFormatted) ?> (<?= $durationMin ? "{$durationMin} Menit" : '-' ?>)</span>
                </div>
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-slate-500 dark:text-slate-400">Jumlah Segmen Audio:</span>
                    <span class="font-mono text-slate-800 dark:text-slate-200"><?= (int) $job['total_chunks'] ?> segmen chunk</span>
                </div>
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-slate-500 dark:text-slate-400">Model Transkripsi &amp; Risalah:</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200" id="ai_model_meta_text"><?= esc($aiModelLabel ?? \App\Libraries\Notulen\NotulenService::formatAiModelLabel($job['ai_model'] ?? null)) ?></span>
                </div>
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-slate-500 dark:text-slate-400">Status Integritas:</span>
                    <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-md text-[11px] font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                        <i data-lucide="check" class="size-2.5"></i> Terverifikasi Sah
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-slate-400">Terakhir Diperbarui:</span>
                    <span class="font-mono text-slate-800 dark:text-slate-200"><?= esc($job['updated_at'] ?? $job['created_at']) ?></span>
                </div>
            </div>

            <div class="flex justify-end items-center gap-x-2 py-3 px-4 sm:px-6 border-t border-slate-200 dark:border-slate-800">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition" data-hs-overlay="#modal_riwayat_proses">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

