<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex items-center justify-between">
    <div>
        <h1 class="page-title">Anggota DPRD</h1>
        <p class="page-subtitle">Kelola data anggota dan nomor WhatsApp</p>
    </div>
    <a href="<?= base_url('admin/anggota/create') ?>" class="btn btn-sm btn-primary gap-1">
        <i data-lucide="plus" class="w-4 h-4"></i>Tambah Anggota
    </a>
</div>

<div class="section-card">
    <div class="section-card-header">
        <div class="header-icon"><i data-lucide="users"></i></div>
        <div>
            <h6>Daftar Anggota</h6>
            <?php $scope = $data_scope ?? ['label' => 'seluruh master anggota']; ?>
            <p class="header-sub">
                <?= count($members) ?> anggota terdaftar
                <span class="text-base-content/50">
                    &bull; <?= esc($scope['label']) ?>
                </span>
            </p>
        </div>
    </div>

    <div class="section-card-body p-0">
        <div class="overflow-x-auto w-full">
            <table class="table table-zebra table-md w-full admin-data-table responsive-card-table" id="table-anggota" data-admin-datatable data-dt-order='[[1,"asc"]]'>
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Nama Lengkap</th>
                        <th>Fraksi</th>
                        <th>Komisi</th>
                        <th>No WhatsApp</th>
                        <th>Status</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr class="hover:bg-base-200/30 transition-colors">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Nama Lengkap">
                                <div class="flex items-center gap-3">
                                    <div class="avatar placeholder">
                                        <div class="bg-primary text-primary-content rounded-full w-8 h-8 font-bold text-sm flex items-center justify-center">
                                            <span><?= strtoupper(substr($m['name'], 0, 1)) ?></span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-bold text-base-content text-sm"><?= esc($m['name']) ?></div>
                                        <div class="text-xs text-base-content/60 mt-0.5"><?= esc($m['jabatan'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Fraksi" class="text-base-content/85"><?= esc($m['fraksi']) ?></td>
                            <td data-label="Komisi">
                                <span class="badge badge-ghost h-auto py-1 px-2 text-xs">
                                    <?= esc($m['komisi']) ?>
                                </span>
                            </td>
                            <td data-label="No WhatsApp" class="font-mono text-sm text-base-content/85"><?= esc($m['no_wa']) ?></td>
                            <td data-label="Status">
                                <?php if ($m['aktif']): ?>
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
                                    <a href="<?= base_url("admin/anggota/{$m['id']}/edit") ?>" class="btn btn-sm btn-outline btn-primary gap-1" title="Edit">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>Edit
                                    </a>
                                    <form method="get" action="<?= base_url("admin/anggota/{$m['id']}/delete") ?>"
                                        onsubmit="return confirm('Hapus anggota ini?')" class="inline-flex m-0">
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
