<?php
$adminCssVersion = is_file(FCPATH . 'assets/css/admin.css') ? filemtime(FCPATH . 'assets/css/admin.css') : time();
$adminJsVersion  = is_file(FCPATH . 'assets/js/admin/main.js') ? filemtime(FCPATH . 'assets/js/admin/main.js') : time();
$fontVersion     = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$vueVersion      = is_file(FCPATH . 'assets/vendor/vue/vue.global.prod.js') ? filemtime(FCPATH . 'assets/vendor/vue/vue.global.prod.js') : time();
$lucideVersion   = is_file(FCPATH . 'assets/vendor/lucide/lucide.min.js') ? filemtime(FCPATH . 'assets/vendor/lucide/lucide.min.js') : time();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>
        <?= esc($pageTitle ?? 'Admin') ?> - Panel Admin Signage DPRD Sulawesi Tengah
    </title>
    <meta name="description"
        content="Panel manajemen sistem informasi digital signage dan notifikasi WhatsApp DPRD Provinsi Sulawesi Tengah." />
    <meta name="robots" content="noindex, nofollow" />

    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet" />
    <?= $this->renderSection('styles') ?>
</head>

<body>

    <div id="sidebar-overlay"></div>

    <div class="od-shell">

        <?= $this->include('admin/layouts/_sidebar') ?>

        <div id="main-wrapper">

            <?= $this->include('admin/layouts/_topbar') ?>

            <main id="content">

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="ta-alert ta-alert-success ta-alert-dismissible ta-alert-flash mb-4" role="alert">
                        <i data-lucide="circle-check" class="mr-2"></i>
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="ta-alert-close" aria-label="Tutup notifikasi"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="ta-alert ta-alert-danger ta-alert-dismissible ta-alert-flash mb-4" role="alert">
                        <i data-lucide="triangle-alert" class="mr-2"></i>
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="ta-alert-close" aria-label="Tutup notifikasi"></button>
                    </div>
                <?php endif; ?>

                <?= $this->renderSection('content') ?>

            </main>

        </div>

    </div>

    <!-- Mobile bottom nav -->
    <nav class="mobile-nav" aria-label="Navigasi mobile">
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

    <script src="<?= base_url('assets/vendor/vue/vue.global.prod.js?v=' . $vueVersion) ?>"></script>
    <script src="<?= base_url('assets/vendor/lucide/lucide.min.js?v=' . $lucideVersion) ?>"></script>
    <script src="<?= base_url('assets/js/admin/main.js?v=' . $adminJsVersion) ?>"></script>

    <?= $this->renderSection('scripts') ?>

</body>

</html>
