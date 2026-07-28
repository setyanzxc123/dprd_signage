<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-base-content/50">Agenda Internal DPRD</p>
        <h1 class="page-title">Agenda Banmus</h1>
        <p class="mt-1 text-sm text-base-content/60">Daftar Surat Keputusan (SK) Badan Musyawarah DPRD.</p>
    </div>
    <a href="<?= base_url('admin/jadwal-banmus/create') ?>" class="btn btn-primary btn-sm w-full gap-1 sm:w-auto">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah SK Banmus
    </a>
</div>

<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-5">
        <h2 class="card-title text-base">
            <i data-lucide="file-stack" class="h-5 w-5 text-primary"></i>
            Daftar SK Banmus
        </h2>
        <span class="badge badge-ghost whitespace-nowrap"><?= count($documents) ?> Dokumen SK</span>
    </div>

    <?php if ($documents === []): ?>
        <div class="p-8 text-center text-base-content/60">
            <i data-lucide="file-text" class="mx-auto h-12 w-12 text-base-content/30"></i>
            <p class="mt-3 font-semibold">Belum ada dokumen SK Banmus.</p>
            <p class="mt-1 text-sm text-base-content/50">Silakan unggah dokumen SK Banmus baru untuk mulai mengelola agenda.</p>
            <a href="<?= base_url('admin/jadwal-banmus/create') ?>" class="btn btn-primary btn-sm mt-4 gap-1">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Tambah SK Banmus Pertama
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="w-12 text-center">No</th>
                        <th>Nomor SK & Judul</th>
                        <th class="w-36 text-center">Periode</th>
                        <th class="w-28 text-center">Jumlah Agenda</th>
                        <th class="w-32 text-center">File PDF</th>
                        <th class="w-48 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $index => $doc): ?>
                        <tr class="hover">
                            <td class="text-center font-medium text-base-content/50"><?= $index + 1 ?></td>
                            <td>
                                <div class="font-bold text-base-content">
                                    SK No. <?= esc($doc['nomor_sk']) ?>
                                </div>
                                <div class="text-xs text-base-content/60">
                                    <?= esc($doc['judul']) ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-outline badge-sm font-mono">
                                    Sem. <?= (int) $doc['semester'] ?> / <?= (int) $doc['tahun'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-primary badge-sm font-semibold">
                                    <?= (int) ($doc['jumlah_item'] ?? 0) ?> item
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if (! empty($doc['dokumen_file'])): ?>
                                    <a href="<?= base_url('uploads/sk-banmus/' . $doc['dokumen_file']) ?>" target="_blank" class="btn btn-ghost btn-xs text-primary gap-1" title="Buka PDF SK">
                                        <i data-lucide="file-pdf" class="h-4 w-4"></i>
                                        PDF
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-base-content/40">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="<?= base_url('admin/jadwal-banmus/' . $doc['id']) ?>" class="btn btn-primary btn-xs gap-1" title="Kelola Item Agenda">
                                        <i data-lucide="list-todo" class="h-3.5 w-3.5"></i>
                                        Kelola Agenda
                                    </a>
                                    <a href="<?= base_url('admin/jadwal-banmus/' . $doc['id'] . '/edit') ?>" class="btn btn-ghost btn-xs btn-square" title="Edit Metadata SK">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    </a>
                                    <form action="<?= base_url('admin/jadwal-banmus/' . $doc['id'] . '/delete') ?>" method="post" class="inline" data-confirm-message="Yakin ingin menghapus SK Banmus ini beserta seluruh item agendanya?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-xs btn-square text-error" title="Hapus SK">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
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
</section>

<?= $this->endSection() ?>
