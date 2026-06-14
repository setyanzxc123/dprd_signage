<?= $this->extend('admin/layouts/main') ?>



<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">
        <?= esc($pageTitle) ?>
    </h1>
    <p class="page-subtitle">
        <?= $room ? 'Perbarui ruangan tetap DPRD' : 'Tambahkan ruangan tetap DPRD' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="POST" class="room-form">
    <?= csrf_field() ?>

    <div class="grid grid-cols-12 gap-3">

        <div class="lg:col-span-7">
            <div class="form-card p-[18px]">

                <div class="form-section-title mb-3 pb-2">Informasi Ruangan</div>

                <div class="alert alert-info py-2 px-3 mb-2 text-xs flex gap-2">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    <span>Master ini untuk ruangan tetap DPRD. Tempat lain diisi dari form jadwal sebagai <strong>Lokasi Lainnya</strong>.</span>
                </div>

                <div class="grid grid-cols-12 gap-3">

                    <div class="col-span-12">
                        <label class="label-text font-bold text-sm mb-1 block" for="name">
                            Nama Ruangan <span class="text-error">*</span>
                        </label>
                        <input type="text" class="input input-bordered w-full" id="name" name="name"
                            value="<?= esc($room['name'] ?? '') ?>" placeholder="Contoh: Ruang Paripurna Utama"
                            required />
                    </div>

                    <div class="col-span-12">
                        <label class="label-text font-bold text-sm mb-1 block" for="kapasitas">
                            Kapasitas <span class="text-error">*</span>
                        </label>
                        <div class="join w-full">
                            <input type="number" class="input input-bordered join-item flex-1 w-full" id="kapasitas" name="kapasitas"
                                value="<?= esc($room['kapasitas'] ?? '') ?>" placeholder="0" min="1" required />
                            <span class="join-item bg-base-200 border border-base-300 border-l-0 px-3 flex items-center text-xs font-semibold font-mono">orang</span>
                        </div>
                    </div>

                    <div class="col-span-12">
                        <label class="label-text font-bold text-sm mb-1 block" for="keterangan">Keterangan</label>
                        <textarea class="textarea textarea-bordered w-full" id="keterangan" name="keterangan" rows="2"
                            placeholder="Contoh: Lantai 2, sisi barat gedung utama, atau catatan fasilitas singkat"><?= esc($room['keterangan'] ?? '') ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="lg:col-span-5">
            <div class="form-card p-[18px]">

                <div class="form-section-title mb-3 pb-2">Status Ruangan</div>

                <div>
                    <label class="label-text font-bold text-sm mb-1 block" for="tersedia">Ketersediaan</label>
                    <select class="select select-bordered w-full" id="tersedia" name="tersedia">
                        <option value="1" <?= ($room['tersedia'] ?? 1) ? 'selected' : '' ?>>
                            Tersedia
                        </option>
                        <option value="0" <?= !($room['tersedia'] ?? 1) ? 'selected' : '' ?>>
                            Tidak Tersedia
                        </option>
                    </select>
                    <div class="label-text-alt text-base-content/60 mt-1">
                        Ruangan nonaktif tidak akan muncul di pilihan jadwal rapat.
                    </div>
                </div>

            </div>
        </div>

    </div>

    <div class="flex gap-2 mt-4">
        <a href="<?= base_url('admin/ruangan') ?>" class="btn btn-outline btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary btn-sm">
            <i data-lucide="check" class="w-4 h-4"></i>
            <?= $room ? 'Simpan Perubahan' : 'Tambah Ruangan' ?>
        </button>
    </div>

</form>

<?= $this->endSection() ?>
