<?php
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$cssVersion = is_file(FCPATH . 'assets/css/agenda.css') ? filemtime(FCPATH . 'assets/css/agenda.css') : time();
$logoVersion = is_file(FCPATH . 'assets/images/logo_dprd.png') ? filemtime(FCPATH . 'assets/images/logo_dprd.png') : time();
$prelineVersion = is_file(FCPATH . 'assets/vendor/preline/preline.js') ? filemtime(FCPATH . 'assets/vendor/preline/preline.js') : time();
$isMember = is_array($member ?? null);
$isAdmin = ! $isMember && ! empty($isAdmin);

$allBanmusItems = [];
if (! empty($documents)) {
    foreach ($documents as $doc) {
        foreach ($doc['items'] as $it) {
            $it['document_id'] = $doc['id'];
            $it['document_judul'] = $doc['judul'] ?: 'SK Banmus';
            $it['document_nomor_sk'] = $doc['nomor_sk'] ?? '';
            $it['document_semester'] = $doc['semester'] ?? '';
            $it['document_tahun'] = $doc['tahun'] ?? '';
            $allBanmusItems[] = $it;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Proyeksi Banmus - DPRD Provinsi Sulawesi Tengah</title>
    <meta name="description" content="Proyeksi kegiatan dan SK Badan Musyawarah DPRD Provinsi Sulawesi Tengah." />
    <link rel="icon" type="image/png" href="<?= esc($logoUrl) ?><?= str_contains($logoUrl, '?') ? '&' : '?' ?>v=<?= $logoVersion ?>" />
    <link rel="preload" as="image" href="<?= esc($logoUrl) ?><?= str_contains($logoUrl, '?') ? '&' : '?' ?>v=<?= $logoVersion ?>" />
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-400-normal.woff2') ?>" as="font" type="font/woff2" crossorigin />
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-600-normal.woff2') ?>" as="font" type="font/woff2" crossorigin />
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
<body class="min-h-screen overflow-x-hidden bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased">
    <header class="sticky top-0 z-50 border-b border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md shadow-xs">
        <div class="relative overflow-hidden">
            <div class="agenda-header-motif" aria-hidden="true"></div>
            <div class="relative z-10 mx-auto flex min-h-16 w-full items-center justify-between gap-3 px-3.5 py-2.5 sm:min-h-20 sm:px-6 xl:px-8">
                <a class="flex items-center gap-3 min-w-0 flex-1" href="<?= esc($portalUrl) ?>" aria-label="Kembali ke agenda DPRD">
                    <img class="h-12 w-12 shrink-0 object-contain sm:h-16 sm:w-16" width="64" height="64" src="<?= esc($logoUrl) ?><?= str_contains($logoUrl, '?') ? '&' : '?' ?>v=<?= $logoVersion ?>" alt="Logo DPRD Provinsi Sulawesi Tengah" />
                    <span class="min-w-0 leading-tight">
                        <span class="block truncate text-sm font-black uppercase tracking-[0.06em] text-slate-900 dark:text-white sm:text-[clamp(17px,1.08vw,22px)] sm:tracking-[0.08em]">
                            AGENDA DPRD
                        </span>
                        <span class="block truncate text-[10px] uppercase tracking-[0.06em] text-slate-500 dark:text-slate-400 sm:text-[clamp(12px,0.82vw,16px)] sm:tracking-[0.08em]">
                            Provinsi Sulawesi Tengah
                        </span>
                    </span>
                </a>

                <div class="flex items-center gap-2 shrink-0">
                    <div class="hidden xl:inline-flex items-center divide-x divide-slate-200 dark:divide-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-800/50 backdrop-blur-sm px-3.5 py-1.5 shadow-xs">
                        <div class="flex items-center gap-2.5 px-3 py-1">
                            <img data-weather-icon class="hidden h-7 w-7 object-contain" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="Ikon cuaca" />
                            <span data-weather-fallback class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                            <div class="text-left">
                                <span data-weather-temperature class="block text-sm font-bold text-slate-900 dark:text-white leading-tight">--°C</span>
                                <span data-weather-condition class="block max-w-28 truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">Memuat...</span>
                            </div>
                        </div>

                        <div class="hidden 2xl:block px-3 py-1 text-left">
                            <span data-weather-location class="block max-w-48 truncate text-xs font-bold text-slate-800 dark:text-slate-200">Sulawesi Tengah</span>
                            <span class="block text-[10px] text-slate-500 dark:text-slate-400">Kelembapan <span data-weather-humidity>--</span> · Angin <span data-weather-wind>--</span></span>
                        </div>

                        <div class="px-3.5 py-1 text-center">
                            <span data-header-day class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">—</span>
                            <span data-header-date class="block text-xs font-semibold text-slate-800 dark:text-slate-200">—</span>
                        </div>

                        <div class="px-3.5 py-1 text-center">
                            <span data-header-time class="block font-mono text-2xl font-black tabular-nums leading-none text-slate-900 dark:text-white">--:--:--</span>
                            <span class="block text-[9px] font-bold uppercase tracking-widest text-blue-700 dark:text-blue-300 mt-0.5">WITA</span>
                        </div>
                    </div>

                    <button class="inline-flex justify-center items-center size-10 sm:size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700/80 transition cursor-pointer" type="button" data-theme-toggle aria-label="Gunakan tema gelap">
                        <svg data-theme-dark-icon viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 15.5A8.5 8.5 0 0 1 8.5 4a7 7 0 1 0 11.5 11.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <svg data-theme-light-icon class="hidden" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v2m0 14v2M4.2 4.2l1.4 1.4m12.8 12.8 1.4 1.4M3 12h2m14 0h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" stroke-linecap="round"/></svg>
                    </button>

                    <?php if ($isMember): ?>
                        <div class="hs-dropdown relative inline-flex [--placement:bottom-right]">
                            <button id="hs-dropdown-member-profile" type="button" class="hs-dropdown-toggle inline-flex items-center justify-center size-10 sm:size-9 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 transition cursor-pointer" aria-haspopup="menu" aria-expanded="false" aria-label="Menu akun anggota dewan">
                                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </button>

                            <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 sm:min-w-64 bg-white dark:bg-slate-900 shadow-xl rounded-2xl p-2 border border-slate-200/80 dark:border-slate-800 z-50 mt-2" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-member-profile">
                                <div class="py-2 px-2.5 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl">
                                    <p class="text-xs font-extrabold text-slate-900 dark:text-white leading-snug"><?= esc((string) ($member['name'] ?? 'Anggota DPRD')) ?></p>
                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                        <?php if (! empty($member['komisi'])): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/20"><?= esc((string) $member['komisi']) ?></span>
                                        <?php endif; ?>
                                        <?php if (! empty($member['fraksi'])): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-200"><?= esc((string) $member['fraksi']) ?></span>
                                        <?php endif; ?>
                                        <?php if (empty($member['fraksi']) && empty($member['komisi'])): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-200"><?= esc((string) ($member['jabatan'] ?? 'Dewan')) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="pt-1.5">
                                    <form action="<?= base_url('anggota/logout') ?>" method="post">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="flex w-full items-center gap-x-2 py-2 px-2.5 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>
                                            </svg>
                                            <span>Keluar</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($isAdmin): ?>
                        <a class="inline-flex items-center gap-x-2 py-2 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700 transition" href="<?= base_url('admin/dashboard') ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                            <span class="hidden sm:inline">Panel Admin</span>
                        </a>
                    <?php else: ?>
                        <a class="inline-flex items-center gap-x-2 py-2 px-3.5 rounded-xl bg-slate-900 text-white hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-500 text-xs font-semibold shadow-xs transition" href="<?= base_url('login?akses=anggota') ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-4-4 5-5-5-5m5 5H3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span class="hidden sm:inline">Masuk Anggota</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/70 dark:bg-slate-900/50 xl:hidden px-3 py-1.5 text-center">
                <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-200 tabular-nums">
                    <span data-weather-temperature>--°C</span> · <span data-weather-condition>Memuat...</span> · <span data-header-short-date>--</span> · <span data-header-time>--:--:--</span> WITA
                </span>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl px-3 py-5 sm:px-6 sm:py-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
            <a class="inline-flex items-center gap-x-1.5 min-h-[38px] sm:min-h-0 py-2 sm:py-1.5 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition w-fit" href="<?= esc($portalUrl) ?>">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Kembali ke Agenda
            </a>

            <form class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 shadow-xs" action="<?= base_url('agenda/jadwal-banmus') ?>" method="get">
                <div class="min-w-0 sm:min-w-28 flex-1 sm:flex-initial">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-0.5">Tahun</label>
                    <select class="py-1.5 px-2.5 block w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 shadow-xs" name="tahun" aria-label="Pilih tahun Proyeksi Banmus">
                        <?php if ($availableYears === []): ?>
                            <option value="<?= (int) $selectedYear ?>"><?= (int) $selectedYear ?></option>
                        <?php else: ?>
                            <?php foreach ($availableYears as $year): ?>
                                <option value="<?= (int) $year ?>" <?= (int) $selectedYear === (int) $year ? 'selected' : '' ?>>
                                    <?= (int) $year ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="min-w-0 sm:min-w-36 flex-1 sm:flex-initial">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-0.5">Semester</label>
                    <select class="py-1.5 px-2.5 block w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 shadow-xs" name="semester" aria-label="Pilih semester Proyeksi Banmus">
                        <option value="" <?= $selectedSemester === null ? 'selected' : '' ?>>Semua semester</option>
                        <option value="1" <?= $selectedSemester === 1 ? 'selected' : '' ?>>Semester 1</option>
                        <option value="2" <?= $selectedSemester === 2 ? 'selected' : '' ?>>Semester 2</option>
                    </select>
                </div>
                <button class="self-end min-h-[38px] sm:min-h-0 py-2 sm:py-1.5 px-3.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-xs font-semibold shadow-xs transition cursor-pointer" type="submit">Terapkan</button>
            </form>
        </div>

        <?php if ($documents === []): ?>
            <section class="rounded-none bg-white dark:bg-slate-900 overflow-hidden mt-4">
                <div class="flex items-center justify-between gap-3 border-b border-slate-400 dark:border-slate-600 px-4 py-3.5 sm:px-6 sm:py-4">
                    <div class="min-w-0">
                        <h1 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white truncate underline decoration-slate-400 decoration-2 underline-offset-[6px] dark:decoration-slate-500">
                            Proyeksi Banmus
                        </h1>
                    </div>
                </div>
                <div class="grid min-h-64 place-items-center p-8 text-center">
                    <div>
                        <svg class="mx-auto text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" width="44" height="44"
                            fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round"/>
                        </svg>
                        <h2 class="mt-4 text-base font-bold text-slate-900 dark:text-white">Belum ada Proyeksi Banmus</h2>
                        <p class="mt-1 max-w-md text-xs font-medium leading-relaxed text-slate-500 dark:text-slate-400">
                            Belum ada SK Banmus yang dapat ditampilkan untuk periode pilihan ini.
                        </p>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <section id="banmus-panel-list" class="order-2 lg:order-1 lg:col-span-7 bg-white dark:bg-slate-900 overflow-hidden rounded-none">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-400 dark:border-slate-600 px-4 py-3.5 sm:px-6 sm:py-4">
                        <div class="min-w-0">
                            <h1 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white truncate underline decoration-slate-400 decoration-2 underline-offset-[6px] dark:decoration-slate-500">
                                Proyeksi Banmus
                            </h1>
                        </div>
                        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                            <button type="button" data-nav-date="prev" class="inline-flex size-9 sm:size-8 items-center justify-center border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition cursor-pointer rounded-none" aria-label="Tanggal sebelumnya">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <span data-date-heading class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 px-1 truncate max-w-[45vw] sm:max-w-none">Semua Agenda</span>
                            <button type="button" data-nav-date="next" class="inline-flex size-9 sm:size-8 items-center justify-center border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition cursor-pointer rounded-none" aria-label="Tanggal berikutnya">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between px-4 py-2 sm:px-6 text-xs border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">
                            Total: <strong class="text-slate-800 dark:text-slate-200 font-bold" data-visible-count><?= count($allBanmusItems) ?></strong> agenda
                        </span>
                        <button type="button" data-reset-filter class="hidden text-blue-600 dark:text-blue-400 hover:underline font-semibold cursor-pointer">
                            Tampilkan Semua Tanggal
                        </button>
                    </div>

                    <div data-agenda-list>
                        <?php foreach ($allBanmusItems as $index => $item): ?>
                            <?php
                                $itemDate = ! empty($item['tanggal']) ? $item['tanggal'] : '';
                                $itemMonth = ! empty($item['tanggal']) ? substr($item['tanggal'], 0, 7) : '';
                                $rangeStart = $item['bulan_mulai'] ?? '';
                                $rangeEnd = $item['bulan_selesai'] ?? '';
                                $hasPeriode = ! empty($item['periode_label']);
                                $hasJadwalPasti = ! empty($item['tanggal']);
                            ?>
                            <details
                                class="group agenda-collapse mx-4 sm:mx-6 border-b border-slate-400 dark:border-slate-600 last:border-b-0 py-3.5 sm:py-4 transition hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                                data-banmus-item
                                data-id="<?= (int) $item['id'] ?>"
                                data-date="<?= esc($itemDate) ?>"
                                data-month="<?= esc($itemMonth) ?>"
                                data-range-start="<?= esc($rangeStart) ?>"
                                data-range-end="<?= esc($rangeEnd) ?>"
                            >
                                <summary class="cursor-pointer select-none pr-7">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <?php if ($hasPeriode): ?>
                                            <span class="text-xs font-bold text-blue-700 dark:text-blue-300">
                                                <?= esc($item['periode_label']) ?>
                                            </span>
                                        <?php elseif ($hasJadwalPasti): ?>
                                            <span class="text-xs font-bold text-blue-700 dark:text-blue-300">
                                                <?= date('d/m/Y', strtotime($item['tanggal'])) ?>
                                            </span>
                                            <?php if (! empty($item['jam_mulai']) && ! empty($item['jam_selesai'])): ?>
                                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                                    · <?= substr($item['jam_mulai'], 0, 5) ?>–<?= substr($item['jam_selesai'], 0, 5) ?> WITA
                                                </span>
                                            <?php elseif (! empty($item['jam_mulai'])): ?>
                                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                                    · <?= substr($item['jam_mulai'], 0, 5) ?> WITA
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                                Periode belum ditentukan
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($isMember && (int) ($item['is_internal'] ?? 0) === 1): ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                                Dokumen Internal DPRD
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <h3 class="text-sm sm:text-base font-semibold text-slate-900 dark:text-white leading-snug group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                        <?= nl2br(esc($item['agenda'])) ?>
                                    </h3>

                                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                        <?php if (! empty($item['units'])): ?>
                                            <?php foreach ($item['units'] as $u): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold border border-blue-500/80 dark:border-blue-400/80 text-blue-700 dark:text-blue-300">
                                                    <?= esc($u['nama']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                        <?php if (! empty($item['nama_ruangan'])): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                                <?= esc($item['nama_ruangan']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </summary>

                                <div class="pt-3 pb-2 space-y-2.5 text-xs text-slate-700 dark:text-slate-300">
                                    <div class="flex items-start gap-2">
                                        <span class="w-24 shrink-0 text-slate-500 dark:text-slate-400">Waktu</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200">
                                            <?php if ($hasPeriode): ?>
                                                <?= esc($item['periode_label']) ?>
                                            <?php elseif ($hasJadwalPasti): ?>
                                                <?= date('d/m/Y', strtotime($item['tanggal'])) ?>
                                                <?php if (! empty($item['jam_mulai']) && ! empty($item['jam_selesai'])): ?>
                                                    (<?= substr($item['jam_mulai'], 0, 5) ?>–<?= substr($item['jam_selesai'], 0, 5) ?> WITA)
                                                <?php elseif (! empty($item['jam_mulai'])): ?>
                                                    (<?= substr($item['jam_mulai'], 0, 5) ?> WITA)
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Periode belum ditentukan
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <?php if (! empty($item['nama_ruangan'])): ?>
                                        <div class="flex items-start gap-2">
                                            <span class="w-24 shrink-0 text-slate-500 dark:text-slate-400">Ruangan</span>
                                            <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                            <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200"><?= esc($item['nama_ruangan']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex items-start gap-2">
                                        <span class="w-24 shrink-0 text-slate-500 dark:text-slate-400">Agenda</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 leading-relaxed text-slate-700 dark:text-slate-200"><?= nl2br(esc($item['agenda'])) ?></span>
                                    </div>

                                    <?php if (! empty($item['catatan'])): ?>
                                        <div class="flex items-start gap-2">
                                            <span class="w-24 shrink-0 text-slate-500 dark:text-slate-400">Keterangan</span>
                                            <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                            <span class="min-w-0 flex-1 leading-relaxed text-slate-700 dark:text-slate-300"><?= nl2br(esc($item['catatan'])) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex items-start gap-2">
                                        <span class="w-24 shrink-0 text-slate-500 dark:text-slate-400">Dokumen SK</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200">
                                            <?= esc($item['document_judul']) ?> (<?= esc($item['document_nomor_sk']) ?>)
                                        </span>
                                    </div>

                                    <div class="pt-2">
                                        <a class="inline-flex items-center gap-x-1.5 py-1.5 px-3 border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 shadow-xs transition rounded-none"
                                            href="<?= base_url("agenda/jadwal-banmus/{$item['document_id']}/dokumen") ?>"
                                            target="_blank" rel="noopener">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                                                <path d="M14 2v6h6M8 13h8m-8 4h6" stroke-linecap="round"/>
                                            </svg>
                                            Lihat SK Asli
                                        </a>
                                    </div>
                                </div>
                            </details>
                        <?php endforeach; ?>

                        <div data-no-items-date class="hidden p-8 text-center">
                            <svg class="mx-auto text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round"/>
                            </svg>
                            <p class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-200">Tidak ada agenda pada tanggal ini</p>
                            <button type="button" data-reset-filter class="mt-2 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">
                                Tampilkan Semua Agenda Bulan Ini
                            </button>
                        </div>
                    </div>
                </section>

                <div id="banmus-panel-calendar" class="contents lg:block lg:order-2 lg:col-span-5 lg:space-y-6">
                    <section class="order-1 bg-white dark:bg-slate-900 overflow-hidden rounded-none">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-400 dark:border-slate-600 px-3.5 py-2 sm:px-6 sm:py-3.5">
                            <div class="min-w-0">
                                <h2 class="text-xs sm:text-base font-extrabold text-slate-900 dark:text-white truncate underline decoration-slate-400 decoration-2 underline-offset-[6px] dark:decoration-slate-500">
                                    Kalender Kegiatan
                                </h2>
                            </div>
                            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                                <button type="button" data-nav-month="prev" class="inline-flex size-7 sm:size-8 items-center justify-center border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition cursor-pointer rounded-none" aria-label="Bulan sebelumnya">
                                    <svg viewBox="0 0 24 24" width="14" height="14" class="sm:w-4 sm:h-4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </button>
                                <span data-calendar-title class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white px-1">Memuat...</span>
                                <button type="button" data-nav-month="next" class="inline-flex size-7 sm:size-8 items-center justify-center border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition cursor-pointer rounded-none" aria-label="Bulan berikutnya">
                                    <svg viewBox="0 0 24 24" width="14" height="14" class="sm:w-4 sm:h-4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-7 text-center font-bold text-[10px] sm:text-[11px] py-1.5 sm:py-2 bg-blue-600 text-white dark:bg-blue-600 select-none">
                            <span><span class="sm:hidden">Sen</span><span class="hidden sm:inline">Senin</span></span>
                            <span><span class="sm:hidden">Sel</span><span class="hidden sm:inline">Selasa</span></span>
                            <span><span class="sm:hidden">Rab</span><span class="hidden sm:inline">Rabu</span></span>
                            <span><span class="sm:hidden">Kam</span><span class="hidden sm:inline">Kamis</span></span>
                            <span><span class="sm:hidden">Jum</span><span class="hidden sm:inline">Jumat</span></span>
                            <span><span class="sm:hidden">Sab</span><span class="hidden sm:inline">Sabtu</span></span>
                            <span><span class="sm:hidden">Min</span><span class="hidden sm:inline">Minggu</span></span>
                        </div>

                        <div class="p-1.5 sm:p-3">
                            <div data-calendar-grid class="grid grid-cols-7 gap-0.5 sm:gap-1 text-center select-none"></div>
                        </div>

                        <div class="px-3 py-2 sm:px-4 sm:py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30 flex flex-wrap items-center justify-between gap-2 text-[10px] sm:text-[11px]">
                            <div class="flex items-center gap-2.5 sm:gap-3">
                                <div class="flex items-center gap-1 sm:gap-1.5 text-slate-600 dark:text-slate-400">
                                    <span class="size-1.5 sm:size-2 bg-amber-500"></span>
                                    <span>Agenda</span>
                                </div>
                                <div class="flex items-center gap-1 sm:gap-1.5 text-slate-600 dark:text-slate-400">
                                    <span class="size-2 sm:size-2.5 bg-blue-600"></span>
                                    <span>Terpilih</span>
                                </div>
                            </div>
                            <button type="button" data-calendar-today-btn class="text-blue-600 dark:text-blue-400 hover:underline font-bold cursor-pointer">
                                Hari Ini
                            </button>
                        </div>
                    </section>

                    <section class="order-3 bg-white dark:bg-slate-900 overflow-hidden rounded-none">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-400 dark:border-slate-600 px-4 py-3.5 sm:px-6 sm:py-4">
                            <div class="min-w-0">
                                <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white truncate underline decoration-slate-400 decoration-2 underline-offset-[6px] dark:decoration-slate-500">
                                    Dokumen SK Banmus
                                </h2>
                            </div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                <?= count($documents) ?> Dokumen
                            </span>
                        </div>

                        <div>
                            <?php foreach ($documents as $doc): ?>
                                <div class="mx-4 sm:mx-6 border-b border-slate-400 dark:border-slate-600 last:border-b-0 py-3.5 sm:py-4">
                                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2.5 sm:gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 mb-1">
                                                <span class="font-bold text-slate-700 dark:text-slate-300">Tahun <?= (int) $doc['tahun'] ?></span>
                                                <span>·</span>
                                                <span>Semester <?= (int) $doc['semester'] ?></span>
                                            </div>
                                            <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-snug">
                                                <?= esc($doc['judul'] ?: 'SK Banmus') ?>
                                            </h3>
                                            <?php if (! empty($doc['nomor_sk'])): ?>
                                                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 font-medium">
                                                    Nomor: <?= esc($doc['nomor_sk']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                                Total <?= count($doc['items']) ?> agenda dalam SK ini
                                            </p>
                                        </div>
                                        <a class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-none border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 shadow-xs transition w-fit shrink-0"
                                            href="<?= base_url("agenda/jadwal-banmus/{$doc['id']}/dokumen") ?>"
                                            target="_blank" rel="noopener">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                                                <path d="M14 2v6h6M8 13h8m-8 4h6" stroke-linecap="round"/>
                                            </svg>
                                            Lihat SK Asli
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="<?= base_url('assets/vendor/preline/preline.js?v=' . $prelineVersion) ?>" defer></script>
    <script {csp-script-nonce}>
        (() => {
            const WEATHER_URL = <?= json_encode(base_url('api/signage/cuaca'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const SELECTED_YEAR = <?= (int) $selectedYear ?>;

            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

            const setText = (selector, value) => {
                document.querySelectorAll(selector).forEach((element) => {
                    element.textContent = value;
                });
            };

            const updateClock = () => {
                const now = new Date();
                const options = { timeZone: 'Asia/Makassar' };
                setText('[data-header-day]', new Intl.DateTimeFormat('id-ID', {
                    ...options,
                    weekday: 'long',
                }).format(now).toUpperCase());
                setText('[data-header-date]', new Intl.DateTimeFormat('id-ID', {
                    ...options,
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                }).format(now));
                setText('[data-header-short-date]', new Intl.DateTimeFormat('id-ID', {
                    ...options,
                    day: 'numeric',
                    month: 'short',
                }).format(now));
                setText('[data-header-time]', new Intl.DateTimeFormat('id-ID', {
                    ...options,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                }).format(now).replaceAll('.', ':'));
            };

            const loadWeather = async () => {
                try {
                    const response = await fetch(WEATHER_URL);
                    const payload = await response.json();
                    if (payload.status !== 'success' || !payload.cuaca) {
                        throw new Error('Data cuaca tidak tersedia.');
                    }

                    const weather = payload.cuaca;
                    const location = [payload.lokasi?.desa, payload.lokasi?.kecamatan]
                        .filter((value) => value && value !== '-')
                        .join(', ') || 'Sulawesi Tengah';
                    setText('[data-weather-temperature]', weather.suhu || '--°C');
                    setText('[data-weather-condition]', weather.kondisi || 'Tidak tersedia');
                    setText('[data-weather-humidity]', weather.kelembapan || '--');
                    setText('[data-weather-wind]', weather.kec_angin || '--');
                    setText('[data-weather-location]', location);

                    const icon = document.querySelector('[data-weather-icon]');
                    const fallback = document.querySelector('[data-weather-fallback]');
                    if (icon && fallback) {
                        if (weather.icon_url) {
                            icon.src = weather.icon_url;
                            icon.classList.remove('hidden');
                            fallback.classList.add('hidden');
                        } else {
                            icon.classList.add('hidden');
                            fallback.classList.remove('hidden');
                        }
                    }
                } catch {
                    setText('[data-weather-temperature]', '--°C');
                    setText('[data-weather-condition]', 'Tidak tersedia');
                }
            };

            const themeToggle = document.querySelector('[data-theme-toggle]');
            const renderThemeToggle = () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                const darkIcon = document.querySelector('[data-theme-dark-icon]');
                const lightIcon = document.querySelector('[data-theme-light-icon]');
                if (darkIcon && lightIcon) {
                    darkIcon.classList.toggle('hidden', isDark);
                    lightIcon.classList.toggle('hidden', !isDark);
                }
                if (themeToggle) {
                    themeToggle.setAttribute('aria-label', isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap');
                }
            };

            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                    const theme = isDark ? 'light' : 'dark';
                    document.documentElement.classList.toggle('dark', theme === 'dark');
                    document.documentElement.setAttribute('data-theme', theme);
                    localStorage.setItem('dprd-admin-theme', theme);
                    renderThemeToggle();
                });
            }

            updateClock();
            loadWeather();
            renderThemeToggle();
            setInterval(updateClock, 1000);
            setInterval(loadWeather, 1800000);

            const itemElements = Array.from(document.querySelectorAll('[data-banmus-item]'));
            if (itemElements.length === 0) {
                return;
            }

            const items = itemElements.map((el) => ({
                el,
                id: el.getAttribute('data-id'),
                date: el.getAttribute('data-date') || '',
                month: el.getAttribute('data-month') || '',
                rangeStart: el.getAttribute('data-range-start') || '',
                rangeEnd: el.getAttribute('data-range-end') || '',
            }));

            const now = new Date();
            const todayKey = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

            const datesWithAgendas = [...new Set(items.map((i) => i.date).filter(Boolean))].sort();

            let cursorDate = new Date(SELECTED_YEAR, now.getMonth(), 1);
            if (datesWithAgendas.length > 0) {
                const firstWithYear = datesWithAgendas.find((d) => d.startsWith(String(SELECTED_YEAR)));
                if (firstWithYear) {
                    const [y, m] = firstWithYear.split('-').map(Number);
                    cursorDate = new Date(y, m - 1, 1);
                } else {
                    const [y, m] = datesWithAgendas[0].split('-').map(Number);
                    cursorDate = new Date(y, m - 1, 1);
                }
            }

            let selectedDate = null;

            const dateHeading = document.querySelector('[data-date-heading]');
            const visibleCountEl = document.querySelector('[data-visible-count]');
            const resetFilterButtons = document.querySelectorAll('[data-reset-filter]');
            const noItemsDateEl = document.querySelector('[data-no-items-date]');
            const calendarTitleEl = document.querySelector('[data-calendar-title]');
            const calendarGridEl = document.querySelector('[data-calendar-grid]');

            const formatFullDate = (dateStr) => {
                const [y, m, d] = dateStr.split('-').map(Number);
                const dt = new Date(y, m - 1, d);
                return `${dayNames[dt.getDay()]}, ${dt.getDate()} ${monthNames[dt.getMonth()]} ${dt.getFullYear()}`;
            };

            const updateListFilter = () => {
                const currentMonthKey = `${cursorDate.getFullYear()}-${String(cursorDate.getMonth() + 1).padStart(2, '0')}`;
                let visibleCount = 0;

                if (selectedDate) {
                    if (dateHeading) dateHeading.textContent = formatFullDate(selectedDate);
                    resetFilterButtons.forEach((btn) => btn.classList.remove('hidden'));

                    items.forEach((item) => {
                        const isMatch = item.date === selectedDate;
                        item.el.classList.toggle('hidden', !isMatch);
                        if (isMatch) visibleCount++;
                    });
                } else {
                    if (dateHeading) dateHeading.textContent = `Semua Tanggal — ${monthNames[cursorDate.getMonth()]} ${cursorDate.getFullYear()}`;
                    resetFilterButtons.forEach((btn) => btn.classList.add('hidden'));

                    items.forEach((item) => {
                        let isMatch = false;
                        if (item.month) {
                            isMatch = item.month === currentMonthKey;
                        } else if (item.rangeStart && item.rangeEnd) {
                            isMatch = currentMonthKey >= item.rangeStart && currentMonthKey <= item.rangeEnd;
                        } else {
                            isMatch = true;
                        }
                        item.el.classList.toggle('hidden', !isMatch);
                        if (isMatch) visibleCount++;
                    });
                }

                if (visibleCountEl) visibleCountEl.textContent = String(visibleCount);
                if (noItemsDateEl) {
                    noItemsDateEl.classList.toggle('hidden', visibleCount > 0);
                }
            };

            const renderCalendar = () => {
                if (!calendarGridEl || !calendarTitleEl) return;

                const year = cursorDate.getFullYear();
                const month = cursorDate.getMonth();

                calendarTitleEl.textContent = `${monthNames[month]} ${year}`;
                calendarGridEl.innerHTML = '';

                const startOffset = (new Date(year, month, 1).getDay() + 6) % 7;
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const daysInPrevMonth = new Date(year, month, 0).getDate();

                for (let i = 0; i < startOffset; i++) {
                    const prevDay = daysInPrevMonth - startOffset + 1 + i;
                    const cell = document.createElement('div');
                    cell.className = 'h-7 sm:h-8 lg:aspect-square flex items-center justify-center text-[11px] sm:text-xs text-slate-300 dark:text-slate-600 select-none rounded-none';
                    cell.textContent = String(prevDay);
                    calendarGridEl.appendChild(cell);
                }

                for (let d = 1; d <= daysInMonth; d++) {
                    const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    const hasAgenda = items.some((i) => i.date === dateKey);
                    const isSelected = selectedDate === dateKey;
                    const isToday = todayKey === dateKey;

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.setAttribute('data-day-key', dateKey);

                    let btnClass = 'h-7 sm:h-8 lg:aspect-square flex flex-col items-center justify-center rounded-none text-[11px] sm:text-xs font-semibold relative transition cursor-pointer ';
                    if (isSelected) {
                        btnClass += 'bg-blue-600 text-white font-bold shadow-xs hover:bg-blue-700';
                    } else if (isToday) {
                        btnClass += 'border-2 border-blue-500 text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-800';
                    } else {
                        btnClass += 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800';
                    }
                    btn.className = btnClass;

                    const numSpan = document.createElement('span');
                    numSpan.textContent = String(d);
                    btn.appendChild(numSpan);

                    if (hasAgenda) {
                        const dot = document.createElement('span');
                        dot.className = `size-1 sm:size-1.5 rounded-none absolute bottom-0.5 sm:bottom-1 ${isSelected ? 'bg-white' : 'bg-amber-500'}`;
                        btn.appendChild(dot);
                    }

                    btn.addEventListener('click', () => {
                        if (selectedDate === dateKey) {
                            selectedDate = null;
                        } else {
                            selectedDate = dateKey;
                        }
                        updateListFilter();
                        renderCalendar();
                    });

                    calendarGridEl.appendChild(btn);
                }

                const totalCells = startOffset + daysInMonth;
                const trailingCells = (7 - (totalCells % 7)) % 7;
                for (let i = 1; i <= trailingCells; i++) {
                    const cell = document.createElement('div');
                    cell.className = 'h-7 sm:h-8 lg:aspect-square flex items-center justify-center text-[11px] sm:text-xs text-slate-300 dark:text-slate-600 select-none rounded-none';
                    cell.textContent = String(i);
                    calendarGridEl.appendChild(cell);
                }
            };

            const navPrevMonthBtn = document.querySelector('[data-nav-month="prev"]');
            const navNextMonthBtn = document.querySelector('[data-nav-month="next"]');

            if (navPrevMonthBtn) {
                navPrevMonthBtn.addEventListener('click', () => {
                    cursorDate = new Date(cursorDate.getFullYear(), cursorDate.getMonth() - 1, 1);
                    if (selectedDate) {
                        const [sy, sm] = selectedDate.split('-').map(Number);
                        if (sy !== cursorDate.getFullYear() || sm !== cursorDate.getMonth() + 1) {
                            selectedDate = null;
                        }
                    }
                    renderCalendar();
                    updateListFilter();
                });
            }

            if (navNextMonthBtn) {
                navNextMonthBtn.addEventListener('click', () => {
                    cursorDate = new Date(cursorDate.getFullYear(), cursorDate.getMonth() + 1, 1);
                    if (selectedDate) {
                        const [sy, sm] = selectedDate.split('-').map(Number);
                        if (sy !== cursorDate.getFullYear() || sm !== cursorDate.getMonth() + 1) {
                            selectedDate = null;
                        }
                    }
                    renderCalendar();
                    updateListFilter();
                });
            }

            const todayBtn = document.querySelector('[data-calendar-today-btn]');
            if (todayBtn) {
                todayBtn.addEventListener('click', () => {
                    cursorDate = new Date(now.getFullYear(), now.getMonth(), 1);
                    selectedDate = todayKey;
                    renderCalendar();
                    updateListFilter();
                });
            }

            resetFilterButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    selectedDate = null;
                    renderCalendar();
                    updateListFilter();
                });
            });

            const navPrevDateBtn = document.querySelector('[data-nav-date="prev"]');
            const navNextDateBtn = document.querySelector('[data-nav-date="next"]');

            if (navPrevDateBtn) {
                navPrevDateBtn.addEventListener('click', () => {
                    if (datesWithAgendas.length === 0) return;
                    if (!selectedDate) {
                        const prev = [...datesWithAgendas].reverse().find((d) => d <= todayKey) || datesWithAgendas[datesWithAgendas.length - 1];
                        selectedDate = prev;
                    } else {
                        const idx = datesWithAgendas.indexOf(selectedDate);
                        if (idx > 0) {
                            selectedDate = datesWithAgendas[idx - 1];
                        } else {
                            selectedDate = datesWithAgendas[datesWithAgendas.length - 1];
                        }
                    }
                    const [y, m] = selectedDate.split('-').map(Number);
                    cursorDate = new Date(y, m - 1, 1);
                    renderCalendar();
                    updateListFilter();
                });
            }

            if (navNextDateBtn) {
                navNextDateBtn.addEventListener('click', () => {
                    if (datesWithAgendas.length === 0) return;
                    if (!selectedDate) {
                        const next = datesWithAgendas.find((d) => d >= todayKey) || datesWithAgendas[0];
                        selectedDate = next;
                    } else {
                        const idx = datesWithAgendas.indexOf(selectedDate);
                        if (idx >= 0 && idx < datesWithAgendas.length - 1) {
                            selectedDate = datesWithAgendas[idx + 1];
                        } else {
                            selectedDate = datesWithAgendas[0];
                        }
                    }
                    const [y, m] = selectedDate.split('-').map(Number);
                    cursorDate = new Date(y, m - 1, 1);
                    renderCalendar();
                    updateListFilter();
                });
            }

            renderCalendar();
            updateListFilter();
        })();
    </script>
</body>
</html>
