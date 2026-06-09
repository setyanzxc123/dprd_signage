<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    <p class="page-subtitle">
        <?= $unit ? 'Perbarui unit target rapat' : 'Tambahkan komisi, badan, pansus, atau target rapat baru' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="POST">
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="form-section-title">Informasi Unit</div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="nama">
                            Nama Unit <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="nama" name="nama"
                            value="<?= esc($unit['nama'] ?? '') ?>"
                            placeholder="Contoh: Pansus Ranperda Pajak Daerah" required />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="jenis">Jenis</label>
                        <select class="form-select" id="jenis" name="jenis">
                            <?php foreach ($jenisOptions as $value => $label): ?>
                                <option value="<?= esc($value, 'attr') ?>" <?= ($unit['jenis'] ?? 'lainnya') === $value ? 'selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="urutan">Urutan Tampil</label>
                        <input type="number" class="form-control" id="urutan" name="urutan"
                            value="<?= esc($unit['urutan'] ?? 0) ?>" min="0" step="1" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-card">
                <div class="form-section-title">Status</div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                        id="aktif" name="aktif" value="1" <?= ($unit['aktif'] ?? 1) ? 'checked' : '' ?> />
                    <label class="form-check-label fw-semibold" for="aktif">Aktif</label>
                </div>
                <div class="form-text mt-2">
                    Unit nonaktif tidak muncul sebagai pilihan target peserta pada form jadwal baru.
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <a href="<?= base_url('admin/unit-rapat') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>
            <?= $unit ? 'Simpan Perubahan' : 'Tambah Unit' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
