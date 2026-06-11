<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex items-center justify-between">
    <div>
        <h1 class="page-title">Anggota DPRD</h1>
        <p class="page-subtitle">Kelola data anggota dan nomor WhatsApp</p>
    </div>
    <a href="<?= base_url('admin/anggota/create') ?>" class="ta-btn ta-btn-primary ta-btn-sm">
        <i data-lucide="plus" class="mr-1"></i>Tambah Anggota
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
        <div class="header-icon"><i data-lucide="users"></i></div>
        <div>
            <h6>Daftar Anggota</h6>
            <p class="header-sub">
                <?= $pagination['total'] ?> anggota terdaftar
                <?php if ($pagination['total'] > 0): ?>
                    &bull; Menampilkan <?= $pagination['from'] ?>-<?= $pagination['to'] ?>
                <?php endif; ?>
            </p>
        </div>
        <form method="get" class="ml-auto flex gap-2">
            <input type="search" class="ta-input ta-input-sm input-search"
                placeholder="Cari nama anggota..." name="q" value="<?= esc($filters['q']) ?>" />
            <select class="ta-select ta-select-sm" name="per_page" style="width:auto;" onchange="this.form.submit()">
                <?php foreach ([10, 25, 50, 100] as $size): ?>
                    <option value="<?= $size ?>" <?= (int) $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="ta-btn ta-btn-sm ta-btn-outline-brand" title="Cari">
                <i data-lucide="search"></i>
            </button>
            <?php if ($filters['q'] !== ''): ?>
                <a href="<?= base_url('admin/anggota') ?>" class="ta-btn ta-btn-sm ta-btn-outline-gray" title="Reset">
                    <i data-lucide="rotate-ccw"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="section-card-body">
        <?php if (empty($members)): ?>
            <div class="empty-state">
                <i data-lucide="users"></i>
                <p>Belum ada data anggota.</p>
                <small>Klik "Tambah Anggota" untuk mulai menambahkan data.</small>
            </div>
        <?php else: ?>
            <table class="custom-table" id="table-anggota">
                <thead>
                    <tr>
                        <th class="ta-col-num">No</th>
                        <th>Nama Lengkap</th>
                        <th>Fraksi</th>
                        <th>Komisi</th>
                        <th>No WhatsApp</th>
                        <th>Status</th>
                        <th class="ta-col-action">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $i => $m): ?>
                        <tr>
                            <td>
                                <?= $pagination['from'] + $i ?>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
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
                                    <span class="ta-badge bg-emerald-50 text-emerald-600 font-semibold text-xs">Aktif</span>
                                <?php else: ?>
                                    <span class="ta-badge bg-gray-100 text-gray-500 font-semibold text-xs">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                <form method="get" action="<?= base_url("admin/anggota/{$m['id']}/edit") ?>">
                                    <button type="submit" class="ta-btn ta-btn-sm ta-btn-outline-brand" title="Edit">
                                        <i data-lucide="pencil" class="mr-1"></i>Edit
                                    </button>
                                </form>
                                <form method="get" action="<?= base_url("admin/anggota/{$m['id']}/delete") ?>"
                                    onsubmit="return confirm('Hapus anggota ini?')">
                                    <button type="submit" class="ta-btn ta-btn-sm ta-btn-outline-danger" title="Hapus">
                                        <i data-lucide="trash-2" class="mr-1"></i>Hapus
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
        'ariaLabel'       => 'Pagination anggota',
    ]) ?>

</div>

<?= $this->endSection() ?>
