<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>
        <?= esc($pageTitle ?? 'Admin') ?> — Panel Admin Signage DPRD Sulawesi Tengah
    </title>
    <meta name="description"
        content="Panel manajemen sistem informasi digital signage dan notifikasi WhatsApp DPRD Provinsi Sulawesi Tengah." />
    <meta name="robots" content="noindex, nofollow" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />

    <link href="<?= base_url('assets/css/admin/token.css') ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin/layout.css') ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin/components.css') ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin/responsive.css') ?>" rel="stylesheet" />
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
                    <div class="alert alert-success alert-dismissible alert-flash mb-4" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible alert-flash mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?= $this->renderSection('content') ?>

            </main>

        </div>

    </div>

    <!-- Mobile bottom nav -->
    <nav class="mobile-nav" aria-label="Navigasi mobile">
        <a href="<?= base_url('admin/dashboard') ?>" data-path="/admin/dashboard">
            <i class="bi bi-grid-1x2-fill"></i>
            Home
        </a>
        <a href="<?= base_url('admin/jadwal') ?>" data-path="/admin/jadwal">
            <i class="bi bi-calendar3-week-fill"></i>
            Jadwal
        </a>
        <a href="<?= base_url('admin/notifikasi') ?>" data-path="/admin/notifikasi">
            <i class="bi bi-whatsapp"></i>
            Notifikasi
        </a>
        <a href="<?= base_url('admin/pengaturan') ?>" data-path="/admin/pengaturan">
            <i class="bi bi-tv-fill"></i>
            Signage
        </a>
        <a href="<?= base_url('admin/logout') ?>" onclick="return confirm('Yakin ingin keluar?')">
            <i class="bi bi-box-arrow-right"></i>
            Keluar
        </a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="<?= base_url('assets/js/admin/main.js') ?>"></script>

    <?= $this->renderSection('scripts') ?>

</body>

</html>
