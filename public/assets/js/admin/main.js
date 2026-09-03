/* Admin shell — Vanilla JS, tanpa Vue. */
(function () {

    /* Icon renderer */
    function renderAdminIcons() {
        if (window.lucide) window.lucide.createIcons();
    }
    window.renderAdminIcons = renderAdminIcons;

    function isActivePath(current, link) {
        return current === link || current.startsWith(link + '/');
    }

    const ADMIN_THEME_STORAGE_KEY = 'dprd-admin-theme';
    const ADMIN_SIDEBAR_STORAGE_KEY = 'dprd-sidebar-collapsed';

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

    function closeTransientShellUi() {
        document.body.classList.remove('mobile-agenda-open');
        const sidebar = document.getElementById('application-sidebar');
        if (sidebar && window.HSOverlay && window.matchMedia('(max-width: 1023px)').matches) {
            window.HSOverlay.close(sidebar);
        }
    }

    function bindFormConfirmations() {
        if (document.documentElement.dataset.adminConfirmBound === '1') return;
        document.documentElement.dataset.adminConfirmBound = '1';

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form[data-confirm-message]');
            if (!form || window.confirm(form.dataset.confirmMessage || 'Lanjutkan tindakan ini?')) return;
            event.preventDefault();
        });
    }

    function bindAutoSubmitControls() {
        if (document.documentElement.dataset.adminAutoSubmitBound === '1') return;
        document.documentElement.dataset.adminAutoSubmitBound = '1';

        document.addEventListener('change', function (event) {
            const control = event.target.closest('[data-auto-submit]');
            if (control?.form) control.form.requestSubmit();
        });
    }

    /* Alert close handler */
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

    /* Sidebar */
    function initSidebar() {
        if (document.documentElement.dataset.adminSidebarBound === '1') return;
        document.documentElement.dataset.adminSidebarBound = '1';

        document.addEventListener('click', function (event) {
            if (event.target.closest('#application-sidebar a') && window.matchMedia('(max-width: 1023px)').matches) {
                const sidebar = document.getElementById('application-sidebar');
                if (sidebar && window.HSOverlay) {
                    window.HSOverlay.close(sidebar);
                }
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeTransientShellUi();
        });
    }

    /* Active navigation */
    function applyActiveNavigation() {
        const current = window.location.pathname.replace(/\/$/, '') || '/';
        document.querySelectorAll('[data-admin-nav][data-path]').forEach(function (link) {
            const path = (link.getAttribute('data-path') || '').replace(/\/$/, '');
            const active = path && (current === path || (path !== '/admin' && current.startsWith(path + '/')));
            link.classList.toggle('menu-active', Boolean(active));
            if (active) {
                link.setAttribute('aria-current', 'page');
                const group = link.closest('[data-admin-nav-group]');
                if (group) group.open = true;
            } else {
                link.removeAttribute('aria-current');
            }
        });
    }

    /* DataTables */

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

    /* DataTables column filters */
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
            var colIdx  = def.col !== undefined ? def.col : def.column;
            if (colIdx === undefined || colIdx === null) return;
            var allText = def.all  || ('Semua ' + (def.label || ''));
            if (!def.all && !def.label) allText = 'Semua';
            var label   = def.label || ('Kolom ' + colIdx);

            /* Kumpulkan nilai unik dari kolom tsb */
            var values = [];
            api.column(colIdx).nodes().each(function (cell) {
                /* Ambil teks bersih dari data-filter / data-search atau HTML (badge, span, dll) */
                var text = cell.getAttribute('data-filter') || cell.getAttribute('data-search');
                if (!text) {
                    var badge = cell.querySelector('.badge');
                    if (badge) {
                        text = (badge.textContent || badge.innerText || '').trim();
                    } else {
                        text = (cell.textContent || cell.innerText || '').trim();
                    }
                }
                text = (text || '').trim();
                if (text && values.indexOf(text) === -1) values.push(text);
            });
            values.sort();

            if (values.length === 0) return;

            /* Bungkus label + select */
            var wrap = document.createElement('div');
            wrap.className = 'flex items-center gap-1.5';

            var lbl = document.createElement('span');
            lbl.className = 'text-xs font-bold text-base-content/50 whitespace-nowrap';
            lbl.textContent = label + ':';

            var sel = document.createElement('select');
            sel.className = 'select select-sm dt-col-filter-select';
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

        if (bar.children.length > 0) {
            toolbar.appendChild(bar);
        }
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

                var urlParams = new URLSearchParams(window.location.search);
                var initialSearch = urlParams.get('q') || '';

                window.jQuery(table).DataTable({
                    autoWidth: false,
                    pageLength: pageLength,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
                    order: parseDataTableOrder(table),
                    search: {
                        search: initialSearch,
                    },
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

    function initPreline() {
        if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
            window.HSStaticMethods.autoInit();
        }
    }

    function refreshAdminPage() {
        initThemeControls();
        initSidebar();
        applyActiveNavigation();
        renderAdminIcons();
        initAutoDismissAlerts();
        initAdminDataTables();
        initPreline();
        renderAdminIcons();
    }

    bindAlertHandlers();
    bindFormConfirmations();
    bindAutoSubmitControls();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', refreshAdminPage);
    } else {
        refreshAdminPage();
    }
    window.addEventListener('load', initPreline);

})();
