<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex items-center justify-between unit-rapat-index-header">
    <div>
        <h1 class="page-title">Kelompok Peserta</h1>
        <p class="page-subtitle">Kelola kelompok internal DPRD untuk peserta rapat</p>
    </div>
    <a href="<?= base_url('admin/unit-rapat/create') ?>" class="btn btn-primary btn-sm gap-1 unit-rapat-add-button">
        <i data-lucide="plus" class="w-4 h-4"></i>Tambah Kelompok
    </a>
</div>

<div class="section-card unit-rapat-card">
    <div class="section-card-header">
        <div class="header-icon"><i data-lucide="workflow"></i></div>
        <div>
            <h6>Daftar Kelompok Peserta</h6>
            <?php $scope = $data_scope ?? ['label' => 'seluruh kelompok, termasuk nonaktif']; ?>
            <p class="header-sub">
                <?= count($units) ?> kelompok terdaftar
                <span class="text-base-content/50">
                    &bull; <?= esc($scope['label']) ?>
                </span>
            </p>
        </div>
    </div>

    <div class="section-card-body p-0">
        <div class="overflow-x-auto w-full unit-rapat-table-container">
            <table class="table table-zebra table-md w-full admin-data-table responsive-card-table" data-admin-datatable data-dt-order='[[1,"asc"]]'>
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Nama Kelompok</th>
                        <th>Status</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($units as $unit): ?>
                        <tr class="hover:bg-base-200/30 transition-colors">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Nama Kelompok">
                                <div class="font-bold text-base-content text-sm"><?= esc($unit['nama']) ?></div>
                            </td>
                            <td data-label="Status">
                                <?php if ($unit['aktif']): ?>
                                    <span class="badge badge-success h-auto py-0.5 px-2 text-xs font-semibold whitespace-nowrap gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-ghost h-auto py-0.5 px-2 text-xs font-semibold whitespace-nowrap text-base-content/60 gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= base_url("admin/unit-rapat/{$unit['id']}/edit") ?>" class="btn btn-sm btn-outline btn-primary gap-1" title="Edit">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>Edit
                                    </a>
                                    <form method="get" action="<?= base_url("admin/unit-rapat/{$unit['id']}/delete") ?>"
                                        onsubmit="return confirm('Nonaktifkan kelompok peserta ini? Kelompok tidak muncul di pilihan jadwal baru, tetapi riwayat jadwal lama tetap aman.')"
                                        class="inline-flex m-0">
                                        <button type="submit" class="btn btn-sm btn-outline btn-error gap-1" title="Nonaktifkan">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>Nonaktif
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
