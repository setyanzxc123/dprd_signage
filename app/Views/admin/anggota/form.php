<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .member-form .form-card {
        padding: 18px;
    }

    .member-form .form-section-title {
        margin-bottom: 12px;
        padding-bottom: 8px;
    }

    .member-form .ta-label {
        margin-bottom: 4px;
        font-size: 0.82rem;
    }

    .member-form .ta-help {
        font-size: 0.72rem;
    }

    .member-form .compact-alert {
        font-size: 0.78rem;
    }

    .member-form .kelompok-option {
        min-height: 36px;
    }

    .member-form #kelompok-list {
        max-height: 220px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $manual_units = $manual_units ?? [];
    $selected_unit_ids = array_map('intval', $selected_unit_ids ?? []);
    $visible_unit_ids = array_map('intval', array_column($manual_units, 'id'));
    $selected_group_count = count(array_intersect($selected_unit_ids, $visible_unit_ids));
?>

<div class="page-header">
    <h1 class="page-title">
        <?= esc($pageTitle) ?>
    </h1>
    <p class="page-subtitle">
        <?= $member ? 'Perbarui data anggota DPRD' : 'Tambahkan anggota baru ke sistem' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="POST" id="anggota-form" class="member-form">
    <?= csrf_field() ?>

    <div class="grid grid-cols-12 gap-3">

        <!-- Kolom kiri: Data Utama -->
        <div class="lg:col-span-8">
            <div class="form-card">

                <div class="form-section-title">Informasi Anggota</div>

                <div class="grid grid-cols-12 gap-3">

                    <div class="col-span-12">
                        <label class="ta-label font-semibold" for="name">
                            Nama Lengkap <span class="text-red-600">*</span>
                        </label>
                        <input type="text" class="ta-input" id="name" name="name"
                            value="<?= esc($member['name'] ?? '') ?>" placeholder="Contoh: H. Ahmad Fauzi, S.H., M.M."
                            required />
                    </div>

                    <div class="md:col-span-6">
                        <label class="ta-label font-semibold" for="jabatan">Jabatan</label>
                        <input type="text" class="ta-input" id="jabatan" name="jabatan"
                            value="<?= esc($member['jabatan'] ?? '') ?>" placeholder="Contoh: Ketua Komisi III" />
                    </div>

                    <div class="md:col-span-6">
                        <label class="ta-label font-semibold" for="fraksi">
                            Fraksi <span class="text-red-600">*</span>
                        </label>
                        <select class="ta-select" id="fraksi" name="fraksi" required>
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

                    <div class="md:col-span-6">
                        <label class="ta-label font-semibold" for="komisi">Komisi</label>
                        <select class="ta-select" id="komisi" name="komisi">
                            <option value="">Tidak dalam komisi</option>
                            <?php foreach ($komisi_list as $k):
                                $selected = ($member['komisi'] ?? '') === $k ? 'selected' : '';
                            ?>
                                <option value="<?= $k ?>" <?= $selected ?>>
                                    <?= $k ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-6">
                        <label class="ta-label font-semibold" for="status">Status</label>
                        <select class="ta-select" id="status" name="aktif">
                            <option value="1" <?= ($member['aktif'] ?? 1) ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= !($member['aktif'] ?? 1) ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="form-card mt-2">
                <div class="form-section-title">Kontak WhatsApp</div>

                <div class="grid grid-cols-12 gap-3 items-start">
                    <div class="md:col-span-6">
                        <label class="ta-label font-semibold" for="no_wa">
                            Nomor WhatsApp <span class="text-red-600">*</span>
                        </label>
                        <div class="ta-input-group">
                            <span class="ta-input-addon">+62</span>
                            <input type="text" class="ta-input" id="no_wa" name="no_wa"
                                value="<?= esc($member['no_wa'] ?? '') ?>" placeholder="8123456789" required />
                        </div>
                        <div class="ta-help">Format tanpa 0 di depan. Contoh: 8123456789</div>
                    </div>

                    <?php if ($member): ?>
                        <div class="md:col-span-6">
                            <div class="ta-alert ta-alert-info ta-alert-sm compact-alert py-2 px-3 mb-0">
                                <i data-lucide="info" class="mr-1"></i>
                                Mengubah nomor WA akan mempengaruhi pengiriman notifikasi rapat.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom kanan: Kelompok Peserta -->
        <div class="lg:col-span-4">
            <div class="form-card">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="form-section-title mb-0">Kelompok Peserta</div>
                    <?php if (! empty($manual_units)): ?>
                        <span class="ta-badge bg-brand-50 text-brand-600" id="kelompok-selected-count">
                            <?= $selected_group_count ?> dipilih
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (empty($manual_units)): ?>
                    <div class="ta-alert ta-alert-warning compact-alert py-2 px-3 mb-0">
                        Belum ada kelompok peserta aktif.
                        <a href="<?= base_url('admin/unit-rapat/create') ?>" class="text-brand-600 font-semibold underline">Buat kelompok peserta</a>
                        sebelum menambahkan anggota.
                    </div>
                <?php else: ?>
                    <div class="ta-help mb-2">Pilih minimal satu kelompok peserta.</div>
                    <div class="relative mb-2">
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 pl-3 text-gray-500">
                            <i data-lucide="search"></i>
                        </span>
                        <input type="text" class="ta-input ta-input-sm pl-10"
                            id="kelompok-search" placeholder="Cari kelompok peserta..." autocomplete="off" />
                    </div>

                    <div class="border rounded overflow-hidden">
                        <div class="flex items-center justify-between px-3 py-1 border-b bg-gray-50">
                            <span class="text-xs font-bold text-gray-500 uppercase">Daftar Kelompok</span>
                            <span class="ta-badge bg-gray-100 rounded-full" id="kelompok-visible-count"><?= count($manual_units) ?></span>
                        </div>
                            <div class="flex flex-col" id="kelompok-list" style="overflow-y: auto;">
                                <?php foreach ($manual_units as $unit):
                                    $unitId = (int) $unit['id'];
                                    $checked = in_array($unitId, $selected_unit_ids, true) ? 'checked' : '';
                                    $inputId = 'unit-manual-' . $unitId;
                                ?>
                                    <label class="flex items-center gap-2 px-3 py-1 border-b mb-0 kelompok-option <?= $checked ? 'bg-brand-50' : '' ?>"
                                        for="<?= esc($inputId, 'attr') ?>"
                                        data-name="<?= esc(strtolower($unit['nama']), 'attr') ?>"
                                        style="cursor: pointer;">
                                        <input class="ta-check-input" type="checkbox"
                                            id="<?= esc($inputId, 'attr') ?>"
                                            name="manual_units[]"
                                            value="<?= $unitId ?>"
                                            data-kelompok-peserta="1"
                                            <?= $checked ?> />
                                        <span class="text-xs font-semibold truncate">
                                            <?= esc($unit['nama']) ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                                <div class="text-gray-500 text-center py-2 hidden" id="kelompok-empty">
                                    <small>Kelompok tidak ditemukan.</small>
                                </div>
                            </div>
                    </div>
                    <div class="text-red-600 text-xs mt-2 hidden" id="kelompok-peserta-error">
                        Pilih minimal satu kelompok peserta.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Tombol Aksi -->
    <div class="flex gap-2 mt-3">
        <a href="<?= base_url('admin/anggota') ?>" class="ta-btn ta-btn-outline-gray">
            <i data-lucide="arrow-left" class="mr-1"></i>Batal
        </a>
        <button type="submit" class="ta-btn ta-btn-primary" <?= empty($manual_units) ? 'disabled' : '' ?>>
            <i data-lucide="check" class="mr-1"></i>
            <?= $member ? 'Simpan Perubahan' : 'Tambah Anggota' ?>
        </button>
    </div>

</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('anggota-form');
        const checkboxes = document.querySelectorAll('[data-kelompok-peserta="1"]');
        const error = document.getElementById('kelompok-peserta-error');
        const searchInput = document.getElementById('kelompok-search');
        const options = document.querySelectorAll('.kelompok-option');
        const selectedCount = document.getElementById('kelompok-selected-count');
        const visibleCount = document.getElementById('kelompok-visible-count');
        const emptyState = document.getElementById('kelompok-empty');

        const hasSelectedGroup = function() {
            return Array.from(checkboxes).some(function(input) {
                return input.checked;
            });
        };

        const syncSelectedCount = function() {
            const count = Array.from(checkboxes).filter(function(input) {
                return input.checked;
            }).length;

            if (selectedCount) selectedCount.textContent = count + ' dipilih';

            checkboxes.forEach(function(input) {
                const option = input.closest('.kelompok-option');
                if (option) option.classList.toggle('bg-brand-50', input.checked);
            });
        };

        const syncGroupValidity = function() {
            const valid = hasSelectedGroup();
            if (error) error.classList.toggle('hidden', valid);
            checkboxes.forEach(function(input) {
                input.classList.toggle('is-invalid', !valid);
            });
            syncSelectedCount();
            return valid;
        };

        checkboxes.forEach(function(input) {
            input.addEventListener('change', syncGroupValidity);
        });

        searchInput?.addEventListener('input', function() {
            const q = (this.value || '').trim().toLowerCase();
            let shown = 0;

            options.forEach(function(option) {
                const match = (option.getAttribute('data-name') || '').includes(q);
                option.style.display = match ? '' : 'none';
                if (match) shown++;
            });

            if (visibleCount) visibleCount.textContent = shown;
            if (emptyState) emptyState.classList.toggle('hidden', shown > 0);
        });

        form?.addEventListener('submit', function(event) {
            if (checkboxes.length > 0 && !syncGroupValidity()) {
                event.preventDefault();
                checkboxes[0].focus();
            }
        });

        syncSelectedCount();
    });
</script>
<?= $this->endSection() ?>
