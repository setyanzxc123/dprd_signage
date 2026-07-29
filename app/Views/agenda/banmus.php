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
    <title>Proyeksi Banmus - DPRD Provinsi Sulawesi Tengah</title>
    <meta name="description" content="Proyeksi kegiatan dan PDF SK Badan Musyawarah DPRD Provinsi Sulawesi Tengah." />
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
<body class="min-h-screen overflow-x-hidden bg-base-200 text-base-content antialiased">
    <header class="sticky top-0 z-50 border-b border-base-300 bg-base-100/95 backdrop-blur-xl">
        <div class="navbar min-h-20 w-full gap-2 px-[2.5vw] py-[0.8vh]">
            <a class="navbar-start min-w-0 flex-1 gap-[1vw]" href="<?= esc($portalUrl) ?>" aria-label="Kembali ke agenda DPRD">
                <img class="h-11 w-11 shrink-0 rounded-box object-contain sm:h-14 sm:w-14"
                    src="<?= esc($logoUrl) ?>" alt="Logo DPRD Provinsi Sulawesi Tengah" />
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-[clamp(17px,1.08vw,24px)] font-bold uppercase tracking-[0.08em]">
                        DPRD Provinsi
                    </span>
                    <span class="block truncate text-[clamp(13px,0.82vw,18px)] uppercase tracking-[0.08em] text-base-content/70">
                        Sulawesi Tengah
                    </span>
                </span>
            </a>

            <div class="navbar-end w-auto shrink-0 gap-1.5">
                <div class="stats stats-horizontal hidden border border-base-300 bg-base-200 shadow-sm xl:inline-grid">
                    <div class="stat place-items-center px-3 py-2">
                        <div class="stat-value flex items-center gap-2 text-xl">
                            <img data-weather-icon class="hidden h-7 w-7 object-contain" src="" alt="Ikon cuaca" />
                            <span data-weather-fallback class="status status-info status-lg"></span>
                            <span data-weather-temperature>--°C</span>
                        </div>
                        <div data-weather-condition class="stat-desc max-w-28 truncate text-xs font-semibold">Memuat...</div>
                    </div>

                    <div class="stat hidden px-3 py-2 2xl:inline-grid">
                        <div data-weather-location class="stat-title max-w-48 truncate text-xs font-bold">Sulawesi Tengah</div>
                        <div class="stat-value mt-0.5 text-xs font-medium">
                            Kelembapan <span data-weather-humidity>--</span> · Angin <span data-weather-wind>--</span>
                        </div>
                        <div class="stat-desc text-[10px] italic">Sumber: BMKG</div>
                    </div>

                    <div class="stat place-items-center px-4 py-2 text-center">
                        <div data-header-day class="stat-title text-xs font-bold uppercase tracking-[0.12em]">—</div>
                        <div data-header-date class="stat-value text-sm">—</div>
                    </div>

                    <div class="stat place-items-center px-4 py-2 text-center">
                        <div data-header-time class="stat-value font-mono text-3xl tabular-nums leading-none">--:--:--</div>
                        <div class="stat-desc mt-0.5 uppercase tracking-[0.18em]">WITA</div>
                    </div>
                </div>

                <button class="btn btn-ghost btn-circle btn-sm" type="button" data-theme-toggle aria-label="Gunakan tema gelap">
                    <svg data-theme-dark-icon viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 15.5A8.5 8.5 0 0 1 8.5 4a7 7 0 1 0 11.5 11.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <svg data-theme-light-icon class="hidden" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v2m0 14v2M4.2 4.2l1.4 1.4m12.8 12.8 1.4 1.4M3 12h2m14 0h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" stroke-linecap="round"/></svg>
                </button>

                <?php if ($isMember): ?>
                    <details class="dropdown dropdown-end">
                        <summary class="btn btn-outline btn-sm max-w-44">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0m12-13a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" stroke-linecap="round"/></svg>
                            <span class="hidden truncate sm:block"><?= esc((string) ($member['name'] ?? 'Anggota')) ?></span>
                        </summary>
                        <div class="dropdown-content z-[60] mt-2 w-64 rounded-box border border-base-300 bg-base-100 p-3 shadow-xl">
                            <p class="truncate text-sm font-extrabold"><?= esc((string) ($member['name'] ?? 'Anggota DPRD')) ?></p>
                            <p class="mt-1 truncate text-xs font-semibold text-base-content/55"><?= esc((string) ($member['jabatan'] ?? 'Anggota DPRD')) ?></p>
                            <form class="mt-3" action="<?= base_url('anggota/logout') ?>" method="post">
                                <?= csrf_field() ?>
                                <button class="btn btn-error btn-outline btn-sm btn-block" type="submit">Keluar</button>
                            </form>
                        </div>
                    </details>
                <?php else: ?>
                    <a class="btn btn-primary btn-sm" href="<?= base_url('login?akses=anggota') ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-4-4 5-5-5-5m5 5H3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="hidden sm:inline">Masuk Anggota</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-3 border-t border-base-300 xl:hidden">
            <div class="min-w-0 p-2 text-center">
                <span class="block text-[9px] font-extrabold uppercase tracking-wider text-base-content/45">Cuaca · BMKG</span>
                <span class="mt-0.5 block truncate text-xs font-black">
                    <span data-weather-temperature>--°C</span> · <span data-weather-condition>Memuat...</span>
                </span>
            </div>
            <div class="min-w-0 border-x border-base-300 p-2 text-center">
                <span data-header-day class="block truncate text-[9px] font-extrabold uppercase tracking-wider text-base-content/45">—</span>
                <span data-header-date class="mt-0.5 block truncate text-xs font-black">—</span>
            </div>
            <div class="min-w-0 p-2 text-center">
                <span class="block text-[9px] font-extrabold uppercase tracking-wider text-base-content/45">Jam</span>
                <span class="mt-0.5 block truncate text-xs font-black tabular-nums"><span data-header-time>--:--:--</span> WITA</span>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1180px] px-2.5 py-5 sm:px-4 sm:py-8">
        <a class="btn btn-ghost btn-sm mb-4 -ml-2" href="<?= esc($portalUrl) ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Kembali ke Agenda
        </a>

        <section class="card card-border bg-base-100 shadow-sm">
            <div class="card-body gap-6 p-4 sm:p-6">
                <div class="flex flex-col justify-between gap-5 border-b border-base-200 pb-5 lg:flex-row lg:items-end">
                    <div class="min-w-0">
                        <h1 class="card-title text-2xl font-black uppercase sm:text-3xl">Proyeksi Banmus</h1>
                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-base-content/60">
                            Proyeksi dan perkembangan jadwal kegiatan per semester sebagaimana ditetapkan dalam SK Badan Musyawarah.
                        </p>
                    </div>

                    <form class="grid min-w-0 grid-cols-1 gap-2 rounded-box border border-base-300 bg-base-200 p-3 sm:flex" action="<?= base_url('agenda/jadwal-banmus') ?>" method="get">
                        <fieldset class="fieldset min-w-0 sm:min-w-28">
                            <legend class="fieldset-legend">Tahun</legend>
                            <select class="select select-sm w-full max-w-full" name="tahun" aria-label="Pilih tahun Proyeksi Banmus">
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
                        </fieldset>
                        <fieldset class="fieldset min-w-0 sm:min-w-36">
                            <legend class="fieldset-legend">Semester</legend>
                            <select class="select select-sm w-full max-w-full" name="semester" aria-label="Pilih semester Proyeksi Banmus">
                                <option value="" <?= $selectedSemester === null ? 'selected' : '' ?>>Semua semester</option>
                                <option value="1" <?= $selectedSemester === 1 ? 'selected' : '' ?>>Semester 1</option>
                                <option value="2" <?= $selectedSemester === 2 ? 'selected' : '' ?>>Semester 2</option>
                            </select>
                        </fieldset>
                        <button class="btn btn-neutral btn-sm self-end" type="submit">Terapkan</button>
                    </form>
                </div>

                <?php if ($documents === []): ?>
                    <div class="grid min-h-64 place-items-center rounded-box border border-dashed border-base-300 bg-base-200 p-8 text-center">
                        <div>
                            <svg class="mx-auto text-base-content/30" viewBox="0 0 24 24" width="44" height="44"
                                fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round"/>
                            </svg>
                            <h2 class="mt-4 text-lg font-extrabold">Belum ada Proyeksi Banmus</h2>
                            <p class="mt-1 max-w-md text-sm font-semibold leading-6 text-base-content/55">
                                Belum ada SK Banmus yang dapat ditampilkan untuk periode pilihan ini.
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid gap-4">
                        <?php foreach ($documents as $document): ?>
                            <article class="card card-border card-sm bg-base-100 shadow-sm">
                                <div class="card-body gap-4">
                                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold uppercase tracking-wider text-base-content/50">
                                                Semester <?= (int) $document['semester'] ?> · <?= (int) $document['tahun'] ?>
                                            </p>
                                            <h2 class="mt-3 text-lg font-black leading-snug sm:text-xl">
                                                <?= esc($document['judul'] ?: 'Agenda Banmus') ?>
                                            </h2>
                                            <p class="mt-1 text-sm font-semibold text-base-content/60">
                                                Nomor SK: <?= esc($document['nomor_sk']) ?>
                                            </p>
                                        </div>

                                        <a class="btn btn-outline btn-sm shrink-0"
                                            href="<?= base_url("agenda/jadwal-banmus/{$document['id']}/dokumen") ?>"
                                            target="_blank" rel="noopener">
                                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor"
                                                stroke-width="2" aria-hidden="true">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                                                <path d="M14 2v6h6M8 13h8m-8 4h6" stroke-linecap="round"/>
                                            </svg>
                                            Lihat SK Asli
                                        </a>
                                    </div>

                                    <ul class="list rounded-box border border-base-300 bg-base-100">
                                        <?php foreach ($document['items'] as $item): ?>
                                            <li class="list-row items-start gap-3 border-b border-base-200 p-4 last:border-b-0">
                                                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-box border border-base-300 bg-base-200 text-sm font-black">
                                                    <?= (int) $item['urutan'] ?>
                                                </div>
                                                <div class="list-col-grow min-w-0">
                                                    <?php if (! empty($item['tanggal'])): ?>
                                                        <p class="text-sm font-bold">
                                                            <?= date('d/m/Y', strtotime($item['tanggal'])) ?>
                                                            <?php if (! empty($item['jam_mulai']) && ! empty($item['jam_selesai'])): ?>
                                                                · <?= substr($item['jam_mulai'], 0, 5) ?>–<?= substr($item['jam_selesai'], 0, 5) ?> WITA
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php else: ?>
                                                        <p class="text-sm font-bold"><?= esc($item['periode_label'] ?: 'Periode belum ditentukan') ?></p>
                                                    <?php endif; ?>
                                                    <h3 class="mt-2 text-sm font-extrabold leading-6 sm:text-base">
                                                        <?= nl2br(esc($item['agenda'])) ?>
                                                    </h3>
                                                    <?php if (! empty($item['catatan'])): ?>
                                                        <p class="mt-2 text-sm leading-6 text-base-content/60">
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
