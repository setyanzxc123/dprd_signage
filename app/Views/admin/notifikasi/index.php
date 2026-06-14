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
        <div class="header-icon green"><i data-lucide="message-circle"></i></div>
        <div>
            <h6>Riwayat Pengiriman</h6>
            <p class="header-sub">
                <?= $pagination['total'] ?> log ditemukan
                <?php if ($pagination['total'] > 0): ?>
                    &bull; Menampilkan <?= $pagination['from'] ?>-<?= $pagination['to'] ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="ml-auto flex gap-2 items-center flex-wrap">
            <?php foreach ($filters as $key => $f):
                $btn_class = 'btn btn-sm ';
                if ($filter_status === $key) {
                    $btn_class .= match ($key) {
                        'all'     => 'btn-neutral',
                        'sent'    => 'btn-success text-success-content',
                        'failed'  => 'btn-error text-error-content',
                        'pending' => 'btn-warning text-warning-content',
                    };
                } else {
                    $btn_class .= match ($key) {
                        'all'     => 'btn-outline btn-neutral',
                        'sent'    => 'btn-outline btn-success',
                        'failed'  => 'btn-outline btn-error',
                        'pending' => 'btn-outline btn-warning',
                    };
                }
                $statusQuery = array_filter([
                    'status' => $key !== 'all' ? $key : null,
                    'per_page' => (int) $table_filters['per_page'] !== 10 ? $table_filters['per_page'] : null,
                ], static fn($value) => $value !== null && $value !== '');
                ?>
                <a href="<?= base_url('admin/notifikasi') ?><?= $statusQuery ? '?' . http_build_query($statusQuery) : '' ?>"
                   class="<?= $btn_class ?>">
                    <?= $f['label'] ?>
                </a>
            <?php endforeach; ?>
            <form method="get" class="flex gap-2 items-center">
                <?php if ($filter_status !== 'all'): ?>
                    <input type="hidden" name="status" value="<?= esc($filter_status) ?>">
                <?php endif; ?>
                <label class="text-xs text-base-content/60 mb-0" for="notif-per-page">Tampilkan</label>
                <select class="select select-sm select-bordered" id="notif-per-page" name="per_page" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= (int) $table_filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <div class="section-card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="empty-state p-8 text-center flex flex-col items-center justify-center">
                <i data-lucide="message-circle-x" class="w-12 h-12 text-base-content/40 mb-3"></i>
                <p class="font-bold text-base-content">Tidak ada log notifikasi<?= $filter_status !== 'all' ? ' dengan status ini' : '' ?>.</p>
                <small class="text-base-content/60 mt-1">Log akan muncul setelah sistem mengirim notifikasi WA.</small>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto w-full">
                <table class="table table-zebra table-md w-full">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th>Waktu Eksekusi</th>
                            <th>Target</th>
                            <th>Jadwal Rapat</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $n):
                            $badge = notif_config($n['status']);
                            $badgeClass = match ($n['status']) {
                                'sent'    => 'badge-success',
                                'failed'  => 'badge-error',
                                'pending' => 'badge-warning',
                                default   => 'badge-ghost',
                            };
                        ?>
                            <tr class="hover:bg-base-200/30 transition-colors">
                                <td class="whitespace-nowrap">
                                    <div class="font-bold text-base-content text-sm">
                                        <?= esc($n['executed_at']) ?>
                                    </div>
                                    <div class="text-xs text-base-content/60 mt-0.5 flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 opacity-60"></i>
                                        <?= esc($n['created_at']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-bold text-base-content text-sm">
                                        <?= esc($n['nama_anggota']) ?>
                                    </div>
                                    <div class="text-xs text-base-content/60 mt-0.5 flex items-center gap-1 font-mono">
                                        <i data-lucide="phone" class="w-3.5 h-3.5 opacity-60"></i>
                                        <?= esc($n['no_wa']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-bold text-base-content text-sm line-clamp-1 max-w-xs" title="<?= esc($n['judul_rapat']) ?>">
                                        <?= esc($n['judul_rapat']) ?>
                                    </div>
                                    <div class="text-xs text-base-content/60 mt-0.5 flex items-center gap-1">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 opacity-60"></i>
                                        <?= esc($n['tanggal_rapat']) ?> &bull; <?= esc($n['waktu_rapat']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?> h-auto py-1 px-2 text-xs font-semibold whitespace-nowrap gap-1">
                                        <i data-lucide="<?= esc($badge['icon'], 'attr') ?>" class="w-3 h-3"></i>
                                        <?= $badge['label'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($n['status'] === 'failed'): ?>
                                            <form method="POST" action="<?= base_url("admin/notifikasi/{$n['id']}/resend") ?>"
                                                class="inline-flex m-0">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline btn-success btn-circle" title="Kirim Ulang">
                                                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        <?php elseif ($n['status'] === 'pending'): ?>
                                            <form method="POST" action="<?= base_url("admin/notifikasi/{$n['id']}/cancel") ?>"
                                                class="inline-flex m-0"
                                                onsubmit="return confirm('Batalkan notifikasi ini?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline btn-error btn-circle" title="Batalkan">
                                                    <i data-lucide="x" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-base-content/40 text-xs px-2">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
