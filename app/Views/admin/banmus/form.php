<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isEdit = is_array($document) && ! empty($document['id']);
$selectedSemester = (int) ($document['semester'] ?? (date('n') <= 6 ? 1 : 2));
?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Agenda Internal DPRD</p>
        <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    </div>
    <a href="<?= base_url('admin/jadwal-banmus') ?>" class="py-2 px-3 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition sm:w-auto">
        <i data-lucide="arrow-left" class="size-4"></i>
        Kembali ke Daftar SK
    </a>
</div>

<form class="min-w-0 max-w-full" action="<?= esc($action_url) ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="flex items-center gap-3 p-4 mb-4 rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 shadow-xs" role="alert">
            <i data-lucide="triangle-alert" class="size-5 shrink-0"></i>
            <span class="text-xs font-semibold sm:text-sm"><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden min-w-0 max-w-full">
        <div class="p-4 sm:p-6 space-y-5">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i data-lucide="file-text" class="size-5 text-emerald-500"></i>
                Dokumen SK Banmus
            </h2>

            <div class="grid min-w-0 grid-cols-12 gap-4">
                <div class="col-span-12 min-w-0 sm:col-span-6 lg:col-span-4">
                    <label for="nomor_sk" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Nomor SK <span class="text-rose-500">*</span>
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 dark:placeholder:text-slate-500" id="nomor_sk" name="nomor_sk" type="text" maxlength="100" required
                        value="<?= esc($document['nomor_sk'] ?? '') ?>"
                        placeholder="Contoh: 160/9/2026" />
                </div>

                <div class="col-span-12 min-w-0 sm:col-span-6 lg:col-span-4">
                    <label for="tahun" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Tahun <span class="text-rose-500">*</span>
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="tahun" name="tahun" type="number" min="2000" max="2100" required
                        value="<?= esc($document['tahun'] ?? date('Y')) ?>" />
                </div>

                <div class="col-span-12 min-w-0 sm:col-span-6 lg:col-span-4">
                    <label for="semester" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Semester <span class="text-rose-500">*</span>
                    </label>
                    <select class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="semester" name="semester" required>
                        <option value="1" <?= $selectedSemester === 1 ? 'selected' : '' ?>>Semester 1</option>
                        <option value="2" <?= $selectedSemester === 2 ? 'selected' : '' ?>>Semester 2</option>
                    </select>
                </div>

                <div class="col-span-12 min-w-0 sm:col-span-12">
                    <label for="judul" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Judul SK (Opsional)
                    </label>
                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 dark:placeholder:text-slate-500" id="judul" name="judul" type="text" maxlength="200"
                        value="<?= esc($document['judul'] ?? '') ?>"
                        placeholder="Default: Jadwal Rapat Hasil Banmus Semester X Tahun YYYY" />
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Jika dikosongkan, judul otomatis dibuat dari semester &amp; tahun.</p>
                </div>

                <div class="col-span-12 min-w-0 sm:col-span-12">
                    <label for="dokumen_file" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        File SK (PDF)
                        <?php if (! $isEdit): ?>
                            <span class="text-rose-500">*</span>
                        <?php endif; ?>
                    </label>
                    <input class="block w-full border border-slate-200 shadow-xs rounded-xl text-sm focus:z-10 focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-400 file:bg-slate-50 file:border-0 file:me-4 file:py-2.5 file:px-4 dark:file:bg-slate-800 dark:file:text-slate-400" id="dokumen_file" name="dokumen_file" type="file"
                        accept="application/pdf,.pdf" <?= $isEdit ? '' : 'required' ?> />
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                        <?php if ($isEdit && (! empty($document['dokumen_file']) || ! empty($document['dokumen_url']))): ?>
                            <?php $storedDocumentName = $document['dokumen_nama_asli'] ?: 'Dokumen SK'; ?>
                            <span class="block max-w-full truncate font-semibold text-emerald-600 dark:text-emerald-400" title="<?= esc($storedDocumentName) ?>">
                                File tersimpan: <?= esc($storedDocumentName) ?> (Pilih file baru jika ingin mengganti)
                            </span>
                        <?php else: ?>
                            Format PDF, ukuran maksimal 10 MB. Dokumen ini digunakan sebagai referensi resmi.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="col-span-12 min-w-0 sm:col-span-12">
                    <label for="catatan" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Catatan Dokumen (Opsional)
                    </label>
                    <textarea class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 dark:placeholder:text-slate-500 min-h-20 resize-none" id="catatan" name="catatan" rows="3" maxlength="1000"
                        placeholder="Catatan tambahan mengenai SK Banmus ini..."><?= esc($document['catatan'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </section>

    <div class="mt-6 flex justify-end items-center gap-2">
        <a href="<?= base_url('admin/jadwal-banmus') ?>" class="py-2.5 px-4 inline-flex items-center text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition">
            Batal
        </a>
        <button type="submit" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer">
            <i data-lucide="check" class="size-4"></i>
            <span class="sm:hidden">Simpan SK</span>
            <span class="hidden sm:inline"><?= $isEdit ? 'Simpan Perubahan SK' : 'Simpan & Lanjut ke Item Agenda' ?></span>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
