<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header dashboard-page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">
        <span id="page-date">-</span> &bull; Ringkasan aktivitas rapat
    </p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5">
    <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Agenda Hari Ini</p>
            <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white"><?= $stats['rapat_hari_ini'] ?></p>
        </div>
        <div class="flex size-10 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <i data-lucide="calendar-check" class="size-5"></i>
        </div>
    </div>
    <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Agenda Bulan Ini</p>
            <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white"><?= $stats['agenda_bulan_ini'] ?></p>
        </div>
        <div class="flex size-10 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400">
            <i data-lucide="calendar-range" class="size-5"></i>
        </div>
    </div>
    <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Berlangsung</p>
            <p class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400"><?= $stats['berlangsung'] ?></p>
        </div>
        <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
            <i data-lucide="radio" class="size-5"></i>
        </div>
    </div>
    <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Mendatang</p>
            <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white"><?= $stats['mendatang'] ?></p>
        </div>
        <div class="flex size-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
            <i data-lucide="clock-3" class="size-5"></i>
        </div>
    </div>
</div>

<section class="dashboard-calendar-workspace dashboard-home-workspace">
    <article class="dashboard-panel dashboard-calendar-panel" aria-labelledby="dashboard-calendar-title">
        <header class="dashboard-panel-head">
            <div class="dashboard-panel-title">
                <h2 id="dashboard-calendar-title">Kalender Agenda <?= esc($monthLabel ?? '') ?></h2>
                <p>Pilih tanggal untuk melihat agenda detail dan status operasionalnya.</p>
            </div>
            <div class="dashboard-month-controls flex items-center gap-1.5">
                <a href="<?= esc($prevMonthUrl) ?>" class="inline-flex justify-center items-center size-8 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition" title="Bulan sebelumnya">
                    <i data-lucide="chevron-left" class="size-4"></i>
                </a>
                <a href="<?= esc($todayMonthUrl) ?>" class="inline-flex items-center py-1 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition">
                    Bulan Ini
                </a>
                <a href="<?= esc($nextMonthUrl) ?>" class="inline-flex justify-center items-center size-8 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition" title="Bulan berikutnya">
                    <i data-lucide="chevron-right" class="size-4"></i>
                </a>
            </div>
        </header>

        <div class="dashboard-calendar-wrap dashboard-home-calendar">
            <div class="dashboard-weekday-grid" aria-hidden="true">
                <?php foreach ($weekdayLabels as $label): ?>
                    <span><?= esc($label) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="dashboard-calendar-grid" role="listbox" aria-label="Kalender agenda bulanan">
                <?php foreach ($calendarDays as $day): ?>
                    <?php
                        $isActive = ($day['date'] ?? '') === ($selectedDate ?? '');
                        $isCurrentMonth = (bool) ($day['is_current_month'] ?? false);
                        $dayClasses = ['dashboard-calendar-day'];
                        if ($isActive) {
                            $dayClasses[] = 'active';
                        }
                        if (! $isCurrentMonth) {
                            $dayClasses[] = 'outside';
                        }
                        if ($day['is_today']) {
                            $dayClasses[] = 'today';
                        }
                        if ((int) $day['count'] > 0) {
                            $dayClasses[] = 'has-events';
                        }
                    ?>
                    <button type="button"
                            class="<?= esc(implode(' ', $dayClasses)) ?>"
                            data-dashboard-day="<?= esc($day['date']) ?>"
                            role="option"
                            aria-selected="<?= $isActive ? 'true' : 'false' ?>">
                        <span class="calendar-day-top">
                            <span class="calendar-day-num"><?= esc((int) $day['date_num']) ?></span>
                            <?php if ((int) $day['count'] > 0): ?>
                                <span class="calendar-day-count"><?= (int) $day['count'] ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="calendar-day-events">
                            <?php if (empty($day['meetings'])): ?>
                                <span class="calendar-event-dot empty"><span>Kosong</span></span>
                            <?php else: ?>
                                <?php foreach (array_slice($day['meetings'], 0, 3) as $meeting): ?>
                                    <span class="calendar-event-dot <?= esc($meeting['status_key']) ?>">
                                        <span><?= esc(preg_replace('/^Rapat\s+/i', '', $meeting['title'])) ?></span>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="dashboard-calendar-legend" aria-label="Legenda status agenda">
                <span class="calendar-event-dot done"><span>Selesai</span></span>
                <span class="calendar-event-dot live"><span>Berlangsung</span></span>
                <span class="calendar-event-dot next"><span>Mendatang</span></span>
                <span class="calendar-event-dot empty"><span>Agenda kosong</span></span>
            </div>

            <button type="button" class="dashboard-mobile-agenda-trigger" data-mobile-agenda-open>
                <i data-lucide="list-checks"></i>
                <span data-mobile-agenda-label>Lihat agenda terpilih</span>
            </button>
        </div>
    </article>

    <aside class="dashboard-panel dashboard-agenda-card"
           id="dashboard-agenda-sheet"
           role="region"
           aria-labelledby="dashboard-agenda-title">
        <header class="dashboard-panel-head">
            <div class="dashboard-panel-title">
                <h2 id="dashboard-agenda-title">Agenda Terpilih</h2>
                <?php foreach ($calendarDays as $day): ?>
                    <p class="dashboard-panel-summary"
                       data-dashboard-summary="<?= esc($day['date']) ?>"
                       <?= (($day['date'] ?? '') === ($selectedDate ?? '')) ? '' : 'hidden' ?>>
                        <?= esc($day['summary']) ?>
                    </p>
                <?php endforeach; ?>
            </div>
            <button type="button" class="dashboard-agenda-close" data-mobile-agenda-close aria-label="Tutup agenda">
                <i data-lucide="x"></i>
            </button>
        </header>

        <div class="dashboard-agenda-panels">
            <?php foreach ($calendarDays as $day): ?>
                <?php
                    $isActive = ($day['date'] ?? '') === ($selectedDate ?? '');
                ?>
                <section class="dashboard-agenda-panel <?= $isActive ? 'active' : '' ?>"
                         data-dashboard-panel="<?= esc($day['date']) ?>"
                         <?= $isActive ? '' : 'hidden' ?>>
                    <div class="selected-date-card flex items-center gap-3 p-3 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 mb-3">
                        <div class="selected-date-number flex size-10 items-center justify-center rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400 font-black text-lg"><?= esc((int) $day['date_num']) ?></div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white"><?= esc($day['day_name']) ?></h3>
                            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400"><?= esc($day['month']) ?> &bull; <?= (int) $day['count'] ?> agenda</p>
                        </div>
                    </div>

                    <ul class="dashboard-agenda-list list <?= empty($day['meetings']) ? 'hidden' : '' ?>" aria-live="polite">
                        <?php foreach ($day['meetings'] as $m): ?>
                            <?php $badge = status_badge($m['status']); ?>
                            <li>
                                <a href="<?= esc($m['detail_url']) ?>" class="dashboard-agenda-item group block p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700 shadow-xs transition mb-2">
                                    <div class="agenda-row-head flex items-center justify-between gap-2 mb-1.5">
                                        <div class="agenda-time-block text-xs font-bold text-slate-700 dark:text-slate-300">
                                            <?php if ($m['start'] === null): ?>
                                                <span>Sepanjang hari</span>
                                            <?php else: ?>
                                                <span><?= esc($m['start']) ?></span>
                                                <?php if ($m['end'] !== null): ?>
                                                    <span aria-hidden="true">&ndash;</span>
                                                    <span><?= esc($m['end']) ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        $dashStatusClass = match ($m['status']) {
                                            'berlangsung' => 'text-emerald-600 dark:text-emerald-400',
                                            'persiapan'   => 'text-amber-600 dark:text-amber-400',
                                            'selesai'     => 'text-sky-600 dark:text-sky-400',
                                            default       => 'text-slate-500 dark:text-slate-400',
                                        };
                                        ?>
                                        <span class="text-xs font-semibold whitespace-nowrap <?= $dashStatusClass ?>">
                                            <?= $badge['label'] ?>
                                        </span>
                                    </div>
                                    <div class="agenda-content">
                                        <h3 class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition leading-snug"><?= esc($m['title']) ?></h3>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="dashboard-agenda-empty <?= empty($day['meetings']) ? 'is-visible' : '' ?>">
                        <i data-lucide="calendar-check"></i>
                        <strong>Tidak ada agenda</strong>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </aside>
</section>

<button type="button" class="dashboard-agenda-overlay" data-mobile-agenda-close aria-label="Tutup agenda"></button>

<?= $this->endSection() ?>
