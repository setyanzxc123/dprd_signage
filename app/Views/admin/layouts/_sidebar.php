<?php
$logoVersion = is_file(FCPATH . 'assets/images/logo_dprd.jpg') ? filemtime(FCPATH . 'assets/images/logo_dprd.jpg') : time();
?>
<aside id="application-sidebar" class="hs-overlay [--auto-close:lg] hs-overlay-open:translate-x-0 -translate-x-full transition-all duration-300 transform hidden fixed top-0 start-0 bottom-0 z-[60] w-64 bg-white border-e border-slate-200/80 overflow-y-auto lg:block lg:translate-x-0 lg:end-auto lg:bottom-0 dark:bg-slate-900 dark:border-slate-800" tabindex="-1" aria-label="Navigasi Utama">
    <div class="relative flex flex-col h-full max-h-full">
        <div class="px-5 pt-4 pb-3 flex items-center justify-between border-b border-slate-100 dark:border-slate-800">
            <a href="<?= base_url('admin/dashboard') ?>" class="flex items-center gap-3 min-w-0" aria-label="Dashboard Admin">
                <div class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full ring-2 ring-emerald-500/40 bg-white shadow-xs p-0.5">
                    <img src="<?= base_url('assets/images/logo_dprd.jpg?v=' . $logoVersion) ?>" alt="Logo DPRD" class="h-full w-full rounded-full object-contain" />
                </div>
                <div class="min-w-0 leading-tight">
                    <strong class="block truncate text-xs font-black uppercase text-slate-900 dark:text-white tracking-wide">DPRD Sulteng</strong>
                    <span class="block truncate text-[11px] font-medium text-slate-400 dark:text-slate-500">Panel Administrasi</span>
                </div>
            </a>
            <button type="button" class="lg:hidden size-8 inline-flex justify-center items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 transition" data-hs-overlay="#application-sidebar" aria-label="Tutup sidebar">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <nav class="h-full overflow-y-auto p-3 flex flex-col gap-y-1" data-admin-menu>
            <div>
                <a href="<?= base_url('admin/dashboard') ?>" data-path="/admin/dashboard" data-admin-nav class="sidebar-item-link flex items-center gap-x-3 py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition group">
                    <i data-lucide="layout-dashboard" class="size-4.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                    <span class="truncate">Dashboard</span>
                </a>
            </div>

            <div>
                <details data-admin-nav-group class="group">
                    <summary class="sidebar-item-link flex items-center justify-between py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition cursor-pointer list-none">
                        <span class="flex items-center gap-x-3 min-w-0">
                            <i data-lucide="database" class="size-4.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                            <span class="truncate">Master Data</span>
                        </span>
                        <svg class="size-3.5 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </summary>
                    <div class="mt-1 ps-7 pe-1 space-y-1 border-s border-slate-100 dark:border-slate-800 ms-5">
                        <a href="<?= base_url('admin/anggota') ?>" data-path="/admin/anggota" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Anggota DPRD</a>
                        <a href="<?= base_url('admin/unit-rapat') ?>" data-path="/admin/unit-rapat" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Kelompok Peserta</a>
                        <a href="<?= base_url('admin/ruangan') ?>" data-path="/admin/ruangan" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Ruangan Rapat</a>
                    </div>
                </details>
            </div>

            <div>
                <details data-admin-nav-group class="group" open>
                    <summary class="sidebar-item-link flex items-center justify-between py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition cursor-pointer list-none">
                        <span class="flex items-center gap-x-3 min-w-0">
                            <i data-lucide="calendar-days" class="size-4.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                            <span class="truncate">Agenda</span>
                        </span>
                        <svg class="size-3.5 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </summary>
                    <div class="mt-1 ps-7 pe-1 space-y-1 border-s border-slate-100 dark:border-slate-800 ms-5">
                        <a href="<?= base_url('admin/jadwal-banmus') ?>" data-path="/admin/jadwal-banmus" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Agenda Banmus</a>
                        <a href="<?= base_url('admin/jadwal-umum') ?>" data-path="/admin/jadwal-umum" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Jadwal Umum</a>
                        <a href="<?= base_url('admin/kalender') ?>" data-path="/admin/kalender" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Kalender Agenda</a>
                        <a href="<?= base_url('admin/notulen') ?>" data-path="/admin/notulen" data-admin-nav class="block py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Notulensi &amp; Risalah AI</a>
                    </div>
                </details>
            </div>

            <div>
                <a href="<?= base_url('admin/pengaturan') ?>" data-path="/admin/pengaturan" data-admin-nav class="sidebar-item-link flex items-center gap-x-3 py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition group">
                    <i data-lucide="settings" class="size-4.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                    <span class="truncate">Pengaturan</span>
                </a>
            </div>

            <div>
                <details data-admin-nav-group class="group">
                    <summary class="sidebar-item-link flex items-center justify-between py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition cursor-pointer list-none">
                        <span class="flex items-center gap-x-3 min-w-0">
                            <i data-lucide="monitor" class="size-4.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                            <span class="truncate">Tampilan Publik</span>
                        </span>
                        <svg class="size-3.5 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </summary>
                    <div class="mt-1 ps-7 pe-1 space-y-1 border-s border-slate-100 dark:border-slate-800 ms-5">
                        <a href="<?= base_url('signage') ?>" target="_blank" rel="noopener" class="flex items-center justify-between py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <span>Layar TV</span>
                            <i data-lucide="external-link" class="size-3.5 text-slate-400"></i>
                        </a>
                        <a href="<?= base_url('agenda') ?>" target="_blank" rel="noopener" class="flex items-center justify-between py-1.5 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <span>Jadwal Publik</span>
                            <i data-lucide="external-link" class="size-3.5 text-slate-400"></i>
                        </a>
                    </div>
                </details>
            </div>

            <div class="mt-auto border-t border-slate-100 dark:border-slate-800 pt-3 lg:hidden">
                <a href="<?= base_url('admin/profile') ?>" data-path="/admin/profile" data-admin-nav class="flex items-center gap-x-3 py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <i data-lucide="user-round" class="size-4.5 text-slate-500"></i>
                    <span>Profil Admin</span>
                </a>
                <form id="sidebar-logout-form" method="post" action="<?= base_url('admin/logout') ?>" data-confirm-message="Yakin ingin keluar?">
                    <?= csrf_field() ?>
                </form>
                <button type="submit" form="sidebar-logout-form" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition text-left mt-1">
                    <i data-lucide="log-out" class="size-4.5"></i>
                    <span>Keluar</span>
                </button>
            </div>
        </nav>
    </div>
</aside>
