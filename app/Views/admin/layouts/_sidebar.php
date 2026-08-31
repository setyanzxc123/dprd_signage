<aside id="sidebar" class="relative flex min-h-full flex-col border-r border-base-300 bg-base-100 p-4 is-drawer-close:w-20 is-drawer-open:w-72" aria-label="Navigasi utama">
    <div class="relative mb-4">
        <a href="<?= base_url('admin/dashboard') ?>" class="flex items-center gap-3 rounded-box px-2 py-2 is-drawer-close:justify-center is-drawer-close:px-0">
            <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>" alt="Logo DPRD" class="h-10 w-10 rounded-box object-contain" />
            <span class="min-w-0 is-drawer-close:hidden">
                <span class="block truncate text-sm font-black">DPRD Sulawesi Tengah</span>
                <span class="block truncate text-xs text-base-content/60">Jadwal Rapat & Signage</span>
            </span>
        </a>
        <button class="sidebar-toggle hidden lg:grid" type="button" id="sidebarToggle"
                aria-label="Ciutkan sidebar" aria-expanded="true" title="Ciutkan sidebar">
            <i data-lucide="chevron-left"></i>
        </button>
    </div>

    <ul class="menu menu-md min-h-0 w-full flex-1 flex-nowrap gap-1 overflow-y-auto p-0" data-admin-menu>
        <li>
            <a href="<?= base_url('admin/dashboard') ?>" data-path="/admin/dashboard" data-admin-nav class="is-drawer-close:tooltip is-drawer-close:tooltip-right is-drawer-close:justify-center" data-tip="Dashboard">
                <i data-lucide="layout-dashboard"></i>
                <span class="is-drawer-close:hidden">Dashboard</span>
            </a>
        </li>

        <li>
            <details data-admin-nav-group class="is-drawer-close:[&>ul]:hidden">
                <summary class="is-drawer-close:tooltip is-drawer-close:tooltip-right is-drawer-close:justify-center is-drawer-close:[&::after]:hidden" data-tip="Master Data">
                    <i data-lucide="database"></i>
                    <span class="is-drawer-close:hidden">Master Data</span>
                </summary>
                <ul>
                    <li><a href="<?= base_url('admin/anggota') ?>" data-path="/admin/anggota" data-admin-nav>Anggota DPRD</a></li>
                    <li><a href="<?= base_url('admin/unit-rapat') ?>" data-path="/admin/unit-rapat" data-admin-nav>Kelompok Peserta</a></li>
                    <li><a href="<?= base_url('admin/ruangan') ?>" data-path="/admin/ruangan" data-admin-nav>Ruangan Rapat</a></li>
                </ul>
            </details>
        </li>

        <li>
            <details data-admin-nav-group class="is-drawer-close:[&>ul]:hidden">
                <summary class="is-drawer-close:tooltip is-drawer-close:tooltip-right is-drawer-close:justify-center is-drawer-close:[&::after]:hidden" data-tip="Agenda">
                    <i data-lucide="calendar-days"></i>
                    <span class="is-drawer-close:hidden">Agenda</span>
                </summary>
                <ul>
                    <li><a href="<?= base_url('admin/jadwal-banmus') ?>" data-path="/admin/jadwal-banmus" data-admin-nav>Agenda Banmus</a></li>
                    <li>
                        <a href="<?= base_url('admin/jadwal-umum') ?>" data-path="/admin/jadwal-umum" data-admin-nav>
                            Jadwal Umum
                            <span class="badge badge-primary badge-sm ml-auto hidden" id="badge-jadwal"></span>
                        </a>
                    </li>
                    <li><a href="<?= base_url('admin/kalender') ?>" data-path="/admin/kalender" data-admin-nav>Kalender Agenda</a></li>
                    <li><a href="<?= base_url('admin/notulen') ?>" data-path="/admin/notulen" data-admin-nav>Notulensi & Risalah AI</a></li>
                    <li><button type="button" class="menu-disabled" disabled>Laporan Agenda <span class="badge badge-ghost badge-xs ml-auto">segera</span></button></li>
                </ul>
            </details>
        </li>

        <li>
            <a href="<?= base_url('admin/pengaturan') ?>" data-path="/admin/pengaturan" data-admin-nav class="is-drawer-close:tooltip is-drawer-close:tooltip-right is-drawer-close:justify-center" data-tip="Pengaturan">
                <i data-lucide="settings"></i>
                <span class="is-drawer-close:hidden">Pengaturan</span>
            </a>
        </li>

        <li>
            <details data-admin-nav-group class="is-drawer-close:[&>ul]:hidden">
                <summary class="is-drawer-close:tooltip is-drawer-close:tooltip-right is-drawer-close:justify-center is-drawer-close:[&::after]:hidden" data-tip="Tampilan Publik">
                    <i data-lucide="monitor"></i>
                    <span class="is-drawer-close:hidden">Tampilan Publik</span>
                </summary>
                <ul>
                    <li>
                        <a href="<?= base_url('signage') ?>" target="_blank" rel="noopener">
                            Layar TV
                            <i data-lucide="external-link" class="ml-auto h-3.5 w-3.5"></i>
                        </a>
                    </li>
                    <li>
                        <a href="<?= base_url('agenda') ?>" target="_blank" rel="noopener">
                            Jadwal Publik
                            <i data-lucide="external-link" class="ml-auto h-3.5 w-3.5"></i>
                        </a>
                    </li>
                </ul>
            </details>
        </li>

        <li class="mt-auto border-t border-base-300 pt-2 lg:hidden">
            <a href="<?= base_url('admin/profile') ?>" data-path="/admin/profile" data-admin-nav>
                <i data-lucide="user-round"></i>
                <span>Profil Admin</span>
            </a>
        </li>
        <li class="lg:hidden">
            <form id="sidebar-logout-form" method="post" action="<?= base_url('admin/logout') ?>" data-confirm-message="Yakin ingin keluar?">
                <?= csrf_field() ?>
            </form>
            <button type="submit" form="sidebar-logout-form" class="text-error">
                <i data-lucide="log-out"></i>
                <span>Keluar</span>
            </button>
        </li>
    </ul>
</aside>
