<?php
$adminCssVersion = is_file(FCPATH . 'assets/css/admin.css') ? filemtime(FCPATH . 'assets/css/admin.css') : time();
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$lucideVersion = is_file(FCPATH . 'assets/vendor/lucide/lucide.min.js') ? filemtime(FCPATH . 'assets/vendor/lucide/lucide.min.js') : time();
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($pageTitle ?? 'Portal Anggota DPRD') ?></title>
    <meta name="robots" content="noindex, nofollow" />
    <link rel="icon" type="image/jpeg" href="<?= base_url('assets/images/logo_dprd.jpg') ?>" />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/admin.css?v=' . $adminCssVersion) ?>" rel="stylesheet" />
</head>

<body class="min-h-screen bg-base-200">
    <header class="border-b border-base-300 bg-base-100">
        <div class="mx-auto flex w-full max-w-5xl items-center justify-between gap-4 px-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
                <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>"
                    alt="Logo DPRD" class="h-11 w-11 rounded-xl border border-base-300 object-contain" />
                <div class="min-w-0">
                    <div class="truncate font-bold text-base-content">Portal Anggota DPRD</div>
                    <div class="truncate text-xs text-base-content/60">Sulawesi Tengah</div>
                </div>
            </div>

            <form action="<?= base_url('anggota/logout') ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost btn-sm">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                    <span class="hidden sm:inline">Keluar</span>
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto w-full max-w-5xl px-4 py-8">
        <section class="card card-border bg-base-100 shadow-sm">
            <div class="card-body">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm text-base-content/60">Selamat datang,</p>
                        <h1 class="text-2xl font-black text-base-content"><?= esc($member['name']) ?></h1>
                        <p class="mt-1 text-sm text-base-content/65">
                            <?= esc($member['jabatan'] ?: 'Anggota DPRD') ?>
                            <?php if (! empty($member['fraksi'])): ?>
                                &middot; <?= esc($member['fraksi']) ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <a href="<?= base_url('signage') ?>" class="btn btn-primary">
                        <i data-lucide="calendar-days" class="h-4 w-4"></i>
                        Lihat Jadwal
                    </a>
                </div>
            </div>
        </section>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <section class="card card-border bg-base-100">
                <div class="card-body">
                    <h2 class="card-title text-base">
                        <i data-lucide="users" class="h-5 w-5"></i>
                        Kelompok Anda
                    </h2>

                    <?php if ($units !== []): ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($units as $unit): ?>
                                <span class="badge badge-neutral"><?= esc($unit['nama']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-base-content/60">Belum ada kelompok peserta yang ditetapkan.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card card-border bg-base-100">
                <div class="card-body">
                    <h2 class="card-title text-base">
                        <i data-lucide="shield-check" class="h-5 w-5"></i>
                        Status Akses
                    </h2>
                    <p class="text-sm text-base-content/65">
                        Akun anggota aktif. Jadwal Saya dan autentikasi OTP akan ditambahkan pada tahap berikutnya.
                    </p>
                </div>
            </section>
        </div>
    </main>

    <script src="<?= base_url('assets/vendor/lucide/lucide.min.js?v=' . $lucideVersion) ?>"></script>
    <script {csp-script-nonce}>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) window.lucide.createIcons();
        });
    </script>
</body>

</html>
