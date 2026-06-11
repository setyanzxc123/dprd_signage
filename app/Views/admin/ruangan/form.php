<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .room-form .form-card {
        padding: 18px;
    }

    .room-form .form-section-title {
        margin-bottom: 12px;
        padding-bottom: 8px;
    }

    .room-form .ta-label {
        margin-bottom: 4px;
        font-size: 0.82rem;
    }

    .room-form .ta-help,
    .room-form .compact-alert {
        font-size: 0.72rem;
    }

    .room-form .compact-alert {
        line-height: 1.35;
    }
</style>
<?= $this->endSection() ?>

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
            <div class="form-card">

                <div class="form-section-title">Informasi Ruangan</div>

                <div class="ta-alert ta-alert-info py-2 px-3 mb-2 compact-alert">
                    <i data-lucide="info" class="mr-1"></i>
                    Master ini untuk ruangan tetap DPRD. Tempat lain diisi dari form jadwal sebagai <strong>Lokasi Lainnya</strong>.
                </div>

                <div class="grid grid-cols-12 gap-3">

                    <div class="col-span-12">
                        <label class="ta-label font-semibold" for="name">
                            Nama Ruangan <span class="text-red-600">*</span>
                        </label>
                        <input type="text" class="ta-input" id="name" name="name"
                            value="<?= esc($room['name'] ?? '') ?>" placeholder="Contoh: Ruang Paripurna Utama"
                            required />
                    </div>

                    <div class="col-span-12">
                        <label class="ta-label font-semibold" for="kapasitas">
                            Kapasitas <span class="text-red-600">*</span>
                        </label>
                        <div class="ta-input-group">
                            <input type="number" class="ta-input" id="kapasitas" name="kapasitas"
                                value="<?= esc($room['kapasitas'] ?? '') ?>" placeholder="0" min="1" required />
                            <span class="ta-input-addon">orang</span>
                        </div>
                    </div>

                    <div class="col-span-12">
                        <label class="ta-label font-semibold" for="keterangan">Keterangan</label>
                        <textarea class="ta-input" id="keterangan" name="keterangan" rows="2"
                            placeholder="Contoh: Lantai 2, sisi barat gedung utama, atau catatan fasilitas singkat"><?= esc($room['keterangan'] ?? '') ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="lg:col-span-5">
            <div class="form-card">

                <div class="form-section-title">Status Ruangan</div>

                <div>
                    <label class="ta-label font-semibold" for="tersedia">Ketersediaan</label>
                    <select class="ta-select" id="tersedia" name="tersedia">
                        <option value="1" <?= ($room['tersedia'] ?? 1) ? 'selected' : '' ?>>
                            Tersedia
                        </option>
                        <option value="0" <?= !($room['tersedia'] ?? 1) ? 'selected' : '' ?>>
                            Tidak Tersedia
                        </option>
                    </select>
                    <div class="ta-help">
                        Ruangan nonaktif tidak akan muncul di pilihan jadwal rapat.
                    </div>
                </div>

            </div>
        </div>

    </div>

    <div class="flex gap-2 mt-3">
        <a href="<?= base_url('admin/ruangan') ?>" class="ta-btn ta-btn-outline-gray">
            <i data-lucide="arrow-left" class="mr-1"></i>Batal
        </a>
        <button type="submit" class="ta-btn ta-btn-primary">
            <i data-lucide="check" class="mr-1"></i>
            <?= $room ? 'Simpan Perubahan' : 'Tambah Ruangan' ?>
        </button>
    </div>

</form>

<?= $this->endSection() ?>
