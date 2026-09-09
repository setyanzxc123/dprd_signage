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
            <p class="mt-1 text-2xl font-black text-red-600 dark:text-red-400"><?= $stats['berlangsung'] ?></p>
        </div>
        <div class="flex size-10 items-center justify-center rounded-xl bg-red-500/15 text-red-600 dark:text-red-400">
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

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <section class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm rounded-2xl overflow-hidden" aria-labelledby="dashboard-calendar-heading">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 px-4 py-3 sm:px-6 sm:py-4 bg-slate-50/50 dark:bg-slate-800/30">
            <h2 id="dashboard-calendar-heading" class="text-xs sm:text-base font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                <?= esc($monthLabel ?? '') ?>
            </h2>
            <div class="flex items-center gap-1.5">
                <a href="<?= esc($prevMonthUrl) ?>" class="inline-flex size-9 sm:size-8 items-center justify-center border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition cursor-pointer rounded-xl shadow-xs" aria-label="Bulan sebelumnya">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <a href="<?= esc($nextMonthUrl) ?>" class="inline-flex size-9 sm:size-8 items-center justify-center border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition cursor-pointer rounded-xl shadow-xs" aria-label="Bulan berikutnya">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </div>
        </div>

        <div class="p-2 sm:p-4">
            <div class="grid grid-cols-7 gap-1 text-center font-bold text-[10px] sm:text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 py-2 border-b border-slate-200 dark:border-slate-700">
                <span>Sen</span>
                <span>Sel</span>
                <span>Rab</span>
                <span>Kam</span>
                <span>Jum</span>
                <span>Sab</span>
                <span>Min</span>
            </div>

            <div class="grid grid-cols-7 gap-1 pt-2" role="grid" aria-label="Kalender agenda">
                <?php foreach ($calendarPrevDays as $prevDayNum): ?>
                    <div class="h-10 sm:h-14 lg:h-16 flex items-center justify-center text-[11px] sm:text-xs text-slate-300 dark:text-slate-600 select-none rounded-xl">
                        <?= (int) $prevDayNum ?>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($calendarDays as $d): ?>
                    <?php
                        $isSelected = ($d['date'] === ($selectedDate ?? ''));
                        $isToday    = (bool) ($d['is_today'] ?? false);
                        $hasAgenda  = (bool) ($d['has_agenda'] ?? false);

                        $btnClass = 'h-10 sm:h-14 lg:h-16 flex flex-col items-center justify-center rounded-xl text-[11px] sm:text-xs font-semibold relative transition cursor-pointer ';
                        if ($isSelected) {
                            $btnClass .= 'bg-blue-600 text-white font-bold shadow-xs hover:bg-blue-700';
                        } elseif ($isToday) {
                            $btnClass .= 'border-2 border-blue-500 text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-800';
                        } else {
                            $btnClass .= 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800';
                        }

                        $dotClass = 'size-1.5 rounded-full absolute bottom-1.5 ' . ($isSelected ? 'bg-white' : 'bg-amber-500');
                    ?>
                    <button type="button"
                            class="<?= $btnClass ?>"
                            data-dashboard-day="<?= esc($d['date']) ?>"
                            data-dashboard-today="<?= $isToday ? 'true' : 'false' ?>"
                            data-dashboard-has-events="<?= $hasAgenda ? 'true' : 'false' ?>"
                            data-formatted-date="<?= esc($d['formatted_date']) ?>"
                            role="gridcell"
                            aria-selected="<?= $isSelected ? 'true' : 'false' ?>">
                        <span><?= (int) $d['day'] ?></span>
                        <?php if ($hasAgenda): ?>
                            <span class="<?= $dotClass ?>" data-agenda-dot></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>

                <?php foreach ($calendarNextDays as $nextDayNum): ?>
                    <div class="h-10 sm:h-14 lg:h-16 flex items-center justify-center text-[11px] sm:text-xs text-slate-300 dark:text-slate-600 select-none rounded-xl">
                        <?= (int) $nextDayNum ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-700 px-4 py-3 text-[11px] text-slate-500 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-800/30">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-amber-500"></span>
                <span class="font-medium">Ada Agenda</span>
            </div>
            <a href="<?= esc($todayMonthUrl) ?>" class="btn-jump-today text-xs text-blue-600 dark:text-blue-400 hover:underline font-bold cursor-pointer">
                Bulan Ini
            </a>
        </div>
    </section>

    <section id="dashboard-panel-list" class="lg:col-span-5 bg-white dark:bg-slate-900 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm lg:sticky lg:top-20" aria-labelledby="agenda-panel-title">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-700 px-4 py-3.5 sm:px-6 sm:py-4 bg-slate-50/50 dark:bg-slate-800/30">
            <div class="min-w-0">
                <h2 id="agenda-panel-title" class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white truncate underline decoration-slate-300 decoration-2 underline-offset-[6px] dark:decoration-slate-600">
                    Agenda Rapat
                </h2>
            </div>
            <div class="shrink-0">
                <span id="date_heading_label" class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300">
                    <?= esc($selectedDateHeading ?? '') ?>
                </span>
            </div>
        </div>

        <div class="flex items-center justify-between px-4 py-2.5 sm:px-6 text-xs border-b border-slate-200 dark:border-slate-700 bg-slate-50/30 dark:bg-slate-800/10">
            <span class="text-slate-500 dark:text-slate-400 font-medium">
                Total: <strong id="total_agenda_count" class="text-slate-800 dark:text-slate-200 font-bold"><?= count($meetingsByDate[$selectedDate] ?? []) ?></strong> agenda
            </span>
        </div>

        <div id="agenda_empty_state" class="py-12 px-4 text-center <?= empty($meetingsByDate[$selectedDate] ?? []) ? '' : 'hidden' ?>">
            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Tidak ada agenda pada tanggal ini.</p>
        </div>

        <div id="agenda_items_container" class="max-h-none lg:max-h-[580px] overflow-y-visible lg:overflow-y-auto">
            <?php foreach ($jadwals as $item): ?>
                <?php
                    $isMatch = ($item['tanggal'] === $selectedDate);
                ?>
                <details name="admin-dashboard-agenda"
                         class="group agenda-collapse mx-4 sm:mx-6 border-b border-slate-200 dark:border-slate-700 last:border-b-0 list-none [&::-webkit-details-marker]:hidden <?= $isMatch ? '' : 'hidden' ?>"
                         data-agenda-item
                         data-date="<?= esc($item['tanggal']) ?>">
                    <summary class="cursor-pointer select-none py-3.5 sm:py-4 pr-8">
                        <div class="flex items-start gap-2.5">
                            <h3 class="min-w-0 flex-1 text-sm sm:text-base font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-snug">
                                <?= esc($item['judul']) ?>
                            </h3>
                            <span class="shrink-0 mt-0.5 inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-md <?= esc($item['status_badge']['class'] ?? '') ?>">
                                <span class="sm:hidden"><?= esc($item['status_badge']['short_label'] ?? $item['status_badge']['label'] ?? '') ?></span>
                                <span class="hidden sm:inline"><?= esc($item['status_badge']['label'] ?? '') ?></span>
                            </span>
                        </div>
                    </summary>

                    <div class="min-w-0 pt-1 pb-4 sm:pb-5 space-y-3">
                        <div class="space-y-1.5 text-xs text-slate-700 dark:text-slate-300">
                            <div class="flex items-start gap-2">
                                <span class="w-20 sm:w-24 shrink-0 text-slate-500 dark:text-slate-400 font-medium">Waktu</span>
                                <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200"><?= esc($item['time_range']) ?></span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="w-20 sm:w-24 shrink-0 text-slate-500 dark:text-slate-400 font-medium">Ruangan</span>
                                <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200"><?= esc($item['room']) ?></span>
                            </div>
                            <?php if ($item['group'] !== '-'): ?>
                                <div class="flex items-start gap-2">
                                    <span class="w-20 sm:w-24 shrink-0 text-slate-500 dark:text-slate-400 font-medium">Peserta</span>
                                    <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                    <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200"><?= esc($item['group']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flex items-start gap-2">
                                <span class="w-20 sm:w-24 shrink-0 text-slate-500 dark:text-slate-400 font-medium">Sumber</span>
                                <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200"><?= esc($item['source_label']) ?></span>
                            </div>
                            <?php if (!empty($item['keterangan'])): ?>
                                <div class="flex items-start gap-2">
                                    <span class="w-20 sm:w-24 shrink-0 text-slate-500 dark:text-slate-400 font-medium">Catatan</span>
                                    <span class="text-slate-400 dark:text-slate-500 shrink-0">:</span>
                                    <span class="min-w-0 flex-1 leading-relaxed text-slate-700 dark:text-slate-200 whitespace-pre-line"><?= esc($item['keterangan']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pt-1 flex justify-end">
                            <a href="<?= esc($item['detail_url']) ?>" class="inline-flex items-center text-xs font-semibold text-slate-700 dark:text-slate-300 hover:underline">
                                Detail Agenda &rarr;
                            </a>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?= $this->endSection() ?>
