<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h1 class="page-title">Ruangan Rapat</h1>
        <p class="page-subtitle">Kelola data ruangan untuk jadwal rapat</p>
    </div>
    <a href="<?= base_url('admin/ruangan/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Ruangan
    </a>
</div>

<div class="section-card">

    <div class="section-card-header">
        <div class="header-icon"><i class="bi bi-door-open-fill"></i></div>
        <div>
            <h6>Daftar Ruangan</h6>
            <p class="header-sub">
                <?= count($rooms) ?> ruangan terdaftar
            </p>
        </div>
    </div>

    <div class="section-card-body">
        <?php if (empty($rooms)): ?>
            <div class="empty-state">
                <i class="bi bi-door-open"></i>
                <p>Belum ada data ruangan.</p>
                <small>Klik "Tambah Ruangan" untuk mulai menambahkan data.</small>
            </div>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th class="col-num">No</th>
                        <th>Nama Ruangan</th>
                        <th>Kapasitas</th>
                        <th>Lantai</th>
                        <th>Status</th>
                        <th class="col-action">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $i => $r): ?>
                        <tr>
                            <td>
                                <?= $i + 1 ?>
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
                                    <i class="bi bi-people me-1"></i>
                                    <?= esc($r['kapasitas']) ?> orang
                                </span>
                            </td>
                            <td>
                                <?= esc($r['lantai'] ?? '-') ?>
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
                                <a href="<?= base_url("admin/ruangan/{$r['id']}/edit") ?>"
                                    class="btn-action btn-action-blue" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= base_url("admin/ruangan/{$r['id']}/delete") ?>"
                                    class="btn-action btn-action-red ms-1"
                                    title="Hapus"
                                    onclick="return confirm('Hapus ruangan ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

<?= $this->endSection() ?>
