<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex items-center justify-between gap-3">
    <div>
        <h1 class="page-title">Proyeksi Banmus</h1>
        <p class="page-subtitle">Kelola SK dan proyeksi kegiatan Banmus per semester.</p>
    </div>
    <a href="<?= base_url('admin/jadwal-banmus/create') ?>" class="btn btn-primary btn-sm gap-1">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah Proyeksi
    </a>
</div>

<section class="card card-border bg-base-100 shadow-sm">
    <div class="card-body gap-4 p-4 sm:p-5">
        <div class="flex items-center gap-3">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-box bg-base-200 text-base-content/70">
                <i data-lucide="file-stack" class="h-5 w-5"></i>
            </div>
            <div>
                <h2 class="card-title text-base">Daftar Proyeksi Banmus</h2>
                <p class="text-xs font-semibold text-base-content/55"><?= count($documents) ?> dokumen terdaftar</p>
            </div>
        </div>

        <?php if ($documents === []): ?>
            <div class="grid min-h-56 place-items-center rounded-box border border-dashed border-base-300 bg-base-200 p-8 text-center">
                <div>
                    <i data-lucide="calendar-range" class="mx-auto h-10 w-10 text-base-content/30"></i>
                    <h3 class="mt-3 text-base font-extrabold">Belum ada SK Banmus</h3>
                    <p class="mt-1 text-sm text-base-content/55">Tambahkan SK semester beserta baris kegiatannya.</p>
                    <a href="<?= base_url('admin/jadwal-banmus/create') ?>" class="btn btn-outline btn-sm mt-4">Tambah Proyeksi</a>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="table table-zebra table-md w-full admin-data-table responsive-card-table"
                    data-admin-datatable data-dt-order='[[1,"desc"],[2,"desc"]]'>
                    <thead>
                        <tr class="bg-base-200/50">
                            <th class="dt-row-number no-sort">No</th>
                            <th>Tahun</th>
                            <th>Semester</th>
                            <th>Nomor SK</th>
                            <th>Isi Jadwal</th>
                            <th>Sumber</th>
                            <th class="text-right no-sort">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr class="transition-colors hover:bg-base-200/30">
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
                                <td data-label="Isi Jadwal">
                                    <span class="badge badge-outline badge-sm"><?= (int) $document['jumlah_item'] ?> baris</span>
                                </td>
                                <td data-label="Sumber">
                                    <span class="badge badge-ghost badge-sm">
                                        <?= ! empty($document['dokumen_file']) ? 'PDF' : 'Tautan' ?>
                                    </span>
                                </td>
                                <td data-label="Aksi">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="<?= base_url("admin/jadwal-banmus/{$document['id']}/edit") ?>"
                                            class="btn btn-outline btn-primary btn-sm gap-1">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                            Edit
                                        </a>
                                        <form method="post" action="<?= base_url("admin/jadwal-banmus/{$document['id']}/delete") ?>"
                                            class="m-0 inline-flex" onsubmit="return confirm('Hapus SK Banmus beserta seluruh baris kegiatannya?')">
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
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
