<?php
$adminCssVersion = is_file(FCPATH . 'assets/css/admin.css') ? filemtime(FCPATH . 'assets/css/admin.css') : time();
$adminJsVersion = is_file(FCPATH . 'assets/js/admin/main.js') ? filemtime(FCPATH . 'assets/js/admin/main.js') : time();
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$turboVersion = is_file(FCPATH . 'assets/vendor/turbo/turbo.es2017-umd.js') ? filemtime(FCPATH . 'assets/vendor/turbo/turbo.es2017-umd.js') : time();
$lucideVersion = is_file(FCPATH . 'assets/vendor/lucide/lucide.min.js') ? filemtime(FCPATH . 'assets/vendor/lucide/lucide.min.js') : time();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script>
        (() => {
            const stored = localStorage.getItem('dprd-admin-theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = stored === 'dark' || stored === 'light'
                ? stored
                : (prefersDark ? 'dark' : 'light');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            document.documentElement.setAttribute('data-theme', theme);
            if (localStorage.getItem('dprd-sidebar-collapsed') === 'collapsed') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>

    <title>
        <?= esc($pageTitle ?? 'Admin') ?> - Panel Admin Signage DPRD Sulawesi Tengah
    </title>
    <meta name="description"
        content="Panel manajemen sistem informasi digital signage dan notifikasi WhatsApp DPRD Provinsi Sulawesi Tengah." />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="turbo-cache-control" content="no-preview" />

    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-400-normal.woff2') ?>" as="font"
        type="font/woff2" crossorigin />
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-700-normal.woff2') ?>" as="font"
        type="font/woff2" crossorigin />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet"
        data-turbo-track="reload" />
    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet"
        data-turbo-track="reload" />
    <script src="<?= base_url('assets/vendor/turbo/turbo.es2017-umd.js?v=' . $turboVersion) ?>" defer
        data-turbo-track="reload"></script>
    <script src="<?= base_url('assets/vendor/lucide/lucide.min.js?v=' . $lucideVersion) ?>" defer
        data-turbo-track="reload"></script>
    <script src="<?= base_url('assets/js/admin/main.js?v=' . $adminJsVersion) ?>" defer
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

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success shadow-sm mb-4" role="alert">
                        <i data-lucide="circle-check"></i>
                        <span><?= session()->getFlashdata('success') ?></span>
                        <button type="button" class="btn btn-ghost btn-xs btn-circle alert-close-btn ml-auto"
                            aria-label="Tutup notifikasi">✕</button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-error shadow-sm mb-4" role="alert">
                        <i data-lucide="triangle-alert"></i>
                        <span><?= session()->getFlashdata('error') ?></span>
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
            Home
        </a>
        <a href="<?= base_url('admin/jadwal') ?>" data-path="/admin/jadwal">
            <i data-lucide="calendar-days"></i>
            Jadwal
        </a>
        <a href="<?= base_url('admin/notifikasi') ?>" data-path="/admin/notifikasi">
            <i data-lucide="message-circle"></i>
            Notifikasi
        </a>
        <a href="<?= base_url('admin/pengaturan') ?>" data-path="/admin/pengaturan">
            <i data-lucide="settings"></i>
            Sistem
        </a>
        <form class="mobile-nav-logout-form" method="post" action="<?= base_url('admin/logout') ?>"
            onsubmit="return confirm('Yakin ingin keluar?')">
            <?= csrf_field() ?>
            <button type="submit">
                <i data-lucide="log-out"></i>
                Keluar
            </button>
        </form>
    </nav>

    <div id="admin-vue-controller" hidden></div>

    <?= $this->renderSection('scripts') ?>

</body>

</html>
