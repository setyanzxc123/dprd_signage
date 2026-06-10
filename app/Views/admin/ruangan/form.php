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

    .room-form .form-label {
        margin-bottom: 4px;
        font-size: 0.82rem;
    }

    .room-form .form-text,
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

    <div class="row g-2">

        <div class="col-lg-7">
            <div class="form-card">

                <div class="form-section-title">Informasi Ruangan</div>

                <div class="alert alert-info py-2 px-3 mb-2 compact-alert">
                    <i class="bi bi-info-circle me-1"></i>
                    Master ini untuk ruangan tetap DPRD. Tempat lain diisi dari form jadwal sebagai <strong>Lokasi Lainnya</strong>.
                </div>

                <div class="row g-2">

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="name">
                            Nama Ruangan <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="<?= esc($room['name'] ?? '') ?>" placeholder="Contoh: Ruang Paripurna Utama"
                            required />
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="kapasitas">
                            Kapasitas <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="kapasitas" name="kapasitas"
                                value="<?= esc($room['kapasitas'] ?? '') ?>" placeholder="0" min="1" required />
                            <span class="input-group-text">orang</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="keterangan">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="2"
                            placeholder="Contoh: Lantai 2, sisi barat gedung utama, atau catatan fasilitas singkat"><?= esc($room['keterangan'] ?? '') ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="form-card">

                <div class="form-section-title">Status Ruangan</div>

                <div>
                    <label class="form-label fw-semibold" for="tersedia">Ketersediaan</label>
                    <select class="form-select" id="tersedia" name="tersedia">
                        <option value="1" <?= ($room['tersedia'] ?? 1) ? 'selected' : '' ?>>
                            Tersedia
                        </option>
                        <option value="0" <?= !($room['tersedia'] ?? 1) ? 'selected' : '' ?>>
                            Tidak Tersedia
                        </option>
                    </select>
                    <div class="form-text">
                        Ruangan nonaktif tidak akan muncul di pilihan jadwal rapat.
                    </div>
                </div>

            </div>
        </div>

    </div>

    <div class="d-flex gap-2 mt-3">
        <a href="<?= base_url('admin/ruangan') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>
            <?= $room ? 'Simpan Perubahan' : 'Tambah Ruangan' ?>
        </button>
    </div>

</form>

<?= $this->endSection() ?>
