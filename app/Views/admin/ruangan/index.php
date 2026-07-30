<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="page-title">Ruangan Rapat</h1>
    <a href="<?= base_url('admin/ruangan/create') ?>" class="btn btn-primary btn-sm w-full gap-1 sm:w-auto">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah Ruangan
    </a>
</div>

<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-5">
        <h2 class="card-title text-base">
            <i data-lucide="door-open" class="h-5 w-5 text-primary"></i>
            Daftar Ruangan
        </h2>
        <span class="badge badge-ghost whitespace-nowrap"><?= count($rooms) ?> ruangan</span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto">
            <table class="table table-zebra table-md w-full admin-data-table responsive-card-table" data-admin-datatable data-dt-order='[[1,"asc"]]'>
                <thead>
                    <tr class="bg-base-200">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Nama Ruangan</th>
                        <th>Kapasitas</th>
                        <th>Status</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $r): ?>
                        <tr class="transition-colors hover:bg-base-200/40">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Nama Ruangan">
                                <div class="text-sm font-bold text-base-content"><?= esc($r['name']) ?></div>
                                <?php if (! empty($r['keterangan'])): ?>
                                    <div class="mt-0.5 max-w-sm truncate text-xs text-base-content/60" title="<?= esc($r['keterangan']) ?>">
                                        <?= esc($r['keterangan']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Kapasitas">
                                <span class="badge badge-ghost h-auto gap-1 px-2 py-1 text-xs">
                                    <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                    <?= esc($r['kapasitas']) ?> orang
                                </span>
                            </td>
                            <td data-label="Status">
                                <?php if ($r['tersedia']): ?>
                                    <span class="badge badge-success h-auto gap-1.5 whitespace-nowrap px-2 py-0.5 text-xs font-semibold">
                                        <span class="status status-success"></span>
                                        Tersedia
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-ghost h-auto gap-1.5 whitespace-nowrap px-2 py-0.5 text-xs font-semibold text-base-content/60">
                                        <span class="status"></span>
                                        Tidak Tersedia
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <a href="<?= base_url("admin/ruangan/{$r['id']}/edit") ?>" class="btn btn-xs w-20 gap-1">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </a>
                                    <form method="post" action="<?= base_url("admin/ruangan/{$r['id']}/delete") ?>"
                                        data-confirm-message="Hapus ruangan ini?" class="m-0 inline-flex">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-error btn-xs w-20 gap-1">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            Hapus
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
</section>

<?= $this->endSection() ?>
