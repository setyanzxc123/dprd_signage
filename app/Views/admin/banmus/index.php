<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php $totalItems = array_sum(array_map(static fn (array $doc): int => (int) ($doc['jumlah_item'] ?? 0), $documents)); ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="page-title">Agenda Banmus</h1>
    </div>
    <a href="<?= base_url('admin/jadwal-banmus/create') ?>" class="py-2 px-3.5 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition w-full sm:w-auto justify-center">
        <i data-lucide="plus" class="size-4"></i>
        Tambah SK Banmus
    </a>
</div>

<section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 px-4 py-3 sm:px-5">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                <i data-lucide="file-stack" class="size-4.5"></i>
            </span>
            <div class="min-w-0">
                <h2 class="text-sm sm:text-base font-bold text-slate-800 dark:text-slate-100">Dokumen SK Banmus</h2>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= count($documents) ?> dokumen</span>
            <span class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= $totalItems ?> agenda</span>
        </div>
    </div>

    <?php if ($documents === []): ?>
        <div class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">
            <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400">
                <i data-lucide="file-text" class="size-7"></i>
            </span>
            <p class="mt-3 font-semibold text-slate-800 dark:text-slate-200">Belum ada dokumen SK Banmus.</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Silakan unggah dokumen SK Banmus baru untuk mulai mengelola agenda.</p>
            <a href="<?= base_url('admin/jadwal-banmus/create') ?>" class="mt-4 py-2 px-3.5 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition">
                <i data-lucide="plus" class="size-4"></i>
                Tambah SK Banmus Pertama
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50">
                        <th class="px-4 py-3 text-start text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dokumen SK</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Periode</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Jumlah Agenda</th>
                        <th class="px-4 py-3 text-end text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-64">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($documents as $doc): ?>
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3.5" data-label="Dokumen SK">
                                <div class="min-w-0">
                                    <a href="<?= base_url('admin/jadwal-banmus/' . $doc['id']) ?>"
                                       class="font-bold text-sm text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition">
                                        SK No. <?= esc($doc['nomor_sk']) ?>
                                    </a>
                                    <p class="mt-0.5 max-w-2xl text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                                        <?= esc($doc['judul']) ?>
                                    </p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center py-0.5 px-2 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                            <?= ! empty($doc['is_publik']) ? 'Publik' : 'Internal' ?>
                                        </span>
                                        <?php if (! empty($doc['dokumen_file'])): ?>
                                            <a href="<?= base_url('uploads/sk-banmus/' . $doc['dokumen_file']) ?>"
                                               target="_blank"
                                               rel="noopener"
                                               class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 transition"
                                               title="Buka PDF SK">
                                                <i data-lucide="file-text" class="size-3.5"></i>
                                                Buka PDF
                                                <i data-lucide="external-link" class="size-3"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 text-xs text-slate-400 dark:text-slate-500">
                                                <i data-lucide="file-x" class="size-3.5"></i>
                                                Tanpa PDF
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Periode">
                                <div class="font-semibold text-xs text-slate-800 dark:text-slate-200">Semester <?= (int) $doc['semester'] ?></div>
                                <div class="mt-0.5 font-mono text-xs text-slate-400 dark:text-slate-500"><?= (int) $doc['tahun'] ?></div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Jumlah Agenda">
                                <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    <i data-lucide="list-checks" class="size-3.5 text-emerald-500"></i>
                                    <?= (int) ($doc['jumlah_item'] ?? 0) ?> agenda
                                </span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-end" data-label="Aksi">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= base_url('admin/jadwal-banmus/' . $doc['id']) ?>"
                                       class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition"
                                       title="Kelola Item Agenda">
                                        <i data-lucide="list-todo" class="size-3.5"></i>
                                        Kelola
                                    </a>
                                    <a href="<?= base_url('admin/jadwal-banmus/' . $doc['id'] . '/edit') ?>"
                                       class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition"
                                       title="Edit Metadata SK">
                                        <i data-lucide="pencil" class="size-3.5"></i>
                                        Edit
                                    </a>
                                    <form action="<?= base_url('admin/jadwal-banmus/' . $doc['id'] . '/delete') ?>" method="post" class="m-0 inline-flex" data-confirm-message="Yakin ingin menghapus SK Banmus No. <?= esc($doc['nomor_sk']) ?> beserta <?= (int) ($doc['jumlah_item'] ?? 0) ?> item agenda terkait?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer" title="Hapus SK">
                                            <i data-lucide="trash-2" class="size-3.5"></i>
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
