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

    <div class="flex min-h-screen w-full min-w-0" id="admin-shell">
        <input id="admin-drawer" type="checkbox" class="peer sr-only" aria-label="Buka navigasi utama" />

        <label for="admin-drawer" aria-label="Tutup navigasi utama" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs transition-opacity duration-200 hidden peer-checked:block lg:hidden"></label>

        <?= $this->include('admin/layouts/_sidebar') ?>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col bg-slate-50 dark:bg-slate-950 lg:h-dvh lg:overflow-y-auto lg:overscroll-contain">
            <?= $this->include('admin/layouts/_topbar') ?>

            <main id="content" class="min-w-0 flex-1 px-4 pb-24 pt-5 sm:px-6 lg:px-8 lg:pb-8">

                <?php if ($flashSuccess): ?>
                    <div class="flex items-center gap-3 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 shadow-xs mb-4" role="alert" data-admin-alert data-auto-dismiss-ms="3500">
                        <i data-lucide="circle-check" class="size-5 shrink-0"></i>
                        <span class="text-xs font-semibold sm:text-sm"><?= esc($flashSuccess) ?></span>
                        <button type="button" class="ml-auto size-7 inline-flex items-center justify-center rounded-lg text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 alert-close-btn transition" aria-label="Tutup notifikasi">
                            <i data-lucide="x" class="size-4"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($flashError): ?>
                    <div class="flex items-center gap-3 p-4 rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 shadow-xs mb-4" role="alert" data-admin-alert data-auto-dismiss-ms="5500">
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
    </div>

    <nav id="mobile-nav" class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-around border-t border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-slate-900/95 py-2 px-3 backdrop-blur-md shadow-lg lg:hidden" aria-label="Navigasi mobile">
        <a href="<?= base_url('admin/dashboard') ?>" data-path="/admin/dashboard" class="flex flex-col items-center gap-1 text-[10px] font-semibold text-slate-500 dark:text-slate-400 transition hover:text-slate-900 dark:hover:text-white">
            <i data-lucide="layout-dashboard" class="size-5"></i>
            <span class="dock-label">Dashboard</span>
        </a>
        <a href="<?= base_url('admin/jadwal-banmus') ?>" data-path="/admin/jadwal-banmus" class="flex flex-col items-center gap-1 text-[10px] font-semibold text-slate-500 dark:text-slate-400 transition hover:text-slate-900 dark:hover:text-white">
            <i data-lucide="calendar-range" class="size-5"></i>
            <span class="dock-label">Banmus</span>
        </a>
        <a href="<?= base_url('admin/jadwal-umum') ?>" data-path="/admin/jadwal-umum" class="flex flex-col items-center gap-1 text-[10px] font-semibold text-slate-500 dark:text-slate-400 transition hover:text-slate-900 dark:hover:text-white">
            <i data-lucide="calendar-days" class="size-5"></i>
            <span class="dock-label">Umum</span>
        </a>
        <label for="admin-drawer" role="button" tabindex="0" aria-label="Buka menu lainnya" data-mobile-menu-toggle class="flex flex-col items-center gap-1 text-[10px] font-semibold text-slate-500 dark:text-slate-400 transition hover:text-slate-900 dark:hover:text-white cursor-pointer">
            <i data-lucide="menu" class="size-5"></i>
            <span class="dock-label">Menu</span>
        </label>
    </nav>

    <?= $this->renderSection('scripts') ?>

</body>

</html>
