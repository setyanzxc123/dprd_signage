<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">Log Notifikasi WA</h1>
    <p class="page-subtitle">Riwayat pengiriman WhatsApp otomatis ke anggota DPRD</p>
</div>

<div class="section-card">
    <div class="section-card-header">
        <div class="header-icon green"><i data-lucide="message-circle"></i></div>
        <div>
            <h6>Riwayat Pengiriman</h6>
            <?php
                $scope = $notification_scope ?? [
                    'window_days' => 60,
                    'max_rows'    => 1000,
                ];
            ?>
            <p class="header-sub">
                <?= count($notifications) ?> log operasional ditampilkan
                <span class="text-base-content/50">
                    &bull; rapat sejak <?= esc($scope['window_days']) ?> hari terakhir + pending
                    &bull; maks. <?= esc($scope['max_rows']) ?> baris
                </span>
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
                $statusQuery = $key !== 'all' ? '?' . http_build_query(['status' => $key]) : '';
            ?>
                <a href="<?= base_url('admin/notifikasi') . $statusQuery ?>" class="<?= $btn_class ?>">
                    <?= $f['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section-card-body p-0">
        <div class="overflow-x-auto w-full">
            <table class="table table-zebra table-md w-full admin-data-table responsive-card-table" id="table-notifikasi" data-admin-datatable data-dt-order='[[1,"desc"]]'>
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Waktu Eksekusi</th>
                        <th>Target</th>
                        <th>Jadwal Rapat</th>
                        <th>Status</th>
                        <th class="text-right no-sort">Aksi</th>
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
                            <td class="dt-row-number" data-label="No"></td>
                            <td class="whitespace-nowrap" data-order="<?= esc($n['sort_at'] ?? '', 'attr') ?>" data-label="Waktu Eksekusi">
                                <div class="font-bold text-base-content text-sm">
                                    <?= esc($n['executed_at'] ?? '-') ?>
                                </div>
                                <div class="text-xs text-base-content/60 mt-0.5 flex items-center gap-1">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 opacity-60"></i>
                                    <?= esc($n['created_at']) ?>
                                </div>
                            </td>
                            <td data-label="Target">
                                <div class="font-bold text-base-content text-sm"><?= esc($n['nama_anggota']) ?></div>
                                <div class="text-xs text-base-content/60 mt-0.5 flex items-center gap-1 font-mono">
                                    <i data-lucide="phone" class="w-3.5 h-3.5 opacity-60"></i>
                                    <?= esc($n['no_wa']) ?>
                                </div>
                            </td>
                            <td data-label="Jadwal Rapat">
                                <div class="font-bold text-base-content text-sm line-clamp-1 max-w-xs" title="<?= esc($n['judul_rapat']) ?>">
                                    <?= esc($n['judul_rapat']) ?>
                                </div>
                                <div class="text-xs text-base-content/60 mt-0.5 flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 opacity-60"></i>
                                    <?= esc($n['tanggal_rapat']) ?> &bull; <?= esc($n['waktu_rapat']) ?>
                                </div>
                            </td>
                            <td data-label="Status">
                                <span class="badge <?= $badgeClass ?> h-auto py-1 px-2 text-xs font-semibold whitespace-nowrap gap-1">
                                    <i data-lucide="<?= esc($badge['icon'], 'attr') ?>" class="w-3 h-3"></i>
                                    <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td data-label="Aksi">
                                <div class="flex items-center justify-end gap-2">
                                    <?php if ($n['status'] === 'failed'): ?>
                                        <form method="POST" action="<?= base_url("admin/notifikasi/{$n['id']}/resend") ?>" class="inline-flex m-0">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline btn-success btn-circle" title="Kirim Ulang">
                                                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($n['status'] === 'pending'): ?>
                                        <form method="POST" action="<?= base_url("admin/notifikasi/{$n['id']}/cancel") ?>"
                                            class="inline-flex m-0" onsubmit="return confirm('Batalkan notifikasi ini?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline btn-error btn-circle" title="Batalkan">
                                                <i data-lucide="x" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-base-content/40 text-xs px-2">&mdash;</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
