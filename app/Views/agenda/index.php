<?php
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$vueVersion = is_file(FCPATH . 'assets/vendor/vue/vue.global.prod.js') ? filemtime(FCPATH . 'assets/vendor/vue/vue.global.prod.js') : time();
$cssVersion = is_file(FCPATH . 'assets/css/agenda.css') ? filemtime(FCPATH . 'assets/css/agenda.css') : time();
$logoVersion = is_file(FCPATH . 'assets/images/logo_dprd.png') ? filemtime(FCPATH . 'assets/images/logo_dprd.png') : time();
$prelineVersion = is_file(FCPATH . 'assets/vendor/preline/preline.js') ? filemtime(FCPATH . 'assets/vendor/preline/preline.js') : time();
$isMember = is_array($member ?? null);
$isAdmin = ! $isMember && ! empty($isAdmin);
$pageTitle = $isMember ? 'Agenda Anggota DPRD' : 'Agenda DPRD';

$memberInitials = 'AD';
$memberSubTitle = 'Anggota DPRD';
if ($isMember) {
    $fullName = trim((string) ($member['name'] ?? 'Anggota DPRD'));
    $parts = preg_split('/\s+/', $fullName) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $clean = preg_replace('/[^A-Za-z]/', '', $p);
        if ($clean !== '') {
            $initials .= mb_substr($clean, 0, 1);
        }
    }
    $memberInitials = strtoupper($initials ?: 'AD');
    $memberSubTitle = ! empty($member['komisi'])
        ? (string) $member['komisi']
        : (! empty($member['fraksi'])
            ? (string) $member['fraksi']
            : (string) ($member['jabatan'] ?? 'Anggota DPRD'));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($pageTitle) ?> - DPRD Provinsi Sulawesi Tengah</title>
    <meta name="description" content="Agenda dan jadwal rapat DPRD Provinsi Sulawesi Tengah." />
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/logo_dprd.png?v=' . $logoVersion) ?>" />
    <link rel="preload" as="image" href="<?= esc($logoUrl) ?><?= str_contains($logoUrl, '?') ? '&' : '?' ?>v=<?= $logoVersion ?>" />
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-400-normal.woff2') ?>" as="font" type="font/woff2" crossorigin />
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/files/inter-latin-600-normal.woff2') ?>" as="font" type="font/woff2" crossorigin />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <script {csp-script-nonce}>
        (() => {
            const stored = localStorage.getItem('dprd-admin-theme');
            const theme = stored === 'dark' || stored === 'light'
                ? stored
                : 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link href="<?= base_url('assets/css/agenda.css?v=' . $cssVersion) ?>" rel="stylesheet" />
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased">
<div id="agenda-app" v-cloak>
    <header class="sticky top-0 z-50 border-b border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md shadow-xs">
        <div class="relative overflow-hidden">
            <div class="agenda-header-motif" aria-hidden="true"></div>
            <div class="relative z-10 mx-auto flex min-h-16 w-full items-center justify-between gap-3 px-3.5 py-2.5 sm:min-h-20 sm:px-6 xl:px-8">
            <a class="flex items-center gap-3 min-w-0 flex-1" href="<?= esc($portalUrl) ?>" aria-label="Halaman agenda DPRD">
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

                <button class="inline-flex justify-center items-center size-10 sm:size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700/80 transition" type="button" @click="toggleTheme" :aria-label="isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap'">
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
            <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-200 tabular-nums">{{ weatherLabel }} · {{ headerShortDate }} · {{ headerTime }} WITA</span>
        </div>

        <nav class="border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-900/80 backdrop-blur-md" aria-label="Navigasi agenda">
            <div class="mx-auto flex w-full flex-col gap-2 px-3 py-2 sm:w-[min(1480px,calc(100%-32px))] sm:flex-row sm:items-center sm:justify-between sm:px-0">
                <div class="flex min-w-0 flex-1 items-center gap-1.5 sm:gap-2">
                    <button
                        :class="{ 'invisible pointer-events-none': !canScrollUnitsLeft }"
                        class="inline-flex size-7 sm:size-8 shrink-0 items-center justify-center text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition cursor-pointer"
                        type="button"
                        :aria-hidden="!canScrollUnitsLeft"
                        :tabindex="canScrollUnitsLeft ? 0 : -1"
                        aria-label="Geser kelompok peserta ke kiri"
                        title="Geser ke kiri"
                        @click="scrollUnitFilters(-1)">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                    </button>

                    <div
                        ref="unitScroller"
                        :class="unitScrollMaskClass"
                        class="agenda-unit-scroll flex w-full min-w-0 items-center gap-1.5 sm:gap-2 overflow-x-auto py-1 px-2 sm:px-4 no-scrollbar"
                        @scroll.passive="updateUnitScrollState">
                        <?php if ($isMember): ?>
                            <button :class="navButtonClass('saya')" :aria-pressed="activeNavigation === 'saya'" type="button" @click="setNavigation('saya')">Jadwal Saya</button>
                        <?php endif; ?>
                        <button :class="navButtonClass('all')" :aria-pressed="activeNavigation === 'all'" type="button" @click="setNavigation('all')">Semua</button>
                        <button
                            v-if="komisiUnits.length > 0"
                            ref="komisiButtonRef"
                            :class="komisiButtonClass"
                            type="button"
                            @click.stop="toggleKomisiDropdown"
                            aria-haspopup="true"
                            :aria-expanded="isKomisiOpen"
                            :aria-pressed="isKomisiActive"
                        >
                            <span>{{ komisiButtonLabel }}</span>
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" class="transition-transform duration-200" :class="{ 'rotate-180': isKomisiOpen }">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>
                        <button v-for="unit in nonKomisiUnits" :key="unit.id" :class="navButtonClass('unit:' + unit.id)" :aria-pressed="activeNavigation === 'unit:' + unit.id" :title="unit.nama" type="button" @click="setNavigation('unit:' + unit.id)">{{ compactUnitName(unit.nama) }}</button>
                    </div>

                    <teleport to="body">
                        <div
                            v-if="isKomisiOpen"
                            ref="komisiDropdownRef"
                            :style="komisiDropdownStyle"
                            class="fixed z-[60] min-w-48 rounded-2xl border border-slate-200/90 dark:border-slate-700/90 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-1.5 shadow-xl"
                            @click.stop
                        >
                            <button
                                type="button"
                                class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition cursor-pointer"
                                :class="komisiItemClass('komisi')"
                                @click="selectKomisiFilter('komisi')"
                            >
                                <span>Semua Komisi (I–IV)</span>
                                <svg v-if="activeNavigation === 'komisi'" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="text-blue-600 dark:text-blue-400"><polyline points="20 6 9 17 4 12"/></svg>
                            </button>
                            <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                            <button
                                v-for="k in komisiUnits"
                                :key="k.id"
                                type="button"
                                class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition cursor-pointer"
                                :class="komisiItemClass('unit:' + k.id)"
                                @click="selectKomisiFilter('unit:' + k.id)"
                            >
                                <span>{{ k.nama }}</span>
                                <svg v-if="activeNavigation === 'unit:' + k.id" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="text-blue-600 dark:text-blue-400"><polyline points="20 6 9 17 4 12"/></svg>
                            </button>
                        </div>
                    </teleport>

                    <teleport to="body">
                        <div
                            v-if="isCalendarOpen"
                            ref="calendarRef"
                            :style="calendarStyle"
                            role="dialog"
                            aria-label="Kalender agenda"
                            class="fixed z-[60] w-72 rounded-2xl border border-slate-200/90 dark:border-slate-700/90 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-3.5 shadow-xl"
                            @click.stop
                        >
                            <div class="flex items-center justify-between gap-2">
                                <button type="button" class="inline-flex size-9 sm:size-8 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition" aria-label="Bulan sebelumnya" @click="shiftCalendar(-1)">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <span class="text-sm font-extrabold text-slate-900 dark:text-white">{{ calendarLabel }}</span>
                                <button type="button" class="inline-flex size-9 sm:size-8 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition" aria-label="Bulan berikutnya" @click="shiftCalendar(1)">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>

                            <div class="mt-3 flex items-center gap-1.5">
                                <button
                                    v-for="preset in calendarPresets"
                                    :key="preset.key"
                                    type="button"
                                    class="flex-1 py-1.5 px-1.5 rounded-lg text-[11px] font-bold whitespace-nowrap transition cursor-pointer"
                                    :class="presetChipClass(preset.key)"
                                    :aria-pressed="periodMode === preset.key"
                                    @click="setCalendarScope(preset.key)"
                                >
                                    {{ preset.label }}
                                </button>
                            </div>

                            <div class="mt-2.5 grid grid-cols-7 gap-1 text-center text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
                            </div>
                            <div class="mt-1 grid grid-cols-7 gap-1">
                                <template v-for="(week, wi) in calendarWeeks" :key="'cal-week-' + wi">
                                    <button
                                        v-for="(cell, ci) in week"
                                        :key="(cell ? cell.key : 'cal-blank-' + wi + '-' + ci)"
                                        type="button"
                                        :disabled="!cell || cell.count === 0"
                                        class="relative flex h-11 w-full flex-col items-center justify-center rounded-xl text-xs font-bold transition disabled:cursor-default"
                                        :class="calendarCellClass(cell)"
                                        @click="pickCalendarDay(cell)"
                                    >
                                        <span>{{ cell ? cell.day : '' }}</span>
                                        <span v-if="cell && cell.count > 0" class="absolute bottom-1 flex items-center gap-0.5">
                                            <span v-for="n in Math.min(cell.count, 3)" :key="n" class="size-1.5 rounded-full bg-blue-500"></span>
                                        </span>
                                        <span v-if="cell && cell.isToday" class="absolute inset-0 rounded-xl ring-2 ring-blue-500 pointer-events-none"></span>
                                    </button>
                                </template>
                            </div>

                            <p class="mt-3 text-xs font-medium text-slate-500 dark:text-slate-400">Klik tanggal bertanda untuk membuka kartunya.</p>
                        </div>
                    </teleport>

                    <button
                        :class="{ 'invisible pointer-events-none': !canScrollUnitsRight }"
                        class="inline-flex size-7 sm:size-8 shrink-0 items-center justify-center text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition cursor-pointer"
                        type="button"
                        :aria-hidden="!canScrollUnitsRight"
                        :tabindex="canScrollUnitsRight ? 0 : -1"
                        aria-label="Geser kelompok peserta ke kanan"
                        title="Geser ke kanan"
                        @click="scrollUnitFilters(1)">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </button>
                </div>
            </div>
        </nav>
    </div>
    </header>

    <div class="mx-auto w-full px-3 pt-2.5 sm:px-6 sm:pt-3.5 xl:w-[min(1480px,calc(100%-32px))] xl:px-0">
        <h1 class="sr-only">Agenda DPRD Provinsi Sulawesi Tengah</h1>

        <div class="flex items-center gap-2 border-b border-slate-200/80 dark:border-slate-800/80 pb-2">
            <span class="relative flex h-2.5 w-2.5 items-center justify-center shrink-0">
                <span v-if="activeLiveAgendas.length > 0" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full" :class="activeLiveAgendas.length > 0 ? 'bg-rose-500' : 'bg-slate-400 dark:bg-slate-500'"></span>
            </span>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 truncate">
                <span class="sm:hidden">Hari Ini</span>
                <span class="hidden sm:inline">Sidang &amp; Kegiatan Hari Ini</span>
            </h2>
            <span v-if="upcomingTodayAgendas.length > 0 && activeLiveAgendas.length === 0" class="text-[11px] font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ upcomingTodayAgendas.length }} Terjadwal</span>
            <div class="flex items-center gap-2 ml-auto shrink-0">
                <button
                    class="inline-flex items-center justify-center gap-x-1.5 sm:gap-x-2 min-h-[38px] sm:min-h-0 py-1.5 sm:py-2 px-2.5 sm:px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 text-slate-700 dark:text-slate-200 text-xs sm:text-sm font-bold shadow-xs transition shrink-0 cursor-pointer"
                    type="button"
                    ref="calendarAnchor"
                    aria-haspopup="dialog"
                    :aria-expanded="isCalendarOpen"
                    aria-label="Pilih periode agenda lewat kalender (Filter periode agenda rapat dan Filter periode jadwal umum)"
                    @click="toggleCalendar"
                >
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="text-blue-600 dark:text-blue-400 shrink-0"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span class="inline text-xs sm:text-sm font-bold">{{ calendarButtonLabel }}</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" class="text-slate-400 shrink-0"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            </div>
        </div>

        <div v-if="todayAgendas.length > 0" class="mt-2 overflow-hidden border-b border-slate-200/80 dark:border-slate-800/80">
            <ol>
                <li v-for="item in [...activeLiveAgendas, ...upcomingTodayAgendas]" :key="'today-card-' + item.key" class="group px-3.5 sm:px-6 border-b border-slate-200 dark:border-slate-800 last:border-b-0 py-2.5 sm:py-3 flex items-center justify-between gap-3 transition-colors" :class="item.status === 'berlangsung' ? 'bg-rose-50/70 dark:bg-rose-950/30' : 'bg-white dark:bg-slate-900'">
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <div class="flex items-center gap-1.5 text-[11px] sm:text-xs font-medium text-slate-500 dark:text-slate-400">
                            <span v-if="item.status === 'berlangsung'" class="relative flex h-2 w-2 items-center justify-center shrink-0">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                            </span>
                            <span class="tabular-nums whitespace-nowrap">{{ executionTime(item) }}</span>
                            <span v-if="item.status === 'berlangsung'" class="text-rose-600 dark:text-rose-400 font-semibold truncate">· Sedang Berlangsung</span>
                        </div>
                        <p class="text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-1 sm:line-clamp-2 leading-snug [text-wrap:pretty]">{{ item.judul }}</p>
                    </div>
                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        <a v-if="item.has_stream" :href="streamUrl(item)" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 min-h-[30px] sm:min-h-8 py-1 sm:py-1.5 px-2.5 sm:px-3 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[11px] sm:text-xs font-bold shadow-xs transition whitespace-nowrap">
                            <svg viewBox="0 0 24 24" class="size-3 sm:size-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                            <span>Tonton Live</span>
                        </a>
                        <button type="button" @click="focusAgenda(item)" class="inline-flex items-center gap-1 min-h-[30px] sm:min-h-8 py-1 sm:py-1.5 px-2.5 sm:px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-[11px] sm:text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-xs transition cursor-pointer whitespace-nowrap">
                            <span>Buka</span>
                            <svg viewBox="0 0 24 24" class="size-3 sm:size-3" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>
                </li>
            </ol>
        </div>
        <p v-if="todayAgendas.length === 0" class="py-3 text-xs font-medium text-slate-600 dark:text-slate-300 border-b border-slate-200/80 dark:border-slate-800/80">
            Tidak ada jadwal sidang atau kegiatan dewan untuk hari ini.<span v-if="nearestUpcomingAgenda"> Agenda berikutnya: <strong class="text-slate-800 dark:text-slate-200 font-semibold">{{ fullDate(nearestUpcomingAgenda.tanggal) }}</strong> ({{ nearestUpcomingAgenda.judul }})</span>
        </p>

        <div class="xl:hidden mt-3 flex border-b border-slate-200 dark:border-slate-800 gap-x-4" role="tablist" aria-label="Pilih tampilan sisi agenda">
            <button
                id="tab-btn-rapat"
                type="button"
                role="tab"
                aria-controls="tab-panel-rapat"
                :aria-selected="activeMobileTab === 'rapat'"
                @click="setMobileTab('rapat')"
                :class="mobileTabClass('rapat')"
                class="flex-1 -mb-px py-2.5 px-2 border-b-2 text-xs sm:text-sm text-center transition-all flex items-center justify-center gap-1.5 cursor-pointer"
            >
                <span>Rapat &amp; Sidang</span>
            </button>
            <button
                id="tab-btn-umum"
                type="button"
                role="tab"
                aria-controls="tab-panel-umum"
                :aria-selected="activeMobileTab === 'umum'"
                @click="setMobileTab('umum')"
                :class="mobileTabClass('umum')"
                class="flex-1 -mb-px py-2.5 px-2 border-b-2 text-xs sm:text-sm text-center transition-all flex items-center justify-center gap-1.5 cursor-pointer"
            >
                <span>Kegiatan &amp; Audiensi</span>
            </button>
        </div>
    </div>

    <main class="mx-auto w-full min-w-0 px-3 py-4 sm:px-6 sm:py-6 xl:w-[min(1480px,calc(100%-32px))] xl:px-0 xl:grid xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] xl:gap-6 xl:items-start">
        <section
            id="tab-panel-rapat"
            role="region"
            aria-labelledby="tab-btn-rapat"
            class="rounded-none bg-white dark:bg-slate-900 overflow-hidden"
            :class="{ 'hidden xl:block': activeMobileTab !== 'rapat' }"
        >
            <div class="p-0">
                <div class="flex items-center justify-between gap-3 border-b border-slate-400 dark:border-slate-600 px-4 py-3.5 sm:px-6 sm:py-4">
                    <div class="min-w-0">
                        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white truncate underline decoration-slate-400 decoration-2 underline-offset-[6px] dark:decoration-slate-500">Agenda Rapat &amp; Sidang</h2>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a class="inline-flex items-center gap-1 py-1 px-2.5 sm:px-3 rounded-md text-xs font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 border border-blue-500/30 hover:border-blue-500/50 bg-blue-50/60 dark:bg-blue-950/30 transition shadow-2xs" href="<?= base_url('agenda/jadwal-banmus') ?>" title="Lihat Proyeksi &amp; SK" aria-label="Lihat Proyeksi &amp; SK">
                            <span>Proyeksi Banmus</span>
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>
                </div>

                <div v-if="initialLoading" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <div v-for="item in 3" :key="'rapat-skeleton-' + item" class="animate-pulse p-4 sm:p-5 h-20 w-full bg-slate-50/50 dark:bg-slate-800/20"></div>
                </div>

                <div v-else-if="loadError" class="p-4 sm:p-6">
                    <div role="alert" class="flex items-center gap-3 p-4 border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 text-sm">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3h.01" stroke-linecap="round"/></svg>
                        <div class="flex-1">
                            <p class="font-semibold">Agenda rapat gagal dimuat.</p>
                            <button class="mt-1 text-xs font-bold underline hover:no-underline" type="button" @click="loadAgenda">Coba lagi</button>
                        </div>
                    </div>
                </div>

                <div v-else-if="filteredAgendas.length === 0" class="grid min-h-80 place-items-center p-8 text-center">
                    <div>
                        <svg class="mx-auto text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round"/></svg>
                        <h2 class="mt-4 text-base font-bold text-slate-900 dark:text-white">Belum ada agenda rapat</h2>
                        <p v-if="activeNavigation === 'saya'" class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                            Tidak ada jadwal rapat untuk Anda pada periode ini. Klik <button type="button" class="text-blue-600 dark:text-blue-400 font-semibold underline hover:text-blue-700 cursor-pointer" @click="setNavigation('all')">Semua</button> untuk melihat agenda dewan lainnya.
                        </p>
                        <p v-else class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Tidak ada jadwal rapat untuk kelompok peserta dan periode yang dipilih.</p>
                    </div>
                </div>

                <div v-else class="min-w-0">
                    <div>
                        <details
                            v-for="item in paginatedAgendas"
                            :key="item.key"
                            :id="'agenda-card-' + item.key"
                            name="agenda-banmus-accordion"
                            class="group agenda-collapse mx-4 sm:mx-6 border-b border-slate-400 dark:border-slate-600 last:border-b-0"
                            :open="expandedAgendaKey === item.key"
                            @toggle="handleAgendaToggle($event, item.key)"
                        >
                            <summary class="grid min-h-0 grid-cols-[2.5rem_minmax(0,1fr)] items-center gap-3 overflow-hidden py-3.5 pr-8 sm:grid-cols-[2.75rem_minmax(0,1fr)] sm:gap-4 sm:pr-8 cursor-pointer select-none">
                                <div class="shrink-0 text-center select-none w-10 sm:w-11">
                                    <span>
                                        <span class="block text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-tight">{{ shortMonth(item.tanggal) }}</span>
                                        <span class="block text-lg sm:text-xl font-semibold text-slate-800 dark:text-slate-200 leading-none mt-0.5 tabular-nums">{{ dayNumber(item.tanggal) }}</span>
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <span class="line-clamp-2 text-sm font-medium leading-snug text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors sm:text-base [text-wrap:pretty]">{{ item.judul }}</span>
                                    <div v-if="(item.units && item.units.length > 0) || item.komisi" class="mt-1.5 flex min-w-0 flex-wrap items-center gap-1.5">
                                        <template v-if="item.units && item.units.length > 0">
                                            <span
                                                v-for="u in item.units"
                                                :key="u.id"
                                                class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold border-[1.5px] border-blue-500/80 dark:border-blue-400/80 text-blue-700 dark:text-blue-300 rounded-md"
                                            >
                                                {{ u.nama }}
                                            </span>
                                        </template>
                                        <template v-else-if="item.komisi">
                                            <span
                                                v-for="k in item.komisi.split(',').map((s) => s.trim()).filter(Boolean)"
                                                :key="k"
                                                class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold border-[1.5px] border-blue-500/80 dark:border-blue-400/80 text-blue-700 dark:text-blue-300 rounded-md"
                                            >
                                                {{ k }}
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </summary>

                            <div class="min-w-0 pt-2 pb-4 sm:pb-5 space-y-3">
                                <div class="space-y-1.5 text-xs text-slate-700 dark:text-slate-300">
                                    <div class="flex items-start gap-2">
                                        <span class="w-28 sm:w-32 shrink-0 text-slate-500 dark:text-slate-400">Waktu</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200">{{ executionTime(item) }}</span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="w-28 sm:w-32 shrink-0 text-slate-500 dark:text-slate-400">Ruangan</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200">{{ item.ruangan || '-' }}</span>
                                    </div>
                                    <div v-if="item.status && item.status !== 'proyeksi'" class="flex items-start gap-2">
                                        <span class="w-28 sm:w-32 shrink-0 text-slate-500 dark:text-slate-400">Status</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200">{{ statusLabel(item.status) }}</span>
                                    </div>
                                    <div v-if="item.keterangan" class="flex items-start gap-2">
                                        <span class="w-28 sm:w-32 shrink-0 text-slate-500 dark:text-slate-400">Pokok Bahasan</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 leading-relaxed text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ item.keterangan }}</span>
                                    </div>
                                    <div v-if="item.pihak_eksternal" class="flex items-start gap-2">
                                        <span class="w-28 sm:w-32 shrink-0 text-slate-500 dark:text-slate-400">Pihak Eksternal</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 leading-relaxed text-slate-700 dark:text-slate-200">{{ item.pihak_eksternal }}</span>
                                    </div>
                                </div>

                                <div v-if="item.has_undangan || item.has_materi || item.has_stream || item.has_risalah || item.risalah_status || item.materi_restricted || item.stream_restricted" class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 w-full sm:w-auto">
                                        <?php if ($isMember): ?>
                                            <a v-if="item.has_undangan" class="flex-1 sm:flex-initial min-h-[34px] sm:min-h-[36px] inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 sm:px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-[11px] sm:text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 shadow-xs transition whitespace-nowrap" :href="item.undangan_url" target="_blank" rel="noopener noreferrer">
                                                <svg viewBox="0 0 24 24" class="size-3.5 text-slate-500 dark:text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                                <span>Undangan</span>
                                            </a>
                                        <?php endif; ?>

                                        <a v-if="item.has_materi" class="flex-1 sm:flex-initial min-h-[34px] sm:min-h-[36px] inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 sm:px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-[11px] sm:text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 shadow-xs transition whitespace-nowrap" :href="item.materi_url" target="_blank" rel="noopener noreferrer">
                                            <svg viewBox="0 0 24 24" class="size-3.5 text-slate-500 dark:text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                                            <span>Bahan Rapat</span>
                                        </a>

                                        <a v-if="item.has_stream" class="flex-1 sm:flex-initial min-h-[34px] sm:min-h-[36px] inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 sm:px-3 rounded-lg border border-rose-200 dark:border-rose-800/80 bg-rose-50/70 dark:bg-rose-950/40 text-[11px] sm:text-xs font-semibold text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 shadow-xs transition whitespace-nowrap" :href="item.stream_url" target="_blank" rel="noopener noreferrer">
                                            <svg viewBox="0 0 24 24" class="size-3.5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                                            <span>Live / Video</span>
                                        </a>

                                        <?php if ($isMember): ?>
                                            <button
                                                v-if="item.has_risalah"
                                                type="button"
                                                class="flex-1 sm:flex-initial min-h-[34px] sm:min-h-[36px] inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 sm:px-3.5 rounded-lg border border-blue-300 dark:border-blue-700/80 bg-blue-50/90 dark:bg-blue-950/40 text-[11px] sm:text-xs font-semibold text-blue-800 dark:text-blue-200 hover:bg-blue-100 dark:hover:bg-blue-900/60 shadow-xs transition cursor-pointer whitespace-nowrap"
                                                @click="openRisalahModal(item)"
                                                aria-haspopup="dialog"
                                                aria-expanded="false"
                                                aria-controls="hs-risalah-modal"
                                            >
                                                <svg viewBox="0 0 24 24" class="size-3.5 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                                <span>Risalah (Notulen AI)</span>
                                            </button>
                                            <span v-else-if="item.risalah_status" class="flex-1 sm:flex-initial min-h-[34px] sm:min-h-[36px] inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 sm:px-3.5 rounded-lg text-[11px] sm:text-xs font-semibold bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/30 select-none whitespace-nowrap">
                                                <span class="inline-block size-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                                                <span>Sedang Ditinjau Notulis</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($isMember): ?>
                                        <div v-if="item.materi_restricted || item.stream_restricted" class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-700/80 dark:text-amber-400/80">
                                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            <span v-if="item.materi_restricted && item.stream_restricted">Bahan &amp; siaran khusus peserta</span>
                                            <span v-else-if="item.materi_restricted">Bahan khusus peserta</span>
                                            <span v-else>Siaran khusus peserta</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>
                        </template>
                    </template>
                    </div>
                </div>
            </div>
        </section>

        <section
            id="tab-panel-umum"
            role="region"
            aria-label="Jadwal Umum"
            aria-labelledby="tab-btn-umum"
            class="rounded-none bg-white dark:bg-slate-900 overflow-hidden mt-4 xl:mt-0"
            :class="{ 'hidden xl:block': activeMobileTab !== 'umum' }"
        >
            <div class="p-0">
                <div class="flex items-center justify-between gap-3 border-b border-slate-400 dark:border-slate-600 px-4 py-3.5 sm:px-6 sm:py-4">
                    <div class="min-w-0">
                        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white truncate underline decoration-slate-400 decoration-2 underline-offset-[6px] dark:decoration-slate-500">Kegiatan &amp; Audiensi Publik</h2>
                    </div>
                </div>

                <div v-if="initialLoading" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <div v-for="item in 3" :key="'general-skeleton-' + item" class="animate-pulse p-4 sm:p-5 h-20 w-full bg-slate-50/50 dark:bg-slate-800/20"></div>
                </div>

                <div v-else-if="loadError" class="p-4 sm:p-6">
                    <div role="alert" class="flex items-center gap-3 p-4 border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 text-sm">
                        <div class="flex-1">
                            <p class="font-semibold">Kegiatan &amp; audiensi gagal dimuat.</p>
                            <button class="mt-1 text-xs font-bold underline hover:no-underline" type="button" @click="loadAgenda">Coba lagi</button>
                        </div>
                    </div>
                </div>

                <div v-else-if="filteredGeneralAgendas.length === 0" class="grid min-h-80 place-items-center p-8 text-center">
                    <div>
                        <svg class="mx-auto text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round"/></svg>
                        <h2 class="mt-4 text-base font-bold text-slate-900 dark:text-white">Belum ada kegiatan &amp; audiensi</h2>
                        <p v-if="activeNavigation === 'saya'" class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                            Tidak ada kegiatan atau audiensi untuk Anda pada periode ini. Klik <button type="button" class="text-blue-600 dark:text-blue-400 font-semibold underline hover:text-blue-700 cursor-pointer" @click="setNavigation('all')">Semua</button> untuk melihat kegiatan dewan lainnya.
                        </p>
                        <p v-else class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Tidak ada kegiatan untuk kelompok peserta dan periode yang dipilih.</p>
                    </div>
                </div>

                <div v-else class="min-w-0">
                    <div>
                        <details
                            v-for="item in paginatedGeneralAgendas"
                            :key="item.key"
                            :id="'agenda-card-' + item.key"
                            name="agenda-general-accordion"
                            class="group agenda-collapse mx-4 sm:mx-6 border-b border-slate-400 dark:border-slate-600 last:border-b-0"
                            :open="expandedGeneralKey === item.key"
                            @toggle="handleGeneralToggle($event, item.key)"
                        >
                            <summary class="grid min-h-0 grid-cols-[2.5rem_minmax(0,1fr)] items-center gap-3 overflow-hidden py-3.5 pr-8 sm:grid-cols-[2.75rem_minmax(0,1fr)] sm:gap-4 sm:pr-8 cursor-pointer select-none">
                                <div class="shrink-0 text-center select-none w-10 sm:w-11">
                                    <span>
                                        <span class="block text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-tight">{{ shortMonth(item.tanggal) }}</span>
                                        <span class="block text-lg sm:text-xl font-semibold text-slate-800 dark:text-slate-200 leading-none mt-0.5 tabular-nums">{{ dayNumber(item.tanggal) }}</span>
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <span class="line-clamp-2 text-sm font-medium leading-snug text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors sm:text-base [text-wrap:pretty]">{{ item.judul }}</span>
                                    <div v-if="(item.units && item.units.length > 0) || item.komisi" class="mt-1.5 flex min-w-0 flex-wrap items-center gap-1.5">
                                        <template v-if="item.units && item.units.length > 0">
                                            <span
                                                v-for="u in item.units"
                                                :key="u.id"
                                                class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold border-[1.5px] border-blue-500/80 dark:border-blue-400/80 text-blue-700 dark:text-blue-300 rounded-md"
                                            >
                                                {{ u.nama }}
                                            </span>
                                        </template>
                                        <template v-else-if="item.komisi">
                                            <span
                                                v-for="k in item.komisi.split(',').map((s) => s.trim()).filter(Boolean)"
                                                :key="k"
                                                class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold border-[1.5px] border-blue-500/80 dark:border-blue-400/80 text-blue-700 dark:text-blue-300 rounded-md"
                                            >
                                                {{ k }}
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </summary>

                            <div class="min-w-0 pt-2 pb-4 sm:pb-5 space-y-3">
                                <div class="space-y-1.5 text-xs text-slate-700 dark:text-slate-300">
                                    <div class="flex items-start gap-2">
                                        <span class="w-28 sm:w-32 shrink-0 text-slate-500 dark:text-slate-400">Waktu</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200">{{ executionTime(item) }}</span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="w-28 sm:w-32 shrink-0 text-slate-500 dark:text-slate-400">Ruangan</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200">{{ item.ruangan || '-' }}</span>
                                    </div>
                                    <div v-if="item.status && item.status !== 'proyeksi'" class="flex items-start gap-2">
                                        <span class="w-28 sm:w-32 shrink-0 text-slate-500 dark:text-slate-400">Status</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200">{{ statusLabel(item.status) }}</span>
                                    </div>
                                    <div v-if="item.keterangan" class="flex items-start gap-2">
                                        <span class="w-28 sm:w-32 shrink-0 text-slate-500 dark:text-slate-400">Deskripsi</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 leading-relaxed text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ item.keterangan }}</span>
                                    </div>
                                    <div v-if="item.pihak_eksternal" class="flex items-start gap-2">
                                        <span class="w-28 sm:w-32 shrink-0 text-slate-500 dark:text-slate-400">Pihak Eksternal</span>
                                        <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                        <span class="min-w-0 flex-1 leading-relaxed text-slate-700 dark:text-slate-200">{{ item.pihak_eksternal }}</span>
                                    </div>
                                </div>

                                <div v-if="item.has_undangan || item.has_materi || item.has_stream || item.has_risalah || item.risalah_status || item.materi_restricted || item.stream_restricted" class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 w-full sm:w-auto">
                                        <?php if ($isMember): ?>
                                            <a v-if="item.has_undangan" class="flex-1 sm:flex-initial min-h-[34px] sm:min-h-[36px] inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 sm:px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-[11px] sm:text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 shadow-xs transition whitespace-nowrap" :href="item.undangan_url" target="_blank" rel="noopener noreferrer">
                                                <svg viewBox="0 0 24 24" class="size-3.5 text-slate-500 dark:text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                                <span>Undangan</span>
                                            </a>
                                        <?php endif; ?>

                                        <a v-if="item.has_materi" class="flex-1 sm:flex-initial min-h-[34px] sm:min-h-[36px] inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 sm:px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-[11px] sm:text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 shadow-xs transition whitespace-nowrap" :href="item.materi_url" target="_blank" rel="noopener noreferrer">
                                            <svg viewBox="0 0 24 24" class="size-3.5 text-slate-500 dark:text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                                            <span>Bahan Rapat</span>
                                        </a>

                                        <a v-if="item.has_stream" class="flex-1 sm:flex-initial min-h-[34px] sm:min-h-[36px] inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 sm:px-3 rounded-lg border border-rose-200 dark:border-rose-800/80 bg-rose-50/70 dark:bg-rose-950/40 text-[11px] sm:text-xs font-semibold text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 shadow-xs transition whitespace-nowrap" :href="item.stream_url" target="_blank" rel="noopener noreferrer">
                                            <svg viewBox="0 0 24 24" class="size-3.5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                                            <span>Live / Video</span>
                                        </a>

                                        <?php if ($isMember): ?>
                                            <button
                                                v-if="item.has_risalah"
                                                type="button"
                                                class="flex-1 sm:flex-initial min-h-[34px] sm:min-h-[36px] inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 sm:px-3.5 rounded-lg border border-blue-300 dark:border-blue-700/80 bg-blue-50/90 dark:bg-blue-950/40 text-[11px] sm:text-xs font-semibold text-blue-800 dark:text-blue-200 hover:bg-blue-100 dark:hover:bg-blue-900/60 shadow-xs transition cursor-pointer whitespace-nowrap"
                                                @click="openRisalahModal(item)"
                                                aria-haspopup="dialog"
                                                aria-expanded="false"
                                                aria-controls="hs-risalah-modal"
                                            >
                                                <svg viewBox="0 0 24 24" class="size-3.5 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                                <span>Risalah (Notulen AI)</span>
                                            </button>
                                            <span v-else-if="item.risalah_status" class="flex-1 sm:flex-initial min-h-[34px] sm:min-h-[36px] inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 sm:px-3.5 rounded-lg text-[11px] sm:text-xs font-semibold bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/30 select-none whitespace-nowrap">
                                                <span class="inline-block size-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                                                <span>Sedang Ditinjau Notulis</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($isMember): ?>
                                        <div v-if="item.materi_restricted || item.stream_restricted" class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-700/80 dark:text-amber-400/80">
                                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            <span v-if="item.materi_restricted && item.stream_restricted">Bahan &amp; siaran khusus peserta</span>
                                            <span v-else-if="item.materi_restricted">Bahan khusus peserta</span>
                                            <span v-else>Siaran khusus peserta</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="border-t border-neutral-800 bg-black text-neutral-300 pt-8 pb-5">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-10">
                <div class="space-y-3 lg:col-span-7 lg:ps-6">
                    <div class="flex items-center gap-3">
                        <img class="h-11 w-11 shrink-0 object-contain sm:h-12 sm:w-12" src="<?= esc($logoUrl) ?><?= str_contains($logoUrl, '?') ? '&' : '?' ?>v=<?= $logoVersion ?>" alt="Logo DPRD Provinsi Sulawesi Tengah" />
                        <div class="min-w-0">
                            <span class="block text-sm font-extrabold uppercase tracking-wider text-white sm:text-base">
                                Dewan Perwakilan Rakyat Daerah
                            </span>
                            <span class="block text-[11px] font-semibold uppercase tracking-wider text-neutral-300 sm:text-xs">
                                PROVINSI SULAWESI TENGAH
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 pt-0.5">
                        <span class="text-xs font-semibold uppercase tracking-wider text-neutral-300">Media Sosial:</span>
                        <div class="flex items-center gap-1.5">
                            <a href="https://www.facebook.com/p/DPRD-Provinsi-Sulawesi-Tengah-100064552912240" target="_blank" rel="noopener noreferrer" class="flex size-8 items-center justify-center rounded-full border border-neutral-700 bg-neutral-900 text-neutral-300 transition hover:border-neutral-400 hover:bg-neutral-800 hover:text-white" aria-label="Facebook DPRD Provinsi Sulawesi Tengah">
                                <svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                            </a>
                            <a href="https://www.instagram.com/dprd_sultengprov" target="_blank" rel="noopener noreferrer" class="flex size-8 items-center justify-center rounded-full border border-neutral-700 bg-neutral-900 text-neutral-300 transition hover:border-neutral-400 hover:bg-neutral-800 hover:text-white" aria-label="Instagram DPRD Provinsi Sulawesi Tengah">
                                <svg class="size-3.5 fill-none stroke-current" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                            </a>
                            <a href="https://www.youtube.com/@dprdprovinsisulawesitengah4027" target="_blank" rel="noopener noreferrer" class="flex size-8 items-center justify-center rounded-full border border-neutral-700 bg-neutral-900 text-neutral-300 transition hover:border-neutral-400 hover:bg-neutral-800 hover:text-white" aria-label="YouTube DPRD Provinsi Sulawesi Tengah">
                                <svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="space-y-2.5 lg:col-span-5 lg:ps-16">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-white">Kontak</h2>
                    <ul class="space-y-1.5 text-xs">
                        <li class="flex items-center gap-2.5">
                            <svg class="size-3.5 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <a href="tel:0451423111" class="transition hover:text-white">(0451) 423111</a>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="size-3.5 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <a href="mailto:sekretariatdprdsulteng@gmail.com" class="transition hover:text-white">sekretariatdprdsulteng@gmail.com</a>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="size-3.5 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <a href="mailto:dprd.sultengprov1@gmail.com" class="transition hover:text-white">dprd.sultengprov1@gmail.com</a>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="mt-0.5 size-3.5 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="2" x2="22" y1="22" y2="22"/><line x1="4" x2="20" y1="2" y2="2"/><path d="M4 2v20"/><path d="M20 2v20"/><path d="M9 22v-4a2 2 0 0 1 4 0v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M16 10h.01"/><path d="M8 14h.01"/><path d="M16 14h.01"/></svg>
                            <address class="not-italic leading-relaxed text-neutral-300">
                                <span class="block">Jl. Dr. Samratulangi No. 80,</span>
                                <span class="block">Kel. Besusu Barat, Kec. Palu Timur</span>
                            </address>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-6 border-t border-neutral-800/80 pt-4">
                <div class="flex flex-col items-center justify-between gap-2 text-center text-xs text-neutral-400 sm:flex-row sm:text-left">
                    <span>&copy; <?= date('Y') ?> Sekretariat DPRD Provinsi Sulawesi Tengah. All rights reserved.</span>
                    <span class="text-neutral-400 font-medium"><?= $isMember ? 'Akses anggota' : 'Akses publik' ?></span>
                </div>
            </div>
        </div>
    </footer>

    <?php if ($isMember): ?>
        <div id="hs-risalah-modal" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="hs-risalah-modal-label">
            <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-3xl lg:max-w-4xl sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center" @click.self="closeRisalahModal">
                <div id="hs-risalah-printable" class="w-full max-h-[90vh] flex flex-col bg-white border border-slate-200/90 shadow-2xl rounded-2xl pointer-events-auto dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex justify-between items-center py-3.5 px-4 sm:px-6 border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 rounded-t-2xl">
                        <div class="min-w-0 pr-4">
                            <h3 id="hs-risalah-modal-label" class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200 truncate">
                                {{ activeRisalahItem ? activeRisalahItem.judul : 'Risalah Rapat DPRD' }}
                            </h3>
                        </div>
                        <button type="button" class="size-8 inline-flex justify-center items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 transition cursor-pointer shrink-0 print:hidden" aria-label="Tutup risalah" @click="closeRisalahModal">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>

                    <div class="p-4 sm:p-6 lg:p-8 overflow-y-auto overscroll-contain space-y-6 text-left">
                        <div v-if="risalahLoading" class="space-y-4 py-8 animate-pulse">
                            <div class="h-4 bg-slate-200 dark:bg-slate-800 rounded w-1/3 mx-auto"></div>
                            <div class="h-6 bg-slate-200 dark:bg-slate-800 rounded w-1/2 mx-auto"></div>
                            <div class="h-4 bg-slate-200 dark:bg-slate-800 rounded w-2/3 mx-auto"></div>
                            <div class="h-px bg-slate-200 dark:bg-slate-800 my-4"></div>
                            <div class="space-y-2 pt-2">
                                <div class="h-3 bg-slate-100 dark:bg-slate-800/60 rounded w-full"></div>
                                <div class="h-3 bg-slate-100 dark:bg-slate-800/60 rounded w-5/6"></div>
                                <div class="h-3 bg-slate-100 dark:bg-slate-800/60 rounded w-4/5"></div>
                                <div class="h-3 bg-slate-100 dark:bg-slate-800/60 rounded w-full"></div>
                            </div>
                        </div>

                        <div v-else-if="risalahError" class="py-8 px-4 text-center">
                            <div class="inline-flex items-center justify-center size-12 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 mb-3 border border-amber-500/20">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ risalahError }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Silakan hubungi tim notulen sekretariat jika membutuhkan salinan naskah fisik.</p>
                        </div>

                        <div v-else-if="risalahData" class="space-y-6">
                            <div class="border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/40 dark:bg-slate-900/60 p-5 sm:p-8 md:p-10 shadow-xs">
                                <div class="text-center border-b-2 border-slate-900 dark:border-slate-200 pb-5 mb-6 space-y-1.5">
                                    <p class="text-[10px] sm:text-xs font-bold uppercase tracking-widest text-slate-600 dark:text-slate-400">
                                        Dewan Perwakilan Rakyat Daerah Provinsi Sulawesi Tengah
                                    </p>
                                    <h2 class="text-base sm:text-lg lg:text-xl font-black uppercase text-slate-900 dark:text-white tracking-wide">
                                        RISALAH RAPAT
                                    </h2>
                                    <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200">
                                        {{ risalahData.judul_rapat || (activeRisalahItem ? activeRisalahItem.judul : 'Rapat DPRD') }}
                                    </p>
                                    <p class="text-[11px] sm:text-xs text-slate-600 dark:text-slate-400 font-medium pt-1">
                                        Hari/Tanggal: <strong>{{ risalahData.tanggal_rapat ? fullDate(risalahData.tanggal_rapat) : (activeRisalahItem ? fullDate(activeRisalahItem.tanggal) : '-') }}</strong>
                                        &nbsp;|&nbsp;
                                        Waktu: <strong>{{ risalahData.waktu_mulai || (activeRisalahItem ? executionTime(activeRisalahItem) : '-') }}</strong>
                                        &nbsp;|&nbsp;
                                        Tempat: <strong>{{ risalahData.ruangan || (activeRisalahItem ? activeRisalahItem.ruangan : 'Gedung DPRD Provinsi Sulawesi Tengah') }}</strong>
                                    </p>
                                </div>

                                <div class="space-y-3 font-sans text-xs sm:text-sm text-slate-800 dark:text-slate-200 leading-relaxed">
                                    <template v-for="(line, idx) in formattedRisalahLines" :key="'ln-' + idx">
                                        <div v-if="line.isEmpty" class="h-2"></div>
                                        <div v-else-if="line.isHeader" class="pt-3 pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <h3 class="text-xs sm:text-sm font-black uppercase text-slate-900 dark:text-white tracking-wide">
                                                {{ line.text }}
                                            </h3>
                                        </div>
                                        <p v-else-if="line.isListItem" class="pl-4 -indent-4 text-left sm:text-justify leading-relaxed">
                                            {{ line.text }}
                                        </p>
                                        <p v-else class="text-left sm:text-justify leading-relaxed">
                                            {{ line.text }}
                                        </p>
                                    </template>
                                </div>

                                <div class="mt-8 pt-4 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                                    <span>Notulen telah diverifikasi notulis sekretariat</span>
                                    <span v-if="risalahData.verified_at">Disahkan: {{ risalahData.verified_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end py-3.5 px-4 sm:px-6 border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 rounded-b-2xl print:hidden">
                        <a
                            v-if="risalahData"
                            :href="risalahPdfUrl(activeRisalahItem)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="py-2 px-3.5 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition"
                        >
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            <span>Cetak PDF</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="<?= base_url('assets/vendor/preline/preline.js?v=' . $prelineVersion) ?>" defer></script>
<script src="<?= base_url('assets/vendor/vue/vue.global.prod.js?v=' . $vueVersion) ?>"></script>
<script {csp-script-nonce}>
    const { createApp, ref, computed, nextTick, onMounted, onUnmounted, watch } = Vue;

    createApp({
        setup() {
            const API_URL = <?= json_encode($apiUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const WEATHER_URL = <?= json_encode(base_url('api/signage/cuaca'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const LOGIN_URL = <?= json_encode(base_url('login?akses=anggota'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const SCHEDULE_RISALAH_BASE = <?= json_encode(base_url('api/v1/jadwal'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const IS_MEMBER = <?= $isMember ? 'true' : 'false' ?>;
            const BANMUS_PROJECTIONS = <?= json_encode(
                $banmusProjections ?? [],
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ) ?>;
            const agendas = ref([]);
            const banmusProjections = ref(BANMUS_PROJECTIONS.map((item) => ({
                ...item,
                key: `banmus_projection:${item.id}`,
            })));
            const activeRisalahItem = ref(null);
            const risalahLoading = ref(false);
            const risalahError = ref('');
            const risalahData = ref(null);
            const activeMobileTab = ref('rapat');
            const units = ref([]);
            const unitScroller = ref(null);
            const canScrollUnitsLeft = ref(false);
            const canScrollUnitsRight = ref(false);
            const unitScrollMaskClass = computed(() => {
                if (canScrollUnitsLeft.value && canScrollUnitsRight.value) {
                    return 'agenda-scroll-mask-both';
                }
                if (canScrollUnitsRight.value) {
                    return 'agenda-scroll-mask-right';
                }
                if (canScrollUnitsLeft.value) {
                    return 'agenda-scroll-mask-left';
                }
                return '';
            });
            const isKomisiOpen = ref(false);
            const komisiButtonRef = ref(null);
            const komisiDropdownRef = ref(null);
            const komisiDropdownStyle = ref({});

            const komisiUnits = computed(() =>
                units.value.filter((u) => /^komisi\s+(i{1,3}|iv|[1-4])$/i.test(u.nama))
            );
            const komisiUnitIds = computed(() =>
                komisiUnits.value.map((u) => Number(u.id))
            );
            const nonKomisiUnits = computed(() =>
                units.value.filter((u) => !/^komisi\s+(i{1,3}|iv|[1-4])$/i.test(u.nama))
            );

            const isKomisiActive = computed(() => {
                if (activeNavigation.value === 'komisi') {
                    return true;
                }
                if (activeNavigation.value.startsWith('unit:')) {
                    const id = Number(activeNavigation.value.slice(5));
                    return komisiUnitIds.value.includes(id);
                }
                return false;
            });

            const komisiButtonLabel = computed(() => {
                if (activeNavigation.value === 'komisi') {
                    return 'Komisi (I–IV)';
                }
                if (activeNavigation.value.startsWith('unit:')) {
                    const id = Number(activeNavigation.value.slice(5));
                    const matched = komisiUnits.value.find((u) => Number(u.id) === id);
                    if (matched) {
                        return matched.nama;
                    }
                }
                return 'Komisi';
            });

            const komisiButtonClass = computed(() => {
                const base = 'inline-flex items-center gap-x-1 sm:gap-x-1.5 py-1 sm:py-1.5 px-2.5 sm:px-3.5 whitespace-nowrap rounded-full text-[11px] sm:text-xs font-bold transition-all duration-150 shrink-0 cursor-pointer';
                return isKomisiActive.value
                    ? `${base} bg-blue-600 text-white shadow-xs ring-2 ring-blue-500/30`
                    : `${base} border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300 hover:bg-slate-100 hover:text-slate-900 dark:hover:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-white`;
            });
            const weather = ref({
                suhu: '--°C',
                kondisi: 'Memuat...',
                kelembapan: '--',
                kec_angin: '--',
                icon_url: '',
                desa: '',
                kecamatan: '',
            });
            const now = ref(new Date());
            const activeNavigation = ref(IS_MEMBER ? 'saya' : 'all');
            const periodMode = ref('month');
            const pageSize = ref(10);
            const generalPageSize = ref(10);
            const currentPage = ref(1);
            const currentGeneralPage = ref(1);
            const expandedAgendaKey = ref(null);
            const expandedGeneralKey = ref(null);
            const initialLoading = ref(true);
            const refreshing = ref(false);
            const loadError = ref(false);
            const isDark = ref(document.documentElement.getAttribute('data-theme') === 'dark');
            let agendaTimer = null;
            let clockTimer = null;
            let weatherTimer = null;
            let requestSequence = 0;

            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

            const headerDay = computed(() => new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Makassar',
                weekday: 'long',
            }).format(now.value).toUpperCase());
            const headerDate = computed(() => new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Makassar',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            }).format(now.value));
            const headerShortDate = computed(() => new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Makassar',
                day: 'numeric',
                month: 'short',
            }).format(now.value));
            const headerTime = computed(() => now.value.toLocaleTimeString('id-ID', {
                timeZone: 'Asia/Makassar',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            }).replaceAll('.', ':'));
            const weatherLabel = computed(() => {
                const temperature = weather.value.suhu || '';
                const condition = weather.value.kondisi || 'Tidak tersedia';
                return temperature ? `${temperature} · ${condition}` : condition;
            });
            const weatherLocation = computed(() => {
                const location = [weather.value.desa, weather.value.kecamatan]
                    .filter((value) => value && value !== '-');
                return location.length ? location.join(', ') : 'Sulawesi Tengah';
            });
            const scheduledAgendas = computed(() => {
                const selectedMonths = new Set(periodMonths());
                let rows = agendas.value.filter((item) =>
                    item.source === 'banmus'
                    && item.status !== 'proyeksi'
                    && selectedMonths.has(String(item.tanggal || '').slice(0, 7)));
                if (activeNavigation.value === 'saya') {
                    rows = rows.filter((item) => item.is_participant);
                } else if (activeNavigation.value === 'komisi') {
                    rows = rows.filter((item) => (item.unit_ids || []).some((id) => komisiUnitIds.value.includes(Number(id))));
                } else if (activeNavigation.value.startsWith('unit:')) {
                    const unitId = Number(activeNavigation.value.slice(5));
                    rows = rows.filter((item) => (item.unit_ids || []).map(Number).includes(unitId));
                }
                return rows;
            });
            const filteredAgendas = computed(() => scheduledAgendas.value);
            const filteredGeneralAgendas = computed(() => {
                const selectedMonths = new Set(periodMonths(periodMode.value));
                let rows = agendas.value.filter((item) =>
                    item.source === 'jadwal_umum'
                    && selectedMonths.has(String(item.tanggal || '').slice(0, 7)));
                if (activeNavigation.value === 'saya') {
                    rows = rows.filter((item) => item.is_participant);
                } else if (activeNavigation.value === 'komisi') {
                    rows = rows.filter((item) => (item.unit_ids || []).some((id) => komisiUnitIds.value.includes(Number(id))));
                } else if (activeNavigation.value.startsWith('unit:')) {
                    const unitId = Number(activeNavigation.value.slice(5));
                    rows = rows.filter((item) => (item.unit_ids || []).map(Number).includes(unitId));
                }

                return rows;
            });
            function orderScheduledRows(rows) {
                const active = rows.filter((item) => item.status === 'berlangsung')
                    .sort((a, b) => (a.waktu_mulai || '').localeCompare(b.waktu_mulai || ''));
                const others = rows.filter((item) => item.status !== 'berlangsung')
                    .sort((a, b) => {
                        const dateCmp = (b.tanggal || '').localeCompare(a.tanggal || '');
                        if (dateCmp !== 0) {
                            return dateCmp;
                        }
                        return (a.waktu_mulai || '').localeCompare(b.waktu_mulai || '');
                    });

                return [...active, ...others];
            }
            function orderGeneralRows(rows) {
                const active = rows.filter((item) => item.status === 'berlangsung')
                    .sort((a, b) => (a.waktu_mulai || '').localeCompare(b.waktu_mulai || ''));
                const others = rows.filter((item) => item.status !== 'berlangsung')
                    .sort((a, b) => {
                        const dateCmp = (b.tanggal || '').localeCompare(a.tanggal || '');
                        if (dateCmp !== 0) {
                            return dateCmp;
                        }
                        return (a.waktu_mulai || '').localeCompare(b.waktu_mulai || '');
                    });

                return [...active, ...others];
            }
            const orderedScheduledAgendas = computed(() => orderScheduledRows(scheduledAgendas.value));
            const orderedAgendas = orderedScheduledAgendas;
            const orderedGeneralAgendas = computed(() => orderGeneralRows(filteredGeneralAgendas.value));
            const myAgendasCount = computed(() => {
                if (!IS_MEMBER) {
                    return 0;
                }
                return agendas.value.filter((item) => item.is_participant).length;
            });
            const todayDateKey = computed(() => {
                try {
                    return new Intl.DateTimeFormat('en-CA', {
                        timeZone: 'Asia/Makassar',
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                    }).format(now.value);
                } catch {
                    return dateKey(now.value);
                }
            });
            const todayAgendas = computed(() => {
                const today = todayDateKey.value;
                const pool = [...scheduledAgendas.value, ...filteredGeneralAgendas.value];
                return pool.filter((item) =>
                    item.status === 'berlangsung'
                    || (item.tanggal === today && item.status !== 'selesai'));
            });
            const activeLiveAgendas = computed(() =>
                todayAgendas.value.filter((item) => item.status === 'berlangsung'));
            const upcomingTodayAgendas = computed(() =>
                todayAgendas.value.filter((item) => item.status !== 'berlangsung'));
            const nearestUpcomingAgenda = computed(() => {
                const today = todayDateKey.value;
                const pool = [...scheduledAgendas.value, ...filteredGeneralAgendas.value];
                const upcoming = pool.filter((item) =>
                    item.tanggal && item.tanggal > today && item.status !== 'selesai');
                return upcoming.length ? upcoming.sort((a, b) => a.tanggal.localeCompare(b.tanggal))[0] : null;
            });
            function focusAgenda(item) {
                if (!item) {
                    return;
                }
                const isGeneral = item.source === 'jadwal_umum';
                if (isGeneral) {
                    activeMobileTab.value = 'umum';
                    expandedGeneralKey.value = item.key;
                } else {
                    activeMobileTab.value = 'rapat';
                    expandedAgendaKey.value = item.key;
                }
                updateUrl();
                nextTick(() => {
                    const card = document.getElementById('agenda-card-' + item.key);
                    if (!card) {
                        return;
                    }
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    card.classList.remove('agenda-flash');
                    void card.offsetWidth;
                    card.classList.add('agenda-flash');
                    window.setTimeout(() => card.classList.remove('agenda-flash'), 1300);
                });
            }
            const totalPages = computed(() => 1);
            const pageStart = computed(() => orderedScheduledAgendas.value.length ? 1 : 0);
            const pageEnd = computed(() => orderedScheduledAgendas.value.length);
            const paginatedAgendas = orderedScheduledAgendas;
            const generalTotalPages = computed(() => 1);
            const generalPageStart = computed(() => orderedGeneralAgendas.value.length ? 1 : 0);
            const generalPageEnd = computed(() => orderedGeneralAgendas.value.length);
            const paginatedGeneralAgendas = orderedGeneralAgendas;

            function dateKey(date) {
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            }

            function monthKey(date) {
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            }

            function periodMonths(mode = periodMode.value) {
                const cursor = calendarCursor.value;
                if (mode === 'month') {
                    return [monthKey(cursor)];
                }

                const firstMonth = mode === 'quarter'
                    ? Math.floor(cursor.getMonth() / 3) * 3
                    : (cursor.getMonth() < 6 ? 0 : 6);
                const count = mode === 'quarter' ? 3 : 6;

                return Array.from({ length: count }, (_, offset) =>
                    monthKey(new Date(cursor.getFullYear(), firstMonth + offset, 1)));
            }

            function projectionRange(item) {
                if (/^\d{4}-\d{2}/.test(item.tanggal || '')) {
                    const month = item.tanggal.slice(0, 7);
                    return [month, month];
                }

                const start = String(item.bulan_mulai || item.tanggal_mulai || '').slice(0, 7);
                const end = String(item.bulan_selesai || item.tanggal_selesai || start).slice(0, 7);
                if (!/^\d{4}-\d{2}$/.test(start) || !/^\d{4}-\d{2}$/.test(end)) {
                    return null;
                }

                return start <= end ? [start, end] : [end, start];
            }

            function projectionOverlapsMonths(item, selectedMonths) {
                const range = projectionRange(item);
                if (range === null || selectedMonths.size === 0) {
                    return false;
                }

                const months = [...selectedMonths].sort();
                return range[0] <= months[months.length - 1] && range[1] >= months[0];
            }

            function requestUrl(month) {
                const url = new URL(API_URL, window.location.origin);
                url.searchParams.set('month', month);
                if (IS_MEMBER) {
                    url.searchParams.set('scope', 'semua');
                }
                return url;
            }

            async function fetchMonth(month) {
                const response = await fetch(requestUrl(month), { credentials: 'same-origin' });
                if (response.status === 401 && IS_MEMBER) {
                    window.location.assign(LOGIN_URL);
                    throw new Error('Sesi anggota berakhir.');
                }
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                const payload = await response.json();
                if (payload.status !== 'success') {
                    throw new Error(payload.message || 'Respons agenda tidak valid.');
                }
                return payload;
            }

            async function loadAgenda() {
                const requestId = ++requestSequence;
                refreshing.value = true;
                try {
                    const requestedMonths = Array.from(new Set([
                        ...periodMonths(),
                        monthKey(calendarCursor.value),
                    ]));
                    const payloads = await Promise.all(requestedMonths.map(fetchMonth));
                    if (requestId !== requestSequence) {
                        return;
                    }
                    const unique = new Map();
                    payloads.flatMap((payload) => payload.data || [])
                        .map((item) => ({
                            ...item,
                            key: `${item.source || 'jadwal_umum'}:${item.source_id ?? item.id}`,
                        }))
                        .forEach((item) => unique.set(item.key, item));
                    agendas.value = Array.from(unique.values()).sort((a, b) =>
                        `${a.tanggal} ${a.waktu_mulai}`.localeCompare(`${b.tanggal} ${b.waktu_mulai}`));
                    units.value = payloads[0]?.units || [];
                    await nextTick();
                    updateUnitScrollState();
                    const validPage = Math.min(currentPage.value, totalPages.value);
                    if (validPage !== currentPage.value) {
                        currentPage.value = validPage;
                        updateUrl();
                    }
                    const validGeneralPage = Math.min(currentGeneralPage.value, generalTotalPages.value);
                    if (validGeneralPage !== currentGeneralPage.value) {
                        currentGeneralPage.value = validGeneralPage;
                        updateUrl();
                    }
                    loadError.value = false;
                } catch (error) {
                    if (requestId === requestSequence) {
                        loadError.value = true;
                        console.error('[Agenda] Gagal mengambil data:', error);
                    }
                } finally {
                    if (requestId === requestSequence) {
                        initialLoading.value = false;
                        refreshing.value = false;
                    }
                }
            }

            async function loadWeather() {
                try {
                    const response = await fetch(WEATHER_URL);
                    const payload = await response.json();
                    weather.value = payload.status === 'success'
                        ? {
                            ...payload.cuaca,
                            desa: payload.lokasi?.desa || '',
                            kecamatan: payload.lokasi?.kecamatan || '',
                        }
                        : {
                            suhu: '--°C',
                            kondisi: 'Tidak tersedia',
                            kelembapan: '--',
                            kec_angin: '--',
                            icon_url: '',
                            desa: '',
                            kecamatan: '',
                        };
                } catch {
                    weather.value = {
                        suhu: '--°C',
                        kondisi: 'Tidak tersedia',
                        kelembapan: '--',
                        kec_angin: '--',
                        icon_url: '',
                        desa: '',
                        kecamatan: '',
                    };
                }
            }

            function toggleKomisiDropdown() {
                if (isKomisiOpen.value) {
                    isKomisiOpen.value = false;
                    return;
                }
                const btn = komisiButtonRef.value;
                if (btn) {
                    const rect = btn.getBoundingClientRect();
                    const left = Math.min(Math.max(8, rect.left), window.innerWidth - 216);
                    komisiDropdownStyle.value = {
                        top: `${Math.round(rect.bottom + 6)}px`,
                        left: `${Math.round(left)}px`,
                    };
                }
                isKomisiOpen.value = true;
            }

            function selectKomisiFilter(value) {
                isKomisiOpen.value = false;
                setNavigation(value);
            }

            function komisiItemClass(value) {
                const active = typeof value === 'number'
                    ? activeNavigation.value === `unit:${value}`
                    : activeNavigation.value === value;
                return active
                    ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-bold'
                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800';
            }

            const isCalendarOpen = ref(false);
            const calendarAnchor = ref(null);
            const calendarRef = ref(null);
            const calendarStyle = ref({});
            const calendarCursor = ref(new Date());
            const calendarLabel = computed(() =>
                `${monthNames[calendarCursor.value.getMonth()]} ${calendarCursor.value.getFullYear()}`);
            const calendarWeeks = computed(() => {
                const cursor = calendarCursor.value;
                const year = cursor.getFullYear();
                const month = cursor.getMonth();
                const counts = {};
                [...scheduledAgendas.value, ...filteredGeneralAgendas.value].forEach((item) => {
                    if (!item.tanggal || item.status === 'proyeksi') {
                        return;
                    }
                    counts[item.tanggal] = (counts[item.tanggal] || 0) + 1;
                });
                const today = todayDateKey.value;
                const cells = [];
                const startOffset = (new Date(year, month, 1).getDay() + 6) % 7;
                for (let i = 0; i < startOffset; i++) {
                    cells.push(null);
                }
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                for (let day = 1; day <= daysInMonth; day++) {
                    const key = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    cells.push({ day, key, count: counts[key] || 0, isToday: key === today });
                }
                while (cells.length % 7 !== 0) {
                    cells.push(null);
                }
                const weeks = [];
                for (let i = 0; i < cells.length; i += 7) {
                    weeks.push(cells.slice(i, i + 7));
                }
                return weeks;
            });

            function toggleCalendar() {
                if (isCalendarOpen.value) {
                    isCalendarOpen.value = false;
                    return;
                }
                const btn = calendarAnchor.value;
                if (btn) {
                    const rect = btn.getBoundingClientRect();
                    const left = Math.min(Math.max(8, rect.right - 288), window.innerWidth - 296);
                    calendarStyle.value = {
                        top: `${Math.round(rect.bottom + 6)}px`,
                        left: `${Math.round(left)}px`,
                    };
                }
                isCalendarOpen.value = true;
            }

            function shiftCalendar(delta) {
                const cursor = calendarCursor.value;
                periodMode.value = 'month';
                calendarCursor.value = new Date(cursor.getFullYear(), cursor.getMonth() + delta, 1);
            }

            function pickCalendarDay(cell) {
                if (!cell || cell.count === 0) {
                    return;
                }
                const pool = [...scheduledAgendas.value, ...filteredGeneralAgendas.value];
                const target = pool.find((item) =>
                    item.tanggal === cell.key && item.status !== 'proyeksi');
                isCalendarOpen.value = false;
                if (target) {
                    focusAgenda(target);
                }
            }

            function calendarCellClass(cell) {
                if (!cell || cell.count === 0) {
                    return 'text-slate-300 dark:text-slate-700 cursor-default';
                }
                return 'text-slate-900 dark:text-white font-bold hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer';
            }

            function handleDocumentClick(event) {
                if (isKomisiOpen.value) {
                    const btn = komisiButtonRef.value;
                    const menu = komisiDropdownRef.value;
                    if (btn && btn.contains(event.target)) return;
                    if (menu && menu.contains(event.target)) return;
                    isKomisiOpen.value = false;
                }
                if (isCalendarOpen.value) {
                    const btn = calendarAnchor.value;
                    const menu = calendarRef.value;
                    if (btn && btn.contains(event.target)) return;
                    if (menu && menu.contains(event.target)) return;
                    isCalendarOpen.value = false;
                }
            }

            function handleWindowScroll() {
                if (isKomisiOpen.value) {
                    isKomisiOpen.value = false;
                }
                if (isCalendarOpen.value) {
                    isCalendarOpen.value = false;
                }
            }

            function handleKeyDown(event) {
                if (event.key === 'Escape') {
                    if (isKomisiOpen.value) {
                        isKomisiOpen.value = false;
                    }
                    if (isCalendarOpen.value) {
                        isCalendarOpen.value = false;
                    }
                    closeRisalahModal();
                }
            }

            let resizeRaf = null;
            function handleResize() {
                if (resizeRaf) {
                    cancelAnimationFrame(resizeRaf);
                }
                resizeRaf = requestAnimationFrame(() => {
                    updateUnitScrollState();
                    resizeRaf = null;
                });
            }

            function startTimers() {
                if (!clockTimer) {
                    now.value = new Date();
                    clockTimer = setInterval(() => {
                        now.value = new Date();
                    }, 1000);
                }
                if (!agendaTimer) {
                    agendaTimer = setInterval(loadAgenda, 60000);
                }
                if (!weatherTimer) {
                    weatherTimer = setInterval(loadWeather, 1800000);
                }
            }

            function stopTimers() {
                if (clockTimer) {
                    clearInterval(clockTimer);
                    clockTimer = null;
                }
                if (agendaTimer) {
                    clearInterval(agendaTimer);
                    agendaTimer = null;
                }
                if (weatherTimer) {
                    clearInterval(weatherTimer);
                    weatherTimer = null;
                }
            }

            function handleVisibilityChange() {
                if (document.visibilityState === 'visible') {
                    startTimers();
                    loadAgenda();
                } else {
                    stopTimers();
                }
            }

            function setNavigation(value) {
                activeNavigation.value = value;
                isKomisiOpen.value = false;
                resetAgendaSelection();
                resetGeneralSelection();
                updateUrl();
            }

            function updateUnitScrollState() {
                if (isKomisiOpen.value) {
                    isKomisiOpen.value = false;
                }
                const scroller = unitScroller.value;
                if (!scroller) {
                    canScrollUnitsLeft.value = false;
                    canScrollUnitsRight.value = false;
                    return;
                }

                const maxScrollLeft = Math.max(0, scroller.scrollWidth - scroller.clientWidth);
                canScrollUnitsLeft.value = scroller.scrollLeft > 2;
                canScrollUnitsRight.value = scroller.scrollLeft < maxScrollLeft - 2;
            }

            function scrollUnitFilters(direction) {
                const scroller = unitScroller.value;
                if (!scroller) {
                    return;
                }

                scroller.scrollBy({
                    left: direction * Math.max(220, scroller.clientWidth * 0.65),
                    behavior: 'smooth',
                });
            }

            let isInitialMount = true;
            watch([calendarCursor, periodMode], () => {
                if (isInitialMount) {
                    return;
                }
                resetAgendaSelection();
                resetGeneralSelection();
                updateUrl();
                loadAgenda();
            });

            const calendarPresets = [
                { key: 'month', label: 'Bulan ini' },
                { key: 'quarter', label: 'Triwulan ini' },
                { key: 'semester', label: 'Semester ini' },
            ];
            const calendarButtonLabel = computed(() => {
                if (periodMode.value === 'quarter') {
                    return 'Triwulan ini';
                }
                if (periodMode.value === 'semester') {
                    return 'Semester ini';
                }
                const cursor = calendarCursor.value;
                return `${shortMonths[cursor.getMonth()]} ${cursor.getFullYear()}`;
            });

            function presetChipClass(key) {
                const base = 'transition cursor-pointer';
                return periodMode.value === key
                    ? `${base} bg-blue-600 text-white shadow-xs`
                    : `${base} border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800`;
            }

            function setCalendarScope(mode) {
                const current = now.value;
                calendarCursor.value = new Date(current.getFullYear(), current.getMonth(), 1);
                periodMode.value = mode;
            }

            function mobileTabClass(tab) {
                if (activeMobileTab.value !== tab) {
                    return 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 font-medium';
                }
                return 'border-blue-600 dark:border-blue-400 text-blue-600 dark:text-blue-400 font-bold';
            }

            function setMobileTab(tab) {
                activeMobileTab.value = tab;
                updateUrl();
            }

            function risalahUrl(item) {
                if (item.risalah_url) {
                    return item.risalah_url;
                }
                const source = item.source === 'banmus' ? 'banmus' : 'umum';
                const id = item.source_id ?? item.id;
                return `${SCHEDULE_RISALAH_BASE}/${source}/${id}/risalah`;
            }

            function risalahPdfUrl(item) {
                if (!item) {
                    return '#';
                }
                if (item.risalah_pdf_url) {
                    return item.risalah_pdf_url;
                }
                if (risalahData.value && risalahData.value.pdf_url) {
                    return risalahData.value.pdf_url;
                }
                const source = item.source === 'banmus' ? 'jadwal-banmus' : 'jadwal-umum';
                const id = item.source_id ?? item.id;
                return `<?= base_url('anggota') ?>/${source}/${id}/risalah-pdf`;
            }

            const formattedRisalahLines = computed(() => {
                const text = risalahData.value?.ringkasan_eksekutif;
                if (!text || typeof text !== 'string') {
                    return [];
                }
                return text.split(/\r\n|\r|\n/).map((line) => {
                    const trimmed = line.trim();
                    const isSectionHeader = /^(?:I|II|III|IV|V|VI|VII|VIII|IX|X)\.\s+\S/i.test(trimmed);
                    const isListItem = /^(?:\d+\.|\-|\*|•)\s+/i.test(trimmed);
                    return {
                        text: trimmed,
                        isHeader: isSectionHeader,
                        isListItem: isListItem,
                        isEmpty: trimmed === '',
                    };
                });
            });

            async function openRisalahModal(item) {
                if (!item) {
                    return;
                }
                activeRisalahItem.value = item;
                risalahLoading.value = true;
                risalahError.value = '';
                risalahData.value = null;

                const modalEl = document.getElementById('hs-risalah-modal');
                if (window.HSOverlay && typeof window.HSOverlay.open === 'function' && modalEl) {
                    window.HSOverlay.open(modalEl);
                } else if (modalEl) {
                    modalEl.classList.remove('hidden');
                    modalEl.classList.add('open', 'opened');
                    document.body.classList.add('hs-overlay-body-open');
                }

                try {
                    const url = risalahUrl(item);
                    const response = await fetch(url, { credentials: 'same-origin' });
                    if (response.status === 401 && IS_MEMBER) {
                        window.location.assign(LOGIN_URL);
                        return;
                    }
                    if (!response.ok) {
                        throw new Error(`Gagal memuat risalah (HTTP ${response.status})`);
                    }
                    const payload = await response.json();
                    if (payload.status !== 'success') {
                        throw new Error(payload.message || 'Respons risalah tidak valid.');
                    }
                    if (!payload.risalah_tersedia) {
                        risalahError.value = payload.message || 'Risalah resmi belum tersedia atau masih ditinjau oleh notulis.';
                        return;
                    }
                    risalahData.value = payload;
                } catch (err) {
                    risalahError.value = err.message || 'Terjadi kesalahan saat memuat risalah rapat.';
                } finally {
                    risalahLoading.value = false;
                }
            }

            function closeRisalahModal() {
                const modalEl = document.getElementById('hs-risalah-modal');
                if (window.HSOverlay && typeof window.HSOverlay.close === 'function' && modalEl) {
                    try {
                        window.HSOverlay.close(modalEl);
                    } catch {
                        // Preline close fallback
                    }
                }
                if (modalEl) {
                    modalEl.classList.add('hidden');
                    modalEl.classList.remove('open', 'opened');
                }
                const backdrop = document.querySelector('[data-hs-overlay-backdrop-template]');
                if (backdrop) {
                    backdrop.remove();
                }
                document.body.classList.remove('hs-overlay-body-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }

            function streamUrl(item) {
                return item ? (item.stream_url || '#') : '#';
            }

            function handleAgendaToggle(event, key) {
                if (event.currentTarget.open) {
                    expandedAgendaKey.value = key;
                } else if (expandedAgendaKey.value === key) {
                    expandedAgendaKey.value = null;
                }
            }

            function handleGeneralToggle(event, key) {
                if (event.currentTarget.open) {
                    expandedGeneralKey.value = key;
                } else if (expandedGeneralKey.value === key) {
                    expandedGeneralKey.value = null;
                }
            }

            function changePageSize() {
                currentPage.value = 1;
                expandedAgendaKey.value = null;
                updateUrl();
            }

            function changeGeneralPageSize() {
                currentGeneralPage.value = 1;
                expandedGeneralKey.value = null;
                updateUrl();
            }

            function goToPage(page) {
                currentPage.value = Math.min(Math.max(1, Number(page)), totalPages.value);
                expandedAgendaKey.value = null;
                updateUrl();
            }

            function goToGeneralPage(page) {
                currentGeneralPage.value = Math.min(
                    Math.max(1, Number(page)),
                    generalTotalPages.value,
                );
                expandedGeneralKey.value = null;
                updateUrl();
            }

            function resetAgendaSelection() {
                currentPage.value = 1;
                expandedAgendaKey.value = null;
            }

            function resetGeneralSelection() {
                currentGeneralPage.value = 1;
                expandedGeneralKey.value = null;
            }

            function updateUrl() {
                const url = new URL(window.location.href);
                const defaultMenu = IS_MEMBER ? 'saya' : 'all';
                setOptionalParam(url, 'menu', activeNavigation.value, defaultMenu);
                url.searchParams.delete('scope');
                if (periodMode.value === 'month') {
                    const currentMonthKey = monthKey(new Date(now.value.getFullYear(), now.value.getMonth(), 1));
                    setOptionalParam(url, 'periode', monthKey(calendarCursor.value), currentMonthKey);
                } else {
                    url.searchParams.set('periode', periodMode.value);
                }
                url.searchParams.delete('tampil');
                url.searchParams.delete('tampil_umum');
                url.searchParams.delete('halaman');
                url.searchParams.delete('halaman_umum');
                setOptionalParam(url, 'tab', activeMobileTab.value, 'rapat');
                url.searchParams.delete('periode_umum');
                window.history.replaceState({}, '', url.toString());
            }

            function setOptionalParam(url, name, value, defaultValue) {
                if (value === defaultValue) {
                    url.searchParams.delete(name);
                } else {
                    url.searchParams.set(name, value);
                }
            }

            function navButtonClass(value) {
                const base = 'inline-flex items-center gap-x-1 sm:gap-x-1.5 py-1 sm:py-1.5 px-2.5 sm:px-3.5 whitespace-nowrap rounded-full text-[11px] sm:text-xs font-bold transition-all duration-150 shrink-0 cursor-pointer';
                return activeNavigation.value === value
                    ? `${base} bg-blue-600 text-white shadow-xs ring-2 ring-blue-500/30`
                    : `${base} border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300 hover:bg-slate-100 hover:text-slate-900 dark:hover:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-white`;
            }

            function compactUnitName(name) {
                return {
                    'Badan Anggaran': 'Banggar',
                    'Badan Musyawarah': 'Banmus',
                    'Badan Kehormatan': 'Kehormatan',
                }[name] || name;
            }

            function statusLabel(status) {
                return {
                    proyeksi: 'Rencana',
                    berlangsung: 'Sedang Berlangsung',
                    persiapan: 'Persiapan',
                    menunggu: 'Akan Datang',
                    selesai: 'Selesai',
                }[status] || status || '-';
            }

            function statusBadgeClass(status) {
                const base = 'items-center gap-1.5 py-0.5 px-2.5 rounded-md text-xs font-semibold';
                return {
                    proyeksi: `${base} bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/30`,
                    berlangsung: `${base} bg-rose-500/15 text-rose-800 dark:text-rose-300 border border-rose-500/30`,
                    persiapan: `${base} bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30`,
                    menunggu: `${base} bg-sky-500/10 text-sky-800 dark:text-sky-300 border border-sky-500/30`,
                    selesai: 'items-center gap-1.5 py-0.5 px-2.5 rounded-md text-xs font-semibold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700',
                }[status] || `${base} bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700`;
            }

            function executionTime(item) {
                if (!item.waktu_mulai) {
                    return 'Sepanjang hari';
                }
                if (!item.waktu_selesai) {
                    return `${item.waktu_mulai} WITA`;
                }

                return `${item.waktu_mulai}–${item.waktu_selesai} WITA`;
            }

            function parseDate(value) {
                if (!value) return new Date();
                const [year, month, day] = String(value).split('-').map(Number);
                return new Date(year, month - 1, day);
            }

            function fullDate(value) {
                if (!value) return '';
                const date = parseDate(value);
                if (isNaN(date.getTime())) return String(value);
                return `${dayNames[date.getDay()]}, ${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;
            }

            function shortMonth(value) {
                return shortMonths[parseDate(value).getMonth()];
            }

            function dayNumber(value) {
                return parseDate(value).getDate();
            }

            function toggleTheme() {
                isDark.value = !isDark.value;
                const theme = isDark.value ? 'dark' : 'light';
                document.documentElement.classList.toggle('dark', isDark.value);
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('dprd-admin-theme', theme);
            }

            onMounted(() => {
                const params = new URLSearchParams(window.location.search);
                const requestedMenu = params.get('menu');
                if (requestedMenu === 'saya' || requestedMenu === 'all' || requestedMenu === 'komisi' || /^unit:\d+$/.test(requestedMenu || '')) {
                    if (requestedMenu !== 'saya' || IS_MEMBER) {
                        activeNavigation.value = requestedMenu;
                    }
                }
                const requestedPeriode = params.get('periode') || '';
                const requestedDate = params.get('tanggal') || params.get('date');
                let cursorDate = null;
                if (requestedDate && /^\d{4}-\d{2}-\d{2}$/.test(requestedDate)) {
                    const [year, month] = requestedDate.split('-').map(Number);
                    periodMode.value = 'month';
                    cursorDate = new Date(year, month - 1, 1);
                } else if (/^\d{4}-\d{2}$/.test(requestedPeriode)) {
                    const [year, month] = requestedPeriode.split('-').map(Number);
                    periodMode.value = 'month';
                    cursorDate = new Date(year, month - 1, 1);
                } else if (['quarter', 'semester'].includes(requestedPeriode)) {
                    periodMode.value = requestedPeriode;
                }
                if (cursorDate === null) {
                    cursorDate = new Date(now.value.getFullYear(), now.value.getMonth(), 1);
                }
                calendarCursor.value = cursorDate;
                if (params.get('tab') === 'umum') {
                    activeMobileTab.value = 'umum';
                }
                isInitialMount = false;
                loadWeather();
                startTimers();
                loadAgenda().then(() => {
                    if (requestedDate && /^\d{4}-\d{2}-\d{2}$/.test(requestedDate)) {
                        const pool = [...scheduledAgendas.value, ...filteredGeneralAgendas.value];
                        const target = pool.find((item) => item.tanggal === requestedDate);
                        if (target) {
                            focusAgenda(target);
                        }
                    }
                });
                window.addEventListener('resize', handleResize);
                document.addEventListener('visibilitychange', handleVisibilityChange);
                document.addEventListener('click', handleDocumentClick);
                document.addEventListener('keydown', handleKeyDown);
                window.addEventListener('scroll', handleWindowScroll, { passive: true });
                nextTick(() => {
                    if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
                        window.HSStaticMethods.autoInit();
                    }
                });
            });

            onUnmounted(() => {
                stopTimers();
                if (resizeRaf) {
                    cancelAnimationFrame(resizeRaf);
                    resizeRaf = null;
                }
                window.removeEventListener('resize', handleResize);
                document.removeEventListener('visibilitychange', handleVisibilityChange);
                document.removeEventListener('click', handleDocumentClick);
                document.removeEventListener('keydown', handleKeyDown);
                window.removeEventListener('scroll', handleWindowScroll);
            });

            return {
                initialLoading,
                refreshing,
                loadError,
                units,
                komisiUnits,
                nonKomisiUnits,
                isKomisiOpen,
                komisiButtonRef,
                komisiDropdownRef,
                komisiDropdownStyle,
                komisiButtonLabel,
                komisiButtonClass,
                komisiItemClass,
                isKomisiActive,
                toggleKomisiDropdown,
                selectKomisiFilter,
                isCalendarOpen,
                calendarAnchor,
                calendarRef,
                calendarStyle,
                calendarLabel,
                calendarWeeks,
                calendarCellClass,
                toggleCalendar,
                shiftCalendar,
                pickCalendarDay,
                calendarPresets,
                calendarButtonLabel,
                presetChipClass,
                setCalendarScope,
                unitScroller,
                canScrollUnitsLeft,
                canScrollUnitsRight,
                weather,
                weatherLabel,
                weatherLocation,
                headerDay,
                headerDate,
                headerShortDate,
                headerTime,
                activeNavigation,
                myAgendasCount,
                periodMode,
                pageSize,
                generalPageSize,
                currentPage,
                currentGeneralPage,
                scheduledAgendas,
                filteredAgendas,
                filteredGeneralAgendas,
                orderedScheduledAgendas,
                orderedAgendas,
                orderedGeneralAgendas,
                todayAgendas,
                activeLiveAgendas,
                upcomingTodayAgendas,
                nearestUpcomingAgenda,
                focusAgenda,
                paginatedAgendas,
                paginatedGeneralAgendas,
                totalPages,
                generalTotalPages,
                pageStart,
                pageEnd,
                generalPageStart,
                generalPageEnd,
                expandedAgendaKey,
                expandedGeneralKey,
                isDark,
                loadAgenda,
                setNavigation,
                updateUnitScrollState,
                unitScrollMaskClass,
                scrollUnitFilters,
                handleAgendaToggle,
                handleGeneralToggle,
                changePageSize,
                changeGeneralPageSize,
                goToPage,
                goToGeneralPage,
                navButtonClass,
                activeMobileTab,
                mobileTabClass,
                setMobileTab,
                risalahUrl,
                risalahPdfUrl,
                formattedRisalahLines,
                streamUrl,
                activeRisalahItem,
                risalahLoading,
                risalahError,
                risalahData,
                openRisalahModal,
                closeRisalahModal,
                compactUnitName,
                statusLabel,
                statusBadgeClass,
                executionTime,
                fullDate,
                shortMonth,
                dayNumber,
                toggleTheme,
            };
        },
    }).mount('#agenda-app');
</script>
</body>
</html>
