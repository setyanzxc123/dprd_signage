<?php
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$vueVersion  = is_file(FCPATH . 'assets/vendor/vue/vue.global.prod.js') ? filemtime(FCPATH . 'assets/vendor/vue/vue.global.prod.js') : time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Agenda Rapat - DPRD Provinsi Sulawesi Tengah</title>
    <meta name="description" content="Agenda rapat publik DPRD Provinsi Sulawesi Tengah." />

    <meta property="og:title" content="Agenda Rapat DPRD Sulteng" />
    <meta property="og:description" content="Pantau jadwal rapat DPRD Provinsi Sulawesi Tengah secara langsung." />
    <meta property="og:type" content="website" />

    <link rel="icon" type="image/jpeg" href="<?= base_url('assets/images/logo_dprd.jpg') ?>" />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <script {csp-script-nonce}>
        (() => {
            const stored = localStorage.getItem('public_schedule_theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const useDark = stored === 'dark' || (!stored && prefersDark);
            if (useDark) {
                document.documentElement.classList.add('dark');
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <link href="<?= base_url('assets/css/publik.css') ?>" rel="stylesheet" />
    <script src="<?= base_url('assets/vendor/vue/vue.global.prod.js?v=' . $vueVersion) ?>"></script>
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased">

<div id="app" v-cloak>
    <header class="sticky top-0 z-50 border-b border-base-300 bg-base-100/95 backdrop-blur-xl">
        <div class="mx-auto flex min-h-[72px] w-[min(960px,calc(100%-20px))] items-center justify-between gap-2 px-1 py-2 sm:min-h-[88px] sm:w-[min(960px,calc(100%-28px))] sm:gap-3 sm:py-3">
            <a class="flex min-w-0 items-center gap-3 sm:gap-4" href="<?= base_url('jadwal') ?>" aria-label="Agenda rapat DPRD">
                <img class="h-12 w-12 shrink-0 rounded-xl border border-base-300 bg-white object-contain sm:h-16 sm:w-16 sm:rounded-2xl" src="<?= esc($logoUrl) ?>" alt="Logo DPRD" />
                <span class="min-w-0">
                    <span class="block truncate text-sm font-black leading-tight text-base-content min-[380px]:text-base sm:text-xl">Agenda Rapat DPRD</span>
                    <span class="mt-0.5 block truncate text-[10px] font-bold leading-tight text-base-content/65 min-[380px]:text-xs sm:text-sm">Provinsi Sulawesi Tengah</span>
                </span>
            </a>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2.5">
                <button
                    class="btn btn-ghost btn-circle btn-sm text-base-content/80 hover:text-primary sm:btn-md"
                    type="button"
                    @click="toggleTheme"
                    :aria-label="isDark ? 'Gunakan mode terang' : 'Gunakan mode gelap'"
                >
                    <svg v-if="!isDark" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v2M12 19v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M3 12h2M19 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/></svg>
                    <svg v-else viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 15.5A8.5 8.5 0 0 1 8.5 4a7 7 0 1 0 11.5 11.5Z"/></svg>
                </button>
                <div :class="syncStatusBadgeClass" :title="syncStatusTitle">
                    <span :class="syncStatusDotClass"></span>
                    <span class="hidden sm:inline">{{ syncStatusLabel }}</span>
                    <span class="sm:hidden">{{ syncStatusMobileLabel }}</span>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto w-[min(960px,calc(100%-20px))] py-3 sm:w-[min(960px,calc(100%-28px))] sm:py-4">
        <section class="card border border-base-300 bg-base-100 shadow-sm rounded-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-base-200 p-3 sm:p-4">
                <div class="min-w-0">
                    <p class="text-[10px] font-extrabold uppercase tracking-[.08em] text-primary">Tanggal aktif</p>
                    <h1 class="mt-1 text-lg font-black leading-tight text-base-content sm:text-2xl">{{ selectedDateFull }}</h1>
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <div class="join flex-1 sm:flex-none">
                        <button class="btn btn-sm btn-outline join-item" type="button" @click="prevDay" aria-label="Hari sebelumnya">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <button class="btn btn-sm btn-outline join-item flex-1 font-extrabold sm:flex-none" type="button" @click="goToday">Hari ini</button>
                        <button class="btn btn-sm btn-outline join-item" type="button" @click="nextDay" aria-label="Hari berikutnya">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>
                    <button class="btn btn-sm btn-outline font-extrabold" type="button" @click="showCalendar = !showCalendar">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ showCalendar ? 'Tutup' : 'Kalender' }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-7 gap-1 border-b border-base-200 p-2 sm:gap-2 sm:p-3">
                <button
                    v-for="day in dateStrip"
                    :key="day.key"
                    :class="dateStripClass(day)"
                    type="button"
                    @click="setDate(day.date)"
                    :aria-pressed="day.key === selectedDateKey"
                >
                    <span class="text-[10px] font-extrabold uppercase tracking-[.04em] opacity-75">{{ day.day }}</span>
                    <span class="mt-1 text-lg font-black leading-none sm:text-xl">{{ day.date.getDate() }}</span>
                    <span v-if="day.count" :class="dateStripCountBadgeClass(day)">{{ day.count }}</span>
                    <span :class="dateStripMetaClass(day)">
                        {{ day.count ? `${day.count} agenda` : 'Kosong' }}
                    </span>
                </button>
            </div>

            <div v-if="showCalendar" class="border-b border-base-200 p-3 sm:p-4">
                <div class="mb-3 flex flex-col gap-3 p-1 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-base-content">{{ monthTitle }}</h2>
                        <p class="mt-0.5 hidden text-xs font-semibold text-base-content/55 sm:block">Pilih bulan dan tahun untuk membuka arsip agenda.</p>
                    </div>
                    <div class="grid grid-cols-[minmax(0,1fr)_88px] items-center gap-2 sm:flex sm:flex-wrap">
                        <select
                            class="select select-sm select-bordered w-full sm:w-36"
                            :value="activeMonthIndex"
                            aria-label="Pilih bulan arsip"
                            @change="setCalendarMonth($event.target.value)"
                        >
                            <option v-for="(month, index) in monthNames" :key="month" :value="index">{{ month }}</option>
                        </select>
                        <input
                            class="input input-sm input-bordered w-full sm:w-24"
                            type="number"
                            min="2000"
                            :max="archiveMaxYear"
                            :value="activeYear"
                            aria-label="Pilih tahun arsip"
                            @change="setCalendarYear($event.target.value)"
                        />
                        <div class="join col-span-2 justify-self-end sm:col-span-1">
                            <button class="btn btn-sm btn-outline join-item" type="button" @click="prevMonth" aria-label="Bulan sebelumnya">
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <button class="btn btn-sm btn-outline join-item" type="button" @click="nextMonth" aria-label="Bulan berikutnya">
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-7 gap-1.5">
                    <span v-for="weekday in weekdays" :key="weekday" class="grid h-7 place-items-center text-[10px] font-bold uppercase text-base-content/50">{{ weekday }}</span>
                    <span v-for="blank in calendarLeadingBlanks" :key="'blank-' + blank" class="h-10"></span>
                    <button
                        v-for="day in calendarDays"
                        :key="day"
                        :class="calendarDayClass(day)"
                        type="button"
                        @click="selectDay(day)"
                    >
                        <span>{{ day }}</span>
                        <small v-if="agendaCount(day)" :class="['absolute right-1 top-1 badge badge-xs font-extrabold', isActiveDay(day) ? 'badge-accent' : 'badge-neutral']">{{ agendaCount(day) }}</small>
                    </button>
                </div>
            </div>

            <div class="grid gap-3 p-3 sm:p-4 md:grid-cols-[minmax(0,1fr)_190px_150px]">
                <input class="input input-bordered w-full" v-model.trim="query" type="search" placeholder="Cari agenda..." autocomplete="off" />
                <button class="btn btn-outline font-extrabold md:hidden" type="button" @click="showFilters = !showFilters">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                    {{ showFilters ? 'Tutup filter' : 'Filter' }}
                    <span v-if="hasActiveFilters" class="badge badge-sm badge-primary">{{ activeFilterLabels.length }}</span>
                </button>

                <div :class="filterPanelClass">
                    <select class="select select-bordered w-full" v-model="unitFilter">
                        <option value="all">Semua unit</option>
                        <option v-for="unit in unitOptions" :key="unit" :value="unit">{{ unit }}</option>
                    </select>

                    <select class="select select-bordered w-full" v-model="statusFilter">
                        <option value="all">Semua status</option>
                        <option value="berlangsung">Berlangsung</option>
                        <option value="persiapan">Persiapan</option>
                        <option value="menunggu">Menunggu</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
            </div>

            <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2 border-t border-base-200 px-3 pb-3 text-xs font-bold sm:px-4 sm:pb-4">
                <span v-for="filter in activeFilterLabels" :key="filter" class="badge badge-outline badge-sm">{{ filter }}</span>
                <button class="btn btn-xs btn-ghost text-primary" type="button" @click="resetFilters">Reset filter</button>
            </div>
        </section>

        <section class="mt-4">
            <div v-if="!initialLoading" class="mb-3 grid grid-cols-4 gap-1.5 sm:gap-2">
                <div class="rounded-xl border border-base-300 bg-base-100 p-2 sm:p-3">
                    <p class="truncate text-[9px] font-extrabold uppercase tracking-[.04em] text-base-content/50 sm:text-[10px] sm:tracking-[.06em]">Total</p>
                    <strong class="mt-1 block text-base leading-none text-base-content sm:text-lg">{{ daySummary.total }}</strong>
                </div>
                <div class="rounded-xl border border-base-300 bg-base-100 p-2 sm:p-3">
                    <p class="truncate text-[9px] font-extrabold uppercase tracking-[.04em] text-base-content/50 sm:text-[10px] sm:tracking-[.06em]"><span class="sm:hidden">Live</span><span class="hidden sm:inline">Berlangsung</span></p>
                    <strong class="mt-1 block text-base leading-none text-success sm:text-lg">{{ daySummary.berlangsung }}</strong>
                </div>
                <div class="rounded-xl border border-base-300 bg-base-100 p-2 sm:p-3">
                    <p class="truncate text-[9px] font-extrabold uppercase tracking-[.04em] text-base-content/50 sm:text-[10px] sm:tracking-[.06em]">Mendatang</p>
                    <strong class="mt-1 block text-base leading-none text-warning sm:text-lg">{{ daySummary.mendatang }}</strong>
                </div>
                <div class="rounded-xl border border-base-300 bg-base-100 p-2 sm:p-3">
                    <p class="truncate text-[9px] font-extrabold uppercase tracking-[.04em] text-base-content/50 sm:text-[10px] sm:tracking-[.06em]">Selesai</p>
                    <strong class="mt-1 block text-base leading-none text-info sm:text-lg">{{ daySummary.selesai }}</strong>
                </div>
            </div>

            <div class="mb-2.5 flex flex-col gap-1 px-1 text-xs font-bold text-base-content/60 sm:flex-row sm:items-center sm:justify-between">
                <span v-if="initialLoading">Memuat agenda...</span>
                <span v-else>{{ agendaListCountLabel }}</span>
                <span v-if="refreshing && !initialLoading" class="text-base-content/45">Memperbarui...</span>
            </div>

            <div v-if="initialLoading" class="grid gap-3">
                <div class="skeleton h-28 w-full rounded-xl border border-base-300" v-for="n in 3" :key="'sk-' + n"></div>
            </div>

            <div v-else-if="filteredAgendas.length === 0" class="flex flex-col items-center justify-center min-h-[180px] rounded-xl border border-dashed border-base-300 bg-base-100 p-6 text-center text-base-content/60 gap-2">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" class="opacity-45"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <p class="font-semibold text-sm">{{ emptyStateText }}</p>
                <button v-if="hasActiveFilters" class="btn btn-sm btn-outline" type="button" @click="resetFilters">Reset filter</button>
            </div>

            <div v-else class="grid gap-3">
                <article
                    v-for="item in filteredAgendas"
                    :key="item.id"
                    :id="'agenda-' + item.id"
                    :class="agendaCardClass(item)"
                >
                    <div class="card-body p-4">
                        <div class="grid gap-3 sm:grid-cols-[82px_minmax(0,1fr)_auto] sm:items-start">
                            <div class="rounded-lg border border-base-300 bg-base-200/50 p-2 text-center shrink-0 min-w-[82px]">
                                <div class="text-lg font-black leading-none text-base-content">{{ item.waktu_mulai }}</div>
                                <div class="mt-1 text-[10px] font-extrabold uppercase tracking-[.06em] text-base-content/60">{{ item.waktu_selesai }} WITA</div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                    <span :class="statusPillClass(item.status)">
                                        <span :class="['h-1.5 w-1.5 rounded-full bg-current', item.status === 'berlangsung' ? 'animate-pulse' : '']"></span>
                                        {{ statusLabel(item.status) }}
                                    </span>
                                    <span v-if="item.jenis" class="badge badge-sm badge-neutral">{{ item.jenis }}</span>
                                </div>

                                <h2 class="text-sm font-extrabold leading-snug text-base-content sm:text-base">{{ item.judul }}</h2>

                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm leading-5 text-base-content/75">
                                    <span>{{ item.ruangan }}</span>
                                    <span v-if="item.komisi">{{ item.komisi }}</span>
                                </div>

                                <p v-if="item.keterangan" class="mt-2 line-clamp-2 text-sm leading-5 text-base-content/65">{{ item.keterangan }}</p>
                            </div>

                            <div class="grid gap-2 sm:flex sm:min-w-[112px] sm:flex-col">
                                <a
                                    v-if="canWatchStream(item)"
                                    :class="streamButtonClass(item)"
                                    :href="item.stream_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="mr-1"><path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.75 15.02l5.75-3.27-5.75-3.27v6.54z" fill="currentColor"/></svg>
                                    {{ streamButtonLabel(item) }}
                                </a>
                                <button
                                    v-else-if="item.has_stream"
                                    class="btn btn-sm btn-disabled w-full font-extrabold"
                                    type="button"
                                    disabled
                                    :title="streamButtonHint(item)"
                                >
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="mr-1"><path d="M10 8.64v6.72L15.27 12 10 8.64Z" fill="currentColor" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    {{ streamButtonLabel(item) }}
                                </button>
                                <a
                                    v-if="item.has_materi"
                                    class="btn btn-sm btn-outline w-full font-extrabold"
                                    :href="item.materi_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="mr-1"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Berkas
                                </a>
                                <p v-if="item.has_stream && !canWatchStream(item)" class="text-xs font-semibold leading-4 text-base-content/55 sm:hidden">Live aktif saat rapat berlangsung.</p>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <footer class="border-t border-base-300 py-6 text-xs text-base-content/50 bg-base-100/40">
        <div class="mx-auto flex w-[min(960px,calc(100%-20px))] flex-col gap-1.5 px-1 sm:w-[min(960px,calc(100%-28px))] sm:flex-row sm:justify-between">
            <span>DPRD Provinsi Sulawesi Tengah &copy; <?= date('Y') ?></span>
            <span>Auto-refresh 60 detik</span>
        </div>
    </footer>

</div>

<script {csp-script-nonce}>
    const { createApp, ref, computed, watch, onMounted, onUnmounted } = Vue;

    createApp({
        setup() {
            const today = new Date();
            const activeDate = ref(new Date(today));
            const jadwal = ref([]);
            const initialLoading = ref(true);
            const refreshing = ref(false);
            const loadError = ref(false);
            const lastRefresh = ref('');
            const query = ref('');
            const unitFilter = ref('all');
            const statusFilter = ref('all');
            const showCalendar = ref(false);
            const showFilters = ref(false);
            const isDark = ref(document.documentElement.classList.contains('dark'));
            const API_URL = '<?= esc($apiUrl) ?>';

            const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const shortDayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
            const weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

            const selectedDateKey = computed(() => toYMD(activeDate.value));
            const monthKey = computed(() => toYM(activeDate.value));
            const monthTitle = computed(() => `${monthNames[activeDate.value.getMonth()]} ${activeDate.value.getFullYear()}`);
            const selectedDateFull = computed(() => fullDate(selectedDateKey.value));
            const activeMonthIndex = computed(() => activeDate.value.getMonth());
            const activeYear = computed(() => activeDate.value.getFullYear());
            const archiveMaxYear = today.getFullYear() + 1;

            const dayAgendas = computed(() => jadwal.value.filter((item) => item.tanggal === selectedDateKey.value));
            const hasActiveFilters = computed(() => Boolean(query.value.trim())
                || unitFilter.value !== 'all'
                || statusFilter.value !== 'all');

            const filteredAgendas = computed(() => {
                const text = query.value.toLowerCase();
                return dayAgendas.value.filter((item) => {
                    const haystack = `${item.judul || ''} ${item.keterangan || ''} ${item.komisi || ''} ${item.ruangan || ''}`.toLowerCase();
                    const matchesText = !text || haystack.includes(text);
                    const matchesStatus = statusFilter.value === 'all' || item.status === statusFilter.value;
                    const matchesUnit = unitFilter.value === 'all' || splitUnits(item.komisi).includes(unitFilter.value);
                    return matchesText && matchesStatus && matchesUnit;
                });
            });

            const daySummary = computed(() => {
                const summary = {
                    total: dayAgendas.value.length,
                    berlangsung: 0,
                    mendatang: 0,
                    selesai: 0,
                };

                dayAgendas.value.forEach((item) => {
                    if (item.status === 'berlangsung') {
                        summary.berlangsung += 1;
                    } else if (item.status === 'selesai') {
                        summary.selesai += 1;
                    } else {
                        summary.mendatang += 1;
                    }
                });

                return summary;
            });

            const activeFilterLabels = computed(() => {
                const labels = [];
                if (query.value.trim()) {
                    labels.push(`Cari: ${query.value.trim()}`);
                }
                if (unitFilter.value !== 'all') {
                    labels.push(`Unit: ${unitFilter.value}`);
                }
                if (statusFilter.value !== 'all') {
                    labels.push(`Status: ${statusLabel(statusFilter.value)}`);
                }
                return labels;
            });

            const agendaListCountLabel = computed(() => {
                if (hasActiveFilters.value) {
                    return `${filteredAgendas.value.length} dari ${dayAgendas.value.length} agenda`;
                }

                return `${filteredAgendas.value.length} agenda`;
            });

            const emptyStateText = computed(() => {
                if (dayAgendas.value.length > 0 && hasActiveFilters.value) {
                    return 'Tidak ada agenda yang cocok dengan filter.';
                }

                return 'Tidak ada agenda pada tanggal ini.';
            });

            const syncStatusLabel = computed(() => {
                if (initialLoading.value && !lastRefresh.value) {
                    return 'Memuat';
                }
                if (loadError.value) {
                    return 'Gagal update';
                }
                return lastRefresh.value ? `Diperbarui ${lastRefresh.value}` : 'Diperbarui';
            });

            const syncStatusMobileLabel = computed(() => {
                if (initialLoading.value && !lastRefresh.value) {
                    return 'Muat';
                }
                if (loadError.value) {
                    return 'Gagal';
                }
                return lastRefresh.value || 'Siap';
            });

            const syncStatusTitle = computed(() => {
                if (initialLoading.value && !lastRefresh.value) {
                    return 'Memuat agenda publik.';
                }
                if (refreshing.value) {
                    return 'Sedang memperbarui agenda. Data lama tetap ditampilkan.';
                }
                if (loadError.value) {
                    return 'Gagal memperbarui agenda. Data terakhir tetap ditampilkan.';
                }
                return lastRefresh.value ? `Update terakhir ${lastRefresh.value}` : 'Agenda siap.';
            });

            const syncStatusBadgeClass = computed(() => {
                if (initialLoading.value && !lastRefresh.value) {
                    return 'badge badge-sm badge-neutral gap-1 px-2 font-bold';
                }
                if (loadError.value) {
                    return 'badge badge-sm badge-error gap-1 px-2 font-bold';
                }
                return 'badge badge-sm badge-success gap-1 px-2 font-bold';
            });

            const syncStatusDotClass = computed(() => {
                const base = 'h-1.5 w-1.5 rounded-full';
                if (initialLoading.value && !lastRefresh.value) {
                    return `${base} animate-pulse bg-neutral-content`;
                }
                if (loadError.value) {
                    return `${base} bg-error-content`;
                }
                return `${base} animate-pulse bg-success-content`;
            });

            const filterPanelClass = computed(() => {
                return (showFilters.value || hasActiveFilters.value)
                    ? 'grid gap-3 md:contents'
                    : 'hidden md:contents';
            });

            const unitOptions = computed(() => {
                const units = new Set();
                jadwal.value.forEach((item) => splitUnits(item.komisi).forEach((unit) => units.add(unit)));
                return Array.from(units).sort((a, b) => a.localeCompare(b, 'id'));
            });

            const agendaDayMap = computed(() => {
                const map = new Map();
                jadwal.value.forEach((item) => {
                    const date = parseYMD(item.tanggal);
                    if (toYM(date) !== monthKey.value) {
                        return;
                    }
                    const day = date.getDate();
                    map.set(day, (map.get(day) || 0) + 1);
                });
                return map;
            });

            const dateStrip = computed(() => {
                const days = [];
                for (let offset = -3; offset <= 3; offset += 1) {
                    const date = new Date(activeDate.value);
                    date.setDate(date.getDate() + offset);
                    days.push({
                        date,
                        key: toYMD(date),
                        day: shortDayNames[date.getDay()],
                        count: toYM(date) === monthKey.value ? (agendaDayMap.value.get(date.getDate()) || 0) : 0,
                    });
                }
                return days;
            });

            const agendaDaySet = computed(() => new Set(agendaDayMap.value.keys()));
            const calendarDays = computed(() => daysInMonth(activeDate.value));
            const calendarLeadingBlanks = computed(() => {
                const first = new Date(activeDate.value.getFullYear(), activeDate.value.getMonth(), 1).getDay();
                return first === 0 ? 6 : first - 1;
            });

            function toYMD(date) {
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            }

            // Sync theme toggle and daisyUI data-theme attributes
            watch(isDark, (val) => {
                document.documentElement.classList.toggle('dark', val);
                document.documentElement.setAttribute('data-theme', val ? 'dark' : 'light');
            }, { immediate: true });

            function toYM(date) {
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            }

            function parseYMD(value) {
                const [year, month, day] = value.split('-').map(Number);
                return new Date(year, month - 1, day);
            }

            function fullDate(value) {
                const date = typeof value === 'string' ? parseYMD(value) : value;
                return `${dayNames[date.getDay()]}, ${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;
            }

            function daysInMonth(date) {
                const total = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
                return Array.from({ length: total }, (_, index) => index + 1);
            }

            function daysInTargetMonth(year, month) {
                return new Date(year, month + 1, 0).getDate();
            }

            function splitUnits(value) {
                return String(value || '')
                    .split(',')
                    .map((unit) => unit.trim())
                    .filter(Boolean);
            }

            function statusLabel(status) {
                return {
                    berlangsung: 'Berlangsung',
                    persiapan: 'Persiapan',
                    menunggu: 'Menunggu',
                    selesai: 'Selesai',
                }[status] || status || '-';
            }



            function statusPillClass(status) {
                return {
                    berlangsung: 'badge badge-success badge-sm gap-1.5',
                    persiapan: 'badge badge-warning badge-sm gap-1.5',
                    menunggu: 'badge badge-neutral badge-sm gap-1.5',
                    selesai: 'badge badge-info badge-sm gap-1.5',
                }[status] || 'badge badge-neutral badge-sm gap-1.5';
            }

            function agendaCardClass(item) {
                const base = 'card card-border bg-base-100 shadow-sm transition-all duration-200';
                const statusStyles = {
                    selesai: 'opacity-65 hover:opacity-100',
                }[item.status] || 'hover:border-primary';
                return `${base} ${statusStyles}`;
            }

            function canWatchStream(item) {
                return Boolean(item.has_stream
                    && item.stream_url
                    && ['berlangsung', 'selesai'].includes(item.status));
            }

            function streamButtonLabel(item) {
                if (item.status === 'berlangsung') {
                    return 'Nonton Live';
                }

                if (item.status === 'selesai') {
                    return 'Tonton Rekaman';
                }

                return 'Belum live';
            }

            function streamButtonClass(item) {
                const base = 'btn btn-sm w-full font-extrabold';
                return item.status === 'berlangsung'
                    ? `${base} btn-error`
                    : `${base} btn-outline`;
            }

            function streamButtonHint(item) {
                return 'Tombol nonton aktif saat rapat sedang berlangsung.';
            }

            function calendarDayClass(day) {
                const base = 'relative grid h-10 place-items-center rounded-xl border text-sm font-extrabold transition cursor-pointer';
                if (isActiveDay(day)) {
                    return `${base} border-primary bg-primary text-primary-content shadow-sm`;
                }
                if (agendaDaySet.value.has(day)) {
                    return `${base} border-primary/25 bg-primary/10 text-primary hover:border-primary`;
                }
                if (isTodayInMonth(day)) {
                    return `${base} border-base-300 bg-base-100 text-base-content ring-2 ring-primary/20 hover:border-primary`;
                }
                return `${base} border-base-200 bg-base-100 text-base-content hover:border-primary`;
            }

            function dateStripClass(day) {
                const active = day.key === selectedDateKey.value;
                const base = 'relative flex min-h-[76px] flex-col items-center justify-center rounded-xl border p-2.5 text-center transition cursor-pointer';
                return active
                    ? `${base} border-primary bg-primary text-primary-content font-bold shadow-sm`
                    : `${base} border-base-300 bg-base-100 text-base-content hover:border-primary hover:text-primary`;
            }

            function dateStripMetaClass(day) {
                const active = day.key === selectedDateKey.value;
                const base = 'mt-1 hidden text-[10px] font-extrabold leading-none sm:block';
                if (active) {
                    return `${base} text-primary-content/85`;
                }

                return day.count
                    ? `${base} text-primary`
                    : `${base} text-base-content/45`;
            }

            function dateStripCountBadgeClass(day) {
                const active = day.key === selectedDateKey.value;
                return active
                    ? 'badge badge-xs badge-accent absolute right-1 top-1 font-extrabold sm:hidden'
                    : 'badge badge-xs badge-neutral absolute right-1 top-1 font-extrabold sm:hidden';
            }

            function toggleTheme() {
                isDark.value = !isDark.value;
                localStorage.setItem('public_schedule_theme', isDark.value ? 'dark' : 'light');
            }

            function resetFilters() {
                query.value = '';
                unitFilter.value = 'all';
                statusFilter.value = 'all';
            }

            function setDate(date) {
                const previousMonth = monthKey.value;
                activeDate.value = new Date(date);
                if (toYM(date) !== previousMonth) {
                    loadData();
                }
            }

            function selectDay(day) {
                setDate(new Date(activeDate.value.getFullYear(), activeDate.value.getMonth(), day));
            }

            function prevDay() {
                const date = new Date(activeDate.value);
                date.setDate(date.getDate() - 1);
                setDate(date);
            }

            function nextDay() {
                const date = new Date(activeDate.value);
                date.setDate(date.getDate() + 1);
                setDate(date);
            }

            function prevMonth() {
                setDate(new Date(activeDate.value.getFullYear(), activeDate.value.getMonth() - 1, 1));
            }

            function nextMonth() {
                setDate(new Date(activeDate.value.getFullYear(), activeDate.value.getMonth() + 1, 1));
            }

            function setCalendarMonth(value) {
                const month = Number(value);
                if (!Number.isInteger(month) || month < 0 || month > 11) {
                    return;
                }

                const current = activeDate.value;
                const day = Math.min(current.getDate(), daysInTargetMonth(current.getFullYear(), month));
                setDate(new Date(current.getFullYear(), month, day));
            }

            function setCalendarYear(value) {
                const year = Number(value);
                if (!Number.isInteger(year) || year < 2000 || year > archiveMaxYear) {
                    return;
                }

                const current = activeDate.value;
                const day = Math.min(current.getDate(), daysInTargetMonth(year, current.getMonth()));
                setDate(new Date(year, current.getMonth(), day));
            }

            // Sync URL query params with active date on load
            const updateUrlParams = () => {
                const url = new URL(window.location.href);
                url.searchParams.set('date', selectedDateKey.value);
                window.history.replaceState({}, '', url.toString());
            };

            function goToday() {
                setDate(new Date(today));
            }

            function isActiveDay(day) {
                return activeDate.value.getDate() === day;
            }

            // Sync browser back navigation and dynamic query updating
            watch(selectedDateKey, (val) => {
                const url = new URL(window.location.href);
                url.searchParams.set('date', val);
                window.history.replaceState({}, '', url.toString());
            });

            function isTodayInMonth(day) {
                return activeDate.value.getFullYear() === today.getFullYear()
                    && activeDate.value.getMonth() === today.getMonth()
                    && day === today.getDate();
            }

            function agendaCount(day) {
                return agendaDayMap.value.get(day) || 0;
            }

            function currentTime() {
                const now = new Date();
                return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
            }

            let requestSequence = 0;
            let jadwalSignature = '';

            function loadData() {
                const requestId = ++requestSequence;
                const requestedMonth = monthKey.value;
                refreshing.value = true;

                fetch(`${API_URL}?month=${requestedMonth}`)
                    .then((response) => response.json())
                    .then((payload) => {
                        if (requestId !== requestSequence) {
                            return;
                        }

                        const nextData = payload.data || [];
                        const nextSignature = JSON.stringify(nextData);
                        if (nextSignature !== jadwalSignature) {
                            jadwal.value = nextData;
                            jadwalSignature = nextSignature;
                        }

                        lastRefresh.value = currentTime();
                        loadError.value = false;
                    })
                    .catch((error) => {
                        if (requestId !== requestSequence) {
                            return;
                        }

                        loadError.value = true;
                        console.error('[Publik] Gagal ambil data:', error);
                    })
                    .finally(() => {
                        if (requestId !== requestSequence) {
                            return;
                        }

                        initialLoading.value = false;
                        refreshing.value = false;
                    });
            }

            watch(unitOptions, (options) => {
                if (unitFilter.value !== 'all' && !options.includes(unitFilter.value)) {
                    unitFilter.value = 'all';
                }
            });

            let timer = null;
            onMounted(() => {
                const params = new URLSearchParams(window.location.search);
                const dateParam = params.get('date');
                if (/^\d{4}-\d{2}-\d{2}$/.test(dateParam || '')) {
                    activeDate.value = parseYMD(dateParam);
                }

                loadData();
                timer = setInterval(loadData, 60000);
            });

            onUnmounted(() => clearInterval(timer));

            return {
                initialLoading,
                refreshing,
                loadError,
                lastRefresh,
                query,
                unitFilter,
                statusFilter,
                showCalendar,
                showFilters,
                isDark,
                weekdays,
                dateStrip,
                selectedDateKey,
                selectedDateFull,
                monthTitle,
                activeMonthIndex,
                activeYear,
                archiveMaxYear,
                monthNames,
                dayAgendas,
                filteredAgendas,
                daySummary,
                hasActiveFilters,
                activeFilterLabels,
                agendaListCountLabel,
                emptyStateText,
                syncStatusLabel,
                syncStatusMobileLabel,
                syncStatusTitle,
                syncStatusBadgeClass,
                syncStatusDotClass,
                filterPanelClass,
                unitOptions,
                calendarDays,
                calendarLeadingBlanks,
                prevDay,
                nextDay,
                prevMonth,
                nextMonth,
                setCalendarMonth,
                setCalendarYear,
                goToday,
                setDate,
                selectDay,
                statusLabel,
                statusPillClass,
                dateStripCountBadgeClass,
                agendaCardClass,
                canWatchStream,
                streamButtonLabel,
                streamButtonClass,
                streamButtonHint,
                calendarDayClass,
                dateStripClass,
                dateStripMetaClass,
                isActiveDay,
                isTodayInMonth,
                agendaCount,
                resetFilters,
                toggleTheme,
            };
        }
    }).mount('#app');
</script>

</body>
</html>
