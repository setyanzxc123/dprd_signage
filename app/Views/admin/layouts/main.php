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

    <?= $this->renderSection('scripts') ?>

</body>

</html>
