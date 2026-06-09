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

    <?php
        $members = $members ?? [];
        $selectedAnggotaIds = array_map('intval', $selectedAnggotaIds ?? []);
    ?>

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

            <div class="form-card mt-3" id="manual-member-card">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
                    <div class="form-section-title mb-0">Anggota Unit</div>
                    <div class="position-relative" style="width: 100%; max-width: 250px;">
                        <span class="position-absolute start-0 top-50 translate-middle-y ps-3 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control form-control-sm ps-5" id="search-anggota" placeholder="Cari nama atau komisi..." />
                    </div>
                </div>

                <?php if (empty($members)): ?>
                    <div class="alert alert-warning py-2 px-3 mb-0">
                        Belum ada anggota aktif.
                    </div>
                <?php else: ?>
                    <div class="row g-2 overflow-y-auto" style="max-height: 300px; padding-right: 5px;" id="anggota-list-container">
                        <?php foreach ($members as $member):
                            $memberId = (int) $member['id'];
                            $checked = in_array($memberId, $selectedAnggotaIds, true) ? 'checked' : '';
                            $inputId = 'anggota-unit-' . $memberId;
                        ?>
                            <div class="col-md-6 anggota-item"
                                 data-name="<?= esc(strtolower($member['name']), 'attr') ?>"
                                 data-komisi="<?= esc(strtolower($member['komisi'] ?: 'tanpa komisi'), 'attr') ?>">
                                <label class="form-check border rounded px-3 py-2 h-100" for="<?= esc($inputId, 'attr') ?>" style="cursor: pointer;">
                                    <input class="form-check-input me-2" type="checkbox"
                                        id="<?= esc($inputId, 'attr') ?>"
                                        name="anggota_unit_rapat[]"
                                        value="<?= $memberId ?>"
                                        <?= $checked ?> />
                                    <span class="fw-semibold"><?= esc($member['name']) ?></span>
                                    <span class="d-block text-muted small"><?= esc($member['komisi'] ?: 'Tanpa komisi') ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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

            <div class="form-card mt-3" id="auto-member-card">
                <div class="form-section-title">Keanggotaan Otomatis</div>
                <div class="form-text">
                    Unit jenis komisi memakai data komisi utama pada menu Anggota DPRD. Unit Seluruh Anggota memakai semua anggota aktif.
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

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jenisInput = document.getElementById('jenis');
        const namaInput = document.getElementById('nama');
        const manualCard = document.getElementById('manual-member-card');
        const autoCard = document.getElementById('auto-member-card');

        const syncMemberMode = function() {
            const jenis = jenisInput?.value || 'lainnya';
            const nama = (namaInput?.value || '').trim().toLowerCase();
            const isAutomatic = jenis === 'komisi' || nama === 'seluruh anggota';

            if (manualCard) {
                manualCard.style.display = isAutomatic ? 'none' : '';
            }
            if (autoCard) {
                autoCard.style.display = isAutomatic ? '' : 'none';
            }
        };

        jenisInput?.addEventListener('change', syncMemberMode);
        namaInput?.addEventListener('input', syncMemberMode);
        syncMemberMode();

        // Pencarian/filtering anggota secara real-time
        const searchInput = document.getElementById('search-anggota');
        const items = document.querySelectorAll('.anggota-item');

        searchInput?.addEventListener('input', function() {
            const query = (this.value || '').trim().toLowerCase();
            items.forEach(function(item) {
                const name = item.getAttribute('data-name') || '';
                const komisi = item.getAttribute('data-komisi') || '';
                if (name.includes(query) || komisi.includes(query)) {
                    item.style.setProperty('display', '', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>
