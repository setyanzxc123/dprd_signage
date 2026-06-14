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

<?php
    $filters = $filters ?? ['q' => '', 'per_page' => 10];
    $pagination = $pagination ?? [
        'page'       => 1,
        'perPage'    => 10,
        'total'      => count($members),
        'totalPages' => 1,
        'from'       => count($members) ? 1 : 0,
        'to'         => count($members),
    ];
    $paginationBase = base_url('admin/anggota');
    $paginationQuery = array_filter([
        'q'        => $filters['q'] !== '' ? $filters['q'] : null,
        'per_page' => (int) $filters['per_page'] !== 10 ? $filters['per_page'] : null,
    ], static fn($value) => $value !== null && $value !== '');
?>

<div class="section-card">

    <div class="section-card-header">
        <div class="header-icon"><i data-lucide="users"></i></div>
        <div>
            <h6>Daftar Anggota</h6>
            <p class="header-sub">
                <?= $pagination['total'] ?> anggota terdaftar
                <?php if ($pagination['total'] > 0): ?>
                    &bull; Menampilkan <?= $pagination['from'] ?>-<?= $pagination['to'] ?>
                <?php endif; ?>
            </p>
        </div>
        <form method="get" class="ml-auto flex gap-2 items-center">
            <input type="search" class="input input-sm input-bordered w-48"
                placeholder="Cari nama anggota..." name="q" value="<?= esc($filters['q']) ?>" />
            <select class="select select-sm select-bordered" name="per_page" onchange="this.form.submit()">
                <?php foreach ([10, 25, 50, 100] as $size): ?>
                    <option value="<?= $size ?>" <?= (int) $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline btn-primary" title="Cari">
                <i data-lucide="search" class="w-4 h-4"></i>
            </button>
            <?php if ($filters['q'] !== ''): ?>
                <a href="<?= base_url('admin/anggota') ?>" class="btn btn-sm btn-outline btn-ghost" title="Reset">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="section-card-body p-0">
        <?php if (empty($members)): ?>
            <div class="empty-state p-8 text-center flex flex-col items-center justify-center">
                <i data-lucide="users" class="w-12 h-12 text-base-content/40 mb-3"></i>
                <p class="font-bold text-base-content">Belum ada data anggota.</p>
                <small class="text-base-content/60 mt-1">Klik "Tambah Anggota" untuk mulai menambahkan data.</small>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto w-full">
                <table class="table table-zebra table-md w-full" id="table-anggota">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th>No</th>
                            <th>Nama Lengkap</th>
                            <th>Fraksi</th>
                            <th>Komisi</th>
                            <th>No WhatsApp</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $i => $m): ?>
                            <tr class="hover:bg-base-200/30 transition-colors">
                                <td>
                                    <?= $pagination['from'] + $i ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar placeholder">
                                            <div class="bg-primary text-primary-content rounded-full w-8 h-8 font-bold text-sm flex items-center justify-center">
                                                <span><?= strtoupper(substr($m['name'], 0, 1)) ?></span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-bold text-base-content text-sm">
                                                <?= esc($m['name']) ?>
                                            </div>
                                            <div class="text-xs text-base-content/60 mt-0.5">
                                                <?= esc($m['jabatan'] ?? '') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-base-content/85">
                                    <?= esc($m['fraksi']) ?>
                                </td>
                                <td>
                                    <span class="badge badge-ghost h-auto py-1 px-2 text-xs">
                                        <?= esc($m['komisi']) ?>
                                    </span>
                                </td>
                                <td class="font-mono text-sm text-base-content/85">
                                    <?= esc($m['no_wa']) ?>
                                </td>
                                <td>
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
                                <td>
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
        <?php endif; ?>
    </div>

    <?= view('admin/components/_pagination', [
        'pagination'      => $pagination,
        'paginationBase'  => $paginationBase,
        'paginationQuery' => $paginationQuery,
        'ariaLabel'       => 'Pagination anggota',
    ]) ?>

</div>

<?= $this->endSection() ?>
