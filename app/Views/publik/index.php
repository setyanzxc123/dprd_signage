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

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="<?= base_url('assets/css/publik.css') ?>" rel="stylesheet" />
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">

<div id="app" v-cloak>
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur-xl">
        <div class="mx-auto flex min-h-[58px] w-[min(960px,calc(100%-28px))] items-center justify-between gap-3">
            <a class="flex min-w-0 items-center gap-3" href="<?= base_url('jadwal') ?>" aria-label="Agenda rapat DPRD">
                <img class="h-9 w-9 shrink-0 rounded-md border border-slate-200 bg-white object-contain" src="<?= esc($logoUrl) ?>" alt="Logo DPRD" />
                <span class="min-w-0">
                    <span class="block truncate text-sm font-extrabold leading-tight text-slate-950">Agenda Rapat DPRD</span>
                    <span class="hidden truncate text-xs font-semibold text-slate-500 sm:block">Provinsi Sulawesi Tengah</span>
                </span>
            </a>

            <div class="inline-flex min-h-7 items-center gap-2 rounded-full border border-green-700/25 bg-green-50 px-2.5 text-[11px] font-extrabold uppercase tracking-[.06em] text-green-700">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current"></span>
                Live
            </div>
        </div>
    </header>

    <main class="mx-auto w-[min(960px,calc(100%-28px))] py-4">
        <section class="rounded-lg border border-slate-200 bg-white">
            <div class="grid gap-3 border-b border-slate-100 p-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                <div class="min-w-0">
                    <p class="text-[10px] font-extrabold uppercase tracking-[.08em] text-blue-700">Tanggal aktif</p>
                    <h1 class="mt-1 text-xl font-black leading-tight text-slate-950 sm:text-2xl">{{ selectedDateFull }}</h1>
                </div>

                <div class="flex items-center gap-2">
                    <button class="grid h-9 w-9 place-items-center rounded-md border border-slate-200 bg-white text-slate-700 hover:border-blue-700 hover:text-blue-700" type="button" @click="prevDay" aria-label="Hari sebelumnya">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="m15 18-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button class="inline-flex h-9 items-center justify-center rounded-md border border-blue-700 bg-blue-700 px-3 text-sm font-extrabold text-white hover:bg-blue-800" type="button" @click="goToday">Hari ini</button>
                    <button class="grid h-9 w-9 place-items-center rounded-md border border-slate-200 bg-white text-slate-700 hover:border-blue-700 hover:text-blue-700" type="button" @click="nextDay" aria-label="Hari berikutnya">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="m9 18 6-6-6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-7 gap-1 border-b border-slate-100 p-2 sm:gap-2 sm:p-3">
                <button
                    v-for="day in dateStrip"
                    :key="day.key"
                    :class="dateStripClass(day)"
                    type="button"
                    @click="setDate(day.date)"
                >
                    <span class="text-[10px] font-extrabold uppercase tracking-[.04em]">{{ day.day }}</span>
                    <span class="text-base font-black leading-none">{{ day.date.getDate() }}</span>
                    <span v-if="day.count" class="absolute right-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-white/85 px-1 text-[10px] text-blue-700">{{ day.count }}</span>
                </button>
            </div>

            <div class="grid gap-2 p-3 md:grid-cols-[minmax(0,1fr)_190px_150px_auto]">
                <input class="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm outline-none focus:border-blue-700 focus:ring-4 focus:ring-blue-600/15" v-model.trim="query" type="search" placeholder="Cari agenda..." autocomplete="off" />

                <select class="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm outline-none focus:border-blue-700 focus:ring-4 focus:ring-blue-600/15" v-model="unitFilter">
                    <option value="all">Semua unit</option>
                    <option v-for="unit in unitOptions" :key="unit" :value="unit">{{ unit }}</option>
                </select>

                <select class="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm outline-none focus:border-blue-700 focus:ring-4 focus:ring-blue-600/15" v-model="statusFilter">
                    <option value="all">Semua status</option>
                    <option value="berlangsung">Berlangsung</option>
                    <option value="persiapan">Persiapan</option>
                    <option value="menunggu">Menunggu</option>
                    <option value="selesai">Selesai</option>
                </select>

                <button class="inline-flex h-9 items-center justify-center rounded-md border border-slate-200 bg-white px-3 text-sm font-extrabold text-slate-700 hover:border-blue-700 hover:text-blue-700" type="button" @click="showCalendar = !showCalendar">
                    {{ showCalendar ? 'Tutup' : 'Tanggal' }}
                </button>
            </div>

            <div v-if="showCalendar" class="border-t border-slate-100 p-3">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-extrabold text-slate-950">{{ monthTitle }}</h2>
                    <div class="flex gap-2">
                        <button class="grid h-8 w-8 place-items-center rounded-md border border-slate-200 bg-white text-slate-700 hover:border-blue-700 hover:text-blue-700" type="button" @click="prevMonth" aria-label="Bulan sebelumnya">
                            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="m15 18-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button class="grid h-8 w-8 place-items-center rounded-md border border-slate-200 bg-white text-slate-700 hover:border-blue-700 hover:text-blue-700" type="button" @click="nextMonth" aria-label="Bulan berikutnya">
                            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="m9 18 6-6-6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-7 gap-1">
                    <span v-for="weekday in weekdays" :key="weekday" class="grid h-7 place-items-center text-[10px] font-extrabold uppercase text-slate-500">{{ weekday }}</span>
                    <span v-for="blank in calendarLeadingBlanks" :key="'blank-' + blank" class="h-8"></span>
                    <button
                        v-for="day in calendarDays"
                        :key="day"
                        :class="calendarDayClass(day)"
                        type="button"
                        @click="selectDay(day)"
                    >
                        <span>{{ day }}</span>
                        <small v-if="agendaCount(day)" class="absolute right-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-white/85 px-1 text-[10px] text-blue-700">{{ agendaCount(day) }}</small>
                    </button>
                </div>
            </div>
        </section>

        <section class="mt-3">
            <div class="mb-2 flex flex-col gap-1 px-1 text-xs font-bold text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <span v-if="loading">Memuat agenda...</span>
                <span v-else>{{ filteredAgendas.length }} agenda</span>
                <span v-if="lastRefresh">Update {{ lastRefresh }}</span>
            </div>

            <div v-if="loading" class="grid gap-2">
                <div class="h-[112px] animate-pulse rounded-lg border border-slate-200 bg-white" v-for="n in 3" :key="'sk-' + n"></div>
            </div>

            <div v-else-if="filteredAgendas.length === 0" class="grid min-h-[180px] place-items-center rounded-lg border border-dashed border-slate-200 bg-white p-6 text-center text-sm text-slate-500">
                <p>Tidak ada agenda pada tanggal ini.</p>
            </div>

            <div v-else class="grid gap-2">
                <article
                    v-for="item in filteredAgendas"
                    :key="item.id"
                    :id="'agenda-' + item.id"
                    :class="agendaCardClass(item)"
                >
                    <div class="grid gap-2 sm:grid-cols-[86px_minmax(0,1fr)_auto] sm:items-start">
                        <div class="rounded-md border border-slate-100 bg-slate-50 p-2 text-center">
                            <div class="text-lg font-black leading-none text-slate-950">{{ item.waktu_mulai }}</div>
                            <div class="mt-1 text-[10px] font-extrabold uppercase tracking-[.06em] text-slate-500">{{ item.waktu_selesai }} WITA</div>
                        </div>

                        <div class="min-w-0">
                            <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                <span :class="statusPillClass(item.status)">
                                    <span :class="['h-1.5 w-1.5 rounded-full bg-current', item.status === 'berlangsung' ? 'animate-pulse' : '']"></span>
                                    {{ statusLabel(item.status) }}
                                </span>
                                <span v-if="item.jenis" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[.05em] text-slate-500">{{ item.jenis }}</span>
                            </div>

                            <h2 class="text-base font-extrabold leading-snug text-slate-950">{{ item.judul }}</h2>

                            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-sm leading-5 text-slate-700">
                                <span>{{ item.ruangan }}</span>
                                <span v-if="item.komisi">{{ item.komisi }}</span>
                            </div>

                            <p v-if="item.keterangan" class="mt-2 line-clamp-2 text-sm leading-5 text-slate-500">{{ item.keterangan }}</p>
                        </div>

                        <div class="grid gap-2 sm:min-w-[112px]">
                            <a
                                v-if="item.has_stream"
                                class="inline-flex h-9 items-center justify-center rounded-md border border-red-700/25 bg-red-50 px-3 text-sm font-extrabold text-red-700 hover:bg-red-100"
                                :href="item.stream_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >Live</a>
                            <a
                                v-if="item.has_materi"
                                class="inline-flex h-9 items-center justify-center rounded-md border border-slate-200 bg-white px-3 text-sm font-extrabold text-slate-700 hover:border-blue-700 hover:text-blue-700"
                                :href="item.materi_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >Berkas</a>
                            <button class="inline-flex h-9 items-center justify-center rounded-md border border-slate-200 bg-white px-3 text-sm font-extrabold text-slate-700 hover:border-blue-700 hover:text-blue-700" type="button" @click="copyLink(item)">Salin</button>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 py-4 text-xs text-slate-500">
        <div class="mx-auto flex w-[min(960px,calc(100%-28px))] flex-col gap-1 sm:flex-row sm:justify-between">
            <span>DPRD Provinsi Sulawesi Tengah &copy; <?= date('Y') ?></span>
            <span>Auto-refresh 60 detik</span>
        </div>
    </footer>

    <div
        :class="[
            'pointer-events-none fixed bottom-5 right-5 z-[80] rounded-md bg-slate-950 px-4 py-3 text-sm font-extrabold text-white shadow-lg transition duration-200',
            toastVisible ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'
        ]"
        role="status"
        aria-live="polite"
    >Link agenda disalin.</div>
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
                const base = 'inline-flex w-fit items-center gap-1.5 rounded-full border px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[.05em]';
                return {
                    berlangsung: `${base} border-green-700/25 bg-green-50 text-green-700`,
                    persiapan: `${base} border-amber-700/25 bg-amber-50 text-amber-700`,
                    menunggu: `${base} border-slate-300 bg-slate-100 text-slate-600`,
                    selesai: `${base} border-blue-700/20 bg-blue-50 text-blue-700`,
                }[status] || `${base} border-slate-300 bg-slate-100 text-slate-600`;
            }

            function agendaCardClass(item) {
                const statusBorder = {
                    berlangsung: 'border-green-700/35 bg-green-50/40',
                    persiapan: 'border-amber-700/35 bg-amber-50/40',
                    selesai: 'border-slate-200 bg-white opacity-80',
                }[item.status] || 'border-slate-200 bg-white';
                return `rounded-lg border p-3 ${statusBorder}`;
            }

            function calendarDayClass(day) {
                const base = 'relative grid h-8 place-items-center rounded-md border text-sm font-extrabold';
                if (isActiveDay(day)) {
                    return `${base} border-blue-700 bg-blue-700 text-white`;
                }
                if (agendaDaySet.value.has(day)) {
                    return `${base} border-blue-700/30 bg-blue-50 text-blue-700 hover:border-blue-700`;
                }
                if (isTodayInMonth(day)) {
                    return `${base} border-slate-200 bg-white text-slate-700 ring-2 ring-blue-600/15 hover:border-blue-700 hover:text-blue-700`;
                }
                return `${base} border-slate-100 bg-white text-slate-700 hover:border-blue-700 hover:text-blue-700`;
            }

            function dateStripClass(day) {
                const active = day.key === selectedDateKey.value;
                const base = 'relative grid min-h-[58px] place-items-center rounded-md border px-1 text-center';
                return active
                    ? `${base} border-blue-700 bg-blue-700 text-white`
                    : `${base} border-slate-200 bg-white text-slate-600 hover:border-blue-700 hover:text-blue-700`;
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

            function goToday() {
                setDate(new Date(today));
            }

            function isActiveDay(day) {
                return activeDate.value.getDate() === day;
            }

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
                weekdays,
                dateStrip,
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
            };
        }
    }).mount('#app');
</script>

</body>
</html>
