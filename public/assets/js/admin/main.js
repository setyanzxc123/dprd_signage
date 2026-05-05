function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const wrapper = document.getElementById('main-wrapper');
    const overlay = document.getElementById('sidebar-overlay');
    const isMobile = window.innerWidth <= 992;

    if (isMobile) {
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('visible');
    } else {
        sidebar.classList.toggle('collapsed');
        wrapper.classList.toggle('expanded');
    }
}


function toggleSubmenu(subId, triggerEl) {
    const sub = document.getElementById(subId);
    const arrow = triggerEl.querySelector('.nav-arrow');

    if (!sub) return;

    sub.classList.toggle('open');
    if (arrow) arrow.classList.toggle('rotated');
}


function updateClock() {
    const el = document.getElementById('topbar-clock');
    if (!el) return;

    const formatter = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'Asia/Makassar'
    });

    el.textContent = formatter.format(new Date()) + ' WITA';
}


function updatePageDate() {
    const el = document.getElementById('page-date');
    if (!el) return;

    const formatter = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Makassar'
    });

    el.textContent = formatter.format(new Date());
}


function setActiveNav() {
    const currentPath = window.location.pathname;

    document.querySelectorAll('.nav-link-custom[data-path]').forEach(function (link) {
        const linkPath = link.getAttribute('data-path');

        if (currentPath.startsWith(linkPath)) {
            link.classList.add('active');

            const parentSub = link.closest('.nav-sub');
            if (parentSub) {
                parentSub.classList.add('open');

                const triggerBtn = parentSub.previousElementSibling;
                if (triggerBtn) {
                    const arrow = triggerBtn.querySelector('.nav-arrow');
                    if (arrow) arrow.classList.add('rotated');
                }
            }
        }
    });
}


function initFlashMessages() {
    document.querySelectorAll('.alert-flash').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }, 4000);
    });
}


function initOverlayClose() {
    const overlay = document.getElementById('sidebar-overlay');
    if (!overlay) return;

    overlay.addEventListener('click', function () {
        document.getElementById('sidebar').classList.remove('mobile-open');
        overlay.classList.remove('visible');
    });
}


document.addEventListener('DOMContentLoaded', function () {
    updateClock();
    setInterval(updateClock, 1000);
    updatePageDate();
    setActiveNav();
    initFlashMessages();
    initOverlayClose();
});
