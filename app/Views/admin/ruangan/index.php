<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .room-list .compact-alert {
        font-size: 0.75rem;
        line-height: 1.35;
    }

    .room-list .section-card-body {
        padding-top: 14px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header flex items-center justify-between">
    <div>
        <h1 class="page-title">Ruangan Rapat</h1>
        <p class="page-subtitle">Kelola ruangan tetap DPRD untuk jadwal rapat</p>
    </div>
    <a href="<?= base_url('admin/ruangan/create') ?>" class="ta-btn ta-btn-primary ta-btn-sm">
        <i data-lucide="plus" class="mr-1"></i>Tambah Ruangan
    </a>
</div>

<?php
    $filters = $filters ?? ['per_page' => 10];
    $pagination = $pagination ?? [
        'page'       => 1,
        'perPage'    => 10,
        'total'      => count($rooms),
        'totalPages' => 1,
        'from'       => count($rooms) ? 1 : 0,
        'to'         => count($rooms),
    ];
    $paginationBase = base_url('admin/ruangan');
    $paginationQuery = array_filter([
        'per_page' => (int) $filters['per_page'] !== 10 ? $filters['per_page'] : null,
    ], static fn($value) => $value !== null && $value !== '');
?>

<div class="section-card room-list">

    <div class="section-card-header">
        <div class="header-icon"><i data-lucide="door-open"></i></div>
        <div>
            <h6>Daftar Ruangan</h6>
            <p class="header-sub">
                <?= $pagination['total'] ?> ruangan terdaftar
                <?php if ($pagination['total'] > 0): ?>
                    &bull; Menampilkan <?= $pagination['from'] ?>-<?= $pagination['to'] ?>
                <?php endif; ?>
            </p>
        </div>
        <form method="get" class="ml-auto flex gap-2 items-center">
            <label class="text-xs text-gray-500 mb-0" for="ruangan-per-page">Tampilkan</label>
            <select class="ta-select ta-select-sm" id="ruangan-per-page" name="per_page" style="width:auto;" onchange="this.form.submit()">
                <?php foreach ([10, 25, 50, 100] as $size): ?>
                    <option value="<?= $size ?>" <?= (int) $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="section-card-body">
        <div class="ta-alert ta-alert-info py-2 px-3 mb-2 compact-alert">
            <i data-lucide="info" class="mr-1"></i>
            Master ini hanya untuk ruangan tetap DPRD. Tempat lain diisi melalui <strong>Lokasi Lainnya</strong> di form jadwal.
        </div>

        <?php if (empty($rooms)): ?>
            <div class="empty-state">
                <i data-lucide="door-open"></i>
                <p>Belum ada data ruangan.</p>
                <small>Klik "Tambah Ruangan" untuk mulai menambahkan data.</small>
            </div>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th class="ta-col-num">No</th>
                        <th>Nama Ruangan</th>
                        <th>Kapasitas</th>
                        <th>Status</th>
                        <th class="ta-col-action">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $i => $r): ?>
                        <tr>
                            <td>
                                <?= $pagination['from'] + $i ?>
                            </td>
                            <td>
                                <div class="cell-title">
                                    <?= esc($r['name']) ?>
                                </div>
                                <div class="cell-subtitle">
                                    <?= esc($r['keterangan'] ?? '') ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-group">
                                    <i data-lucide="users" class="mr-1"></i>
                                    <?= esc($r['kapasitas']) ?> orang
                                </span>
                            </td>
                            <td>
                                <?php if ($r['tersedia']): ?>
                                    <span class="status-badge badge-selesai">
                                        <span class="dot"></span>Tersedia
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge badge-menunggu">
                                        <span class="dot"></span>Tidak Tersedia
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                <form method="get" action="<?= base_url("admin/ruangan/{$r['id']}/edit") ?>">
                                    <button type="submit" class="ta-btn ta-btn-sm ta-btn-outline-brand" title="Edit">
                                        <i data-lucide="pencil" class="mr-1"></i>Edit
                                    </button>
                                </form>
                                <form method="get" action="<?= base_url("admin/ruangan/{$r['id']}/delete") ?>"
                                    onsubmit="return confirm('Hapus ruangan ini?')">
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
        'ariaLabel'       => 'Pagination ruangan',
    ]) ?>

</div>

<?= $this->endSection() ?>
