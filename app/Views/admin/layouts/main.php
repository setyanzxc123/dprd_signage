<?php
$adminCssVersion = is_file(FCPATH . 'assets/css/admin.css') ? filemtime(FCPATH . 'assets/css/admin.css') : time();
$adminJsVersion = is_file(FCPATH . 'assets/js/admin/main.js') ? filemtime(FCPATH . 'assets/js/admin/main.js') : time();
$adminPagesJsVersion = is_file(FCPATH . 'assets/js/admin/pages.js') ? filemtime(FCPATH . 'assets/js/admin/pages.js') : time();
$adminThemeJsVersion = is_file(FCPATH . 'assets/js/admin/theme-init.js') ? filemtime(FCPATH . 'assets/js/admin/theme-init.js') : time();
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$turboVersion = is_file(FCPATH . 'assets/vendor/turbo/turbo.es2017-umd.js') ? filemtime(FCPATH . 'assets/vendor/turbo/turbo.es2017-umd.js') : time();
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
    <script src="<?= base_url('assets/js/admin/theme-init.js?v=' . $adminThemeJsVersion) ?>"
        data-turbo-track="reload"></script>

    <title>
        <?= esc($pageTitle ?? 'Admin') ?> - Panel Admin Signage DPRD Sulawesi Tengah
    </title>
    <meta name="description"
        content="Panel manajemen sistem informasi jadwal rapat dan digital signage DPRD Provinsi Sulawesi Tengah." />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="turbo-cache-control" content="no-preview" />

    <link rel="icon" type="image/jpeg" href="<?= base_url('assets/images/logo_dprd.jpg') ?>" />
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-400-normal.woff2') ?>" as="font"
        type="font/woff2" crossorigin />
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-700-normal.woff2') ?>" as="font"
        type="font/woff2" crossorigin />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet"
        data-turbo-track="reload" />
    <link href="<?= base_url('assets/vendor/datatables/dataTables.dataTables.min.css?v=' . $dataTablesCssVersion) ?>"
        rel="stylesheet" data-turbo-track="reload" />
    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet"
        data-turbo-track="reload" />
    <script src="<?= base_url('assets/vendor/turbo/turbo.es2017-umd.js?v=' . $turboVersion) ?>" defer
        data-turbo-track="reload"></script>
    <script src="<?= base_url('assets/vendor/lucide/lucide.min.js?v=' . $lucideVersion) ?>" defer
        data-turbo-track="reload"></script>
    <script src="<?= base_url('assets/vendor/jquery/jquery.min.js?v=' . $jqueryVersion) ?>" defer
        data-turbo-track="reload"></script>
    <script src="<?= base_url('assets/vendor/datatables/dataTables.min.js?v=' . $dataTablesJsVersion) ?>" defer
        data-turbo-track="reload"></script>
    <script src="<?= base_url('assets/js/admin/main.js?v=' . $adminJsVersion) ?>" defer
        data-turbo-track="reload"></script>
    <script src="<?= base_url('assets/js/admin/pages.js?v=' . $adminPagesJsVersion) ?>" defer
        data-turbo-track="reload"></script>
    <?= $this->renderSection('styles') ?>
</head>

<body>

    <div class="drawer lg:drawer-open lg:h-dvh lg:overflow-hidden" id="admin-shell">
        <input id="admin-drawer" type="checkbox" class="drawer-toggle" aria-label="Buka navigasi utama" />

        <div class="drawer-content flex min-h-screen min-w-0 flex-col bg-base-200 lg:h-dvh lg:min-h-0 lg:overflow-y-auto lg:overscroll-contain">
            <?= $this->include('admin/layouts/_topbar') ?>

            <main id="content" class="min-w-0 flex-1 px-4 pb-24 pt-5 sm:px-6 lg:px-8 lg:pb-8">

                <?php if ($flashSuccess): ?>
                    <div class="alert alert-success admin-flash-alert shadow-sm mb-4" role="alert" data-admin-alert data-auto-dismiss-ms="3500">
                        <i data-lucide="circle-check"></i>
                        <span><?= esc($flashSuccess) ?></span>
                        <button type="button" class="btn btn-ghost btn-xs btn-circle alert-close-btn ml-auto"
                            aria-label="Tutup notifikasi"><i data-lucide="x"></i></button>
                    </div>
                <?php endif; ?>

                <?php if ($flashError): ?>
                    <div class="alert alert-error admin-flash-alert shadow-sm mb-4" role="alert" data-admin-alert data-auto-dismiss-ms="5500">
                        <i data-lucide="triangle-alert"></i>
                        <span><?= esc($flashError) ?></span>
                        <button type="button" class="btn btn-ghost btn-xs btn-circle alert-close-btn ml-auto"
                            aria-label="Tutup notifikasi"><i data-lucide="x"></i></button>
                    </div>
                <?php endif; ?>

                <?= $this->renderSection('content') ?>

            </main>
        </div>

        <div class="drawer-side z-50 lg:overflow-visible">
            <label for="admin-drawer" aria-label="Tutup navigasi utama" class="drawer-overlay"></label>
            <?= $this->include('admin/layouts/_sidebar') ?>
        </div>
    </div>

    <nav id="mobile-nav" class="dock dock-sm border-t border-base-300 bg-base-100 lg:hidden" aria-label="Navigasi mobile">
        <a href="<?= base_url('admin/dashboard') ?>" data-path="/admin/dashboard">
            <i data-lucide="layout-dashboard"></i>
            <span class="dock-label">Dashboard</span>
        </a>
        <a href="<?= base_url('admin/jadwal-banmus') ?>" data-path="/admin/jadwal-banmus">
            <i data-lucide="calendar-range"></i>
            <span class="dock-label">Banmus</span>
        </a>
        <a href="<?= base_url('admin/jadwal-umum') ?>" data-path="/admin/jadwal-umum">
            <i data-lucide="calendar-days"></i>
            <span class="dock-label">Umum</span>
        </a>
        <label for="admin-drawer" role="button" tabindex="0" aria-label="Buka menu lainnya" data-mobile-menu-toggle>
            <i data-lucide="menu"></i>
            <span class="dock-label">Menu</span>
        </label>
    </nav>

    <?= $this->renderSection('scripts') ?>

</body>

</html>
