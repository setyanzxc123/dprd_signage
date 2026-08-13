<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="page-title">Kelompok Peserta</h1>
    <a href="<?= base_url('admin/unit-rapat/create') ?>" class="btn btn-primary btn-sm w-full gap-1 sm:w-auto">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah Kelompok
    </a>
</div>

<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-5">
        <h2 class="card-title text-base">
            <i data-lucide="workflow" class="h-5 w-5 text-primary"></i>
            Daftar Kelompok
        </h2>
        <span class="badge badge-ghost whitespace-nowrap"><?= count($units) ?> kelompok</span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto">
            <table class="table table-zebra table-md w-full admin-data-table" data-admin-datatable data-dt-order='[[1,"asc"]]'>
                <thead>
                    <tr class="bg-base-200">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Nama Kelompok</th>
                        <th>Status</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($units as $unit): ?>
                        <tr class="transition-colors hover:bg-base-200/40">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Nama Kelompok">
                                <div class="text-sm font-bold text-base-content"><?= esc($unit['nama']) ?></div>
                            </td>
                            <td data-label="Status">
                                <?php if ($unit['aktif']): ?>
                                    <span class="badge badge-success h-auto gap-1.5 whitespace-nowrap px-2 py-0.5 text-xs font-semibold">
                                        <span class="status status-success"></span>
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-ghost h-auto gap-1.5 whitespace-nowrap px-2 py-0.5 text-xs font-semibold text-base-content/60">
                                        <span class="status"></span>
                                        Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= base_url("admin/unit-rapat/{$unit['id']}/edit") ?>" class="btn btn-outline btn-primary btn-sm gap-1">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Edit
                                    </a>
                                    <?php if ($unit['aktif']): ?>
                                        <form method="post" action="<?= base_url("admin/unit-rapat/{$unit['id']}/delete") ?>"
                                            data-confirm-message="Nonaktifkan kelompok peserta ini? Kelompok tidak muncul di pilihan jadwal baru, tetapi riwayat jadwal lama tetap aman."
                                            class="m-0 inline-flex">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline btn-error btn-sm gap-1">
                                                <i data-lucide="circle-off" class="h-4 w-4"></i>
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
