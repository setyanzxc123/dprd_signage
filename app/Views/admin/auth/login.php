<?php
$adminCssVersion = is_file(FCPATH . 'assets/css/admin.css') ? filemtime(FCPATH . 'assets/css/admin.css') : time();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Panel Admin Signage DPRD Sulteng</title>
    <meta name="robots" content="noindex, nofollow" />

    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet" />

    <style>
        /* Login */
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f1f3d 0%, #1a3360 50%, #0f1f3d 100%);
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
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 4px;
        }

        .login-brand p {
            font-size: .8rem;
            color: rgba(255, 255, 255, .5);
            margin: 0;
        }

        /* Card form login */
        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .35);
        }

        .login-card h2 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--text-primary);
        }

        .login-card .login-sub {
            font-size: .82rem;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        /* Input dengan ikon di dalam */
        .input-icon-wrap {
            position: relative;
        }

        .input-icon-wrap .ta-input {
            padding-left: 42px;
        }

        .input-icon-wrap .input-icon {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: .95rem;
            pointer-events: none;
        }

        /* Tombol toggle show/hide password */
        .ta-eye-button {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            font-size: .95rem;
        }

        .ta-eye-button:hover {
            color: var(--text-primary);
        }

        /* Tombol login */
        .ta-login-button {
            width: 100%;
            padding: 11px;
            font-weight: 600;
            font-size: .95rem;
            letter-spacing: .3px;
            border-radius: var(--radius-btn);
        }

        /* Footer bawah card */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: .75rem;
            color: rgba(255, 255, 255, .35);
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
            <h1>Signage DPRD Sulteng</h1>
            <p>Sistem Informasi Digital Signage &amp; Notifikasi</p>
        </div>

        <!-- Card Login -->
        <div class="login-card">
            <h2>Masuk ke Panel Admin</h2>
            <p class="login-sub">Masukkan kredensial Anda untuk melanjutkan</p>

            <!-- Flash Error -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="ta-alert ta-alert-danger ta-alert-sm py-2 px-3 mb-3">
                    <i data-lucide="triangle-alert" class="mr-1"></i>
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('admin/login') ?>" method="POST" id="login-form">
                <?= csrf_field() ?>

                <!-- Username -->
                <div class="mb-3">
                    <label class="ta-label font-semibold" for="username">Username</label>
                    <div class="input-icon-wrap">
                        <i data-lucide="user" class="input-icon"></i>
                        <input type="text" class="ta-input" id="username" name="username"
                            placeholder="Masukkan username" autocomplete="username" required />
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label class="ta-label font-semibold" for="password">Password</label>
                    <div class="input-icon-wrap">
                        <i data-lucide="lock" class="input-icon"></i>
                        <input type="password" class="ta-input" id="password" name="password" placeholder="********"
                            autocomplete="current-password" required />
                        <button type="button" class="ta-eye-button" id="btn-toggle-pwd"
                            aria-label="Toggle password visibility">
                            <i data-lucide="eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <!-- Tombol Login -->
                <button type="submit" class="ta-btn ta-btn-primary ta-login-button" id="ta-login-button">
                    <i data-lucide="log-in" class="mr-2"></i>Masuk
                </button>

            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            &copy; <?= date('Y') ?> DPRD Provinsi Sulawesi Tengah
        </div>

    </div><!-- /.login-wrapper -->

    <div id="login-vue-controller" hidden></div>

    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
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
            const loginButton = document.getElementById('ta-login-button');
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
                loginButton.innerHTML = '<span class="ta-spinner ta-spinner-sm mr-2"></span>Memverifikasi...';
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
