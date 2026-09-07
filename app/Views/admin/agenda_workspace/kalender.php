<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$sourceTextClasses = [
    'banmus'      => 'text-emerald-600 dark:text-emerald-400',
    'jadwal_umum' => 'text-indigo-600 dark:text-indigo-400',
];
$statusTextClasses = [
    'menunggu'    => 'text-slate-600 dark:text-slate-400',
    'persiapan'   => 'text-amber-600 dark:text-amber-400',
    'berlangsung' => 'text-emerald-600 dark:text-emerald-400',
    'selesai'     => 'text-sky-600 dark:text-sky-400',
];
$weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

$activeFilterCount = 0;
if (! empty($filters['source'])) $activeFilterCount++;
if (! empty($filters['unit'])) $activeFilterCount++;
if (! empty($filters['lokasi'])) $activeFilterCount++;
if (! empty($filters['status'])) $activeFilterCount++;
if (! empty($filters['publikasi'])) $activeFilterCount++;
?>

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Kalender Agenda</h1>
    </div>

    <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
        <a href="<?= esc($list_url) ?>"
            class="py-1.5 px-3 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg transition <?= $view_mode === 'list' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' ?>">
            <i data-lucide="list" class="size-3.5"></i>
            Daftar
        </a>
        <a href="<?= esc($calendar_url) ?>"
            class="py-1.5 px-3 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg transition <?= $view_mode === 'calendar' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' ?>">
            <i data-lucide="calendar-days" class="size-3.5"></i>
            Kalender
        </a>
    </div>
</div>

<!-- Stats Strip -->
<section class="mb-4 grid grid-cols-3 gap-2.5 sm:gap-4">
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 p-3.5 sm:p-4">
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ditampilkan</span>
        <div class="mt-1 text-xl sm:text-2xl font-black text-slate-900 dark:text-white"><?= number_format($counts['total'], 0, ',', '.') ?></div>
    </div>
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 p-3.5 sm:p-4">
        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Banmus</span>
        <div class="mt-1 text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400"><?= number_format($counts['banmus'], 0, ',', '.') ?></div>
    </div>
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 p-3.5 sm:p-4">
        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Jadwal Umum</span>
        <div class="mt-1 text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400"><?= number_format($counts['jadwal_umum'], 0, ',', '.') ?></div>
    </div>
</section>

<!-- Collapsible Filter Workspace -->
<section class="mb-4 bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 overflow-hidden">
    <div class="flex items-center justify-between p-3.5 sm:p-4 border-b border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-2">
            <i data-lucide="sliders-horizontal" class="size-4 text-emerald-500"></i>
            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Filter Workspace</span>
            <?php if ($activeFilterCount > 0): ?>
                <span class="py-0.5 px-2 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    <?= $activeFilterCount ?> aktif
                </span>
            <?php endif; ?>
        </div>
        <button type="button"
            class="hs-collapse-toggle py-1.5 px-3 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition cursor-pointer"
            id="calendar-filter-toggle"
            aria-expanded="<?= $activeFilterCount > 0 ? 'true' : 'false' ?>"
            data-hs-collapse="#calendar-filter-collapse">
            <span><?= $activeFilterCount > 0 ? 'Tutup Filter' : 'Buka Filter' ?></span>
            <i data-lucide="chevron-down" class="size-3.5 hs-collapse-open:rotate-180 transition-transform"></i>
        </button>
    </div>

    <div id="calendar-filter-collapse" class="hs-collapse <?= $activeFilterCount > 0 ? '' : 'hidden' ?> w-full overflow-hidden transition-[height] duration-300" aria-labelledby="calendar-filter-toggle">
        <form action="<?= base_url('admin/kalender') ?>" method="get" class="p-4 sm:p-5 space-y-4">
            <input type="hidden" name="view" value="<?= esc($view_mode) ?>" />
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label for="filter-month" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Bulan</label>
                    <input id="filter-month" class="py-2 px-3 block w-full border border-slate-200 rounded-xl text-xs focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 font-semibold" type="month" name="month" value="<?= esc($month) ?>" />
                </div>
                <div>
                    <label for="filter-source" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Sumber Agenda</label>
                    <select id="filter-source" class="py-2 px-3 pe-9 block w-full border border-slate-200 rounded-xl text-xs focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" name="source">
                        <option value="">Semua sumber</option>
                        <?php foreach ($filter_options['sources'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['source'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter-unit" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Unit / Peserta</label>
                    <select id="filter-unit" class="py-2 px-3 pe-9 block w-full border border-slate-200 rounded-xl text-xs focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" name="unit">
                        <option value="">Semua unit</option>
                        <?php foreach ($filter_options['units'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['unit'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter-lokasi" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Lokasi</label>
                    <select id="filter-lokasi" class="py-2 px-3 pe-9 block w-full border border-slate-200 rounded-xl text-xs focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" name="lokasi">
                        <option value="">Semua lokasi</option>
                        <?php foreach ($filter_options['locations'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['lokasi'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter-status" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Status Waktu</label>
                    <select id="filter-status" class="py-2 px-3 pe-9 block w-full border border-slate-200 rounded-xl text-xs focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" name="status">
                        <option value="">Semua status</option>
                        <?php foreach ($filter_options['statuses'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter-publikasi" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Publikasi</label>
                    <select id="filter-publikasi" class="py-2 px-3 pe-9 block w-full border border-slate-200 rounded-xl text-xs focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" name="publikasi">
                        <option value="">Semua publikasi</option>
                        <?php foreach ($filter_options['publications'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['publikasi'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <a href="<?= base_url('admin/kalender') ?>" class="py-1.5 px-3 inline-flex items-center text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition">
                    Reset Filter
                </a>
                <button type="submit" class="py-1.5 px-3.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer">
                    <i data-lucide="filter" class="size-3.5"></i>
                    Terapkan
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Main Workspace Card -->
<section class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 overflow-hidden">
    <div class="flex flex-col gap-3 border-b border-slate-100 dark:border-slate-800 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <i data-lucide="<?= $view_mode === 'list' ? 'list' : 'calendar-range' ?>" class="size-4 text-emerald-500"></i>
            <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white"><?= esc($month_label) ?></h2>
        </div>
        <div class="inline-flex rounded-xl shadow-xs">
            <a href="<?= esc($previous_url) ?>" class="py-1.5 px-3 inline-flex items-center gap-x-1 text-xs font-semibold rounded-s-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition" aria-label="Bulan sebelumnya">
                <i data-lucide="chevron-left" class="size-3.5"></i>
                Sebelumnya
            </a>
            <a href="<?= esc($next_url) ?>" class="py-1.5 px-3 inline-flex items-center gap-x-1 text-xs font-semibold rounded-e-xl border border-s-0 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition" aria-label="Bulan berikutnya">
                Berikutnya
                <i data-lucide="chevron-right" class="size-3.5"></i>
            </a>
        </div>
    </div>

    <?php if ($view_mode === 'calendar'): ?>
        <div class="dashboard-calendar-wrap agenda-workspace-calendar">
            <div class="dashboard-weekday-grid" aria-hidden="true">
                <?php foreach ($weekdays as $weekday): ?>
                    <span><?= esc($weekday) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="dashboard-calendar-grid" aria-label="Kalender seluruh agenda">
                <?php foreach ($calendar_days as $cell): ?>
                    <?php
                    $dayClasses = ['dashboard-calendar-day'];
                    if ($cell['date'] === null) {
                        $dayClasses[] = 'outside';
                    }
                    if ($cell['is_today']) {
                        $dayClasses[] = 'today';
                    }
                    if ($cell['agendas'] !== []) {
                        $dayClasses[] = 'has-events';
                    }
                    ?>
                    <div class="<?= esc(implode(' ', $dayClasses)) ?>">
                        <?php if ($cell['date'] !== null): ?>
                            <span class="calendar-day-top">
                                <span class="calendar-day-num"><?= (int) $cell['day'] ?></span>
                                <?php if ($cell['agendas'] !== []): ?>
                                    <span class="calendar-day-count"><?= count($cell['agendas']) ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="calendar-day-events">
                                <?php foreach (array_slice($cell['agendas'], 0, 3) as $agenda): ?>
                                    <?php
                                    $eventClass = match (true) {
                                        $agenda['has_conflict']          => 'conflict',
                                        $agenda['status'] === 'selesai' => 'done',
                                        $agenda['status'] === 'berlangsung' => 'live',
                                        default                         => 'next',
                                    };
                                    $sourceShort = match ($agenda['source']) {
                                        'banmus' => 'Banmus',
                                        default  => 'Jadwal Umum',
                                    };
                                    ?>
                                    <a href="<?= esc($agenda['edit_url']) ?>"
                                        class="calendar-event-dot <?= esc($eventClass) ?>"
                                        title="<?= esc($agenda['waktu_mulai'] . ' · ' . $agenda['source_label'] . ' · ' . $agenda['judul'] . ' · ' . $agenda['lokasi']) ?>">
                                        <span>[<?= esc($sourceShort) ?>] <?= esc($agenda['judul']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (count($cell['agendas']) > 3): ?>
                                    <span class="calendar-event-dot empty">
                                        <span>+<?= count($cell['agendas']) - 3 ?> agenda lainnya</span>
                                    </span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="dashboard-calendar-legend" aria-label="Legenda kalender">
                <span class="calendar-event-dot done"><span>Selesai</span></span>
                <span class="calendar-event-dot live"><span>Berlangsung</span></span>
                <span class="calendar-event-dot next"><span>Mendatang</span></span>
                <span class="calendar-event-dot conflict"><span>Konflik</span></span>
            </div>
        </div>
    <?php else: ?>
        <div class="min-w-0">
            <div class="w-full overflow-x-auto">
            <table class="calendar-agenda-table table w-full text-start divide-y divide-slate-200 dark:divide-slate-800 admin-data-table"
                id="table-agenda-terpadu"
                data-admin-datatable
                data-dt-order='[[0,"asc"]]'
                data-dt-page-length="25">
                <thead class="bg-slate-50/75 dark:bg-slate-800/40">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tanggal dan Waktu</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Agenda</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sumber</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Unit / Peserta</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Lokasi</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Publikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs sm:text-sm">
                    <?php foreach ($agendas as $agenda): ?>
                        <tr class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Tanggal dan Waktu" data-order="<?= esc($agenda['tanggal'] . ' ' . $agenda['waktu_mulai'], 'attr') ?>">
                                <div>
                                    <div class="whitespace-nowrap text-xs font-bold text-slate-900 dark:text-white">
                                        <?= esc(date('d/m/Y', strtotime($agenda['tanggal']))) ?>
                                    </div>
                                    <div class="mt-0.5 whitespace-nowrap font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                        <?php if ($agenda['waktu_mulai'] === null): ?>
                                            Sepanjang hari
                                        <?php else: ?>
                                            <?= esc($agenda['waktu_mulai']) ?>
                                        <?php endif; ?>
                                        <?php if ($agenda['waktu_mulai'] !== null && $agenda['waktu_selesai'] !== null): ?>
                                            &ndash;<?= esc($agenda['waktu_selesai']) ?>
                                        <?php endif; ?>
                                        <?= $agenda['waktu_mulai'] !== null ? ' WITA' : '' ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5" data-label="Agenda">
                                <a href="<?= esc($agenda['edit_url']) ?>" class="text-sm font-bold text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition">
                                    <?= esc($agenda['judul']) ?>
                                </a>
                                <?php if ($agenda['has_conflict']): ?>
                                    <div class="mt-1.5 p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200/60 dark:border-rose-800/60">
                                        <div class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 dark:text-rose-300">
                                            <i data-lucide="triangle-alert" class="size-3"></i>
                                            Konflik lintas sumber
                                        </div>
                                        <?php foreach ($agenda['conflicts'] as $conflict): ?>
                                            <p class="mt-0.5 text-xs text-rose-600 dark:text-rose-400"><?= esc($conflict['label']) ?></p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Sumber">
                                <span class="text-xs font-semibold whitespace-nowrap <?= esc($sourceTextClasses[$agenda['source']] ?? 'text-slate-600 dark:text-slate-400') ?>">
                                    <?= esc($agenda['source_label']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5" data-label="Unit / Peserta">
                                <?php if ($agenda['units'] !== []): ?>
                                    <div class="max-w-xs text-xs text-slate-700 dark:text-slate-300">
                                        <?= esc(implode(', ', $agenda['units'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 dark:text-slate-500">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5" data-label="Lokasi"><span class="max-w-xs text-xs font-semibold text-slate-800 dark:text-slate-200"><?= esc($agenda['lokasi']) ?></span></td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Status">
                                <span class="text-xs font-semibold whitespace-nowrap <?= esc($statusTextClasses[$agenda['status']] ?? 'text-slate-600 dark:text-slate-400') ?>">
                                    <?= esc(ucfirst($agenda['status'])) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Publikasi">
                                <?php if ($agenda['is_publik']): ?>
                                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 whitespace-nowrap">Publik</span>
                                <?php else: ?>
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">Internal</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    <?php endif; ?>
</section>

<?= $this->endSection() ?>
