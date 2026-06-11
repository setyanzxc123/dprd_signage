<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">
        <span id="page-date">—</span> &bull; Data diperbarui secara real-time
    </p>
</div>

<div class="grid grid-cols-12 gap-4 mb-4">

    <div class="xl:col-span-4 sm:col-span-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i data-lucide="calendar-check"></i></div>
            <div>
                <div class="stat-value"><?= $stats['rapat_hari_ini'] ?></div>
                <div class="stat-label">Rapat Hari Ini</div>
            </div>
        </div>
    </div>

    <div class="xl:col-span-4 sm:col-span-4">
        <div class="stat-card">
            <div class="stat-icon green"><i data-lucide="circle-check"></i></div>
            <div>
                <div class="stat-value"><?= $stats['wa_terkirim'] ?></div>
                <div class="stat-label">WA Terkirim</div>
            </div>
        </div>
    </div>

    <div class="xl:col-span-4 sm:col-span-4">
        <div class="stat-card">
            <div class="stat-icon red"><i data-lucide="circle-x"></i></div>
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
        <div class="header-icon"><i data-lucide="calendar-days"></i></div>
        <div>
            <h6>Agenda Rapat Terdekat</h6>
            <p class="header-sub" id="header-date">—</p>
        </div>
        <a href="<?= base_url('admin/jadwal/create') ?>"
           class="ta-btn ta-btn-sm ta-btn-outline-brand ta-btn-header-sm ml-auto">
            <i data-lucide="plus" class="mr-1"></i>Tambah Jadwal
        </a>
        <a href="<?= base_url('admin/jadwal') ?>"
           class="ta-btn ta-btn-sm ta-btn-outline-gray ta-btn-header-sm">
            <i data-lucide="calendar-days" class="mr-1"></i>Kalender Semester
        </a>
    </div>

    <div class="section-card-body">
        <?php if (empty($meetings)): ?>
            <div class="empty-state">
                <i data-lucide="calendar-x"></i>
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
                            <a href="<?= esc($m['detail_url']) ?>" class="ta-icon-action ta-icon-action-blue" title="Detail">
                                <i data-lucide="eye"></i>
                            </a>
                            <a href="<?= esc($m['edit_url']) ?>" class="ta-icon-action ml-1" title="Edit">
                                <i data-lucide="pencil"></i>
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
