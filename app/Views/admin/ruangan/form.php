<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">
        <?= esc($pageTitle) ?>
    </h1>
    <p class="page-subtitle">
        <?= $room ? 'Perbarui data ruangan rapat' : 'Tambahkan ruangan baru ke sistem' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="POST">
    <?= csrf_field() ?>

    <div class="row g-3">

        <div class="col-lg-8">
            <div class="form-card">

                <div class="form-section-title">Informasi Ruangan</div>

                <div class="row g-3">

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="name">
                            Nama Ruangan <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="<?= esc($room['name'] ?? '') ?>" placeholder="Contoh: Ruang Paripurna Utama"
                            required />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="kapasitas">
                            Kapasitas <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="kapasitas" name="kapasitas"
                                value="<?= esc($room['kapasitas'] ?? '') ?>" placeholder="0" min="1" required />
                            <span class="input-group-text">orang</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="lantai">Lantai</label>
                        <select class="form-select" id="lantai" name="lantai">
                            <option value="">-- Pilih Lantai --</option>
                            <?php foreach ($lantai_opts as $lt):
                                $selected = ($room['lantai'] ?? '') == $lt ? 'selected' : '';
                            ?>
                                <option value="<?= $lt ?>" <?= $selected ?>>Lantai <?= $lt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="keterangan">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="2"
                            placeholder="Contoh: Dilengkapi proyektor dan AC"><?= esc($room['keterangan'] ?? '') ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-card">

                <div class="form-section-title">Status Ruangan</div>

                <div class="mb-3">
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
