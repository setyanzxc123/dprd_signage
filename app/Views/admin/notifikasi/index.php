<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">Log Notifikasi WA</h1>
    <p class="page-subtitle">Riwayat pengiriman WhatsApp otomatis ke anggota DPRD</p>
</div>

<?php
    $table_filters = $table_filters ?? ['per_page' => 10];
    $pagination = $pagination ?? [
        'page'       => 1,
        'perPage'    => 10,
        'total'      => count($notifications),
        'totalPages' => 1,
        'from'       => count($notifications) ? 1 : 0,
        'to'         => count($notifications),
    ];
    $paginationBase = base_url('admin/notifikasi');
    $paginationQuery = array_filter([
        'status' => $filter_status !== 'all' ? $filter_status : null,
        'per_page' => (int) $table_filters['per_page'] !== 10 ? $table_filters['per_page'] : null,
    ], static fn($value) => $value !== null && $value !== '');
?>

<div class="section-card">

    <div class="section-card-header">
        <div class="header-icon green"><i class="bi bi-whatsapp"></i></div>
        <div>
            <h6>Riwayat Pengiriman</h6>
            <p class="header-sub">
                <?= $pagination['total'] ?> log ditemukan
                <?php if ($pagination['total'] > 0): ?>
                    &bull; Menampilkan <?= $pagination['from'] ?>-<?= $pagination['to'] ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
            <?php foreach ($filters as $key => $f):
                $active = $filter_status === $key ? 'active' : '';
                $statusQuery = array_filter([
                    'status' => $key !== 'all' ? $key : null,
                    'per_page' => (int) $table_filters['per_page'] !== 10 ? $table_filters['per_page'] : null,
                ], static fn($value) => $value !== null && $value !== '');
                ?>
                <a href="<?= base_url('admin/notifikasi') ?><?= $statusQuery ? '?' . http_build_query($statusQuery) : '' ?>"
                   class="btn btn-sm <?= $f['class'] ?> <?= $active ?>">
                    <?= $f['label'] ?>
                </a>
            <?php endforeach; ?>
            <form method="get" class="d-flex gap-2 align-items-center">
                <?php if ($filter_status !== 'all'): ?>
                    <input type="hidden" name="status" value="<?= esc($filter_status) ?>">
                <?php endif; ?>
                <label class="small text-muted mb-0" for="notif-per-page">Tampilkan</label>
                <select class="form-select form-select-sm" id="notif-per-page" name="per_page" style="width:auto;" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= (int) $table_filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <div class="section-card-body">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="bi bi-chat-x"></i>
                <p>Tidak ada log notifikasi
                    <?= $filter_status !== 'all' ? ' dengan status ini' : '' ?>.
                </p>
                <small>Log akan muncul setelah sistem mengirim notifikasi WA.</small>
            </div>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Waktu Eksekusi</th>
                        <th>Target</th>
                        <th>Jadwal Rapat</th>
                        <th>Status</th>
                        <th class="col-action-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $n):
                        $badge = notif_config($n['status']);
                    ?>
                        <tr>
                            <td>
                                <div class="cell-title">
                                    <?= esc($n['executed_at']) ?>
                                </div>
                                <div class="cell-subtitle">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= esc($n['created_at']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">
                                    <?= esc($n['nama_anggota']) ?>
                                </div>
                                <div class="cell-subtitle">
                                    <i class="bi bi-telephone me-1"></i>
                                    <?= esc($n['no_wa']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">
                                    <?= esc($n['judul_rapat']) ?>
                                </div>
                                <div class="cell-subtitle">
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= esc($n['tanggal_rapat']) ?>
                                    &nbsp;
                                    <?= esc($n['waktu_rapat']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge <?= $badge['class'] ?>">
                                    <i class="bi <?= $badge['icon'] ?> me-1"></i>
                                    <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($n['status'] === 'failed'): ?>
                                    <form method="POST" action="<?= base_url("admin/notifikasi/{$n['id']}/resend") ?>"
                                        class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn-action btn-action-green" title="Kirim Ulang">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                <?php elseif ($n['status'] === 'pending'): ?>
                                    <form method="POST" action="<?= base_url("admin/notifikasi/{$n['id']}/cancel") ?>"
                                        class="d-inline"
                                        onsubmit="return confirm('Batalkan notifikasi ini?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn-action btn-action-red" title="Batalkan">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <small class="text-muted">—</small>
                                <?php endif; ?>
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
        'ariaLabel'       => 'Pagination notifikasi',
    ]) ?>

</div>

<?= $this->endSection() ?>
