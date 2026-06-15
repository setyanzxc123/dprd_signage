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
        const sidebar  = document.getElementById('sidebar');
        const overlay  = document.getElementById('sidebar-overlay');
        const topbarToggle  = document.querySelector('.topbar-toggle');
        const sidebarToggle = document.getElementById('sidebarToggle');
 
        // Restore collapsed state
        const collapsed = localStorage.getItem('dprd-sidebar-collapsed') === 'collapsed';
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        setSidebarToggleLabel(collapsed);

        // Mobile: topbar hamburger
        topbarToggle?.addEventListener('click', function () {
            sidebar?.classList.toggle('mobile-open');
            overlay?.classList.toggle('visible');
        });
 
        // Mobile: overlay click → close
        overlay?.addEventListener('click', function () {
            sidebar?.classList.remove('mobile-open');
            overlay.classList.remove('visible');
        });
 
        // Desktop: collapse toggle (hide/show)
        sidebarToggle?.addEventListener('click', function () {
            const nowCollapsed = !document.documentElement.classList.contains('sidebar-collapsed');
            document.documentElement.classList.toggle('sidebar-collapsed', nowCollapsed);
            localStorage.setItem('dprd-sidebar-collapsed', nowCollapsed ? 'collapsed' : 'expanded');
            setSidebarToggleLabel(nowCollapsed);
        });

        // ESC → close mobile sidebar
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                sidebar?.classList.remove('mobile-open');
                overlay?.classList.remove('visible');
            }
        });
    }

    /* ── Active navigation ──────────────────────────────────────────────── */
    function applyActiveNavigation() {
        const current = window.location.pathname.replace(/\/$/, '') || '/';
        document.querySelectorAll('.nav-link-custom[data-path], .mobile-nav a[data-path]').forEach(function (link) {
            const path = link.getAttribute('data-path');
            const isDashboard = path === '/admin/dashboard' && current === '/admin';
            link.classList.toggle('active', isDashboard || isActivePath(current, path));
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

    /* ── Bootstrap ──────────────────────────────────────────────────────── */
    // Jalankan langsung karena script dipanggil di akhir body (elemen DOM sudah siap)
    bindAlertHandlers();
    initSidebar();
    applyActiveNavigation();
    startClock();
    renderAdminIcons();
    checkTopbarWaStatus();

})();
