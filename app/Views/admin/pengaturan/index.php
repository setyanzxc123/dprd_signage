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

    <section class="bg-white border border-slate-200 rounded-xl shadow-sm dark:bg-slate-900 dark:border-slate-800 min-w-0 max-w-full">
        <div class="p-4 sm:p-5 min-w-0 space-y-5">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <i data-lucide="tv" class="size-5 text-emerald-600 dark:text-emerald-400"></i>
                Pengaturan Signage
            </h2>

            <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-950/50 min-w-0">
                    <span class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2.5">Tema Layar</span>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-x-3 py-2 px-3 w-full bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/80 transition">
                            <input type="radio" name="tema_signage" value="dark" class="shrink-0 border-slate-300 rounded-full text-emerald-600 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-emerald-500 dark:checked:border-emerald-500"
                                <?= $settings['tema_signage'] === 'dark' ? 'checked' : '' ?> />
                            <span>Dark</span>
                        </label>
                        <label class="flex items-center gap-x-3 py-2 px-3 w-full bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/80 transition">
                            <input type="radio" name="tema_signage" value="light" class="shrink-0 border-slate-300 rounded-full text-emerald-600 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-emerald-500 dark:checked:border-emerald-500"
                                <?= $settings['tema_signage'] === 'light' ? 'checked' : '' ?> />
                            <span>Light</span>
                        </label>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-950/50 min-w-0">
                    <span class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2.5">Media Tampilan</span>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-x-3 py-2 px-3 w-full bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/80 transition">
                            <input type="radio" name="media_mode" value="video" class="shrink-0 border-slate-300 rounded-full text-emerald-600 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-emerald-500 dark:checked:border-emerald-500"
                                <?= $settings['media_mode'] === 'video' ? 'checked' : '' ?> />
                            <span>Video</span>
                        </label>
                        <label class="flex items-center gap-x-3 py-2 px-3 w-full bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/80 transition">
                            <input type="radio" name="media_mode" value="image" class="shrink-0 border-slate-300 rounded-full text-emerald-600 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-emerald-500 dark:checked:border-emerald-500"
                                <?= $settings['media_mode'] === 'image' ? 'checked' : '' ?> />
                            <span>Gambar</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="grid min-w-0 grid-cols-12 gap-4 border-t border-slate-200 dark:border-slate-800 pt-4">
                <div class="col-span-12 min-w-0 lg:col-span-8">
                    <label for="media_file" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Upload Media</label>
                    <input type="file" class="block w-full border border-slate-200 shadow-xs rounded-xl text-sm focus:z-10 focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-800 dark:text-slate-300 file:bg-slate-100 file:border-0 file:me-4 file:py-2.5 file:px-4 dark:file:bg-slate-800 dark:file:text-slate-300 cursor-pointer" id="media_file" name="media_file"
                        accept="video/mp4,video/webm,image/jpeg,image/png,image/webp" />
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">MP4, WebM, JPG, PNG, atau WebP. Maksimal 200 MB. File dikirim bertahap agar lebih stabil.</p>
                </div>

                <div class="col-span-12 min-w-0 lg:col-span-4">
                    <span class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">File Aktif</span>
                    <?php if (! empty($settings['media_file'])): ?>
                        <div class="inline-flex items-center gap-2 p-2.5 w-full bg-sky-50 border border-sky-200 text-sky-800 rounded-xl text-xs font-medium dark:bg-sky-950/40 dark:border-sky-800 dark:text-sky-300 overflow-hidden" role="status"
                            title="<?= esc(basename($settings['media_file'])) ?>">
                            <i data-lucide="file-check-2" class="size-4 shrink-0 text-sky-600 dark:text-sky-400"></i>
                            <span class="min-w-0 truncate"><?= esc(basename($settings['media_file'])) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="inline-flex items-center gap-2 p-2.5 w-full bg-slate-50 border border-slate-200 text-slate-500 rounded-xl text-xs font-medium dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400" role="status">
                            <i data-lucide="file-x-2" class="size-4 shrink-0"></i>
                            <span>Belum ada file</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-950/50 min-w-0">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Running Text</span>
                    <label for="running_text_aktif" class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="running_text_aktif" name="running_text_aktif" value="1" <?= $settings['running_text_aktif'] ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:size-5 after:transition-all peer-checked:bg-emerald-600 dark:peer-checked:bg-emerald-500"></div>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 ms-2.5 select-none">Aktif</span>
                    </label>
                </div>

                <textarea class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:placeholder:text-slate-500" id="running_text" name="running_text" rows="2"
                    placeholder="Contoh: Selamat datang di Gedung DPRD Provinsi Sulawesi Tengah."><?= esc($settings['running_text']) ?></textarea>

                <div class="mt-3 min-w-0 max-w-full overflow-hidden rounded-xl bg-slate-950 p-3 text-slate-100 border border-slate-800">
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

    <section class="bg-white border border-slate-200 rounded-xl shadow-sm dark:bg-slate-900 dark:border-slate-800 min-w-0 max-w-full" id="wa-integration-card"
        data-connected="<?= ! empty($whatsapp['connected']) ? '1' : '0' ?>">
        <div class="p-4 sm:p-5 min-w-0 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="message-square" class="size-5 text-emerald-600 dark:text-emerald-400"></i>
                    Integrasi WhatsApp OTP Gateway
                </h2>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:border dark:border-emerald-800/60 dark:text-emerald-300" id="wa-provider-badge">
                        Provider: <?= esc(strtoupper($otpConfig->provider ?? 'HYBRID')) ?>
                    </span>
                    <button type="button" class="inline-flex items-center gap-x-1.5 py-1.5 px-2.5 text-xs font-medium rounded-lg border border-slate-200 bg-white text-slate-800 shadow-xs hover:bg-slate-50 focus:outline-hidden dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700 transition" id="btn-refresh-wa-status" title="Periksa status koneksi WhatsApp">
                        <i data-lucide="refresh-cw" class="size-3.5" id="icon-refresh-wa"></i>
                        <span>Cek Status</span>
                    </button>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-3 dark:border-slate-800 dark:bg-slate-950/50" id="wa-primary-status-card">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status Koneksi Gateway</span>
                </div>
                <div id="wa-primary-status">
                    <?php if (! empty($whatsapp['connected'])): ?>
                        <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-semibold text-sm">
                            <i data-lucide="check-circle-2" class="size-5 shrink-0"></i>
                            <span>WhatsApp Gateway Terhubung</span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                            No. Pengirim: <strong>+<?= esc($whatsapp['phone'] ?? '-') ?></strong>
                            <?php if (! empty($whatsapp['name'])): ?>
                                (<?= esc($whatsapp['name']) ?>)
                            <?php endif; ?>
                        </p>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <button type="button" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 font-semibold text-xs transition dark:border-rose-900/60 dark:text-rose-400 dark:hover:bg-rose-950/30" id="btn-wa-logout" data-hs-overlay="#modal_wa_logout" aria-haspopup="dialog" aria-expanded="false" aria-controls="modal_wa_logout">
                                <i data-lucide="log-out" class="size-4"></i>
                                <span>Putuskan Perangkat</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-2 text-rose-600 dark:text-rose-400 font-semibold text-sm">
                            <i data-lucide="alert-triangle" class="size-5 shrink-0"></i>
                            <span>WhatsApp Belum Terhubung</span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1" id="wa-error-text">
                            <?= esc($whatsapp['error'] ?? 'Gateway belum terhubung. Silakan scan QR Code untuk menghubungkan nomor pengirim.') ?>
                        </p>
                        <div class="pt-2">
                            <button type="button" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-lg bg-amber-500 text-white hover:bg-amber-600 font-semibold text-xs shadow-xs transition" id="wa-qr-btn"
                                data-hs-overlay="#modal_wa_pairing" aria-haspopup="dialog" aria-expanded="false" aria-controls="modal_wa_pairing" onclick="window.switchWaTab('qr');">
                                <i data-lucide="qr-code" class="size-4"></i>
                                <span>Buka Scan QR / Pairing Code</span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="mt-6 flex flex-col gap-3 sm:items-end">
        <div class="w-full max-w-xl p-3 bg-sky-50 border border-sky-200 text-sky-800 rounded-xl dark:bg-sky-950/40 dark:border-sky-800 dark:text-sky-300" id="settings-upload-progress" hidden aria-live="polite">
            <div class="w-full">
                <div class="mb-2 flex items-center justify-between text-xs font-bold text-sky-900 dark:text-sky-300">
                    <div class="flex items-center gap-2">
                        <span class="animate-spin inline-block size-3.5 border-2 border-current border-t-transparent text-sky-600 rounded-full dark:text-sky-400" aria-hidden="true"></span>
                        <span id="settings-upload-status">Menyiapkan upload...</span>
                    </div>
                    <span id="settings-upload-percent">0%</span>
                </div>
                <progress class="w-full h-2 rounded-full overflow-hidden [&::-webkit-progress-bar]:bg-slate-200 [&::-webkit-progress-value]:bg-emerald-600 [&::-moz-progress-bar]:bg-emerald-600 dark:[&::-webkit-progress-bar]:bg-slate-700 dark:[&::-webkit-progress-value]:bg-emerald-500" id="settings-upload-bar" value="0" max="100"></progress>
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
<div id="modal_wa_pairing" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="modal_wa_pairing_label">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-slate-200 shadow-xl rounded-2xl pointer-events-auto dark:bg-slate-900 dark:border-slate-800">
            <div class="flex justify-between items-center py-3.5 px-4 sm:px-6 border-b border-slate-200 dark:border-slate-800">
                <h3 id="modal_wa_pairing_label" class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="smartphone" class="size-5 text-emerald-600 dark:text-emerald-400"></i>
                    Tautkan WhatsApp Gateway
                </h3>
                <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-slate-100 text-slate-800 hover:bg-slate-200 focus:outline-hidden focus:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-400" data-hs-overlay="#modal_wa_pairing">
                    <span class="sr-only">Tutup</span>
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-4">
                <nav class="grid grid-cols-2 p-1 bg-slate-100 rounded-xl dark:bg-slate-800/80" aria-label="Tabs" role="tablist">
                    <button type="button" class="hs-tab-active:bg-white hs-tab-active:text-slate-800 hs-tab-active:shadow-xs dark:hs-tab-active:bg-slate-900 dark:hs-tab-active:text-slate-200 py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition active" id="tab-btn-qr" aria-selected="true" data-hs-tab="#panel-wa-qr" aria-controls="panel-wa-qr" role="tab">
                        <i data-lucide="qr-code" class="size-3.5"></i> Scan QR Code
                    </button>
                    <button type="button" class="hs-tab-active:bg-white hs-tab-active:text-slate-800 hs-tab-active:shadow-xs dark:hs-tab-active:bg-slate-900 dark:hs-tab-active:text-slate-200 py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition" id="tab-btn-pair" aria-selected="false" data-hs-tab="#panel-wa-pair" aria-controls="panel-wa-pair" role="tab">
                        <i data-lucide="key-round" class="size-3.5"></i> Pairing Code (8 Digit)
                    </button>
                </nav>

                <div id="panel-wa-qr" class="space-y-3" role="tabpanel" aria-labelledby="tab-btn-qr">
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Buka WhatsApp di HP, masuk ke <strong>Perangkat Tertaut</strong>, pilih <strong>Tautkan Perangkat</strong>, lalu scan kode berikut:
                    </p>

                    <div class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-200 rounded-xl min-h-56 dark:bg-slate-950/50 dark:border-slate-800">
                        <div id="wa-qr-loading" class="flex flex-col items-center gap-2">
                            <span class="animate-spin inline-block size-6 border-2 border-current border-t-transparent text-emerald-600 rounded-full dark:text-emerald-400"></span>
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Mengambil QR Code dari Gateway...</span>
                        </div>
                        <img id="wa-qr-image" src="" alt="WhatsApp QR Code" class="max-w-48 max-h-48 rounded-lg shadow-xs bg-white p-2 border border-slate-200 dark:border-slate-700" hidden />
                        <div id="wa-qr-error" class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs mt-2 w-full dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-300" hidden></div>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5" id="wa-qr-timer">
                            <i data-lucide="clock" class="size-3.5"></i> Auto-refresh tiap 15 detik
                        </span>
                        <button type="button" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-medium rounded-lg border border-slate-200 bg-white text-slate-800 hover:bg-slate-50 shadow-xs dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700" id="btn-reload-qr">
                            <i data-lucide="refresh-cw" class="size-3.5"></i> Muat Ulang QR
                        </button>
                    </div>
                </div>

                <div id="panel-wa-pair" class="space-y-3 hidden" role="tabpanel" aria-labelledby="tab-btn-pair">
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Masukkan nomor WhatsApp resmi DPRD (contoh: <code>08123456789</code> atau <code>628123456789</code>) untuk menerima 8 digit kode pairing:
                    </p>

                    <div class="space-y-2">
                        <input type="tel" id="input-pair-phone" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm font-mono placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:placeholder:text-slate-500"
                            placeholder="Contoh: 081234567890" aria-label="Nomor WhatsApp untuk pairing code" />
                        <button type="button" class="py-2.5 px-4 w-full inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 disabled:pointer-events-none shadow-xs" id="btn-request-pair-code">
                            <span class="animate-spin inline-block size-4 border-2 border-current border-t-transparent text-white rounded-full" id="spinner-pair-code" hidden></span>
                            <i data-lucide="send" class="size-4" id="icon-pair-send"></i>
                            <span>Dapatkan Pairing Code</span>
                        </button>
                    </div>

                    <div id="box-pair-result" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-center space-y-2 dark:bg-emerald-950/30 dark:border-emerald-800" hidden>
                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Kode Pairing Anda</div>
                        <div class="text-2xl font-black font-mono tracking-widest text-emerald-800 dark:text-emerald-300 select-all" id="text-pairing-code">-</div>
                        <p class="text-xs text-slate-600 dark:text-slate-300">
                            Buka WhatsApp di HP, masuk ke <strong>Perangkat Tertaut</strong>, pilih <strong>Tautkan dengan nomor telepon</strong>, lalu masukkan kode di atas.
                        </p>
                    </div>

                    <div id="box-pair-error" class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-lg text-xs dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300" hidden></div>
                </div>
            </div>

            <div class="flex justify-end items-center py-3 px-4 sm:px-6 border-t border-slate-200 dark:border-slate-800">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-slate-200 bg-white text-slate-800 shadow-xs hover:bg-slate-50 focus:outline-hidden dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700" data-hs-overlay="#modal_wa_pairing">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Pemutusan Perangkat WhatsApp -->
<div id="modal_wa_logout" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="modal_wa_logout_label">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-md sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-slate-200 shadow-xl rounded-2xl pointer-events-auto dark:bg-slate-900 dark:border-slate-800">
            <div class="p-4 sm:p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 shrink-0">
                        <i data-lucide="log-out" class="size-5"></i>
                    </div>
                    <h3 id="modal_wa_logout_label" class="text-base font-bold text-slate-800 dark:text-slate-200">
                        Putuskan Perangkat WhatsApp?
                    </h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Sesi WhatsApp yang aktif akan dihapus dari gateway dan nomor pengirim berhenti menerima OTP sampai pairing dilakukan ulang.
                </p>
                <p class="text-xs font-semibold text-rose-600 dark:text-rose-400" id="wa-logout-error" hidden></p>
                <div class="flex justify-end gap-x-2 pt-2">
                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-slate-200 bg-white text-slate-800 shadow-xs hover:bg-slate-50 focus:outline-hidden dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700" id="btn-wa-logout-cancel" data-hs-overlay="#modal_wa_logout">
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
