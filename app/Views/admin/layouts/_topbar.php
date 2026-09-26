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
            <!-- Aktivasi Push Notification Browser -->
            <button id="btn_aktifkan_push" type="button"
                    class="max-md:hidden inline-flex items-center gap-x-2 h-9 px-3 rounded-xl border border-blue-500 bg-blue-50 text-blue-700 hover:bg-blue-100 dark:border-blue-400 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20 text-xs font-semibold shadow-xs transition cursor-pointer"
                    title="Terima notifikasi OS saat panel tidak terbuka"
                    aria-label="Aktifkan notifikasi browser">
                <i data-lucide="bell-ring" class="size-4"></i>
                <span class="hidden lg:inline">Aktifkan Notifikasi</span>
            </button>

            <!-- Unified Notification Hub Dropdown -->
            <div class="hs-dropdown relative inline-flex [--placement:bottom-right] [--strategy:fixed] sm:[--strategy:absolute] [--auto-close:inside]">
                <button id="hs-dropdown-notifications" type="button" class="hs-dropdown-toggle relative inline-flex justify-center items-center size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs cursor-pointer transition focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-hidden" aria-haspopup="dialog" aria-expanded="false" aria-label="Notifikasi Sistem" title="Notifikasi Sistem">
                    <i data-lucide="bell" class="size-4" id="notification_hub_icon"></i>
                    <!-- Badge Counter / Indicator Dot -->
                    <span id="notification_hub_badge" class="hidden absolute -top-1 -end-1 min-w-4.5 h-4.5 px-1 items-center justify-center rounded-full bg-rose-600 text-[11px] font-bold text-white shadow-xs animate-pulse leading-none">
                        0
                    </span>
                </button>

                <span id="csrf_global_token" class="hidden"><?= csrf_field() ?></span>

                <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden w-[calc(100vw-1.5rem)] sm:w-96 max-w-sm sm:max-w-md bg-white dark:bg-slate-900 shadow-xl rounded-2xl border border-slate-200/80 dark:border-slate-800 z-50 mt-2 overflow-hidden" role="dialog" aria-label="Pusat Notifikasi & Aktivitas" aria-modal="false" aria-labelledby="hs-dropdown-notifications">
                    <!-- Header Dropdown -->
                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                        <div class="flex items-center gap-2">
                            <i data-lucide="bell-ring" class="size-4 text-blue-600 dark:text-blue-400"></i>
                            <span class="text-xs font-bold text-slate-900 dark:text-slate-100">Pusat Notifikasi</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span id="notification_hub_header_count" role="status" aria-live="polite" class="py-0.5 px-2 rounded-full text-[11px] font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                Semua Aman
                            </span>
                            <button type="button" id="btn_notification_hub_refresh" class="size-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-800 transition cursor-pointer focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-hidden" title="Perbarui Notifikasi" aria-label="Perbarui notifikasi">
                                <i data-lucide="rotate-cw" class="size-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Preline Tabs Nav -->
                    <div class="px-3 pt-2 pb-1 border-b border-slate-100 dark:border-slate-800">
                        <nav class="flex gap-x-1 p-0.5 bg-slate-100 dark:bg-slate-800/60 rounded-xl" aria-label="Tabs" role="tablist">
                            <button type="button" class="hs-tab-active:bg-white hs-tab-active:text-slate-800 hs-tab-active:shadow-xs dark:hs-tab-active:bg-slate-900 dark:hs-tab-active:text-slate-200 py-2 px-3 flex-1 inline-flex justify-center items-center gap-x-1.5 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-hidden cursor-pointer active" id="notif-tab-alerts-btn" aria-selected="true" data-hs-tab="#notif-tab-alerts" aria-controls="notif-tab-alerts" role="tab">
                                <span class="truncate">Peringatan</span>
                                <span id="tab_alerts_count_pill" class="hidden py-0.5 px-1.5 text-[11px] font-bold rounded-full bg-rose-600 text-white leading-none ring-1 ring-white/20">0</span>
                            </button>
                            <button type="button" class="hs-tab-active:bg-white hs-tab-active:text-slate-800 hs-tab-active:shadow-xs dark:hs-tab-active:bg-slate-900 dark:hs-tab-active:text-slate-200 py-2 px-3 flex-1 inline-flex justify-center items-center gap-x-1.5 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-hidden cursor-pointer" id="notif-tab-ai-btn" aria-selected="false" data-hs-tab="#notif-tab-ai" aria-controls="notif-tab-ai" role="tab">
                                <span class="truncate">Antrean AI</span>
                                <span id="tab_ai_count_pill" class="hidden py-0.5 px-1.5 text-[11px] font-bold rounded-full bg-blue-600 text-white leading-none ring-1 ring-white/20">0</span>
                            </button>
                        </nav>
                    </div>

                    <!-- Feed Error Banner -->
                    <div id="notif_hub_error" class="hidden mx-3 mt-2 px-3 py-2 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-700 dark:text-rose-300 text-[11px] font-semibold flex items-center gap-2" role="alert">
                        <i data-lucide="cloud-off" class="size-3.5 shrink-0"></i>
                        <span class="min-w-0">Gagal memuat notifikasi dari server. Periksa koneksi atau muat ulang halaman.</span>
                    </div>

                    <!-- Tab Content Panes -->
                    <div class="min-h-[220px] max-h-[60vh] sm:max-h-80 overflow-y-auto p-3">
                        <!-- Pane 1: Peringatan Sistem -->
                        <div id="notif-tab-alerts" role="tabpanel" aria-labelledby="notif-tab-alerts-btn" class="space-y-2.5">
                            <div id="notif_alerts_list" class="space-y-2"></div>
                            <!-- Empty state for Alerts -->
                            <div id="notif_alerts_empty" class="py-6 text-center text-slate-500 dark:text-slate-400 space-y-1.5">
                                <div class="size-8 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
                                    <i data-lucide="check-circle-2" class="size-4"></i>
                                </div>
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Semua Sistem Normal</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Tidak ada kendala pada koneksi gateway atau layanan.</p>
                            </div>
                        </div>

                        <!-- Pane 2: Antrean AI -->
                        <div id="notif-tab-ai" class="hidden space-y-3 divide-y divide-slate-100 dark:divide-slate-800/60" role="tabpanel" aria-labelledby="notif-tab-ai-btn">
                            <!-- Active Tasks Section -->
                            <div id="task_monitor_active_container" class="space-y-2 hidden">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-1">
                                    Sedang Berjalan
                                </div>
                                <div id="task_monitor_active_list" class="space-y-2"></div>
                            </div>

                            <!-- Recent Finished Section -->
                            <div id="task_monitor_recent_container" class="space-y-2 pt-2 hidden">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-1">
                                    Baru Selesai
                                </div>
                                <div id="task_monitor_recent_list" class="space-y-1.5"></div>
                            </div>

                            <!-- Empty State AI -->
                            <div id="task_monitor_empty" class="py-6 text-center text-slate-500 dark:text-slate-400 space-y-1.5">
                                <div class="size-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                                    <i data-lucide="layers" class="size-4"></i>
                                </div>
                                <p class="text-xs font-medium text-slate-700 dark:text-slate-300">Tidak ada proses AI yang berjalan</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Antrean pemrosesan notulensi sedang kosong.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Dropdown -->
                    <div class="px-3 py-2 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <a href="<?= base_url('admin/pengaturan') ?>" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition font-medium">
                            Pengaturan
                        </a>
                        <a href="<?= base_url('admin/notulen') ?>" class="inline-flex items-center gap-1 font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition">
                            <span>Buka Risalah AI</span>
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
