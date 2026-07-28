<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isEdit = is_array($document) && ! empty($document['id']);
$selectedSemester = (int) ($document['semester'] ?? (date('n') <= 6 ? 1 : 2));
?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-base-content/50">Agenda Internal DPRD</p>
        <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    </div>
    <a href="<?= base_url('admin/jadwal-banmus') ?>" class="btn btn-ghost btn-sm gap-1 sm:w-auto">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Kembali ke Daftar SK
    </a>
</div>

<form class="min-w-0 max-w-full" action="<?= esc($action_url) ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div role="alert" class="alert alert-error mb-4 shadow-sm">
            <i data-lucide="triangle-alert" class="h-4 w-4"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <section class="card card-border min-w-0 max-w-full overflow-hidden bg-base-100 shadow-sm">
        <div class="card-body gap-5 p-4 sm:p-6">
            <h2 class="card-title text-base font-bold">
                <i data-lucide="file-text" class="h-5 w-5 text-primary"></i>
                Dokumen SK Banmus
            </h2>

            <div class="grid min-w-0 grid-cols-12 gap-4">
                <fieldset class="fieldset col-span-12 min-w-0 sm:col-span-6 lg:col-span-4">
                    <legend class="fieldset-legend">Nomor SK <span class="text-error">*</span></legend>
                    <input class="input min-w-0 max-w-full" id="nomor_sk" name="nomor_sk" type="text" maxlength="100" required
                        value="<?= esc($document['nomor_sk'] ?? '') ?>"
                        placeholder="Contoh: 160/9/2026" />
                </fieldset>

                <fieldset class="fieldset col-span-12 min-w-0 sm:col-span-6 lg:col-span-4">
                    <legend class="fieldset-legend">Tahun <span class="text-error">*</span></legend>
                    <input class="input min-w-0 max-w-full" id="tahun" name="tahun" type="number" min="2000" max="2100" required
                        value="<?= esc($document['tahun'] ?? date('Y')) ?>" />
                </fieldset>

                <fieldset class="fieldset col-span-12 min-w-0 sm:col-span-6 lg:col-span-4">
                    <legend class="fieldset-legend">Semester <span class="text-error">*</span></legend>
                    <select class="select min-w-0 max-w-full" id="semester" name="semester" required>
                        <option value="1" <?= $selectedSemester === 1 ? 'selected' : '' ?>>Semester 1</option>
                        <option value="2" <?= $selectedSemester === 2 ? 'selected' : '' ?>>Semester 2</option>
                    </select>
                </fieldset>

                <fieldset class="fieldset col-span-12 min-w-0 sm:col-span-12">
                    <legend class="fieldset-legend">Judul SK (Opsional)</legend>
                    <input class="input min-w-0 max-w-full" id="judul" name="judul" type="text" maxlength="200"
                        value="<?= esc($document['judul'] ?? '') ?>"
                        placeholder="Default: Jadwal Rapat Hasil Banmus Semester X Tahun YYYY" />
                    <p class="label block text-xs text-base-content/50">Jika dikosongkan, judul otomatis dibuat dari semester & tahun.</p>
                </fieldset>

                <fieldset class="fieldset col-span-12 min-w-0 sm:col-span-12">
                    <legend class="fieldset-legend">
                        File SK (PDF)
                        <?php if (! $isEdit): ?>
                            <span class="text-error">*</span>
                        <?php endif; ?>
                    </legend>
                    <input class="file-input w-full min-w-0 max-w-full overflow-hidden" id="dokumen_file" name="dokumen_file" type="file"
                        accept="application/pdf,.pdf" <?= $isEdit ? '' : 'required' ?> />
                    <p class="label block min-w-0 max-w-full overflow-hidden text-xs text-base-content/60">
                        <?php if ($isEdit && (! empty($document['dokumen_file']) || ! empty($document['dokumen_url']))): ?>
                            <?php $storedDocumentName = $document['dokumen_nama_asli'] ?: 'Dokumen SK'; ?>
                            <span class="block max-w-full truncate font-semibold text-primary" title="<?= esc($storedDocumentName) ?>">
                                File tersimpan: <?= esc($storedDocumentName) ?> (Pilih file baru jika ingin mengganti)
                            </span>
                        <?php else: ?>
                            Format PDF, ukuran maksimal 10 MB. Dokumen ini digunakan sebagai referensi resmi.
                        <?php endif; ?>
                    </p>
                </fieldset>

                <fieldset class="fieldset col-span-12 min-w-0 sm:col-span-12">
                    <legend class="fieldset-legend">Catatan Dokumen (Opsional)</legend>
                    <textarea class="textarea min-h-20 w-full resize-none" id="catatan" name="catatan" rows="3" maxlength="1000"
                        placeholder="Catatan tambahan mengenai SK Banmus ini..."><?= esc($document['catatan'] ?? '') ?></textarea>
                </fieldset>
            </div>
        </div>
    </section>

    <div class="form-actions-sticky mt-6 flex justify-end gap-2">
        <a href="<?= base_url('admin/jadwal-banmus') ?>" class="btn btn-ghost">
            Batal
        </a>
        <button type="submit" class="btn btn-primary gap-1">
            <i data-lucide="check" class="h-4 w-4"></i>
            <?= $isEdit ? 'Simpan Perubahan SK' : 'Simpan & Lanjut ke Item Agenda' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
