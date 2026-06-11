<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex items-center justify-between">
    <div>
        <h1 class="page-title">Jadwal Rapat</h1>
        <p class="page-subtitle">Kelola jadwal rapat dan notifikasi WhatsApp</p>
    </div>
    <a href="<?= base_url('admin/jadwal/create') ?>" class="ta-btn ta-btn-primary ta-btn-sm">
        <i data-lucide="plus" class="mr-1"></i>Tambah Jadwal
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
        <div class="header-icon"><i data-lucide="calendar-days"></i></div>
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

    <form method="get" class="p-3 border-b">
        <div class="grid grid-cols-12 gap-3 items-end">
            <div class="md:col-span-2 col-span-6">
                <label class="ta-label text-xs text-gray-500 font-semibold" for="filter-tahun">Tahun</label>
                <select class="ta-select ta-select-sm" id="filter-tahun" name="tahun" onchange="this.form.submit()">
                    <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= (int) $filters['tahun'] === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="md:col-span-2 col-span-6">
                <label class="ta-label text-xs text-gray-500 font-semibold" for="filter-semester">Semester</label>
                <select class="ta-select ta-select-sm" id="filter-semester" name="semester" onchange="this.form.submit()">
                    <option value="all" <?= $filters['semester'] === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="1" <?= $filters['semester'] === '1' ? 'selected' : '' ?>>Semester I</option>
                    <option value="2" <?= $filters['semester'] === '2' ? 'selected' : '' ?>>Semester II</option>
                </select>
            </div>

            <div class="md:col-span-2 col-span-6">
                <label class="ta-label text-xs text-gray-500 font-semibold" for="filter-jenis">Jenis</label>
                <select class="ta-select ta-select-sm" id="filter-jenis" name="jenis" onchange="this.form.submit()">
                    <option value="all" <?= $filters['jenis'] === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="reguler" <?= $filters['jenis'] === 'reguler' ? 'selected' : '' ?>>Reguler</option>
                    <option value="insidental" <?= $filters['jenis'] === 'insidental' ? 'selected' : '' ?>>Insidental</option>
                </select>
            </div>

            <div class="md:col-span-2 col-span-6">
                <label class="ta-label text-xs text-gray-500 font-semibold" for="filter-status">Status</label>
                <select class="ta-select ta-select-sm" id="filter-status" name="status" onchange="this.form.submit()">
                    <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="menunggu" <?= $filters['status'] === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                    <option value="persiapan" <?= $filters['status'] === 'persiapan' ? 'selected' : '' ?>>Persiapan</option>
                    <option value="berlangsung" <?= $filters['status'] === 'berlangsung' ? 'selected' : '' ?>>Berlangsung</option>
                    <option value="selesai" <?= $filters['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="ta-label text-xs text-gray-500 font-semibold" for="filter-q">Cari</label>
                <input class="ta-input ta-input-sm" id="filter-q" name="q" value="<?= esc($filters['q']) ?>"
                    placeholder="Agenda, ruangan, peserta">
            </div>
            <div class="md:col-span-1 col-span-6">
                <label class="ta-label text-xs text-gray-500 font-semibold" for="filter-per-page">Tampil</label>
                <select class="ta-select ta-select-sm" id="filter-per-page" name="per_page" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= (int) $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-1 flex gap-2">
                <button class="ta-btn ta-btn-sm ta-btn-outline-brand" type="submit" title="Terapkan filter">
                    <i data-lucide="search"></i>
                </button>
                <a href="<?= base_url('admin/jadwal') ?>" class="ta-btn ta-btn-sm ta-btn-outline-gray" title="Reset filter">
                    <i data-lucide="rotate-ccw"></i>
                </a>
            </div>
        </div>
    </form>

    <div class="section-card-body">
        <?php if (empty($meetings)): ?>
            <div class="empty-state">
                <i data-lucide="calendar-x"></i>
                <p>Tidak ada jadwal rapat sesuai filter.</p>
                <small>Ubah filter atau klik "Tambah Jadwal".</small>
            </div>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th class="ta-col-num">Tanggal & Waktu</th>
                        <th>Judul Rapat</th>
                        <th>Ruangan</th>
                        <th>Peserta</th>
                        <th>Jenis</th>
                        <th>Publik</th>
                        <th>Status</th>
                        <th class="ta-col-action">Aksi</th>
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
                                    <span class="ta-badge bg-brand-50 text-brand-600" style="font-size:.7rem;">Reguler</span>
                                <?php else: ?>
                                    <span class="ta-badge bg-gray-100 text-gray-500" style="font-size:.7rem;">Insidental</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($m['is_publik'] ?? 0): ?>
                                    <span class="ta-badge bg-emerald-50 text-emerald-600" title="Ubah publikasi melalui halaman edit" style="font-size:.7rem;">Publik</span>
                                <?php else: ?>
                                    <span class="ta-badge bg-gray-100 text-gray-500" title="Ubah publikasi melalui halaman edit" style="font-size:.7rem;">Internal</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $badge['class'] ?>">
                                    <span class="dot"></span>
                                    <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                <form method="get" action="<?= base_url("admin/jadwal/{$m['id']}/edit") ?>">
                                    <button type="submit" class="ta-btn ta-btn-sm ta-btn-outline-brand" title="Edit">
                                        <i data-lucide="pencil" class="mr-1"></i>Edit
                                    </button>
                                </form>
                                <form method="get" action="<?= base_url("admin/jadwal/{$m['id']}/delete") ?>"
                                    onsubmit="return confirm('Hapus jadwal ini?')">
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
        'ariaLabel'       => 'Pagination jadwal',
    ]) ?>

</div>

<?= $this->endSection() ?>
