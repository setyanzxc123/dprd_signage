<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Panel Admin Signage DPRD Sulteng</title>
    <meta name="robots" content="noindex, nofollow" />

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <!-- Tokens (untuk font Inter + variabel warna) -->
    <link href="<?= base_url('assets/css/admin/token.css') ?>" rel="stylesheet" />

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

        .input-icon-wrap .form-control {
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
        .btn-eye {
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

        .btn-eye:hover {
            color: var(--text-primary);
        }

        /* Tombol login */
        .btn-login {
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
                <div class="alert alert-danger alert-sm py-2 px-3 mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('admin/login') ?>" method="POST" id="login-form">
                <?= csrf_field() ?>

                <!-- Username -->
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="username">Username</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" class="form-control" id="username" name="username"
                            placeholder="Masukkan username" autocomplete="username" required />
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="password">Password</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="********"
                            autocomplete="current-password" required />
                        <button type="button" class="btn-eye" id="btn-toggle-pwd"
                            aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <!-- Tombol Login -->
                <button type="submit" class="btn btn-primary btn-login" id="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                </button>

            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            &copy; <?= date('Y') ?> DPRD Provinsi Sulawesi Tengah
        </div>

    </div><!-- /.login-wrapper -->

    <script>
        // Toggle show/hide password
        document.getElementById('btn-toggle-pwd')?.addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        });

        // Disable tombol saat submit (cegah double submit)
        document.getElementById('login-form')?.addEventListener('submit', function () {
            const btn = document.getElementById('btn-login');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memverifikasi...';
        });
    </script>
</body>

</html>
