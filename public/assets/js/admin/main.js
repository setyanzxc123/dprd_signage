/* Admin shell — Vanilla JS, tanpa Vue. */
(function () {

    /* ── Icon renderer ─────────────────────────────────────────────────── */
    function renderAdminIcons() {
        if (window.lucide) window.lucide.createIcons();
    }
    window.renderAdminIcons = renderAdminIcons;

    /* ── Format helpers ─────────────────────────────────────────────────── */
    function formatClock(date) {
        return new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit', minute: '2-digit', second: '2-digit',
            hour12: false, timeZone: 'Asia/Makassar'
        }).format(date) + ' WITA';
    }

    function formatPageDate(date) {
        return new Intl.DateTimeFormat('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long',
            year: 'numeric', timeZone: 'Asia/Makassar'
        }).format(date);
    }

    function isActivePath(current, link) {
        return current === link || current.startsWith(link + '/');
    }

    const ADMIN_THEME_STORAGE_KEY = 'dprd-admin-theme';

    function getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function syncThemeControls(theme) {
        const isDark = theme === 'dark';
        const label = isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap';
        document.querySelectorAll('[data-theme-toggle-input]').forEach(function (input) {
            input.checked = isDark;
        });
        document.querySelectorAll('[data-theme-toggle]').forEach(function (toggle) {
            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);
        });
    }

    function applyAdminTheme(theme, persist) {
        const nextTheme = theme === 'dark' ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark', nextTheme === 'dark');
        document.documentElement.setAttribute('data-theme', nextTheme);
        if (persist) {
            localStorage.setItem(ADMIN_THEME_STORAGE_KEY, nextTheme);
        }
        syncThemeControls(nextTheme);
    }

    function initThemeControls() {
        syncThemeControls(getCurrentTheme());

        if (document.documentElement.dataset.adminThemeBound === '1') return;
        document.documentElement.dataset.adminThemeBound = '1';

        document.addEventListener('change', function (event) {
            const input = event.target.closest('[data-theme-toggle-input]');
            if (!input) return;
            applyAdminTheme(input.checked ? 'dark' : 'light', true);
        });
    }

    /* patchReadyCallbacksForTurbo dihapus — monkey-patch document.addEventListener
     * sangat berbahaya dan menyebabkan konflik dengan jQuery/DataTables.
     * Turbo sudah firing turbo:load setelah setiap navigasi, yang kita gunakan
     * di bawah (lihat bagian Bootstrap). */

    function closeTransientShellUi() {
        document.body.classList.remove('mobile-agenda-open');
        setMobileSidebarOpen(false);
    }

    function disableTurboFormSubmissions() {
        document.querySelectorAll('form:not([data-turbo])').forEach(function (form) {
            form.setAttribute('data-turbo', 'false');
        });
    }

    /* ── Alert close handler ────────────────────────────────────────────── */
    function dismissAdminAlert(alert) {
        if (!alert || alert.dataset.dismissed === '1') return;
        alert.dataset.dismissed = '1';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-6px)';
        window.setTimeout(function () {
            alert.remove();
        }, 180);
    }

    function initAutoDismissAlerts() {
        document.querySelectorAll('[data-admin-alert]').forEach(function (alert) {
            if (alert.dataset.autoDismissBound === '1') return;
            alert.dataset.autoDismissBound = '1';
            alert.style.transition = 'opacity 180ms ease, transform 180ms ease';
            alert.style.willChange = 'opacity, transform';

            var delay = parseInt(alert.getAttribute('data-auto-dismiss-ms') || '4000', 10);
            if (!Number.isFinite(delay) || delay <= 0) return;

            window.setTimeout(function () {
                dismissAdminAlert(alert);
            }, delay);
        });
    }

    function bindAlertHandlers() {
        if (document.documentElement.dataset.adminAlertBound === '1') return;
        document.documentElement.dataset.adminAlertBound = '1';
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.alert-close-btn, .ta-alert-close');
            if (btn) dismissAdminAlert(btn.closest('.alert, .ta-alert'));
        });
    }

    /* ── Sidebar ────────────────────────────────────────────────────────── */
    function setSidebarToggleLabel(collapsed) {
        const btn = document.getElementById('sidebarToggle');
        if (!btn) return;
        btn.setAttribute('aria-expanded', String(!collapsed));
        btn.setAttribute('aria-label', collapsed ? 'Tampilkan sidebar' : 'Sembunyikan sidebar');
        btn.setAttribute('title',       collapsed ? 'Tampilkan sidebar' : 'Sembunyikan sidebar');
    }

    function isMobilePrimaryPath(current, path) {
        if (path === '/admin/dashboard') {
            return current === '/admin' || isActivePath(current, path);
        }

        return isActivePath(current, path);
    }

    function isCurrentMobileMenuSection() {
        const current = window.location.pathname.replace(/\/$/, '') || '/';
        if (!current.startsWith('/admin')) return false;

        return ![
            '/admin/dashboard',
            '/admin/jadwal',
            '/admin/notifikasi',
        ].some(function (path) {
            return isMobilePrimaryPath(current, path);
        });
    }

    function syncMobileMenuButton(sidebarOpen) {
        const shouldActivate = Boolean(sidebarOpen) || isCurrentMobileMenuSection();
        document.querySelectorAll('[data-mobile-menu-toggle]').forEach(function (button) {
            button.classList.toggle('active', shouldActivate);
            button.setAttribute('aria-expanded', String(Boolean(sidebarOpen)));
            button.setAttribute('aria-label', sidebarOpen ? 'Tutup menu lainnya' : 'Buka menu lainnya');
            button.setAttribute('title', sidebarOpen ? 'Tutup menu lainnya' : 'Buka menu lainnya');
        });
    }

    function setMobileSidebarOpen(open) {
        const isOpen = Boolean(open);
        document.body.classList.toggle('mobile-sidebar-open', isOpen);
        document.getElementById('sidebar')?.classList.toggle('mobile-open', isOpen);
        document.getElementById('sidebar-overlay')?.classList.toggle('visible', isOpen);
        document.querySelectorAll('.topbar-toggle').forEach(function (button) {
            button.setAttribute('aria-expanded', String(isOpen));
            button.setAttribute('aria-label', isOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi');
            button.setAttribute('title', isOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        });
        syncMobileMenuButton(isOpen);
    }

    function initSidebar() {
        const collapsed = localStorage.getItem('dprd-sidebar-collapsed') === 'collapsed';
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        setSidebarToggleLabel(collapsed);
        syncMobileMenuButton(document.getElementById('sidebar')?.classList.contains('mobile-open'));

        if (document.documentElement.dataset.adminSidebarBound === '1') return;
        document.documentElement.dataset.adminSidebarBound = '1';

        document.addEventListener('click', function (e) {
            const sidebar = document.getElementById('sidebar');

            if (e.target.closest('.topbar-toggle, [data-mobile-menu-toggle]')) {
                setMobileSidebarOpen(!sidebar?.classList.contains('mobile-open'));
                return;
            }

            if (e.target.closest('#sidebarToggle')) {
                const nowCollapsed = !document.documentElement.classList.contains('sidebar-collapsed');
                document.documentElement.classList.toggle('sidebar-collapsed', nowCollapsed);
                localStorage.setItem('dprd-sidebar-collapsed', nowCollapsed ? 'collapsed' : 'expanded');
                setSidebarToggleLabel(nowCollapsed);
                return;
            }

            if (e.target.closest('#sidebar-overlay')) {
                closeTransientShellUi();
            }

            if (e.target.closest('#sidebar a') && window.matchMedia('(max-width: 1180px)').matches) {
                setMobileSidebarOpen(false);
            }
        });

        // ESC → close mobile sidebar
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeTransientShellUi();
            }
        });
    }

    /* ── Active navigation ──────────────────────────────────────────────── */
    function applyActiveNavigation() {
        const current = window.location.pathname.replace(/\/$/, '') || '/';
        document.querySelectorAll('.nav-link-custom[data-path], .mobile-nav a[data-path]').forEach(function (link) {
            const path = link.getAttribute('data-path');
            const active = isMobilePrimaryPath(current, path);
            link.classList.toggle('active', active);
            link.classList.toggle('menu-active', active && link.classList.contains('nav-link-custom'));
        });
        syncMobileMenuButton(document.getElementById('sidebar')?.classList.contains('mobile-open'));
    }

    /* ── Clock ──────────────────────────────────────────────────────────── */
    function startClock() {
        function tick() {
            const now = new Date();
            const clock = document.getElementById('topbar-clock');
            if (clock) clock.textContent = formatClock(now);
            const pageDate = document.getElementById('page-date');
            if (pageDate) pageDate.textContent = formatPageDate(now);
        }
        tick();
        setInterval(tick, 1000);
    }

    /* ── WA status (topbar) ─────────────────────────────────────────────── */
    async function checkTopbarWaStatus() {
        const statusEl = document.getElementById('topbar-wa-status');
        const badgeEl  = document.getElementById('topbar-wa-badge');
        const dotEl    = document.getElementById('topbar-wa-dot');
        const textEl   = document.getElementById('topbar-wa-text');
        if (!statusEl || !badgeEl || !dotEl || !textEl) return;

        const CACHE_KEY  = 'dprd_wa_status_cache';
        const CACHE_TIME = 'dprd_wa_status_cache_time';
        const TTL        = 5 * 60 * 1000; // 5 menit
        const now        = Date.now();

        function applyUi(data) {
            ['style'].forEach(function (p) { badgeEl.removeAttribute(p); dotEl.removeAttribute(p); });
            if (data.configured && data.connected) {
                badgeEl.className = 'badge badge-success badge-soft gap-1.5 h-8 px-3';
                dotEl.className   = 'h-1.5 w-1.5 rounded-full bg-success';
                textEl.textContent = 'WhatsApp: Siap';
                statusEl.setAttribute('title', 'WhatsApp Terhubung (Siap)');
            } else if (data.configured && !data.connected) {
                badgeEl.className = 'badge badge-error badge-soft gap-1.5 h-8 px-3';
                dotEl.className   = 'h-1.5 w-1.5 rounded-full bg-error';
                textEl.textContent = 'WhatsApp: Error';
                statusEl.setAttribute('title', 'WhatsApp Terputus: ' + (data.error || 'Gagal terhubung'));
            } else {
                badgeEl.className = 'badge badge-neutral badge-soft gap-1.5 h-8 px-3';
                dotEl.className   = 'h-1.5 w-1.5 rounded-full bg-neutral-content';
                textEl.textContent = 'WhatsApp: Belum Aktif';
                statusEl.setAttribute('title', 'WhatsApp Belum Dikonfigurasi');
            }
        }

        // Pakai cache jika masih valid
        const cached = localStorage.getItem(CACHE_KEY);
        const cachedAt = parseInt(localStorage.getItem(CACHE_TIME) || '0', 10);
        if (cached && (now - cachedAt < TTL)) {
            try { applyUi(JSON.parse(cached)); return; } catch (_) {}
        }

        // Loading state
        badgeEl.className  = 'badge badge-neutral badge-soft gap-1.5 h-8 px-3';
        dotEl.className    = 'h-1.5 w-1.5 rounded-full bg-neutral-content animate-pulse';
        textEl.textContent = 'WhatsApp: Memeriksa...';

        try {
            const resp = await fetch('/admin/pengaturan/wa-status', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await resp.json();
            localStorage.setItem(CACHE_KEY, JSON.stringify(data));
            localStorage.setItem(CACHE_TIME, now.toString());
            applyUi(data);
        } catch (_) {
            badgeEl.className  = 'badge badge-warning badge-soft gap-1.5 h-8 px-3';
            dotEl.className    = 'h-1.5 w-1.5 rounded-full bg-warning';
            textEl.textContent = 'WhatsApp: Gagal Pengecekan';
            statusEl.setAttribute('title', 'Gagal memeriksa status WhatsApp');
        }
    }

    /* ── DataTables ─────────────────────────────────────────────────────── */

    function parseDataTableOrder(table) {
        const raw = table.getAttribute('data-dt-order');
        if (!raw) return [];
        try {
            return JSON.parse(raw);
        } catch (_) {
            return [];
        }
    }

    function styleDataTableControls(wrapper) {
        if (!wrapper) return;

        wrapper.querySelectorAll('.dt-paging .dt-paging-button').forEach(function (btn) {
            if (!btn.classList.contains('btn')) {
                btn.classList.add('btn', 'btn-sm');
            }
            btn.classList.toggle('btn-active', btn.classList.contains('current'));
            btn.classList.toggle('btn-disabled', btn.disabled || btn.classList.contains('disabled'));
        });
    }

    function updateDataTableRowNumbers(api) {
        var info = api.page.info();
        api.column('.dt-row-number', { page: 'current' }).nodes().each(function (cell, index) {
            cell.textContent = info.start + index + 1;
        });
    }

    /* ── DataTables column filters (client-side) ────────────────────────── */
    /*
     * Membaca atribut data-dt-col-filters pada <table> (JSON array):
     *   [{"col": <index>, "label": "Label", "all": "Semua ..."}]
     * Lalu meng-inject dropdown DaisyUI ke area toolbar DataTables
     * sehingga admin bisa filter Jenis / Status tanpa reload halaman.
     */
    function buildDtColumnFilters(table, api) {
        var raw = table.getAttribute('data-dt-col-filters');
        if (!raw) return;

        var defs;
        try { defs = JSON.parse(raw); } catch (_) { return; }
        if (!Array.isArray(defs) || defs.length === 0) return;

        /* Cari wrapper DT dan tempatkan di baris toolbar (dt-layout-start) */
        var wrapper = api.table().container();
        var toolbar  = wrapper.querySelector('.dt-layout-start');
        if (!toolbar) return;

        /* Jangan inject dua kali (misal saat DT sudah init) */
        if (wrapper.querySelector('.dt-col-filter-bar')) return;

        var bar = document.createElement('div');
        bar.className = 'dt-col-filter-bar flex flex-wrap gap-2 items-center mt-2 mb-1';

        defs.forEach(function (def) {
            var colIdx  = def.col;
            var allText = def.all  || 'Semua';
            var label   = def.label || ('Kolom ' + colIdx);

            /* Kumpulkan nilai unik dari kolom tsb */
            var values = [];
            api.column(colIdx).data().each(function (cell) {
                /* Ambil teks bersih dari HTML (badge, span, dll) */
                var tmp = document.createElement('div');
                tmp.innerHTML = cell;
                var text = (tmp.textContent || tmp.innerText || '').trim();
                if (text && values.indexOf(text) === -1) values.push(text);
            });
            values.sort();

            /* Bungkus label + select */
            var wrap = document.createElement('div');
            wrap.className = 'flex items-center gap-1.5';

            var lbl = document.createElement('span');
            lbl.className = 'text-xs font-bold text-base-content/50 whitespace-nowrap';
            lbl.textContent = label + ':';

            var sel = document.createElement('select');
            sel.className = 'select select-sm select-bordered dt-col-filter-select';
            sel.setAttribute('data-dt-filter-col', colIdx);
            sel.setAttribute('aria-label', 'Filter ' + label);

            /* Opsi "Semua" */
            var optAll = document.createElement('option');
            optAll.value = '';
            optAll.textContent = allText;
            sel.appendChild(optAll);

            values.forEach(function (v) {
                var opt = document.createElement('option');
                opt.value = v;
                opt.textContent = v;
                sel.appendChild(opt);
            });

            sel.addEventListener('change', function () {
                /* DataTables search pada kolom — exact match (regex escape) */
                var term = this.value
                    ? '^' + this.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '$'
                    : '';
                api.column(colIdx).search(term, /* regex */ true, /* smart */ false).draw();
            });

            wrap.appendChild(lbl);
            wrap.appendChild(sel);
            bar.appendChild(wrap);
        });

        toolbar.appendChild(bar);
    }

    function initAdminDataTables() {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) return;

        document.querySelectorAll('table[data-admin-datatable]').forEach(function (table) {
            if (window.jQuery.fn.DataTable.isDataTable(table)) {
                var existingApi = window.jQuery(table).DataTable();
                updateDataTableRowNumbers(existingApi);
                var wrapper = existingApi.table().container();
                styleDataTableControls(wrapper);
                renderAdminIcons();
                return;
            }

            try {
                var pageLength = parseInt(table.getAttribute('data-dt-page-length') || '10', 10);
                if (!Number.isFinite(pageLength) || pageLength <= 0) pageLength = 10;

                window.jQuery(table).DataTable({
                    autoWidth: false,
                    pageLength: pageLength,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
                    order: parseDataTableOrder(table),
                    columnDefs: [
                        { targets: '.no-sort', orderable: false, searchable: false },
                    ],
                    language: {
                        emptyTable:     'Tidak ada data.',
                        info:           'Menampilkan _START_\u2013_END_ dari _TOTAL_ data',
                        infoEmpty:      'Menampilkan 0 data',
                        infoFiltered:   '(difilter dari _MAX_ total data)',
                        lengthMenu:     'Tampilkan _MENU_',
                        loadingRecords: 'Memuat\u2026',
                        processing:     'Memproses\u2026',
                        search:         'Cari:',
                        zeroRecords:    'Tidak ada data yang cocok.',
                        paginate: {
                            first:    'Awal',
                            previous: 'Sebelumnya',
                            next:     'Berikutnya',
                            last:     'Akhir',
                        },
                        aria: {
                            orderable:        'Urutkan kolom ini',
                            orderableReverse: 'Balik urutan kolom ini',
                        },
                    },
                    drawCallback: function () {
                        var api = this.api();
                        updateDataTableRowNumbers(api);
                        var wrapper = api.table().container();
                        styleDataTableControls(wrapper);
                        renderAdminIcons();
                    },
                    initComplete: function () {
                        var api = this.api();
                        updateDataTableRowNumbers(api);
                        var wrapper = api.table().container();
                        styleDataTableControls(wrapper);
                        buildDtColumnFilters(table, api);
                        renderAdminIcons();
                    },
                });

            } catch (error) {
                console.error('Gagal menginisialisasi DataTables admin:', error);
                renderAdminIcons();
            }
        });
    }

    function destroyAdminDataTables() {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) return;

        document.querySelectorAll('table[data-admin-datatable]').forEach(function (table) {
            if (window.jQuery.fn.DataTable.isDataTable(table)) {
                try {
                    /* destroy(false) = lepas DataTables dari tabel dan bersihkan
                     * event handler-nya, tapi TIDAK menghapus node <table> dari DOM.
                     * Ini penting untuk Turbo: snapshot cache akan menyimpan
                     * <table> asli, dan saat restore turbo:load akan re-init DT. */
                    window.jQuery(table).DataTable().destroy(false);
                } catch (e) {
                    /* abaikan error saat destroy */
                }
            }
        });
    }

    function refreshAdminPage() {
        initThemeControls();
        initSidebar();
        applyActiveNavigation();
        renderAdminIcons();
        disableTurboFormSubmissions();
        initAutoDismissAlerts();
        initAdminDataTables();
        renderAdminIcons(); /* re-render ikon setelah DT menambah elemen baru */
    }

    /* ── Bootstrap ──────────────────────────────────────────────────────── */
    /* Script dimuat dengan defer; DOM awal sudah siap saat bootstrap berjalan.
     *
     * Alur Hotwire Turbo:
     *  - turbo:load         → halaman baru selesai di-render (navigasi penuh / restore)
     *  - turbo:before-cache → sebelum halaman saat ini di-cache Turbo
     *
     * Kita destroy DataTables SEBELUM halaman di-cache sehingga Turbo menyimpan
     * markup tabel yang bersih (tanpa wrapper DT). Saat halaman di-restore,
     * turbo:load akan re-init DataTables kembali.
     *
     * CATATAN: turbo:render TIDAK digunakan karena ia firing baik saat fresh
     * navigation MAUPUN saat restore cache (setelah turbo:load sudah firing),
     * sehingga menyebabkan double-init yang bisa merusak DataTables.
     * turbo:load sudah cukup — ia meng-cover semua skenario.
     */
    bindAlertHandlers();
    refreshAdminPage();
    startClock();
    checkTopbarWaStatus();

    document.addEventListener('turbo:load', function () {
        refreshAdminPage();
        checkTopbarWaStatus();
    });

    document.addEventListener('turbo:before-cache', function () {
        /* destroy(false) = lepas DataTables dari tabel tapi TIDAK hapus <table> dari DOM.
         * Turbo menyimpan snapshot dengan <table> yang bersih (tanpa wrapper DT).
         * Saat halaman di-restore, turbo:load akan re-init DataTables kembali. */
        destroyAdminDataTables();
        closeTransientShellUi();
        document.querySelectorAll('[data-admin-alert]').forEach(function (alert) {
            alert.remove();
        });
    });

})();
