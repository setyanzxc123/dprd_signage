<?= $this->extend('admin/layouts/main') ?>



<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">
        <?= esc($pageTitle) ?>
    </h1>
</div>

<form action="<?= esc($action_url) ?>" method="POST" class="room-form" data-turbo="true">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="alert alert-error shadow-sm mb-3" role="alert">
            <i data-lucide="triangle-alert" class="w-4 h-4"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <section class="card card-border bg-base-100 shadow-sm">
        <div class="card-body gap-5 p-4 sm:p-5">
            <h2 class="card-title text-base">
                <i data-lucide="door-open" class="h-5 w-5 text-primary"></i>
                Data Ruangan
            </h2>

            <fieldset class="fieldset gap-3">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 lg:col-span-8">
                        <label class="label py-1 font-semibold" for="name">
                            Nama Ruangan <span class="text-error">*</span>
                        </label>
                        <input type="text" class="input w-full" id="name" name="name"
                            value="<?= esc($room['name'] ?? '') ?>" placeholder="Masukkan nama ruangan"
                            required />
                    </div>

                    <div class="col-span-12 lg:col-span-4">
                        <label class="label py-1 font-semibold" for="kapasitas">
                            Kapasitas <span class="text-error">*</span>
                        </label>
                        <div class="join w-full">
                            <input type="number" class="input join-item flex-1 w-full" id="kapasitas" name="kapasitas"
                                value="<?= esc($room['kapasitas'] ?? '') ?>" placeholder="0" min="1" required />
                            <span class="join-item bg-base-200 border border-base-300 border-l-0 px-3 flex items-center text-xs font-semibold font-mono">orang</span>
                        </div>
                    </div>

                    <div class="col-span-12 lg:col-span-8">
                        <label class="label py-1 font-semibold" for="keterangan">Keterangan</label>
                        <textarea class="textarea w-full" id="keterangan" name="keterangan" rows="2"
                            placeholder="Masukkan keterangan ruangan"><?= esc($room['keterangan'] ?? '') ?></textarea>
                    </div>

                    <div class="col-span-12 lg:col-span-4">
                        <label class="label py-1 font-semibold" for="tersedia">Status</label>
                        <select class="select w-full" id="tersedia" name="tersedia">
                            <option value="1" <?= ($room['tersedia'] ?? 1) ? 'selected' : '' ?>>
                                Tersedia
                            </option>
                            <option value="0" <?= !($room['tersedia'] ?? 1) ? 'selected' : '' ?>>
                                Tidak Tersedia
                            </option>
                        </select>
                    </div>

                </div>
            </fieldset>
        </div>
    </section>

    <div class="form-actions-sticky">
        <a href="<?= base_url('admin/ruangan') ?>" class="btn btn-outline sm:btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary sm:btn-sm">
            <i data-lucide="check" class="w-4 h-4"></i>
            <?= $room ? 'Simpan Perubahan' : 'Tambah Ruangan' ?>
        </button>
    </div>

</form>

<?= $this->endSection() ?>
