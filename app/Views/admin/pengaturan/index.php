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

    <section class="bg-white border border-gray-200 rounded-xl shadow-xs dark:bg-neutral-800 dark:border-neutral-700 min-w-0 max-w-full">
        <div class="p-4 sm:p-5 min-w-0 space-y-5">
            <h2 class="text-base font-bold text-gray-800 dark:text-neutral-200 flex items-center gap-2">
                <i data-lucide="tv" class="size-5 text-emerald-600 dark:text-emerald-400"></i>
                Pengaturan Signage
            </h2>

            <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-neutral-700 dark:bg-neutral-900/50 min-w-0">
                    <span class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-neutral-300 mb-2.5">Tema Layar</span>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-x-3 py-2 px-3 w-full bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-800 focus:ring-emerald-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800 transition">
                            <input type="radio" name="tema_signage" value="dark" class="shrink-0 border-gray-300 rounded-full text-emerald-600 focus:ring-emerald-500 dark:bg-neutral-800 dark:border-neutral-600 dark:checked:bg-emerald-500 dark:checked:border-emerald-500"
                                <?= $settings['tema_signage'] === 'dark' ? 'checked' : '' ?> />
                            <span>Dark</span>
                        </label>
                        <label class="flex items-center gap-x-3 py-2 px-3 w-full bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-800 focus:ring-emerald-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800 transition">
                            <input type="radio" name="tema_signage" value="light" class="shrink-0 border-gray-300 rounded-full text-emerald-600 focus:ring-emerald-500 dark:bg-neutral-800 dark:border-neutral-600 dark:checked:bg-emerald-500 dark:checked:border-emerald-500"
                                <?= $settings['tema_signage'] === 'light' ? 'checked' : '' ?> />
                            <span>Light</span>
                        </label>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-neutral-700 dark:bg-neutral-900/50 min-w-0">
                    <span class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-neutral-300 mb-2.5">Media Tampilan</span>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-x-3 py-2 px-3 w-full bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-800 focus:ring-emerald-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800 transition">
                            <input type="radio" name="media_mode" value="video" class="shrink-0 border-gray-300 rounded-full text-emerald-600 focus:ring-emerald-500 dark:bg-neutral-800 dark:border-neutral-600 dark:checked:bg-emerald-500 dark:checked:border-emerald-500"
                                <?= $settings['media_mode'] === 'video' ? 'checked' : '' ?> />
                            <span>Video</span>
                        </label>
                        <label class="flex items-center gap-x-3 py-2 px-3 w-full bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-800 focus:ring-emerald-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800 transition">
                            <input type="radio" name="media_mode" value="image" class="shrink-0 border-gray-300 rounded-full text-emerald-600 focus:ring-emerald-500 dark:bg-neutral-800 dark:border-neutral-600 dark:checked:bg-emerald-500 dark:checked:border-emerald-500"
                                <?= $settings['media_mode'] === 'image' ? 'checked' : '' ?> />
                            <span>Gambar</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="grid min-w-0 grid-cols-12 gap-4 border-t border-gray-200 dark:border-neutral-700 pt-4">
                <div class="col-span-12 min-w-0 lg:col-span-8">
                    <label for="media_file" class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-neutral-300 mb-1.5">Upload Media</label>
                    <input type="file" class="block w-full border border-gray-200 shadow-xs rounded-xl text-sm focus:z-10 focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 file:bg-gray-100 file:border-0 file:me-4 file:py-2.5 file:px-4 dark:file:bg-neutral-700 dark:file:text-neutral-300 cursor-pointer" id="media_file" name="media_file"
                        accept="video/mp4,video/webm,image/jpeg,image/png,image/webp" />
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-neutral-400">MP4, WebM, JPG, PNG, atau WebP. Maksimal 200 MB. File dikirim bertahap agar lebih stabil.</p>
                </div>

                <div class="col-span-12 min-w-0 lg:col-span-4">
                    <span class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-neutral-300 mb-1.5">File Aktif</span>
                    <?php if (! empty($settings['media_file'])): ?>
                        <div class="inline-flex items-center gap-2 p-2.5 w-full bg-sky-50 border border-sky-200 text-sky-800 rounded-xl text-xs font-medium dark:bg-sky-950/40 dark:border-sky-800 dark:text-sky-300 overflow-hidden" role="status"
                            title="<?= esc(basename($settings['media_file'])) ?>">
                            <i data-lucide="file-check-2" class="size-4 shrink-0 text-sky-600 dark:text-sky-400"></i>
                            <span class="min-w-0 truncate"><?= esc(basename($settings['media_file'])) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="inline-flex items-center gap-2 p-2.5 w-full bg-gray-50 border border-gray-200 text-gray-500 rounded-xl text-xs font-medium dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400" role="status">
                            <i data-lucide="file-x-2" class="size-4 shrink-0"></i>
                            <span>Belum ada file</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-neutral-700 dark:bg-neutral-900/50 min-w-0">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-neutral-300">Running Text</span>
                    <div class="flex items-center">
                        <input type="checkbox" id="running_text_aktif" name="running_text_aktif" value="1" <?= $settings['running_text_aktif'] ? 'checked' : '' ?>
                            class="relative w-11 h-6 p-px bg-gray-200 border-transparent text-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:ring-emerald-600 disabled:opacity-50 disabled:pointer-events-none checked:bg-none checked:text-emerald-600 checked:border-emerald-600 focus:checked:border-emerald-600 dark:bg-neutral-700 dark:border-neutral-700 dark:checked:bg-emerald-500 dark:checked:border-emerald-500 before:inline-block before:size-5 before:bg-white checked:before:bg-white before:translate-x-0 checked:before:translate-x-full before:rounded-full before:shadow-xs before:transform before:ring-0 before:transition before:ease-in-out before:duration-200 dark:before:bg-neutral-400 dark:checked:before:bg-neutral-900">
                        <label for="running_text_aktif" class="text-xs font-semibold text-gray-700 dark:text-neutral-300 ms-2.5 cursor-pointer">Aktif</label>
                    </div>
                </div>

                <textarea class="py-2.5 px-3.5 block w-full border border-gray-200 rounded-xl text-sm placeholder:text-gray-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200" id="running_text" name="running_text" rows="2"
                    placeholder="Contoh: Selamat datang di Gedung DPRD Provinsi Sulawesi Tengah."><?= esc($settings['running_text']) ?></textarea>

                <div class="mt-3 min-w-0 max-w-full overflow-hidden rounded-xl bg-slate-900 dark:bg-black p-3 text-slate-100 border border-slate-800">
                    <div class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-400">
                        <i data-lucide="eye" class="size-3.5"></i>
                        Pratinjau
                    </div>
                    <div class="settings-running-track" id="preview-track">
                        <span id="preview-text"><?= esc($settings['running_text']) ?: 'Teks berjalan akan tampil di sini...' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white border border-gray-200 rounded-xl shadow-xs dark:bg-neutral-800 dark:border-neutral-700 min-w-0 max-w-full" id="wa-integration-card"
        data-connected="<?= ! empty($whatsapp['connected']) ? '1' : '0' ?>">
        <div class="p-4 sm:p-5 min-w-0 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-bold text-gray-800 dark:text-neutral-200 flex items-center gap-2">
                    <i data-lucide="message-square" class="size-5 text-emerald-600 dark:text-emerald-400"></i>
                    Integrasi WhatsApp OTP Gateway
                </h2>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400" id="wa-provider-badge">
                        Provider: <?= esc(strtoupper($otpConfig->provider ?? 'HYBRID')) ?>
                    </span>
                    <button type="button" class="inline-flex items-center gap-x-1.5 py-1.5 px-2.5 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-xs hover:bg-gray-50 focus:outline-hidden dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700 transition" id="btn-refresh-wa-status" title="Periksa status koneksi WhatsApp">
                        <i data-lucide="refresh-cw" class="size-3.5" id="icon-refresh-wa"></i>
                        <span>Cek Status</span>
                    </button>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 space-y-3 dark:border-neutral-700 dark:bg-neutral-900/50" id="wa-primary-status-card">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-neutral-400">Status Koneksi Gateway</span>
                </div>
                <div id="wa-primary-status">
                    <?php if (! empty($whatsapp['connected'])): ?>
                        <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-semibold text-sm">
                            <i data-lucide="check-circle-2" class="size-5 shrink-0"></i>
                            <span>WhatsApp Gateway Terhubung</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-neutral-400 mt-1">
                            No. Pengirim: <strong>+<?= esc($whatsapp['phone'] ?? '-') ?></strong>
                            <?php if (! empty($whatsapp['name'])): ?>
                                (<?= esc($whatsapp['name']) ?>)
                            <?php endif; ?>
                        </p>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <button type="button" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 font-semibold text-xs transition dark:border-rose-900 dark:text-rose-400 dark:hover:bg-rose-900/20" id="btn-wa-logout" data-hs-overlay="#modal_wa_logout">
                                <i data-lucide="log-out" class="size-4"></i>
                                <span>Putuskan Perangkat</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-2 text-rose-600 dark:text-rose-400 font-semibold text-sm">
                            <i data-lucide="alert-triangle" class="size-5 shrink-0"></i>
                            <span>WhatsApp Belum Terhubung</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-neutral-400 mt-1" id="wa-error-text">
                            <?= esc($whatsapp['error'] ?? 'Gateway belum terhubung. Silakan scan QR Code untuk menghubungkan nomor pengirim.') ?>
                        </p>
                        <div class="pt-2">
                            <button type="button" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-lg bg-amber-500 text-white hover:bg-amber-600 font-semibold text-xs shadow-xs transition" id="wa-qr-btn"
                                data-hs-overlay="#modal_wa_pairing" onclick="window.switchWaTab('qr');">
                                <i data-lucide="qr-code" class="size-4"></i>
                                <span>Buka Scan QR / Pairing Code</span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="sticky bottom-0 z-10 -mx-4 -mb-4 sm:-mx-6 sm:-mb-6 p-4 sm:p-6 bg-white/95 dark:bg-neutral-900/95 backdrop-blur-sm border-t border-gray-200 dark:border-neutral-800 flex flex-col gap-3 sm:items-end">
        <div class="w-full max-w-xl p-3 bg-sky-50 border border-sky-200 text-sky-800 rounded-xl dark:bg-sky-950/40 dark:border-sky-800 dark:text-sky-300" id="settings-upload-progress" hidden aria-live="polite">
            <div class="w-full">
                <div class="mb-2 flex items-center justify-between text-xs font-bold text-sky-900 dark:text-sky-300">
                    <div class="flex items-center gap-2">
                        <span class="animate-spin inline-block size-3.5 border-2 border-current border-t-transparent text-sky-600 rounded-full dark:text-sky-400" aria-hidden="true"></span>
                        <span id="settings-upload-status">Menyiapkan upload...</span>
                    </div>
                    <span id="settings-upload-percent">0%</span>
                </div>
                <progress class="w-full h-2 rounded-full overflow-hidden [&::-webkit-progress-bar]:bg-gray-200 [&::-webkit-progress-value]:bg-emerald-600 [&::-moz-progress-bar]:bg-emerald-600 dark:[&::-webkit-progress-bar]:bg-neutral-700 dark:[&::-webkit-progress-value]:bg-emerald-500" id="settings-upload-bar" value="0" max="100"></progress>
                <div class="mt-1 text-right text-xs font-medium text-sky-700 dark:text-sky-400 opacity-80" id="settings-upload-speed" hidden>
                    Mengukur kecepatan...
                </div>
            </div>
        </div>

        <button type="submit" class="py-2.5 px-4 inline-flex items-center justify-center gap-x-2 text-sm font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-hidden focus:bg-emerald-700 disabled:opacity-50 disabled:pointer-events-none w-full sm:w-auto shadow-xs" id="settings-submit-button">
            <span class="animate-spin inline-block size-4 border-2 border-current border-t-transparent text-white rounded-full" id="settings-submit-spinner" hidden aria-hidden="true"></span>
            <i data-lucide="save" class="size-4" id="settings-submit-icon"></i>
            <span id="settings-submit-label">Simpan Pengaturan</span>
        </button>
    </div>
</form>

<!-- Modal Penautan WhatsApp Gateway (QR Code & Pairing Code) -->
<div id="modal_wa_pairing" class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="modal_wa_pairing_label">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-gray-200 shadow-xl rounded-2xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700">
            <div class="flex justify-between items-center py-3.5 px-4 sm:px-6 border-b border-gray-200 dark:border-neutral-700">
                <h3 id="modal_wa_pairing_label" class="font-bold text-gray-800 dark:text-neutral-200 flex items-center gap-2">
                    <i data-lucide="smartphone" class="size-5 text-emerald-600 dark:text-emerald-400"></i>
                    Tautkan WhatsApp Gateway
                </h3>
                <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400" data-hs-overlay="#modal_wa_pairing">
                    <span class="sr-only">Tutup</span>
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-4">
                <div class="grid grid-cols-2 p-1 bg-gray-100 rounded-xl dark:bg-neutral-700/60">
                    <button type="button" class="py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-semibold rounded-lg bg-white text-gray-800 shadow-xs dark:bg-neutral-800 dark:text-neutral-200 transition" id="tab-btn-qr" onclick="switchWaTab('qr')">
                        <i data-lucide="qr-code" class="size-3.5"></i> Scan QR Code
                    </button>
                    <button type="button" class="py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-semibold rounded-lg text-gray-500 hover:text-gray-800 dark:text-neutral-400 dark:hover:text-neutral-200 transition" id="tab-btn-pair" onclick="switchWaTab('pair')">
                        <i data-lucide="key-round" class="size-3.5"></i> Pairing Code (8 Digit)
                    </button>
                </div>

                <div id="panel-wa-qr" class="space-y-3">
                    <p class="text-xs text-gray-600 dark:text-neutral-400">
                        Buka WhatsApp di HP, masuk ke <strong>Perangkat Tertaut</strong>, pilih <strong>Tautkan Perangkat</strong>, lalu scan kode berikut:
                    </p>

                    <div class="flex flex-col items-center justify-center p-6 bg-gray-50 border border-gray-200 rounded-xl min-h-56 dark:bg-neutral-900/50 dark:border-neutral-700">
                        <div id="wa-qr-loading" class="flex flex-col items-center gap-2">
                            <span class="animate-spin inline-block size-6 border-2 border-current border-t-transparent text-emerald-600 rounded-full dark:text-emerald-400"></span>
                            <span class="text-xs font-semibold text-gray-500 dark:text-neutral-400">Mengambil QR Code dari Gateway...</span>
                        </div>
                        <img id="wa-qr-image" src="" alt="WhatsApp QR Code" class="max-w-48 max-h-48 rounded-lg shadow-xs bg-white p-2 border border-gray-200" hidden />
                        <div id="wa-qr-error" class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs mt-2 w-full dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-300" hidden></div>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <span class="text-xs text-gray-500 dark:text-neutral-400 flex items-center gap-1.5" id="wa-qr-timer">
                            <i data-lucide="clock" class="size-3.5"></i> Auto-refresh tiap 15 detik
                        </span>
                        <button type="button" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-800 hover:bg-gray-50 shadow-xs dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700" id="btn-reload-qr">
                            <i data-lucide="refresh-cw" class="size-3.5"></i> Muat Ulang QR
                        </button>
                    </div>
                </div>

                <div id="panel-wa-pair" class="space-y-3" hidden>
                    <p class="text-xs text-gray-600 dark:text-neutral-400">
                        Masukkan nomor WhatsApp resmi DPRD (contoh: <code>08123456789</code> atau <code>628123456789</code>) untuk menerima 8 digit kode pairing:
                    </p>

                    <div class="space-y-2">
                        <input type="tel" id="input-pair-phone" class="py-2.5 px-3.5 block w-full border border-gray-200 rounded-xl text-sm font-mono placeholder:text-gray-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200"
                            placeholder="Contoh: 081234567890" />
                        <button type="button" class="py-2.5 px-4 w-full inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 disabled:pointer-events-none shadow-xs" id="btn-request-pair-code">
                            <span class="animate-spin inline-block size-4 border-2 border-current border-t-transparent text-white rounded-full" id="spinner-pair-code" hidden></span>
                            <i data-lucide="send" class="size-4" id="icon-pair-send"></i>
                            <span>Dapatkan Pairing Code</span>
                        </button>
                    </div>

                    <div id="box-pair-result" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-center space-y-2 dark:bg-emerald-950/30 dark:border-emerald-800" hidden>
                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Kode Pairing Anda</div>
                        <div class="text-2xl font-black font-mono tracking-widest text-emerald-800 dark:text-emerald-300 select-all" id="text-pairing-code">-</div>
                        <p class="text-xs text-gray-600 dark:text-neutral-300">
                            Buka WhatsApp di HP, masuk ke <strong>Perangkat Tertaut</strong>, pilih <strong>Tautkan dengan nomor telepon</strong>, lalu masukkan kode di atas.
                        </p>
                    </div>

                    <div id="box-pair-error" class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-lg text-xs dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300" hidden></div>
                </div>
            </div>

            <div class="flex justify-end items-center py-3 px-4 sm:px-6 border-t border-gray-200 dark:border-neutral-700">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-xs hover:bg-gray-50 focus:outline-hidden dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700" data-hs-overlay="#modal_wa_pairing">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Pemutusan Perangkat WhatsApp -->
<div id="modal_wa_logout" class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="modal_wa_logout_label">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-md sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-gray-200 shadow-xl rounded-2xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700">
            <div class="p-4 sm:p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 shrink-0">
                        <i data-lucide="log-out" class="size-5"></i>
                    </div>
                    <h3 id="modal_wa_logout_label" class="text-base font-bold text-gray-800 dark:text-neutral-200">
                        Putuskan Perangkat WhatsApp?
                    </h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-neutral-400">
                    Sesi WhatsApp yang aktif akan dihapus dari gateway dan nomor pengirim berhenti menerima OTP sampai pairing dilakukan ulang.
                </p>
                <p class="text-xs font-semibold text-rose-600 dark:text-rose-400" id="wa-logout-error" hidden></p>
                <div class="flex justify-end gap-x-2 pt-2">
                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-xs hover:bg-gray-50 focus:outline-hidden dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700" id="btn-wa-logout-cancel" data-hs-overlay="#modal_wa_logout">
                        Batal
                    </button>
                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-50 disabled:pointer-events-none shadow-xs" id="btn-wa-logout-confirm">
                        <span class="animate-spin inline-block size-3.5 border-2 border-current border-t-transparent text-white rounded-full" id="spinner-wa-logout-confirm" hidden></span>
                        Ya, Putuskan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
