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
         class="bg-white border border-slate-200 rounded-2xl shadow-xs dark:bg-slate-900 dark:border-slate-800 p-4 sm:p-5 space-y-4">
        
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Alur Proses AI</span>
                <span id="ai_model_badge" class="py-1 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-full border border-slate-200 bg-slate-50 text-slate-700 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
                    <i data-lucide="sparkles" class="size-3 text-slate-500 dark:text-slate-400"></i>
                    <span id="ai_model_label_text"><?= esc($aiModelLabel ?? \App\Libraries\Notulen\NotulenService::formatAiModelLabel($job['ai_model'] ?? null)) ?></span>
                </span>
            </div>

            <!-- Kontrol Proses AI -->
            <div class="flex items-center gap-2" id="notulen_process_controls">
                <?php if ($isCancelled): ?>
                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg text-xs font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">
                        <i data-lucide="pause" class="size-3 fill-current text-amber-500"></i> Dihentikan
                    </span>
                    <form method="post" action="<?= base_url('admin/notulen/retry/' . $job['id']) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="py-1 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition cursor-pointer">
                            <i data-lucide="play" class="size-3.5 fill-current"></i>
                            Lanjutkan Proses
                        </button>
                    </form>
                <?php elseif ($isFailed): ?>
                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg text-xs font-semibold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/60">
                        <i data-lucide="alert-triangle" class="size-3 text-rose-500"></i> Gagal
                    </span>
                    <form method="post" action="<?= base_url('admin/notulen/retry/' . $job['id']) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="py-1 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg bg-rose-600 text-white hover:bg-rose-700 shadow-xs transition cursor-pointer">
                            <i data-lucide="rotate-cw" class="size-3"></i>
                            Coba Ulang
                        </button>
                    </form>
                <?php elseif ($isCompleted): ?>
                    <?php if ($isFinal): ?>
                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                            <i data-lucide="check-check" class="size-3.5 text-emerald-600 dark:text-emerald-400"></i> Risalah Final &amp; Sah
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg text-xs font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">
                            <i data-lucide="file-edit" class="size-3.5 text-amber-500"></i> Draf Siap Ditinjau
                        </span>
                    <?php endif; ?>
                <?php elseif ($isInProgress || $job['status'] === 'queued'): ?>
                    <?php if (! empty($job['cancel_requested'])): ?>
                        <button type="button" disabled class="py-1 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 cursor-not-allowed shadow-xs opacity-90">
                            <span class="animate-spin inline-block size-3 border-2 border-current border-t-transparent rounded-full" role="status" aria-label="loading"></span>
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

        <!-- Live Progress Bar Section -->
        <div id="live_progress_panel" class="<?= ($isInProgress || $job['status'] === 'queued') ? '' : 'hidden ' ?>rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-blue-200 dark:border-blue-800/60 p-3.5 space-y-2">
            <div class="flex items-center justify-between text-xs font-semibold">
                <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                    <span class="animate-spin inline-block size-3.5 border-2 border-current border-t-transparent rounded-full" role="status" aria-label="loading"></span>
                    <span id="live_status_title"><?= esc($job['current_step'] ?? 'Memproses rekaman audio...') ?></span>
                </div>
                <span id="live_progress_percent" class="font-mono text-blue-600 dark:text-blue-400"><?= (int) ($job['progress_percent'] ?? 0) ?>%</span>
            </div>
            <div class="flex w-full h-2 bg-slate-200 rounded-full overflow-hidden dark:bg-slate-700"
                role="progressbar" aria-valuenow="<?= (int) ($job['progress_percent'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Kemajuan pemrosesan AI">
                <div id="live_progress_bar" class="flex flex-col justify-center overflow-hidden bg-blue-600 text-xs text-white text-center whitespace-nowrap transition-all duration-500" style="width: <?= (int) ($job['progress_percent'] ?? 0) ?>%"></div>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-mono">
                <div class="flex items-center gap-2">
                    <span id="live_current_step"><?= esc($job['current_step'] ?? 'Menyiapkan audio') ?></span>
                </div>
                <span id="live_chunk_info"><?= (int) ($job['completed_chunks'] ?? 0) ?> / <?= (int) ($job['total_chunks'] ?? 0) ?> segmen</span>
            </div>
        </div>

        <!-- Stepper Ringkas 5 Langkah -->
        <div class="overflow-x-auto py-2.5 px-1">
            <div class="notulen-stepper-track min-w-[600px]">
                <div class="notulen-stepper-line"></div>

                <!-- Step 1: Unggah Rekaman -->
                <div class="notulen-step-item" id="step_upload_item">
                    <div class="notulen-step-circle done">
                        <i data-lucide="cloud-upload" class="size-5"></i>
                    </div>
                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200 mt-2">1. Unggah Rekaman</span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5 flex items-center gap-1">
                        <i data-lucide="check-circle-2" class="size-3 text-emerald-600 dark:text-emerald-400"></i>
                        <?= substr((string) $job['created_at'], 8, 2) . ' ' . date('M H:i', strtotime((string) $job['created_at'])) ?>
                    </span>
                </div>

                <!-- Step 2: Pemrosesan Rekaman -->
                <?php
                $isStep2Done = in_array($job['status'], ['transcribing', 'summarizing', 'completed'], true);
                $isStep2Active = in_array($job['status'], ['chunking', 'queued'], true);
                $step2CircleClass = $isStep2Done ? 'done' : ($isStep2Active ? 'active' : '');
                ?>
                <div class="notulen-step-item" id="step_chunking_item">
                    <div class="notulen-step-circle <?= $step2CircleClass ?>" id="step_chunking_circle">
                        <i data-lucide="sliders" class="size-5"></i>
                    </div>
                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200 mt-2">2. Pemrosesan Rekaman</span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5 flex items-center gap-1" id="step_chunking_status">
                        <?php if ($isStep2Done): ?>
                            <i data-lucide="check-circle-2" class="size-3 text-emerald-600 dark:text-emerald-400"></i>
                            Selesai
                        <?php elseif ($isStep2Active): ?>
                            <span class="animate-spin inline-block size-3 border-2 border-current border-t-transparent text-emerald-600 dark:text-emerald-400 rounded-full" role="status" aria-label="loading"></span>
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
                        <i data-lucide="mic" class="size-5"></i>
                    </div>
                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200 mt-2">3. Transkripsi Suara</span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5 flex items-center gap-1" id="step_transcribing_status">
                        <?php if ($isStep3Done): ?>
                            <i data-lucide="check-circle-2" class="size-3 text-emerald-600 dark:text-emerald-400"></i>
                            Selesai
                        <?php elseif ($isStep3Active): ?>
                            <span class="animate-spin inline-block size-3 border-2 border-current border-t-transparent text-emerald-600 dark:text-emerald-400 rounded-full" role="status" aria-label="loading"></span>
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
                        <i data-lucide="sparkles" class="size-5"></i>
                    </div>
                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200 mt-2">4. Penyusunan Risalah</span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5 flex items-center gap-1" id="step_summarizing_status">
                        <?php if ($isStep4Done): ?>
                            <i data-lucide="check-circle-2" class="size-3 text-emerald-600 dark:text-emerald-400"></i>
                            Selesai
                        <?php elseif ($isStep4Active): ?>
                            <span class="animate-spin inline-block size-3 border-2 border-current border-t-transparent text-emerald-600 dark:text-emerald-400 rounded-full" role="status" aria-label="loading"></span>
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
                            <i data-lucide="check-check" class="size-5"></i>
                        <?php else: ?>
                            <i data-lucide="file-check" class="size-5"></i>
                        <?php endif; ?>
                    </div>
                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200 mt-2" id="step_completed_title">
                        <?= $isFinal ? '5. Risalah Sah' : '5. Risalah Siap' ?>
                    </span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5 flex items-center gap-1" id="step_completed_status">
                        <?php if ($isCompleted): ?>
                            <?php if ($isFinal): ?>
                                <i data-lucide="check-check" class="size-3 text-emerald-600 dark:text-emerald-400"></i>
                                Final &amp; Sah
                            <?php else: ?>
                                <i data-lucide="file-edit" class="size-3 text-amber-600 dark:text-amber-400"></i>
                                Siap Ditinjau
                            <?php endif; ?>
                        <?php else: ?>
                            Menunggu
                        <?php endif; ?>
                    </span>
                </div>

            </div>
        </div>

        <!-- Info Alert Bawah Stepper -->
        <?php if ($isInProgress || $job['status'] === 'queued'): ?>
            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800/40 dark:border-slate-800 text-xs flex items-center gap-2 text-slate-600 dark:text-slate-300 mt-2">
                <i data-lucide="info" class="size-4 shrink-0 text-slate-400"></i>
                <span>Proses berjalan di background. Anda dapat menutup halaman ini. Sistem akan otomatis memuat hasil begitu selesai.</span>
            </div>
        <?php elseif ($isCancelled): ?>
            <div class="p-3 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-900/40 text-xs flex items-center gap-2 text-amber-800 dark:text-amber-300 mt-2">
                <i data-lucide="pause" class="size-4 shrink-0 text-amber-600 fill-current"></i>
                <span>Proses AI dihentikan sementara. Klik tombol <strong>Lanjutkan Proses</strong> di bagian atas untuk melanjutkan dari checkpoint terakhir.</span>
            </div>
        <?php elseif ($isFailed): ?>
            <div class="p-3 rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-950/20 dark:border-rose-900/40 text-xs flex items-center gap-2 text-rose-800 dark:text-rose-300 mt-2">
                <i data-lucide="alert-triangle" class="size-4 shrink-0 text-rose-600"></i>
                <span>Pemrosesan mengalami kendala: <strong class="text-rose-700 dark:text-rose-400 font-semibold"><?= esc($job['error_message']) ?: 'Koneksi AI timeout.' ?></strong></span>
            </div>
        <?php endif; ?>

    </div>

    <!-- 2. Card identitas rapat -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-xs dark:bg-slate-900 dark:border-slate-800 p-4 sm:p-5 flex flex-col md:flex-row items-start justify-between gap-4">
        
        <!-- Metadata Rapat -->
        <div class="space-y-1.5 min-w-0 flex-1">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    <?= esc($badgeKategori) ?>
                </span>
            </div>

            <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 leading-snug" title="<?= esc($judulRapat) ?>">
                <?= esc($judulRapat) ?>
            </h1>

            <div class="flex flex-wrap items-center gap-y-1.5 gap-x-5 text-xs text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5">
                    <i data-lucide="calendar" class="size-3.5 text-slate-400"></i>
                    <?= esc($tanggalRapat) ?>
                </span>
                <span class="flex items-center gap-1.5">
                    <i data-lucide="clock" class="size-3.5 text-slate-400"></i>
                    <?= ! empty($schedule['waktu_mulai']) ? substr((string) $schedule['waktu_mulai'], 0, 5) : '09:00' ?> WITA
                    <span class="font-mono text-slate-600 dark:text-slate-300 font-medium">(<?= esc($durationFormatted) ?>)</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <i data-lucide="map-pin" class="size-3.5 text-slate-400"></i>
                    <?= esc($schedule['ruangan'] ?? 'Ruang Rapat Paripurna DPRD Provinsi Sulawesi Tengah') ?>
                </span>
            </div>
        </div>

        <!-- Tombol Kembali -->
        <div class="flex items-center gap-2 shrink-0 self-start">
            <a href="<?= base_url('admin/notulen') ?>" class="py-2 px-3 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-700 shadow-xs hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700 transition">
                <i data-lucide="arrow-left" class="size-4"></i>
                Kembali
            </a>
        </div>

    </div>

    <div id="tab_panel_risalah" class="w-full bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 shadow-xs dark:bg-slate-900 dark:border-slate-800 space-y-6">
            
            <?php if ($minutes && ! empty($minutes['ringkasan_eksekutif'])): ?>
                <!-- Action Bar & Verification Status -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
                    <!-- Status Verifikasi -->
                    <div class="flex items-center gap-2">
                        <?php if ($isFinal): ?>
                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                <i data-lucide="check-check" class="size-3.5"></i> Naskah Final &amp; Sah
                            </span>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">Diverifikasi pada <?= esc(date('d/m/Y H:i', strtotime((string) ($minutes['verified_at'] ?? $minutes['updated_at'])))) ?> WITA</span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg text-xs font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">
                                <i data-lucide="file-edit" class="size-3.5 text-amber-500"></i> Draf Risalah
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Action Controls -->
                    <div class="flex items-center gap-2">
                        <?php if (! $isFinal): ?>
                            <button type="button" id="btn_toggle_edit_risalah" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-800 shadow-xs hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                                <i data-lucide="edit-3" class="size-3.5"></i>
                                <span id="btn_toggle_edit_text">Sunting Naskah</span>
                            </button>

                            <form method="post" action="<?= base_url('admin/notulen/finalize/' . $minutes['id']) ?>" onsubmit="return confirm('Sahkan dan finalisasi naskah risalah rapat ini? Setelah difinalisasi, risalah berstatus resmi dan siap diakses anggota.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition cursor-pointer">
                                    <i data-lucide="check-check" class="size-3.5"></i>
                                    Sahkan / Finalisasi
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= base_url('admin/notulen/unfinalize/' . $minutes['id']) ?>" onsubmit="return confirm('Buka kunci naskah risalah untuk melakukan revisi atau penyuntingan ulang?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-300 transition cursor-pointer">
                                    <i data-lucide="lock-open" class="size-3.5"></i>
                                    Buka Kunci Revisi
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 1. Mode Tampilan Normal (Naskah Dinas) -->
                <div id="risalah_view_mode" class="space-y-6">
                    <div class="text-center border-b-2 border-slate-200 dark:border-slate-800 pb-5 space-y-1.5">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Dewan Perwakilan Rakyat Daerah Provinsi Sulawesi Tengah</p>
                        <h2 class="text-lg sm:text-xl font-bold uppercase text-slate-900 dark:text-slate-100 tracking-wide">RISALAH RAPAT</h2>
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200"><?= esc($judulRapat) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Hari/Tanggal: <strong><?= esc($tanggalRapat) ?></strong> &nbsp;|&nbsp; Waktu: <strong><?= ! empty($schedule['waktu_mulai']) ? substr((string) $schedule['waktu_mulai'], 0, 5) : '09:00' ?> WITA</strong></p>
                    </div>

                    <div id="risalah_preview_text" class="prose max-w-none text-xs sm:text-sm text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap font-sans text-justify pl-2">
<?= esc($minutes['ringkasan_eksekutif']) ?>
                    </div>
                </div>

                <!-- 2. Mode Sunting / Editor 3 Seksi Bersambung -->
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
                        
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                            <div class="flex items-center gap-2">
                                <i data-lucide="file-edit" class="size-4 text-emerald-600 dark:text-emerald-400"></i>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Editor Risalah (3 Seksi Terkunci)</span>
                            </div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">Shortcut: Tekan <kbd class="py-0.5 px-1.5 text-[10px] font-mono bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md">Ctrl + S</kbd> untuk simpan cepat</span>
                        </div>

                        <!-- Seksi 1: Ringkasan Utama -->
                        <div class="space-y-0">
                            <div class="flex items-center justify-between text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/40 py-2 px-3.5 rounded-t-xl border border-b-0 border-indigo-200/80 dark:border-indigo-800/40">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="sparkles" class="size-3.5"></i>
                                    <span>I. RINGKASAN UTAMA</span>
                                </div>
                                <span class="text-[10px] font-normal opacity-75">Latar belakang, postur anggaran, dan gambaran umum rapat</span>
                            </div>
                            <textarea id="section_ringkasan"
                                      name="section_ringkasan"
                                      rows="6"
                                      class="notulen-editor-section py-3 px-3.5 block w-full border border-indigo-200/80 dark:border-indigo-800/40 rounded-b-xl rounded-t-none text-xs sm:text-sm font-sans leading-relaxed outline-none focus:outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:text-slate-200"
                                      placeholder="Tuliskan intisari komprehensif rapat di sini..."><?= esc($p1Text) ?></textarea>
                        </div>

                        <!-- Seksi 2: Poin-Poin Pembahasan -->
                        <div class="space-y-0">
                            <div class="flex items-center justify-between text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 py-2 px-3.5 rounded-t-xl border border-b-0 border-amber-200/80 dark:border-amber-800/40">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="list-ordered" class="size-3.5"></i>
                                    <span>II. POIN-POIN PEMBAHASAN</span>
                                </div>
                                <span class="text-[10px] font-normal opacity-75">Rincian pokok bahasan, pandangan fraksi/komisi, dan tanggapan</span>
                            </div>
                            <textarea id="section_pembahasan"
                                      name="section_pembahasan"
                                      rows="12"
                                      class="notulen-editor-section py-3 px-3.5 block w-full border border-amber-200/80 dark:border-amber-800/40 rounded-b-xl rounded-t-none text-xs sm:text-sm font-sans leading-relaxed outline-none focus:outline-none focus:border-amber-500 focus:ring-amber-500 dark:bg-slate-900 dark:text-slate-200"
                                      placeholder="1. Topik: ...&#10;   - Pembicara: ...&#10;   - Uraian: ..."><?= esc($p2Text) ?></textarea>
                        </div>

                        <!-- Seksi 3: Kesimpulan & Keputusan Akhir -->
                        <div class="space-y-0">
                            <div class="flex items-center justify-between text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 py-2 px-3.5 rounded-t-xl border border-b-0 border-emerald-200/80 dark:border-emerald-800/40">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="check-square" class="size-3.5"></i>
                                    <span>III. KESIMPULAN &amp; KEPUTUSAN AKHIR</span>
                                </div>
                                <span class="text-[10px] font-normal opacity-75">Butir-butir kesepakatan dan rekomendasi resmi yang disahkan</span>
                            </div>
                            <textarea id="section_kesimpulan"
                                      name="section_kesimpulan"
                                      rows="6"
                                      class="notulen-editor-section py-3 px-3.5 block w-full border border-emerald-200/80 dark:border-emerald-800/40 rounded-b-xl rounded-t-none text-xs sm:text-sm font-sans leading-relaxed outline-none focus:outline-none focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:text-slate-200"
                                      placeholder="1. Kesimpulan pertama...&#10;2. Kesimpulan kedua..."><?= esc($p3Text) ?></textarea>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                <span id="last_saved_time"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btn_cancel_edit_risalah" class="py-2 px-3 inline-flex items-center text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition cursor-pointer">
                                    Tutup / Batal
                                </button>
                                <button type="submit" id="btn_save_draft" class="py-2 px-3 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition cursor-pointer">
                                    <i data-lucide="save" class="size-4"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php elseif ($isInProgress || $job['status'] === 'queued'): ?>
                <!-- Preline Skeleton Loader saat AI sedang menyusun risalah -->
                <div class="space-y-4 animate-pulse">
                    <div class="text-center border-b border-slate-200 dark:border-slate-800 pb-5 space-y-2">
                        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-64 mx-auto"></div>
                        <div class="h-5 bg-slate-300 dark:bg-slate-600 rounded w-48 mx-auto"></div>
                        <div class="h-3.5 bg-slate-200 dark:bg-slate-700 rounded w-72 mx-auto"></div>
                    </div>
                    <div class="space-y-2.5 pt-2">
                        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-full"></div>
                        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-11/12"></div>
                        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-5/6"></div>
                        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-full"></div>
                        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-3/4"></div>
                    </div>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium pt-2 flex items-center justify-center gap-2">
                        <span class="animate-spin inline-block size-3.5 border-2 border-current border-t-transparent rounded-full" role="status" aria-label="loading"></span>
                        AI sedang memformat naskah dinas resmi DPRD Provinsi Sulawesi Tengah...
                    </p>
                </div>
            <?php else: ?>
                <div class="py-14 text-center text-slate-500 dark:text-slate-400 space-y-1">
                    <p class="font-semibold text-xs sm:text-sm text-slate-700 dark:text-slate-300">Naskah Risalah Belum Tersedia</p>
                    <p class="text-[11px] sm:text-xs">Format naskah risalah resmi rapat akan ditampilkan di sini setelah proses AI selesai.</p>
                </div>
            <?php endif; ?>

        </div>

    <!-- 4. Bagian bawah: audio asli dan aksi cepat -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- Kiri: Hub Pemutar Audio Rapat Asli (7 Kolom) -->
        <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl shadow-xs dark:bg-slate-900 dark:border-slate-800 p-5 space-y-3.5">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <i data-lucide="headphones" class="size-4 text-emerald-600 dark:text-emerald-400"></i>
                    <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">Audio Rapat (Asli)</h3>
                </div>
                <span class="py-0.5 px-2 text-[10px] font-mono rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                    <?= round($job['audio_size'] / (1024 * 1024), 2) ?> MB · <?= esc(pathinfo((string) $job['audio_filename'], PATHINFO_EXTENSION) ?: 'WAV') ?>
                </span>
            </div>

            <?php if ($hasAudioFile): ?>
                <audio id="audio_player" controls class="w-full focus:outline-none" preload="metadata" aria-label="Pemutar Audio Rekaman Rapat">
                    <source src="<?= base_url('admin/notulen/audio/' . $job['id']) ?>" type="audio/mpeg">
                    Peramban Anda tidak mendukung pemutar audio HTML5.
                </audio>
            <?php else: ?>
                <div class="rounded-xl bg-slate-100 dark:bg-slate-800/60 p-4 text-center text-xs text-slate-500 dark:text-slate-400">
                    <i data-lucide="hard-drive" class="size-6 mx-auto mb-1.5 text-slate-400"></i>
                    Berkas audio telah dibersihkan untuk retensi penyimpanan server.
                </div>
            <?php endif; ?>

            <div class="p-2.5 rounded-xl border border-blue-200 bg-blue-50/50 dark:bg-blue-950/30 dark:border-blue-900/40 text-[11px] flex items-center gap-2 text-blue-700 dark:text-blue-300">
                <i data-lucide="info" class="size-3.5 shrink-0 text-blue-500"></i>
                <span>Audio asli tidak dapat diubah dan digunakan sebagai sumber kebenaran utama.</span>
            </div>
        </div>

        <!-- Kanan: Panel 4 Tombol Aksi Cepat (5 Kolom) -->
        <div class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl shadow-xs dark:bg-slate-900 dark:border-slate-800 p-5 space-y-3.5">
            <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <i data-lucide="zap" class="size-4 text-emerald-600 dark:text-emerald-400"></i>
                <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Aksi Cepat</h3>
            </div>

            <div class="grid grid-cols-2 gap-3">
                
                <!-- 1. Unduh Audio Asli -->
                <?php if ($hasAudioFile): ?>
                    <a href="<?= base_url('admin/notulen/audio/' . $job['id']) ?>" download="<?= esc($job['audio_filename']) ?>"
                       class="notulen-action-card border border-slate-200 dark:border-slate-800 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/60 shadow-xs transition cursor-pointer">
                        <i data-lucide="download" class="size-5 text-emerald-600 dark:text-emerald-400"></i>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Unduh Audio Asli</span>
                        <span class="text-[10px] text-slate-400 font-medium">(mp3 / wav)</span>
                    </a>
                <?php else: ?>
                    <div class="border border-slate-200 dark:border-slate-800 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-slate-50 dark:bg-slate-800/30 text-slate-400 dark:text-slate-600 select-none">
                        <i data-lucide="download" class="size-5"></i>
                        <span class="text-xs font-bold">Unduh Audio</span>
                        <span class="text-[10px]">(Tidak tersedia)</span>
                    </div>
                <?php endif; ?>

                <!-- 2. Unduh Transkrip -->
                <?php if ($isCompleted && ! empty($transcripts['full_text'])): ?>
                    <a href="<?= base_url('admin/notulen/download-transcript/' . $job['id']) ?>"
                       class="notulen-action-card border border-slate-200 dark:border-slate-800 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/60 shadow-xs transition cursor-pointer">
                        <i data-lucide="file-text" class="size-5 text-amber-600 dark:text-amber-400"></i>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Unduh Transkrip</span>
                        <span class="text-[10px] text-slate-400 font-medium">(.txt utuh)</span>
                    </a>
                <?php else: ?>
                    <div class="border border-slate-200 dark:border-slate-800 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-slate-50 dark:bg-slate-800/30 text-slate-400 dark:text-slate-600 cursor-not-allowed select-none" title="Transkrip dapat diunduh setelah proses AI selesai">
                        <i data-lucide="file-text" class="size-5"></i>
                        <span class="text-xs font-bold">Unduh Transkrip</span>
                        <span class="text-[10px] font-medium">(Setelah proses selesai)</span>
                    </div>
                <?php endif; ?>

                <!-- 3. Cetak Risalah PDF -->
                <?php if ($isCompleted && $minutes && ! empty($minutes['ringkasan_eksekutif'])): ?>
                    <a href="<?= base_url('admin/notulen/export-pdf/' . $minutes['id']) ?>" target="_blank"
                       class="notulen-action-card border border-slate-200 dark:border-slate-800 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/60 shadow-xs transition cursor-pointer">
                        <i data-lucide="printer" class="size-5 text-emerald-600 dark:text-emerald-400"></i>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Cetak Risalah</span>
                        <span class="text-[10px] text-slate-400 font-medium">(Naskah Resmi PDF)</span>
                    </a>
                <?php else: ?>
                    <div class="border border-slate-200 dark:border-slate-800 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-slate-50 dark:bg-slate-800/30 text-slate-400 dark:text-slate-600 cursor-not-allowed select-none" title="Risalah dapat dicetak setelah proses AI selesai">
                        <i data-lucide="printer" class="size-5"></i>
                        <span class="text-xs font-bold">Cetak Risalah</span>
                        <span class="text-[10px] font-medium">(Setelah proses selesai)</span>
                    </div>
                <?php endif; ?>

                <!-- 4. Riwayat Proses & Versi -->
                <button type="button" data-hs-overlay="#modal_riwayat_proses"
                        class="notulen-action-card border border-slate-200 dark:border-slate-800 rounded-xl p-3 flex flex-col items-center justify-center gap-1 text-center bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/60 shadow-xs transition cursor-pointer">
                    <i data-lucide="history" class="size-5 text-amber-500"></i>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Riwayat Proses</span>
                    <span class="text-[10px] text-slate-400 font-mono">Audit Log &amp; Info</span>
                </button>

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

