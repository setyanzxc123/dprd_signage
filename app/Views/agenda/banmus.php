<?php
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$cssVersion = is_file(FCPATH . 'assets/css/agenda.css') ? filemtime(FCPATH . 'assets/css/agenda.css') : time();
$logoVersion = is_file(FCPATH . 'assets/images/logo_dprd.jpg') ? filemtime(FCPATH . 'assets/images/logo_dprd.jpg') : time();
$isMember = is_array($member ?? null);
$isAdmin = ! $isMember && ! empty($isAdmin);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Proyeksi Banmus - DPRD Provinsi Sulawesi Tengah</title>
    <meta name="description" content="Proyeksi kegiatan dan PDF SK Badan Musyawarah DPRD Provinsi Sulawesi Tengah." />
    <link rel="icon" type="image/jpeg" href="<?= esc($logoUrl) ?><?= str_contains($logoUrl, '?') ? '&' : '?' ?>v=<?= $logoVersion ?>" />
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
<body class="min-h-screen overflow-x-hidden bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased selection:bg-emerald-500 selection:text-white">
    <header class="sticky top-0 z-50 border-b border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md shadow-xs">
        <div class="mx-auto flex min-h-16 w-full items-center justify-between gap-3 px-3.5 py-2.5 sm:min-h-20 sm:px-6 xl:px-8">
            <a class="flex items-center gap-3 min-w-0 flex-1" href="<?= esc($portalUrl) ?>" aria-label="Kembali ke agenda DPRD">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full ring-2 ring-emerald-500/40 bg-white shadow-xs p-0.5 sm:h-12 sm:w-12 sm:p-1">
                    <img class="h-full w-full rounded-full object-contain" src="<?= esc($logoUrl) ?><?= str_contains($logoUrl, '?') ? '&' : '?' ?>v=<?= $logoVersion ?>" alt="Logo DPRD Provinsi Sulawesi Tengah" />
                </div>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-sm font-black uppercase tracking-[0.06em] text-slate-900 dark:text-white sm:text-[clamp(17px,1.08vw,22px)] sm:tracking-[0.08em]">
                        DPRD Provinsi
                    </span>
                    <span class="block truncate text-[10px] uppercase tracking-[0.06em] text-slate-500 dark:text-slate-400 sm:text-[clamp(12px,0.82vw,16px)] sm:tracking-[0.08em]">
                        Sulawesi Tengah
                    </span>
                </span>
            </a>

            <div class="flex items-center gap-2 shrink-0">
                <div class="hidden xl:inline-flex items-center divide-x divide-slate-200 dark:divide-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-800/50 backdrop-blur-sm px-3.5 py-1.5 shadow-xs">
                    <div class="flex items-center gap-2.5 px-3 py-1">
                        <img data-weather-icon class="hidden h-7 w-7 object-contain" src="" alt="Ikon cuaca" />
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
                        <span class="block text-[9px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mt-0.5">WITA</span>
                    </div>
                </div>

                <button class="inline-flex justify-center items-center size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700/80 transition" type="button" data-theme-toggle aria-label="Gunakan tema gelap">
                    <svg data-theme-dark-icon viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 15.5A8.5 8.5 0 0 1 8.5 4a7 7 0 1 0 11.5 11.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <svg data-theme-light-icon class="hidden" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v2m0 14v2M4.2 4.2l1.4 1.4m12.8 12.8 1.4 1.4M3 12h2m14 0h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" stroke-linecap="round"/></svg>
                </button>

                <?php if ($isMember): ?>
                    <details class="relative">
                        <summary class="inline-flex items-center gap-x-2 py-2 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer list-none">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0m12-13a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" stroke-linecap="round"/></svg>
                            <span class="hidden truncate sm:block max-w-40"><?= esc((string) ($member['name'] ?? 'Anggota')) ?></span>
                        </summary>
                        <div class="absolute right-0 z-50 mt-2 w-64 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 shadow-xl">
                            <p class="truncate text-sm font-extrabold text-slate-900 dark:text-white"><?= esc((string) ($member['name'] ?? 'Anggota DPRD')) ?></p>
                            <p class="mt-0.5 truncate text-xs font-semibold text-slate-500 dark:text-slate-400"><?= esc((string) ($member['jabatan'] ?? 'Anggota DPRD')) ?></p>
                            <form class="mt-3" action="<?= base_url('anggota/logout') ?>" method="post">
                                <?= csrf_field() ?>
                                <button class="w-full py-2 px-3 rounded-xl border border-rose-200 dark:border-rose-800 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition text-center" type="submit">Keluar</button>
                            </form>
                        </div>
                    </details>
                <?php elseif ($isAdmin): ?>
                    <a class="inline-flex items-center gap-x-2 py-2 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700 transition" href="<?= base_url('admin/dashboard') ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        <span class="hidden sm:inline">Panel Admin</span>
                    </a>
                <?php else: ?>
                    <a class="inline-flex items-center gap-x-2 py-2 px-3.5 rounded-xl bg-slate-900 text-white hover:bg-slate-800 dark:bg-emerald-600 dark:hover:bg-emerald-500 text-xs font-semibold shadow-xs transition" href="<?= base_url('login?akses=anggota') ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-4-4 5-5-5-5m5 5H3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="hidden sm:inline">Masuk Anggota</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-3 border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/70 dark:bg-slate-900/50 xl:hidden divide-x divide-slate-200/80 dark:divide-slate-800/80 py-2">
            <div class="min-w-0 px-2 text-center">
                <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Cuaca · BMKG</span>
                <span class="mt-0.5 block truncate text-xs font-black text-slate-800 dark:text-slate-200">
                    <span data-weather-temperature>--°C</span> · <span data-weather-condition>Memuat...</span>
                </span>
            </div>
            <div class="min-w-0 px-2 text-center">
                <span data-header-day class="block truncate text-[9px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">—</span>
                <span data-header-date class="mt-0.5 block truncate text-xs font-black text-slate-800 dark:text-slate-200">—</span>
            </div>
            <div class="min-w-0 px-2 text-center">
                <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Jam</span>
                <span class="mt-0.5 block truncate text-xs font-black tabular-nums text-slate-900 dark:text-white"><span data-header-time>--:--:--</span> WITA</span>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-5xl px-3 py-5 sm:px-6 sm:py-8">
        <a class="inline-flex items-center gap-x-1.5 py-1.5 px-3 mb-4 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition" href="<?= esc($portalUrl) ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Kembali ke Agenda
        </a>

        <section class="rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-xs overflow-hidden">
            <div class="p-4 sm:p-6 lg:p-8">
                <div class="flex flex-col justify-between gap-5 border-b border-slate-100 dark:border-slate-800 pb-6 lg:flex-row lg:items-end">
                    <div class="min-w-0">
                        <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900 dark:text-white sm:text-3xl">Proyeksi Banmus</h1>
                        <p class="mt-2 max-w-2xl text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400">
                            Proyeksi dan perkembangan jadwal kegiatan per semester sebagaimana ditetapkan dalam SK Badan Musyawarah.
                        </p>
                    </div>

                    <form class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 p-2 sm:p-3" action="<?= base_url('agenda/jadwal-banmus') ?>" method="get">
                        <div class="min-w-0 sm:min-w-28">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Tahun</label>
                            <select class="py-1.5 px-3 block w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/20 shadow-xs" name="tahun" aria-label="Pilih tahun Proyeksi Banmus">
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
                        <div class="min-w-0 sm:min-w-36">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Semester</label>
                            <select class="py-1.5 px-3 block w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/20 shadow-xs" name="semester" aria-label="Pilih semester Proyeksi Banmus">
                                <option value="" <?= $selectedSemester === null ? 'selected' : '' ?>>Semua semester</option>
                                <option value="1" <?= $selectedSemester === 1 ? 'selected' : '' ?>>Semester 1</option>
                                <option value="2" <?= $selectedSemester === 2 ? 'selected' : '' ?>>Semester 2</option>
                            </select>
                        </div>
                        <button class="self-end py-1.5 px-3.5 rounded-lg bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-semibold shadow-xs hover:bg-slate-800 transition" type="submit">Terapkan</button>
                    </form>
                </div>

                <?php if ($documents === []): ?>
                    <div class="grid min-h-64 place-items-center rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 p-8 text-center mt-6">
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
                <?php else: ?>
                    <div class="grid gap-5 mt-6">
                        <?php foreach ($documents as $document): ?>
                            <article class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs">
                                <div class="space-y-4">
                                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                                Semester <?= (int) $document['semester'] ?> · <?= (int) $document['tahun'] ?>
                                            </p>
                                            <?php if ($isMember && (int) ($document['is_publik'] ?? 0) !== 1): ?>
                                                <span class="inline-flex items-center py-0.5 px-2 mt-1.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                                    Dokumen Internal DPRD
                                                </span>
                                            <?php endif; ?>
                                            <h2 class="mt-1.5 text-lg font-black leading-snug text-slate-900 dark:text-white sm:text-xl">
                                                <?= esc($document['judul'] ?: 'Agenda Banmus') ?>
                                            </h2>
                                            <p class="mt-0.5 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                Nomor SK: <?= esc($document['nomor_sk']) ?>
                                            </p>
                                        </div>

                                        <a class="inline-flex items-center gap-x-1.5 py-2 px-3.5 min-h-[40px] rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition shrink-0"
                                            href="<?= base_url("agenda/jadwal-banmus/{$document['id']}/dokumen") ?>"
                                            target="_blank" rel="noopener">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                                                stroke-width="2" aria-hidden="true">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                                                <path d="M14 2v6h6M8 13h8m-8 4h6" stroke-linecap="round"/>
                                            </svg>
                                            Lihat SK Asli
                                        </a>
                                    </div>

                                    <ul class="divide-y divide-slate-100 dark:divide-slate-800 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden">
                                        <?php foreach ($document['items'] as $item): ?>
                                            <li class="flex items-start gap-3 p-4">
                                                <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300">
                                                    <?= (int) $item['urutan'] ?>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <?php if (! empty($item['tanggal'])): ?>
                                                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                                            <?= date('d/m/Y', strtotime($item['tanggal'])) ?>
                                                            <?php if (! empty($item['jam_mulai']) && ! empty($item['jam_selesai'])): ?>
                                                                · <?= substr($item['jam_mulai'], 0, 5) ?>–<?= substr($item['jam_selesai'], 0, 5) ?> WITA
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php else: ?>
                                                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400"><?= esc($item['periode_label'] ?: 'Periode belum ditentukan') ?></p>
                                                    <?php endif; ?>
                                                    <h3 class="mt-1 text-sm font-bold leading-relaxed text-slate-900 dark:text-white">
                                                        <?= nl2br(esc($item['agenda'])) ?>
                                                    </h3>
                                                    <?php if (! empty($item['catatan'])): ?>
                                                        <p class="mt-1.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                                                            <span class="font-bold">Keterangan:</span>
                                                            <?= nl2br(esc($item['catatan'])) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script {csp-script-nonce}>
        (() => {
            const WEATHER_URL = <?= json_encode(base_url('api/signage/cuaca'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
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
                    if (weather.icon_url) {
                        icon.src = weather.icon_url;
                        icon.classList.remove('hidden');
                        fallback.classList.add('hidden');
                    }
                } catch {
                    setText('[data-weather-temperature]', '--°C');
                    setText('[data-weather-condition]', 'Tidak tersedia');
                }
            };

            const themeToggle = document.querySelector('[data-theme-toggle]');
            const renderThemeToggle = () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                document.querySelector('[data-theme-dark-icon]').classList.toggle('hidden', isDark);
                document.querySelector('[data-theme-light-icon]').classList.toggle('hidden', !isDark);
                themeToggle.setAttribute('aria-label', isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap');
            };
            themeToggle.addEventListener('click', () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                const theme = isDark ? 'light' : 'dark';
                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('dprd-admin-theme', theme);
                renderThemeToggle();
            });

            updateClock();
            loadWeather();
            renderThemeToggle();
            setInterval(updateClock, 1000);
            setInterval(loadWeather, 1800000);
        })();
    </script>
</body>
</html>
