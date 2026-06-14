<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex items-center justify-between">
    <div>
        <h1 class="page-title">Kelompok Peserta</h1>
        <p class="page-subtitle">Kelola kelompok internal DPRD untuk target rapat dan notifikasi WA</p>
    </div>
    <a href="<?= base_url('admin/unit-rapat/create') ?>" class="btn btn-primary btn-sm gap-1">
        <i data-lucide="plus" class="w-4 h-4"></i>Tambah Kelompok
    </a>
</div>

<?php
    $filters = $filters ?? ['per_page' => 10];
    $pagination = $pagination ?? [
        'page'       => 1,
        'perPage'    => 10,
        'total'      => count($units),
        'totalPages' => 1,
        'from'       => count($units) ? 1 : 0,
        'to'         => count($units),
    ];
    $paginationBase = base_url('admin/unit-rapat');
    $paginationQuery = array_filter([
        'per_page' => (int) $filters['per_page'] !== 10 ? $filters['per_page'] : null,
    ], static fn($value) => $value !== null && $value !== '');
?>

<div class="section-card">
    <div class="section-card-header">
        <div class="header-icon"><i data-lucide="workflow"></i></div>
        <div>
            <h6>Daftar Kelompok Peserta</h6>
            <p class="header-sub">
                <?= $pagination['total'] ?> kelompok terdaftar
                <?php if ($pagination['total'] > 0): ?>
                    &bull; Menampilkan <?= $pagination['from'] ?>-<?= $pagination['to'] ?>
                <?php endif; ?>
            </p>
        </div>
        <form method="get" class="ml-auto flex gap-2 items-center">
            <label class="text-xs text-base-content/60 mb-0" for="unit-per-page">Tampilkan</label>
            <select class="select select-sm select-bordered" id="unit-per-page" name="per_page" style="width:auto;" onchange="this.form.submit()">
                <?php foreach ([10, 25, 50, 100] as $size): ?>
                    <option value="<?= $size ?>" <?= (int) $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="section-card-body p-0">
        <?php if (empty($units)): ?>
            <div class="empty-state p-8 text-center flex flex-col items-center justify-center">
                <i data-lucide="workflow" class="w-12 h-12 text-base-content/40 mb-3"></i>
                <p class="font-bold text-base-content">Belum ada kelompok peserta.</p>
                <small class="text-base-content/60 mt-1">Klik "Tambah Kelompok" untuk membuat kelompok internal DPRD.</small>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto w-full">
                <table class="table table-zebra table-md w-full">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th>No</th>
                            <th>Nama Kelompok</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($units as $i => $unit): ?>
                            <tr class="hover:bg-base-200/30 transition-colors">
                                <td><?= $pagination['from'] + $i ?></td>
                                <td>
                                    <div class="font-bold text-base-content text-sm"><?= esc($unit['nama']) ?></div>
                                </td>
                                <td>
                                    <?php if ($unit['aktif']): ?>
                                        <span class="badge badge-success h-auto py-0.5 px-2 text-xs font-semibold whitespace-nowrap gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-ghost h-auto py-0.5 px-2 text-xs font-semibold whitespace-nowrap text-base-content/60 gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                            Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="<?= base_url("admin/unit-rapat/{$unit['id']}/edit") ?>" class="btn btn-sm btn-outline btn-primary gap-1" title="Edit">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>Edit
                                        </a>
                                        <form method="get" action="<?= base_url("admin/unit-rapat/{$unit['id']}/delete") ?>"
                                            onsubmit="return confirm('Nonaktifkan kelompok peserta ini? Kelompok tidak muncul di pilihan jadwal baru, tetapi riwayat jadwal lama tetap aman.')"
                                            class="inline-flex m-0">
                                            <button type="submit" class="btn btn-sm btn-outline btn-error gap-1" title="Nonaktifkan">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>Nonaktif
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?= view('admin/components/_pagination', [
        'pagination'      => $pagination,
        'paginationBase'  => $paginationBase,
        'paginationQuery' => $paginationQuery,
        'ariaLabel'       => 'Pagination kelompok peserta',
    ]) ?>
</div>

<?= $this->endSection() ?>
