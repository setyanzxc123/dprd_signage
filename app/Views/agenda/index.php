<?php
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$vueVersion = is_file(FCPATH . 'assets/vendor/vue/vue.global.prod.js') ? filemtime(FCPATH . 'assets/vendor/vue/vue.global.prod.js') : time();
$cssVersion = is_file(FCPATH . 'assets/css/agenda.css') ? filemtime(FCPATH . 'assets/css/agenda.css') : time();
$logoVersion = is_file(FCPATH . 'assets/images/logo_dprd.png') ? filemtime(FCPATH . 'assets/images/logo_dprd.png') : time();
$isMember = is_array($member ?? null);
$isAdmin = ! $isMember && ! empty($isAdmin);
$pageTitle = $isMember ? 'Agenda Anggota DPRD' : 'Agenda DPRD';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($pageTitle) ?> - DPRD Provinsi Sulawesi Tengah</title>
    <meta name="description" content="Agenda dan jadwal rapat DPRD Provinsi Sulawesi Tengah." />
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/logo_dprd.png?v=' . $logoVersion) ?>" />
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
    <script src="<?= base_url('assets/vendor/vue/vue.global.prod.js?v=' . $vueVersion) ?>"></script>
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased selection:bg-emerald-500 selection:text-white">
<div id="agenda-app" v-cloak>
    <header class="sticky top-0 z-50 border-b border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md shadow-xs">
        <div class="agenda-header-motif" aria-hidden="true"></div>
        <div class="mx-auto flex min-h-16 w-full items-center justify-between gap-3 px-3.5 py-2.5 sm:min-h-20 sm:px-6 xl:px-8">
            <a class="flex items-center gap-3 min-w-0 flex-1" href="<?= esc($portalUrl) ?>" aria-label="Halaman agenda DPRD">
                <img class="h-12 w-12 shrink-0 object-contain sm:h-16 sm:w-16" src="<?= esc($logoUrl) ?><?= str_contains($logoUrl, '?') ? '&' : '?' ?>v=<?= $logoVersion ?>" alt="Logo DPRD Provinsi Sulawesi Tengah" />
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
                        <span class="block text-[9px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mt-0.5">WITA</span>
                    </div>
                </div>

                <button class="inline-flex justify-center items-center size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700/80 transition" type="button" @click="toggleTheme" :aria-label="isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap'">
                    <svg v-if="!isDark" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 15.5A8.5 8.5 0 0 1 8.5 4a7 7 0 1 0 11.5 11.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <svg v-else viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v2m0 14v2M4.2 4.2l1.4 1.4m12.8 12.8 1.4 1.4M3 12h2m14 0h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" stroke-linecap="round"/></svg>
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

        <div class="border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/70 dark:bg-slate-900/50 xl:hidden px-3 py-1.5 text-center">
            <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-200 tabular-nums">{{ weatherLabel }} · {{ headerShortDate }} · {{ headerTime }} WITA</span>
        </div>

        <nav class="border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-900/80 backdrop-blur-md" aria-label="Navigasi agenda">
            <div class="mx-auto flex w-full flex-col gap-2 px-3 py-2 sm:w-[min(1480px,calc(100%-32px))] sm:flex-row sm:items-center sm:justify-between sm:px-0">
                <div class="flex min-w-0 flex-1 items-center gap-1.5 sm:gap-2">
                    <button
                        :class="{ 'invisible pointer-events-none': !canScrollUnitsLeft }"
                        class="inline-flex size-9 shrink-0 items-center justify-center rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                        type="button"
                        :aria-hidden="!canScrollUnitsLeft"
                        :tabindex="canScrollUnitsLeft ? 0 : -1"
                        aria-label="Geser kelompok peserta ke kiri"
                        title="Geser ke kiri"
                        @click="scrollUnitFilters(-1)">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div
                        ref="unitScroller"
                        :class="unitScrollMaskClass"
                        class="agenda-unit-scroll flex w-full min-w-0 items-center gap-2 overflow-x-auto py-1 px-3 sm:px-4 no-scrollbar"
                        @scroll.passive="updateUnitScrollState">
                        <button :class="navButtonClass('all')" type="button" @click="setNavigation('all')">Semua</button>
                        <button
                            v-if="komisiUnits.length > 0"
                            ref="komisiButtonRef"
                            :class="komisiButtonClass"
                            type="button"
                            @click.stop="toggleKomisiDropdown"
                            aria-haspopup="true"
                            :aria-expanded="isKomisiOpen"
                        >
                            <span>{{ komisiButtonLabel }}</span>
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" class="transition-transform duration-200" :class="{ 'rotate-180': isKomisiOpen }">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>
                        <button v-for="unit in nonKomisiUnits" :key="unit.id" :class="navButtonClass('unit:' + unit.id)" :title="unit.nama" type="button" @click="setNavigation('unit:' + unit.id)">{{ compactUnitName(unit.nama) }}</button>
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
                                class="flex w-full items-center justify-between px-3 py-2 text-xs font-bold rounded-xl transition"
                                :class="activeNavigation === 'komisi' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                @click="selectKomisiFilter('komisi')"
                            >
                                <span>Semua Komisi (I–IV)</span>
                                <svg v-if="activeNavigation === 'komisi'" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="text-emerald-600 dark:text-emerald-400"><polyline points="20 6 9 17 4 12"/></svg>
                            </button>
                            <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                            <button
                                v-for="k in komisiUnits"
                                :key="k.id"
                                type="button"
                                class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition"
                                :class="activeNavigation === 'unit:' + k.id ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-bold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                @click="selectKomisiFilter('unit:' + k.id)"
                            >
                                <span>{{ k.nama }}</span>
                                <svg v-if="activeNavigation === 'unit:' + k.id" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="text-emerald-600 dark:text-emerald-400"><polyline points="20 6 9 17 4 12"/></svg>
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
                                <button type="button" class="inline-flex size-8 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition" aria-label="Bulan sebelumnya" @click="shiftCalendar(-1)">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <span class="text-sm font-extrabold text-slate-900 dark:text-white">{{ calendarLabel }}</span>
                                <button type="button" class="inline-flex size-8 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition" aria-label="Bulan berikutnya" @click="shiftCalendar(1)">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>

                            <div class="mt-3 flex items-center gap-1.5">
                                <button
                                    v-for="preset in calendarPresets"
                                    :key="preset.key"
                                    type="button"
                                    class="flex-1 py-1.5 px-1.5 rounded-lg text-[11px] font-bold whitespace-nowrap transition"
                                    :class="presetChipClass(preset.key)"
                                    @click="setCalendarScope(preset.key)"
                                >
                                    {{ preset.label }}
                                </button>
                            </div>

                            <div class="mt-2.5 grid grid-cols-7 gap-1 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
                            </div>
                            <div class="mt-1 grid grid-cols-7 gap-1">
                                <template v-for="(week, wi) in calendarWeeks" :key="'cal-week-' + wi">
                                    <button
                                        v-for="(cell, ci) in week"
                                        :key="(cell ? cell.key : 'cal-blank-' + wi + '-' + ci)"
                                        type="button"
                                        :disabled="!cell || cell.count === 0"
                                        class="relative flex h-9 flex-col items-center justify-center rounded-lg text-xs font-semibold transition disabled:cursor-default"
                                        :class="cell && cell.count > 0 ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 font-bold hover:bg-emerald-500/20' : 'text-slate-500 dark:text-slate-400'"
                                        @click="pickCalendarDay(cell)"
                                    >
                                        <span>{{ cell ? cell.day : '' }}</span>
                                        <span v-if="cell && cell.count > 0" class="absolute bottom-1 flex items-center gap-0.5">
                                            <span v-for="n in Math.min(cell.count, 3)" :key="n" class="size-1 rounded-full bg-emerald-500"></span>
                                        </span>
                                        <span v-if="cell && cell.isToday" class="absolute inset-0 rounded-lg ring-1 ring-emerald-500/60 pointer-events-none"></span>
                                    </button>
                                </template>
                            </div>

                            <p class="mt-3 text-[11px] font-medium text-slate-500 dark:text-slate-400">Klik tanggal bertanda untuk membuka kartunya.</p>
                        </div>
                    </teleport>

                    <button
                        :class="{ 'invisible pointer-events-none': !canScrollUnitsRight }"
                        class="inline-flex size-9 shrink-0 items-center justify-center rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                        type="button"
                        :aria-hidden="!canScrollUnitsRight"
                        :tabindex="canScrollUnitsRight ? 0 : -1"
                        aria-label="Geser kelompok peserta ke kanan"
                        title="Geser ke kanan"
                        @click="scrollUnitFilters(1)">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>
        </nav>

        <?php if ($isMember): ?>
            <div class="border-t border-slate-200/80 dark:border-slate-800/80 bg-white/90 dark:bg-slate-900/90" aria-label="Cakupan agenda anggota">
                <div class="mx-auto flex w-full flex-col gap-2 px-3.5 py-2.5 sm:w-[min(1480px,calc(100%-32px))] sm:flex-row sm:items-center sm:justify-between sm:px-0">
                    <div class="inline-flex p-1 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 gap-1" role="group" aria-label="Pilih cakupan agenda">
                        <button type="button" class="py-1.5 px-3 rounded-lg text-xs font-semibold transition-all" :class="scopeButtonClass('saya')" :aria-pressed="memberScope === 'saya'" @click="setMemberScope('saya')">Jadwal Saya</button>
                        <button type="button" class="py-1.5 px-3 rounded-lg text-xs font-semibold transition-all" :class="scopeButtonClass('semua')" :aria-pressed="memberScope === 'semua'" @click="setMemberScope('semua')">Semua Jadwal</button>
                    </div>
                    <p class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                        <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-[10px] font-bold bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/20">Akses Anggota</span>
                        Anda dapat melihat agenda dan sumber daya internal sesuai kewenangan.
                    </p>
                </div>
            </div>
        <?php endif; ?>
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
                    class="inline-flex items-center justify-center gap-x-1.5 py-2 px-2.5 sm:px-3.5 rounded-xl border border-emerald-500/40 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:border-emerald-700/60 dark:text-emerald-300 dark:hover:bg-emerald-900/60 text-xs font-bold shadow-xs transition shrink-0"
                    type="button"
                    ref="calendarAnchor"
                    aria-haspopup="dialog"
                    :aria-expanded="isCalendarOpen"
                    aria-label="Pilih periode agenda lewat kalender"
                    @click="toggleCalendar"
                >
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span class="hidden sm:inline">{{ calendarButtonLabel }}</span>
                </button>

                <button
                    class="inline-flex items-center justify-center gap-x-1.5 py-2 px-3.5 rounded-xl border border-emerald-500/40 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-700/60 dark:text-emerald-300 dark:hover:bg-emerald-900/60 text-xs font-bold shadow-xs transition shrink-0 active:scale-[0.98]"
                    type="button"
                    @click="loadAgenda"
                    :disabled="refreshing"
                    title="Perbarui data agenda"
                >
                    <span v-if="refreshing" class="inline-block size-3.5 animate-spin rounded-full border-2 border-emerald-600 border-t-transparent dark:border-emerald-400"></span>
                    <svg v-else viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="text-emerald-600 dark:text-emerald-400"><path d="M20 12a8 8 0 1 1-2.34-5.66M20 4v6h-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="hidden sm:inline">Perbarui</span>
                </button>
            </div>
        </div>

        <div v-if="todayAgendas.length > 0" class="rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-xs p-2 mt-2">
            <ol class="space-y-1.5">
                <li v-for="item in [...activeLiveAgendas, ...upcomingTodayAgendas]" :key="'today-card-' + item.key" class="rounded-lg border border-slate-200 dark:border-slate-700/70 bg-slate-50/70 dark:bg-slate-800/40 shadow-xs px-3 py-1.5 flex items-center justify-between gap-3" :class="item.status === 'berlangsung' ? 'bg-white dark:bg-slate-900 shadow-md shadow-slate-900/10 dark:shadow-black/30 relative z-[1]' : ''">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 tabular-nums whitespace-nowrap">{{ executionTime(item) }}</span>
                        <span v-if="item.status === 'berlangsung'" class="relative flex h-2 w-2 items-center justify-center shrink-0">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                        </span>
                        <span class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ item.judul }}</span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span v-if="item.status === 'berlangsung'" class="hidden sm:inline-flex items-center py-0.5 px-2 rounded-full text-[11px] font-bold bg-rose-500/15 text-rose-800 dark:text-rose-300 border border-rose-500/30">Berlangsung</span>
                        <a v-if="item.has_stream" :href="streamUrl(item)" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                            <span>Tonton Live</span>
                        </a>
                        <button type="button" @click="focusAgenda(item)" class="inline-flex items-center gap-1 py-1 px-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-xs transition">
                            <span>Buka</span>
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>
                </li>
            </ol>
        </div>
        <p v-if="todayAgendas.length === 0" class="py-3 text-xs font-medium text-slate-600 dark:text-slate-300 border-b border-slate-200/80 dark:border-slate-800/80">
            Tidak ada jadwal sidang atau kegiatan dewan untuk hari ini.<span v-if="nearestUpcomingAgenda"> Agenda berikutnya: <strong class="text-slate-800 dark:text-slate-200 font-semibold">{{ fullDate(nearestUpcomingAgenda.tanggal) }}</strong> ({{ nearestUpcomingAgenda.judul }})</span>
        </p>

        <div class="xl:hidden mt-3 flex p-1 rounded-xl bg-slate-200/80 dark:bg-slate-800 border border-slate-300/60 dark:border-slate-700/60 gap-1" role="tablist" aria-label="Pilih tampilan sisi agenda">
            <button
                type="button"
                role="tab"
                :aria-selected="activeMobileTab === 'rapat'"
                @click="setMobileTab('rapat')"
                :class="mobileTabClass('rapat')"
                class="flex-1 py-2.5 px-3 rounded-lg text-xs font-bold text-center transition-all flex items-center justify-center gap-1.5"
            >
                <span>Rapat &amp; Sidang</span>
                <span class="py-0.5 px-1.5 rounded-full text-[10px] font-extrabold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">{{ filteredAgendas.length }}</span>
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="activeMobileTab === 'umum'"
                @click="setMobileTab('umum')"
                :class="mobileTabClass('umum')"
                class="flex-1 py-2.5 px-3 rounded-lg text-xs font-bold text-center transition-all flex items-center justify-center gap-1.5"
            >
                <span>Kegiatan &amp; Audiensi</span>
                <span class="py-0.5 px-1.5 rounded-full text-[10px] font-extrabold bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30">{{ filteredGeneralAgendas.length }}</span>
            </button>
        </div>
    </div>

    <main class="mx-auto w-full min-w-0 px-3 py-4 sm:px-6 sm:py-6 xl:w-[min(1480px,calc(100%-32px))] xl:px-0 xl:grid xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] xl:gap-6 xl:items-start">
        <section
            class="rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-xs overflow-hidden"
            :class="{ 'hidden xl:block': activeMobileTab !== 'rapat' }"
        >
            <div class="p-0">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 px-4 py-3.5 sm:px-6 sm:py-4">
                    <div class="min-w-0">
                        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white truncate underline decoration-slate-400 decoration-2 underline-offset-[6px] dark:decoration-slate-500">Agenda Rapat &amp; Sidang</h2>
                        <p class="text-[11px] font-medium text-slate-600 dark:text-slate-400 mt-1 truncate">Paripurna, Komisi, dan Banmus</p>
                    </div>
                    <span class="hidden xl:inline-flex items-center py-0.5 px-2.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shrink-0">
                        {{ filteredAgendas.length }} agenda
                    </span>
                </div>

                <div v-if="initialLoading" class="grid gap-3 p-4 sm:p-6">
                    <div v-for="item in 3" :key="'rapat-skeleton-' + item" class="animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-800/50 h-24 w-full"></div>
                </div>

                <div v-else-if="loadError" class="p-4 sm:p-6">
                    <div role="alert" class="flex items-center gap-3 p-4 rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 text-sm">
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
                        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Tidak ada jadwal atau rencana rapat untuk kelompok peserta dan periode yang dipilih.</p>
                    </div>
                </div>

                <div v-else class="min-w-0 p-3 sm:p-6">
                    <div class="grid gap-2.5">
                        <template v-for="row in agendaShelf" :key="row.key">
                            <div v-if="row.kind === 'scheduled-header'" class="flex items-baseline px-1 pt-1">
                                <h3 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400" title="Jadwal dengan tanggal dan ruangan yang sudah ditetapkan">Jadwal Pasti</h3>
                            </div>
                            <p v-else-if="row.kind === 'scheduled-empty'" class="flex items-center gap-2.5 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 px-3.5 py-3 text-xs font-medium text-slate-500 dark:text-slate-400">
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span>Belum ada jadwal rapat bertanggal untuk periode ini.</span>
                            </p>
                            <div v-else-if="row.kind === 'plan-header'" class="px-1 pt-1">
                                <h3 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400" title="Rencana resmi hasil SK Badan Musyawarah; tanggal dan ruangan menyusul ditetapkan">Rencana SK Banmus</h3>
                            </div>
                            <a v-else-if="row.kind === 'plan-more'" class="justify-self-start inline-flex items-center gap-1.5 py-2 px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-emerald-500/40 hover:bg-emerald-50/50 hover:text-emerald-700 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-300 shadow-xs transition" href="<?= base_url('agenda/jadwal-banmus') ?>">
                                <span>Lihat semua rencana ({{ row.count }})</span>
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </a>
                            <template v-else>
                            <details
                                v-for="item in [row.item]"
                                :key="item.key"
                                :id="'agenda-card-' + item.key"
                                name="agenda-banmus-accordion"
                                class="group agenda-collapse rounded-2xl border border-slate-200 dark:border-slate-700/70 bg-slate-50/70 dark:bg-slate-800/40 shadow-xs hover:border-slate-300 dark:hover:border-slate-600"
                                :class="{
                                    'bg-white dark:bg-slate-900 shadow-lg shadow-slate-900/10 dark:shadow-black/30 relative z-[1]': item.status === 'berlangsung' && expandedAgendaKey !== item.key,
                                    'border-emerald-600 outline outline-2 outline-emerald-600 bg-white dark:border-emerald-400 dark:outline-emerald-400 dark:bg-slate-900 shadow-xl shadow-slate-900/10 dark:shadow-black/40 relative z-10': expandedAgendaKey === item.key
                                }"
                                :open="expandedAgendaKey === item.key"
                                @toggle="handleAgendaToggle($event, item.key)"
                            >
                            <summary class="grid min-h-0 grid-cols-[2.75rem_minmax(0,1fr)] items-center gap-3 overflow-hidden py-3.5 px-3.5 pr-10 sm:grid-cols-[3.25rem_minmax(0,1fr)_auto] sm:gap-3.5 sm:px-4 sm:pr-12 cursor-pointer select-none">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 text-center sm:h-12 sm:w-12">
                                    <span v-if="item.tanggal">
                                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-tight">{{ shortMonth(item.tanggal) }}</span>
                                        <strong class="block text-lg font-black text-slate-900 dark:text-white leading-none mt-0.5">{{ dayNumber(item.tanggal) }}</strong>
                                    </span>
                                    <span v-else>
                                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-tight">SK</span>
                                        <strong class="block text-sm font-black text-slate-900 dark:text-white leading-none mt-0.5">{{ item.document_year }}</strong>
                                    </span>
                                </span>

                                <span class="min-w-0">
                                    <span class="line-clamp-2 text-sm font-bold leading-snug text-slate-900 dark:text-white sm:text-base [text-wrap:pretty]">{{ item.judul }}</span>
                                    <span class="mt-1 flex min-w-0 flex-wrap items-center gap-1.5">
                                        <span v-if="item.status !== 'proyeksi' && (item.source === 'banmus' || item.source === 'banmus_projection')" class="inline-flex items-center py-0.5 px-2 rounded-full text-[11px] font-semibold bg-sky-500/10 text-sky-700 dark:text-sky-300 border border-sky-500/20">
                                            Banmus
                                        </span>
                                        <?php if ($isMember): ?>
                                            <span v-if="item.is_participant" class="inline-flex items-center py-0.5 px-2 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">Anda Peserta</span>
                                        <?php endif; ?>
                                        <span v-if="item.status !== 'proyeksi' && !item.is_public" class="inline-flex items-center py-0.5 px-2 rounded-full text-[11px] font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">Khusus Peserta</span>
                                        <span v-if="item.status === 'proyeksi'" class="truncate text-xs font-medium text-slate-600 dark:text-slate-400">
                                            {{ item.periode_label || 'Periode belum ditentukan' }}
                                        </span>
                                        <span v-else class="truncate text-xs font-medium text-slate-600 dark:text-slate-400">{{ executionTime(item) }} · {{ item.ruangan || '-' }}</span>
                                        <span v-if="item.status !== 'proyeksi'" :class="statusBadgeClass(item.status)" class="inline-flex shrink-0 sm:hidden">
                                            <span v-if="item.status === 'berlangsung'" class="relative flex h-2 w-2 items-center justify-center">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            </span>
                                            {{ statusLabel(item.status) }}
                                        </span>
                                    </span>
                                </span>

                                <span v-if="item.status !== 'proyeksi'" :class="statusBadgeClass(item.status)" class="hidden sm:inline-flex items-center gap-1.5">
                                    <span v-if="item.status === 'berlangsung'" class="relative flex h-2 w-2 items-center justify-center">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    </span>
                                    {{ statusLabel(item.status) }}
                                </span>
                            </summary>

                            <div class="min-w-0 rounded-b-2xl border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/20 px-4 pt-3 pb-4 sm:px-5 sm:pt-3.5 sm:pb-5 space-y-2.5">
                                <div v-if="item.keterangan">
                                    <p class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Pokok bahasan</p>
                                    <p class="mt-0.5 text-xs sm:text-sm leading-relaxed text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ item.keterangan }}</p>
                                </div>

                                <p v-if="item.status === 'proyeksi' && item.document_number" class="text-xs text-slate-600 dark:text-slate-300">
                                    <span class="font-semibold text-slate-500 dark:text-slate-400">Nomor SK:</span> {{ item.document_number }}
                                </p>

                                <p v-else-if="item.status !== 'proyeksi'" class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
                                    <template v-if="item.komisi"><span class="font-semibold text-slate-500 dark:text-slate-400">Pelaksana:</span> {{ item.komisi }}</template><span v-if="item.pihak_eksternal"><span v-if="item.komisi"> · </span><span class="font-semibold text-slate-500 dark:text-slate-400">Pihak eksternal:</span> {{ item.pihak_eksternal }}</span><?php if ($isMember): ?><span v-if="!item.is_public"> · Khusus peserta &amp; undangan</span><?php endif; ?>
                                </p>

                                <div v-if="item.status === 'proyeksi' || item.has_undangan || item.has_materi || item.has_stream || item.has_risalah || item.risalah_status || item.materi_restricted || item.stream_restricted" class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a v-if="item.status === 'proyeksi'" class="min-h-[42px] sm:min-h-[38px] inline-flex items-center gap-2 py-2 px-3 sm:py-1.5 sm:px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 shadow-xs transition" :href="item.projection_url">
                                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="text-slate-500 dark:text-slate-400"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            <span>Lihat Proyeksi &amp; SK</span>
                                        </a>

                                        <template v-else>
                                            <?php if ($isMember): ?>
                                                <a v-if="item.has_undangan" class="min-h-[42px] sm:min-h-[38px] inline-flex items-center gap-2 py-2 px-3 sm:py-1.5 sm:px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 shadow-xs transition" :href="item.undangan_url" target="_blank" rel="noopener noreferrer">
                                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="text-slate-500 dark:text-slate-400"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                                    <span>Undangan</span>
                                                </a>
                                            <?php endif; ?>

                                            <a v-if="item.has_materi" class="min-h-[42px] sm:min-h-[38px] inline-flex items-center gap-2 py-2 px-3 sm:py-1.5 sm:px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 shadow-xs transition" :href="item.materi_url" target="_blank" rel="noopener noreferrer">
                                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="text-slate-500 dark:text-slate-400"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                                                <span>Bahan Rapat</span>
                                            </a>

                                            <a v-if="item.has_stream" class="min-h-[42px] sm:min-h-[38px] inline-flex items-center gap-2 py-2 px-3 sm:py-1.5 sm:px-3.5 rounded-xl border border-rose-200 dark:border-rose-800/80 bg-rose-50/70 dark:bg-rose-950/40 text-xs font-bold text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 shadow-xs transition" :href="item.stream_url" target="_blank" rel="noopener noreferrer">
                                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="text-rose-600 dark:text-rose-400"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                                                <span>Live / Video</span>
                                            </a>

                                            <?php if ($isMember): ?>
                                                <a v-if="item.has_risalah" class="min-h-[42px] sm:min-h-[38px] inline-flex items-center gap-2 py-2 px-3 sm:py-1.5 sm:px-3.5 rounded-xl border border-emerald-300 dark:border-emerald-700/80 bg-emerald-50/90 dark:bg-emerald-950/40 text-xs font-bold text-emerald-800 dark:text-emerald-200 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 shadow-xs transition" :href="risalahUrl(item)" target="_blank" rel="noopener noreferrer">
                                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="text-emerald-600 dark:text-emerald-400"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                                    <span>Risalah (Notulen AI)</span>
                                                </a>
                                                <span v-else-if="item.risalah_status" class="min-h-[42px] sm:min-h-[38px] inline-flex items-center gap-2 py-2 px-3 sm:py-1.5 sm:px-3.5 rounded-xl text-xs font-semibold bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/30">
                                                    <span class="inline-block size-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                    <span>Notulen AI dalam proses</span>
                                                </span>
                                            <?php endif; ?>
                                        </template>
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

                    <div v-if="totalPages > 1" class="mt-4 flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Menampilkan {{ pageStart }}–{{ pageEnd }} dari {{ orderedAgendas.length }}</span>
                            <label class="hidden sm:inline-flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                                <span class="sr-only">Jumlah agenda per halaman</span>
                                <select class="py-1 px-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/20 shadow-xs" v-model.number="pageSize" @change="changePageSize" aria-label="Jumlah agenda per halaman">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                </select>
                                <span>/ hal</span>
                            </label>
                        </div>
                        <div class="inline-flex items-center gap-x-1">
                            <button class="inline-flex items-center gap-x-1 min-h-[38px] sm:min-h-0 py-2 sm:py-1.5 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:border-emerald-500/40 hover:bg-emerald-50/50 hover:text-emerald-700 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-300 disabled:opacity-40 disabled:pointer-events-none shadow-xs transition" type="button" @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1">Sebelumnya</button>
                            <span class="inline-flex items-center justify-center size-8 rounded-lg bg-emerald-600 text-xs font-black text-white shadow-xs">{{ currentPage }}</span>
                            <button class="inline-flex items-center gap-x-1 min-h-[38px] sm:min-h-0 py-2 sm:py-1.5 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:border-emerald-500/40 hover:bg-emerald-50/50 hover:text-emerald-700 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-300 disabled:opacity-40 disabled:pointer-events-none shadow-xs transition" type="button" @click="goToPage(currentPage + 1)" :disabled="currentPage >= totalPages">Berikutnya</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-xs overflow-hidden mt-4 xl:mt-0"
            :class="{ 'hidden xl:block': activeMobileTab !== 'umum' }"
        >
            <div class="p-0">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 px-4 py-3.5 sm:px-6 sm:py-4">
                    <div class="min-w-0">
                        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white truncate underline decoration-slate-400 decoration-2 underline-offset-[6px] dark:decoration-slate-500">Kegiatan &amp; Audiensi Publik</h2>
                        <p class="text-[11px] font-medium text-slate-600 dark:text-slate-400 mt-1 truncate">Audiensi publik &amp; kunjungan kerja</p>
                    </div>
                    <span class="hidden xl:inline-flex items-center py-0.5 px-2.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shrink-0">
                        {{ filteredGeneralAgendas.length }} agenda
                    </span>
                </div>

                <div v-if="initialLoading" class="grid gap-3 p-4 sm:p-6">
                    <div v-for="item in 3" :key="'general-skeleton-' + item" class="animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-800/50 h-24 w-full"></div>
                </div>

                <div v-else-if="loadError" class="p-4 sm:p-6">
                    <div role="alert" class="flex items-center gap-3 p-4 rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 text-sm">
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
                        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Tidak ada kegiatan untuk kelompok peserta dan periode yang dipilih.</p>
                    </div>
                </div>

                <div v-else class="min-w-0 p-3 sm:p-6">
                    <div class="grid gap-2.5">
                        <details
                            v-for="item in paginatedGeneralAgendas"
                            :key="item.key"
                            :id="'agenda-card-' + item.key"
                            name="agenda-general-accordion"
                            class="group agenda-collapse rounded-2xl border border-slate-200 dark:border-slate-700/70 bg-slate-50/70 dark:bg-slate-800/40 shadow-xs hover:border-slate-300 dark:hover:border-slate-600"
                            :class="{
                                'bg-white dark:bg-slate-900 shadow-lg shadow-slate-900/10 dark:shadow-black/30 relative z-[1]': item.status === 'berlangsung' && expandedGeneralKey !== item.key,
                                'border-emerald-600 outline outline-2 outline-emerald-600 bg-white dark:border-emerald-400 dark:outline-emerald-400 dark:bg-slate-900 shadow-xl shadow-slate-900/10 dark:shadow-black/40 relative z-10': expandedGeneralKey === item.key
                            }"
                            :open="expandedGeneralKey === item.key"
                            @toggle="handleGeneralToggle($event, item.key)"
                        >
                            <summary class="grid min-h-0 grid-cols-[2.75rem_minmax(0,1fr)] items-center gap-3 overflow-hidden py-3.5 px-3.5 pr-10 sm:grid-cols-[3.25rem_minmax(0,1fr)_auto] sm:gap-3.5 sm:px-4 sm:pr-12 cursor-pointer select-none">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 text-center sm:h-12 sm:w-12">
                                    <span>
                                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-tight">{{ shortMonth(item.tanggal) }}</span>
                                        <strong class="block text-lg font-black text-slate-900 dark:text-white leading-none mt-0.5">{{ dayNumber(item.tanggal) }}</strong>
                                    </span>
                                </span>

                                <span class="min-w-0">
                                    <span class="line-clamp-2 text-sm font-bold leading-snug text-slate-900 dark:text-white sm:text-base [text-wrap:pretty]">{{ item.judul }}</span>
                                    <span class="mt-1 flex min-w-0 flex-wrap items-center gap-1.5">
                                        <?php if ($isMember): ?>
                                            <span v-if="item.is_participant" class="inline-flex items-center py-0.5 px-2 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">Anda Peserta</span>
                                        <?php endif; ?>
                                        <span class="truncate text-xs font-medium text-slate-600 dark:text-slate-400">{{ executionTime(item) }} · {{ item.ruangan || '-' }}</span>
                                        <span v-if="item.status !== 'proyeksi'" :class="statusBadgeClass(item.status)" class="inline-flex shrink-0 sm:hidden">
                                            <span v-if="item.status === 'berlangsung'" class="relative flex h-2 w-2 items-center justify-center">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            </span>
                                            {{ statusLabel(item.status) }}
                                        </span>
                                    </span>
                                </span>

                                <span v-if="item.status !== 'proyeksi'" :class="statusBadgeClass(item.status)" class="hidden sm:inline-flex items-center gap-1.5">
                                    <span v-if="item.status === 'berlangsung'" class="relative flex h-2 w-2 items-center justify-center">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    </span>
                                    {{ statusLabel(item.status) }}
                                </span>
                            </summary>

                            <div class="min-w-0 rounded-b-2xl border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/20 px-4 pt-3 pb-4 sm:px-5 sm:pt-3.5 sm:pb-5 space-y-2.5">
                                <div v-if="item.keterangan">
                                    <p class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Deskripsi</p>
                                    <p class="mt-0.5 text-xs sm:text-sm leading-relaxed text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ item.keterangan }}</p>
                                </div>

                                <p v-if="item.komisi || item.pihak_eksternal" class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
                                    <template v-if="item.komisi"><span class="font-semibold text-slate-500 dark:text-slate-400">Pelaksana:</span> {{ item.komisi }}</template><span v-if="item.pihak_eksternal"><span v-if="item.komisi"> · </span><span class="font-semibold text-slate-500 dark:text-slate-400">Pihak eksternal:</span> {{ item.pihak_eksternal }}</span><?php if ($isMember): ?><span v-if="!item.is_public"> · Khusus peserta &amp; tamu terundang</span><?php endif; ?>
                                </p>

                                <div v-if="item.has_undangan || item.has_materi || item.has_stream || item.has_risalah || item.risalah_status || item.materi_restricted || item.stream_restricted" class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <?php if ($isMember): ?>
                                            <a v-if="item.has_undangan" class="min-h-[42px] sm:min-h-[38px] inline-flex items-center gap-2 py-2 px-3 sm:py-1.5 sm:px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 shadow-xs transition" :href="item.undangan_url" target="_blank" rel="noopener noreferrer">
                                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="text-slate-500 dark:text-slate-400"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                                <span>Undangan</span>
                                            </a>
                                        <?php endif; ?>

                                        <a v-if="item.has_materi" class="min-h-[42px] sm:min-h-[38px] inline-flex items-center gap-2 py-2 px-3 sm:py-1.5 sm:px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 shadow-xs transition" :href="item.materi_url" target="_blank" rel="noopener noreferrer">
                                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="text-slate-500 dark:text-slate-400"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                                            <span>Bahan Rapat</span>
                                        </a>

                                        <a v-if="item.has_stream" class="min-h-[42px] sm:min-h-[38px] inline-flex items-center gap-2 py-2 px-3 sm:py-1.5 sm:px-3.5 rounded-xl border border-rose-200 dark:border-rose-800/80 bg-rose-50/70 dark:bg-rose-950/40 text-xs font-bold text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 shadow-xs transition" :href="item.stream_url" target="_blank" rel="noopener noreferrer">
                                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="text-rose-600 dark:text-rose-400"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                                            <span>Live / Video</span>
                                        </a>

                                        <?php if ($isMember): ?>
                                            <a v-if="item.has_risalah" class="min-h-[42px] sm:min-h-[38px] inline-flex items-center gap-2 py-2 px-3 sm:py-1.5 sm:px-3.5 rounded-xl border border-emerald-300 dark:border-emerald-700/80 bg-emerald-50/90 dark:bg-emerald-950/40 text-xs font-bold text-emerald-800 dark:text-emerald-200 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 shadow-xs transition" :href="risalahUrl(item)" target="_blank" rel="noopener noreferrer">
                                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="text-emerald-600 dark:text-emerald-400"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                                <span>Risalah (Notulen AI)</span>
                                            </a>
                                            <span v-else-if="item.risalah_status" class="min-h-[42px] sm:min-h-[38px] inline-flex items-center gap-2 py-2 px-3 sm:py-1.5 sm:px-3.5 rounded-xl text-xs font-semibold bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/30">
                                                <span class="inline-block size-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                <span>Notulen AI dalam proses</span>
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

                    <div v-if="generalTotalPages > 1" class="mt-4 flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Menampilkan {{ generalPageStart }}–{{ generalPageEnd }} dari {{ orderedGeneralAgendas.length }}</span>
                            <label class="hidden sm:inline-flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                                <span class="sr-only">Jumlah kegiatan per halaman</span>
                                <select class="py-1 px-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/20 shadow-xs" v-model.number="generalPageSize" @change="changeGeneralPageSize" aria-label="Jumlah kegiatan per halaman">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                </select>
                                <span>/ hal</span>
                            </label>
                        </div>
                        <div class="inline-flex items-center gap-x-1">
                            <button class="inline-flex items-center gap-x-1 min-h-[38px] sm:min-h-0 py-2 sm:py-1.5 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:border-emerald-500/40 hover:bg-emerald-50/50 hover:text-emerald-700 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-300 disabled:opacity-40 disabled:pointer-events-none shadow-xs transition" type="button" @click="goToGeneralPage(currentGeneralPage - 1)" :disabled="currentGeneralPage <= 1">Sebelumnya</button>
                            <span class="inline-flex items-center justify-center size-8 rounded-lg bg-emerald-600 text-xs font-black text-white shadow-xs">{{ currentGeneralPage }}</span>
                            <button class="inline-flex items-center gap-x-1 min-h-[38px] sm:min-h-0 py-2 sm:py-1.5 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:border-emerald-500/40 hover:bg-emerald-50/50 hover:text-emerald-700 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-300 disabled:opacity-40 disabled:pointer-events-none shadow-xs transition" type="button" @click="goToGeneralPage(currentGeneralPage + 1)" :disabled="currentGeneralPage >= generalTotalPages">Berikutnya</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="border-t border-neutral-800 bg-black text-neutral-300 pt-12 pb-8">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-12">
                <div class="space-y-5 lg:col-span-7">
                    <div class="flex items-center gap-3.5 sm:gap-4">
                        <img class="h-14 w-14 shrink-0 object-contain sm:h-16 sm:w-16" src="<?= esc($logoUrl) ?><?= str_contains($logoUrl, '?') ? '&' : '?' ?>v=<?= $logoVersion ?>" alt="Logo DPRD Provinsi Sulawesi Tengah" />
                        <div class="min-w-0">
                            <span class="block text-base font-extrabold uppercase tracking-wider text-white sm:text-lg">
                                Dewan Perwakilan Rakyat Daerah
                            </span>
                            <span class="block text-sm font-semibold uppercase tracking-wider text-neutral-300 sm:text-base">
                                PROVINSI SULAWESI TENGAH
                            </span>
                        </div>
                    </div>

                    <div class="h-0.5 w-full max-w-md bg-red-700"></div>

                    <div class="flex flex-wrap items-center gap-3 pt-1">
                        <span class="text-xs font-semibold uppercase tracking-wider text-neutral-300 sm:text-sm">Media Sosial:</span>
                        <div class="flex items-center gap-2">
                            <a href="https://www.facebook.com/p/DPRD-Provinsi-Sulawesi-Tengah-100064552912240" target="_blank" rel="noopener noreferrer" class="flex size-9 items-center justify-center rounded-full border border-neutral-700 bg-neutral-900 text-neutral-300 transition hover:border-neutral-400 hover:bg-neutral-800 hover:text-white" aria-label="Facebook DPRD Provinsi Sulawesi Tengah">
                                <svg class="size-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                            </a>
                            <a href="https://www.instagram.com/dprd_sultengprov" target="_blank" rel="noopener noreferrer" class="flex size-9 items-center justify-center rounded-full border border-neutral-700 bg-neutral-900 text-neutral-300 transition hover:border-neutral-400 hover:bg-neutral-800 hover:text-white" aria-label="Instagram DPRD Provinsi Sulawesi Tengah">
                                <svg class="size-4 fill-none stroke-current" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                            </a>
                            <a href="https://www.youtube.com/@dprdprovinsisulawesitengah4027" target="_blank" rel="noopener noreferrer" class="flex size-9 items-center justify-center rounded-full border border-neutral-700 bg-neutral-900 text-neutral-300 transition hover:border-neutral-400 hover:bg-neutral-800 hover:text-white" aria-label="YouTube DPRD Provinsi Sulawesi Tengah">
                                <svg class="size-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="space-y-3.5 lg:col-span-5">
                    <h2 class="text-base font-bold uppercase tracking-wider text-white">Kontak</h2>
                    <ul class="space-y-2.5 text-xs sm:text-sm">
                        <li class="flex items-center gap-3">
                            <svg class="size-4 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <a href="tel:0451423111" class="transition hover:text-white">(0451) 423111</a>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="size-4 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <a href="mailto:sekretariatdprdsulteng@gmail.com" class="transition hover:text-white">sekretariatdprdsulteng@gmail.com</a>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="size-4 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <a href="mailto:dprd.sultengprov1@gmail.com" class="transition hover:text-white">dprd.sultengprov1@gmail.com</a>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 size-4 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="2" x2="22" y1="22" y2="22"/><line x1="4" x2="20" y1="2" y2="2"/><path d="M4 2v20"/><path d="M20 2v20"/><path d="M9 22v-4a2 2 0 0 1 4 0v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M16 10h.01"/><path d="M8 14h.01"/><path d="M16 14h.01"/></svg>
                            <address class="not-italic leading-relaxed text-neutral-300">
                                <span class="block">Jl. Dr. Samratulangi No. 80,</span>
                                <span class="block">Kel. Besusu Barat, Kec. Palu Timur</span>
                            </address>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 border-t border-neutral-800/80 pt-6">
                <div class="flex flex-col items-center justify-between gap-2 text-center text-xs text-neutral-400 sm:flex-row sm:text-left">
                    <span>&copy; <?= date('Y') ?> Sekretariat DPRD Provinsi Sulawesi Tengah. All rights reserved.</span>
                    <span class="text-neutral-400 font-medium"><?= $isMember ? 'Akses anggota' : 'Akses publik' ?></span>
                </div>
            </div>
        </div>
    </footer>
</div>

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
                const base = 'inline-flex items-center gap-x-1.5 py-1.5 px-3.5 whitespace-nowrap rounded-full text-xs font-bold transition-all duration-150 shrink-0 cursor-pointer';
                return isKomisiActive.value
                    ? `${base} bg-emerald-600 text-white shadow-xs ring-2 ring-emerald-500/30`
                    : `${base} border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:border-emerald-500/40 hover:bg-emerald-50/50 hover:text-emerald-700 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-300`;
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
            const activeNavigation = ref('all');
            const memberScope = ref(IS_MEMBER ? 'saya' : 'semua');
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
            const filteredAgendas = computed(() => {
                let visibleProjections = IS_MEMBER && memberScope.value === 'saya'
                    ? banmusProjections.value.filter((item) => item.is_participant)
                    : banmusProjections.value;
                const selectedMonths = new Set(periodMonths());
                visibleProjections = visibleProjections.filter((item) =>
                    projectionOverlapsMonths(item, selectedMonths));
                const rows = [
                    ...agendas.value.filter((item) =>
                        item.source === 'banmus'
                        && selectedMonths.has(String(item.tanggal || '').slice(0, 7))),
                    ...visibleProjections,
                ];
                if (activeNavigation.value === 'komisi') {
                    return rows.filter((item) => (item.unit_ids || []).some((id) => komisiUnitIds.value.includes(Number(id))));
                }
                if (activeNavigation.value.startsWith('unit:')) {
                    const unitId = Number(activeNavigation.value.slice(5));
                    return rows.filter((item) => (item.unit_ids || []).map(Number).includes(unitId));
                }

                return rows;
            });
            const filteredGeneralAgendas = computed(() => {
                const selectedMonths = new Set(periodMonths(periodMode.value));
                let rows = agendas.value.filter((item) =>
                    item.source === 'jadwal_umum'
                    && selectedMonths.has(String(item.tanggal || '').slice(0, 7)));
                if (activeNavigation.value === 'komisi') {
                    rows = rows.filter((item) => (item.unit_ids || []).some((id) => komisiUnitIds.value.includes(Number(id))));
                } else if (activeNavigation.value.startsWith('unit:')) {
                    const unitId = Number(activeNavigation.value.slice(5));
                    rows = rows.filter((item) => (item.unit_ids || []).map(Number).includes(unitId));
                }

                return rows;
            });
            function orderAgendaRows(rows) {
                const today = dateKey(new Date());
                const active = rows.filter((item) => item.status === 'berlangsung');
                const activeKeys = new Set(active.map((item) => item.key));
                const upcoming = rows.filter((item) =>
                    item.status !== 'proyeksi'
                    && !activeKeys.has(item.key)
                    && item.tanggal >= today
                    && item.status !== 'selesai');
                const projections = rows.filter((item) => item.status === 'proyeksi');
                const prioritizedKeys = new Set([...active, ...upcoming, ...projections].map((item) => item.key));
                const remaining = rows.filter((item) =>
                    !prioritizedKeys.has(item.key)).reverse();

                return [...active, ...upcoming, ...projections, ...remaining];
            }
            const orderedAgendas = computed(() => orderAgendaRows(filteredAgendas.value));
            const orderedGeneralAgendas = computed(() => orderAgendaRows(filteredGeneralAgendas.value));
            const todayDateKey = computed(() => dateKey(now.value));
            const todayAgendas = computed(() => {
                const today = todayDateKey.value;
                const pool = [...filteredAgendas.value, ...filteredGeneralAgendas.value];
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
                const pool = [...filteredAgendas.value, ...filteredGeneralAgendas.value];
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
            const totalPages = computed(() =>
                Math.max(1, Math.ceil(orderedAgendas.value.length / pageSize.value)));
            const pageStart = computed(() =>
                orderedAgendas.value.length ? ((currentPage.value - 1) * pageSize.value) + 1 : 0);
            const pageEnd = computed(() =>
                Math.min(currentPage.value * pageSize.value, orderedAgendas.value.length));
            const paginatedAgendas = computed(() => {
                const offset = (currentPage.value - 1) * pageSize.value;
                return orderedAgendas.value.slice(offset, offset + pageSize.value);
            });
            const agendaShelf = computed(() => {
                const scheduled = paginatedAgendas.value.filter((item) => item.status !== 'proyeksi');
                const projections = paginatedAgendas.value.filter((item) => item.status === 'proyeksi');
                const projectionTotal = orderedAgendas.value.filter((item) => item.status === 'proyeksi').length;
                const rows = [];
                rows.push({ kind: 'scheduled-header', key: 'shelf:scheduled' });
                if (scheduled.length > 0) {
                    scheduled.forEach((item) => rows.push({ kind: 'item', key: item.key, item }));
                } else {
                    rows.push({ kind: 'scheduled-empty', key: 'shelf:scheduled-empty' });
                }
                if (projections.length > 0) {
                    rows.push({ kind: 'plan-header', key: 'shelf:plan' });
                    const visibleProjections = projections.slice(0, 3);
                    visibleProjections.forEach((item) => rows.push({ kind: 'item', key: item.key, item }));
                    if (projectionTotal > visibleProjections.length) {
                        rows.push({ kind: 'plan-more', key: 'shelf:plan-more', count: projectionTotal });
                    }
                }
                return rows;
            });
            const generalTotalPages = computed(() =>
                Math.max(1, Math.ceil(orderedGeneralAgendas.value.length / generalPageSize.value)));
            const generalPageStart = computed(() =>
                orderedGeneralAgendas.value.length
                    ? ((currentGeneralPage.value - 1) * generalPageSize.value) + 1
                    : 0);
            const generalPageEnd = computed(() =>
                Math.min(currentGeneralPage.value * generalPageSize.value, orderedGeneralAgendas.value.length));
            const paginatedGeneralAgendas = computed(() => {
                const offset = (currentGeneralPage.value - 1) * generalPageSize.value;
                return orderedGeneralAgendas.value.slice(offset, offset + generalPageSize.value);
            });
            function scopeButtonClass(scope) {
                return memberScope.value === scope
                    ? 'bg-white dark:bg-slate-900 text-sky-700 dark:text-sky-300 font-bold shadow-xs border border-sky-500/30'
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-transparent';
            }

            function dateKey(date) {
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            }

            function monthKey(date) {
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            }

            function periodMonths(mode = periodMode.value) {
                if (mode === 'month') {
                    const cursor = calendarCursor.value;
                    return [monthKey(cursor)];
                }

                const current = now.value;
                const firstMonth = mode === 'quarter'
                    ? Math.floor(current.getMonth() / 3) * 3
                    : (current.getMonth() < 6 ? 0 : 6);
                const count = mode === 'quarter' ? 3 : 6;

                return Array.from({ length: count }, (_, offset) =>
                    monthKey(new Date(current.getFullYear(), firstMonth + offset, 1)));
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
                    url.searchParams.set('scope', memberScope.value);
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
                [...filteredAgendas.value, ...filteredGeneralAgendas.value].forEach((item) => {
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
                calendarCursor.value = new Date(cursor.getFullYear(), cursor.getMonth() + delta, 1);
            }

            function pickCalendarDay(cell) {
                if (!cell || cell.count === 0) {
                    return;
                }
                const pool = [...filteredAgendas.value, ...filteredGeneralAgendas.value];
                const target = pool.find((item) =>
                    item.tanggal === cell.key && item.status !== 'proyeksi');
                isCalendarOpen.value = false;
                if (target) {
                    focusAgenda(target);
                }
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

            function setMemberScope(scope) {
                if (!IS_MEMBER) {
                    return;
                }
                if (!['semua', 'saya'].includes(scope) || memberScope.value === scope) {
                    return;
                }
                memberScope.value = scope;
                activeNavigation.value = 'all';
                resetAgendaSelection();
                resetGeneralSelection();
                updateUrl();
                loadAgenda();
            }

            watch([calendarCursor, periodMode], () => {
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
                const base = 'transition';
                return periodMode.value === key
                    ? `${base} bg-emerald-600 text-white shadow-xs`
                    : `${base} border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800`;
            }

            function setCalendarScope(mode) {
                const current = now.value;
                calendarCursor.value = new Date(current.getFullYear(), current.getMonth(), 1);
                periodMode.value = mode;
            }

            function mobileTabClass(tab) {
                if (activeMobileTab.value !== tab) {
                    return 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-transparent';
                }
                return tab === 'rapat'
                    ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-300 font-bold shadow-xs border border-emerald-500/30'
                    : 'bg-white dark:bg-slate-900 text-purple-700 dark:text-purple-300 font-bold shadow-xs border border-purple-500/30';
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
                setOptionalParam(url, 'menu', activeNavigation.value, 'all');
                setOptionalParam(url, 'scope', memberScope.value, IS_MEMBER ? 'saya' : 'semua');
                if (periodMode.value === 'month') {
                    const currentMonthKey = monthKey(new Date(now.value.getFullYear(), now.value.getMonth(), 1));
                    setOptionalParam(url, 'periode', monthKey(calendarCursor.value), currentMonthKey);
                } else {
                    url.searchParams.set('periode', periodMode.value);
                }
                setOptionalParam(url, 'tampil', String(pageSize.value), '10');
                setOptionalParam(url, 'tampil_umum', String(generalPageSize.value), '10');
                setOptionalParam(url, 'halaman', String(currentPage.value), '1');
                setOptionalParam(url, 'halaman_umum', String(currentGeneralPage.value), '1');
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
                const base = 'inline-flex items-center gap-x-1.5 min-h-[40px] sm:min-h-0 py-2 sm:py-1.5 px-3.5 whitespace-nowrap rounded-full text-xs font-bold transition-all duration-150 shrink-0';
                return activeNavigation.value === value
                    ? `${base} bg-emerald-600 text-white shadow-xs ring-2 ring-emerald-500/30`
                    : `${base} border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:border-emerald-500/40 hover:bg-emerald-50/50 hover:text-emerald-700 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-300`;
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
                const base = 'items-center gap-1.5 py-0.5 px-2.5 rounded-full text-xs font-semibold';
                return {
                    proyeksi: `${base} bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/30`,
                    berlangsung: `${base} bg-rose-500/15 text-rose-800 dark:text-rose-300 border border-rose-500/30`,
                    persiapan: `${base} bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30`,
                    menunggu: `${base} bg-sky-500/10 text-sky-800 dark:text-sky-300 border border-sky-500/30`,
                    selesai: 'items-center gap-1.5 py-0.5 px-2.5 rounded-full text-xs font-semibold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700',
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
                const [year, month, day] = String(value).split('-').map(Number);
                return new Date(year, month - 1, day);
            }

            function fullDate(value) {
                const date = parseDate(value);
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
                if (requestedMenu === 'all' || requestedMenu === 'komisi' || /^unit:\d+$/.test(requestedMenu || '')) {
                    activeNavigation.value = requestedMenu;
                }
                if (IS_MEMBER && ['saya', 'semua'].includes(params.get('scope'))) {
                    memberScope.value = params.get('scope');
                }
                const requestedPeriode = params.get('periode') || '';
                let cursorDate = null;
                if (/^\d{4}-\d{2}$/.test(requestedPeriode)) {
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
                if ([10, 25, 50, 100].includes(Number(params.get('tampil')))) {
                    pageSize.value = Number(params.get('tampil'));
                }
                if ([10, 25, 50, 100].includes(Number(params.get('tampil_umum')))) {
                    generalPageSize.value = Number(params.get('tampil_umum'));
                }
                if (/^[1-9]\d*$/.test(params.get('halaman') || '')) {
                    currentPage.value = Number(params.get('halaman'));
                }
                if (/^[1-9]\d*$/.test(params.get('halaman_umum') || '')) {
                    currentGeneralPage.value = Number(params.get('halaman_umum'));
                }
                if (params.get('tab') === 'umum') {
                    activeMobileTab.value = 'umum';
                }
                loadWeather();
                agendaTimer = setInterval(loadAgenda, 60000);
                clockTimer = setInterval(() => {
                    now.value = new Date();
                }, 1000);
                weatherTimer = setInterval(loadWeather, 1800000);
                window.addEventListener('resize', updateUnitScrollState);
                document.addEventListener('click', handleDocumentClick);
                window.addEventListener('scroll', handleWindowScroll, { passive: true });
            });

            onUnmounted(() => {
                clearInterval(agendaTimer);
                clearInterval(clockTimer);
                clearInterval(weatherTimer);
                window.removeEventListener('resize', updateUnitScrollState);
                document.removeEventListener('click', handleDocumentClick);
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
                toggleKomisiDropdown,
                selectKomisiFilter,
                isCalendarOpen,
                calendarAnchor,
                calendarRef,
                calendarStyle,
                calendarLabel,
                calendarWeeks,
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
                memberScope,
                periodMode,
                pageSize,
                generalPageSize,
                currentPage,
                currentGeneralPage,
                filteredAgendas,
                filteredGeneralAgendas,
                orderedAgendas,
                orderedGeneralAgendas,
                todayAgendas,
                activeLiveAgendas,
                upcomingTodayAgendas,
                nearestUpcomingAgenda,
                focusAgenda,
                paginatedAgendas,
                agendaShelf,
                paginatedGeneralAgendas,
                totalPages,
                generalTotalPages,
                pageStart,
                pageEnd,
                generalPageStart,
                generalPageEnd,
                expandedAgendaKey,
                expandedGeneralKey,
                scopeButtonClass,
                isDark,
                loadAgenda,
                setNavigation,
                updateUnitScrollState,
                unitScrollMaskClass,
                scrollUnitFilters,
                setMemberScope,
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
                streamUrl,
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
