<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Jadwal Umum</h1>
    </div>
    <a href="<?= base_url('admin/jadwal-umum/create') ?>" class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition">
        <i data-lucide="plus" class="size-4"></i>
        Tambah Jadwal
    </a>
</div>

<section class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 overflow-hidden">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 px-5 py-4">
        <div class="flex items-center gap-2.5">
            <div class="flex size-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                <i data-lucide="calendar-range" class="size-4"></i>
            </div>
            <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Daftar Jadwal Umum</h2>
        </div>
        <span class="py-0.5 px-2.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
            <?= count($schedules) ?> jadwal
        </span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto">
            <table class="general-schedule-table table w-full text-start divide-y divide-slate-200 dark:divide-slate-800 admin-data-table"
                id="table-jadwal-umum"
                data-admin-datatable
                data-dt-order='[[1,"desc"]]'
                data-dt-col-filters='[{"col":4,"label":"Status"},{"col":5,"label":"Publikasi"}]'>
                <thead class="bg-slate-50/75 dark:bg-slate-800/40">
                    <tr>
                        <th class="dt-row-number no-sort px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">No</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Jadwal</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Agenda</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Lokasi &amp; Peserta</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Publikasi</th>
                        <th class="px-4 py-3 text-end text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs sm:text-sm">
                    <?php foreach ($schedules as $schedule): ?>
                        <tr class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="dt-row-number px-4 py-3.5" data-label="No"></td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Jadwal" data-order="<?= esc($schedule['tanggal'] . ' ' . ($schedule['waktu_mulai'] ?? '00:00:00')) ?>">
                                <div>
                                    <div class="whitespace-nowrap text-xs font-bold text-slate-900 dark:text-white">
                                        <?= esc(date('d/m/Y', strtotime($schedule['tanggal']))) ?>
                                    </div>
                                    <div class="mt-0.5 whitespace-nowrap font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                        <?php if (empty($schedule['waktu_mulai'])): ?>
                                            Sepanjang hari
                                        <?php else: ?>
                                            <?= esc(substr($schedule['waktu_mulai'], 0, 5)) ?>
                                            <?= ! empty($schedule['waktu_selesai']) ? '–' . esc(substr($schedule['waktu_selesai'], 0, 5)) : '' ?>
                                            WITA
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5" data-label="Agenda">
                                <div class="max-w-md text-sm font-bold leading-snug text-slate-900 dark:text-white"><?= esc($schedule['judul']) ?></div>
                                <?php if (! empty($schedule['pihak_eksternal'])): ?>
                                    <div class="mt-1 max-w-md text-xs text-slate-500 dark:text-slate-400">
                                        Pihak luar: <?= esc($schedule['pihak_eksternal']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5" data-label="Lokasi &amp; Peserta">
                                <div class="max-w-xs text-xs font-semibold text-slate-800 dark:text-slate-200"><?= esc($schedule['lokasi']) ?></div>
                                <div class="mt-0.5 max-w-xs text-[11px] text-slate-500 dark:text-slate-400">
                                    <?= $schedule['unit_names'] !== []
                                        ? esc(implode(', ', $schedule['unit_names']))
                                        : 'Tanpa kelompok peserta khusus' ?>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Status" data-filter="<?= esc(ucfirst($schedule['status'])) ?>">
                                <?php
                                $statusTextClass = match ($schedule['status']) {
                                    'berlangsung' => 'text-emerald-600 dark:text-emerald-400',
                                    'persiapan'   => 'text-amber-600 dark:text-amber-400',
                                    'selesai'     => 'text-sky-600 dark:text-sky-400',
                                    default       => 'text-slate-600 dark:text-slate-400',
                                };
                                ?>
                                <span class="text-xs font-semibold whitespace-nowrap <?= $statusTextClass ?>">
                                    <?= esc(ucfirst($schedule['status'])) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Publikasi" data-filter="<?= (int) $schedule['is_publik'] === 1 ? 'Publik' : 'Internal' ?>">
                                <?php if ((int) $schedule['is_publik'] === 1): ?>
                                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 whitespace-nowrap">Publik</span>
                                <?php else: ?>
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">Internal</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-end" data-label="Aksi">
                                <div class="general-schedule-actions flex items-center justify-end gap-1.5">
                                    <a href="<?= base_url("admin/jadwal-umum/{$schedule['id']}/edit") ?>" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-blue-200/80 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 hover:border-blue-300 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-600 dark:hover:text-white dark:hover:border-blue-600 shadow-2xs transition" title="Edit Jadwal Umum" aria-label="Edit <?= esc($schedule['judul']) ?>">
                                        <i data-lucide="pencil" class="size-3.5"></i>
                                        Edit
                                    </a>
                                    <a href="<?= base_url('admin/notulen?jadwal_type=umum&jadwal_id=' . (int) $schedule['id']) ?>" class="p-1.5 inline-flex items-center justify-center rounded-lg border border-indigo-200/80 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 hover:text-indigo-800 hover:border-indigo-300 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-600 dark:hover:text-white dark:hover:border-indigo-600 shadow-2xs transition" title="Buka / Buat Notulensi AI" aria-label="Notulensi AI <?= esc($schedule['judul']) ?>">
                                        <i data-lucide="mic" class="size-4"></i>
                                    </a>
                                    <form method="post" action="<?= base_url("admin/jadwal-umum/{$schedule['id']}/delete") ?>"
                                        class="m-0 inline-flex" data-confirm-message="Hapus Jadwal Umum ini?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="p-1.5 inline-flex items-center justify-center rounded-lg border border-rose-200/80 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 hover:border-rose-300 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-600 dark:hover:text-white dark:hover:border-rose-600 shadow-2xs transition cursor-pointer" title="Hapus Jadwal" aria-label="Hapus <?= esc($schedule['judul']) ?>">
                                            <i data-lucide="trash-2" class="size-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
