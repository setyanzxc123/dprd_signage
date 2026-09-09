<?php
$pageTitle   = $pageTitle ?? 'Dashboard';
$breadcrumbs = $breadcrumbs ?? [];
$logoVersion = is_file(FCPATH . 'assets/images/logo_dprd.png') ? filemtime(FCPATH . 'assets/images/logo_dprd.png') : time();

$authUser  = session()->get('auth_user') ?? [];
$userName  = $authUser['name'] ?? 'Admin Operator';
$userRole  = $authUser['role'] ?? 'operator';
$userInit  = strtoupper(substr($userName, 0, 1));
$roleLabel = $userRole === 'superadmin' ? 'Super Admin' : 'Sekretariat DPRD';

if (empty($breadcrumbs) && $pageTitle !== 'Dashboard') {
    $currentRoute = '/' . ltrim(service('uri')->getRoutePath(), '/');
    $currentRoute = $currentRoute === '/' ? '/' : rtrim($currentRoute, '/');

    $parentMap = [
        '/admin/jadwal-banmus' => ['label' => 'Agenda Banmus', 'url' => 'admin/jadwal-banmus'],
        '/admin/jadwal-umum'   => ['label' => 'Jadwal Umum', 'url' => 'admin/jadwal-umum'],
        '/admin/anggota'       => ['label' => 'Anggota DPRD', 'url' => 'admin/anggota'],
        '/admin/unit-rapat'    => ['label' => 'Kelompok Peserta', 'url' => 'admin/unit-rapat'],
        '/admin/ruangan'       => ['label' => 'Ruangan Rapat', 'url' => 'admin/ruangan'],
        '/admin/kalender'      => ['label' => 'Kalender Agenda', 'url' => 'admin/kalender'],
        '/admin/notulen'       => ['label' => 'Notulensi & Risalah AI', 'url' => 'admin/notulen'],
    ];

    foreach ($parentMap as $parentPath => $crumb) {
        if (str_starts_with($currentRoute, $parentPath . '/')) {
            $breadcrumbs = [$crumb];
            break;
        }
    }
}
?>

<header id="admin-topbar" class="h-16 sticky top-0 inset-x-0 z-40 flex items-center w-full bg-white/95 dark:bg-slate-900/95 border-b border-slate-200 dark:border-slate-800 text-sm lg:ps-64 backdrop-blur-md">
    <div class="w-full mx-auto px-4 sm:px-6 flex items-center justify-between h-full">
        <div class="flex items-center gap-x-3">
            <button type="button" class="py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs lg:hidden transition cursor-pointer" data-hs-overlay="#application-sidebar" aria-controls="application-sidebar" aria-haspopup="dialog" aria-expanded="false" aria-label="Buka navigasi">
                <i data-lucide="menu" class="size-4.5"></i>
            </button>

            <div class="flex min-w-0 items-center gap-2.5 lg:hidden">
                <img src="<?= base_url('assets/images/logo_dprd.png?v=' . $logoVersion) ?>" alt="Logo DPRD" width="36" height="36" loading="eager" fetchpriority="high" decoding="async" class="size-9 shrink-0 object-contain" />
                <div class="min-w-0 leading-tight">
                    <strong class="block truncate text-xs font-bold text-slate-900 dark:text-white">E-Agenda</strong>
                    <span class="block truncate text-[10px] font-semibold text-slate-500 dark:text-slate-400">DPRD Sulteng</span>
                </div>
            </div>

            <nav class="hidden min-w-0 lg:block" aria-label="Breadcrumb">
                <ol class="flex items-center whitespace-nowrap gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400">
                    <?php if ($pageTitle === 'Dashboard' && empty($breadcrumbs)): ?>
                        <li class="flex items-center text-slate-900 dark:text-white font-bold">
                            <span><?= esc($pageTitle) ?></span>
                        </li>
                    <?php else: ?>
                        <li class="inline-flex items-center">
                            <a href="<?= base_url('admin/dashboard') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Dashboard</a>
                        </li>
                        <?php foreach ($breadcrumbs as $crumb): ?>
                            <li class="inline-flex items-center gap-1.5">
                                <svg class="size-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                <?php if (!empty($crumb['url'])): ?>
                                    <a href="<?= base_url($crumb['url']) ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition"><?= esc($crumb['label']) ?></a>
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
            <!-- Background Task Monitor AI Dropdown -->
            <div class="hs-dropdown relative inline-flex [--placement:bottom-right] [--strategy:fixed] sm:[--strategy:absolute]">
                <button id="hs-dropdown-task-monitor" type="button" class="hs-dropdown-toggle relative inline-flex justify-center items-center size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs cursor-pointer transition focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-hidden" aria-haspopup="dialog" aria-expanded="false" aria-label="Antrean Proses AI" title="Antrean Proses AI">
                    <i data-lucide="layers" class="size-4" id="task_monitor_icon"></i>
                    <!-- Badge Counter / Indicator Dot -->
                    <span id="task_monitor_badge" class="hidden absolute -top-1 -end-1 flex min-w-4 h-4 px-1 items-center justify-center rounded-full bg-rose-600 dark:bg-rose-600 text-[10px] font-bold text-white shadow-xs motion-safe:animate-pulse leading-none">
                        0
                    </span>
                </button>

                <span id="csrf_global_token" class="hidden"><?= csrf_field() ?></span>

                <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden w-[calc(100vw-1.5rem)] sm:w-96 max-w-sm sm:max-w-md bg-white dark:bg-slate-900 shadow-xl rounded-2xl border border-slate-200/80 dark:border-slate-800 z-50 mt-2 overflow-hidden" role="dialog" aria-label="Antrean Proses AI" aria-modal="false" aria-labelledby="hs-dropdown-task-monitor">
                    <!-- Header Dropdown -->
                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                        <div class="flex items-center gap-2">
                            <i data-lucide="activity" class="size-4 text-blue-600 dark:text-blue-400"></i>
                            <span class="text-xs font-bold text-slate-900 dark:text-slate-100">Antrean Proses AI</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span id="task_monitor_header_count" class="py-0.5 px-2 rounded-full text-[10px] font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                0 Aktif
                            </span>
                            <button type="button" id="btn_task_monitor_refresh" class="size-7 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-800 transition cursor-pointer focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-hidden" title="Perbarui Status" aria-label="Perbarui status antrean">
                                <i data-lucide="rotate-cw" class="size-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Body List: Active Tasks & Recent History -->
                    <div id="task_monitor_body" class="max-h-[65vh] sm:max-h-80 overflow-y-auto p-3 space-y-3 divide-y divide-slate-100 dark:divide-slate-800/60">
                        <!-- Active Tasks Section -->
                        <div id="task_monitor_active_container" class="space-y-2 hidden">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 px-1">
                                Sedang Berjalan
                            </div>
                            <div id="task_monitor_active_list" class="space-y-2"></div>
                        </div>

                        <!-- Recent Finished Section -->
                        <div id="task_monitor_recent_container" class="space-y-2 pt-2 hidden">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 px-1">
                                Baru Selesai
                            </div>
                            <div id="task_monitor_recent_list" class="space-y-1.5"></div>
                        </div>

                        <!-- Empty State -->
                        <div id="task_monitor_empty" class="py-6 text-center text-slate-500 dark:text-slate-400 space-y-1.5">
                            <div class="size-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                                <i data-lucide="check-circle" class="size-4"></i>
                            </div>
                            <p class="text-xs font-medium text-slate-700 dark:text-slate-300">Tidak ada proses AI yang berjalan</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Antrean pemrosesan notulensi sedang kosong.</p>
                        </div>
                    </div>

                    <!-- Footer Dropdown -->
                    <div class="px-3 py-2.5 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 text-center">
                        <a href="<?= base_url('admin/notulen') ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-hidden rounded-md px-2 py-1">
                            <span>Buka Notulensi &amp; Risalah AI</span>
                            <i data-lucide="chevron-right" class="size-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <label class="inline-flex justify-center items-center size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs cursor-pointer transition" title="Gunakan tema gelap" aria-label="Gunakan tema gelap" data-theme-toggle>
                <input type="checkbox" value="dark" class="hidden" data-theme-toggle-input />
                <i class="theme-icon-sun size-4" data-lucide="sun"></i>
                <i class="theme-icon-moon size-4" data-lucide="moon"></i>
            </label>

            <a href="<?= base_url('admin/profile') ?>" class="inline-flex items-center gap-x-2.5 py-1.5 px-2 sm:px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition" title="Buka profil admin" aria-label="Profil <?= esc($userName) ?>">
                <span class="grid size-7 place-items-center rounded-lg bg-blue-500/15 text-xs font-black text-blue-600 dark:text-blue-400"><?= esc($userInit) ?></span>
                <span class="hidden text-left lg:block leading-tight">
                    <strong class="block max-w-32 truncate text-xs font-bold text-slate-800 dark:text-slate-200"><?= esc($userName) ?></strong>
                    <span class="block text-[10px] font-medium text-slate-500 dark:text-slate-400"><?= esc($roleLabel) ?></span>
                </span>
            </a>

            <form class="hidden sm:block" method="post" action="<?= base_url('admin/logout') ?>"
                  data-confirm-title="Keluar dari Panel Admin"
                  data-confirm-message="Yakin ingin keluar dari sesi admin ini?"
                  data-confirm-button="Keluar"
                  data-confirm-variant="danger">
                <?= csrf_field() ?>
                <button class="inline-flex justify-center items-center size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 shadow-xs transition" type="submit" title="Keluar" aria-label="Keluar">
                    <i data-lucide="log-out" class="size-4"></i>
                </button>
            </form>
        </div>
    </div>
</header>
