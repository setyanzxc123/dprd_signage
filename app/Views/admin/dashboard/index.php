<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">
        <span id="page-date">-</span> &bull; Ringkasan aktivitas rapat
    </p>
</div>

<div class="stats stats-vertical sm:stats-horizontal shadow bg-base-100 border border-base-300 w-full mb-6">
    <div class="stat">
        <div class="stat-figure text-primary">
            <i data-lucide="calendar-check" class="w-8 h-8"></i>
        </div>
        <div class="stat-title text-xs font-bold uppercase tracking-wider text-base-content/60">Rapat Hari Ini</div>
        <div class="stat-value text-3xl font-extrabold text-primary"><?= $stats['rapat_hari_ini'] ?></div>
    </div>

    <div class="stat">
        <div class="stat-figure text-success">
            <i data-lucide="circle-check" class="w-8 h-8"></i>
        </div>
        <div class="stat-title text-xs font-bold uppercase tracking-wider text-base-content/60">WA Terkirim</div>
        <div class="stat-value text-3xl font-extrabold text-success"><?= $stats['wa_terkirim'] ?></div>
    </div>

    <div class="stat">
        <div class="stat-figure text-error">
            <i data-lucide="circle-x" class="w-8 h-8"></i>
        </div>
        <div class="stat-title text-xs font-bold uppercase tracking-wider text-base-content/60">WA Gagal</div>
        <div class="stat-value text-3xl font-extrabold text-error"><?= $stats['wa_gagal'] ?></div>
    </div>
</div>

<section class="dashboard-calendar-workspace">
    <article class="dashboard-panel dashboard-calendar-panel" aria-labelledby="dashboard-calendar-title">
        <header class="dashboard-panel-head">
            <div class="dashboard-panel-title">
                <h2 id="dashboard-calendar-title">Kalender Rapat <?= esc($monthLabel ?? '') ?></h2>
                <p>Pilih tanggal untuk melihat agenda detail dan status operasionalnya.</p>
            </div>
            <div class="dashboard-month-controls flex items-center gap-1">
                <a href="<?= esc($prevMonthUrl) ?>" class="btn btn-sm btn-ghost btn-circle" title="Bulan sebelumnya">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </a>
                <a href="<?= esc($todayMonthUrl) ?>" class="btn btn-sm btn-outline btn-primary">
                    Bulan Ini
                </a>
                <a href="<?= esc($nextMonthUrl) ?>" class="btn btn-sm btn-ghost btn-circle" title="Bulan berikutnya">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            </div>
        </header>

        <div class="dashboard-calendar-wrap">
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
                <span>Lihat agenda terpilih</span>
            </button>
        </div>
    </article>

    <aside class="dashboard-panel dashboard-agenda-card" id="dashboard-agenda-sheet" aria-labelledby="dashboard-agenda-title">
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
                    <div class="selected-date-card">
                        <div class="selected-date-number"><?= esc((int) $day['date_num']) ?></div>
                        <div>
                            <h3><?= esc($day['day_name']) ?></h3>
                            <p><?= esc($day['month']) ?> &bull; <?= (int) $day['count'] ?> agenda</p>
                        </div>
                    </div>

                    <div class="dashboard-agenda-list" aria-live="polite">
                        <?php foreach ($day['meetings'] as $m): ?>
                            <?php $badge = status_badge($m['status']); ?>
                            <a href="<?= esc($m['detail_url']) ?>" class="dashboard-agenda-item">
                                <div class="agenda-time-block">
                                    <?= esc($m['start']) ?>
                                    <span><?= esc($m['end']) ?></span>
                                </div>
                                <div class="agenda-content">
                                    <div class="agenda-title-row">
                                        <h3><?= esc($m['title']) ?></h3>
                                        <span class="badge <?= $badge['class'] ?> h-auto py-1 px-2 text-xs shrink-0 whitespace-nowrap font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current <?= $m['status'] === 'berlangsung' ? 'animate-pulse' : '' ?>"></span>
                                            <?= $badge['label'] ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

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

<?= $this->section('scripts') ?>
<script>
    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const dayNames   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

    function setDate() {
        const now = new Date();
        const str = `${dayNames[now.getDay()]}, ${now.getDate()} ${monthNames[now.getMonth()]} ${now.getFullYear()}`;
        const el = document.getElementById('page-date');
        if (el) el.textContent = str;
    }

    function initDashboardCalendar() {
        const buttons = document.querySelectorAll('[data-dashboard-day]');
        const panels = document.querySelectorAll('[data-dashboard-panel]');
        const summaries = document.querySelectorAll('[data-dashboard-summary]');
        const mobileAgendaQuery = window.matchMedia('(max-width: 520px)');
        const openAgendaButtons = document.querySelectorAll('[data-mobile-agenda-open]');
        const closeAgendaButtons = document.querySelectorAll('[data-mobile-agenda-close]');

        function openMobileAgenda() {
            if (mobileAgendaQuery.matches) {
                document.body.classList.add('mobile-agenda-open');
            }
        }

        function closeMobileAgenda() {
            document.body.classList.remove('mobile-agenda-open');
        }

        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const date = button.dataset.dashboardDay;

                buttons.forEach(item => {
                    const active = item === button;
                    item.classList.toggle('active', active);
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                panels.forEach(panel => {
                    const active = panel.dataset.dashboardPanel === date;
                    panel.hidden = !active;
                    panel.classList.toggle('active', active);
                });

                summaries.forEach(summary => {
                    summary.hidden = summary.dataset.dashboardSummary !== date;
                });

                if (window.lucide) {
                    window.lucide.createIcons();
                }

                openMobileAgenda();
            });
        });

        openAgendaButtons.forEach(button => {
            button.addEventListener('click', openMobileAgenda);
        });

        closeAgendaButtons.forEach(button => {
            button.addEventListener('click', closeMobileAgenda);
        });

        mobileAgendaQuery.addEventListener('change', event => {
            if (!event.matches) {
                closeMobileAgenda();
            }
        });
    }

    setDate();
    initDashboardCalendar();
</script>
<?= $this->endSection() ?>
