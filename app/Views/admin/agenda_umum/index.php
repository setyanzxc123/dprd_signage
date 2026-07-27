<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$categoryLabels = [
    'demonstrasi'     => 'Demonstrasi',
    'audiensi_publik' => 'Audiensi Publik',
    'kunjungan'       => 'Kunjungan',
    'kegiatan_sosial' => 'Kegiatan Sosial',
    'lainnya'         => 'Lainnya',
];
$statusLabels = [
    'tentatif'       => 'Tentatif',
    'terkonfirmasi'  => 'Terkonfirmasi',
    'selesai'        => 'Selesai',
    'dibatalkan'     => 'Dibatalkan',
];
$statusClasses = [
    'tentatif'       => 'badge badge-warning',
    'terkonfirmasi'  => 'badge badge-success',
    'selesai'        => 'badge badge-info',
    'dibatalkan'     => 'badge badge-error',
];
?>

<div class="page-header flex items-center justify-between gap-3">
    <div>
        <h1 class="page-title">Jadwal Umum</h1>
        <p class="page-subtitle">Kelola demonstrasi dan kegiatan publik nonrapat di lingkungan DPRD</p>
    </div>
    <a href="<?= base_url('admin/agenda-umum/create') ?>" class="btn btn-sm btn-primary gap-1">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah Jadwal Umum
    </a>
</div>

<div class="section-card">
    <div class="section-card-header">
        <div class="header-icon"><i data-lucide="calendar-range"></i></div>
        <div>
            <h6>Daftar Jadwal Umum</h6>
            <p class="header-sub"><?= count($agendas) ?> kegiatan terdaftar</p>
        </div>
    </div>

    <div class="section-card-body pt-3.5">
        <div role="alert" class="alert alert-info mb-3 text-sm">
            <i data-lucide="info" class="h-4 w-4"></i>
            <span>Jadwal Umum digunakan untuk kegiatan nonrapat. Rapat resmi yang mendadak tetap dicatat sebagai <strong>Rapat Insidental</strong> pada menu Jadwal Rapat.</span>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-zebra table-md w-full admin-data-table responsive-card-table" data-admin-datatable data-dt-order='[[1,"desc"]]'>
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Tanggal</th>
                        <th>Kegiatan</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Publikasi</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendas as $agenda): ?>
                        <tr class="transition-colors hover:bg-base-200/30">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Tanggal" data-order="<?= esc($agenda['tanggal'] . ' ' . $agenda['waktu_mulai']) ?>">
                                <div class="whitespace-nowrap text-sm font-bold">
                                    <?= esc(date('d/m/Y', strtotime($agenda['tanggal']))) ?>
                                </div>
                                <div class="mt-0.5 whitespace-nowrap text-xs text-base-content/55">
                                    <?= esc(substr($agenda['waktu_mulai'], 0, 5)) ?>
                                    <?php if (! empty($agenda['waktu_selesai'])): ?>
                                        –<?= esc(substr($agenda['waktu_selesai'], 0, 5)) ?> WITA
                                    <?php else: ?>
                                        WITA
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Kegiatan">
                                <div class="max-w-md text-sm font-bold"><?= esc($agenda['judul']) ?></div>
                                <span class="badge badge-ghost badge-sm mt-1">
                                    <?= esc($categoryLabels[$agenda['kategori']] ?? $agenda['kategori']) ?>
                                </span>
                            </td>
                            <td data-label="Lokasi">
                                <div class="max-w-xs text-sm font-semibold"><?= esc($agenda['lokasi']) ?></div>
                                <?php if (! empty($agenda['perkiraan_peserta'])): ?>
                                    <div class="mt-0.5 text-xs text-base-content/55">
                                        ±<?= number_format((int) $agenda['perkiraan_peserta'], 0, ',', '.') ?> peserta
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status">
                                <span class="<?= esc($statusClasses[$agenda['status']] ?? 'badge badge-neutral') ?> badge-sm whitespace-nowrap">
                                    <?= esc($statusLabels[$agenda['status']] ?? $agenda['status']) ?>
                                </span>
                            </td>
                            <td data-label="Publikasi">
                                <?php if ((int) $agenda['is_publik'] === 1): ?>
                                    <span class="badge badge-success badge-sm">Dipublikasikan</span>
                                <?php else: ?>
                                    <span class="badge badge-ghost badge-sm">Draf internal</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= base_url("admin/agenda-umum/{$agenda['id']}/edit") ?>" class="btn btn-sm btn-outline btn-primary gap-1">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Edit
                                    </a>
                                    <form method="post" action="<?= base_url("admin/agenda-umum/{$agenda['id']}/delete") ?>" class="m-0 inline-flex"
                                        onsubmit="return confirm('Hapus jadwal umum ini?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline btn-error gap-1">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
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
</div>

<?= $this->endSection() ?>
