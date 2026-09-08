<?php
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$vueVersion = is_file(FCPATH . 'assets/vendor/vue/vue.global.prod.js') ? filemtime(FCPATH . 'assets/vendor/vue/vue.global.prod.js') : time();
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
            const theme = stored === 'dark' || stored === 'light' ? stored : 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link href="<?= base_url('assets/css/agenda.css?v=' . $cssVersion) ?>" rel="stylesheet" />
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased">
<div id="banmus-app" v-cloak>
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
                            <img v-if="weather.icon_url" :src="weather.icon_url" class="h-7 w-7 object-contain" alt="Ikon cuaca" />
                            <span v-else class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                            <div class="text-left">
                                <span class="block text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ weather.suhu }}</span>
                                <span class="block max-w-28 truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ weather.kondisi }}</span>
                            </div>
                        </div>

                        <div class="hidden 2xl:block px-3 py-1 text-left">
                            <span class="block max-w-48 truncate text-xs font-bold text-slate-800 dark:text-slate-200">{{ weatherLocation }}</span>
                            <span class="block text-[10px] text-slate-500 dark:text-slate-400">Kelembapan {{ weather.kelembapan }} · Angin {{ weather.kec_angin }}</span>
                        </div>

                        <div class="px-3.5 py-1 text-center">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ headerDay }}</span>
                            <span class="block text-xs font-semibold text-slate-800 dark:text-slate-200">{{ headerDate }}</span>
                        </div>

                        <div class="px-3.5 py-1 text-center">
                            <span class="block font-mono text-2xl font-black tabular-nums leading-none text-slate-900 dark:text-white">{{ headerTime }}</span>
                            <span class="block text-[9px] font-bold uppercase tracking-widest text-blue-700 dark:text-blue-300 mt-0.5">WITA</span>
                        </div>
                    </div>

                    <button class="inline-flex justify-center items-center size-10 sm:size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700/80 transition cursor-pointer" type="button" @click="toggleTheme" :aria-label="isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap'">
                        <svg v-if="!isDark" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 15.5A8.5 8.5 0 0 1 8.5 4a7 7 0 1 0 11.5 11.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <svg v-else viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v2m0 14v2M4.2 4.2l1.4 1.4m12.8 12.8 1.4 1.4M3 12h2m14 0h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" stroke-linecap="round"/></svg>
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
                    {{ weatherLabel }} · {{ headerShortDate }} · {{ headerTime }} WITA
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

            <form class="flex flex-wrap items-center gap-2" method="get" action="<?= base_url('agenda/jadwal-banmus') ?>">
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-slate-500 dark:text-slate-400" for="tahun">Tahun:</label>
                    <select class="py-1.5 px-2.5 pe-8 block w-auto border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 shadow-xs cursor-pointer" id="tahun" name="tahun">
                        <?php if ($availableYears === []): ?>
                            <option value="<?= (int) $selectedYear ?>" selected><?= (int) $selectedYear ?></option>
                        <?php else: ?>
                            <?php foreach ($availableYears as $yr): ?>
                                <option value="<?= (int) $yr ?>" <?= $yr === $selectedYear ? 'selected' : '' ?>><?= (int) $yr ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-slate-500 dark:text-slate-400" for="semester">Semester:</label>
                    <select class="py-1.5 px-2.5 pe-8 block w-auto border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 shadow-xs cursor-pointer" id="semester" name="semester">
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
                        <svg class="mx-auto text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
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
                            <button type="button" @click="navDate('prev')" class="inline-flex size-9 sm:size-8 items-center justify-center border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition cursor-pointer rounded-none" aria-label="Tanggal sebelumnya">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <span class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 px-1 truncate max-w-[45vw] sm:max-w-none">{{ dateHeading }}</span>
                            <button type="button" @click="navDate('next')" class="inline-flex size-9 sm:size-8 items-center justify-center border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition cursor-pointer rounded-none" aria-label="Tanggal berikutnya">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between px-4 py-2 sm:px-6 text-xs border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">
                            Total: <strong class="text-slate-800 dark:text-slate-200 font-bold">{{ filteredItems.length }}</strong> agenda
                        </span>
                        <button v-if="selectedDate" type="button" @click="resetFilter" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold cursor-pointer">
                            Tampilkan Semua Tanggal
                        </button>
                    </div>

                    <div v-if="filteredItems.length === 0" class="py-12 px-4 text-center">
                        <svg class="mx-auto text-slate-300 dark:text-slate-600 mb-2" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round"/>
                        </svg>
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Tidak ada agenda pada periode ini.</p>
                        <button type="button" @click="resetFilter" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline font-bold cursor-pointer">Tampilkan Semua Tanggal</button>
                    </div>

                    <div>
                        <details
                            v-for="item in filteredItems"
                            :key="'banmus-item-' + item.id"
                            class="group agenda-collapse mx-4 sm:mx-6 border-b border-slate-400 dark:border-slate-600 last:border-b-0 py-3.5 sm:py-4 transition hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                            :open="expandedItemId === item.id"
                            @toggle="handleToggle($event, item.id)"
                        >
                            <summary class="cursor-pointer select-none pr-7">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span v-if="item.periode_label" class="text-xs font-bold text-blue-700 dark:text-blue-300">
                                        {{ item.periode_label }}
                                    </span>
                                    <span v-else-if="item.tanggal" class="text-xs font-bold text-blue-700 dark:text-blue-300">
                                        {{ formatDateDmy(item.tanggal) }}
                                        <template v-if="item.jam_mulai && item.jam_selesai">
                                            · {{ item.jam_mulai.substring(0, 5) }}–{{ item.jam_selesai.substring(0, 5) }} WITA
                                        </template>
                                        <template v-else-if="item.jam_mulai">
                                            · {{ item.jam_mulai.substring(0, 5) }} WITA
                                        </template>
                                    </span>
                                    <span v-else class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                        Periode belum ditentukan
                                    </span>

                                    <span v-if="item.jenis_agenda === 'rapat'" class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-none bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/30">
                                        Rapat
                                    </span>
                                </div>

                                <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white leading-snug group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                                    {{ item.agenda }}
                                </h2>

                                <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                    <span v-if="item.ruangan || item.nama_ruangan" class="flex items-center gap-1">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <span>{{ item.ruangan || item.nama_ruangan }}</span>
                                    </span>
                                    <span v-if="item.pihak_eksternal" class="flex items-center gap-1">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        <span>{{ item.pihak_eksternal }}</span>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M16 13H8m8 4H8m2-8H8"/></svg>
                                        <span>{{ item.document_judul || 'SK Banmus' }}</span>
                                    </span>
                                </div>
                            </summary>

                            <div class="pt-3 pb-1 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-300 space-y-2">
                                <div v-if="item.keterangan" class="leading-relaxed whitespace-pre-line">
                                    {{ item.keterangan }}
                                </div>
                                <div class="flex flex-wrap items-center gap-3 pt-1 text-[11px] text-slate-500 dark:text-slate-400">
                                    <span v-if="item.document_nomor_sk">SK: {{ item.document_nomor_sk }}</span>
                                    <span v-if="item.document_semester">Semester: {{ item.document_semester }}</span>
                                    <span v-if="item.document_tahun">Tahun: {{ item.document_tahun }}</span>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div v-if="false" class="hidden" aria-hidden="true">
                        <?php foreach ($allBanmusItems as $it): ?>
                            <span><?= esc($it['agenda']) ?></span>
                            <span><?= esc($it['periode_label'] ?? '') ?></span>
                            <span><?= ! empty($it['tanggal']) ? date('d/m/Y', strtotime($it['tanggal'])) : '' ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>

                <div class="order-1 lg:order-2 lg:col-span-5 space-y-6">
                    <section class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 shadow-xs rounded-none overflow-hidden">
                        <div class="flex items-center justify-between border-b border-slate-300 dark:border-slate-700 px-3.5 py-2 sm:px-6 sm:py-3.5">
                            <h2 class="text-xs sm:text-base font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                                {{ monthNames[cursorMonth] }} {{ cursorYear }}
                            </h2>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="prevMonth" class="inline-flex size-7 sm:size-8 items-center justify-center border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition cursor-pointer rounded-none" aria-label="Bulan sebelumnya">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </button>
                                <button type="button" @click="nextMonth" class="inline-flex size-7 sm:size-8 items-center justify-center border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition cursor-pointer rounded-none" aria-label="Bulan berikutnya">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-1.5 sm:p-3">
                            <div class="grid grid-cols-7 gap-0.5 sm:gap-1 text-center font-bold text-[10px] sm:text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 py-1.5 sm:py-2 border-b border-slate-200 dark:border-slate-800">
                                <span>Sen</span>
                                <span>Sel</span>
                                <span>Rab</span>
                                <span>Kam</span>
                                <span>Jum</span>
                                <span>Sab</span>
                                <span>Min</span>
                            </div>

                            <div class="grid grid-cols-7 gap-0.5 sm:gap-1 pt-1 sm:pt-2">
                                <div v-for="prev in calendarPrevDays" :key="'prev-' + prev" class="h-7 sm:h-8 lg:aspect-square flex items-center justify-center text-[11px] sm:text-xs text-slate-300 dark:text-slate-600 select-none rounded-none">
                                    {{ prev }}
                                </div>
                                <button
                                    v-for="d in calendarDays"
                                    :key="'day-' + d.dateKey"
                                    type="button"
                                    :class="d.btnClass"
                                    @click="selectDate(d.dateKey)"
                                >
                                    <span>{{ d.day }}</span>
                                    <span v-if="d.hasAgenda" :class="d.dotClass"></span>
                                </button>
                                <div v-for="next in calendarNextDays" :key="'next-' + next" class="h-7 sm:h-8 lg:aspect-square flex items-center justify-center text-[11px] sm:text-xs text-slate-300 dark:text-slate-600 select-none rounded-none">
                                    {{ next }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-300 dark:border-slate-700 px-3 py-2 sm:px-4 sm:py-3 text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-800/30">
                            <div class="flex items-center gap-1.5">
                                <span class="size-2 rounded-none bg-amber-500"></span>
                                <span class="font-medium">Ada Agenda</span>
                            </div>
                            <button type="button" @click="goToThisMonth" class="text-blue-600 dark:text-blue-400 hover:underline font-bold cursor-pointer">
                                Bulan Ini
                            </button>
                        </div>
                    </section>

                    <section class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 shadow-xs rounded-none overflow-hidden">
                        <div class="border-b border-slate-300 dark:border-slate-700 px-4 py-3.5 sm:px-6">
                            <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                                Dokumen SK Banmus
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Berkas Keputusan Badan Musyawarah resmi yang menjadi dasar penetapan jadwal.
                            </p>
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

    <?= $this->include('agenda/_footer') ?>
</div>

<script src="<?= base_url('assets/vendor/preline/preline.js?v=' . $prelineVersion) ?>" defer></script>
<script src="<?= base_url('assets/vendor/vue/vue.global.prod.js?v=' . $vueVersion) ?>"></script>
<script {csp-script-nonce}>
    const { createApp, ref, computed, onMounted, onUnmounted } = Vue;

    createApp({
        setup() {
            const WEATHER_URL = <?= json_encode(base_url('api/signage/cuaca'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const SELECTED_YEAR = <?= (int) $selectedYear ?>;
            const RAW_ITEMS = <?= json_encode($allBanmusItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

            const weather = ref({
                suhu: '--°C',
                kondisi: 'Memuat...',
                kelembapan: '--',
                kec_angin: '--',
                icon_url: '',
            });
            const weatherLocation = ref('Sulawesi Tengah');
            const weatherLabel = computed(() => `${weather.value.suhu} · ${weather.value.kondisi}`);

            const headerDay = ref('—');
            const headerDate = ref('—');
            const headerShortDate = ref('—');
            const headerTime = ref('--:--:--');

            const isDark = ref(document.documentElement.getAttribute('data-theme') === 'dark');

            const toggleTheme = () => {
                isDark.value = !isDark.value;
                const theme = isDark.value ? 'dark' : 'light';
                document.documentElement.classList.toggle('dark', isDark.value);
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('dprd-admin-theme', theme);
            };

            const updateClock = () => {
                const now = new Date();
                const options = { timeZone: 'Asia/Makassar' };
                headerDay.value = new Intl.DateTimeFormat('id-ID', { ...options, weekday: 'long' }).format(now).toUpperCase();
                headerDate.value = new Intl.DateTimeFormat('id-ID', { ...options, day: 'numeric', month: 'long', year: 'numeric' }).format(now);
                headerShortDate.value = new Intl.DateTimeFormat('id-ID', { ...options, day: 'numeric', month: 'short' }).format(now);
                headerTime.value = new Intl.DateTimeFormat('id-ID', { ...options, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).format(now).replaceAll('.', ':');
            };

            const loadWeather = async () => {
                try {
                    const response = await fetch(WEATHER_URL);
                    const payload = await response.json();
                    if (payload.status === 'success' && payload.cuaca) {
                        weather.value = payload.cuaca;
                        const loc = [payload.lokasi?.desa, payload.lokasi?.kecamatan].filter((v) => v && v !== '-').join(', ');
                        weatherLocation.value = loc || 'Sulawesi Tengah';
                    }
                } catch (_) {
                    weather.value.suhu = '--°C';
                    weather.value.kondisi = 'Tidak tersedia';
                }
            };

            let clockInterval = null;
            let weatherInterval = null;

            onMounted(() => {
                updateClock();
                loadWeather();
                clockInterval = setInterval(updateClock, 1000);
                weatherInterval = setInterval(loadWeather, 1800000);
            });

            onUnmounted(() => {
                if (clockInterval) clearInterval(clockInterval);
                if (weatherInterval) clearInterval(weatherInterval);
            });

            const items = ref(RAW_ITEMS);
            const selectedDate = ref(null);
            const expandedItemId = ref(null);

            const now = new Date();
            const todayKey = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

            const datesWithAgendas = [...new Set(items.value.map((i) => i.tanggal).filter(Boolean))].sort();

            let initialMonth = now.getMonth();
            let initialYear = SELECTED_YEAR;
            if (datesWithAgendas.length > 0) {
                const firstWithYear = datesWithAgendas.find((d) => d.startsWith(String(SELECTED_YEAR)));
                if (firstWithYear) {
                    const [y, m] = firstWithYear.split('-').map(Number);
                    initialYear = y;
                    initialMonth = m - 1;
                } else {
                    const [y, m] = datesWithAgendas[0].split('-').map(Number);
                    initialYear = y;
                    initialMonth = m - 1;
                }
            }

            const cursorYear = ref(initialYear);
            const cursorMonth = ref(initialMonth);

            const currentMonthKey = computed(() => `${cursorYear.value}-${String(cursorMonth.value + 1).padStart(2, '0')}`);

            const formatFullDate = (dateStr) => {
                if (!dateStr) return '';
                const [y, m, d] = dateStr.split('-').map(Number);
                const dt = new Date(y, m - 1, d);
                return `${dayNames[dt.getDay()]}, ${dt.getDate()} ${monthNames[dt.getMonth()]} ${dt.getFullYear()}`;
            };

            const formatDateDmy = (dateStr) => {
                if (!dateStr) return '';
                const [y, m, d] = dateStr.split('-').map(Number);
                return `${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}/${y}`;
            };

            const dateHeading = computed(() => {
                if (selectedDate.value) {
                    return formatFullDate(selectedDate.value);
                }
                return `Semua Tanggal — ${monthNames[cursorMonth.value]} ${cursorYear.value}`;
            });

            const filteredItems = computed(() => {
                if (selectedDate.value) {
                    return items.value.filter((i) => i.tanggal === selectedDate.value);
                }
                const monthKey = currentMonthKey.value;
                return items.value.filter((i) => {
                    if (i.tanggal) {
                        return i.tanggal.startsWith(monthKey);
                    }
                    if (i.bulan_mulai && i.bulan_selesai) {
                        return monthKey >= i.bulan_mulai && monthKey <= i.bulan_selesai;
                    }
                    return true;
                });
            });

            const calendarPrevDays = computed(() => {
                const startOffset = (new Date(cursorYear.value, cursorMonth.value, 1).getDay() + 6) % 7;
                const daysInPrevMonth = new Date(cursorYear.value, cursorMonth.value, 0).getDate();
                const list = [];
                for (let i = 0; i < startOffset; i++) {
                    list.push(daysInPrevMonth - startOffset + 1 + i);
                }
                return list;
            });

            const calendarDays = computed(() => {
                const daysInMonth = new Date(cursorYear.value, cursorMonth.value + 1, 0).getDate();
                const list = [];
                for (let d = 1; d <= daysInMonth; d++) {
                    const dateKey = `${cursorYear.value}-${String(cursorMonth.value + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    const hasAgenda = items.value.some((i) => i.tanggal === dateKey);
                    const isSelected = selectedDate.value === dateKey;
                    const isToday = todayKey === dateKey;

                    let btnClass = 'h-7 sm:h-8 lg:aspect-square flex flex-col items-center justify-center rounded-none text-[11px] sm:text-xs font-semibold relative transition cursor-pointer ';
                    if (isSelected) {
                        btnClass += 'bg-blue-600 text-white font-bold shadow-xs hover:bg-blue-700';
                    } else if (isToday) {
                        btnClass += 'border-2 border-blue-500 text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-800';
                    } else {
                        btnClass += 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800';
                    }

                    const dotClass = `size-1 sm:size-1.5 rounded-none absolute bottom-0.5 sm:bottom-1 ${isSelected ? 'bg-white' : 'bg-amber-500'}`;

                    list.push({ day: d, dateKey, hasAgenda, isSelected, isToday, btnClass, dotClass });
                }
                return list;
            });

            const calendarNextDays = computed(() => {
                const startOffset = (new Date(cursorYear.value, cursorMonth.value, 1).getDay() + 6) % 7;
                const daysInMonth = new Date(cursorYear.value, cursorMonth.value + 1, 0).getDate();
                const totalCells = startOffset + daysInMonth;
                const trailingCells = (7 - (totalCells % 7)) % 7;
                const list = [];
                for (let i = 1; i <= trailingCells; i++) {
                    list.push(i);
                }
                return list;
            });

            const selectDate = (dateKey) => {
                if (selectedDate.value === dateKey) {
                    selectedDate.value = null;
                } else {
                    selectedDate.value = dateKey;
                }
            };

            const prevMonth = () => {
                if (cursorMonth.value === 0) {
                    cursorMonth.value = 11;
                    cursorYear.value--;
                } else {
                    cursorMonth.value--;
                }
                selectedDate.value = null;
            };

            const nextMonth = () => {
                if (cursorMonth.value === 11) {
                    cursorMonth.value = 0;
                    cursorYear.value++;
                } else {
                    cursorMonth.value++;
                }
                selectedDate.value = null;
            };

            const goToThisMonth = () => {
                cursorYear.value = now.getFullYear();
                cursorMonth.value = now.getMonth();
                selectedDate.value = todayKey;
            };

            const resetFilter = () => {
                selectedDate.value = null;
            };

            const navDate = (dir) => {
                const monthItems = filteredItems.value.filter((i) => Boolean(i.tanggal));
                const monthDates = [...new Set(monthItems.map((i) => i.tanggal))].sort();
                if (monthDates.length === 0) return;

                if (!selectedDate.value) {
                    selectedDate.value = dir === 'prev' ? monthDates[monthDates.length - 1] : monthDates[0];
                    return;
                }

                const currentIndex = monthDates.indexOf(selectedDate.value);
                if (currentIndex === -1) {
                    selectedDate.value = monthDates[0];
                } else if (dir === 'prev') {
                    selectedDate.value = currentIndex > 0 ? monthDates[currentIndex - 1] : monthDates[monthDates.length - 1];
                } else {
                    selectedDate.value = currentIndex < monthDates.length - 1 ? monthDates[currentIndex + 1] : monthDates[0];
                }
            };

            const handleToggle = (e, id) => {
                if (e.target.open) {
                    expandedItemId.value = id;
                } else if (expandedItemId.value === id) {
                    expandedItemId.value = null;
                }
            };

            return {
                weather,
                weatherLocation,
                weatherLabel,
                headerDay,
                headerDate,
                headerShortDate,
                headerTime,
                isDark,
                toggleTheme,
                monthNames,
                cursorYear,
                cursorMonth,
                selectedDate,
                dateHeading,
                filteredItems,
                calendarPrevDays,
                calendarDays,
                calendarNextDays,
                selectDate,
                prevMonth,
                nextMonth,
                goToThisMonth,
                resetFilter,
                navDate,
                formatFullDate,
                formatDateDmy,
                expandedItemId,
                handleToggle,
            };
        },
    }).mount('#banmus-app');
</script>
</body>
</html>
