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
                <div class="brand-sub">Sistem Jadwal Rapat & Signage</div>
            </div>
        </div>
        <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-label="Sembunyikan sidebar" aria-expanded="true" title="Sembunyikan sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/></svg>
        </button>
    </div>

    <div class="sidebar-menu-wrapper">
        <ul class="sidebar-nav menu menu-md w-full! p-0 gap-1 bg-base-100">

            <li class="nav-item-custom">
                <a class="nav-link-custom" href="<?= base_url('admin/dashboard') ?>" data-path="/admin/dashboard">
                    <i data-lucide="layout-dashboard" class="nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-item-custom">
                <details class="nav-group">
                    <summary class="nav-link-custom nav-group-toggle" title="Master Data">
                        <i data-lucide="database" class="nav-icon"></i>
                        <span class="nav-text">Master Data</span>
                    </summary>
                    <ul>
                        <li>
                            <a class="nav-link-custom nav-child-link" href="<?= base_url('admin/anggota') ?>" data-path="/admin/anggota">
                                <span class="nav-text">Anggota DPRD</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link-custom nav-child-link" href="<?= base_url('admin/unit-rapat') ?>" data-path="/admin/unit-rapat">
                                <span class="nav-text">Kelompok Peserta</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link-custom nav-child-link" href="<?= base_url('admin/ruangan') ?>" data-path="/admin/ruangan">
                                <span class="nav-text">Ruangan Rapat</span>
                            </a>
                        </li>
                    </ul>
                </details>
            </li>

            <li class="nav-item-custom">
                <details class="nav-group">
                    <summary class="nav-link-custom nav-group-toggle" title="Agenda">
                        <i data-lucide="calendar-days" class="nav-icon"></i>
                        <span class="nav-text">Agenda</span>
                    </summary>
                    <ul>
                        <li>
                            <a class="nav-link-custom nav-child-link" href="<?= base_url('admin/jadwal-banmus') ?>" data-path="/admin/jadwal-banmus">
                                <span class="nav-text">Agenda Banmus</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link-custom nav-child-link" href="<?= base_url('admin/jadwal-umum') ?>" data-path="/admin/jadwal-umum">
                                <span class="nav-text">Jadwal Umum</span>
                                <span class="nav-badge badge badge-primary badge-sm ml-auto hidden" id="badge-jadwal"></span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link-custom nav-child-link" href="<?= base_url('admin/kalender') ?>" data-path="/admin/kalender">
                                <span class="nav-text">Kalender Agenda</span>
                            </a>
                        </li>
                        <li>
                            <button
                                type="button"
                                class="nav-link-custom nav-child-link menu-disabled"
                                disabled
                                aria-disabled="true"
                                title="Segera hadir">
                                <span class="nav-text">Laporan Agenda</span>
                                <span class="badge badge-ghost badge-xs ml-auto">segera</span>
                            </button>
                        </li>
                    </ul>
                </details>
            </li>

            <li class="nav-item-custom">
                <a class="nav-link-custom" href="<?= base_url('admin/pengaturan') ?>" data-path="/admin/pengaturan">
                    <i data-lucide="settings" class="nav-icon"></i>
                    <span class="nav-text">Pengaturan</span>
                </a>
            </li>

            <li class="nav-item-custom">
                <details class="nav-group">
                    <summary class="nav-link-custom nav-group-toggle" title="Tampilan Publik">
                        <i data-lucide="monitor" class="nav-icon"></i>
                        <span class="nav-text">Tampilan Publik</span>
                    </summary>
                    <ul>
                        <li>
                            <a class="nav-link-custom nav-child-link" href="<?= base_url('signage') ?>" target="_blank" rel="noopener" title="Buka di tab baru">
                                <span class="nav-text">Layar TV</span>
                                <i data-lucide="external-link" class="nav-external-icon" aria-hidden="true"></i>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link-custom nav-child-link" href="<?= base_url('agenda') ?>" target="_blank" rel="noopener" title="Buka di tab baru">
                                <span class="nav-text">Jadwal Publik</span>
                                <i data-lucide="external-link" class="nav-external-icon" aria-hidden="true"></i>
                            </a>
                        </li>
                    </ul>
                </details>
            </li>

            <li class="nav-account-divider lg:hidden" aria-hidden="true"></li>
            <li class="nav-item-custom lg:hidden">
                <a class="nav-link-custom" href="<?= base_url('admin/profile') ?>" data-path="/admin/profile">
                    <i data-lucide="user-round" class="nav-icon"></i>
                    <span class="nav-text">Profil Admin</span>
                </a>
            </li>
            <li class="nav-item-custom nav-account-logout-item lg:hidden">
                <form id="sidebar-logout-form" class="sidebar-logout-form" method="post" action="<?= base_url('admin/logout') ?>" data-confirm-message="Yakin ingin keluar?">
                    <?= csrf_field() ?>
                </form>
                <button type="submit" form="sidebar-logout-form" class="nav-link-custom sidebar-logout">
                    <i data-lucide="log-out" class="nav-icon"></i>
                    <span class="nav-text">Keluar</span>
                </button>
            </li>

        </ul>

    </div>
</nav>
