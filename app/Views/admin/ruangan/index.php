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

<?php
    $filters = $filters ?? ['per_page' => 10];
    $pagination = $pagination ?? [
        'page'       => 1,
        'perPage'    => 10,
        'total'      => count($rooms),
        'totalPages' => 1,
        'from'       => count($rooms) ? 1 : 0,
        'to'         => count($rooms),
    ];
    $paginationBase = base_url('admin/ruangan');
    $paginationQuery = array_filter([
        'per_page' => (int) $filters['per_page'] !== 10 ? $filters['per_page'] : null,
    ], static fn($value) => $value !== null && $value !== '');
?>

<div class="section-card room-list">

    <div class="section-card-header">
        <div class="header-icon"><i data-lucide="door-open"></i></div>
        <div>
            <h6>Daftar Ruangan</h6>
            <p class="header-sub">
                <?= $pagination['total'] ?> ruangan terdaftar
                <?php if ($pagination['total'] > 0): ?>
                    &bull; Menampilkan <?= $pagination['from'] ?>-<?= $pagination['to'] ?>
                <?php endif; ?>
            </p>
        </div>
        <form method="get" class="ml-auto flex gap-2 items-center">
            <label class="text-xs text-base-content/60 mb-0" for="ruangan-per-page">Tampilkan</label>
            <select class="select select-sm select-bordered" id="ruangan-per-page" name="per_page" onchange="this.form.submit()">
                <?php foreach ([10, 25, 50, 100] as $size): ?>
                    <option value="<?= $size ?>" <?= (int) $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="section-card-body pt-3.5">
        <div class="alert alert-info py-2 px-3 mb-2 text-xs flex gap-2">
            <i data-lucide="info" class="w-4 h-4"></i>
            <span>Master ini hanya untuk ruangan tetap DPRD. Tempat lain diisi melalui <strong>Lokasi Lainnya</strong> di form jadwal.</span>
        </div>

        <?php if (empty($rooms)): ?>
            <div class="empty-state p-8 text-center flex flex-col items-center justify-center">
                <i data-lucide="door-open" class="w-12 h-12 text-base-content/40 mb-3"></i>
                <p class="font-bold text-base-content">Belum ada data ruangan.</p>
                <small class="text-base-content/60 mt-1">Klik "Tambah Ruangan" untuk mulai menambahkan data.</small>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto w-full">
                <table class="table table-zebra table-md w-full">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th>No</th>
                            <th>Nama Ruangan</th>
                            <th>Kapasitas</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $i => $r): ?>
                            <tr class="hover:bg-base-200/30 transition-colors">
                                <td>
                                    <?= $pagination['from'] + $i ?>
                                </td>
                                <td>
                                    <div class="font-bold text-base-content text-sm">
                                        <?= esc($r['name']) ?>
                                    </div>
                                    <div class="text-xs text-base-content/60 mt-0.5 max-w-sm truncate" title="<?= esc($r['keterangan'] ?? '') ?>">
                                        <?= esc($r['keterangan'] ?? '') ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-ghost h-auto py-1 px-2 text-xs gap-1">
                                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                        <?= esc($r['kapasitas']) ?> orang
                                    </span>
                                </td>
                                <td>
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
                                <td>
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
        <?php endif; ?>
    </div>

    <?= view('admin/components/_pagination', [
        'pagination'      => $pagination,
        'paginationBase'  => $paginationBase,
        'paginationQuery' => $paginationQuery,
        'ariaLabel'       => 'Pagination ruangan',
    ]) ?>

</div>

<?= $this->endSection() ?>
