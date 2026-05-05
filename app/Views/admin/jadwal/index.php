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

<div class="section-card">
    <div class="section-card-header">
        <div class="header-icon"><i class="bi bi-calendar3-week-fill"></i></div>
        <div>
            <h6>Daftar Jadwal</h6>
            <p class="header-sub">
                <?= count($meetings) ?> jadwal ditemukan
            </p>
        </div>
        <div class="ms-auto">
            <input type="date" class="form-control form-control-sm input-date"
                id="filter-date" value="<?= esc($filter_date) ?>" />
        </div>
    </div>

    <div class="section-card-body">
        <?php if (empty($meetings)): ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <p>Tidak ada jadwal rapat pada tanggal ini.</p>
                <small>Pilih tanggal lain atau klik "Tambah Jadwal".</small>
            </div>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th class="col-num">Waktu</th>
                        <th>Judul Rapat</th>
                        <th>Ruangan</th>
                        <th>Peserta</th>
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
                                    <?= esc($m['waktu_mulai']) ?> –
                                    <?= esc($m['waktu_selesai']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="cell-title">
                                    <?= esc($m['judul']) ?>
                                </div>
                                <div class="cell-subtitle">
                                    <?= esc($m['keterangan'] ?? '') ?>
                                </div>
                            </td>
                            <td>
                                <?= esc($m['ruangan']) ?>
                            </td>
                            <td><span class="badge-group">
                                    <?= esc($m['komisi_target']) ?>
                                </span></td>
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

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Filter tanggal: kirim GET param ke server saat tanggal berubah
    document.getElementById('filter-date')?.addEventListener('change', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('date', this.value);
        window.location.href = url.toString();
    });
</script>
<?= $this->endSection() ?>