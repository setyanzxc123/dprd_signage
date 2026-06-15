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

    function patchReadyCallbacksForTurbo() {
        if (document.documentElement.dataset.adminReadyPatchBound === '1') return;
        document.documentElement.dataset.adminReadyPatchBound = '1';

        const originalAddEventListener = document.addEventListener.bind(document);
        document.addEventListener = function (type, listener, options) {
            if (type === 'DOMContentLoaded' && document.readyState !== 'loading') {
                window.setTimeout(function () {
                    const event = new Event('DOMContentLoaded');
                    if (typeof listener === 'function') {
                        listener.call(document, event);
                    } else if (listener && typeof listener.handleEvent === 'function') {
                        listener.handleEvent(event);
                    }
                }, 0);
                return;
            }

            return originalAddEventListener(type, listener, options);
        };
    }

    function closeTransientShellUi() {
        document.body.classList.remove('mobile-agenda-open');
        document.getElementById('sidebar')?.classList.remove('mobile-open');
        document.getElementById('sidebar-overlay')?.classList.remove('visible');
    }

    function disableTurboFormSubmissions() {
        document.querySelectorAll('form:not([data-turbo])').forEach(function (form) {
            form.setAttribute('data-turbo', 'false');
        });
    }



    /* ── Alert close handler ────────────────────────────────────────────── */
    function bindAlertHandlers() {
        if (document.documentElement.dataset.adminAlertBound === '1') return;
        document.documentElement.dataset.adminAlertBound = '1';
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.alert-close-btn, .ta-alert-close');
            if (btn) btn.closest('.alert, .ta-alert')?.remove();
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

    function initSidebar() {
        const collapsed = localStorage.getItem('dprd-sidebar-collapsed') === 'collapsed';
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        setSidebarToggleLabel(collapsed);

        if (document.documentElement.dataset.adminSidebarBound === '1') return;
        document.documentElement.dataset.adminSidebarBound = '1';

        document.addEventListener('click', function (e) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (e.target.closest('.topbar-toggle')) {
                sidebar?.classList.toggle('mobile-open');
                overlay?.classList.toggle('visible');
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
        });
 
        // Mobile: overlay click → close
 
        // Desktop: collapse toggle (hide/show)

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
            const isDashboard = path === '/admin/dashboard' && current === '/admin';
            const active = isDashboard || isActivePath(current, path);
            link.classList.toggle('active', active);
            link.classList.toggle('menu-active', active && link.classList.contains('nav-link-custom'));
        });
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

    function refreshAdminPage() {
        initThemeControls();
        initSidebar();
        applyActiveNavigation();
        renderAdminIcons();
        disableTurboFormSubmissions();
    }

    /* ── Bootstrap ──────────────────────────────────────────────────────── */
    // Script dimuat dengan defer; DOM awal sudah siap saat bootstrap berjalan.
    patchReadyCallbacksForTurbo();
    bindAlertHandlers();
    refreshAdminPage();
    startClock();
    checkTopbarWaStatus();

    document.addEventListener('turbo:load', refreshAdminPage);
    document.addEventListener('turbo:before-cache', closeTransientShellUi);

})();
