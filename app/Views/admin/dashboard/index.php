<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">Dashboard Ringkasan</h1>
    <p class="page-subtitle">
        <span id="page-date">—</span> &bull; Data diperbarui secara real-time
    </p>
</div>

<div class="row g-3 mb-4">

    <div class="col-xl-4 col-sm-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-calendar-check-fill"></i></div>
            <div>
                <div class="stat-value"><?= $stats['rapat_hari_ini'] ?></div>
                <div class="stat-label">Rapat Hari Ini</div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-sm-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-value"><?= $stats['wa_terkirim'] ?></div>
                <div class="stat-label">WA Terkirim</div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-sm-4">
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
            <div>
                <div class="stat-value"><?= $stats['wa_gagal'] ?></div>
                <div class="stat-label">WA Gagal</div>
            </div>
        </div>
    </div>

</div>

<!-- Tabel Agenda Rapat Terdekat -->
<div class="section-card">

    <div class="section-card-header">
        <div class="header-icon"><i class="bi bi-calendar3-week-fill"></i></div>
        <div>
            <h6>Agenda Rapat Terdekat</h6>
            <p class="header-sub" id="header-date">—</p>
        </div>
        <a href="<?= base_url('admin/jadwal/create') ?>"
           class="btn btn-sm btn-outline-primary btn-header-sm ms-auto">
            <i class="bi bi-plus-lg me-1"></i>Tambah Jadwal
        </a>
        <a href="<?= base_url('admin/jadwal') ?>"
           class="btn btn-sm btn-outline-secondary btn-header-sm">
            <i class="bi bi-calendar3-week me-1"></i>Kalender Semester
        </a>
    </div>

    <div class="section-card-body">
        <?php if (empty($meetings)): ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <p>Tidak ada rapat hari ini.</p>
            </div>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Agenda</th>
                        <th>Ruangan</th>
                        <th>Peserta</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meetings as $m):
                        $badge = status_badge($m['status']);
                    ?>
                    <tr>
                        <td>
                            <span class="time-badge"><?= esc($m['start']) ?> – <?= esc($m['end']) ?></span>
                        </td>
                        <td>
                            <div class="cell-title"><?= esc($m['title']) ?></div>
                            <div class="cell-subtitle"><?= esc($m['subtitle']) ?></div>
                        </td>
                        <td><?= esc($m['room']) ?></td>
                        <td><span class="badge-group"><?= esc($m['group']) ?></span></td>
                        <td>
                            <span class="status-badge <?= $badge['class'] ?>">
                                <span class="dot"></span><?= $badge['label'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= esc($m['detail_url']) ?>" class="btn-action btn-action-blue" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= esc($m['edit_url']) ?>" class="btn-action ms-1" title="Edit">
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const dayNames   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

    function setDate() {
        const now  = new Date();
        const str  = `${dayNames[now.getDay()]}, ${now.getDate()} ${monthNames[now.getMonth()]} ${now.getFullYear()}`;
        const els  = document.querySelectorAll('#page-date, #header-date');
        els.forEach(el => { if (el) el.textContent = str; });
    }
    setDate();
</script>
<?= $this->endSection() ?>
