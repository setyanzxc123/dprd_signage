<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h1 class="page-title">Jadwal Rapat</h1>
        <p class="page-subtitle">Kelola jadwal rapat dan notifikasi WhatsApp</p>
    </div>
    <a href="<?= base_url('admin/jadwal/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Jadwal
    </a>
</div>

<?php
    $filters = $filters ?? [
        'tahun'    => date('Y'),
        'semester' => 'all',
        'jenis'    => 'all',
            'status'   => 'all',
            'q'        => '',
            'per_page' => 10,
        ];
    $pagination = $pagination ?? [
        'page'       => 1,
        'perPage'    => 10,
        'total'      => count($meetings),
        'totalPages' => 1,
        'from'       => count($meetings) ? 1 : 0,
        'to'         => count($meetings),
    ];
    $paginationBase = base_url('admin/jadwal');
    $paginationQuery = array_filter([
        'tahun'    => $filters['tahun'],
        'semester' => $filters['semester'] !== 'all' ? $filters['semester'] : null,
        'jenis'    => $filters['jenis'] !== 'all' ? $filters['jenis'] : null,
        'status'   => $filters['status'] !== 'all' ? $filters['status'] : null,
        'q'        => $filters['q'] !== '' ? $filters['q'] : null,
        'per_page' => (int) $filters['per_page'] !== 10 ? $filters['per_page'] : null,
    ], static fn($value) => $value !== null && $value !== '');
?>

<div class="section-card">
    <div class="section-card-header">
        <div class="header-icon"><i class="bi bi-calendar3-week-fill"></i></div>
        <div>
            <h6>Daftar Jadwal</h6>
            <p class="header-sub">
                <?= $pagination['total'] ?> jadwal ditemukan
                <?php if ($pagination['total'] > 0): ?>
                    &bull; Menampilkan <?= $pagination['from'] ?>-<?= $pagination['to'] ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <form method="get" class="p-3 border-bottom">
        <div class="row g-2 align-items-end">
            <div class="col-md-2 col-6">
                <label class="form-label small text-muted fw-semibold" for="filter-tahun">Tahun</label>
                <select class="form-select form-select-sm" id="filter-tahun" name="tahun" onchange="this.form.submit()">
                    <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= (int) $filters['tahun'] === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label small text-muted fw-semibold" for="filter-semester">Semester</label>
                <select class="form-select form-select-sm" id="filter-semester" name="semester" onchange="this.form.submit()">
                    <option value="all" <?= $filters['semester'] === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="1" <?= $filters['semester'] === '1' ? 'selected' : '' ?>>Semester I</option>
                    <option value="2" <?= $filters['semester'] === '2' ? 'selected' : '' ?>>Semester II</option>
                </select>
            </div>

            <div class="col-md-2 col-6">
                <label class="form-label small text-muted fw-semibold" for="filter-jenis">Jenis</label>
                <select class="form-select form-select-sm" id="filter-jenis" name="jenis" onchange="this.form.submit()">
                    <option value="all" <?= $filters['jenis'] === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="reguler" <?= $filters['jenis'] === 'reguler' ? 'selected' : '' ?>>Reguler</option>
                    <option value="insidental" <?= $filters['jenis'] === 'insidental' ? 'selected' : '' ?>>Insidental</option>
                </select>
            </div>

            <div class="col-md-2 col-6">
                <label class="form-label small text-muted fw-semibold" for="filter-status">Status</label>
                <select class="form-select form-select-sm" id="filter-status" name="status" onchange="this.form.submit()">
                    <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="menunggu" <?= $filters['status'] === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                    <option value="persiapan" <?= $filters['status'] === 'persiapan' ? 'selected' : '' ?>>Persiapan</option>
                    <option value="berlangsung" <?= $filters['status'] === 'berlangsung' ? 'selected' : '' ?>>Berlangsung</option>
                    <option value="selesai" <?= $filters['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted fw-semibold" for="filter-q">Cari</label>
                <input class="form-control form-control-sm" id="filter-q" name="q" value="<?= esc($filters['q']) ?>"
                    placeholder="Agenda, ruangan, peserta">
            </div>
            <div class="col-md-1 col-6">
                <label class="form-label small text-muted fw-semibold" for="filter-per-page">Tampil</label>
                <select class="form-select form-select-sm" id="filter-per-page" name="per_page" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= (int) $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" type="submit" title="Terapkan filter">
                    <i class="bi bi-search"></i>
                </button>
                <a href="<?= base_url('admin/jadwal') ?>" class="btn btn-sm btn-outline-secondary" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </div>
    </form>

    <div class="section-card-body">
        <?php if (empty($meetings)): ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <p>Tidak ada jadwal rapat sesuai filter.</p>
                <small>Ubah filter atau klik "Tambah Jadwal".</small>
            </div>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th class="col-num">Tanggal & Waktu</th>
                        <th>Judul Rapat</th>
                        <th>Ruangan</th>
                        <th>Peserta</th>
                        <th>Jenis</th>
                        <th>Publik</th>
                        <th>Status</th>
                        <th class="col-action">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meetings as $m):
                        $badge = status_badge($m['status']);
                    ?>
                        <tr>
                            <td>
                                <span class="time-badge">
                                    <?= esc(date('d/m/Y', strtotime($m['tanggal']))) ?>
                                </span>
                                <div class="cell-subtitle mt-1">
                                    <?= esc($m['waktu_mulai']) ?> -
                                    <?= esc($m['waktu_selesai']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title"><?= esc($m['judul']) ?></div>
                                <div class="cell-subtitle"><?= esc($m['keterangan'] ?? '') ?></div>
                            </td>
                            <td><?= esc($m['ruangan']) ?></td>
                            <td><span class="badge-group"><?= esc($m['target_peserta']) ?></span></td>
                            <td>
                                <?php if (($m['jenis'] ?? 'insidental') === 'reguler'): ?>
                                    <span class="badge bg-primary-subtle text-primary" style="font-size:.7rem;">Reguler</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:.7rem;">Insidental</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button
                                    class="btn-toggle-publik btn btn-sm <?= ($m['is_publik'] ?? 0) ? 'btn-success' : 'btn-outline-secondary' ?>"
                                    data-id="<?= $m['id'] ?>"
                                    title="<?= ($m['is_publik'] ?? 0) ? 'Tampil di Publik (klik untuk sembunyikan)' : 'Hanya Internal (klik untuk tampilkan)' ?>"
                                    style="font-size:.7rem; padding:.2rem .5rem;">
                                    <?= ($m['is_publik'] ?? 0) ? 'Publik' : 'Internal' ?>
                                </button>
                            </td>
                            <td>
                                <span class="status-badge <?= $badge['class'] ?>">
                                    <span class="dot"></span>
                                    <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= base_url("admin/jadwal/{$m['id']}/edit") ?>"
                                    class="btn-action btn-action-blue" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= base_url("admin/jadwal/{$m['id']}/delete") ?>"
                                    class="btn-action btn-action-red ms-1"
                                    title="Hapus"
                                    onclick="return confirm('Hapus jadwal ini?')">
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
        'ariaLabel'       => 'Pagination jadwal',
    ]) ?>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Toggle is_publik via AJAX
    document.querySelectorAll('.btn-toggle-publik').forEach(btn => {
        btn.addEventListener('click', function() {
            const id  = this.dataset.id;
            const btn = this;
            fetch(`/admin/jadwal/${id}/toggle-publik`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
                body: JSON.stringify({ '<?= csrf_token() ?>': '<?= csrf_hash() ?>' })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if (res.is_publik) {
                        btn.textContent = 'Publik';
                        btn.className = btn.className.replace('btn-outline-secondary', 'btn-success');
                        btn.title = 'Tampil di Publik (klik untuk sembunyikan)';
                    } else {
                        btn.textContent = 'Internal';
                        btn.className = btn.className.replace('btn-success', 'btn-outline-secondary');
                        btn.title = 'Hanya Internal (klik untuk tampilkan)';
                    }
                }
            })
            .catch(err => console.error('Toggle gagal:', err));
        });
    });
</script>
<?= $this->endSection() ?>
