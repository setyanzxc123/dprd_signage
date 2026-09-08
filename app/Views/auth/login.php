<?php
$adminCssVersion = is_file(FCPATH . 'assets/css/admin.css') ? filemtime(FCPATH . 'assets/css/admin.css') : time();
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$lucideVersion = is_file(FCPATH . 'assets/vendor/lucide/lucide.min.js') ? filemtime(FCPATH . 'assets/vendor/lucide/lucide.min.js') : time();
$logoVersion = is_file(FCPATH . 'assets/images/logo_dprd.png') ? filemtime(FCPATH . 'assets/images/logo_dprd.png') : time();
$adminThemeJsVersion = is_file(FCPATH . 'assets/js/admin/theme-init.js') ? filemtime(FCPATH . 'assets/js/admin/theme-init.js') : time();
$activeAccess = ($access ?? 'anggota') === 'admin' ? 'admin' : 'anggota';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="<?= base_url('assets/js/admin/theme-init.js?v=' . $adminThemeJsVersion) ?>"></script>
    <title><?= esc($pageTitle ?? 'Sistem Informasi Agenda dan Jadwal Rapat DPRD') ?></title>
    <meta name="robots" content="noindex, nofollow" />
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/logo_dprd.png?v=' . $logoVersion) ?>" />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet" />
</head>

<body class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-800 antialiased dark:bg-slate-900 dark:text-slate-200">
    <label class="fixed right-4 top-4 z-20 inline-flex size-11 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-xs transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white"
        title="Ganti tema" aria-label="Ganti tema" data-theme-toggle>
        <input type="checkbox" value="dark" class="hidden" data-theme-toggle-input />
        <i class="theme-icon-sun size-4" data-lucide="sun"></i>
        <i class="theme-icon-moon size-4" data-lucide="moon"></i>
    </label>

    <main class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6">
        <div class="w-full max-w-[490px] sm:max-w-[500px]">
            <section class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xl shadow-slate-200/50 dark:border-slate-700/80 dark:bg-slate-800 dark:shadow-none">
                <div class="login-card-motif" aria-hidden="true"></div>

                <div class="relative z-10 p-6 sm:p-8">
                    <header class="mb-6 text-center">
                        <img src="<?= base_url('assets/images/logo_dprd.png?v=' . $logoVersion) ?>"
                            alt="Logo DPRD Provinsi Sulawesi Tengah"
                            width="64" height="64"
                            class="mx-auto mb-3 h-14 w-14 shrink-0 object-contain drop-shadow-xs sm:h-16 sm:w-16" />
                        <h1 class="text-lg font-bold leading-snug tracking-tight text-slate-800 sm:text-xl dark:text-slate-100">
                            Sistem Informasi Agenda dan Jadwal Rapat DPRD
                        </h1>
                        <p class="mt-1 text-[11px] font-semibold tracking-wider text-slate-500 uppercase sm:text-xs dark:text-slate-400">
                            PROVINSI SULAWESI TENGAH
                        </p>
                    </header>

                    <div class="mb-5">
                        <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-700/50">
                            <label for="akses-anggota"
                                class="relative flex cursor-pointer items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold sm:text-sm transition select-none <?= $activeAccess === 'anggota' ? 'bg-white text-blue-600 shadow-xs dark:bg-slate-800 dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200' ?>">
                                <input type="radio" name="login_access" id="akses-anggota" value="anggota"
                                    class="sr-only" data-login-tab="anggota"
                                    <?= $activeAccess === 'anggota' ? 'checked' : '' ?> />
                                <i data-lucide="users" class="size-4 shrink-0"></i>
                                <span class="truncate">Anggota DPRD</span>
                            </label>
                            <label for="akses-admin"
                                class="relative flex cursor-pointer items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold sm:text-sm transition select-none <?= $activeAccess === 'admin' ? 'bg-white text-blue-600 shadow-xs dark:bg-slate-800 dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200' ?>">
                                <input type="radio" name="login_access" id="akses-admin" value="admin"
                                    class="sr-only" data-login-tab="admin"
                                    <?= $activeAccess === 'admin' ? 'checked' : '' ?> />
                                <i data-lucide="settings-2" class="size-4 shrink-0"></i>
                                <span class="truncate">Admin / Operator</span>
                            </label>
                        </div>
                    </div>

                    <?php if (! empty($form_error)): ?>
                        <div role="alert" class="mb-4 flex items-start justify-between gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-800 transition-opacity duration-200 sm:text-sm dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300" data-login-alert data-alert-scope="<?= esc($activeAccess) ?>">
                            <div class="flex items-start gap-2.5 grow">
                                <i data-lucide="triangle-alert" class="mt-0.5 size-4 shrink-0"></i>
                                <div class="leading-relaxed">
                                    <span data-alert-message><?= esc($form_error) ?></span>
                                    <?php if (($admin_retry_after ?? 0) > 0 && $activeAccess === 'admin'): ?>
                                        <div class="mt-1 font-semibold flex items-center gap-1.5 text-red-700 dark:text-red-300" data-admin-countdown-wrap>
                                            <i data-lucide="timer" class="size-3.5 shrink-0"></i>
                                            <span>Coba lagi dalam: <span id="admin-countdown" class="font-mono font-bold" data-admin-retry-after="<?= (int) $admin_retry_after ?>"><?= (int) $admin_retry_after ?></span> detik</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="button" class="shrink-0 -me-1 -mt-1 p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 transition rounded-lg cursor-pointer" aria-label="Tutup notifikasi" data-alert-dismiss>
                                <i data-lucide="x" class="size-4"></i>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($flash_success)): ?>
                        <div role="alert" class="mb-4 flex items-start justify-between gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs text-emerald-800 transition-opacity duration-200 sm:text-sm dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300" data-login-alert>
                            <div class="flex items-start gap-2.5 grow">
                                <i data-lucide="circle-check" class="mt-0.5 size-4 shrink-0"></i>
                                <div class="leading-relaxed"><?= esc($flash_success) ?></div>
                            </div>
                            <button type="button" class="shrink-0 -me-1 -mt-1 p-1 text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200 transition rounded-lg cursor-pointer" aria-label="Tutup notifikasi" data-alert-dismiss>
                                <i data-lucide="x" class="size-4"></i>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div data-login-panel="anggota" class="<?= $activeAccess === 'anggota' ? '' : 'hidden' ?>">
                        <div class="mb-4 text-center">
                            <h2 class="text-sm font-bold text-slate-800 sm:text-base dark:text-slate-100">
                                <?= ($member_step ?? 'request') === 'verify' ? 'Verifikasi Kode OTP' : 'Masuk sebagai Anggota' ?>
                            </h2>
                            <?php if (($member_step ?? 'request') === 'verify'): ?>
                                <p class="mt-1 text-xs leading-relaxed text-slate-500 sm:text-sm dark:text-slate-400">
                                    Masukkan enam digit kode yang dikirim ke <?= esc($masked_phone ?? 'nomor WhatsApp Anda') ?>.
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if (! empty($otp_success)): ?>
                            <div role="alert" class="mb-4 flex items-start justify-between gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3.5 text-xs text-blue-800 transition-opacity duration-200 sm:text-sm dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300" data-login-alert>
                                <div class="flex items-start gap-2.5 grow">
                                    <i data-lucide="message-circle-check" class="mt-0.5 size-4 shrink-0"></i>
                                    <div class="leading-relaxed"><?= esc($otp_success) ?></div>
                                </div>
                                <button type="button" class="shrink-0 -me-1 -mt-1 p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 transition rounded-lg cursor-pointer" aria-label="Tutup notifikasi" data-alert-dismiss>
                                    <i data-lucide="x" class="size-4"></i>
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if (($member_step ?? 'request') === 'verify'): ?>
                            <form action="<?= base_url('login/anggota/verifikasi') ?>" method="POST" data-login-form>
                                <?= csrf_field() ?>
                                <label class="block text-center text-xs font-semibold text-slate-700 sm:text-sm dark:text-slate-300" for="member-otp-1">
                                    Kode OTP
                                </label>
                                <div class="mt-2 mx-auto grid max-w-[320px] grid-cols-6 gap-2 sm:max-w-[340px] sm:gap-2.5" data-otp-group>
                                    <?php for ($digit = 1; $digit <= 6; $digit++): ?>
                                        <input type="text" class="otp-input" id="member-otp-<?= $digit ?>"
                                            inputmode="numeric" maxlength="1" data-otp-input
                                            <?= $digit === 1 ? 'autocomplete="one-time-code" autofocus' : '' ?>
                                            aria-label="Digit ke-<?= $digit ?>" <?= $digit === 1 ? 'required' : '' ?> />
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" id="member-otp" name="otp" required />
                                <button type="submit" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition dark:focus:ring-offset-slate-800 disabled:pointer-events-none disabled:opacity-60" data-login-button
                                    data-loading-label="Memverifikasi...">
                                    <i data-lucide="shield-check" class="size-4 shrink-0"></i>
                                    <span>Verifikasi dan Masuk</span>
                                </button>
                            </form>

                            <form action="<?= base_url('login/anggota/kirim-ulang') ?>" method="POST" class="mt-3" data-resend-form>
                                <?= csrf_field() ?>
                                <button type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-xl px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition dark:text-slate-400 dark:hover:bg-slate-700/50 dark:hover:text-slate-200 disabled:opacity-50"
                                    data-resend-button
                                    data-retry-after="<?= (int) ($retry_after ?? 0) ?>">
                                    Kirim ulang kode
                                </button>
                            </form>
                            <form action="<?= base_url('login/anggota/reset') ?>" method="POST" class="mt-1">
                                <?= csrf_field() ?>
                                <button type="submit" class="inline-flex w-full items-center justify-center py-1.5 text-xs font-medium text-blue-600 hover:text-blue-700 hover:underline transition dark:text-blue-400 dark:hover:text-blue-300">
                                    Gunakan nomor lain
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="<?= base_url('login/anggota') ?>" method="POST" data-login-form>
                                <?= csrf_field() ?>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 sm:text-sm dark:text-slate-300" for="member-phone">
                                        Nomor WhatsApp
                                    </label>
                                    <div class="mt-1.5 flex rounded-xl border border-slate-200 bg-white shadow-xs focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900">
                                        <span class="inline-flex items-center rounded-s-xl border-e border-slate-200 bg-slate-50 px-3.5 text-sm font-semibold text-slate-500 select-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">+62</span>
                                        <input type="tel" class="block w-full rounded-e-xl border-0 bg-transparent px-3.5 py-2.5 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:outline-none dark:text-slate-100 dark:placeholder:text-slate-500"
                                            id="member-phone" name="no_wa"
                                            value="<?= esc($old_phone ?? '') ?>" placeholder="8123456789"
                                            inputmode="numeric" pattern="8[0-9]{7,11}" minlength="8" maxlength="12"
                                            autocomplete="tel" data-digits-only data-max-digits="12" required />
                                    </div>
                                </div>
                                <button type="submit" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition dark:focus:ring-offset-slate-800 disabled:pointer-events-none disabled:opacity-60" data-login-button
                                    data-loading-label="Mengirim kode...">
                                    <i data-lucide="message-circle" class="size-4 shrink-0"></i>
                                    <span>Kirim Kode OTP</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div data-login-panel="admin" class="<?= $activeAccess === 'admin' ? '' : 'hidden' ?>">
                        <div class="mb-4 text-center">
                            <h2 class="text-sm font-bold text-slate-800 sm:text-base dark:text-slate-100">Masuk sebagai Admin</h2>
                        </div>

                        <form action="<?= base_url('login/admin') ?>" method="POST" data-login-form class="space-y-4">
                            <?= csrf_field() ?>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 sm:text-sm dark:text-slate-300" for="admin-username">
                                    Username
                                </label>
                                <input type="text" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-800 placeholder:text-slate-400 shadow-xs focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500" id="admin-username"
                                    name="username" value="<?= esc($old_username ?? '') ?>"
                                    placeholder="Masukkan username" autocomplete="username" required />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 sm:text-sm dark:text-slate-300" for="admin-password">
                                    Password
                                </label>
                                <div class="relative mt-1.5">
                                    <input type="password" class="block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pe-12 text-sm font-medium text-slate-800 placeholder:text-slate-400 shadow-xs focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500" id="admin-password"
                                        name="password" placeholder="Masukkan password"
                                        autocomplete="current-password" required />
                                    <button type="button"
                                        class="absolute inset-y-0 end-0 flex w-11 items-center justify-center rounded-e-xl text-slate-400 hover:text-slate-600 transition dark:text-slate-400 dark:hover:text-slate-200"
                                        data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                                        <i data-lucide="eye" class="size-4" data-password-icon-show></i>
                                        <i data-lucide="eye-off" class="size-4 hidden" data-password-icon-hide></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition dark:focus:ring-offset-slate-800 disabled:pointer-events-none disabled:opacity-60" data-login-button>
                                <i data-lucide="shield-check" class="size-4 shrink-0"></i>
                                <span>Masuk sebagai Admin</span>
                            </button>
                        </form>
                    </div>

                </div>
            </section>

            <p class="mt-5 text-center text-xs text-slate-500 dark:text-slate-400">
                &copy; <?= date('Y') ?> Sekretariat DPRD Provinsi Sulawesi Tengah. All rights reserved.
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
                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('dprd-admin-theme', theme);
                syncThemeLabel(theme === 'dark');
            });

            const tabs = document.querySelectorAll('[data-login-tab]');
            const panels = document.querySelectorAll('[data-login-panel]');

            function selectAccess(access) {
                tabs.forEach(function (tab) {
                    const isCurrent = tab.dataset.loginTab === access;
                    tab.checked = isCurrent;
                    const label = tab.closest('label');
                    if (label) {
                        if (isCurrent) {
                            label.classList.add('bg-white', 'text-blue-600', 'shadow-xs', 'dark:bg-slate-800', 'dark:text-blue-400');
                            label.classList.remove('text-slate-600', 'hover:text-slate-900', 'dark:text-slate-400', 'dark:hover:text-slate-200');
                        } else {
                            label.classList.remove('bg-white', 'text-blue-600', 'shadow-xs', 'dark:bg-slate-800', 'dark:text-blue-400');
                            label.classList.add('text-slate-600', 'hover:text-slate-900', 'dark:text-slate-400', 'dark:hover:text-slate-200');
                        }
                    }
                });
                panels.forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.dataset.loginPanel !== access);
                });

                document.querySelectorAll('[data-login-alert]').forEach(function (alert) {
                    if (alert.dataset.alertScope && alert.dataset.alertScope !== access) {
                        alert.classList.add('hidden');
                    } else {
                        alert.classList.remove('hidden');
                    }
                });

                const url = new URL(window.location.href);
                url.searchParams.set('akses', access);
                window.history.replaceState({}, '', url);
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('change', function () {
                    if (tab.checked) selectAccess(tab.dataset.loginTab);
                });
            });

            document.querySelectorAll('[data-alert-dismiss]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const alert = button.closest('[data-login-alert]');
                    if (alert) {
                        alert.classList.add('opacity-0');
                        window.setTimeout(function () { alert.remove(); }, 200);
                    }
                });
            });

            const adminCountdown = document.getElementById('admin-countdown');
            if (adminCountdown) {
                let secondsLeft = Number.parseInt(adminCountdown.dataset.adminRetryAfter || '0', 10);
                const adminBtn = document.querySelector('[data-login-panel="admin"] [data-login-button]');
                if (secondsLeft > 0 && adminBtn) {
                    adminBtn.disabled = true;
                    const timer = window.setInterval(function () {
                        secondsLeft -= 1;
                        if (secondsLeft <= 0) {
                            window.clearInterval(timer);
                            const wrap = document.querySelector('[data-admin-countdown-wrap]');
                            if (wrap) wrap.remove();
                            const msg = document.querySelector('[data-alert-message]');
                            if (msg) msg.textContent = 'Batas waktu tunggu selesai. Silakan coba masuk kembali.';
                            adminBtn.disabled = false;
                        } else {
                            adminCountdown.textContent = String(secondsLeft);
                        }
                    }, 1000);
                }
            }

            document.querySelectorAll('[data-login-form]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    const button = form.querySelector('[data-login-button]');
                    if (!button) return;
                    button.disabled = true;
                    const label = button.dataset.loadingLabel || 'Memverifikasi...';
                    button.innerHTML = '<svg class="size-4 animate-spin motion-reduce:animate-none shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>' + label + '</span>';
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

            const passwordToggle = document.querySelector('[data-password-toggle]');
            if (passwordToggle) {
                const passwordInput = document.getElementById('admin-password');
                const showIcon = passwordToggle.querySelector('[data-password-icon-show]');
                const hideIcon = passwordToggle.querySelector('[data-password-icon-hide]');

                passwordToggle.addEventListener('click', function () {
                    const reveal = passwordInput.type === 'password';
                    passwordInput.type = reveal ? 'text' : 'password';
                    showIcon.classList.toggle('hidden', reveal);
                    hideIcon.classList.toggle('hidden', !reveal);
                    passwordToggle.setAttribute('aria-pressed', String(reveal));
                    passwordToggle.setAttribute('aria-label', reveal ? 'Sembunyikan password' : 'Tampilkan password');
                    passwordInput.focus({ preventScroll: true });
                });
            }

            const otpGroup = document.querySelector('[data-otp-group]');
            if (otpGroup) {
                const boxes = Array.from(otpGroup.querySelectorAll('[data-otp-input]'));
                const hiddenOtp = document.getElementById('member-otp');
                const digitsOf = function (value) {
                    return value.replace(/[^0-9]/g, '');
                };

                const syncHiddenOtp = function () {
                    hiddenOtp.value = boxes.map(function (box) { return box.value; }).join('');
                };

                const focusBox = function (index) {
                    (boxes[index] || boxes[boxes.length - 1]).focus();
                };

                const fillDigits = function (digits, startIndex) {
                    let index = startIndex;
                    digits.split('').every(function (digit) {
                        if (index >= boxes.length) return false;
                        boxes[index].value = digit;
                        index += 1;
                        return true;
                    });
                    syncHiddenOtp();
                    focusBox(Math.min(index, boxes.length - 1));
                };

                const firstEmptyIndex = function () {
                    return boxes.findIndex(function (box) { return box.value === ''; });
                };

                boxes.forEach(function (box, index) {
                    // Digits must be entered in order: focusing a box beyond the
                    // first empty one is redirected to that empty box.
                    box.addEventListener('focus', function () {
                        const empty = firstEmptyIndex();
                        if (empty !== -1 && index > empty) {
                            boxes[empty].focus();
                        }
                    });

                    box.addEventListener('input', function () {
                        const digits = digitsOf(box.value);
                        if (digits.length > 1) {
                            box.value = '';
                            fillDigits(digits, index);
                            return;
                        }
                        box.value = digits;
                        syncHiddenOtp();
                        if (digits !== '' && index < boxes.length - 1) focusBox(index + 1);
                    });

                    box.addEventListener('keydown', function (event) {
                        if (event.key === 'Backspace' && box.value === '' && index > 0) {
                            boxes[index - 1].value = '';
                            syncHiddenOtp();
                            focusBox(index - 1);
                            event.preventDefault();
                            return;
                        }
                        if (event.key === 'ArrowLeft' && index > 0) {
                            focusBox(index - 1);
                            event.preventDefault();
                        } else if (event.key === 'ArrowRight' && index < boxes.length - 1) {
                            focusBox(index + 1);
                            event.preventDefault();
                        } else if (event.key.length === 1 && ! /[0-9]/.test(event.key)) {
                            event.preventDefault();
                        }
                    });

                    box.addEventListener('paste', function (event) {
                        const digits = digitsOf(event.clipboardData.getData('text'));
                        if (digits === '') return;
                        event.preventDefault();
                        fillDigits(digits.slice(0, boxes.length - index), index);
                    });
                });

                otpGroup.closest('form').addEventListener('submit', function (event) {
                    syncHiddenOtp();
                    if (hiddenOtp.value.length < boxes.length) {
                        event.preventDefault();
                        const firstEmpty = boxes.find(function (box) { return box.value === ''; });
                        (firstEmpty || boxes[boxes.length - 1]).focus();
                    }
                });
            }

            const countdownMarkup = function (remaining) {
                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const seconds = remaining % 60;
                const unit = function (value, label) {
                    const formatted = String(value).padStart(2, '0');
                    return '<span class="font-mono font-semibold" aria-live="polite" aria-label="' + value + ' ' + label + '">' + formatted + '</span>';
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
