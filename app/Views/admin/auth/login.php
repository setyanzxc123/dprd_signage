<?php
$adminCssVersion = is_file(FCPATH . 'assets/css/admin.css') ? filemtime(FCPATH . 'assets/css/admin.css') : time();
$fontVersion     = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$vueVersion      = is_file(FCPATH . 'assets/vendor/vue/vue.global.prod.js') ? filemtime(FCPATH . 'assets/vendor/vue/vue.global.prod.js') : time();
$lucideVersion   = is_file(FCPATH . 'assets/vendor/lucide/lucide.min.js') ? filemtime(FCPATH . 'assets/vendor/lucide/lucide.min.js') : time();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script>
        (() => {
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (prefersDark) {
                document.documentElement.classList.add('dark');
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <title>Login - Panel Admin Signage DPRD Sulteng</title>
    <meta name="robots" content="noindex, nofollow" />

    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-400-normal.woff2') ?>" as="font" type="font/woff2" crossorigin />
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-700-normal.woff2') ?>" as="font" type="font/woff2" crossorigin />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet" />
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-base-200 to-base-200 p-5">

    <div class="w-full max-w-[420px]">

        <!-- Brand -->
        <div class="flex flex-col items-center text-center mb-7">
            <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>" alt="Logo DPRD Provinsi Sulawesi Tengah"
                class="w-[clamp(60px,12vw,96px)] h-[clamp(60px,12vw,96px)] object-contain rounded-2xl border border-base-300 bg-white mb-3 block" />
            <h1 class="text-lg font-black text-base-content leading-tight m-0">
                Sistem Notifikasi Rapat &amp; Signage DPRD Sulawesi Tengah
            </h1>
        </div>

        <!-- Card Login -->
        <div class="card bg-base-100 border border-base-200/80 shadow-xl">
            <div class="card-body p-8">
                <h2 class="text-base font-bold text-base-content mb-5">Masuk ke Panel Admin</h2>

                <!-- Flash Error -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-error text-xs py-2 px-3 mb-3 flex gap-2">
                        <i data-lucide="triangle-alert" class="w-4 h-4 shrink-0"></i>
                        <span><?= session()->getFlashdata('error') ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('admin/login') ?>" method="POST" id="login-form">
                    <?= csrf_field() ?>

                    <!-- Username -->
                    <div class="mb-3">
                        <label class="label-text font-bold text-sm mb-1 block" for="username">Username</label>
                        <label class="input input-bordered flex items-center gap-2 w-full">
                            <i data-lucide="user" class="w-4 h-4 opacity-70 shrink-0"></i>
                            <input type="text" class="grow" id="username" name="username"
                                placeholder="Masukkan username" autocomplete="username" required />
                        </label>
                    </div>

                    <!-- Password -->
                    <div class="mb-5">
                        <label class="label-text font-bold text-sm mb-1 block" for="password">Password</label>
                        <label class="input input-bordered flex items-center gap-2 w-full">
                            <i data-lucide="lock" class="w-4 h-4 opacity-70 shrink-0"></i>
                            <input type="password" class="grow" id="password" name="password" placeholder="********"
                                autocomplete="current-password" required />
                            <button type="button" class="btn btn-ghost btn-xs btn-circle text-base-content/60" id="btn-toggle-pwd"
                                aria-label="Toggle password visibility">
                                <i data-lucide="eye" id="eye-icon" class="w-4 h-4"></i>
                            </button>
                        </label>
                    </div>

                    <!-- Tombol Login -->
                    <button type="submit" class="btn btn-primary w-full" id="login-button">
                        <i data-lucide="log-in" class="w-4 h-4"></i>Masuk
                    </button>

                </form>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center mt-6 text-xs text-base-content/50">
            &copy; <?= date('Y') ?> DPRD Provinsi Sulawesi Tengah
        </p>

    </div><!-- /.max-w -->

    <div id="login-vue-controller" hidden></div>

    <script src="<?= base_url('assets/vendor/vue/vue.global.prod.js?v=' . $vueVersion) ?>"></script>
    <script src="<?= base_url('assets/vendor/lucide/lucide.min.js?v=' . $lucideVersion) ?>"></script>
    <script>
        window.renderAdminIcons = function () {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        };

        function initLoginInteractions() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('btn-toggle-pwd');
            const loginForm = document.getElementById('login-form');
            const loginButton = document.getElementById('login-button');
            let passwordVisible = false;

            toggleButton?.addEventListener('click', function () {
                passwordVisible = !passwordVisible;
                passwordInput.type = passwordVisible ? 'text' : 'password';
                toggleButton.innerHTML = '<i data-lucide="' + (passwordVisible ? 'eye-off' : 'eye') + '" id="eye-icon"></i>';
                window.renderAdminIcons();
            });

            loginForm?.addEventListener('submit', function () {
                if (!loginButton) return;
                loginButton.disabled = true;
                // Paksa warna primary tetap — browser/daisyUI v5 disabled state override warna ke putih
                loginButton.style.backgroundColor = 'var(--color-primary)';
                loginButton.style.color = 'var(--color-primary-content)';
                loginButton.style.borderColor = 'var(--color-primary)';
                loginButton.style.opacity = '0.8';
                loginButton.innerHTML = '<span class="loading loading-spinner loading-xs"></span>Memverifikasi...';
            });

            window.renderAdminIcons();
        }

        if (window.Vue && document.getElementById('login-vue-controller')) {
            window.Vue.createApp({ mounted: initLoginInteractions }).mount('#login-vue-controller');
        } else {
            initLoginInteractions();
        }
    </script>
</body>

</html>
