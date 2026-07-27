<?php
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$cssVersion = is_file(FCPATH . 'assets/css/agenda.css') ? filemtime(FCPATH . 'assets/css/agenda.css') : time();
$isMember = is_array($member ?? null);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Jadwal Banmus - DPRD Provinsi Sulawesi Tengah</title>
    <meta name="description" content="Proyeksi agenda berdasarkan hasil Badan Musyawarah DPRD Provinsi Sulawesi Tengah." />
    <link rel="icon" type="image/jpeg" href="<?= esc($logoUrl) ?>" />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <script {csp-script-nonce}>
        (() => {
            const stored = localStorage.getItem('dprd-admin-theme');
            const theme = stored === 'dark' ? 'dark' : 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link href="<?= base_url('assets/css/agenda.css?v=' . $cssVersion) ?>" rel="stylesheet" />
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased">
    <header class="border-b border-base-300 bg-base-100">
        <div class="navbar mx-auto min-h-20 w-[min(960px,calc(100%-20px))] gap-3 px-0 sm:w-[min(960px,calc(100%-32px))]">
            <a class="navbar-start min-w-0 flex-1 gap-3" href="<?= esc($portalUrl) ?>" aria-label="Kembali ke agenda DPRD">
                <img class="h-12 w-12 shrink-0 rounded-lg border border-base-300 bg-white object-contain" src="<?= esc($logoUrl) ?>" alt="Logo DPRD" />
                <span class="min-w-0">
                    <span class="block truncate text-base font-black sm:text-xl">DPRD Provinsi Sulawesi Tengah</span>
                    <span class="block truncate text-xs font-semibold text-base-content/55 sm:text-sm"><?= $isMember ? 'Portal Agenda Anggota' : 'Portal Agenda Publik' ?></span>
                </span>
            </a>

            <?php if ($isMember): ?>
                <form class="navbar-end w-auto" action="<?= base_url('anggota/logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline btn-sm" type="submit">Keluar</button>
                </form>
            <?php else: ?>
                <a class="btn btn-primary btn-sm" href="<?= base_url('login?akses=anggota') ?>">Masuk Anggota</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="mx-auto w-[min(960px,calc(100%-20px))] py-5 sm:w-[min(960px,calc(100%-32px))] sm:py-8">
        <a class="btn btn-ghost btn-sm mb-4 -ml-2" href="<?= esc($portalUrl) ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Kembali ke Agenda
        </a>

        <section class="card card-border bg-base-100 shadow-sm">
            <div class="card-body gap-5">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-widest text-base-content/45">Proyeksi hasil SK Banmus</p>
                    <h1 class="card-title mt-1 text-2xl font-black uppercase sm:text-3xl">Jadwal Banmus</h1>
                </div>

                <div role="alert" class="alert alert-info alert-soft">
                    <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01" stroke-linecap="round"/></svg>
                    <span>Halaman ini disiapkan untuk proyeksi agenda dari SK Banmus. Proyeksi dapat hanya memiliki bulan dan belum mempunyai tanggal pasti.</span>
                </div>

                <div class="grid min-h-64 place-items-center rounded-box border border-dashed border-base-300 bg-base-200 p-8 text-center">
                    <div>
                        <svg class="mx-auto text-base-content/30" viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round"/></svg>
                        <h2 class="mt-4 text-lg font-extrabold">Belum ada data proyeksi Banmus</h2>
                        <p class="mt-1 max-w-md text-sm font-semibold leading-6 text-base-content/55">Data akan ditampilkan setelah struktur periode, status proyeksi, dan pengelolaannya diselesaikan pada fase berikutnya.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
