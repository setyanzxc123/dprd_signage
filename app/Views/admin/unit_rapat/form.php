<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .participant-group-form .form-card {
        padding: 18px;
    }

    .participant-group-form .form-section-title {
        margin-bottom: 12px;
        padding-bottom: 8px;
    }

    .participant-group-form .ta-label {
        margin-bottom: 4px;
        font-size: 0.82rem;
    }

    .participant-group-form .ta-help {
        font-size: 0.72rem;
    }

    .participant-group-form .compact-list {
        max-height: 240px;
        overflow-y: auto;
    }

    .participant-group-form .compact-target-list {
        max-height: 282px;
        overflow-y: auto;
    }

    .participant-group-form .member-row {
        min-height: 42px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $members = $members ?? [];
    $selectedAnggotaIds = array_map('intval', $selectedAnggotaIds ?? []);
    $unitNama = $unit['nama'] ?? '';
?>

<div class="page-header">
    <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    <p class="page-subtitle">
        <?= $unit ? 'Perbarui kelompok internal DPRD' : 'Tambahkan kelompok internal DPRD untuk target rapat' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="POST" id="unit-form" class="participant-group-form">
    <?= csrf_field() ?>

    <div class="grid grid-cols-12 gap-3">
        <div class="lg:col-span-4">
            <div class="form-card">
                <div class="form-section-title">Informasi Kelompok</div>

                <div class="mb-2">
                    <label class="ta-label font-semibold" for="nama">
                        Nama Kelompok <span class="text-red-600">*</span>
                    </label>
                    <input type="text" class="ta-input" id="nama" name="nama"
                        value="<?= esc($unitNama) ?>"
                        placeholder="Contoh: Pimpinan DPRD, Komisi I, Badan Anggaran"
                        required />
                    <div class="ta-help mt-1">
                        Kelompok peserta adalah target internal DPRD untuk rapat dan notifikasi WA. Bisa berisi satu atau banyak anggota.
                    </div>
                </div>

                <div>
                    <label class="ta-label font-semibold">Status</label>
                    <div class="ta-check ta-switch mt-1">
                        <input class="ta-check-input" type="checkbox" role="switch"
                            id="aktif" name="aktif" value="1"
                            <?= ($unit['aktif'] ?? 1) ? 'checked' : '' ?> />
                        <label class="ta-check-label font-semibold" for="aktif" id="aktif-label">
                            <?= ($unit['aktif'] ?? 1) ? 'Aktif' : 'Nonaktif' ?>
                        </label>
                    </div>
                    <div class="ta-help mt-1">Nonaktif tidak muncul di pilihan target jadwal baru.</div>
                </div>
            </div>

            <div class="form-card mt-2">
                <div class="form-section-title">Ringkasan</div>
                <div class="flex items-center gap-2">
                    <span class="ta-badge bg-gray-100 rounded-full text-base" id="sidebar-selected-count"><?= count($selectedAnggotaIds) ?></span>
                    <small class="text-gray-500">anggota terpilih</small>
                </div>
            </div>
        </div>

        <div class="lg:col-span-8">
            <div class="form-card">
                <div class="form-section-title">
                    Anggota Kelompok
                    <span class="ta-badge bg-gray-100 ml-2" id="member-count-badge" style="font-size:11px;">
                        <?= count($selectedAnggotaIds) ?>
                    </span>
                </div>

                <?php if (empty($members)): ?>
                    <div class="ta-alert ta-alert-warning mb-0 py-2 px-3">
                        <i data-lucide="triangle-alert" class="mr-1"></i>
                        Belum ada anggota DPRD aktif. Tambahkan melalui menu <strong>Anggota DPRD</strong>.
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-12 gap-3">
                        <div class="md:col-span-6">
                            <div class="border rounded overflow-hidden">
                                <div class="flex items-center justify-between px-3 py-1 border-b bg-gray-50">
                                    <span class="text-xs font-bold text-gray-500 uppercase">Tersedia</span>
                                    <span class="ta-badge bg-gray-100 rounded-full" id="source-count"><?= count($members) ?></span>
                                </div>
                                <div class="p-2 border-b">
                                    <input type="text" class="ta-input ta-input-sm" id="source-search"
                                        placeholder="Cari nama, jabatan, atau komisi..." autocomplete="off" />
                                </div>
                                <div class="compact-list" id="source-list">
                                    <?php foreach ($members as $member):
                                        $memberId = (int) $member['id'];
                                        $checked = in_array($memberId, $selectedAnggotaIds, true);
                                        $inputId = 'src-' . $memberId;
                                        $komisiLabel = $member['komisi'] ?: 'Tanpa komisi';
                                    ?>
                                        <label class="flex items-center gap-2 px-3 py-1 border-b mb-0 anggota-source member-row"
                                            for="<?= esc($inputId, 'attr') ?>"
                                            data-id="<?= $memberId ?>"
                                            data-name="<?= esc(strtolower($member['name']), 'attr') ?>"
                                            data-komisi="<?= esc(strtolower($komisiLabel), 'attr') ?>"
                                            data-jabatan="<?= esc(strtolower($member['jabatan'] ?? ''), 'attr') ?>"
                                            style="cursor: pointer;">
                                            <input class="ta-check-input source-checkbox"
                                                type="checkbox"
                                                id="<?= esc($inputId, 'attr') ?>"
                                                name="anggota_unit_rapat[]"
                                                value="<?= $memberId ?>"
                                                <?= $checked ? 'checked' : '' ?> />
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-semibold truncate"><?= esc($member['name']) ?></div>
                                                <div class="text-gray-500 truncate" style="font-size:11px;">
                                                    <?= esc($member['jabatan'] ?: '-') ?>
                                                    &middot; <?= esc($komisiLabel) ?>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-6">
                            <div class="border rounded overflow-hidden">
                                <div class="flex items-center justify-between px-3 py-1 border-b bg-gray-50">
                                    <span class="text-xs font-bold text-gray-500 uppercase">Terpilih</span>
                                    <span class="ta-badge bg-brand-500 rounded-full" id="target-count"><?= count($selectedAnggotaIds) ?></span>
                                </div>
                                <div class="compact-target-list" id="target-list">
                                    <?php
                                    $hasSelected = false;
                                    foreach ($members as $member):
                                        $memberId = (int) $member['id'];
                                        if (! in_array($memberId, $selectedAnggotaIds, true)) {
                                            continue;
                                        }
                                        $hasSelected = true;
                                        $komisiLabel = $member['komisi'] ?: 'Tanpa komisi';
                                        $initial = mb_strtoupper(mb_substr($member['name'], 0, 1));
                                    ?>
                                        <div class="flex items-center gap-2 px-3 py-1 border-b transfer-target-item member-row"
                                            id="target-<?= $memberId ?>" data-id="<?= $memberId ?>">
                                            <span class="inline-flex items-center justify-center rounded shrink-0 bg-brand-500 text-white"
                                                style="width:28px;height:28px;font-size:12px;font-weight:700;">
                                                <?= esc($initial) ?>
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-semibold truncate"><?= esc($member['name']) ?></div>
                                                <div class="text-gray-500 truncate" style="font-size:11px;">
                                                    <?= esc($member['jabatan'] ?: '-') ?>
                                                    &middot; <?= esc($komisiLabel) ?>
                                                </div>
                                            </div>
                                            <button type="button" class="ta-btn ta-btn-sm text-gray-500 p-0 border-0"
                                                title="Hapus dari unit"
                                                onclick="removeMember(<?= $memberId ?>)"
                                                style="width:24px;height:24px;line-height:1;">
                                                <i data-lucide="x" style="width:12px;height:12px;"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (! $hasSelected): ?>
                                        <div class="flex flex-col items-center justify-center text-gray-500 py-4 gap-1" id="target-empty">
                                            <i data-lucide="shuffle" style="width:20px;height:20px;opacity:0.4;"></i>
                                            <small>Pilih anggota dari panel kiri</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="flex gap-2 mt-3">
        <a href="<?= base_url('admin/unit-rapat') ?>" class="ta-btn ta-btn-outline-gray">
            <i data-lucide="arrow-left" class="mr-1"></i>Batal
        </a>
        <button type="submit" class="ta-btn ta-btn-primary">
            <i data-lucide="check" class="mr-1"></i>
            <?= $unit ? 'Simpan Perubahan' : 'Simpan Kelompok' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const aktifToggle = document.getElementById('aktif');
        const aktifLabel = document.getElementById('aktif-label');
        const sourceSearch = document.getElementById('source-search');
        const targetList = document.getElementById('target-list');
        const targetCount = document.getElementById('target-count');
        const sourceCount = document.getElementById('source-count');
        const memberCountBadge = document.getElementById('member-count-badge');
        const sidebarSelectedCount = document.getElementById('sidebar-selected-count');
        const allSourceItems = document.querySelectorAll('.anggota-source');
        const allCheckboxes = document.querySelectorAll('.source-checkbox');

        aktifToggle?.addEventListener('change', function() {
            if (aktifLabel) aktifLabel.textContent = this.checked ? 'Aktif' : 'Nonaktif';
        });

        const updateTargetPanel = function() {
            if (!targetList || !targetCount) return;

            const checked = document.querySelectorAll('.source-checkbox:checked');
            const count = checked.length;

            targetCount.textContent = count;
            if (memberCountBadge) memberCountBadge.textContent = count;
            if (sidebarSelectedCount) sidebarSelectedCount.textContent = count;

            targetList.querySelectorAll('.transfer-target-item').forEach(el => el.remove());

            const emptyEl = targetList.querySelector('#target-empty');
            if (emptyEl) emptyEl.remove();

            if (count === 0) {
                const div = document.createElement('div');
                div.className = 'flex flex-col items-center justify-center text-gray-500 py-4 gap-1';
                div.id = 'target-empty';
                div.innerHTML = '<i data-lucide="shuffle" style="width:20px;height:20px;opacity:0.4;"></i><small>Pilih anggota dari panel kiri</small>';
                targetList.appendChild(div);
                window.renderAdminIcons?.();
                return;
            }

            checked.forEach(function(cb) {
                const src = cb.closest('.anggota-source');
                if (!src) return;

                const id = src.getAttribute('data-id');
                const name = src.querySelector('.font-semibold')?.textContent || '';
                const detail = src.querySelector('.text-gray-500')?.textContent?.trim() || '';
                const initial = name.trim().charAt(0).toUpperCase();

                const el = document.createElement('div');
                el.className = 'flex items-center gap-2 px-3 py-1 border-b transfer-target-item member-row';
                el.id = 'target-' + id;
                el.setAttribute('data-id', id);
                el.innerHTML =
                    '<span class="inline-flex items-center justify-center rounded shrink-0 bg-brand-500 text-white" style="width:28px;height:28px;font-size:12px;font-weight:700;">' + initial + '</span>' +
                    '<div class="flex-1 min-w-0">' +
                        '<div class="text-xs font-semibold truncate">' + name + '</div>' +
                        '<div class="text-gray-500 truncate" style="font-size:11px;">' + detail + '</div>' +
                    '</div>' +
                    '<button type="button" class="ta-btn ta-btn-sm text-gray-500 p-0 border-0" title="Hapus dari unit" style="width:24px;height:24px;line-height:1;">' +
                        '<i data-lucide="x" style="width:12px;height:12px;"></i>' +
                    '</button>';

                el.querySelector('button').addEventListener('click', function() {
                    removeMember(parseInt(id));
                });

                targetList.appendChild(el);
            });

            window.renderAdminIcons?.();
        };

        allCheckboxes.forEach(function(cb) {
            cb.addEventListener('change', updateTargetPanel);
        });

        window.removeMember = function(memberId) {
            const cb = document.getElementById('src-' + memberId);
            if (cb) {
                cb.checked = false;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        sourceSearch?.addEventListener('input', function() {
            const q = (this.value || '').trim().toLowerCase();
            let n = 0;
            allSourceItems.forEach(function(item) {
                const name = item.getAttribute('data-name') || '';
                const komisi = item.getAttribute('data-komisi') || '';
                const jabatan = item.getAttribute('data-jabatan') || '';
                const match = name.includes(q) || komisi.includes(q) || jabatan.includes(q);
                item.style.display = match ? '' : 'none';
                if (match) n++;
            });
            if (sourceCount) sourceCount.textContent = n;
        });
    });
</script>
<?= $this->endSection() ?>
