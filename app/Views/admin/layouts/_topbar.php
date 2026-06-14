<?php
$pageTitle   = $pageTitle ?? 'Dashboard';
$breadcrumbs = $breadcrumbs ?? [];

$authUser  = session()->get('auth_user') ?? [];
$userName  = $authUser['name'] ?? 'Admin Operator';
$userRole  = $authUser['role'] ?? 'operator';
$userInit  = strtoupper(substr($userName, 0, 1));
$roleLabel = $userRole === 'superadmin' ? 'Super Admin' : 'Sekretariat DPRD';
?>

<header id="topbar">

    <button class="topbar-toggle" type="button" aria-label="Toggle sidebar">
        <i data-lucide="menu"></i>
    </button>

    <nav class="topbar-breadcrumb" aria-label="breadcrumb">
        <?php if ($pageTitle === 'Dashboard' && empty($breadcrumbs)): ?>
            <strong class="current"><?= esc($pageTitle) ?></strong>
        <?php else: ?>
            <a href="<?= base_url('admin/dashboard') ?>">Dashboard</a>
            <?php foreach ($breadcrumbs as $crumb): ?>
                <span class="bc-sep">/</span>
                <?php if (!empty($crumb['url'])): ?>
                    <a href="<?= base_url($crumb['url']) ?>"><?= esc($crumb['label']) ?></a>
                <?php else: ?>
                    <span><?= esc($crumb['label']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
            <span class="bc-sep">/</span>
            <strong class="current"><?= esc($pageTitle) ?></strong>
        <?php endif; ?>
    </nav>

    <div class="topbar-actions">

        <!-- Status Koneksi WA -->
        <a class="topbar-wa-status-link" href="<?= base_url('admin/pengaturan#wa-notif-card') ?>" id="topbar-wa-status" title="Memeriksa status WhatsApp...">
            <span class="badge badge-neutral badge-soft gap-1.5 h-8 px-3" id="topbar-wa-badge">
                <span class="h-1.5 w-1.5 rounded-full bg-neutral-content animate-pulse" id="topbar-wa-dot"></span>
                <span id="topbar-wa-text">WhatsApp: Memeriksa...</span>
            </span>
        </a>
        <script>
            (function() {
                try {
                    var cache = localStorage.getItem('dprd_wa_status_cache');
                    var time = localStorage.getItem('dprd_wa_status_cache_time');
                    if (cache && time && (Date.now() - parseInt(time) < 300000)) {
                        var data = JSON.parse(cache);
                        var statusEl = document.getElementById('topbar-wa-status');
                        var badgeEl = document.getElementById('topbar-wa-badge');
                        var dotEl = document.getElementById('topbar-wa-dot');
                        var textEl = document.getElementById('topbar-wa-text');
                        if (statusEl && badgeEl && dotEl && textEl) {
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
                    }
                } catch(e) {}
            })();
        </script>

        <a class="btn btn-ghost btn-circle relative" href="<?= base_url('admin/notifikasi') ?>" title="Notifikasi WA">
            <i data-lucide="bell"></i>
            <span class="absolute top-2.5 right-2.5 h-2 w-2 rounded-full bg-error hidden" id="topbar-notif-dot"></span>
        </a>

        <div class="topbar-user">
            <div class="topbar-avatar"><?= esc($userInit) ?></div>
            <div class="topbar-user-info">
                <div class="topbar-user-name"><?= esc($userName) ?></div>
                <div class="topbar-user-role"><?= esc($roleLabel) ?></div>
            </div>
        </div>

        <form class="topbar-logout-form" method="post" action="<?= base_url('admin/logout') ?>"
              onsubmit="return confirm('Yakin ingin keluar?')">
            <?= csrf_field() ?>
            <button class="btn btn-ghost btn-circle hover:text-error" type="submit" title="Logout" aria-label="Logout">
                <i data-lucide="log-out"></i>
            </button>
        </form>
    </div>

</header>
