<?php
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$vueVersion = is_file(FCPATH . 'assets/vendor/vue/vue.global.prod.js') ? filemtime(FCPATH . 'assets/vendor/vue/vue.global.prod.js') : time();
$cssVersion = is_file(FCPATH . 'assets/css/agenda.css') ? filemtime(FCPATH . 'assets/css/agenda.css') : time();
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
    <link rel="icon" type="image/jpeg" href="<?= base_url('assets/images/logo_dprd.jpg') ?>" />
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
<body class="min-h-screen overflow-x-hidden bg-base-200 text-base-content antialiased">
<div id="agenda-app" v-cloak>
    <header class="sticky top-0 z-50 border-b border-base-300 bg-base-100/95 backdrop-blur-xl">
        <div class="navbar min-h-20 w-full gap-2 px-[2.5vw] py-[0.8vh]">
            <a class="navbar-start min-w-0 flex-1 gap-[1vw]" href="<?= esc($portalUrl) ?>" aria-label="Halaman agenda DPRD">
                <img class="h-11 w-11 shrink-0 rounded-box object-contain sm:h-14 sm:w-14" src="<?= esc($logoUrl) ?>" alt="Logo DPRD Provinsi Sulawesi Tengah" />
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
                            <img v-if="weather.icon_url" :src="weather.icon_url" class="h-7 w-7 object-contain" alt="Ikon cuaca" />
                            <span v-else class="status status-info status-lg"></span>
                            <span>{{ weather.suhu }}</span>
                        </div>
                        <div class="stat-desc max-w-28 truncate text-xs font-semibold">{{ weather.kondisi }}</div>
                    </div>

                    <div class="stat hidden px-3 py-2 2xl:inline-grid">
                        <div class="stat-title max-w-48 truncate text-xs font-bold">{{ weatherLocation }}</div>
                        <div class="stat-value mt-0.5 text-xs font-medium">
                            Kelembapan {{ weather.kelembapan }} · Angin {{ weather.kec_angin }}
                        </div>
                        <div class="stat-desc text-[10px] italic">Sumber: BMKG</div>
                    </div>

                    <div class="stat place-items-center px-4 py-2 text-center">
                        <div class="stat-title text-xs font-bold uppercase tracking-[0.12em]">{{ headerDay }}</div>
                        <div class="stat-value text-sm">{{ headerDate }}</div>
                    </div>

                    <div class="stat place-items-center px-4 py-2 text-center">
                        <div class="stat-value font-mono text-3xl tabular-nums leading-none">{{ headerTime }}</div>
                        <div class="stat-desc mt-0.5 uppercase tracking-[0.18em]">WITA</div>
                    </div>
                </div>

                <button class="btn btn-ghost btn-circle btn-sm" type="button" @click="toggleTheme" :aria-label="isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap'">
                    <svg v-if="!isDark" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 15.5A8.5 8.5 0 0 1 8.5 4a7 7 0 1 0 11.5 11.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <svg v-else viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v2m0 14v2M4.2 4.2l1.4 1.4m12.8 12.8 1.4 1.4M3 12h2m14 0h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" stroke-linecap="round"/></svg>
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
                <?php elseif ($isAdmin): ?>
                    <a class="btn btn-outline btn-sm" href="<?= base_url('admin/dashboard') ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        <span class="hidden sm:inline">Panel Admin</span>
                    </a>
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
                <span class="mt-0.5 block truncate text-xs font-black">{{ weatherLabel }}</span>
            </div>
            <div class="min-w-0 border-x border-base-300 p-2 text-center">
                <span class="block truncate text-[9px] font-extrabold uppercase tracking-wider text-base-content/45">{{ headerDay }}</span>
                <span class="mt-0.5 block truncate text-xs font-black">{{ headerDate }}</span>
            </div>
            <div class="min-w-0 p-2 text-center">
                <span class="block text-[9px] font-extrabold uppercase tracking-wider text-base-content/45">Jam</span>
                <span class="mt-0.5 block truncate text-xs font-black tabular-nums">{{ headerTime }} WITA</span>
            </div>
        </div>

        <nav class="border-t border-base-300 bg-base-200" aria-label="Navigasi agenda">
            <div class="mx-auto flex w-[min(1180px,calc(100%-20px))] flex-col gap-2 py-2 sm:w-[min(1180px,calc(100%-32px))] sm:flex-row sm:items-center">
                <div class="grid min-w-0 flex-1 grid-cols-[2rem_minmax(0,1fr)_2rem] items-center gap-2">
                    <button
                        :class="{ 'invisible pointer-events-none': !canScrollUnitsLeft }"
                        class="btn btn-outline btn-circle btn-sm bg-base-100 shadow-md"
                        type="button"
                        :aria-hidden="!canScrollUnitsLeft"
                        :tabindex="canScrollUnitsLeft ? 0 : -1"
                        aria-label="Geser kelompok peserta ke kiri"
                        title="Geser ke kiri"
                        @click="scrollUnitFilters(-1)">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div
                        ref="unitScroller"
                        class="agenda-unit-scroll carousel carousel-start w-full gap-2 px-1"
                        @scroll.passive="updateUnitScrollState">
                        <div class="carousel-item">
                            <button :class="navButtonClass('all')" type="button" @click="setNavigation('all')">Semua</button>
                        </div>
                        <div v-for="unit in units" :key="unit.id" class="carousel-item">
                            <button :class="navButtonClass('unit:' + unit.id)" type="button" @click="setNavigation('unit:' + unit.id)">{{ compactUnitName(unit.nama) }}</button>
                        </div>
                    </div>

                    <button
                        :class="{ 'invisible pointer-events-none': !canScrollUnitsRight }"
                        class="btn btn-outline btn-circle btn-sm bg-base-100 shadow-md"
                        type="button"
                        :aria-hidden="!canScrollUnitsRight"
                        :tabindex="canScrollUnitsRight ? 0 : -1"
                        aria-label="Geser kelompok peserta ke kanan"
                        title="Geser ke kanan"
                        @click="scrollUnitFilters(1)">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <a class="btn btn-outline btn-sm w-full shrink-0 sm:w-auto" href="<?= base_url('agenda/jadwal-banmus') ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round"/></svg>
                    Proyeksi Banmus
                </a>
            </div>
        </nav>

        <?php if ($isMember): ?>
            <div class="border-t border-base-300 bg-base-100" aria-label="Cakupan agenda anggota">
                <div class="mx-auto flex w-[min(1180px,calc(100%-20px))] flex-col gap-2 py-3 sm:w-[min(1180px,calc(100%-32px))] sm:flex-row sm:items-center sm:justify-between">
                    <div class="join" role="group" aria-label="Pilih cakupan agenda">
                        <button type="button" class="join-item btn btn-sm" :class="scopeButtonClass('saya')" :aria-pressed="memberScope === 'saya'" @click="setMemberScope('saya')">Jadwal Saya</button>
                        <button type="button" class="join-item btn btn-sm" :class="scopeButtonClass('semua')" :aria-pressed="memberScope === 'semua'" @click="setMemberScope('semua')">Semua Jadwal</button>
                    </div>
                    <p class="flex items-center gap-2 text-xs font-semibold text-base-content/60">
                        <span class="badge badge-info badge-soft badge-sm">Akses Anggota</span>
                        Anda dapat melihat agenda dan sumber daya internal sesuai kewenangan.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </header>

    <main class="mx-auto grid w-[min(1480px,calc(100%-20px))] gap-4 py-4 sm:w-[min(1480px,calc(100%-32px))] sm:py-6 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] xl:items-start">
        <section class="card card-border bg-base-100 shadow-sm">
            <div class="card-body gap-0 p-0">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-6">
                    <h1 class="text-2xl font-black uppercase tracking-tight">Agenda Rapat</h1>
                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                        <select class="select select-sm w-full font-bold sm:w-44" v-model="periodMode" @change="changePeriod" aria-label="Filter periode agenda rapat">
                            <option value="month">Bulan ini</option>
                            <option value="quarter">Triwulan ini</option>
                            <option value="semester">Semester ini</option>
                        </select>
                        <button class="btn btn-outline btn-sm" type="button" @click="loadAgenda" :disabled="refreshing">
                            <span v-if="refreshing" class="loading loading-spinner loading-xs"></span>
                            <svg v-else viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.34-5.66M20 4v6h-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Perbarui
                        </button>
                    </div>
                </div>

                <div v-if="initialLoading" class="grid gap-3 p-4 sm:p-6">
                    <div v-for="item in 3" :key="item" class="skeleton h-20 w-full"></div>
                </div>

                <div v-else-if="loadError" class="p-4 sm:p-6">
                    <div role="alert" class="alert alert-error alert-soft">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3h.01" stroke-linecap="round"/></svg>
                        <span>Agenda gagal dimuat. Silakan coba perbarui kembali.</span>
                    </div>
                </div>

                <div v-else-if="filteredAgendas.length === 0" class="grid min-h-80 place-items-center p-8 text-center">
                    <div>
                        <svg class="mx-auto text-base-content/30" viewBox="0 0 24 24" width="42" height="42" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round"/></svg>
                        <h2 class="mt-4 text-lg font-extrabold">Belum ada agenda</h2>
                        <p class="mt-1 text-sm font-semibold text-base-content/55">Tidak ada jadwal untuk kelompok dan periode yang dipilih.</p>
                    </div>
                </div>

                <div v-else class="p-4 sm:p-6">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm font-semibold text-base-content/55">Menampilkan {{ pageStart }}–{{ pageEnd }} dari {{ orderedAgendas.length }} agenda</p>
                        <label class="flex items-center gap-2 text-sm font-bold">
                            Tampilkan
                            <select class="select select-sm w-20" v-model.number="pageSize" @change="changePageSize" aria-label="Jumlah agenda per halaman">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </label>
                    </div>

                    <div class="grid gap-2">
                        <details
                            v-for="item in paginatedAgendas"
                            :key="item.key"
                            class="collapse collapse-arrow border border-base-300 bg-base-100"
                            :class="{ 'outline outline-2 outline-base-content': expandedAgendaKey === item.key }"
                            :open="expandedAgendaKey === item.key"
                            @toggle="handleAgendaToggle($event, item.key)"
                        >
                            <summary class="collapse-title grid min-h-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 py-3 pr-12">
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-box bg-base-200 text-center">
                                    <span v-if="item.tanggal">
                                        <span class="block text-[10px] font-extrabold uppercase text-base-content/45">{{ shortMonth(item.tanggal) }}</span>
                                        <strong class="block text-lg leading-none">{{ dayNumber(item.tanggal) }}</strong>
                                    </span>
                                    <span v-else>
                                        <span class="block text-[10px] font-extrabold uppercase text-base-content/45">SK</span>
                                        <strong class="block text-sm leading-none">{{ item.document_year }}</strong>
                                    </span>
                                </span>
                                <span class="min-w-0">
                                    <span class="line-clamp-2 text-sm font-extrabold leading-5 sm:text-base">{{ item.judul }}</span>
                                    <span class="mt-1 flex min-w-0 flex-wrap items-center gap-2">
                                        <span v-if="item.source === 'banmus' || item.source === 'banmus_projection'" class="badge badge-info badge-soft badge-sm">
                                            Banmus
                                        </span>
                                        <span v-if="item.source === 'jadwal_umum'" class="badge badge-secondary badge-soft badge-sm">
                                            Jadwal Umum
                                        </span>
                                        <?php if ($isMember): ?>
                                            <span
                                                v-if="!item.is_public"
                                                class="tooltip tooltip-bottom cursor-help"
                                                data-tip="Agenda ini hanya terlihat oleh anggota DPRD yang masuk dan tidak tampil pada akses publik."
                                                aria-label="Internal DPRD. Agenda ini hanya terlihat oleh anggota DPRD yang masuk dan tidak tampil pada akses publik."
                                            >
                                                <span class="badge badge-ghost badge-sm">Internal DPRD</span>
                                            </span>
                                            <span v-if="item.is_participant" class="badge badge-primary badge-soft badge-sm">Anda Peserta</span>
                                        <?php endif; ?>
                                        <span v-if="item.status === 'proyeksi'" class="truncate text-xs font-semibold text-base-content/50">
                                            {{ item.periode_label || 'Periode belum ditentukan' }}
                                        </span>
                                        <span v-else class="truncate text-xs font-semibold text-base-content/50">{{ executionTime(item) }} · {{ item.ruangan || '-' }}</span>
                                        <span :class="statusBadgeClass(item.status)" class="shrink-0 sm:hidden">{{ statusLabel(item.status) }}</span>
                                    </span>
                                </span>
                                <span :class="statusBadgeClass(item.status)" class="hidden sm:inline-flex">{{ statusLabel(item.status) }}</span>
                            </summary>

                            <div class="collapse-content border-t border-base-300">
                                <p v-if="item.keterangan" class="pt-4 text-sm font-medium leading-6 text-base-content/60">{{ item.keterangan }}</p>

                                <dl v-if="item.status === 'proyeksi'" class="mt-4 grid gap-3 rounded-box bg-base-200 p-4 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Periode proyeksi</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ item.periode_label || 'Belum ditentukan' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Dokumen Banmus</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ item.document_title }}</dd>
                                        <dd class="mt-0.5 text-xs font-semibold text-base-content/55">Nomor SK: {{ item.document_number }}</dd>
                                    </div>
                                </dl>

                                <dl v-else class="mt-4 grid gap-3 rounded-box bg-base-200 p-4 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Tanggal dan waktu</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ fullDate(item.tanggal) }} · {{ executionTime(item) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Lokasi</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ item.ruangan || '-' }}</dd>
                                    </div>
                                    <div v-if="item.komisi" class="sm:col-span-2">
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Unit rapat</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ item.komisi }}</dd>
                                    </div>
                                    <div v-if="item.pihak_eksternal" class="sm:col-span-2">
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Pihak eksternal</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ item.pihak_eksternal }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <a v-if="item.status === 'proyeksi'" class="btn btn-outline btn-sm" :href="item.projection_url">
                                        Lihat Proyeksi &amp; SK
                                    </a>
                                    <template v-else>
                                        <a v-if="item.has_materi" class="btn btn-outline btn-sm" :href="item.materi_url" target="_blank" rel="noopener noreferrer">
                                            Bahan Rapat
                                            <span v-if="item.materi_access_label" class="badge badge-ghost badge-xs">{{ item.materi_access_label }}</span>
                                        </a>
                                        <a v-if="item.has_stream" class="btn btn-outline btn-sm" :href="item.stream_url" target="_blank" rel="noopener noreferrer">
                                            Live / Video
                                            <span v-if="item.stream_access_label" class="badge badge-ghost badge-xs">{{ item.stream_access_label }}</span>
                                        </a>
                                        <?php if ($isMember): ?>
                                            <span v-if="item.materi_restricted" class="badge badge-warning badge-soft badge-sm">Bahan khusus peserta</span>
                                            <span v-if="item.stream_restricted" class="badge badge-warning badge-soft badge-sm">Live/video khusus peserta</span>
                                        <?php endif; ?>
                                        <span v-if="!item.has_materi && !item.has_stream && !item.materi_restricted && !item.stream_restricted" class="text-xs font-semibold text-base-content/45">Belum ada bahan atau tautan video.</span>
                                    </template>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div v-if="totalPages > 1" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <span class="text-xs font-semibold text-base-content/50">Halaman {{ currentPage }} dari {{ totalPages }}</span>
                        <div class="join">
                            <button class="btn btn-sm join-item" type="button" @click="goToPage(currentPage - 1)" :disabled="currentPage === 1">Sebelumnya</button>
                            <button class="btn btn-sm btn-neutral join-item pointer-events-none" type="button" aria-current="page">{{ currentPage }}</button>
                            <button class="btn btn-sm join-item" type="button" @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages">Berikutnya</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card card-border bg-base-100 shadow-sm">
            <div class="card-body gap-0 p-0">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-6">
                    <h1 class="text-2xl font-black uppercase tracking-tight">Jadwal Umum</h1>
                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                        <select class="select select-sm w-full font-bold sm:w-44" v-model="generalPeriodMode" @change="changeGeneralPeriod" aria-label="Filter periode jadwal umum">
                            <option value="month">Bulan ini</option>
                            <option value="quarter">Triwulan ini</option>
                            <option value="semester">Semester ini</option>
                        </select>
                        <button class="btn btn-outline btn-sm" type="button" @click="loadAgenda" :disabled="refreshing">
                            <span v-if="refreshing" class="loading loading-spinner loading-xs"></span>
                            <svg v-else viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.34-5.66M20 4v6h-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Perbarui
                        </button>
                    </div>
                </div>

                <div v-if="initialLoading" class="grid gap-3 p-4 sm:p-6">
                    <div v-for="item in 3" :key="'general-skeleton-' + item" class="skeleton h-20 w-full"></div>
                </div>

                <div v-else-if="loadError" class="p-4 sm:p-6">
                    <div role="alert" class="alert alert-error alert-soft">
                        <span>Jadwal umum gagal dimuat. Silakan coba perbarui kembali.</span>
                    </div>
                </div>

                <div v-else-if="filteredGeneralAgendas.length === 0" class="grid min-h-80 place-items-center p-8 text-center">
                    <div>
                        <svg class="mx-auto text-base-content/30" viewBox="0 0 24 24" width="42" height="42" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round"/></svg>
                        <h2 class="mt-4 text-lg font-extrabold">Belum ada jadwal umum</h2>
                        <p class="mt-1 text-sm font-semibold text-base-content/55">Tidak ada jadwal untuk kelompok, cakupan, dan periode yang dipilih.</p>
                    </div>
                </div>

                <div v-else class="p-4 sm:p-6">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm font-semibold text-base-content/55">Menampilkan {{ generalPageStart }}–{{ generalPageEnd }} dari {{ orderedGeneralAgendas.length }} agenda</p>
                        <label class="flex items-center gap-2 text-sm font-bold">
                            Tampilkan
                            <select class="select select-sm w-20" v-model.number="generalPageSize" @change="changeGeneralPageSize" aria-label="Jumlah jadwal umum per halaman">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </label>
                    </div>

                    <div class="grid gap-2">
                        <details
                            v-for="item in paginatedGeneralAgendas"
                            :key="item.key"
                            class="collapse collapse-arrow border border-base-300 bg-base-100"
                            :class="{ 'outline outline-2 outline-base-content': expandedGeneralKey === item.key }"
                            :open="expandedGeneralKey === item.key"
                            @toggle="handleGeneralToggle($event, item.key)"
                        >
                            <summary class="collapse-title grid min-h-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 py-3 pr-12">
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-box bg-base-200 text-center">
                                    <span>
                                        <span class="block text-[10px] font-extrabold uppercase text-base-content/45">{{ shortMonth(item.tanggal) }}</span>
                                        <strong class="block text-lg leading-none">{{ dayNumber(item.tanggal) }}</strong>
                                    </span>
                                </span>
                                <span class="min-w-0">
                                    <span class="line-clamp-2 text-sm font-extrabold leading-5 sm:text-base">{{ item.judul }}</span>
                                    <span class="mt-1 flex min-w-0 flex-wrap items-center gap-2">
                                        <span class="badge badge-secondary badge-soft badge-sm">Jadwal Umum</span>
                                        <?php if ($isMember): ?>
                                            <span v-if="!item.is_public" class="badge badge-ghost badge-sm">Internal DPRD</span>
                                            <span v-if="item.is_participant" class="badge badge-primary badge-soft badge-sm">Anda Peserta</span>
                                        <?php endif; ?>
                                        <span class="truncate text-xs font-semibold text-base-content/50">{{ executionTime(item) }} · {{ item.ruangan || '-' }}</span>
                                        <span :class="statusBadgeClass(item.status)" class="shrink-0 sm:hidden">{{ statusLabel(item.status) }}</span>
                                    </span>
                                </span>
                                <span :class="statusBadgeClass(item.status)" class="hidden sm:inline-flex">{{ statusLabel(item.status) }}</span>
                            </summary>

                            <div class="collapse-content border-t border-base-300">
                                <p v-if="item.keterangan" class="pt-4 text-sm font-medium leading-6 text-base-content/60">{{ item.keterangan }}</p>
                                <dl class="mt-4 grid gap-3 rounded-box bg-base-200 p-4 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Tanggal dan waktu</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ fullDate(item.tanggal) }} · {{ executionTime(item) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Lokasi</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ item.ruangan || '-' }}</dd>
                                    </div>
                                    <div v-if="item.komisi" class="sm:col-span-2">
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Kelompok peserta</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ item.komisi }}</dd>
                                    </div>
                                    <div v-if="item.pihak_eksternal" class="sm:col-span-2">
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Pihak eksternal</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ item.pihak_eksternal }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </details>
                    </div>

                    <div v-if="generalTotalPages > 1" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <span class="text-xs font-semibold text-base-content/50">Halaman {{ currentGeneralPage }} dari {{ generalTotalPages }}</span>
                        <div class="join">
                            <button class="btn btn-sm join-item" type="button" @click="goToGeneralPage(currentGeneralPage - 1)" :disabled="currentGeneralPage === 1">Sebelumnya</button>
                            <button class="btn btn-sm btn-neutral join-item pointer-events-none" type="button" aria-current="page">{{ currentGeneralPage }}</button>
                            <button class="btn btn-sm join-item" type="button" @click="goToGeneralPage(currentGeneralPage + 1)" :disabled="currentGeneralPage === generalTotalPages">Berikutnya</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="border-t border-base-300 bg-base-100 py-5">
        <div class="mx-auto flex w-[min(1180px,calc(100%-20px))] flex-col gap-1 text-xs font-semibold text-base-content/50 sm:w-[min(1180px,calc(100%-32px))] sm:flex-row sm:items-center sm:justify-between">
            <span>DPRD Provinsi Sulawesi Tengah &copy; <?= date('Y') ?></span>
            <span><?= $isMember ? 'Akses anggota' : 'Akses publik' ?> · diperbarui otomatis setiap 60 detik</span>
        </div>
    </footer>
</div>

<script {csp-script-nonce}>
    const { createApp, ref, computed, nextTick, onMounted, onUnmounted } = Vue;

    createApp({
        setup() {
            const API_URL = <?= json_encode($apiUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const WEATHER_URL = <?= json_encode(base_url('api/signage/cuaca'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const LOGIN_URL = <?= json_encode(base_url('login?akses=anggota'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
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
            const units = ref([]);
            const unitScroller = ref(null);
            const canScrollUnitsLeft = ref(false);
            const canScrollUnitsRight = ref(false);
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
            const generalPeriodMode = ref('month');
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
            const headerTime = computed(() => now.value.toLocaleTimeString('id-ID', {
                timeZone: 'Asia/Makassar',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            }).replace('.', ':'));
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
                if (activeNavigation.value.startsWith('unit:')) {
                    const unitId = Number(activeNavigation.value.slice(5));
                    return rows.filter((item) => (item.unit_ids || []).map(Number).includes(unitId));
                }

                return rows;
            });
            const filteredGeneralAgendas = computed(() => {
                const selectedMonths = new Set(periodMonths(generalPeriodMode.value));
                let rows = agendas.value.filter((item) =>
                    item.source === 'jadwal_umum'
                    && selectedMonths.has(String(item.tanggal || '').slice(0, 7)));
                if (activeNavigation.value.startsWith('unit:')) {
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
                    ? 'btn-neutral'
                    : 'btn-ghost';
            }

            function dateKey(date) {
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            }

            function monthKey(date) {
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            }

            function periodMonths(mode = periodMode.value) {
                const current = now.value;
                let firstMonth = current.getMonth();
                let count = 1;

                if (mode === 'quarter') {
                    firstMonth = Math.floor(current.getMonth() / 3) * 3;
                    count = 3;
                } else if (mode === 'semester') {
                    firstMonth = current.getMonth() < 6 ? 0 : 6;
                    count = 6;
                }

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
                        ...periodMonths(periodMode.value),
                        ...periodMonths(generalPeriodMode.value),
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

            function setNavigation(value) {
                activeNavigation.value = value;
                resetAgendaSelection();
                resetGeneralSelection();
                updateUrl();
            }

            function updateUnitScrollState() {
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

            function changePeriod() {
                resetAgendaSelection();
                updateUrl();
                loadAgenda();
            }

            function changeGeneralPeriod() {
                resetGeneralSelection();
                updateUrl();
                loadAgenda();
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
                setOptionalParam(url, 'periode', periodMode.value, 'month');
                setOptionalParam(url, 'periode_umum', generalPeriodMode.value, 'month');
                setOptionalParam(url, 'tampil', String(pageSize.value), '10');
                setOptionalParam(url, 'tampil_umum', String(generalPageSize.value), '10');
                setOptionalParam(url, 'halaman', String(currentPage.value), '1');
                setOptionalParam(url, 'halaman_umum', String(currentGeneralPage.value), '1');
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
                const base = 'btn btn-sm whitespace-nowrap';
                return activeNavigation.value === value ? `${base} btn-neutral` : `${base} btn-ghost`;
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
                    proyeksi: 'Proyeksi',
                    berlangsung: 'Berlangsung',
                    persiapan: 'Persiapan',
                    menunggu: 'Menunggu',
                    selesai: 'Selesai',
                }[status] || status || '-';
            }

            function statusBadgeClass(status) {
                return {
                    proyeksi: 'badge badge-warning badge-soft badge-sm',
                    berlangsung: 'badge badge-success badge-soft badge-sm',
                    persiapan: 'badge badge-warning badge-soft badge-sm',
                    menunggu: 'badge badge-ghost badge-sm',
                    selesai: 'badge badge-info badge-soft badge-sm',
                }[status] || 'badge badge-ghost badge-sm';
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
                return `${dayNames[date.getDay()].toUpperCase()}, ${date.getDate()} ${monthNames[date.getMonth()].toUpperCase()} ${date.getFullYear()}`;
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
                if (requestedMenu === 'all' || /^unit:\d+$/.test(requestedMenu || '')) {
                    activeNavigation.value = requestedMenu;
                }
                if (IS_MEMBER && ['saya', 'semua'].includes(params.get('scope'))) {
                    memberScope.value = params.get('scope');
                }
                if (['quarter', 'semester'].includes(params.get('periode'))) {
                    periodMode.value = params.get('periode');
                }
                if (['quarter', 'semester'].includes(params.get('periode_umum'))) {
                    generalPeriodMode.value = params.get('periode_umum');
                }
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
                loadAgenda();
                loadWeather();
                agendaTimer = setInterval(loadAgenda, 60000);
                clockTimer = setInterval(() => {
                    now.value = new Date();
                }, 1000);
                weatherTimer = setInterval(loadWeather, 1800000);
                window.addEventListener('resize', updateUnitScrollState);
            });

            onUnmounted(() => {
                clearInterval(agendaTimer);
                clearInterval(clockTimer);
                clearInterval(weatherTimer);
                window.removeEventListener('resize', updateUnitScrollState);
            });

            return {
                initialLoading,
                refreshing,
                loadError,
                units,
                unitScroller,
                canScrollUnitsLeft,
                canScrollUnitsRight,
                weather,
                weatherLabel,
                weatherLocation,
                headerDay,
                headerDate,
                headerTime,
                activeNavigation,
                memberScope,
                periodMode,
                generalPeriodMode,
                pageSize,
                generalPageSize,
                currentPage,
                currentGeneralPage,
                filteredAgendas,
                filteredGeneralAgendas,
                orderedAgendas,
                orderedGeneralAgendas,
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
                scopeButtonClass,
                isDark,
                loadAgenda,
                setNavigation,
                updateUnitScrollState,
                scrollUnitFilters,
                setMemberScope,
                changePeriod,
                changeGeneralPeriod,
                handleAgendaToggle,
                handleGeneralToggle,
                changePageSize,
                changeGeneralPageSize,
                goToPage,
                goToGeneralPage,
                navButtonClass,
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
