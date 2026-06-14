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

    <button class="topbar-toggle" type="button" aria-label="Toggle sidebar">
        <i data-lucide="menu"></i>
    </button>

    <nav class="topbar-breadcrumb" aria-label="breadcrumb">
        <?php if ($pageTitle === 'Dashboard' && empty($breadcrumbs)): ?>
            <strong class="current"><?= esc($pageTitle) ?></strong>
        <?php else: ?>
            <a href="<?= base_url('admin/dashboard') ?>">Dashboard</a>
            <?php foreach ($breadcrumbs as $crumb): ?>
                <span class="bc-sep">/</span>
                <?php if (!empty($crumb['url'])): ?>
                    <a href="<?= base_url($crumb['url']) ?>"><?= esc($crumb['label']) ?></a>
                <?php else: ?>
                    <span><?= esc($crumb['label']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
            <span class="bc-sep">/</span>
            <strong class="current"><?= esc($pageTitle) ?></strong>
        <?php endif; ?>
    </nav>

    <div class="topbar-actions">

        <!-- Status Koneksi WA -->
        <a class="topbar-wa-status-link" href="<?= base_url('admin/pengaturan#wa-notif-card') ?>" id="topbar-wa-status" title="Memeriksa status WhatsApp...">
            <span class="status-badge" id="topbar-wa-badge">
                <span class="dot" id="topbar-wa-dot"></span>
                <span id="topbar-wa-text">WhatsApp: Memeriksa...</span>
            </span>
        </a>

        <a class="ta-topbar-button" href="<?= base_url('admin/notifikasi') ?>" title="Notifikasi WA">
            <i data-lucide="bell"></i>
            <span class="notif-dot hidden" id="topbar-notif-dot"></span>
        </a>

        <div class="topbar-user">
            <div class="topbar-avatar"><?= esc($userInit) ?></div>
            <div class="topbar-user-info">
                <div class="topbar-user-name"><?= esc($userName) ?></div>
                <div class="topbar-user-role"><?= esc($roleLabel) ?></div>
            </div>
        </div>

        <form class="topbar-logout-form" method="post" action="<?= base_url('admin/logout') ?>"
              onsubmit="return confirm('Yakin ingin keluar?')">
            <?= csrf_field() ?>
            <button class="ta-topbar-button topbar-logout" type="submit" title="Logout" aria-label="Logout">
                <i data-lucide="log-out"></i>
            </button>
        </form>
    </div>

</header>
