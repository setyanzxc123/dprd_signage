<?php
$authUser  = session()->get('auth_user') ?? [];
$userName  = $authUser['name'] ?? 'Admin Operator';
$userRole  = $authUser['role'] ?? 'operator';
$userInit  = strtoupper(substr($userName, 0, 1));
$roleLabel = $userRole === 'superadmin' ? 'Super Admin' : 'Sekretariat DPRD';
?>

<nav id="sidebar" aria-label="Navigasi utama">

    <div class="sidebar-top">
        <div class="sidebar-brand">
            <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>" alt="Logo DPRD" class="brand-logo-img" />
            <div class="brand-copy">
                <div class="brand-title">DPRD Signage</div>
                <div class="brand-sub">Panel Admin</div>
            </div>
        </div>
        <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-label="Ciutkan sidebar" aria-expanded="true" title="Ciutkan sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/></svg>
        </button>
    </div>

    <ul class="sidebar-nav">

        <li class="nav-section-label">Utama</li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/dashboard') ?>" data-path="/admin/dashboard">
                <i class="bi bi-grid-1x2-fill nav-icon"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>

        <li class="nav-section-label">Master Data</li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/unit-rapat') ?>" data-path="/admin/unit-rapat">
                <i class="bi bi-diagram-3-fill nav-icon"></i>
                <span class="nav-text">Kelompok Peserta</span>
            </a>
        </li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/anggota') ?>" data-path="/admin/anggota">
                <i class="bi bi-people-fill nav-icon"></i>
                <span class="nav-text">Anggota DPRD</span>
            </a>
        </li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/ruangan') ?>" data-path="/admin/ruangan">
                <i class="bi bi-door-open-fill nav-icon"></i>
                <span class="nav-text">Ruangan Rapat</span>
            </a>
        </li>

        <li class="nav-section-label">Operasional</li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/jadwal') ?>" data-path="/admin/jadwal">
                <i class="bi bi-calendar3-week-fill nav-icon"></i>
                <span class="nav-text">Jadwal Rapat</span>
                <span class="nav-badge badge bg-primary d-none" id="badge-jadwal"></span>
            </a>
        </li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/notifikasi') ?>" data-path="/admin/notifikasi">
                <i class="bi bi-whatsapp nav-icon"></i>
                <span class="nav-text">Notifikasi WA</span>
                <span class="nav-badge badge bg-danger d-none" id="badge-wa-gagal"></span>
            </a>
        </li>

        <li class="nav-section-label">Tampilan Publik</li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/pengaturan') ?>" data-path="/admin/pengaturan">
                <i class="bi bi-tv-fill nav-icon"></i>
                <span class="nav-text">Pengaturan Signage</span>
            </a>
        </li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('signage') ?>" target="_blank" title="Buka di tab baru">
                <i class="bi bi-display nav-icon"></i>
                <span class="nav-text">Pratinjau Layar TV</span>
            </a>
        </li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('jadwal') ?>" target="_blank" title="Buka di tab baru">
                <i class="bi bi-calendar-check nav-icon"></i>
                <span class="nav-text">Pratinjau Jadwal Publik</span>
            </a>
        </li>

    </ul>

    <div class="sidebar-note">
        Sistem signage dan notifikasi DPRD Provinsi Sulawesi Tengah. Kelola jadwal rapat, kirim pengingat WhatsApp, dan pantau tampilan publik.
    </div>

</nav>
