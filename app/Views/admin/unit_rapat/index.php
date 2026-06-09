<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h1 class="page-title">Unit Rapat</h1>
        <p class="page-subtitle">Kelola komisi, badan, pansus, dan target peserta rapat</p>
    </div>
    <a href="<?= base_url('admin/unit-rapat/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Unit
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
        <div class="header-icon"><i class="bi bi-diagram-3-fill"></i></div>
        <div>
            <h6>Daftar Unit Rapat</h6>
            <p class="header-sub">
                <?= $pagination['total'] ?> unit terdaftar
                <?php if ($pagination['total'] > 0): ?>
                    &bull; Menampilkan <?= $pagination['from'] ?>-<?= $pagination['to'] ?>
                <?php endif; ?>
            </p>
        </div>
        <form method="get" class="ms-auto d-flex gap-2 align-items-center">
            <label class="small text-muted mb-0" for="unit-per-page">Tampilkan</label>
            <select class="form-select form-select-sm" id="unit-per-page" name="per_page" style="width:auto;" onchange="this.form.submit()">
                <?php foreach ([10, 25, 50, 100] as $size): ?>
                    <option value="<?= $size ?>" <?= (int) $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="section-card-body">
        <?php if (empty($units)): ?>
            <div class="empty-state">
                <i class="bi bi-diagram-3"></i>
                <p>Belum ada unit rapat.</p>
                <small>Klik "Tambah Unit" untuk menambahkan komisi, badan, atau pansus.</small>
            </div>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th class="col-num">No</th>
                        <th>Nama Unit</th>
                        <th>Jenis</th>
                        <th>Urutan</th>
                        <th>Status</th>
                        <th class="col-action">Aksi</th>
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
                                <span class="badge bg-primary-subtle text-primary" style="font-size:.7rem;">
                                    <?= esc($jenisOptions[$unit['jenis']] ?? ucfirst($unit['jenis'])) ?>
                                </span>
                            </td>
                            <td><?= esc($unit['urutan']) ?></td>
                            <td>
                                <?php if ($unit['aktif']): ?>
                                    <span class="status-badge badge-selesai"><span class="dot"></span>Aktif</span>
                                <?php else: ?>
                                    <span class="status-badge badge-menunggu"><span class="dot"></span>Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= base_url("admin/unit-rapat/{$unit['id']}/edit") ?>"
                                    class="btn-action btn-action-blue" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= base_url("admin/unit-rapat/{$unit['id']}/delete") ?>"
                                    class="btn-action btn-action-red ms-1"
                                    title="Nonaktifkan"
                                    onclick="return confirm('Nonaktifkan unit rapat ini? Unit tidak muncul di pilihan jadwal baru, tetapi riwayat jadwal lama tetap aman.')">
                                    <i class="bi bi-trash"></i>
                                </a>
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
        'ariaLabel'       => 'Pagination unit rapat',
    ]) ?>
</div>

<?= $this->endSection() ?>
