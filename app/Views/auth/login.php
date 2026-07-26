<?php
$adminCssVersion = is_file(FCPATH . 'assets/css/admin.css') ? filemtime(FCPATH . 'assets/css/admin.css') : time();
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$lucideVersion = is_file(FCPATH . 'assets/vendor/lucide/lucide.min.js') ? filemtime(FCPATH . 'assets/vendor/lucide/lucide.min.js') : time();
$activeAccess = ($access ?? 'anggota') === 'admin' ? 'admin' : 'anggota';
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($pageTitle ?? 'Masuk Sistem DPRD') ?></title>
    <meta name="robots" content="noindex, nofollow" />
    <link rel="icon" type="image/jpeg" href="<?= base_url('assets/images/logo_dprd.jpg') ?>" />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet" />
</head>

<body class="min-h-screen bg-base-200">
    <main class="flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            <header class="mb-6 text-center">
                <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>"
                    alt="Logo DPRD Provinsi Sulawesi Tengah"
                    class="mx-auto mb-4 h-20 w-20 rounded-2xl border border-base-300 bg-white object-contain" />
                <h1 class="text-2xl font-black text-base-content">Sistem DPRD Sulawesi Tengah</h1>
                <p class="mt-2 text-sm text-base-content/65">
                    Pilih jenis akses untuk masuk ke sistem.
                </p>
            </header>

            <section class="card card-border bg-base-100 shadow-xl">
                <div class="card-body">
                    <div role="tablist" class="tabs tabs-box grid grid-cols-2">
                        <button type="button" role="tab"
                            class="tab <?= $activeAccess === 'anggota' ? 'tab-active' : '' ?>"
                            data-login-tab="anggota" aria-selected="<?= $activeAccess === 'anggota' ? 'true' : 'false' ?>">
                            Anggota DPRD
                        </button>
                        <button type="button" role="tab"
                            class="tab <?= $activeAccess === 'admin' ? 'tab-active' : '' ?>"
                            data-login-tab="admin" aria-selected="<?= $activeAccess === 'admin' ? 'true' : 'false' ?>">
                            Admin / Operator
                        </button>
                    </div>

                    <?php if (! empty($form_error)): ?>
                        <div role="alert" class="alert alert-error text-sm">
                            <i data-lucide="triangle-alert" class="h-4 w-4 shrink-0"></i>
                            <span><?= esc($form_error) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('success')): ?>
                        <div role="alert" class="alert alert-success text-sm">
                            <i data-lucide="circle-check" class="h-4 w-4 shrink-0"></i>
                            <span><?= esc(session()->getFlashdata('success')) ?></span>
                        </div>
                    <?php endif; ?>

                    <div data-login-panel="anggota" class="<?= $activeAccess === 'anggota' ? '' : 'hidden' ?>">
                        <div class="mb-3">
                            <h2 class="font-bold text-base-content">Masuk sebagai Anggota</h2>
                            <p class="text-sm text-base-content/60">Gunakan nomor WhatsApp yang terdaftar.</p>
                        </div>

                        <form action="<?= base_url('login/anggota') ?>" method="POST" data-login-form>
                            <?= csrf_field() ?>

                            <label class="block text-sm font-semibold text-base-content" for="member-phone">
                                Nomor WhatsApp
                            </label>
                            <label class="input mt-1 flex w-full items-center gap-2">
                                <span class="text-sm font-semibold text-base-content/60">+62</span>
                                <input type="tel" class="grow" id="member-phone" name="no_wa"
                                    value="<?= esc($old_phone ?? '') ?>" placeholder="8123456789"
                                    inputmode="numeric" autocomplete="tel" required />
                            </label>

                            <label class="mt-3 block text-sm font-semibold text-base-content" for="member-password">
                                Password
                            </label>
                            <input type="password" class="input mt-1 w-full" id="member-password"
                                name="password" placeholder="Masukkan password"
                                autocomplete="current-password" required />

                            <button type="submit" class="btn btn-primary btn-block mt-5" data-login-button>
                                <i data-lucide="log-in" class="h-4 w-4"></i>
                                Masuk sebagai Anggota
                            </button>
                        </form>
                    </div>

                    <div data-login-panel="admin" class="<?= $activeAccess === 'admin' ? '' : 'hidden' ?>">
                        <div class="mb-3">
                            <h2 class="font-bold text-base-content">Masuk sebagai Admin</h2>
                            <p class="text-sm text-base-content/60">Khusus operator dan pengelola sistem.</p>
                        </div>

                        <form action="<?= base_url('login/admin') ?>" method="POST" data-login-form>
                            <?= csrf_field() ?>

                            <label class="block text-sm font-semibold text-base-content" for="admin-username">
                                Username
                            </label>
                            <input type="text" class="input mt-1 w-full" id="admin-username"
                                name="username" value="<?= esc($old_username ?? '') ?>"
                                placeholder="Masukkan username" autocomplete="username" required />

                            <label class="mt-3 block text-sm font-semibold text-base-content" for="admin-password">
                                Password
                            </label>
                            <input type="password" class="input mt-1 w-full" id="admin-password"
                                name="password" placeholder="Masukkan password"
                                autocomplete="current-password" required />

                            <button type="submit" class="btn btn-primary btn-block mt-5" data-login-button>
                                <i data-lucide="shield-check" class="h-4 w-4"></i>
                                Masuk sebagai Admin
                            </button>
                        </form>
                    </div>

                    <div class="card-actions justify-center">
                        <a href="<?= base_url('signage') ?>" class="btn btn-ghost btn-sm">
                            <i data-lucide="arrow-left" class="h-4 w-4"></i>
                            Kembali ke Signage
                        </a>
                    </div>
                </div>
            </section>

            <p class="mt-4 text-center text-xs text-base-content/50">
                &copy; <?= date('Y') ?> DPRD Provinsi Sulawesi Tengah
            </p>
        </div>
    </main>

    <script src="<?= base_url('assets/vendor/lucide/lucide.min.js?v=' . $lucideVersion) ?>"></script>
    <script {csp-script-nonce}>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) window.lucide.createIcons();

            const tabs = document.querySelectorAll('[data-login-tab]');
            const panels = document.querySelectorAll('[data-login-panel]');

            function selectAccess(access) {
                tabs.forEach(function (tab) {
                    const active = tab.dataset.loginTab === access;
                    tab.classList.toggle('tab-active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                panels.forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.dataset.loginPanel !== access);
                });

                const url = new URL(window.location.href);
                url.searchParams.set('akses', access);
                window.history.replaceState({}, '', url);
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    selectAccess(tab.dataset.loginTab);
                });
            });

            document.querySelectorAll('[data-login-form]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    const button = form.querySelector('[data-login-button]');
                    if (!button) return;
                    button.disabled = true;
                    button.innerHTML = '<span class="loading loading-spinner loading-xs"></span>Memverifikasi...';
                });
            });
        });
    </script>
</body>

</html>
