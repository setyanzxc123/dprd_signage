<?php
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$vueVersion = is_file(FCPATH . 'assets/vendor/vue/vue.global.prod.js') ? filemtime(FCPATH . 'assets/vendor/vue/vue.global.prod.js') : time();
$cssVersion = is_file(FCPATH . 'assets/css/agenda.css') ? filemtime(FCPATH . 'assets/css/agenda.css') : time();
$isMember = is_array($member ?? null);
$pageTitle = $isMember ? 'Agenda Anggota DPRD' : 'Agenda Rapat DPRD';
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
        <div class="navbar mx-auto min-h-20 w-[min(1180px,calc(100%-20px))] gap-2 px-0 py-2 sm:w-[min(1180px,calc(100%-32px))] lg:min-h-28">
            <a class="navbar-start min-w-0 flex-1 gap-3" href="<?= esc($portalUrl) ?>" aria-label="Halaman agenda DPRD">
                <img class="h-12 w-12 shrink-0 rounded-lg border border-base-300 bg-white object-contain sm:h-16 sm:w-16" src="<?= esc($logoUrl) ?>" alt="Logo DPRD" />
                <span class="min-w-0">
                    <span class="block truncate text-base font-black leading-tight sm:text-xl">DPRD Provinsi Sulawesi Tengah</span>
                </span>
            </a>

            <div class="navbar-center hidden lg:flex">
                <div class="join">
                    <div class="join-item min-w-36 border border-base-300 bg-base-200 px-4 py-3 text-center">
                        <span class="block text-[10px] font-extrabold uppercase tracking-widest text-base-content/50">Cuaca · BMKG</span>
                        <span class="mt-1 block text-sm font-black">{{ weatherLabel }}</span>
                    </div>
                    <div class="join-item min-w-40 border-y border-r border-base-300 bg-base-200 px-4 py-3 text-center">
                        <span class="block text-[10px] font-extrabold uppercase tracking-widest text-base-content/50">Tanggal</span>
                        <span class="mt-1 block text-sm font-black">{{ headerDate }}</span>
                    </div>
                    <div class="join-item min-w-32 border-y border-r border-base-300 bg-base-200 px-4 py-3 text-center">
                        <span class="block text-[10px] font-extrabold uppercase tracking-widest text-base-content/50">Jam</span>
                        <span class="mt-1 block text-sm font-black tabular-nums">{{ headerTime }} WITA</span>
                    </div>
                </div>
            </div>

            <div class="navbar-end w-auto shrink-0 gap-1.5">
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
                <?php else: ?>
                    <a class="btn btn-primary btn-sm" href="<?= base_url('login?akses=anggota') ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-4-4 5-5-5-5m5 5H3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="hidden sm:inline">Masuk Anggota</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-3 border-t border-base-300 lg:hidden">
            <div class="min-w-0 p-2 text-center">
                <span class="block text-[9px] font-extrabold uppercase tracking-wider text-base-content/45">Cuaca · BMKG</span>
                <span class="mt-0.5 block truncate text-xs font-black">{{ weatherLabel }}</span>
            </div>
            <div class="min-w-0 border-x border-base-300 p-2 text-center">
                <span class="block text-[9px] font-extrabold uppercase tracking-wider text-base-content/45">Tanggal</span>
                <span class="mt-0.5 block truncate text-xs font-black">{{ headerDate }}</span>
            </div>
            <div class="min-w-0 p-2 text-center">
                <span class="block text-[9px] font-extrabold uppercase tracking-wider text-base-content/45">Jam</span>
                <span class="mt-0.5 block truncate text-xs font-black tabular-nums">{{ headerTime }} WITA</span>
            </div>
        </div>

        <nav class="border-t border-base-300 bg-base-200" aria-label="Navigasi agenda">
            <div class="mx-auto flex w-[min(1180px,calc(100%-20px))] flex-col gap-2 py-2 sm:w-[min(1180px,calc(100%-32px))] sm:flex-row sm:items-center">
                <div class="carousel carousel-start min-w-0 flex-1 gap-2">
                    <div class="carousel-item">
                        <button :class="navButtonClass('all')" type="button" @click="setNavigation('all')">Semua</button>
                    </div>
                    <div v-for="unit in units" :key="unit.id" class="carousel-item">
                        <button :class="navButtonClass('unit:' + unit.id)" type="button" @click="setNavigation('unit:' + unit.id)">{{ compactUnitName(unit.nama) }}</button>
                    </div>
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
                        <button type="button" class="join-item btn btn-sm" :class="scopeButtonClass('semua')" :aria-pressed="memberScope === 'semua'" @click="setMemberScope('semua')">Semua Jadwal</button>
                        <button type="button" class="join-item btn btn-sm" :class="scopeButtonClass('saya')" :aria-pressed="memberScope === 'saya'" @click="setMemberScope('saya')">Jadwal Saya</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </header>

    <main class="mx-auto grid w-[min(1180px,calc(100%-20px))] gap-4 py-4 sm:w-[min(1180px,calc(100%-32px))] sm:py-6 lg:grid-cols-[minmax(0,1.55fr)_minmax(300px,.85fr)] lg:items-start">
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
                                        <span v-if="item.source === 'insidental_internal'" class="badge badge-secondary badge-soft badge-sm">
                                            Insidental Internal
                                        </span>
                                        <span v-if="item.status === 'proyeksi'" class="truncate text-xs font-semibold text-base-content/50">
                                            {{ item.periode_label || 'Periode belum ditentukan' }}
                                        </span>
                                        <span v-else class="truncate text-xs font-semibold text-base-content/50">{{ item.waktu_mulai }} WITA · {{ item.ruangan || '-' }}</span>
                                        <span :class="statusBadgeClass(item.status)" class="shrink-0 sm:hidden">{{ statusLabel(item.status) }}</span>
                                    </span>
                                </span>
                                <span :class="statusBadgeClass(item.status)" class="hidden sm:inline-flex">{{ statusLabel(item.status) }}</span>
                            </summary>

                            <div class="collapse-content border-t border-base-300">
                                <div class="flex flex-wrap gap-2 pt-4">
                                    <span :class="statusBadgeClass(item.status)">{{ statusLabel(item.status) }}</span>
                                    <?php if ($isMember): ?>
                                        <span v-if="item.is_participant" class="badge badge-primary badge-soft badge-sm">Anda Peserta</span>
                                        <span v-if="!item.is_public" class="badge badge-ghost badge-sm">Internal</span>
                                    <?php endif; ?>
                                </div>

                                <p v-if="item.keterangan" class="mt-3 text-sm font-medium leading-6 text-base-content/60">{{ item.keterangan }}</p>

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
                                        <dd class="mt-1 text-sm font-bold">{{ fullDate(item.tanggal) }} · {{ item.waktu_mulai }}–{{ item.waktu_selesai }} WITA</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Lokasi</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ item.ruangan || '-' }}</dd>
                                    </div>
                                    <div v-if="item.komisi" class="sm:col-span-2">
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/45">Unit rapat</dt>
                                        <dd class="mt-1 text-sm font-bold">{{ item.komisi }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <a v-if="item.status === 'proyeksi'" class="btn btn-outline btn-sm" :href="item.projection_url">
                                        Lihat Proyeksi &amp; SK
                                    </a>
                                    <template v-else>
                                        <a v-if="item.has_materi" class="btn btn-outline btn-sm" :href="item.materi_url" target="_blank" rel="noopener noreferrer">Bahan Rapat</a>
                                        <a v-if="item.has_stream" class="btn btn-outline btn-sm" :href="item.stream_url" target="_blank" rel="noopener noreferrer">Live / Video</a>
                                        <span v-if="!item.has_materi && !item.has_stream" class="text-xs font-semibold text-base-content/45">Belum ada bahan atau tautan video.</span>
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

        <aside class="grid gap-4">
            <section class="card card-border bg-base-100 shadow-sm">
                <div class="card-body gap-4">
                    <div>
                        <h2 class="card-title text-xl font-black uppercase">Jadwal Umum</h2>
                    </div>

                    <div v-if="generalLoading" class="grid gap-2">
                        <div v-for="item in 2" :key="'general-skeleton-' + item" class="skeleton h-16 w-full"></div>
                    </div>

                    <div v-else-if="generalLoadError" role="alert" class="alert alert-error alert-soft text-sm">
                        <span>Jadwal umum gagal dimuat.</span>
                    </div>

                    <ul v-else-if="generalAgendas.length" class="list rounded-box border border-base-300 bg-base-100">
                        <li v-for="item in generalAgendas" :key="'general-' + item.id" class="list-row items-center border-b border-base-300 last:border-b-0">
                            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-box bg-base-200 text-center">
                                <span class="text-[10px] font-extrabold uppercase text-base-content/45">{{ shortMonth(item.tanggal) }}</span>
                                <strong class="text-lg leading-none">{{ dayNumber(item.tanggal) }}</strong>
                            </div>
                            <div class="list-col-grow min-w-0">
                                <p class="line-clamp-2 text-sm font-extrabold leading-5">{{ item.judul }}</p>
                                <p class="mt-1 truncate text-xs font-semibold text-base-content/50">{{ item.waktu_mulai }} WITA · {{ item.lokasi }}</p>
                                <p v-if="item.pihak_eksternal" class="mt-1 truncate text-xs text-base-content/50">{{ item.pihak_eksternal }}</p>
                                <span class="mt-1 flex flex-wrap gap-1">
                                    <span class="badge badge-ghost badge-sm">{{ generalCategoryLabel(item.kategori) }}</span>
                                </span>
                            </div>
                        </li>
                    </ul>

                    <p v-else class="rounded-box border border-dashed border-base-300 p-6 text-center text-sm font-semibold text-base-content/50">Belum ada jadwal umum yang dipublikasikan.</p>
                </div>
            </section>
        </aside>
    </main>

    <footer class="border-t border-base-300 bg-base-100 py-5">
        <div class="mx-auto flex w-[min(1180px,calc(100%-20px))] flex-col gap-1 text-xs font-semibold text-base-content/50 sm:w-[min(1180px,calc(100%-32px))] sm:flex-row sm:items-center sm:justify-between">
            <span>DPRD Provinsi Sulawesi Tengah &copy; <?= date('Y') ?></span>
            <span><?= $isMember ? 'Akses anggota' : 'Akses publik' ?> · diperbarui otomatis setiap 60 detik</span>
        </div>
    </footer>
</div>

<script {csp-script-nonce}>
    const { createApp, ref, computed, onMounted, onUnmounted } = Vue;

    createApp({
        setup() {
            const API_URL = <?= json_encode($apiUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const GENERAL_API_URL = <?= json_encode($generalApiUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
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
            const generalAgendas = ref([]);
            const units = ref([]);
            const weather = ref(null);
            const now = ref(new Date());
            const activeNavigation = ref('all');
            const memberScope = ref('semua');
            const periodMode = ref('month');
            const pageSize = ref(10);
            const currentPage = ref(1);
            const expandedAgendaKey = ref(null);
            const initialLoading = ref(true);
            const refreshing = ref(false);
            const loadError = ref(false);
            const generalLoading = ref(true);
            const generalLoadError = ref(false);
            const isDark = ref(document.documentElement.getAttribute('data-theme') === 'dark');
            let agendaTimer = null;
            let clockTimer = null;
            let weatherTimer = null;
            let requestSequence = 0;

            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

            const headerDate = computed(() => {
                const value = now.value;
                return `${dayNames[value.getDay()].toUpperCase()}, ${value.getDate()} ${shortMonths[value.getMonth()].toUpperCase()} ${value.getFullYear()}`;
            });
            const headerTime = computed(() => now.value.toLocaleTimeString('id-ID', {
                timeZone: 'Asia/Makassar',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            }).replace('.', ':'));
            const weatherLabel = computed(() => {
                if (!weather.value) {
                    return 'Memuat...';
                }
                const temperature = weather.value.suhu || '';
                const condition = weather.value.kondisi || 'Tidak tersedia';
                return temperature ? `${temperature} · ${condition}` : condition;
            });
            const filteredAgendas = computed(() => {
                let visibleProjections = IS_MEMBER && memberScope.value === 'saya'
                    ? banmusProjections.value.filter((item) => item.is_participant)
                    : banmusProjections.value;
                const selectedMonths = new Set(periodMonths());
                visibleProjections = visibleProjections.filter((item) => {
                    const month = projectionMonth(item);

                    return month === null || selectedMonths.has(month);
                });
                const rows = [...agendas.value, ...visibleProjections];
                if (activeNavigation.value.startsWith('unit:')) {
                    const unitId = Number(activeNavigation.value.slice(5));
                    return rows.filter((item) => (item.unit_ids || []).map(Number).includes(unitId));
                }

                return rows;
            });
            const orderedAgendas = computed(() => {
                const today = dateKey(new Date());
                const active = filteredAgendas.value.filter((item) => item.status === 'berlangsung');
                const activeKeys = new Set(active.map((item) => item.key));
                const upcoming = filteredAgendas.value.filter((item) =>
                    item.status !== 'proyeksi'
                    && !activeKeys.has(item.key)
                    && item.tanggal >= today
                    && item.status !== 'selesai');
                const projections = filteredAgendas.value.filter((item) => item.status === 'proyeksi');
                const prioritizedKeys = new Set([...active, ...upcoming, ...projections].map((item) => item.key));
                const remaining = filteredAgendas.value.filter((item) =>
                    !prioritizedKeys.has(item.key)).reverse();
                return [...active, ...upcoming, ...projections, ...remaining];
            });
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

            function periodMonths() {
                const current = now.value;
                let firstMonth = current.getMonth();
                let count = 1;

                if (periodMode.value === 'quarter') {
                    firstMonth = Math.floor(current.getMonth() / 3) * 3;
                    count = 3;
                } else if (periodMode.value === 'semester') {
                    firstMonth = current.getMonth() < 6 ? 0 : 6;
                    count = 6;
                }

                return Array.from({ length: count }, (_, offset) =>
                    monthKey(new Date(current.getFullYear(), firstMonth + offset, 1)));
            }

            function projectionMonth(item) {
                if (/^\d{4}-\d{2}/.test(item.tanggal || '')) {
                    return item.tanggal.slice(0, 7);
                }

                const label = String(item.periode_label || '').toLowerCase();
                const monthIndex = monthNames.findIndex((month) =>
                    label.includes(month.toLowerCase()));
                if (monthIndex < 0) {
                    return null;
                }

                const yearMatch = label.match(/\b(20\d{2})\b/);
                const year = yearMatch ? Number(yearMatch[1]) : Number(item.document_year);
                if (!Number.isInteger(year) || year < 2000) {
                    return null;
                }

                return `${year}-${String(monthIndex + 1).padStart(2, '0')}`;
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
                    const payloads = await Promise.all(periodMonths().map(fetchMonth));
                    if (requestId !== requestSequence) {
                        return;
                    }
                    const unique = new Map();
                    payloads.flatMap((payload) => payload.data || [])
                        .map((item) => ({
                            ...item,
                            key: `${item.source || 'insidental_internal'}:${item.source_id ?? item.id}`,
                        }))
                        .forEach((item) => unique.set(item.key, item));
                    agendas.value = Array.from(unique.values()).sort((a, b) =>
                        `${a.tanggal} ${a.waktu_mulai}`.localeCompare(`${b.tanggal} ${b.waktu_mulai}`));
                    units.value = payloads[0]?.units || [];
                    const validPage = Math.min(currentPage.value, totalPages.value);
                    if (validPage !== currentPage.value) {
                        currentPage.value = validPage;
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
                    weather.value = payload.status === 'success' ? payload.cuaca : { kondisi: 'Tidak tersedia', suhu: '' };
                } catch {
                    weather.value = { kondisi: 'Tidak tersedia', suhu: '' };
                }
            }

            async function loadGeneralAgenda() {
                generalLoading.value = generalAgendas.value.length === 0;
                try {
                    const url = new URL(GENERAL_API_URL, window.location.origin);
                    url.searchParams.set('month', monthKey(now.value));
                    const response = await fetch(url);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    const payload = await response.json();
                    if (payload.status !== 'success') {
                        throw new Error(payload.message || 'Respons jadwal umum tidak valid.');
                    }
                    generalAgendas.value = (payload.data || []).slice(0, 5);
                    generalLoadError.value = false;
                } catch (error) {
                    generalLoadError.value = true;
                    console.error('[Agenda Umum] Gagal mengambil data:', error);
                } finally {
                    generalLoading.value = false;
                }
            }

            function setNavigation(value) {
                activeNavigation.value = value;
                resetAgendaSelection();
                updateUrl();
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
                updateUrl();
                loadAgenda();
            }

            function changePeriod() {
                resetAgendaSelection();
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

            function changePageSize() {
                currentPage.value = 1;
                expandedAgendaKey.value = null;
                updateUrl();
            }

            function goToPage(page) {
                currentPage.value = Math.min(Math.max(1, Number(page)), totalPages.value);
                expandedAgendaKey.value = null;
                updateUrl();
            }

            function resetAgendaSelection() {
                currentPage.value = 1;
                expandedAgendaKey.value = null;
            }

            function updateUrl() {
                const url = new URL(window.location.href);
                setOptionalParam(url, 'menu', activeNavigation.value, 'all');
                setOptionalParam(url, 'scope', memberScope.value, 'semua');
                setOptionalParam(url, 'periode', periodMode.value, 'month');
                setOptionalParam(url, 'tampil', String(pageSize.value), '10');
                setOptionalParam(url, 'halaman', String(currentPage.value), '1');
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
                    berlangsung: 'Berlangsung',
                    persiapan: 'Persiapan',
                    menunggu: 'Menunggu',
                    proyeksi: 'Proyeksi',
                    selesai: 'Selesai',
                }[status] || status || '-';
            }

            function statusBadgeClass(status) {
                return {
                    berlangsung: 'badge badge-success badge-soft badge-sm',
                    persiapan: 'badge badge-warning badge-soft badge-sm',
                    menunggu: 'badge badge-ghost badge-sm',
                    proyeksi: 'badge badge-warning badge-soft badge-sm',
                    selesai: 'badge badge-info badge-soft badge-sm',
                }[status] || 'badge badge-ghost badge-sm';
            }

            function generalCategoryLabel(category) {
                return {
                    audiensi: 'Audiensi / Aspirasi',
                    audiensi_publik: 'Audiensi / Aspirasi',
                    demonstrasi: 'Unjuk Rasa / Demonstrasi',
                    kunjungan: 'Kunjungan Tamu',
                    undangan: 'Undangan / Luar Gedung',
                    kegiatan_sosial: 'Kegiatan Sosial / Publik',
                    lainnya: 'Lainnya',
                }[category] || category || 'Lainnya';
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
                if (IS_MEMBER && params.get('scope') === 'saya') {
                    memberScope.value = 'saya';
                }
                if (['quarter', 'semester'].includes(params.get('periode'))) {
                    periodMode.value = params.get('periode');
                }
                if ([10, 25, 50, 100].includes(Number(params.get('tampil')))) {
                    pageSize.value = Number(params.get('tampil'));
                }
                if (/^[1-9]\d*$/.test(params.get('halaman') || '')) {
                    currentPage.value = Number(params.get('halaman'));
                }
                loadAgenda();
                loadGeneralAgenda();
                loadWeather();
                agendaTimer = setInterval(() => {
                    loadAgenda();
                    loadGeneralAgenda();
                }, 60000);
                clockTimer = setInterval(() => {
                    now.value = new Date();
                }, 1000);
                weatherTimer = setInterval(loadWeather, 1800000);
            });

            onUnmounted(() => {
                clearInterval(agendaTimer);
                clearInterval(clockTimer);
                clearInterval(weatherTimer);
            });

            return {
                initialLoading,
                refreshing,
                loadError,
                generalLoading,
                generalLoadError,
                units,
                weatherLabel,
                headerDate,
                headerTime,
                activeNavigation,
                memberScope,
                periodMode,
                pageSize,
                currentPage,
                filteredAgendas,
                orderedAgendas,
                paginatedAgendas,
                totalPages,
                pageStart,
                pageEnd,
                expandedAgendaKey,
                generalAgendas,
                scopeButtonClass,
                isDark,
                loadAgenda,
                setNavigation,
                setMemberScope,
                changePeriod,
                handleAgendaToggle,
                changePageSize,
                goToPage,
                navButtonClass,
                compactUnitName,
                statusLabel,
                statusBadgeClass,
                generalCategoryLabel,
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
