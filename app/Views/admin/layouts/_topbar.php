<?php
$pageTitle   = $pageTitle ?? 'Dashboard';
$breadcrumbs = $breadcrumbs ?? [];
$logoVersion = is_file(FCPATH . 'assets/images/logo_dprd.jpg') ? filemtime(FCPATH . 'assets/images/logo_dprd.jpg') : time();

$authUser  = session()->get('auth_user') ?? [];
$userName  = $authUser['name'] ?? 'Admin Operator';
$userRole  = $authUser['role'] ?? 'operator';
$userInit  = strtoupper(substr($userName, 0, 1));
$roleLabel = $userRole === 'superadmin' ? 'Super Admin' : 'Sekretariat DPRD';
?>

<header id="topbar" class="sticky top-0 z-30 flex min-h-16 items-center justify-between border-b border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-slate-900/95 px-4 sm:px-6 backdrop-blur-md shadow-xs">
    <div class="flex items-center min-w-0 flex-1 gap-3">
        <label for="admin-drawer" class="inline-flex justify-center items-center size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs lg:hidden cursor-pointer" aria-label="Buka navigasi utama">
            <i data-lucide="menu" class="size-4.5"></i>
        </label>

        <div class="flex min-w-0 items-center gap-2.5 lg:hidden">
            <div class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-full ring-2 ring-emerald-500/40 bg-white shadow-xs p-0.5">
                <img src="<?= base_url('assets/images/logo_dprd.jpg?v=' . $logoVersion) ?>" alt="Logo DPRD" class="h-full w-full rounded-full object-contain" />
            </div>
            <span class="min-w-0 leading-tight">
                <strong class="block truncate text-xs font-black uppercase text-slate-900 dark:text-white">DPRD Sulteng</strong>
                <span class="block truncate text-[11px] font-medium text-slate-400 dark:text-slate-500"><?= esc($pageTitle) ?></span>
            </span>
        </div>

        <nav class="hidden min-w-0 lg:block" aria-label="Breadcrumb">
            <ol class="flex items-center whitespace-nowrap gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400">
                <?php if ($pageTitle === 'Dashboard' && empty($breadcrumbs)): ?>
                    <li class="flex items-center text-slate-900 dark:text-white font-bold">
                        <span><?= esc($pageTitle) ?></span>
                    </li>
                <?php else: ?>
                    <li class="inline-flex items-center">
                        <a href="<?= base_url('admin/dashboard') ?>" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Dashboard</a>
                    </li>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <li class="inline-flex items-center gap-1.5">
                            <svg class="size-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            <?php if (!empty($crumb['url'])): ?>
                                <a href="<?= base_url($crumb['url']) ?>" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition"><?= esc($crumb['label']) ?></a>
                            <?php else: ?>
                                <span><?= esc($crumb['label']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <li class="inline-flex items-center gap-1.5 text-slate-900 dark:text-white font-bold">
                        <svg class="size-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        <span><?= esc($pageTitle) ?></span>
                    </li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>

    <div class="flex items-center gap-2 shrink-0">
        <label class="inline-flex justify-center items-center size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs cursor-pointer transition" title="Gunakan tema gelap" aria-label="Gunakan tema gelap" data-theme-toggle>
            <input type="checkbox" value="dark" class="hidden" data-theme-toggle-input />
            <i class="theme-icon-sun size-4" data-lucide="sun"></i>
            <i class="theme-icon-moon size-4" data-lucide="moon"></i>
        </label>

        <a href="<?= base_url('admin/profile') ?>" class="hidden sm:inline-flex items-center gap-x-2.5 py-1.5 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition" title="Buka profil admin">
            <span class="grid size-7 place-items-center rounded-lg bg-emerald-500/15 text-xs font-black text-emerald-600 dark:text-emerald-400"><?= esc($userInit) ?></span>
            <span class="hidden text-left lg:block leading-tight">
                <strong class="block max-w-32 truncate text-xs font-bold text-slate-800 dark:text-slate-200"><?= esc($userName) ?></strong>
                <span class="block text-[10px] font-medium text-slate-400 dark:text-slate-500"><?= esc($roleLabel) ?></span>
            </span>
        </a>

        <form class="hidden sm:block" method="post" action="<?= base_url('admin/logout') ?>" data-confirm-message="Yakin ingin keluar?">
            <?= csrf_field() ?>
            <button class="inline-flex justify-center items-center size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 shadow-xs transition" type="submit" title="Keluar" aria-label="Keluar">
                <i data-lucide="log-out" class="size-4"></i>
            </button>
        </form>
    </div>
</header>
