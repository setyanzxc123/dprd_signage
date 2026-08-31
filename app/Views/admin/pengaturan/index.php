<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">Pengaturan Sistem</h1>
</div>

<form action="<?= base_url('admin/pengaturan/save') ?>" method="post" enctype="multipart/form-data"
    class="min-w-0 max-w-full space-y-5" id="settings-form"
    data-redirect-url="<?= base_url('admin/pengaturan') ?>"
    data-upload-start-url="/admin/pengaturan/media-upload/start"
    data-upload-chunk-url="/admin/pengaturan/media-upload/chunk"
    data-upload-cancel-url="/admin/pengaturan/media-upload/cancel"
    data-upload-token="<?= esc($mediaUploadToken, 'attr') ?>"
    data-upload-max="<?= (int) $mediaUploadMax ?>"
    data-upload-chunk-size="<?= (int) $mediaChunkSize ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="media_upload_key" id="media_upload_key" value="">

    <section class="card card-border min-w-0 max-w-full bg-base-100 shadow-sm">
        <div class="card-body min-w-0 gap-5 p-4 sm:p-5">
            <h2 class="card-title text-base">
                <i data-lucide="tv" class="h-5 w-5 text-primary"></i>
                Pengaturan Signage
            </h2>

            <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-2">
                <fieldset class="fieldset min-w-0 rounded-box border border-base-300 bg-base-200 p-4">
                    <legend class="fieldset-legend">Tema Layar</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="label cursor-pointer justify-start gap-3 rounded-field border border-base-300 bg-base-100 px-3 py-2">
                            <input type="radio" name="tema_signage" value="dark" class="radio radio-primary radio-sm"
                                <?= $settings['tema_signage'] === 'dark' ? 'checked' : '' ?> />
                            <span class="font-semibold">Dark</span>
                        </label>
                        <label class="label cursor-pointer justify-start gap-3 rounded-field border border-base-300 bg-base-100 px-3 py-2">
                            <input type="radio" name="tema_signage" value="light" class="radio radio-primary radio-sm"
                                <?= $settings['tema_signage'] === 'light' ? 'checked' : '' ?> />
                            <span class="font-semibold">Light</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="fieldset min-w-0 rounded-box border border-base-300 bg-base-200 p-4">
                    <legend class="fieldset-legend">Media Tampilan</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="label cursor-pointer justify-start gap-3 rounded-field border border-base-300 bg-base-100 px-3 py-2">
                            <input type="radio" name="media_mode" value="video" class="radio radio-primary radio-sm"
                                <?= $settings['media_mode'] === 'video' ? 'checked' : '' ?> />
                            <span class="font-semibold">Video</span>
                        </label>
                        <label class="label cursor-pointer justify-start gap-3 rounded-field border border-base-300 bg-base-100 px-3 py-2">
                            <input type="radio" name="media_mode" value="image" class="radio radio-primary radio-sm"
                                <?= $settings['media_mode'] === 'image' ? 'checked' : '' ?> />
                            <span class="font-semibold">Gambar</span>
                        </label>
                    </div>
                </fieldset>
            </div>

            <div class="grid min-w-0 grid-cols-12 gap-4 border-t border-base-300 pt-4">
                <fieldset class="fieldset col-span-12 min-w-0 lg:col-span-8">
                    <legend class="fieldset-legend">Upload Media</legend>
                    <input type="file" class="file-input file-input-sm w-full min-w-0 max-w-full overflow-hidden" id="media_file" name="media_file"
                        accept="video/mp4,video/webm,image/jpeg,image/png,image/webp" />
                    <p class="label block w-full min-w-0 whitespace-normal break-words">MP4, WebM, JPG, PNG, atau WebP. Maksimal 200 MB. File dikirim bertahap agar lebih stabil.</p>
                </fieldset>

                <fieldset class="fieldset col-span-12 min-w-0 lg:col-span-4">
                    <legend class="fieldset-legend">File Aktif</legend>
                    <?php if (! empty($settings['media_file'])): ?>
                        <div class="alert alert-info min-h-8 w-full min-w-0 max-w-full grid-cols-[auto_minmax(0,1fr)] overflow-hidden py-2 text-sm" role="status"
                            title="<?= esc(basename($settings['media_file'])) ?>">
                            <i data-lucide="file-check-2" class="h-4 w-4 shrink-0"></i>
                            <span class="min-w-0 truncate"><?= esc(basename($settings['media_file'])) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="alert min-h-8 py-2 text-sm" role="status">
                            <i data-lucide="file-x-2" class="h-4 w-4 shrink-0"></i>
                            <span>Belum ada file</span>
                        </div>
                    <?php endif; ?>
                </fieldset>
            </div>

            <fieldset class="fieldset min-w-0 rounded-box border border-base-300 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span class="font-bold">Running Text</span>
                    <label class="label cursor-pointer gap-2">
                        <span class="font-semibold">Aktif</span>
                        <input class="toggle toggle-primary toggle-sm" type="checkbox" role="switch" id="running_text_aktif"
                            name="running_text_aktif" value="1" <?= $settings['running_text_aktif'] ? 'checked' : '' ?> />
                    </label>
                </div>

                <textarea class="textarea mt-2 min-h-16 w-full min-w-0 max-w-full" id="running_text" name="running_text" rows="2"
                    placeholder="Contoh: Selamat datang di Gedung DPRD Provinsi Sulawesi Tengah."><?= esc($settings['running_text']) ?></textarea>

                <div class="mt-2 min-w-0 max-w-full overflow-hidden rounded-box bg-neutral p-3 text-neutral-content">
                    <div class="mb-2 flex items-center gap-1 text-xs font-bold uppercase tracking-wider opacity-60">
                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                        Pratinjau
                    </div>
                    <div class="settings-running-track" id="preview-track">
                        <span id="preview-text"><?= esc($settings['running_text']) ?: 'Teks berjalan akan tampil di sini...' ?></span>
                    </div>
                </div>
            </fieldset>
        </div>
    </section>

    <section class="card card-border min-w-0 max-w-full bg-base-100 shadow-sm" id="wa-integration-card">
        <div class="card-body min-w-0 gap-5 p-4 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="card-title text-base">
                    <i data-lucide="message-square" class="h-5 w-5 text-primary"></i>
                    Integrasi WhatsApp OTP Gateway
                </h2>
                <div class="flex items-center gap-2">
                    <span class="badge badge-outline badge-primary text-xs font-semibold" id="wa-provider-badge">
                        Provider: <?= esc(strtoupper($otpConfig->provider ?? 'HYBRID')) ?>
                    </span>
                    <button type="button" class="btn btn-ghost btn-xs gap-1" id="btn-refresh-wa-status" title="Periksa status koneksi WhatsApp">
                        <i data-lucide="refresh-cw" class="h-3.5 w-3.5" id="icon-refresh-wa"></i>
                        <span>Cek Status</span>
                    </button>
                </div>
            </div>

            <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-2" id="wa-status-grid">
                <div class="rounded-box border border-base-300 bg-base-200 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider opacity-70">Jalur Primer (Lokal)</span>
                        <span class="badge badge-sm badge-success font-bold">Biaya Rp 0</span>
                    </div>
                    <div id="wa-primary-status">
                        <?php if (! empty($whatsapp['connected'])): ?>
                            <div class="flex items-center gap-2 text-success font-semibold">
                                <i data-lucide="check-circle-2" class="h-5 w-5 shrink-0"></i>
                                <span>WhatsApp Gateway Terhubung</span>
                            </div>
                            <p class="text-xs text-base-content/80 mt-1">
                                No. Pengirim: <strong>+<?= esc($whatsapp['phone'] ?? '-') ?></strong>
                                <?php if (! empty($whatsapp['name'])): ?>
                                    (<?= esc($whatsapp['name']) ?>)
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <div class="flex items-center gap-2 text-error font-semibold">
                                <i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i>
                                <span>WhatsApp Belum Terhubung</span>
                            </div>
                            <p class="text-xs text-base-content/80 mt-1" id="wa-error-text">
                                <?= esc($whatsapp['error'] ?? 'Gateway belum terhubung. Silakan scan QR Code untuk menghubungkan nomor pengirim.') ?>
                            </p>
                            <div class="pt-2">
                                <a href="<?= esc($whatsapp['qr_url'] ?? 'http://127.0.0.1:3001/qr') ?>" target="_blank" rel="noopener noreferrer"
                                    class="btn btn-warning btn-xs gap-1.5 font-semibold" id="wa-qr-btn">
                                    <i data-lucide="qr-code" class="h-4 w-4"></i>
                                    <span>Buka Scan QR Code</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-box border border-base-300 bg-base-200 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider opacity-70">Jalur Cadangan (Cloud Fallback)</span>
                        <span class="badge badge-sm badge-info font-bold">Fazpass API</span>
                    </div>
                    <div>
                        <?php if (! empty($otpConfig->fazpassFallbackEnabled)): ?>
                            <div class="flex items-center gap-2 text-info font-semibold">
                                <i data-lucide="shield-check" class="h-5 w-5 shrink-0"></i>
                                <span>Fallback Otomatis Aktif</span>
                            </div>
                            <p class="text-xs text-base-content/80 mt-1">
                                Jika service Baileys mati atau terputus, sistem otomatis mengalihkan OTP ke Fazpass Cloud tanpa gangguan.
                            </p>
                        <?php else: ?>
                            <div class="flex items-center gap-2 text-warning font-semibold">
                                <i data-lucide="shield-alert" class="h-5 w-5 shrink-0"></i>
                                <span>Fallback Nonaktif</span>
                            </div>
                            <p class="text-xs text-base-content/80 mt-1">
                                Failover ke Fazpass dimatikan melalui konfigurasi FAZPASS_FALLBACK_ENABLED=false.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="form-actions-sticky mt-5 flex flex-col gap-3 sm:items-end">
        <div class="alert alert-info w-full max-w-xl" id="settings-upload-progress" hidden aria-live="polite">
            <div class="w-full">
                <div class="mb-2 grid grid-cols-[auto_1fr_auto] items-center gap-2 text-sm font-bold">
                    <span class="loading loading-spinner loading-sm" aria-hidden="true"></span>
                    <span id="settings-upload-status">Menyiapkan upload...</span>
                    <strong id="settings-upload-percent">0%</strong>
                </div>
                <progress class="progress progress-primary w-full" id="settings-upload-bar" value="0" max="100"></progress>
                <div class="mt-1 text-right text-xs font-semibold opacity-70" id="settings-upload-speed" hidden>
                    Mengukur kecepatan...
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-full gap-1 sm:btn-sm sm:w-auto" id="settings-submit-button">
            <span class="loading loading-spinner loading-sm" id="settings-submit-spinner" hidden aria-hidden="true"></span>
            <i data-lucide="save" class="h-4 w-4" id="settings-submit-icon"></i>
            <span id="settings-submit-label">Simpan Pengaturan</span>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
