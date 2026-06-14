<?= $this->extend('admin/layouts/main') ?>



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
            <div class="form-card p-[18px]">

                <div class="form-section-title mb-3 pb-2">Informasi Anggota</div>

                <div class="grid grid-cols-12 gap-3">

                    <div class="col-span-12">
                        <label class="label-text font-bold text-sm mb-1 block" for="name">
                            Nama Lengkap <span class="text-error">*</span>
                        </label>
                        <input type="text" class="input input-bordered w-full" id="name" name="name"
                            value="<?= esc($member['name'] ?? '') ?>" placeholder="Contoh: H. Ahmad Fauzi, S.H., M.M."
                            required />
                    </div>

                    <div class="md:col-span-6">
                        <label class="label-text font-bold text-sm mb-1 block" for="jabatan">Jabatan</label>
                        <input type="text" class="input input-bordered w-full" id="jabatan" name="jabatan"
                            value="<?= esc($member['jabatan'] ?? '') ?>" placeholder="Contoh: Ketua Komisi III" />
                    </div>

                    <div class="md:col-span-6">
                        <label class="label-text font-bold text-sm mb-1 block" for="fraksi">
                            Fraksi <span class="text-error">*</span>
                        </label>
                        <select class="select select-bordered w-full" id="fraksi" name="fraksi" required>
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
                        <label class="label-text font-bold text-sm mb-1 block" for="komisi">Komisi</label>
                        <select class="select select-bordered w-full" id="komisi" name="komisi">
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
                        <label class="label-text font-bold text-sm mb-1 block" for="status">Status</label>
                        <select class="select select-bordered w-full" id="status" name="aktif">
                            <option value="1" <?= ($member['aktif'] ?? 1) ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= !($member['aktif'] ?? 1) ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="form-card mt-2 p-[18px]">
                <div class="form-section-title mb-3 pb-2">Kontak WhatsApp</div>

                <div class="grid grid-cols-12 gap-3 items-start">
                    <div class="md:col-span-6">
                        <label class="label-text font-bold text-sm mb-1 block" for="no_wa">
                            Nomor WhatsApp <span class="text-error">*</span>
                        </label>
                        <div class="join w-full">
                            <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold font-mono">+62</span>
                            <input type="text" class="input input-bordered join-item flex-1 w-full" id="no_wa" name="no_wa"
                                value="<?= esc($member['no_wa'] ?? '') ?>" placeholder="8123456789" required />
                        </div>
                        <div class="label-text-alt text-base-content/60 mt-1">Format tanpa 0 di depan. Contoh: 8123456789</div>
                    </div>

                    <?php if ($member): ?>
                        <div class="md:col-span-6">
                            <div class="alert alert-info py-2 px-3 text-xs mb-0 flex gap-2">
                                <i data-lucide="info" class="w-4 h-4"></i>
                                <span>Mengubah nomor WA akan mempengaruhi pengiriman notifikasi rapat.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom kanan: Kelompok Peserta -->
        <div class="lg:col-span-4">
            <div class="form-card p-[18px]">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="form-section-title mb-0">Kelompok Peserta</div>
                    <?php if (! empty($manual_units)): ?>
                        <span class="badge badge-primary badge-sm" id="kelompok-selected-count">
                            <?= $selected_group_count ?> dipilih
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (empty($manual_units)): ?>
                    <div class="alert alert-warning py-2 px-3 text-xs mb-0 flex gap-2">
                        <i data-lucide="triangle-alert" class="w-4 h-4"></i>
                        <span>Belum ada kelompok peserta aktif. <a href="<?= base_url('admin/unit-rapat/create') ?>" class="link link-primary font-semibold">Buat kelompok peserta</a> sebelum menambahkan anggota.</span>
                    </div>
                <?php else: ?>
                    <div class="label-text-alt text-base-content/60 mb-2">Pilih minimal satu kelompok peserta.</div>
                    <div class="relative mb-2">
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 pl-3 text-base-content/40">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" class="input input-sm input-bordered w-full pl-10"
                            id="kelompok-search" placeholder="Cari kelompok peserta..." autocomplete="off" />
                    </div>

                    <div class="border border-base-300 rounded overflow-hidden">
                        <div class="flex items-center justify-between px-3 py-1.5 border-b border-base-300 bg-base-200">
                            <span class="text-xs font-bold text-base-content/60 uppercase">Daftar Kelompok</span>
                            <span class="badge badge-neutral badge-sm rounded-full" id="kelompok-visible-count"><?= count($manual_units) ?></span>
                        </div>
                            <div class="flex flex-col max-h-[220px] overflow-y-auto" id="kelompok-list">
                                <?php foreach ($manual_units as $unit):
                                    $unitId = (int) $unit['id'];
                                    $checked = in_array($unitId, $selected_unit_ids, true) ? 'checked' : '';
                                    $inputId = 'unit-manual-' . $unitId;
                                ?>
                                    <label class="flex items-center gap-2 px-3 py-1.5 border-b border-base-300 mb-0 kelompok-option min-h-9 hover:bg-base-200 cursor-pointer <?= $checked ? 'bg-primary/10 text-primary' : '' ?>"
                                        for="<?= esc($inputId, 'attr') ?>"
                                        data-name="<?= esc(strtolower($unit['nama']), 'attr') ?>">
                                        <input class="checkbox checkbox-primary checkbox-xs" type="checkbox"
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
                                <div class="text-base-content/50 text-center py-2 hidden" id="kelompok-empty">
                                    <small>Kelompok tidak ditemukan.</small>
                                </div>
                            </div>
                    </div>
                    <div class="text-error text-xs mt-2 hidden" id="kelompok-peserta-error">
                        Pilih minimal satu kelompok peserta.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Tombol Aksi -->
    <div class="flex gap-2 mt-4">
        <a href="<?= base_url('admin/anggota') ?>" class="btn btn-outline btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary btn-sm" <?= empty($manual_units) ? 'disabled' : '' ?>>
            <i data-lucide="check" class="w-4 h-4"></i>
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
                if (option) {
                    option.classList.toggle('bg-primary/10', input.checked);
                    option.classList.toggle('text-primary', input.checked);
                }
            });
        };

        const syncGroupValidity = function() {
            const valid = hasSelectedGroup();
            if (error) error.classList.toggle('hidden', valid);
            checkboxes.forEach(function(input) {
                input.classList.toggle('checkbox-error', !valid);
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
