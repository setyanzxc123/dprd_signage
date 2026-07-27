<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isEdit = is_array($document) && ! empty($document['id']);
$selectedSemester = (int) ($document['semester'] ?? (date('n') <= 6 ? 1 : 2));
?>

<div class="page-header">
    <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    <p class="page-subtitle">Masukkan identitas SK dan salin baris kegiatan sesuai tabel pada dokumen.</p>
</div>

<form class="min-w-0 max-w-full" action="<?= esc($action_url) ?>" method="post" enctype="multipart/form-data" data-banmus-form>
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div role="alert" class="alert alert-error mb-4 shadow-sm">
            <i data-lucide="triangle-alert" class="h-4 w-4"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <section class="card card-border min-w-0 max-w-full overflow-hidden bg-base-100 shadow-sm">
        <div class="card-body gap-4 p-4 sm:p-5">
            <div>
                <h2 class="card-title text-base">Dokumen SK Proyeksi Banmus</h2>
                <p class="mt-1 text-xs font-semibold text-base-content/55">Satu SK berlaku untuk satu semester.</p>
            </div>

            <div class="grid min-w-0 grid-cols-12 gap-3">
                <fieldset class="fieldset col-span-12 min-w-0 sm:col-span-6 lg:col-span-4">
                    <legend class="fieldset-legend">Nomor SK <span class="text-error">*</span></legend>
                    <input class="input min-w-0 max-w-full" id="nomor_sk" name="nomor_sk" type="text" maxlength="100" required
                        value="<?= esc($document['nomor_sk'] ?? '') ?>"
                        placeholder="Contoh: 160/9/2026" />
                </fieldset>

                <fieldset class="fieldset col-span-6 min-w-0 sm:col-span-3 lg:col-span-2">
                    <legend class="fieldset-legend">Tahun <span class="text-error">*</span></legend>
                    <input class="input min-w-0 max-w-full" id="tahun" name="tahun" type="number" min="2000" max="2100" required
                        value="<?= esc($document['tahun'] ?? date('Y')) ?>" />
                </fieldset>

                <fieldset class="fieldset col-span-6 min-w-0 sm:col-span-3 lg:col-span-2">
                    <legend class="fieldset-legend">Semester <span class="text-error">*</span></legend>
                    <select class="select min-w-0 max-w-full" id="semester" name="semester" required>
                        <option value="1" <?= $selectedSemester === 1 ? 'selected' : '' ?>>Semester 1</option>
                        <option value="2" <?= $selectedSemester === 2 ? 'selected' : '' ?>>Semester 2</option>
                    </select>
                </fieldset>

                <fieldset class="fieldset col-span-12 min-w-0 lg:col-span-4">
                    <legend class="fieldset-legend">File SK <span class="text-error">*</span></legend>
                    <input class="file-input min-w-0 max-w-full" id="dokumen_file" name="dokumen_file" type="file"
                        accept="application/pdf,.pdf" <?= $isEdit ? '' : 'required' ?> />
                    <p class="label">
                        <?php if ($isEdit && (! empty($document['dokumen_file']) || ! empty($document['dokumen_url']))): ?>
                            Saat ini: <?= esc($document['dokumen_nama_asli'] ?: 'Dokumen SK tersimpan') ?>. Pilih PDF baru hanya untuk mengganti.
                        <?php else: ?>
                            PDF maksimal 10 MB.
                        <?php endif; ?>
                    </p>
                </fieldset>
            </div>
        </div>
    </section>

    <section class="card card-border mt-5 min-w-0 max-w-full overflow-hidden bg-base-100 shadow-sm">
        <div class="card-body gap-4 p-0">
            <div class="flex flex-col justify-between gap-3 px-4 pt-4 sm:flex-row sm:items-center sm:px-5 sm:pt-5">
                <div>
                    <h2 class="card-title text-base">Isi Proyeksi Banmus</h2>
                    <p class="mt-1 text-xs font-semibold text-base-content/55">Tambahkan baris dari atas ke bawah sesuai urutan pada SK.</p>
                </div>
                <button class="btn btn-outline btn-sm shrink-0 gap-1" type="button" data-add-item>
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Tambah Baris
                </button>
            </div>

            <div class="overflow-x-auto border-y border-base-300 max-sm:overflow-x-visible">
                <table class="table table-zebra w-full max-sm:block max-sm:min-w-0 max-sm:table-fixed sm:min-w-[900px]">
                    <thead class="bg-base-200 max-sm:hidden">
                        <tr>
                            <th class="w-14 text-center">No</th>
                            <th class="w-56">Tanggal Pelaksanaan</th>
                            <th>Uraian Kegiatan</th>
                            <th class="w-64">Keterangan</th>
                            <th class="w-16"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="max-sm:block max-sm:w-full max-sm:min-w-0 max-sm:p-3" data-items-container>
                        <?php foreach ($items as $index => $item): ?>
                            <?php
                            $implementationDate = $item['tanggal_pelaksanaan'] ?? $item['periode_label'] ?? '';
                            $activity = $item['uraian_kegiatan'] ?? $item['agenda'] ?? '';
                            $notes = $item['keterangan'] ?? $item['catatan'] ?? '';
                            ?>
                            <tr class="align-top max-sm:mb-3 max-sm:block max-sm:w-full max-sm:min-w-0 max-sm:rounded-box max-sm:border max-sm:border-base-300 max-sm:bg-base-100 max-sm:p-3 last:max-sm:mb-0" data-banmus-item>
                                <td class="pt-5 text-center font-black text-base-content/60 max-sm:flex max-sm:items-center max-sm:justify-between max-sm:border-0 max-sm:px-1 max-sm:pb-2 max-sm:pt-0" data-item-number>
                                    <span class="sm:hidden">Baris</span><span data-item-index><?= $index + 1 ?></span>
                                </td>
                                <td class="max-sm:block max-sm:w-full max-sm:min-w-0 max-sm:border-0 max-sm:px-1 max-sm:py-2">
                                    <span class="mb-1 block text-xs font-bold text-base-content/60 sm:hidden">Tanggal Pelaksanaan</span>
                                    <textarea class="textarea min-h-24 w-full resize-y" rows="3" maxlength="100" required
                                        name="items[<?= $index ?>][tanggal_pelaksanaan]" data-field="tanggal_pelaksanaan"
                                        placeholder="Contoh: Juni–Juli 2026"><?= esc($implementationDate) ?></textarea>
                                </td>
                                <td class="max-sm:block max-sm:w-full max-sm:min-w-0 max-sm:border-0 max-sm:px-1 max-sm:py-2">
                                    <span class="mb-1 block text-xs font-bold text-base-content/60 sm:hidden">Uraian Kegiatan</span>
                                    <textarea class="textarea min-h-24 w-full resize-y" rows="3" maxlength="10000" required
                                        name="items[<?= $index ?>][uraian_kegiatan]" data-field="uraian_kegiatan"
                                        placeholder="Uraian kegiatan sesuai SK"><?= esc($activity) ?></textarea>
                                </td>
                                <td class="max-sm:block max-sm:w-full max-sm:min-w-0 max-sm:border-0 max-sm:px-1 max-sm:py-2">
                                    <span class="mb-1 block text-xs font-bold text-base-content/60 sm:hidden">Keterangan</span>
                                    <textarea class="textarea min-h-24 w-full resize-y" rows="3" maxlength="2000"
                                        name="items[<?= $index ?>][keterangan]" data-field="keterangan"
                                        placeholder="Opsional"><?= esc($notes) ?></textarea>
                                </td>
                                <td class="pt-4 text-center max-sm:block max-sm:border-0 max-sm:px-1 max-sm:pb-0 max-sm:pt-2 max-sm:text-right">
                                    <button class="btn btn-ghost btn-error btn-sm sm:btn-square" type="button"
                                        data-remove-item aria-label="Hapus baris <?= $index + 1 ?>">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        <span class="sm:hidden">Hapus Baris</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-4 pb-4 sm:px-5 sm:pb-5">
                <button class="btn btn-outline btn-sm gap-1" type="button" data-add-item>
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Tambah Baris
                </button>
            </div>
        </div>
    </section>

    <div class="form-actions-sticky mt-5 flex gap-2">
        <a href="<?= base_url('admin/jadwal-banmus') ?>" class="btn btn-outline flex-1 sm:btn-sm sm:flex-none">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Batal
        </a>
        <button type="submit" class="btn btn-primary flex-1 sm:btn-sm sm:flex-none">
            <i data-lucide="check" class="h-4 w-4"></i>
            <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Proyeksi' ?>
        </button>
    </div>

    <template data-item-template>
        <tr class="align-top max-sm:mb-3 max-sm:block max-sm:w-full max-sm:min-w-0 max-sm:rounded-box max-sm:border max-sm:border-base-300 max-sm:bg-base-100 max-sm:p-3 last:max-sm:mb-0" data-banmus-item>
            <td class="pt-5 text-center font-black text-base-content/60 max-sm:flex max-sm:items-center max-sm:justify-between max-sm:border-0 max-sm:px-1 max-sm:pb-2 max-sm:pt-0" data-item-number>
                <span class="sm:hidden">Baris</span><span data-item-index>1</span>
            </td>
            <td class="max-sm:block max-sm:w-full max-sm:min-w-0 max-sm:border-0 max-sm:px-1 max-sm:py-2">
                <span class="mb-1 block text-xs font-bold text-base-content/60 sm:hidden">Tanggal Pelaksanaan</span>
                <textarea class="textarea min-h-24 w-full resize-y" rows="3" maxlength="100" required
                    data-field="tanggal_pelaksanaan" placeholder="Contoh: Juni–Juli 2026"></textarea>
            </td>
            <td class="max-sm:block max-sm:w-full max-sm:min-w-0 max-sm:border-0 max-sm:px-1 max-sm:py-2">
                <span class="mb-1 block text-xs font-bold text-base-content/60 sm:hidden">Uraian Kegiatan</span>
                <textarea class="textarea min-h-24 w-full resize-y" rows="3" maxlength="10000" required
                    data-field="uraian_kegiatan" placeholder="Uraian kegiatan sesuai SK"></textarea>
            </td>
            <td class="max-sm:block max-sm:w-full max-sm:min-w-0 max-sm:border-0 max-sm:px-1 max-sm:py-2">
                <span class="mb-1 block text-xs font-bold text-base-content/60 sm:hidden">Keterangan</span>
                <textarea class="textarea min-h-24 w-full resize-y" rows="3" maxlength="2000"
                    data-field="keterangan" placeholder="Opsional"></textarea>
            </td>
            <td class="pt-4 text-center max-sm:block max-sm:border-0 max-sm:px-1 max-sm:pb-0 max-sm:pt-2 max-sm:text-right">
                <button class="btn btn-ghost btn-error btn-sm sm:btn-square" type="button"
                    data-remove-item aria-label="Hapus baris">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    <span class="sm:hidden">Hapus Baris</span>
                </button>
            </td>
        </tr>
    </template>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script {csp-script-nonce}>
    (() => {
        const initializeBanmusForm = () => {
            const form = document.querySelector('[data-banmus-form]');
            if (!form || form.dataset.initialized === 'true') {
                return;
            }
            form.dataset.initialized = 'true';

            const container = form.querySelector('[data-items-container]');
            const template = form.querySelector('[data-item-template]');

            const refreshItems = () => {
                const rows = [...container.querySelectorAll('[data-banmus-item]')];
                rows.forEach((row, position) => {
                    row.querySelector('[data-item-index]').textContent = String(position + 1);
                    row.querySelectorAll('[data-field]').forEach((field) => {
                        field.name = `items[${position}][${field.dataset.field}]`;
                    });

                    const removeButton = row.querySelector('[data-remove-item]');
                    removeButton.disabled = rows.length === 1;
                    removeButton.setAttribute('aria-label', `Hapus baris ${position + 1}`);
                });
            };

            form.querySelectorAll('[data-add-item]').forEach((button) => {
                button.addEventListener('click', () => {
                    const wrapper = document.createElement('tbody');
                    wrapper.innerHTML = template.innerHTML.trim();
                    const row = wrapper.firstElementChild;
                    container.appendChild(row);
                    refreshItems();
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                    row.querySelector('[data-field]')?.focus();
                });
            });

            container.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-item]');
                if (!removeButton || container.querySelectorAll('[data-banmus-item]').length <= 1) {
                    return;
                }

                removeButton.closest('[data-banmus-item]')?.remove();
                refreshItems();
            });

            refreshItems();
        };

        document.addEventListener('DOMContentLoaded', initializeBanmusForm);
        document.addEventListener('turbo:load', initializeBanmusForm);
    })();
</script>
<?= $this->endSection() ?>
