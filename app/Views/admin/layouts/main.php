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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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

    <div id="sidebar-overlay" data-turbo-permanent></div>

    <div class="od-shell">

        <?= $this->include('admin/layouts/_sidebar') ?>

        <div id="main-wrapper">

            <?= $this->include('admin/layouts/_topbar') ?>

            <main id="content">

                <?php if ($flashSuccess): ?>
                    <div class="alert alert-success admin-flash-alert shadow-sm mb-4" role="alert" data-admin-alert data-auto-dismiss-ms="3500">
                        <i data-lucide="circle-check"></i>
                        <span><?= esc($flashSuccess) ?></span>
                        <button type="button" class="btn btn-ghost btn-xs btn-circle alert-close-btn ml-auto"
                            aria-label="Tutup notifikasi">✕</button>
                    </div>
                <?php endif; ?>

                <?php if ($flashError): ?>
                    <div class="alert alert-error admin-flash-alert shadow-sm mb-4" role="alert" data-admin-alert data-auto-dismiss-ms="5500">
                        <i data-lucide="triangle-alert"></i>
                        <span><?= esc($flashError) ?></span>
                        <button type="button" class="btn btn-ghost btn-xs btn-circle alert-close-btn ml-auto"
                            aria-label="Tutup notifikasi">✕</button>
                    </div>
                <?php endif; ?>

                <?= $this->renderSection('content') ?>

            </main>

        </div>

    </div>

    <!-- Mobile bottom nav -->
    <nav id="mobile-nav" class="mobile-nav" aria-label="Navigasi mobile" data-turbo-permanent>
        <a href="<?= base_url('admin/dashboard') ?>" data-path="/admin/dashboard">
            <i data-lucide="layout-dashboard"></i>
            <span class="mobile-nav-label">Dashboard</span>
        </a>
        <a href="<?= base_url('admin/jadwal') ?>" data-path="/admin/jadwal">
            <i data-lucide="calendar-days"></i>
            <span class="mobile-nav-label">Jadwal</span>
        </a>
        <button type="button" class="mobile-menu-toggle" aria-label="Buka menu lainnya" aria-controls="sidebar"
            aria-expanded="false" data-mobile-menu-toggle>
            <i data-lucide="menu"></i>
            <span class="mobile-nav-label">Menu</span>
        </button>
    </nav>

    <div id="admin-vue-controller" hidden></div>

    <?= $this->renderSection('scripts') ?>

</body>

</html>
