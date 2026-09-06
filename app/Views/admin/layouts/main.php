<?php
$adminCssVersion = is_file(FCPATH . 'assets/css/admin.css') ? filemtime(FCPATH . 'assets/css/admin.css') : time();
$adminJsVersion = is_file(FCPATH . 'assets/js/admin/main.js') ? filemtime(FCPATH . 'assets/js/admin/main.js') : time();
$adminPagesJsVersion = is_file(FCPATH . 'assets/js/admin/pages.js') ? filemtime(FCPATH . 'assets/js/admin/pages.js') : time();
$adminThemeJsVersion = is_file(FCPATH . 'assets/js/admin/theme-init.js') ? filemtime(FCPATH . 'assets/js/admin/theme-init.js') : time();
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$prelineVersion = is_file(FCPATH . 'assets/vendor/preline/preline.js') ? filemtime(FCPATH . 'assets/vendor/preline/preline.js') : time();
$logoVersion = is_file(FCPATH . 'assets/images/logo_dprd.jpg') ? filemtime(FCPATH . 'assets/images/logo_dprd.jpg') : time();
$lucideVersion = is_file(FCPATH . 'assets/vendor/lucide/lucide.min.js') ? filemtime(FCPATH . 'assets/vendor/lucide/lucide.min.js') : time();
$jqueryVersion = is_file(FCPATH . 'assets/vendor/jquery/jquery.min.js') ? filemtime(FCPATH . 'assets/vendor/jquery/jquery.min.js') : time();
$dataTablesJsVersion = is_file(FCPATH . 'assets/vendor/datatables/dataTables.min.js') ? filemtime(FCPATH . 'assets/vendor/datatables/dataTables.min.js') : time();
$dataTablesCssVersion = is_file(FCPATH . 'assets/vendor/datatables/dataTables.dataTables.min.css') ? filemtime(FCPATH . 'assets/vendor/datatables/dataTables.dataTables.min.css') : time();
$flashSuccess = session()->getFlashdata('success');
$flashError = session()->getFlashdata('error');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <script src="<?= base_url('assets/js/admin/theme-init.js?v=' . $adminThemeJsVersion) ?>"></script>

    <title>
        <?= esc($pageTitle ?? 'Admin') ?> - Panel Admin Signage DPRD Sulawesi Tengah
    </title>
    <meta name="description"
        content="Panel manajemen sistem informasi jadwal rapat dan digital signage DPRD Provinsi Sulawesi Tengah." />
    <meta name="robots" content="noindex, nofollow" />

    <link rel="icon" type="image/jpeg" href="<?= base_url('assets/images/logo_dprd.jpg?v=' . $logoVersion) ?>" />
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-400-normal.woff2') ?>" as="font"
        type="font/woff2" crossorigin />
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-700-normal.woff2') ?>" as="font"
        type="font/woff2" crossorigin />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/vendor/datatables/dataTables.dataTables.min.css?v=' . $dataTablesCssVersion) ?>"
        rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet" />
    <script src="<?= base_url('assets/vendor/preline/preline.js?v=' . $prelineVersion) ?>" defer></script>
    <script src="<?= base_url('assets/vendor/lucide/lucide.min.js?v=' . $lucideVersion) ?>" defer></script>
    <script src="<?= base_url('assets/vendor/jquery/jquery.min.js?v=' . $jqueryVersion) ?>" defer></script>
    <script src="<?= base_url('assets/vendor/datatables/dataTables.min.js?v=' . $dataTablesJsVersion) ?>" defer></script>
    <script src="<?= base_url('assets/js/admin/main.js?v=' . $adminJsVersion) ?>" defer></script>
    <script src="<?= base_url('assets/js/admin/pages.js?v=' . $adminPagesJsVersion) ?>" defer></script>
    <?= $this->renderSection('styles') ?>
</head>

<body class="min-h-screen overflow-x-hidden bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased selection:bg-emerald-500 selection:text-white">

    <?= $this->include('admin/layouts/_sidebar') ?>

    <?= $this->include('admin/layouts/_topbar') ?>

    <div class="w-full lg:ps-64 min-h-screen bg-slate-50 dark:bg-slate-950">
        <main id="content" class="p-4 sm:p-6 lg:p-8 space-y-6">

            <?php if ($flashSuccess): ?>
                <div class="flex items-center gap-3 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 shadow-xs" role="alert" data-admin-alert data-auto-dismiss-ms="3500">
                    <i data-lucide="circle-check" class="size-5 shrink-0"></i>
                    <span class="text-xs font-semibold sm:text-sm"><?= esc($flashSuccess) ?></span>
                    <button type="button" class="ml-auto size-7 inline-flex items-center justify-center rounded-lg text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 alert-close-btn transition" aria-label="Tutup notifikasi">
                        <i data-lucide="x" class="size-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="flex items-center gap-3 p-4 rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 shadow-xs" role="alert" data-admin-alert data-auto-dismiss-ms="5500">
                    <i data-lucide="triangle-alert" class="size-5 shrink-0"></i>
                    <span class="text-xs font-semibold sm:text-sm"><?= esc($flashError) ?></span>
                    <button type="button" class="ml-auto size-7 inline-flex items-center justify-center rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/50 alert-close-btn transition" aria-label="Tutup notifikasi">
                        <i data-lucide="x" class="size-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?= $this->renderSection('content') ?>

        </main>
    </div>

    <!-- Preline Confirmation Modal Global -->
    <div id="admin-confirm-modal" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="admin-confirm-modal-label">
        <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
            <div class="w-full flex flex-col bg-white border border-slate-200 shadow-xl rounded-2xl pointer-events-auto dark:bg-slate-900 dark:border-slate-800">
                <div class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                            <i data-lucide="triangle-alert" class="size-6"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 id="admin-confirm-modal-label" class="text-base font-bold text-slate-900 dark:text-white">
                                Konfirmasi Tindakan
                            </h3>
                            <p id="admin-confirm-modal-message" class="mt-1 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                                Apakah Anda yakin ingin melanjutkan tindakan ini?
                            </p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end items-center gap-x-2">
                        <button type="button" class="py-2 px-3.5 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition cursor-pointer" data-hs-overlay="#admin-confirm-modal">
                            Batal
                        </button>
                        <button type="button" id="admin-confirm-modal-submit" class="py-2 px-3.5 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent bg-rose-600 text-white hover:bg-rose-700 focus:outline-hidden focus:bg-rose-700 shadow-xs transition cursor-pointer">
                            Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($emergencyOtp = (session()->getFlashdata('emergency_otp') ?? ($emergency_otp ?? null))): ?>
    <div id="admin-emergency-otp-modal" class="hs-overlay size-full fixed top-0 start-0 z-[85] overflow-x-hidden overflow-y-auto" role="dialog" tabindex="-1" aria-labelledby="admin-emergency-otp-label">
        <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-100 ease-out transition-all sm:max-w-md sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
            <div class="w-full flex flex-col bg-white border border-amber-200 shadow-2xl rounded-2xl pointer-events-auto dark:bg-slate-900 dark:border-amber-900/50">
                <div class="p-6 text-center">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                        <i data-lucide="key-round" class="size-6"></i>
                    </div>
                    <h3 id="admin-emergency-otp-label" class="mt-3 text-base font-bold text-slate-900 dark:text-white">
                        OTP Darurat Berhasil Dibuat
                    </h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Untuk: <strong class="text-slate-700 dark:text-slate-200"><?= esc($emergencyOtp['member'] ?? 'Anggota DPRD') ?></strong>
                    </p>
                    <div class="my-4 p-4 rounded-xl border border-dashed border-amber-300 dark:border-amber-700/60 bg-amber-50/60 dark:bg-amber-950/20">
                        <div id="emergency-otp-code" class="font-mono text-3xl font-black text-amber-600 dark:text-amber-400 select-all tracking-widest"><?= esc($emergencyOtp['code'] ?? '') ?></div>
                        <p class="mt-1.5 text-[11px] font-medium text-amber-700 dark:text-amber-300">
                            Berlaku sampai <?= esc($emergencyOtp['expires_at'] ?? '15 menit') ?> &bull; Hanya tampil sekali
                        </p>
                    </div>
                    <div class="flex items-center justify-center gap-2">
                        <button type="button" class="py-2 px-4 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer" data-copy-otp data-code="<?= esc($emergencyOtp['code'] ?? '') ?>">
                            <i data-lucide="copy" class="size-4"></i>
                            <span id="copy-otp-label">Salin Kode</span>
                        </button>
                        <button type="button" class="py-2 px-4 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition cursor-pointer" data-hs-overlay="#admin-emergency-otp-modal">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?= $this->renderSection('scripts') ?>

</body>

</html>
