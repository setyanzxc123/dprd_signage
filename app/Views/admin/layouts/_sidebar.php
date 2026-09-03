<?php
$logoVersion = is_file(FCPATH . 'assets/images/logo_dprd.jpg') ? filemtime(FCPATH . 'assets/images/logo_dprd.jpg') : time();
?>
<aside id="sidebar" class="fixed inset-y-0 start-0 z-50 flex flex-col border-e border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 transition-all duration-200 -translate-x-full peer-checked:translate-x-0 lg:static lg:translate-x-0 peer-checked:lg:w-68 peer-not-checked:lg:w-20 w-72 max-w-[85vw] p-3 sm:p-4 shrink-0" aria-label="Navigasi utama">
    <div class="relative mb-4 flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
        <a href="<?= base_url('admin/dashboard') ?>" class="sidebar-brand-container flex items-center gap-3 rounded-xl px-1.5 py-1 min-w-0 transition hover:bg-slate-50 dark:hover:bg-slate-800">
            <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full ring-2 ring-emerald-500/40 bg-white shadow-xs p-0.5">
                <img src="<?= base_url('assets/images/logo_dprd.jpg?v=' . $logoVersion) ?>" alt="Logo DPRD" class="h-full w-full rounded-full object-contain" />
            </div>
            <span class="sidebar-brand-text min-w-0 leading-tight">
                <strong class="block truncate text-xs font-black uppercase text-slate-900 dark:text-white tracking-wide">DPRD Sulteng</strong>
                <span class="block truncate text-[11px] font-medium text-slate-400 dark:text-slate-500">Jadwal Rapat &amp; Signage</span>
            </span>
        </a>
        <button class="sidebar-toggle hidden lg:inline-flex" type="button" id="sidebarToggle"
                aria-label="Ciutkan sidebar" aria-expanded="true" title="Ciutkan sidebar">
            <i data-lucide="chevron-left" class="size-4"></i>
        </button>
    </div>

    <ul class="flex flex-col gap-1 flex-1 overflow-y-auto min-h-0 p-0" data-admin-menu>
        <li>
            <a href="<?= base_url('admin/dashboard') ?>" data-path="/admin/dashboard" data-admin-nav class="sidebar-item-link flex items-center gap-x-3 py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition group">
                <i data-lucide="layout-dashboard" class="size-4.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                <span class="sidebar-label truncate">Dashboard</span>
            </a>
        </li>

        <li>
            <details data-admin-nav-group class="group">
                <summary class="sidebar-item-link flex items-center justify-between py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition cursor-pointer list-none">
                    <span class="flex items-center gap-x-3 min-w-0">
                        <i data-lucide="database" class="size-4.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                        <span class="sidebar-label truncate">Master Data</span>
                    </span>
                    <svg class="size-3.5 text-slate-400 transition group-open:rotate-180 sidebar-label" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <ul class="mt-1 ps-7 pe-1 space-y-1 border-s border-slate-100 dark:border-slate-800 ms-5">
                    <li><a href="<?= base_url('admin/anggota') ?>" data-path="/admin/anggota" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Anggota DPRD</a></li>
                    <li><a href="<?= base_url('admin/unit-rapat') ?>" data-path="/admin/unit-rapat" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Kelompok Peserta</a></li>
                    <li><a href="<?= base_url('admin/ruangan') ?>" data-path="/admin/ruangan" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Ruangan Rapat</a></li>
                </ul>
            </details>
        </li>

        <li>
            <details data-admin-nav-group class="group">
                <summary class="sidebar-item-link flex items-center justify-between py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition cursor-pointer list-none">
                    <span class="flex items-center gap-x-3 min-w-0">
                        <i data-lucide="calendar-days" class="size-4.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                        <span class="sidebar-label truncate">Agenda</span>
                    </span>
                    <svg class="size-3.5 text-slate-400 transition group-open:rotate-180 sidebar-label" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <ul class="mt-1 ps-7 pe-1 space-y-1 border-s border-slate-100 dark:border-slate-800 ms-5">
                    <li><a href="<?= base_url('admin/jadwal-banmus') ?>" data-path="/admin/jadwal-banmus" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Agenda Banmus</a></li>
                    <li>
                        <a href="<?= base_url('admin/jadwal-umum') ?>" data-path="/admin/jadwal-umum" data-admin-nav class="flex items-center justify-between py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <span>Jadwal Umum</span>
                            <span class="inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 hidden" id="badge-jadwal"></span>
                        </a>
                    </li>
                    <li><a href="<?= base_url('admin/kalender') ?>" data-path="/admin/kalender" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Kalender Agenda</a></li>
                    <li><a href="<?= base_url('admin/notulen') ?>" data-path="/admin/notulen" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Notulensi &amp; Risalah AI</a></li>
                    <li><button type="button" class="w-full flex items-center justify-between py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-400 dark:text-slate-600 opacity-60 cursor-not-allowed text-left" disabled><span>Laporan Agenda</span> <span class="text-[9px] uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-500 px-1.5 py-0.5 rounded">segera</span></button></li>
                </ul>
            </details>
        </li>

        <li>
            <a href="<?= base_url('admin/pengaturan') ?>" data-path="/admin/pengaturan" data-admin-nav class="sidebar-item-link flex items-center gap-x-3 py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition group">
                <i data-lucide="settings" class="size-4.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                <span class="sidebar-label truncate">Pengaturan</span>
            </a>
        </li>

        <li>
            <details data-admin-nav-group class="group">
                <summary class="sidebar-item-link flex items-center justify-between py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition cursor-pointer list-none">
                    <span class="flex items-center gap-x-3 min-w-0">
                        <i data-lucide="monitor" class="size-4.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                        <span class="sidebar-label truncate">Tampilan Publik</span>
                    </span>
                    <svg class="size-3.5 text-slate-400 transition group-open:rotate-180 sidebar-label" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <ul class="mt-1 ps-7 pe-1 space-y-1 border-s border-slate-100 dark:border-slate-800 ms-5">
                    <li>
                        <a href="<?= base_url('signage') ?>" target="_blank" rel="noopener" class="flex items-center justify-between py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <span>Layar TV</span>
                            <i data-lucide="external-link" class="size-3.5 text-slate-400"></i>
                        </a>
                    </li>
                    <li>
                        <a href="<?= base_url('agenda') ?>" target="_blank" rel="noopener" class="flex items-center justify-between py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <span>Jadwal Publik</span>
                            <i data-lucide="external-link" class="size-3.5 text-slate-400"></i>
                        </a>
                    </li>
                </ul>
            </details>
        </li>

        <li class="mt-auto border-t border-slate-100 dark:border-slate-800 pt-3 lg:hidden">
            <a href="<?= base_url('admin/profile') ?>" data-path="/admin/profile" data-admin-nav class="flex items-center gap-x-3 py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                <i data-lucide="user-round" class="size-4.5 text-slate-500"></i>
                <span>Profil Admin</span>
            </a>
        </li>
        <li class="lg:hidden">
            <form id="sidebar-logout-form" method="post" action="<?= base_url('admin/logout') ?>" data-confirm-message="Yakin ingin keluar?">
                <?= csrf_field() ?>
            </form>
            <button type="submit" form="sidebar-logout-form" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition text-left">
                <i data-lucide="log-out" class="size-4.5"></i>
                <span>Keluar</span>
            </button>
        </li>
    </ul>
</aside>
