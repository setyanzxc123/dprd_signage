<?php
$adminCssVersion = is_file(FCPATH . 'assets/css/admin.css') ? filemtime(FCPATH . 'assets/css/admin.css') : time();
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$lucideVersion = is_file(FCPATH . 'assets/vendor/lucide/lucide.min.js') ? filemtime(FCPATH . 'assets/vendor/lucide/lucide.min.js') : time();
$logoVersion = is_file(FCPATH . 'assets/images/logo_dprd.jpg') ? filemtime(FCPATH . 'assets/images/logo_dprd.jpg') : time();
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
    <link rel="icon" type="image/jpeg" href="<?= base_url('assets/images/logo_dprd.jpg?v=' . $logoVersion) ?>" />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet" />
</head>

<body class="min-h-screen overflow-x-hidden bg-base-200">
    <label class="inline-flex size-9 items-center justify-center rounded-xl border border-base-300 bg-base-100 text-base-content hover:bg-base-200 shadow-sm cursor-pointer transition fixed right-4 top-4 z-10"
        title="Ganti tema" aria-label="Ganti tema" data-theme-toggle>
        <input type="checkbox" value="dark" class="hidden" data-theme-toggle-input />
        <i class="theme-icon-sun size-4" data-lucide="sun"></i>
        <i class="theme-icon-moon size-4" data-lucide="moon"></i>
    </label>

    <main class="flex min-h-screen items-center justify-center px-3 py-5 min-[380px]:px-4 min-[380px]:py-8">
        <div class="w-full max-w-md">
            <header class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 min-[380px]:h-20 min-[380px]:w-20 items-center justify-center overflow-hidden rounded-full bg-white p-1 ring-2 ring-emerald-500/40 shadow-md">
                    <img src="<?= base_url('assets/images/logo_dprd.jpg?v=' . $logoVersion) ?>"
                        alt="Logo DPRD Provinsi Sulawesi Tengah"
                        class="h-full w-full rounded-full object-contain" />
                </div>
                <h1 class="text-xl font-black leading-normal text-base-content min-[380px]:text-2xl">
                    Sistem Informasi Agenda dan Jadwal Rapat DPRD
                </h1>
                <p class="mt-1 text-xs font-semibold tracking-widest text-base-content/70 min-[380px]:text-sm">
                    PROVINSI SULAWESI TENGAH
                </p>
            </header>

            <section class="card card-border bg-base-100 shadow-xl">
                <div class="card-body max-[379px]:p-4">
                    <fieldset class="fieldset gap-2">
                        <legend class="fieldset-legend">Jenis Akses</legend>
                        <div class="grid grid-cols-1 gap-2 min-[380px]:grid-cols-2">
                            <label class="label min-w-0 cursor-pointer justify-start gap-2 rounded-lg border border-base-300 px-3 py-2 has-checked:border-primary has-checked:bg-primary/10 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-primary"
                                for="akses-anggota">
                                <input type="radio" name="login_access" id="akses-anggota" value="anggota"
                                    class="sr-only" data-login-tab="anggota"
                                    <?= $activeAccess === 'anggota' ? 'checked' : '' ?> />
                                <i data-lucide="users" class="h-4 w-4 shrink-0"></i>
                                <span class="min-w-0 text-sm font-semibold">Anggota DPRD</span>
                            </label>
                            <label class="label min-w-0 cursor-pointer justify-start gap-2 rounded-lg border border-base-300 px-3 py-2 has-checked:border-primary has-checked:bg-primary/10 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-primary"
                                for="akses-admin">
                                <input type="radio" name="login_access" id="akses-admin" value="admin"
                                    class="sr-only" data-login-tab="admin"
                                    <?= $activeAccess === 'admin' ? 'checked' : '' ?> />
                                <i data-lucide="settings-2" class="h-4 w-4 shrink-0"></i>
                                <span class="min-w-0 text-sm font-semibold">Admin / Operator</span>
                            </label>
                        </div>
                    </fieldset>

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

                    <div data-login-panel="anggota" class="card-body max-[379px]:px-0 max-[379px]:pb-0 <?= $activeAccess === 'anggota' ? '' : 'hidden' ?>">
                        <div class="mb-4 text-center">
                            <h2 class="font-bold text-base-content">
                                <?= ($member_step ?? 'request') === 'verify' ? 'Verifikasi Kode OTP' : 'Masuk sebagai Anggota' ?>
                            </h2>
                            <?php if (($member_step ?? 'request') === 'verify'): ?>
                                <p class="mt-1 text-sm leading-relaxed text-base-content/60">
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
                                <label class="block text-sm font-semibold text-base-content" for="member-otp-1">
                                    Kode OTP
                                </label>
                                <div class="mt-1 grid grid-cols-6 gap-2 min-[380px]:gap-3" data-otp-group>
                                    <?php for ($digit = 1; $digit <= 6; $digit++): ?>
                                        <input type="text" class="otp-input" id="member-otp-<?= $digit ?>"
                                            inputmode="numeric" maxlength="1" data-otp-input
                                            <?= $digit === 1 ? 'autocomplete="one-time-code" autofocus' : '' ?>
                                            aria-label="Digit ke-<?= $digit ?>" <?= $digit === 1 ? 'required' : '' ?> />
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" id="member-otp" name="otp" required />
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
                                <label class="input mt-1 w-full flex items-center gap-2" for="member-phone">
                                    <span class="select-none text-sm font-semibold text-base-content/60">+62</span>
                                    <input type="tel" class="w-full grow border-0 bg-transparent p-0 text-sm font-medium text-base-content outline-none placeholder:text-base-content/40 focus:outline-none"
                                        id="member-phone" name="no_wa"
                                        value="<?= esc($old_phone ?? '') ?>" placeholder="8123456789"
                                        inputmode="numeric" pattern="8[0-9]{7,11}" minlength="8" maxlength="12"
                                        autocomplete="tel" data-digits-only data-max-digits="12" required />
                                </label>
                                <button type="submit" class="btn btn-primary btn-block mt-5" data-login-button
                                    data-loading-label="Mengirim kode...">
                                    <i data-lucide="message-circle" class="h-4 w-4"></i>
                                    Kirim Kode OTP
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div data-login-panel="admin" class="card-body max-[379px]:px-0 max-[379px]:pb-0 <?= $activeAccess === 'admin' ? '' : 'hidden' ?>">
                        <div class="mb-4 text-center">
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
                            <div class="relative mt-1">
                                <input type="password" class="input w-full pe-10" id="admin-password"
                                    name="password" placeholder="Masukkan password"
                                    autocomplete="current-password" required />
                                <button type="button"
                                    class="absolute inset-y-0 end-0 flex w-10 items-center justify-center rounded-lg text-base-content/60 hover:text-base-content transition"
                                    data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                                    <i data-lucide="eye" class="size-4" data-password-icon-show></i>
                                    <i data-lucide="eye-off" class="size-4 hidden" data-password-icon-hide></i>
                                </button>
                            </div>

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
                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('dprd-admin-theme', theme);
                syncThemeLabel(theme === 'dark');
            });

            const tabs = document.querySelectorAll('[data-login-tab]');
            const panels = document.querySelectorAll('[data-login-panel]');

            function selectAccess(access) {
                tabs.forEach(function (tab) {
                    tab.checked = tab.dataset.loginTab === access;
                });
                panels.forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.dataset.loginPanel !== access);
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
