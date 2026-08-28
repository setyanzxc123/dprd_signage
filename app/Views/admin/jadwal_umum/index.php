<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="page-title">Jadwal Umum</h1>
    <a href="<?= base_url('admin/jadwal-umum/create') ?>" class="btn btn-primary btn-sm w-full gap-1 sm:w-auto">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah Jadwal
    </a>
</div>

<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-5">
        <h2 class="card-title text-base">
            <i data-lucide="calendar-range" class="h-5 w-5 text-primary"></i>
            Daftar Jadwal Umum
        </h2>
        <span class="badge badge-ghost whitespace-nowrap"><?= count($schedules) ?> jadwal</span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto">
            <table class="general-schedule-table table table-zebra table-md w-full admin-data-table"
                id="table-jadwal-umum"
                data-admin-datatable
                data-dt-order='[[1,"desc"]]'
                data-dt-col-filters='[{"col":4,"label":"Status"},{"col":5,"label":"Publikasi"}]'>
                <thead>
                    <tr class="bg-base-200">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Jadwal</th>
                        <th>Agenda</th>
                        <th>Lokasi &amp; Peserta</th>
                        <th>Status</th>
                        <th>Publikasi</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr class="transition-colors hover:bg-base-200/40">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Jadwal" data-order="<?= esc($schedule['tanggal'] . ' ' . ($schedule['waktu_mulai'] ?? '00:00:00')) ?>">
                                <div>
                                <div class="whitespace-nowrap text-sm font-bold">
                                    <?= esc(date('d/m/Y', strtotime($schedule['tanggal']))) ?>
                                </div>
                                <div class="mt-0.5 whitespace-nowrap text-xs text-base-content/55">
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
                            <td data-label="Agenda">
                                <div class="max-w-md text-sm font-bold"><?= esc($schedule['judul']) ?></div>
                                <?php if (! empty($schedule['pihak_eksternal'])): ?>
                                    <div class="mt-1 max-w-md text-xs text-base-content/55">
                                        Pihak luar: <?= esc($schedule['pihak_eksternal']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Lokasi & Peserta">
                                <div class="max-w-xs text-sm font-semibold"><?= esc($schedule['lokasi']) ?></div>
                                <div class="mt-1 max-w-xs text-xs text-base-content/55">
                                    <?= $schedule['unit_names'] !== []
                                        ? esc(implode(', ', $schedule['unit_names']))
                                        : 'Tanpa kelompok peserta khusus' ?>
                                </div>
                            </td>
                            <td data-label="Status" data-filter="<?= esc(ucfirst($schedule['status'])) ?>">
                                <?php
                                $statusClass = match ($schedule['status']) {
                                    'berlangsung' => 'badge-success',
                                    'persiapan'   => 'badge-warning',
                                    'selesai'     => 'badge-info',
                                    default       => 'badge-ghost',
                                };
                                ?>
                                <span class="badge badge-sm <?= $statusClass ?>">
                                    <?= esc(ucfirst($schedule['status'])) ?>
                                </span>
                            </td>
                            <td data-label="Publikasi" data-filter="<?= (int) $schedule['is_publik'] === 1 ? 'Publik' : 'Internal' ?>">
                                <?php if ((int) $schedule['is_publik'] === 1): ?>
                                    <span class="badge badge-success badge-sm">Publik</span>
                                <?php else: ?>
                                    <span class="badge badge-ghost badge-sm">Internal</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="general-schedule-actions flex flex-wrap items-center justify-end gap-1.5">
                                    <a href="<?= base_url('admin/notulen?jadwal_type=umum&jadwal_id=' . (int) $schedule['id']) ?>" class="btn btn-ghost btn-xs gap-1 text-primary" title="Buka / Buat Notulensi AI">
                                        <i data-lucide="mic" class="h-3.5 w-3.5"></i>
                                        Notulen
                                    </a>
                                    <a href="<?= base_url("admin/jadwal-umum/{$schedule['id']}/edit") ?>" class="btn btn-xs w-16 gap-1">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </a>
                                    <form method="post" action="<?= base_url("admin/jadwal-umum/{$schedule['id']}/delete") ?>"
                                        class="m-0 inline-flex" data-confirm-message="Hapus Jadwal Umum ini?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-error btn-xs w-20 gap-1">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            Hapus
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
