<?php
$pageTitle   = $pageTitle ?? 'Dashboard';
$breadcrumbs = $breadcrumbs ?? [];

$authUser  = session()->get('auth_user') ?? [];
$userName  = $authUser['name'] ?? 'Admin Operator';
$userRole  = $authUser['role'] ?? 'operator';
$userInit  = strtoupper(substr($userName, 0, 1));
$roleLabel = $userRole === 'superadmin' ? 'Super Admin' : 'Sekretariat DPRD';
?>

<header id="topbar" class="navbar sticky top-0 z-40 min-h-16 border-b border-base-300 bg-base-100/95 px-3 backdrop-blur sm:px-5">
    <div class="navbar-start min-w-0 flex-1 gap-2">
        <label for="admin-drawer" class="btn btn-ghost btn-circle drawer-button lg:hidden" aria-label="Buka navigasi utama">
            <i data-lucide="menu"></i>
        </label>

        <div class="flex min-w-0 items-center gap-2 lg:hidden">
            <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>" alt="Logo DPRD" class="h-9 w-9 rounded-box object-contain" />
            <span class="min-w-0">
                <strong class="block truncate text-sm">DPRD Sulawesi Tengah</strong>
                <span class="block truncate text-xs text-base-content/60"><?= esc($pageTitle) ?></span>
            </span>
        </div>

        <div class="breadcrumbs hidden min-w-0 text-sm lg:block" aria-label="Breadcrumb">
            <ul>
                <?php if ($pageTitle === 'Dashboard' && empty($breadcrumbs)): ?>
                    <li><span class="font-bold"><?= esc($pageTitle) ?></span></li>
                <?php else: ?>
                    <li><a href="<?= base_url('admin/dashboard') ?>">Dashboard</a></li>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <li>
                            <?php if (!empty($crumb['url'])): ?>
                                <a href="<?= base_url($crumb['url']) ?>"><?= esc($crumb['label']) ?></a>
                            <?php else: ?>
                                <span><?= esc($crumb['label']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <li><span class="font-bold"><?= esc($pageTitle) ?></span></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="navbar-end w-auto gap-1 sm:gap-2">
        <label class="btn btn-ghost btn-circle swap swap-rotate" title="Gunakan tema gelap"
               aria-label="Gunakan tema gelap" data-theme-toggle>
            <input type="checkbox" value="dark" class="theme-controller" data-theme-toggle-input />
            <i class="swap-on" data-lucide="sun"></i>
            <i class="swap-off" data-lucide="moon"></i>
        </label>

        <a href="<?= base_url('admin/profile') ?>" class="btn btn-ghost hidden h-auto gap-2 px-2 sm:flex" title="Buka profil admin">
            <span class="grid h-8 w-8 place-items-center rounded-full bg-primary/10 text-xs font-black text-primary"><?= esc($userInit) ?></span>
            <span class="hidden text-left lg:block">
                <strong class="block max-w-32 truncate text-xs"><?= esc($userName) ?></strong>
                <span class="block text-[10px] font-normal text-base-content/60"><?= esc($roleLabel) ?></span>
            </span>
        </a>

        <form class="hidden sm:block" method="post" action="<?= base_url('admin/logout') ?>" data-confirm-message="Yakin ingin keluar?">
            <?= csrf_field() ?>
            <button class="btn btn-ghost btn-circle hover:text-error" type="submit" title="Keluar" aria-label="Keluar">
                <i data-lucide="log-out"></i>
            </button>
        </form>
    </div>
</header>
