<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex items-center justify-between">
    <div>
        <h1 class="page-title">Kelompok Peserta</h1>
        <p class="page-subtitle">Kelola kelompok internal DPRD untuk target rapat dan notifikasi WA</p>
    </div>
    <a href="<?= base_url('admin/unit-rapat/create') ?>" class="ta-btn ta-btn-primary ta-btn-sm">
        <i data-lucide="plus" class="mr-1"></i>Tambah Kelompok
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
            <label class="text-xs text-gray-500 mb-0" for="unit-per-page">Tampilkan</label>
            <select class="ta-select ta-select-sm" id="unit-per-page" name="per_page" style="width:auto;" onchange="this.form.submit()">
                <?php foreach ([10, 25, 50, 100] as $size): ?>
                    <option value="<?= $size ?>" <?= (int) $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="section-card-body">
        <?php if (empty($units)): ?>
            <div class="empty-state">
                <i data-lucide="workflow"></i>
                <p>Belum ada kelompok peserta.</p>
                <small>Klik "Tambah Kelompok" untuk membuat kelompok internal DPRD.</small>
            </div>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th class="ta-col-num">No</th>
                        <th>Nama Kelompok</th>
                        <th>Status</th>
                        <th class="ta-col-action">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($units as $i => $unit): ?>
                        <tr>
                            <td><?= $pagination['from'] + $i ?></td>
                            <td>
                                <div class="cell-title"><?= esc($unit['nama']) ?></div>
                            </td>
                            <td>
                                <?php if ($unit['aktif']): ?>
                                    <span class="status-badge badge-selesai"><span class="dot"></span>Aktif</span>
                                <?php else: ?>
                                    <span class="status-badge badge-menunggu"><span class="dot"></span>Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                <form method="get" action="<?= base_url("admin/unit-rapat/{$unit['id']}/edit") ?>">
                                    <button type="submit" class="ta-btn ta-btn-sm ta-btn-outline-brand" title="Edit">
                                        <i data-lucide="pencil" class="mr-1"></i>Edit
                                    </button>
                                </form>
                                <form method="get" action="<?= base_url("admin/unit-rapat/{$unit['id']}/delete") ?>"
                                    onsubmit="return confirm('Nonaktifkan kelompok peserta ini? Kelompok tidak muncul di pilihan jadwal baru, tetapi riwayat jadwal lama tetap aman.')">
                                    <button type="submit" class="ta-btn ta-btn-sm ta-btn-outline-danger" title="Nonaktifkan">
                                        <i data-lucide="trash-2" class="mr-1"></i>Nonaktif
                                    </button>
                                </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
