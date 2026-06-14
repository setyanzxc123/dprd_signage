/* Admin shell - Vue controller island for CodeIgniter-rendered pages. */
(function () {
    function renderAdminIcons() {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    window.renderAdminIcons = renderAdminIcons;

    function formatClock(date) {
        return new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
            timeZone: 'Asia/Makassar'
        }).format(date) + ' WITA';
    }

    function formatPageDate(date) {
        return new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            timeZone: 'Asia/Makassar'
        }).format(date);
    }

    function isActivePath(currentPath, linkPath) {
        return currentPath === linkPath || currentPath.startsWith(linkPath + '/');
    }

    function setupIconObserver() {
        if (!window.MutationObserver) return;

        let renderQueued = false;
        const queueRender = function () {
            if (renderQueued) return;
            renderQueued = true;
            window.requestAnimationFrame(function () {
                renderQueued = false;
                renderAdminIcons();
            });
        };

        const observer = new MutationObserver(function (mutations) {
            const hasIconChanges = mutations.some(function (mutation) {
                return Array.from(mutation.addedNodes).some(function (node) {
                    return node.nodeType === 1 && (
                        node.matches?.('[data-lucide]') ||
                        node.querySelector?.('[data-lucide]')
                    );
                });
            });

            if (hasIconChanges) queueRender();
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    function setSidebarToggleState(isCollapsed) {
        const toggleButton = document.getElementById('sidebarToggle');
        if (!toggleButton) return;

        toggleButton.setAttribute('aria-expanded', String(!isCollapsed));
        toggleButton.setAttribute('aria-label', isCollapsed ? 'Buka sidebar' : 'Ciutkan sidebar');
        toggleButton.setAttribute('title', isCollapsed ? 'Buka sidebar' : 'Ciutkan sidebar');
    }

    function bindAlertHandlers() {
        if (document.documentElement.dataset.adminAlertBound === '1') {
            return;
        }
        document.documentElement.dataset.adminAlertBound = '1';

        document.addEventListener('click', (event) => {
            const closeButton = event.target.closest('.ta-alert-close');
            if (closeButton) {
                closeButton.closest('.ta-alert')?.remove();
            }
        });
    }

    function bindFallbackShellHandlers() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const topbarToggle = document.querySelector('.topbar-toggle');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const savedCollapsed = localStorage.getItem('dprd-sidebar-collapsed') === 'collapsed';

        document.body.classList.toggle('sidebar-collapsed', savedCollapsed);
        setSidebarToggleState(savedCollapsed);

        topbarToggle?.addEventListener('click', function () {
            sidebar?.classList.toggle('mobile-open');
            overlay?.classList.toggle('visible');
        });

        overlay?.addEventListener('click', function () {
            sidebar?.classList.remove('mobile-open');
            overlay.classList.remove('visible');
        });

        sidebarToggle?.addEventListener('click', function () {
            const isCollapsed = !document.body.classList.contains('sidebar-collapsed');
            document.body.classList.toggle('sidebar-collapsed', isCollapsed);
            localStorage.setItem('dprd-sidebar-collapsed', isCollapsed ? 'collapsed' : 'expanded');
            setSidebarToggleState(isCollapsed);
        });
    }

    function applyFallbackActiveNavigation() {
        const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
        document.querySelectorAll('.nav-link-custom[data-path], .mobile-nav a[data-path]').forEach((link) => {
            const path = link.getAttribute('data-path');
            const isDashboard = path === '/admin/dashboard' && currentPath === '/admin';
            link.classList.toggle('active', isDashboard || isActivePath(currentPath, path));
        });
    }

    function startFallbackClock() {
        const update = function () {
            const clock = document.getElementById('topbar-clock');
            if (clock) {
                clock.textContent = formatClock(new Date());
            }

            const pageDate = document.getElementById('page-date');
            if (pageDate) {
                pageDate.textContent = formatPageDate(new Date());
            }
        };

        update();
        window.setInterval(update, 1000);
    }

    async function checkTopbarWaStatus() {
        const statusEl = document.getElementById('topbar-wa-status');
        const badgeEl = document.getElementById('topbar-wa-badge');
        const dotEl = document.getElementById('topbar-wa-dot');
        const textEl = document.getElementById('topbar-wa-text');
        if (!statusEl || !badgeEl || !dotEl || !textEl) return;

        const cacheKey = 'dprd_wa_status_cache';
        const cacheTimeKey = 'dprd_wa_status_cache_time';
        const cacheDuration = 5 * 60 * 1000; // 5 menit dalam ms

        const cachedData = localStorage.getItem(cacheKey);
        const cachedTime = localStorage.getItem(cacheTimeKey);
        const now = Date.now();

        function applyStatusUi(data) {
            dotEl.classList.remove('animate-pulse');
            badgeEl.style.backgroundColor = '';
            badgeEl.style.color = '';
            badgeEl.style.borderColor = '';
            dotEl.style.backgroundColor = '';
            
            if (data.configured && data.connected) {
                badgeEl.className = 'badge badge-success badge-soft gap-1.5 h-8 px-3';
                dotEl.className = 'h-1.5 w-1.5 rounded-full bg-success';
                textEl.textContent = 'WhatsApp: Siap';
                statusEl.setAttribute('title', 'WhatsApp Terhubung (Siap)');
            } else if (data.configured && !data.connected) {
                badgeEl.className = 'badge badge-error badge-soft gap-1.5 h-8 px-3';
                dotEl.className = 'h-1.5 w-1.5 rounded-full bg-error';
                textEl.textContent = 'WhatsApp: Error';
                statusEl.setAttribute('title', 'WhatsApp Terputus: ' + (data.error || 'Gagal terhubung'));
            } else {
                badgeEl.className = 'badge badge-neutral badge-soft gap-1.5 h-8 px-3';
                dotEl.className = 'h-1.5 w-1.5 rounded-full bg-neutral-content';
                textEl.textContent = 'WhatsApp: Belum Aktif';
                statusEl.setAttribute('title', 'WhatsApp Belum Dikonfigurasi');
            }
        }

        // Jika cache valid dan belum kedaluwarsa
        if (cachedData && cachedTime && (now - cachedTime < cacheDuration)) {
            try {
                const data = JSON.parse(cachedData);
                applyStatusUi(data);
                return;
            } catch(e) {
                // Abaikan jika error parsing dan fetch baru
            }
        }

        // Set loading state (pulse effect)
        badgeEl.className = 'badge badge-neutral badge-soft gap-1.5 h-8 px-3';
        dotEl.className = 'h-1.5 w-1.5 rounded-full bg-neutral-content animate-pulse';
        textEl.textContent = 'WhatsApp: Memeriksa...';
        badgeEl.style.backgroundColor = '';
        badgeEl.style.color = '';
        badgeEl.style.borderColor = '';
        dotEl.style.backgroundColor = '';

        try {
            const resp = await fetch('/admin/pengaturan/wa-status', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await resp.json();

            // Simpan ke cache
            localStorage.setItem(cacheKey, JSON.stringify(data));
            localStorage.setItem(cacheTimeKey, now.toString());

            applyStatusUi(data);
        } catch(e) {
            dotEl.classList.remove('animate-pulse');
            badgeEl.className = 'badge badge-warning badge-soft gap-1.5 h-8 px-3';
            dotEl.className = 'h-1.5 w-1.5 rounded-full bg-warning';
            badgeEl.style.backgroundColor = '';
            badgeEl.style.color = '';
            badgeEl.style.borderColor = '';
            dotEl.style.backgroundColor = '';
            textEl.textContent = 'WhatsApp: Gagal Pengecekan';
            statusEl.setAttribute('title', 'Gagal memeriksa status WhatsApp');
        }
    }

    function mountAdminController() {
        const controllerRoot = document.getElementById('admin-vue-controller');

        if (!window.Vue || !controllerRoot) {
            bindAlertHandlers();
            bindFallbackShellHandlers();
            applyFallbackActiveNavigation();
            startFallbackClock();
            renderAdminIcons();
            checkTopbarWaStatus();
            return;
        }

        window.Vue.createApp({
            data() {
                return {
                    currentPath: window.location.pathname.replace(/\/$/, '') || '/',
                    sidebarOpen: false,
                    sidebarCollapsed: localStorage.getItem('dprd-sidebar-collapsed') === 'collapsed',
                    clockTimer: null
                };
            },
            mounted() {
                this.applySidebarState();
                this.applyActiveNavigation();
                this.updateClock();
                this.updatePageDate();
                this.bindEvents();
                this.autoDismissFlashMessages();
                setupIconObserver();
                renderAdminIcons();
                checkTopbarWaStatus();

                this.clockTimer = window.setInterval(() => this.updateClock(), 1000);
                this.boundHandleKeydown = this.handleKeydown.bind(this);
                window.addEventListener('keydown', this.boundHandleKeydown);
            },
            beforeUnmount() {
                if (this.clockTimer) {
                    window.clearInterval(this.clockTimer);
                }
                if (this.boundHandleKeydown) {
                    window.removeEventListener('keydown', this.boundHandleKeydown);
                }
            },
            methods: {
                bindEvents() {
                    document.querySelector('.topbar-toggle')?.addEventListener('click', () => this.toggleMobileSidebar());
                    document.getElementById('sidebarToggle')?.addEventListener('click', () => this.toggleSidebarCollapsed());
                    document.getElementById('sidebar-overlay')?.addEventListener('click', () => this.closeMobileSidebar());
                    bindAlertHandlers();
                },
                updateClock() {
                    const clock = document.getElementById('topbar-clock');
                    if (clock) {
                        clock.textContent = formatClock(new Date());
                    }
                },
                updatePageDate() {
                    const pageDate = document.getElementById('page-date');
                    if (pageDate) {
                        pageDate.textContent = formatPageDate(new Date());
                    }
                },
                applyActiveNavigation() {
                    document.querySelectorAll('.nav-link-custom[data-path], .mobile-nav a[data-path]').forEach((link) => {
                        const path = link.getAttribute('data-path');
                        const isDashboard = path === '/admin/dashboard' && this.currentPath === '/admin';
                        link.classList.toggle('active', isDashboard || isActivePath(this.currentPath, path));
                    });
                },
                applySidebarState() {
                    document.body.classList.toggle('sidebar-collapsed', this.sidebarCollapsed);
                    document.getElementById('sidebar')?.classList.toggle('mobile-open', this.sidebarOpen);
                    document.getElementById('sidebar-overlay')?.classList.toggle('visible', this.sidebarOpen);
                    setSidebarToggleState(this.sidebarCollapsed);
                    localStorage.setItem('dprd-sidebar-collapsed', this.sidebarCollapsed ? 'collapsed' : 'expanded');
                },
                toggleMobileSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                    this.applySidebarState();
                },
                closeMobileSidebar() {
                    this.sidebarOpen = false;
                    this.applySidebarState();
                },
                toggleSidebarCollapsed() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    this.applySidebarState();
                },
                autoDismissFlashMessages() {
                    document.querySelectorAll('.ta-alert-flash').forEach(function (alert) {
                        window.setTimeout(function () {
                            alert.style.transition = 'opacity 0.5s ease';
                            alert.style.opacity = '0';
                            window.setTimeout(function () { alert.remove(); }, 500);
                        }, 4000);
                    });
                },
                handleKeydown(event) {
                    if (event.key === 'Escape') {
                        this.closeMobileSidebar();
                    }
                }
            }
        }).mount(controllerRoot);
    }

    document.addEventListener('DOMContentLoaded', mountAdminController);
})();
