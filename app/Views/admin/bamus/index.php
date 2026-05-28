<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-title">📅 Jadwal Bamus</h1>
        <p class="page-subtitle">Proyeksi agenda dewan Semester <?= $semester ?> Tahun <?= $tahun ?></p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="get" class="d-flex gap-2 align-items-center" id="bamus-filter-form">
            <select name="semester" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="1" <?= $semester == 1 ? 'selected' : '' ?>>Semester I (Jan–Jun)</option>
                <option value="2" <?= $semester == 2 ? 'selected' : '' ?>>Semester II (Jul–Des)</option>
            </select>
            <select name="tahun" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                    <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
        <a href="<?= base_url('admin/jadwal/create') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Tambah Jadwal Bamus
        </a>
    </div>
</div>

<!-- Summary -->
<div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 mb-3" style="font-size:.875rem;">
    <i class="bi bi-info-circle-fill"></i>
    Menampilkan <strong><?= $totalRapat ?> jadwal</strong> berjenis "Bamus" pada Semester <?= $semester ?> (<?= $namaBulan[$bulanMulai] ?> – <?= $namaBulan[$bulanAkhir] ?> <?= $tahun ?>).
    Untuk menambah atau mengedit jadwal, gunakan menu <a href="<?= base_url('admin/jadwal') ?>" class="alert-link">Jadwal Rapat</a>.
</div>

<?php if ($totalRapat === 0): ?>
    <div class="section-card">
        <div class="section-card-body">
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <p>Belum ada jadwal Bamus untuk semester ini.</p>
                <small>Tambah jadwal baru dan pilih jenis "Bamus" agar tampil di sini.</small>
            </div>
        </div>
    </div>
<?php else: ?>

    <?php for ($b = $bulanMulai; $b <= $bulanAkhir; $b++): ?>
        <div class="section-card mb-3">
            <div class="section-card-header">
                <div class="header-icon"><i class="bi bi-calendar3-fill"></i></div>
                <div>
                    <h6><?= $namaBulan[$b] ?> <?= $tahun ?></h6>
                    <p class="header-sub">
                        <?= isset($byBulan[$b]) ? count($byBulan[$b]) . ' jadwal' : 'Tidak ada jadwal' ?>
                    </p>
                </div>
            </div>
            <div class="section-card-body">
                <?php if (empty($byBulan[$b])): ?>
                    <div class="text-muted small py-2 px-1">Tidak ada jadwal Bamus bulan ini.</div>
                <?php else: ?>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Agenda</th>
                                <th>Ruangan</th>
                                <th>Peserta</th>
                                <th>Status</th>
                                <th class="col-action">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($byBulan[$b] as $item):
                                $badge = status_badge($item['status']);
                            ?>
                            <tr>
                                <td>
                                    <span class="time-badge">
                                        <?= date('d', strtotime($item['tanggal'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="time-badge">
                                        <?= esc($item['waktu_mulai']) ?> – <?= esc($item['waktu_selesai']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="cell-title"><?= esc($item['judul']) ?></div>
                                </td>
                                <td><?= esc($item['ruangan']) ?></td>
                                <td><span class="badge-group"><?= esc($item['komisi']) ?></span></td>
                                <td>
                                    <span class="status-badge <?= $badge['class'] ?>">
                                        <span class="dot"></span><?= $badge['label'] ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url("admin/jadwal/{$item['id']}/edit") ?>"
                                       class="btn-action btn-action-blue" title="Edit Jadwal">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endfor; ?>

<?php endif; ?>

<?= $this->endSection() ?>
