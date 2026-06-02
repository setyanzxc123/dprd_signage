<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h1 class="page-title">Anggota DPRD</h1>
        <p class="page-subtitle">Kelola data anggota dan nomor WhatsApp</p>
    </div>
    <a href="<?= base_url('admin/anggota/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Anggota
    </a>
</div>

<?php
    $filters = $filters ?? ['q' => '', 'per_page' => 10];
    $pagination = $pagination ?? [
        'page'       => 1,
        'perPage'    => 10,
        'total'      => count($members),
        'totalPages' => 1,
        'from'       => count($members) ? 1 : 0,
        'to'         => count($members),
    ];
    $paginationBase = base_url('admin/anggota');
    $paginationQuery = array_filter([
        'q'        => $filters['q'] !== '' ? $filters['q'] : null,
        'per_page' => (int) $filters['per_page'] !== 10 ? $filters['per_page'] : null,
    ], static fn($value) => $value !== null && $value !== '');
?>

<div class="section-card">

    <div class="section-card-header">
        <div class="header-icon"><i class="bi bi-people-fill"></i></div>
        <div>
            <h6>Daftar Anggota</h6>
            <p class="header-sub">
                <?= $pagination['total'] ?> anggota terdaftar
                <?php if ($pagination['total'] > 0): ?>
                    &bull; Menampilkan <?= $pagination['from'] ?>-<?= $pagination['to'] ?>
                <?php endif; ?>
            </p>
        </div>
        <form method="get" class="ms-auto d-flex gap-2">
            <input type="search" class="form-control form-control-sm input-search"
                placeholder="Cari nama anggota..." name="q" value="<?= esc($filters['q']) ?>" />
            <select class="form-select form-select-sm" name="per_page" style="width:auto;" onchange="this.form.submit()">
                <?php foreach ([10, 25, 50, 100] as $size): ?>
                    <option value="<?= $size ?>" <?= (int) $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary" title="Cari">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($filters['q'] !== ''): ?>
                <a href="<?= base_url('admin/anggota') ?>" class="btn btn-sm btn-outline-secondary" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="section-card-body">
        <?php if (empty($members)): ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <p>Belum ada data anggota.</p>
                <small>Klik "Tambah Anggota" untuk mulai menambahkan data.</small>
            </div>
        <?php else: ?>
            <table class="custom-table" id="table-anggota">
                <thead>
                    <tr>
                        <th class="col-num">No</th>
                        <th>Nama Lengkap</th>
                        <th>Fraksi</th>
                        <th>Komisi</th>
                        <th>No WhatsApp</th>
                        <th>Status</th>
                        <th class="col-action">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $i => $m): ?>
                        <tr>
                            <td>
                                <?= $pagination['from'] + $i ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="table-avatar">
                                        <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="cell-title">
                                            <?= esc($m['name']) ?>
                                        </div>
                                        <div class="cell-subtitle">
                                            <?= esc($m['jabatan'] ?? '') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?= esc($m['fraksi']) ?>
                            </td>
                            <td><span class="badge-group">
                                    <?= esc($m['komisi']) ?>
                                </span></td>
                            <td>
                                <span class="text-mono">
                                    <?= esc($m['no_wa']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($m['aktif']): ?>
                                    <span class="badge bg-success-subtle text-success fw-semibold text-xs">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary fw-semibold text-xs">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= base_url("admin/anggota/{$m['id']}/edit") ?>" class="btn-action btn-action-blue"
                                    title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= base_url("admin/anggota/{$m['id']}/delete") ?>"
                                    class="btn-action btn-action-red ms-1" title="Hapus"
                                    onclick="return confirm('Hapus anggota ini?')">
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
        'ariaLabel'       => 'Pagination anggota',
    ]) ?>

</div>

<?= $this->endSection() ?>
