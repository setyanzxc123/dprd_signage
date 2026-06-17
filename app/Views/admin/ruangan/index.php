<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex items-center justify-between">
    <div>
        <h1 class="page-title">Ruangan Rapat</h1>
        <p class="page-subtitle">Kelola ruangan tetap DPRD untuk jadwal rapat</p>
    </div>
    <a href="<?= base_url('admin/ruangan/create') ?>" class="btn btn-sm btn-primary gap-1">
        <i data-lucide="plus" class="w-4 h-4"></i>Tambah Ruangan
    </a>
</div>

<div class="section-card room-list">
    <div class="section-card-header">
        <div class="header-icon"><i data-lucide="door-open"></i></div>
        <div>
            <h6>Daftar Ruangan</h6>
            <?php $scope = $data_scope ?? ['label' => 'seluruh master ruangan tetap']; ?>
            <p class="header-sub">
                <?= count($rooms) ?> ruangan terdaftar
                <span class="text-base-content/50">
                    &bull; <?= esc($scope['label']) ?>
                </span>
            </p>
        </div>
    </div>

    <div class="section-card-body pt-3.5">
        <div class="alert alert-info py-2 px-3 mb-2 text-xs flex gap-2">
            <i data-lucide="info" class="w-4 h-4"></i>
            <span>Master ini hanya untuk ruangan tetap DPRD. Tempat lain diisi melalui <strong>Lokasi Lainnya</strong> di form jadwal.</span>
        </div>

        <div class="overflow-x-auto w-full">
            <table class="table table-zebra table-md w-full admin-data-table responsive-card-table" data-admin-datatable data-dt-order='[[1,"asc"]]'>
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Nama Ruangan</th>
                        <th>Kapasitas</th>
                        <th>Status</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $r): ?>
                        <tr class="hover:bg-base-200/30 transition-colors">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Nama Ruangan">
                                <div class="font-bold text-base-content text-sm"><?= esc($r['name']) ?></div>
                                <div class="text-xs text-base-content/60 mt-0.5 max-w-sm truncate" title="<?= esc($r['keterangan'] ?? '') ?>">
                                    <?= esc($r['keterangan'] ?? '') ?>
                                </div>
                            </td>
                            <td data-label="Kapasitas">
                                <span class="badge badge-ghost h-auto py-1 px-2 text-xs gap-1">
                                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                    <?= esc($r['kapasitas']) ?> orang
                                </span>
                            </td>
                            <td data-label="Status">
                                <?php if ($r['tersedia']): ?>
                                    <span class="badge badge-success h-auto py-0.5 px-2 text-xs font-semibold whitespace-nowrap gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        Tersedia
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-ghost h-auto py-0.5 px-2 text-xs font-semibold whitespace-nowrap text-base-content/60 gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        Tidak Tersedia
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= base_url("admin/ruangan/{$r['id']}/edit") ?>" class="btn btn-sm btn-outline btn-primary gap-1" title="Edit">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>Edit
                                    </a>
                                    <form method="get" action="<?= base_url("admin/ruangan/{$r['id']}/delete") ?>"
                                        onsubmit="return confirm('Hapus ruangan ini?')" class="inline-flex m-0">
                                        <button type="submit" class="btn btn-sm btn-outline btn-error gap-1" title="Hapus">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>Hapus
                                        </button>
                                    </form>
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
