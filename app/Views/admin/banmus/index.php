<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php $totalItems = array_sum(array_map(static fn (array $doc): int => (int) ($doc['jumlah_item'] ?? 0), $documents)); ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="page-title">Agenda Banmus</h1>
    </div>
    <a href="<?= base_url('admin/jadwal-banmus/create') ?>" class="btn btn-primary btn-sm w-full gap-1.5 sm:w-auto">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah SK Banmus
    </a>
</div>

<section class="card card-sm card-border banmus-index-card min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-300 px-4 py-3 sm:px-5">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-base-200 text-base-content/70">
                <i data-lucide="file-stack" class="h-4.5 w-4.5"></i>
            </span>
            <div class="min-w-0">
                <h2 class="card-title text-sm sm:text-base">Dokumen SK Banmus</h2>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="badge badge-ghost badge-sm whitespace-nowrap"><?= count($documents) ?> dokumen</span>
            <span class="badge badge-ghost badge-sm whitespace-nowrap"><?= $totalItems ?> agenda</span>
        </div>
    </div>

    <?php if ($documents === []): ?>
        <div class="px-5 py-12 text-center text-base-content/60">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-base-200">
                <i data-lucide="file-text" class="h-7 w-7 text-base-content/35"></i>
            </span>
            <p class="mt-3 font-semibold">Belum ada dokumen SK Banmus.</p>
            <p class="mt-1 text-sm text-base-content/50">Silakan unggah dokumen SK Banmus baru untuk mulai mengelola agenda.</p>
            <a href="<?= base_url('admin/jadwal-banmus/create') ?>" class="btn btn-primary btn-sm mt-4 gap-1">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Tambah SK Banmus Pertama
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table table-sm banmus-document-table w-full">
                <thead>
                    <tr class="bg-base-200/60">
                        <th>Dokumen SK</th>
                        <th class="w-36">Periode</th>
                        <th class="w-36">Jumlah Agenda</th>
                        <th class="w-64 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr class="hover:bg-base-200/50">
                            <td data-label="Dokumen SK">
                                <div class="min-w-0">
                                    <a href="<?= base_url('admin/jadwal-banmus/' . $doc['id']) ?>"
                                       class="font-bold text-base-content hover:text-primary">
                                        SK No. <?= esc($doc['nomor_sk']) ?>
                                    </a>
                                    <p class="mt-0.5 max-w-2xl text-xs leading-relaxed text-base-content/60">
                                        <?= esc($doc['judul']) ?>
                                    </p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="badge badge-ghost badge-xs">
                                            <?= ! empty($doc['is_publik']) ? 'Publik' : 'Internal' ?>
                                        </span>
                                        <?php if (! empty($doc['dokumen_file'])): ?>
                                            <a href="<?= base_url('uploads/sk-banmus/' . $doc['dokumen_file']) ?>"
                                               target="_blank"
                                               rel="noopener"
                                               class="inline-flex items-center gap-1 text-xs font-semibold text-base-content/60 hover:text-primary"
                                               title="Buka PDF SK">
                                                <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                                Buka PDF
                                                <i data-lucide="external-link" class="h-3 w-3"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 text-xs text-base-content/40">
                                                <i data-lucide="file-x" class="h-3.5 w-3.5"></i>
                                                Tanpa PDF
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Periode">
                                <div class="font-semibold text-base-content">Semester <?= (int) $doc['semester'] ?></div>
                                <div class="mt-0.5 font-mono text-xs text-base-content/50"><?= (int) $doc['tahun'] ?></div>
                            </td>
                            <td data-label="Jumlah Agenda">
                                <span class="badge badge-ghost badge-sm font-semibold">
                                    <i data-lucide="list-checks" class="h-3.5 w-3.5"></i>
                                    <?= (int) ($doc['jumlah_item'] ?? 0) ?> agenda
                                </span>
                            </td>
                            <td data-label="Aksi" class="text-right">
                                <div class="banmus-document-actions flex flex-wrap items-center justify-end gap-1.5">
                                    <a href="<?= base_url('admin/jadwal-banmus/' . $doc['id']) ?>"
                                       class="btn btn-xs w-20 gap-1"
                                       title="Kelola Item Agenda">
                                        <i data-lucide="list-todo" class="h-3.5 w-3.5"></i>
                                        Kelola
                                    </a>
                                    <a href="<?= base_url('admin/jadwal-banmus/' . $doc['id'] . '/edit') ?>"
                                       class="btn btn-xs w-20 gap-1"
                                       title="Edit Metadata SK">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </a>
                                    <form action="<?= base_url('admin/jadwal-banmus/' . $doc['id'] . '/delete') ?>" method="post" class="m-0 inline-flex" data-confirm-message="Yakin ingin menghapus SK Banmus ini beserta seluruh item agendanya?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-error btn-xs w-20 gap-1" title="Hapus SK">
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
    <?php endif; ?>
</section>

<?= $this->endSection() ?>
