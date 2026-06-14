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

    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <script>
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
        <div class="mx-auto flex min-h-[88px] w-[min(960px,calc(100%-28px))] items-center justify-between gap-3 px-1 py-3">
            <a class="flex min-w-0 items-center gap-4" href="<?= base_url('jadwal') ?>" aria-label="Agenda rapat DPRD">
                <img class="h-16 w-16 shrink-0 rounded-2xl border border-base-300 bg-white object-contain" src="<?= esc($logoUrl) ?>" alt="Logo DPRD" />
                <span class="min-w-0">
                    <span class="block truncate text-lg font-black leading-tight text-base-content sm:text-xl">Agenda Rapat DPRD</span>
                    <span class="hidden truncate text-xs font-bold text-base-content/65 sm:text-sm sm:block mt-0.5">Provinsi Sulawesi Tengah</span>
                </span>
            </a>

            <div class="flex items-center gap-2.5">
                <button
                    class="btn btn-ghost btn-circle text-base-content/80 hover:text-primary"
                    type="button"
                    @click="toggleTheme"
                    :aria-label="isDark ? 'Gunakan mode terang' : 'Gunakan mode gelap'"
                >
                    <svg v-if="!isDark" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v2M12 19v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M3 12h2M19 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/></svg>
                    <svg v-else viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 15.5A8.5 8.5 0 0 1 8.5 4a7 7 0 1 0 11.5 11.5Z"/></svg>
                </button>
                <div class="badge badge-success gap-1.5 px-3 py-1 font-bold">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-success-content"></span>
                    Live
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto w-[min(960px,calc(100%-28px))] py-4">
        <section class="card border border-base-300 bg-base-100 shadow-sm rounded-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-base-200 p-4">
                <div class="min-w-0">
                    <p class="text-[10px] font-extrabold uppercase tracking-[.08em] text-primary">Tanggal aktif</p>
                    <h1 class="mt-1 text-xl font-black leading-tight text-base-content sm:text-2xl">{{ selectedDateFull }}</h1>
                </div>

                <div class="join">
                    <button class="btn btn-sm btn-outline join-item" type="button" @click="prevDay" aria-label="Hari sebelumnya">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button class="btn btn-sm btn-outline join-item font-extrabold" type="button" @click="goToday">Hari ini</button>
                    <button class="btn btn-sm btn-outline join-item" type="button" @click="nextDay" aria-label="Hari berikutnya">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-7 gap-1.5 border-b border-base-200 p-3 sm:gap-2">
                <button
                    v-for="day in dateStrip"
                    :key="day.key"
                    :class="dateStripClass(day)"
                    type="button"
                    @click="setDate(day.date)"
                >
                    <span class="text-[9px] font-extrabold uppercase tracking-[.04em] opacity-80">{{ day.day }}</span>
                    <span class="text-base font-black leading-none mt-0.5">{{ day.date.getDate() }}</span>
                    <span v-if="day.count" :class="['absolute right-1 top-1 badge badge-xs font-extrabold', day.key === selectedDateKey.value ? 'badge-accent' : 'badge-neutral']">{{ day.count }}</span>
                </button>
            </div>

            <div class="grid gap-3 p-4 md:grid-cols-[minmax(0,1fr)_190px_150px_auto] border-b border-base-200">
                <input class="input input-bordered w-full" v-model.trim="query" type="search" placeholder="Cari agenda..." autocomplete="off" />

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

                <button class="btn btn-outline" type="button" @click="showCalendar = !showCalendar">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ showCalendar ? 'Tutup' : 'Kalender' }}
                </button>
            </div>

            <div v-if="showCalendar" class="border-t border-base-200 p-4">
                <div class="mb-3 flex items-center justify-between gap-3 p-1">
                    <h2 class="text-sm font-bold text-base-content">{{ monthTitle }}</h2>
                    <div class="join">
                        <button class="btn btn-sm btn-outline join-item" type="button" @click="prevMonth" aria-label="Bulan sebelumnya">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <button class="btn btn-sm btn-outline join-item" type="button" @click="nextMonth" aria-label="Bulan berikutnya">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
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
        </section>

        <section class="mt-4">
            <div class="mb-2.5 flex flex-col gap-1 px-1 text-xs font-bold text-base-content/60 sm:flex-row sm:items-center sm:justify-between">
                <span v-if="loading">Memuat agenda...</span>
                <span v-else>{{ filteredAgendas.length }} agenda</span>
                <span v-if="lastRefresh">Update {{ lastRefresh }}</span>
            </div>

            <div v-if="loading" class="grid gap-3">
                <div class="skeleton h-28 w-full rounded-xl border border-base-300" v-for="n in 3" :key="'sk-' + n"></div>
            </div>

            <div v-else-if="filteredAgendas.length === 0" class="flex flex-col items-center justify-center min-h-[180px] rounded-xl border border-dashed border-base-300 bg-base-100 p-6 text-center text-base-content/60 gap-1.5">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" class="opacity-45"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <p class="font-semibold text-sm">Tidak ada agenda pada tanggal ini.</p>
            </div>

            <div v-else class="grid gap-3">
                <article
                    v-for="item in filteredAgendas"
                    :key="item.id"
                    :id="'agenda-' + item.id"
                    :class="agendaCardClass(item)"
                >
                    <div class="card-body p-4 sm:p-5">
                        <div class="grid gap-3 sm:grid-cols-[90px_minmax(0,1fr)_auto] sm:items-start">
                            <div class="rounded-lg border border-base-300 bg-base-200/50 p-2.5 text-center shrink-0 min-w-[90px]">
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

                                <h2 class="text-base font-extrabold leading-snug text-base-content">{{ item.judul }}</h2>

                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm leading-5 text-base-content/75">
                                    <span>{{ item.ruangan }}</span>
                                    <span v-if="item.komisi">{{ item.komisi }}</span>
                                </div>

                                <p v-if="item.keterangan" class="mt-2 line-clamp-2 text-sm leading-5 text-base-content/65">{{ item.keterangan }}</p>
                            </div>

                            <div class="flex sm:flex-col gap-2 sm:min-w-[112px]">
                                <a
                                    v-if="item.has_stream"
                                    class="btn btn-sm btn-error w-full font-extrabold"
                                    :href="item.stream_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="mr-1"><path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.75 15.02l5.75-3.27-5.75-3.27v6.54z" fill="currentColor"/></svg>
                                    Live
                                </a>
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
                                <button class="btn btn-sm btn-outline w-full font-extrabold" type="button" @click="copyLink(item)">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="mr-1"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2" stroke-linecap="round" stroke-linejoin="round"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 14h6m-6-4h6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Salin
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <footer class="border-t border-base-300 py-6 text-xs text-base-content/50 bg-base-100/40">
        <div class="mx-auto flex w-[min(960px,calc(100%-28px))] flex-col gap-1.5 px-1 sm:flex-row sm:justify-between">
            <span>DPRD Provinsi Sulawesi Tengah &copy; <?= date('Y') ?></span>
            <span>Auto-refresh 60 detik</span>
        </div>
    </footer>

    <div
        :class="[
            'pointer-events-none fixed bottom-5 right-5 z-[80] transition-all duration-200',
            toastVisible ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'
        ]"
        role="status"
        aria-live="polite"
    >
        <div class="alert alert-success shadow-lg py-2.5 px-4 flex items-center gap-2">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" class="text-success-content"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="text-xs font-extrabold text-success-content">Link agenda disalin.</span>
        </div>
    </div>
</div>

<script>
    const { createApp, ref, computed, watch, onMounted, onUnmounted } = Vue;

    createApp({
        setup() {
            const today = new Date();
            const activeDate = ref(new Date(today));
            const jadwal = ref([]);
            const loading = ref(true);
            const lastRefresh = ref('');
            const query = ref('');
            const unitFilter = ref('all');
            const statusFilter = ref('all');
            const showCalendar = ref(false);
            const toastVisible = ref(false);
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

            const dayAgendas = computed(() => jadwal.value.filter((item) => item.tanggal === selectedDateKey.value));
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
                const base = 'relative flex flex-col items-center justify-center p-2 rounded-xl border text-center transition cursor-pointer min-h-[58px]';
                return active
                    ? `${base} border-primary bg-primary text-primary-content font-bold shadow-sm`
                    : `${base} border-base-300 bg-base-100 text-base-content/85 hover:border-primary hover:text-primary`;
            }

            function toggleTheme() {
                isDark.value = !isDark.value;
                localStorage.setItem('public_schedule_theme', isDark.value ? 'dark' : 'light');
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

            function loadData() {
                loading.value = true;
                fetch(`${API_URL}?month=${monthKey.value}`)
                    .then((response) => response.json())
                    .then((payload) => {
                        jadwal.value = payload.data || [];
                        lastRefresh.value = currentTime();
                    })
                    .catch((error) => console.error('[Publik] Gagal ambil data:', error))
                    .finally(() => {
                        loading.value = false;
                    });
            }

            async function copyLink(item) {
                const link = `${window.location.origin}${window.location.pathname}?date=${item.tanggal}#agenda-${item.id}`;
                try {
                    await navigator.clipboard.writeText(link);
                } catch (error) {
                    window.prompt('Salin link agenda:', link);
                }

                toastVisible.value = true;
                window.setTimeout(() => {
                    toastVisible.value = false;
                }, 1800);
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
                loading,
                lastRefresh,
                query,
                unitFilter,
                statusFilter,
                showCalendar,
                toastVisible,
                isDark,
                weekdays,
                dateStrip,
                selectedDateKey,
                selectedDateFull,
                monthTitle,
                filteredAgendas,
                unitOptions,
                calendarDays,
                calendarLeadingBlanks,
                prevDay,
                nextDay,
                prevMonth,
                nextMonth,
                goToday,
                setDate,
                selectDay,
                statusLabel,
                statusPillClass,
                agendaCardClass,
                calendarDayClass,
                dateStripClass,
                isActiveDay,
                isTodayInMonth,
                agendaCount,
                copyLink,
                toggleTheme,
            };
        }
    }).mount('#app');
</script>

</body>
</html>
