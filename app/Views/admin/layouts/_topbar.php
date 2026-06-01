<?php
$pageTitle   = $pageTitle ?? 'Dashboard';
$breadcrumbs = $breadcrumbs ?? [];

$authUser  = session()->get('auth_user') ?? [];
$userName  = $authUser['name'] ?? 'Admin Operator';
$userRole  = $authUser['role'] ?? 'operator';
$userInit  = strtoupper(substr($userName, 0, 1));
$roleLabel = $userRole === 'superadmin' ? 'Super Admin' : 'Sekretariat DPRD';
?>

<header id="topbar">

    <button class="topbar-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
    </button>

    <nav class="topbar-breadcrumb" aria-label="breadcrumb">
        <span>Admin</span>
        <span>/</span>
        <?php foreach ($breadcrumbs as $crumb): ?>
            <?php if (!empty($crumb['url'])): ?>
                <a href="<?= base_url($crumb['url']) ?>"><?= esc($crumb['label']) ?></a>
            <?php else: ?>
                <?= esc($crumb['label']) ?>
            <?php endif; ?>
            <span>/</span>
        <?php endforeach; ?>
        <strong class="current"><?= esc($pageTitle) ?></strong>
    </nav>

    <div class="topbar-actions">
        <div class="topbar-clock" id="topbar-clock">--:--:-- WITA</div>

        <a class="topbar-icon-btn" href="<?= base_url('admin/notifikasi') ?>" title="Notifikasi WA">
            <i class="bi bi-bell"></i>
            <span class="notif-dot d-none" id="topbar-notif-dot"></span>
        </a>

        <div class="topbar-user">
            <div class="topbar-avatar"><?= esc($userInit) ?></div>
            <div class="topbar-user-info">
                <div class="topbar-user-name"><?= esc($userName) ?></div>
                <div class="topbar-user-role"><?= esc($roleLabel) ?></div>
            </div>
        </div>

        <a class="topbar-icon-btn topbar-logout" href="<?= base_url('admin/logout') ?>" title="Logout"
           onclick="return confirm('Yakin ingin keluar?')">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>

</header>
