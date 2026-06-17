<?php
$authUser  = session()->get('auth_user') ?? [];
$userName  = $authUser['name'] ?? 'Admin Operator';
$userRole  = $authUser['role'] ?? 'operator';
$userInit  = strtoupper(substr($userName, 0, 1));
$roleLabel = $userRole === 'superadmin' ? 'Super Admin' : 'Sekretariat DPRD';
?>

<nav id="sidebar" class="border-r border-base-300 bg-base-100" aria-label="Navigasi utama" data-turbo-permanent>

    <div class="sidebar-top">
        <div class="sidebar-brand">
            <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>" alt="Logo DPRD" class="brand-logo-img" />
            <div class="brand-copy">
                <div class="brand-title">DPRD Sulawesi Tengah</div>
                <div class="brand-sub">Sistem Notifikasi Rapat & Signage</div>
            </div>
        </div>
        <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-label="Sembunyikan sidebar" aria-expanded="true" title="Sembunyikan sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/></svg>
        </button>
    </div>

    <div class="sidebar-menu-wrapper">
        <ul class="sidebar-nav menu menu-md p-0 gap-1 bg-base-100">

            <li class="nav-section-label menu-title text-xs font-bold uppercase tracking-wider text-base-content/40 mt-4 mb-1 p-0">Utama</li>
            <li class="nav-item-custom">
                <a class="nav-link-custom" href="<?= base_url('admin/dashboard') ?>" data-path="/admin/dashboard">
                    <i data-lucide="layout-dashboard" class="nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-section-label menu-title text-xs font-bold uppercase tracking-wider text-base-content/40 mt-4 mb-1 p-0">Master Data</li>
            <li class="nav-item-custom">
                <a class="nav-link-custom" href="<?= base_url('admin/unit-rapat') ?>" data-path="/admin/unit-rapat">
                    <i data-lucide="workflow" class="nav-icon"></i>
                    <span class="nav-text">Kelompok Peserta</span>
                </a>
            </li>
            <li class="nav-item-custom">
                <a class="nav-link-custom" href="<?= base_url('admin/anggota') ?>" data-path="/admin/anggota">
                    <i data-lucide="users" class="nav-icon"></i>
                    <span class="nav-text">Anggota DPRD</span>
                </a>
            </li>
            <li class="nav-item-custom">
                <a class="nav-link-custom" href="<?= base_url('admin/ruangan') ?>" data-path="/admin/ruangan">
                    <i data-lucide="door-open" class="nav-icon"></i>
                    <span class="nav-text">Ruangan Rapat</span>
                </a>
            </li>

            <li class="nav-section-label menu-title text-xs font-bold uppercase tracking-wider text-base-content/40 mt-4 mb-1 p-0">Operasional</li>
            <li class="nav-item-custom">
                <a class="nav-link-custom" href="<?= base_url('admin/jadwal') ?>" data-path="/admin/jadwal">
                    <i data-lucide="calendar-days" class="nav-icon"></i>
                    <span class="nav-text">Jadwal Rapat</span>
                    <span class="nav-badge badge badge-primary badge-sm ml-auto hidden" id="badge-jadwal"></span>
                </a>
            </li>
            <li class="nav-item-custom">
                <a class="nav-link-custom" href="<?= base_url('admin/notifikasi') ?>" data-path="/admin/notifikasi">
                    <i data-lucide="message-circle" class="nav-icon"></i>
                    <span class="nav-text">Notifikasi WA</span>
                    <span class="nav-badge badge badge-error badge-sm ml-auto hidden" id="badge-wa-gagal"></span>
                </a>
            </li>

            <li class="nav-section-label menu-title text-xs font-bold uppercase tracking-wider text-base-content/40 mt-4 mb-1 p-0">Sistem</li>
            <li class="nav-item-custom">
                <a class="nav-link-custom" href="<?= base_url('admin/pengaturan') ?>" data-path="/admin/pengaturan">
                    <i data-lucide="settings" class="nav-icon"></i>
                    <span class="nav-text">Pengaturan Sistem</span>
                </a>
            </li>
            <li class="nav-section-label menu-title text-xs font-bold uppercase tracking-wider text-base-content/40 mt-4 mb-1 p-0">Tampilan Publik</li>
            <li class="nav-item-custom">
                <a class="nav-link-custom" href="<?= base_url('signage') ?>" target="_blank" title="Buka di tab baru">
                    <i data-lucide="monitor" class="nav-icon"></i>
                    <span class="nav-text">Pratinjau Layar TV</span>
                </a>
            </li>
            <li class="nav-item-custom">
                <a class="nav-link-custom" href="<?= base_url('jadwal') ?>" target="_blank" title="Buka di tab baru">
                    <i data-lucide="calendar-check" class="nav-icon"></i>
                    <span class="nav-text">Pratinjau Jadwal Publik</span>
                </a>
            </li>

            <li class="nav-mobile-logout-divider" aria-hidden="true"></li>
            <li class="nav-item-custom nav-mobile-logout-item">
                <form id="sidebar-mobile-logout-form" class="sidebar-mobile-logout-form" method="post" action="<?= base_url('admin/logout') ?>" onsubmit="return confirm('Yakin ingin keluar?')">
                    <?= csrf_field() ?>
                </form>
                <button type="submit" form="sidebar-mobile-logout-form" class="nav-link-custom sidebar-mobile-logout">
                    <i data-lucide="log-out" class="nav-icon"></i>
                    <span class="nav-text">Keluar</span>
                </button>
            </li>

        </ul>

    </div>
</nav>
