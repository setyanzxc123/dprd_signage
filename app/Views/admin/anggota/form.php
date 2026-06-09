<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
    $manual_units = $manual_units ?? [];
    $selected_unit_ids = array_map('intval', $selected_unit_ids ?? []);
?>

<div class="page-header">
    <h1 class="page-title">
        <?= esc($pageTitle) ?>
    </h1>
    <p class="page-subtitle">
        <?= $member ? 'Perbarui data anggota DPRD' : 'Tambahkan anggota baru ke sistem' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="POST">
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- Kolom kiri: Data Utama -->
        <div class="col-lg-8">
            <div class="form-card">

                <div class="form-section-title">Informasi Anggota</div>

                <div class="row g-3">

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="name">
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="<?= esc($member['name'] ?? '') ?>" placeholder="Contoh: H. Ahmad Fauzi, S.H., M.M."
                            required />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="jabatan">Jabatan</label>
                        <input type="text" class="form-control" id="jabatan" name="jabatan"
                            value="<?= esc($member['jabatan'] ?? '') ?>" placeholder="Contoh: Ketua Komisi III" />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="fraksi">
                            Fraksi <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="fraksi" name="fraksi" required>
                            <option value="">-- Pilih Fraksi --</option>
                            <?php foreach ($fraksi_list as $f):
                                $selected = ($member['fraksi'] ?? '') === $f ? 'selected' : '';
                            ?>
                                <option value="<?= $f ?>" <?= $selected ?>>
                                    <?= $f ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="komisi">Komisi</label>
                        <select class="form-select" id="komisi" name="komisi">
                            <option value="">-- Pilih Komisi --</option>
                            <?php foreach ($komisi_list as $k):
                                $selected = ($member['komisi'] ?? '') === $k ? 'selected' : '';
                            ?>
                                <option value="<?= $k ?>" <?= $selected ?>>
                                    <?= $k ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="status">Status</label>
                        <select class="form-select" id="status" name="aktif">
                            <option value="1" <?= ($member['aktif'] ?? 1) ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= !($member['aktif'] ?? 1) ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <!-- Kolom kanan: Kontak & Keanggotaan -->
        <div class="col-lg-4">
            <div class="form-card">

                <div class="form-section-title">Kontak WhatsApp</div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="no_wa">
                        Nomor WhatsApp <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">+62</span>
                        <input type="text" class="form-control" id="no_wa" name="no_wa"
                            value="<?= esc($member['no_wa'] ?? '') ?>" placeholder="8123456789" required />
                    </div>
                    <div class="form-text">Format tanpa 0 di depan. Contoh: 8123456789</div>
                </div>

                <!-- Info mode edit -->
                <?php if ($member): ?>
                    <div class="alert alert-info alert-sm py-2 px-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Mengubah nomor WA akan mempengaruhi pengiriman notifikasi rapat.
                    </div>
                <?php endif; ?>

            </div>

            <div class="form-card mt-3">
                <div class="form-section-title">Keanggotaan Badan / Pansus</div>

                <?php if (empty($manual_units)): ?>
                    <div class="alert alert-warning py-2 px-3 mb-0">
                        Belum ada unit rapat manual aktif.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2" style="max-height: 280px; overflow-y: auto;">
                        <?php foreach ($manual_units as $unit):
                            $unitId = (int) $unit['id'];
                            $checked = in_array($unitId, $selected_unit_ids, true) ? 'checked' : '';
                            $inputId = 'unit-manual-' . $unitId;
                        ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    id="<?= esc($inputId, 'attr') ?>"
                                    name="manual_units[]"
                                    value="<?= $unitId ?>"
                                    <?= $checked ?> />
                                <label class="form-check-label fw-semibold" for="<?= esc($inputId, 'attr') ?>">
                                    <?= esc($unit['nama']) ?>
                                    <span class="d-block text-muted small fw-normal"><?= esc(ucfirst($unit['jenis'])) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Tombol Aksi -->
    <div class="d-flex gap-2 mt-3">
        <a href="<?= base_url('admin/anggota') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>
            <?= $member ? 'Simpan Perubahan' : 'Tambah Anggota' ?>
        </button>
    </div>

</form>

<?= $this->endSection() ?>
