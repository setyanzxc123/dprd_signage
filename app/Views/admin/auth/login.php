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

    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet" />

    <style>
        /* Login */
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--od-bg) 0%, color-mix(in srgb, var(--color-primary) 8%, var(--od-bg)) 100%);
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }

        /* Logo di atas card */
        .login-brand {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-brand .brand-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 12px;
        }

        .login-brand h1 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--od-fg);
            margin: 0 0 4px;
        }

        .login-brand p {
            font-size: .82rem;
            color: var(--od-muted);
            margin: 0;
        }

        /* Card form login */
        .login-card {
            background: var(--od-surface);
            border: 1px solid var(--od-border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: var(--od-shadow-lg);
            text-align: left;
        }

        .login-card h2 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--od-fg);
        }

        /* Footer bawah card */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: .75rem;
            color: var(--od-muted);
        }

        /* Logo gambar di halaman login */
        .brand-logo-img {
            width: clamp(60px, 12vw, 100px);
            height: clamp(60px, 12vw, 100px);
            object-fit: contain;
            border-radius: clamp(10px, 2vw, 16px);
            margin-bottom: 12px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body>
    <div class="login-wrapper">

        <!-- Brand -->
        <div class="login-brand">
            <!-- Logo: backend isi src dengan path dari DB/settings -->
            <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>" alt="Logo DPRD Provinsi Sulawesi Tengah" class="brand-logo-img" />
            <h1>Sistem Notifikasi Rapat &amp; Signage DPRD Sulawesi Tengah</h1>
        </div>

        <!-- Card Login -->
        <div class="card bg-base-100 shadow-xl border border-base-200/80 p-8 login-card">
            <h2>Masuk ke Panel Admin</h2>

            <!-- Flash Error -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-error text-xs py-2 px-3 mb-3 text-error-content flex gap-2">
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
                <div class="mb-4">
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
                    <i data-lucide="log-in" class="w-4 h-4 mr-2"></i>Masuk
                </button>

            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            &copy; <?= date('Y') ?> DPRD Provinsi Sulawesi Tengah
        </div>

    </div><!-- /.login-wrapper -->

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
                loginButton.innerHTML = '<span class="loading loading-spinner loading-xs mr-2"></span>Memverifikasi...';
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
