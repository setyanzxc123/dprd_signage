<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="page-title">Jadwal Rapat Hasil Banmus</h1>
    <a href="<?= base_url('admin/jadwal-banmus/create') ?>" class="btn btn-primary btn-sm w-full gap-1 sm:w-auto">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah Jadwal
    </a>
</div>

<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-5">
        <h2 class="card-title text-base">
            <i data-lucide="file-stack" class="h-5 w-5 text-primary"></i>
            Daftar Jadwal
        </h2>
        <span class="badge badge-ghost whitespace-nowrap"><?= count($documents) ?> dokumen</span>
    </div>

    <?php if ($documents === []): ?>
        <div class="p-4 sm:p-5">
            <div class="grid min-h-56 place-items-center rounded-box border border-dashed border-base-300 bg-base-200 p-8 text-center">
                <div>
                    <i data-lucide="calendar-range" class="mx-auto h-10 w-10 text-base-content/30"></i>
                    <h3 class="mt-3 text-base font-extrabold">Belum ada hasil Banmus</h3>
                    <p class="mt-1 text-sm text-base-content/55">Tambahkan SK beserta jadwal rapat resmi yang telah ditetapkan.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="min-w-0">
            <div class="w-full overflow-x-auto max-sm:overflow-x-visible">
                <table class="table table-zebra table-md w-full admin-data-table responsive-card-table"
                    data-admin-datatable data-dt-order='[[1,"desc"],[2,"desc"]]'>
                    <thead>
                        <tr class="bg-base-200">
                            <th class="dt-row-number no-sort">No</th>
                            <th>Tahun</th>
                            <th>Semester</th>
                            <th>Nomor SK</th>
                            <th>Jadwal Rapat</th>
                            <th>Sumber</th>
                            <th class="text-right no-sort">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr class="transition-colors hover:bg-base-200/40">
                                <td class="dt-row-number" data-label="No"></td>
                                <td data-label="Tahun" data-order="<?= (int) $document['tahun'] ?>">
                                    <span class="font-extrabold"><?= (int) $document['tahun'] ?></span>
                                </td>
                                <td data-label="Semester" data-order="<?= (int) $document['semester'] ?>">
                                    Semester <?= (int) $document['semester'] ?>
                                </td>
                                <td data-label="Nomor SK">
                                    <span class="font-extrabold"><?= esc($document['nomor_sk']) ?></span>
                                </td>
                                <td data-label="Jadwal Rapat">
                                    <span class="badge badge-outline badge-sm"><?= (int) $document['jumlah_item'] ?> jadwal</span>
                                </td>
                                <td data-label="Sumber">
                                    <span class="badge badge-ghost badge-sm">
                                        <?= ! empty($document['dokumen_file']) ? 'PDF' : 'Tautan' ?>
                                    </span>
                                </td>
                                <td data-label="Aksi">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <a href="<?= base_url("admin/jadwal-banmus/{$document['id']}/edit") ?>"
                                            class="btn btn-outline btn-primary btn-sm gap-1">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                            Edit
                                        </a>
                                        <form method="post" action="<?= base_url("admin/jadwal-banmus/{$document['id']}/delete") ?>"
                                            class="m-0 inline-flex" data-confirm-message="Hapus SK Banmus beserta seluruh jadwal rapat hasilnya?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline btn-error btn-sm gap-1">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
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
    <?php endif; ?>
</section>

<?= $this->endSection() ?>
