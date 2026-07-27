<?php
$adminCssVersion = is_file(FCPATH . 'assets/css/admin.css') ? filemtime(FCPATH . 'assets/css/admin.css') : time();
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$lucideVersion = is_file(FCPATH . 'assets/vendor/lucide/lucide.min.js') ? filemtime(FCPATH . 'assets/vendor/lucide/lucide.min.js') : time();
$activeAccess = ($access ?? 'anggota') === 'admin' ? 'admin' : 'anggota';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script {csp-script-nonce}>
        (() => {
            const stored = localStorage.getItem('dprd-admin-theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = stored === 'dark' || stored === 'light'
                ? stored
                : (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <title><?= esc($pageTitle ?? 'Sistem Informasi Agenda dan Jadwal Rapat DPRD') ?></title>
    <meta name="robots" content="noindex, nofollow" />
    <link rel="icon" type="image/jpeg" href="<?= base_url('assets/images/logo_dprd.jpg') ?>" />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet" />
</head>

<body class="min-h-screen bg-base-200">
    <label class="btn btn-ghost btn-circle swap swap-rotate fixed right-4 top-4 z-10 bg-base-100 shadow-sm"
        title="Ganti tema" aria-label="Ganti tema" data-theme-toggle>
        <input type="checkbox" value="dark" class="theme-controller" data-theme-toggle-input />
        <i class="swap-on h-5 w-5" data-lucide="sun"></i>
        <i class="swap-off h-5 w-5" data-lucide="moon"></i>
    </label>

    <main class="flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            <header class="mb-6 text-center">
                <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>"
                    alt="Logo DPRD Provinsi Sulawesi Tengah"
                    class="mx-auto mb-4 h-20 w-20 rounded-2xl border border-base-300 bg-white object-contain" />
                <h1 class="text-2xl font-black text-base-content">
                    Sistem Informasi Agenda dan Jadwal Rapat DPRD
                </h1>
            </header>

            <section class="card card-border bg-base-100 shadow-xl">
                <div class="card-body">
                    <div role="group" aria-label="Pilih jenis akses" class="grid grid-cols-2 gap-2">
                        <button type="button"
                            class="btn px-2 sm:px-4 <?= $activeAccess === 'anggota' ? 'btn-outline border-base-content text-base-content' : 'btn-ghost text-base-content/65' ?>"
                            data-login-tab="anggota" aria-pressed="<?= $activeAccess === 'anggota' ? 'true' : 'false' ?>">
                            <i data-lucide="users" class="h-5 w-5 shrink-0"></i>
                            <span>Anggota DPRD</span>
                        </button>
                        <button type="button"
                            class="btn px-2 sm:px-4 <?= $activeAccess === 'admin' ? 'btn-outline border-base-content text-base-content' : 'btn-ghost text-base-content/65' ?>"
                            data-login-tab="admin" aria-pressed="<?= $activeAccess === 'admin' ? 'true' : 'false' ?>">
                            <i data-lucide="settings-2" class="h-5 w-5 shrink-0"></i>
                            <span>Admin / Operator</span>
                        </button>
                    </div>

                    <?php if (! empty($form_error)): ?>
                        <div role="alert" class="alert alert-error text-sm">
                            <i data-lucide="triangle-alert" class="h-4 w-4 shrink-0"></i>
                            <span><?= esc($form_error) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($flash_success)): ?>
                        <div role="alert" class="alert alert-success text-sm">
                            <i data-lucide="circle-check" class="h-4 w-4 shrink-0"></i>
                            <span><?= esc($flash_success) ?></span>
                        </div>
                    <?php endif; ?>

                    <div data-login-panel="anggota" class="<?= $activeAccess === 'anggota' ? '' : 'hidden' ?>">
                        <div class="mb-3">
                            <h2 class="font-bold text-base-content">
                                <?= ($member_step ?? 'request') === 'verify' ? 'Verifikasi Kode OTP' : 'Masuk sebagai Anggota' ?>
                            </h2>
                            <?php if (($member_step ?? 'request') === 'verify'): ?>
                                <p class="text-sm text-base-content/60">
                                    Masukkan enam digit kode yang dikirim ke <?= esc($masked_phone ?? 'nomor WhatsApp Anda') ?>.
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if (! empty($otp_success)): ?>
                            <div role="alert" class="alert alert-info mb-4 text-sm">
                                <i data-lucide="message-circle-check" class="h-4 w-4 shrink-0"></i>
                                <span><?= esc($otp_success) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (($member_step ?? 'request') === 'verify'): ?>
                            <form action="<?= base_url('login/anggota/verifikasi') ?>" method="POST" data-login-form>
                                <?= csrf_field() ?>
                                <label class="block text-sm font-semibold text-base-content" for="member-otp">
                                    Kode OTP
                                </label>
                                <input type="text" class="input input-lg mt-1 w-full text-center text-2xl tracking-[0.35em]"
                                    id="member-otp" name="otp" placeholder="000000" inputmode="numeric"
                                    pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code"
                                    data-digits-only data-max-digits="6" autofocus required />
                                <button type="submit" class="btn btn-primary btn-block mt-5" data-login-button
                                    data-loading-label="Memverifikasi...">
                                    <i data-lucide="shield-check" class="h-4 w-4"></i>
                                    Verifikasi dan Masuk
                                </button>
                            </form>

                            <form action="<?= base_url('login/anggota/kirim-ulang') ?>" method="POST" class="mt-3" data-resend-form>
                                <?= csrf_field() ?>
                                <button type="submit"
                                    class="btn btn-ghost btn-block text-base-content/80 disabled:opacity-100"
                                    data-resend-button
                                    data-retry-after="<?= (int) ($retry_after ?? 0) ?>">
                                    Kirim ulang kode
                                </button>
                            </form>
                            <form action="<?= base_url('login/anggota/reset') ?>" method="POST" class="mt-1">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-link btn-block btn-sm">
                                    Gunakan nomor lain
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="<?= base_url('login/anggota') ?>" method="POST" data-login-form>
                                <?= csrf_field() ?>
                                <label class="block text-sm font-semibold text-base-content" for="member-phone">
                                    Nomor WhatsApp
                                </label>
                                <label class="input mt-1 flex w-full items-center gap-2">
                                    <span class="text-sm font-semibold text-base-content/60">+62</span>
                                    <input type="tel" class="grow" id="member-phone" name="no_wa"
                                        value="<?= esc($old_phone ?? '') ?>" placeholder="8123456789"
                                        inputmode="numeric" pattern="8[0-9]{7,12}" minlength="8" maxlength="13"
                                        autocomplete="tel" data-digits-only data-max-digits="13" required />
                                </label>
                                <button type="submit" class="btn btn-primary btn-block mt-5" data-login-button
                                    data-loading-label="Mengirim kode...">
                                    <i data-lucide="message-circle" class="h-4 w-4"></i>
                                    Kirim Kode OTP
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div data-login-panel="admin" class="<?= $activeAccess === 'admin' ? '' : 'hidden' ?>">
                        <div class="mb-3">
                            <h2 class="font-bold text-base-content">Masuk sebagai Admin</h2>
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

            const themeInput = document.querySelector('[data-theme-toggle-input]');
            const themeToggle = document.querySelector('[data-theme-toggle]');
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            themeInput.checked = isDark;

            function syncThemeLabel(dark) {
                const label = dark ? 'Gunakan tema terang' : 'Gunakan tema gelap';
                themeToggle.setAttribute('aria-label', label);
                themeToggle.setAttribute('title', label);
            }

            syncThemeLabel(isDark);
            themeInput.addEventListener('change', function () {
                const theme = themeInput.checked ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('dprd-admin-theme', theme);
                syncThemeLabel(theme === 'dark');
            });

            const tabs = document.querySelectorAll('[data-login-tab]');
            const panels = document.querySelectorAll('[data-login-panel]');

            function selectAccess(access) {
                tabs.forEach(function (tab) {
                    const active = tab.dataset.loginTab === access;
                    tab.classList.toggle('btn-outline', active);
                    tab.classList.toggle('border-base-content', active);
                    tab.classList.toggle('text-base-content', active);
                    tab.classList.toggle('btn-ghost', !active);
                    tab.classList.toggle('text-base-content/65', !active);
                    tab.setAttribute('aria-pressed', active ? 'true' : 'false');
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
                    const label = button.dataset.loadingLabel || 'Memverifikasi...';
                    button.innerHTML = '<span class="loading loading-spinner loading-xs"></span>' + label;
                });
            });

            document.querySelectorAll('[data-digits-only]').forEach(function (input) {
                const maxDigits = Number.parseInt(input.dataset.maxDigits || '0', 10);
                const sanitizeDigits = function () {
                    const digits = input.value.replace(/[^0-9]/g, '');
                    input.value = maxDigits > 0 ? digits.slice(0, maxDigits) : digits;
                };

                input.addEventListener('keydown', function (event) {
                    if (event.key.length === 1 && ! /[0-9]/.test(event.key)) {
                        event.preventDefault();
                    }
                });
                input.addEventListener('input', sanitizeDigits);
                sanitizeDigits();
            });

            const countdownMarkup = function (remaining) {
                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const seconds = remaining % 60;
                const unit = function (value, label) {
                    if (value > 999) {
                        return '<span aria-label="' + value + ' ' + label + '">' + value + '</span>';
                    }

                    return '<span class="countdown" aria-live="polite" aria-label="' + value + ' ' + label + '">'
                        + '<span style="--value:' + value + ';">' + value + '</span></span>';
                };
                const minuteAndSecond = unit(minutes, 'menit') + ':' + unit(seconds, 'detik');

                return hours > 0 ? unit(hours, 'jam') + ':' + minuteAndSecond : minuteAndSecond;
            };

            const resendButton = document.querySelector('[data-resend-button]');
            if (resendButton) {
                let remaining = Number.parseInt(resendButton.dataset.retryAfter || '0', 10);
                const renderCountdown = function () {
                    const waiting = remaining > 0;
                    resendButton.disabled = waiting;
                    resendButton.innerHTML = waiting
                        ? 'Kirim ulang dalam ' + countdownMarkup(remaining)
                        : 'Kirim ulang kode';
                };
                renderCountdown();
                if (remaining > 0) {
                    const timer = window.setInterval(function () {
                        remaining -= 1;
                        renderCountdown();
                        if (remaining <= 0) window.clearInterval(timer);
                    }, 1000);
                }
            }

        });
    </script>
</body>

</html>
