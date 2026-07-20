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

    <div class="topbar-mobile-title" aria-current="page"><?= esc($pageTitle) ?></div>

    <div class="topbar-mobile-brand">
        <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>" alt="Logo DPRD" class="topbar-mobile-logo" />
        <div class="topbar-mobile-copy">
            <span class="topbar-mobile-name">DPRD Sulawesi Tengah</span>
            <span class="topbar-mobile-sub">Jadwal & Signage</span>
        </div>
    </div>

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

    <div id="topbar-actions" class="topbar-actions" data-turbo-permanent>

        <label class="btn btn-ghost btn-circle swap swap-rotate admin-theme-toggle" title="Gunakan tema gelap"
               aria-label="Gunakan tema gelap" data-theme-toggle>
            <input type="checkbox" value="dark" class="theme-controller" data-theme-toggle-input />
            <i class="swap-on" data-lucide="moon"></i>
            <i class="swap-off" data-lucide="sun"></i>
        </label>

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
            <button class="btn btn-ghost btn-circle hover:text-error" type="submit" title="Logout" aria-label="Logout">
                <i data-lucide="log-out"></i>
            </button>
        </form>
    </div>

</header>
