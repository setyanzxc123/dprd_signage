<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$sourceBadgeClasses = [
    'banmus'              => 'badge badge-info badge-soft',
    'insidental_internal' => 'badge badge-secondary badge-soft',
    'agenda_eksternal'    => 'badge badge-warning badge-soft',
];
$statusBadgeClasses = [
    'menunggu'     => 'badge badge-ghost',
    'persiapan'    => 'badge badge-warning badge-soft',
    'berlangsung'  => 'badge badge-success badge-soft',
    'selesai'      => 'badge badge-info badge-soft',
];
$weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-base-content/50">Operasional</p>
        <h1 class="page-title">Seluruh Agenda</h1>
        <p class="mt-1 text-sm text-base-content/60">
            Daftar operasional Agenda Banmus terjadwal, Agenda Insidental, dan Agenda Eksternal.
        </p>
    </div>
    <div class="join w-full sm:w-auto">
        <a href="<?= esc($list_url) ?>"
            class="btn btn-sm join-item flex-1 <?= $view_mode === 'list' ? 'btn-neutral' : 'btn-outline' ?>">
            <i data-lucide="list" class="h-4 w-4"></i>
            Daftar
        </a>
        <a href="<?= esc($calendar_url) ?>"
            class="btn btn-sm join-item flex-1 <?= $view_mode === 'calendar' ? 'btn-neutral' : 'btn-outline' ?>">
            <i data-lucide="calendar-days" class="h-4 w-4"></i>
            Kalender
        </a>
    </div>
</div>

<section class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
    <div class="card card-border bg-base-100 shadow-sm">
        <div class="card-body gap-1 p-4">
            <span class="text-xs font-bold uppercase tracking-wide text-base-content/50">Ditampilkan</span>
            <strong class="text-2xl"><?= number_format($counts['total'], 0, ',', '.') ?></strong>
        </div>
    </div>
    <div class="card card-border bg-base-100 shadow-sm">
        <div class="card-body gap-1 p-4">
            <span class="text-xs font-bold uppercase tracking-wide text-base-content/50">Banmus</span>
            <strong class="text-2xl"><?= number_format($counts['banmus'], 0, ',', '.') ?></strong>
        </div>
    </div>
    <div class="card card-border bg-base-100 shadow-sm">
        <div class="card-body gap-1 p-4">
            <span class="text-xs font-bold uppercase tracking-wide text-base-content/50">Agenda Insidental</span>
            <strong class="text-2xl"><?= number_format($counts['insidental_internal'], 0, ',', '.') ?></strong>
        </div>
    </div>
    <div class="card card-border bg-base-100 shadow-sm">
        <div class="card-body gap-1 p-4">
            <span class="text-xs font-bold uppercase tracking-wide text-base-content/50">Eksternal</span>
            <strong class="text-2xl"><?= number_format($counts['agenda_eksternal'], 0, ',', '.') ?></strong>
        </div>
    </div>
    <div class="card card-border col-span-2 bg-base-100 shadow-sm lg:col-span-1">
        <div class="card-body gap-1 p-4">
            <span class="text-xs font-bold uppercase tracking-wide text-base-content/50">Berpotensi Konflik</span>
            <strong class="text-2xl <?= $counts['conflicts'] > 0 ? 'text-error' : '' ?>">
                <?= number_format($counts['conflicts'], 0, ',', '.') ?>
            </strong>
        </div>
    </div>
</section>

<section class="card card-border mb-4 bg-base-100 shadow-sm">
    <div class="card-body p-4">
        <form action="<?= base_url('admin/kalender') ?>" method="get" data-turbo="true">
            <input type="hidden" name="view" value="<?= esc($view_mode) ?>" />
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Bulan</legend>
                    <input class="input input-sm w-full" type="month" name="month" value="<?= esc($month) ?>" />
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Sumber</legend>
                    <select class="select select-sm w-full" name="source">
                        <option value="">Semua sumber</option>
                        <?php foreach ($filter_options['sources'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['source'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Jenis</legend>
                    <select class="select select-sm w-full" name="jenis">
                        <option value="">Semua jenis</option>
                        <?php foreach ($filter_options['types'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['jenis'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Lingkup</legend>
                    <select class="select select-sm w-full" name="lingkup">
                        <option value="">Semua lingkup</option>
                        <?php foreach ($filter_options['scopes'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['lingkup'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Unit / peserta</legend>
                    <select class="select select-sm w-full" name="unit">
                        <option value="">Semua unit</option>
                        <?php foreach ($filter_options['units'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['unit'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Lokasi</legend>
                    <select class="select select-sm w-full" name="lokasi">
                        <option value="">Semua lokasi</option>
                        <?php foreach ($filter_options['locations'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['lokasi'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Status waktu</legend>
                    <select class="select select-sm w-full" name="status">
                        <option value="">Semua status</option>
                        <?php foreach ($filter_options['statuses'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Publikasi</legend>
                    <select class="select select-sm w-full" name="publikasi">
                        <option value="">Semua publikasi</option>
                        <?php foreach ($filter_options['publications'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $filters['publikasi'] === $value ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>
            </div>
            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <a href="<?= base_url('admin/kalender') ?>" class="btn btn-sm btn-ghost">
                    Reset filter
                </a>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Terapkan
                </button>
            </div>
        </form>
    </div>
</section>

<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex flex-col gap-3 border-b border-base-300 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <i data-lucide="<?= $view_mode === 'list' ? 'list' : 'calendar-range' ?>" class="h-5 w-5 text-base-content/60"></i>
            <h2 class="card-title text-base"><?= esc($month_label) ?></h2>
        </div>
        <div class="join w-full sm:w-auto">
            <a href="<?= esc($previous_url) ?>" class="btn btn-sm btn-outline join-item flex-1" aria-label="Bulan sebelumnya">
                <i data-lucide="chevron-left" class="h-4 w-4"></i>
                Sebelumnya
            </a>
            <a href="<?= esc($next_url) ?>" class="btn btn-sm btn-outline join-item flex-1" aria-label="Bulan berikutnya">
                Berikutnya
                <i data-lucide="chevron-right" class="h-4 w-4"></i>
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
                                        'banmus'              => 'Banmus',
                                        'insidental_internal' => 'Agenda Insidental',
                                        default               => 'Eksternal',
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
            <table class="table table-zebra table-md w-full admin-data-table"
                id="table-agenda-terpadu"
                data-admin-datatable
                data-dt-order='[[0,"asc"]]'
                data-dt-page-length="25">
                <thead>
                    <tr class="bg-base-200">
                        <th>Tanggal dan Waktu</th>
                        <th>Agenda</th>
                        <th>Sumber / Jenis</th>
                        <th>Unit / Peserta</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Publikasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendas as $agenda): ?>
                        <tr class="transition-colors hover:bg-base-200/40">
                            <td data-order="<?= esc($agenda['tanggal'] . ' ' . $agenda['waktu_mulai'], 'attr') ?>">
                                <div class="whitespace-nowrap text-sm font-bold">
                                    <?= esc(date('d/m/Y', strtotime($agenda['tanggal']))) ?>
                                </div>
                                <div class="whitespace-nowrap text-xs text-base-content/55">
                                    <?= esc($agenda['waktu_mulai']) ?>
                                    <?php if ($agenda['waktu_selesai'] !== null): ?>
                                        &ndash;<?= esc($agenda['waktu_selesai']) ?>
                                    <?php endif; ?>
                                    WITA
                                </div>
                            </td>
                            <td>
                                <a href="<?= esc($agenda['edit_url']) ?>" class="link link-hover font-bold">
                                    <?= esc($agenda['judul']) ?>
                                </a>
                                <?php if ($agenda['has_conflict']): ?>
                                    <div class="mt-2">
                                        <span class="badge badge-error badge-sm">Konflik lintas sumber</span>
                                        <?php foreach ($agenda['conflicts'] as $conflict): ?>
                                            <p class="mt-1 max-w-sm text-xs text-error"><?= esc($conflict['label']) ?></p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?= esc($sourceBadgeClasses[$agenda['source']] ?? 'badge') ?> badge-sm">
                                    <?= esc($agenda['source_label']) ?>
                                </span>
                                <p class="mt-1 text-xs text-base-content/55"><?= esc($agenda['jenis_label']) ?></p>
                            </td>
                            <td>
                                <?php if ($agenda['units'] !== []): ?>
                                    <div class="flex max-w-xs flex-wrap gap-1">
                                        <?php foreach ($agenda['units'] as $unit): ?>
                                            <span class="badge badge-ghost badge-sm"><?= esc($unit) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-base-content/45">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="max-w-xs text-sm font-semibold"><?= esc($agenda['lokasi']) ?></span></td>
                            <td>
                                <span class="<?= esc($statusBadgeClasses[$agenda['status']] ?? 'badge badge-ghost') ?> badge-sm">
                                    <?= esc(ucfirst($agenda['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-sm <?= $agenda['is_publik'] ? 'badge-success badge-soft' : 'badge-ghost' ?>">
                                    <?= $agenda['is_publik'] ? 'Publik' : 'Internal' ?>
                                </span>
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
