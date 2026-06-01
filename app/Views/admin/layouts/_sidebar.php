<?php
$authUser  = session()->get('auth_user') ?? [];
$userName  = $authUser['name'] ?? 'Admin Operator';
$userRole  = $authUser['role'] ?? 'operator';
$userInit  = strtoupper(substr($userName, 0, 1));
$roleLabel = $userRole === 'superadmin' ? 'Super Admin' : 'Sekretariat DPRD';
?>

<nav id="sidebar" aria-label="Navigasi utama">

    <div class="sidebar-brand">
        <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>" alt="Logo DPRD" class="brand-logo-img" />
        <div>
            <div class="brand-title">DPRD Signage</div>
            <div class="brand-sub">Panel Admin</div>
        </div>
    </div>

    <ul class="sidebar-nav">

        <li class="nav-section-label">Utama</li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/dashboard') ?>" data-path="/admin/dashboard">
                <i class="bi bi-grid-1x2-fill nav-icon"></i>
                Dashboard
            </a>
        </li>

        <li class="nav-section-label">Master Data</li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/anggota') ?>" data-path="/admin/anggota">
                <i class="bi bi-people-fill nav-icon"></i>
                Anggota DPRD
            </a>
        </li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/ruangan') ?>" data-path="/admin/ruangan">
                <i class="bi bi-door-open-fill nav-icon"></i>
                Ruangan Rapat
            </a>
        </li>

        <li class="nav-section-label">Operasional</li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/jadwal') ?>" data-path="/admin/jadwal">
                <i class="bi bi-calendar3-week-fill nav-icon"></i>
                Jadwal Rapat
                <span class="nav-badge badge bg-primary d-none" id="badge-jadwal"></span>
            </a>
        </li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/notifikasi') ?>" data-path="/admin/notifikasi">
                <i class="bi bi-whatsapp nav-icon"></i>
                Notifikasi WA
                <span class="nav-badge badge bg-danger d-none" id="badge-wa-gagal"></span>
            </a>
        </li>

        <li class="nav-section-label">Sistem</li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('admin/pengaturan') ?>" data-path="/admin/pengaturan">
                <i class="bi bi-tv-fill nav-icon"></i>
                Pengaturan Signage
            </a>
        </li>
        <li class="nav-item-custom">
            <a class="nav-link-custom" href="<?= base_url('signage') ?>" target="_blank" title="Buka di tab baru">
                <i class="bi bi-display nav-icon"></i>
                Pratinjau Layar TV
            </a>
        </li>

    </ul>

    <div class="sidebar-note">
        Sistem signage dan notifikasi DPRD Provinsi Sulawesi Tengah. Kelola jadwal rapat, kirim pengingat WhatsApp, dan atur tampilan layar TV.
    </div>

</nav>
