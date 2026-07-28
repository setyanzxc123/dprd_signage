<?= $this->extend('admin/layouts/main') ?>



<?= $this->section('content') ?>

<?php
    $members = $members ?? [];
    $selectedAnggotaIds = array_map('intval', $selectedAnggotaIds ?? []);
    $unitNama = $unit['nama'] ?? '';
?>

<div class="page-header">
    <h1 class="page-title"><?= esc($pageTitle) ?></h1>
</div>

<form action="<?= esc($action_url) ?>" method="POST" id="unit-form" class="participant-group-form" data-turbo="true">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="alert alert-error shadow-sm mb-3" role="alert">
            <i data-lucide="triangle-alert" class="w-4 h-4"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-12 gap-3 participant-group-grid">
        <div class="col-span-12 participant-info-col">
            <section class="card card-border bg-base-100 shadow-sm">
                <div class="card-body gap-4 p-4 sm:p-5">
                    <h2 class="card-title text-base">
                        <i data-lucide="users-round" class="h-5 w-5 text-primary"></i>
                        Informasi Kelompok
                    </h2>

                    <fieldset class="fieldset grid grid-cols-12 gap-3">
                        <div class="col-span-12 lg:col-span-8">
                            <label class="label py-1 font-semibold" for="nama">
                                Nama Kelompok <span class="text-error">*</span>
                            </label>
                            <input type="text" class="input w-full" id="nama" name="nama"
                                value="<?= esc($unitNama) ?>"
                                placeholder="Masukkan nama kelompok"
                                required />
                        </div>

                        <div class="col-span-12 lg:col-span-4">
                            <label class="label py-1 font-semibold" for="aktif">Status</label>
                            <label class="label h-10 w-full cursor-pointer items-center justify-between rounded-lg border border-base-300 px-3" for="aktif">
                                <span class="font-semibold" id="aktif-label">
                                    <?= ($unit['aktif'] ?? 1) ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                                <input class="toggle toggle-primary" type="checkbox" role="switch"
                                    id="aktif" name="aktif" value="1"
                                    <?= ($unit['aktif'] ?? 1) ? 'checked' : '' ?> />
                            </label>
                        </div>
                    </fieldset>
                </div>
            </section>
        </div>

        <div class="col-span-12 participant-member-col">
            <section class="card card-border bg-base-100 shadow-sm">
                <div class="card-body participant-member-card gap-4 p-4 sm:p-5">
                    <h2 class="card-title participant-member-head flex items-center justify-between gap-2 text-base">
                        <span class="flex items-center gap-2">
                            <i data-lucide="contact-round" class="h-5 w-5 text-primary"></i>
                            Anggota Kelompok
                        </span>
                        <span class="badge badge-primary badge-sm" id="member-count-badge">
                            <?= count($selectedAnggotaIds) ?> dipilih
                        </span>
                    </h2>

                <?php if (empty($members)): ?>
                    <div class="alert alert-warning">
                        <i data-lucide="triangle-alert" class="w-4 h-4"></i>
                        <span>Belum ada anggota DPRD aktif.</span>
                    </div>
                    <div class="text-error text-xs mt-2 hidden" id="anggota-kelompok-error">
                        Kelompok peserta aktif wajib memiliki minimal satu anggota.
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-12 gap-3 participant-member-grid">
                        <div class="col-span-12 md:col-span-6 participant-source-col">
                            <section class="card card-border card-sm h-full overflow-hidden participant-list-panel">
                                <div class="card-body gap-0 p-0">
                                    <div class="flex items-center justify-between border-b border-base-300 bg-base-200 px-3 py-2">
                                        <span class="font-semibold">Tersedia</span>
                                        <span class="badge badge-neutral badge-sm" id="source-count"><?= count($members) ?></span>
                                    </div>
                                    <div class="border-b border-base-300 bg-base-100 p-2">
                                        <input type="text" class="input input-sm w-full" id="source-search"
                                            placeholder="Cari anggota" autocomplete="off" />
                                    </div>
                                    <div class="overflow-y-auto md:h-[240px] participant-source-list" id="source-list">
                                    <?php foreach ($members as $member):
                                        $memberId = (int) $member['id'];
                                        $checked = in_array($memberId, $selectedAnggotaIds, true);
                                        $inputId = 'src-' . $memberId;
                                        $komisiLabel = $member['komisi'] ?: 'Tanpa komisi';
                                    ?>
                                        <label class="flex items-center gap-2 px-3 py-1 border-b border-base-300 mb-0 anggota-source min-h-[42px] hover:bg-base-200 cursor-pointer <?= $checked ? 'bg-primary/10 text-primary' : '' ?>"
                                            for="<?= esc($inputId, 'attr') ?>"
                                            data-id="<?= $memberId ?>"
                                            data-name="<?= esc(strtolower($member['name']), 'attr') ?>"
                                            data-komisi="<?= esc(strtolower($komisiLabel), 'attr') ?>"
                                            data-jabatan="<?= esc(strtolower($member['jabatan'] ?? ''), 'attr') ?>">
                                            <input class="checkbox checkbox-primary checkbox-sm source-checkbox"
                                                type="checkbox"
                                                id="<?= esc($inputId, 'attr') ?>"
                                                name="anggota_unit_rapat[]"
                                                value="<?= $memberId ?>"
                                                <?= $checked ? 'checked' : '' ?> />
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-semibold truncate"><?= esc($member['name']) ?></div>
                                                <div class="member-detail text-[11px] text-base-content/60 truncate">
                                                    <?= esc($member['jabatan'] ?: '-') ?>
                                                    &middot; <?= esc($komisiLabel) ?>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="col-span-12 md:col-span-6 participant-target-col">
                            <section class="card card-border card-sm h-full overflow-hidden participant-list-panel">
                                <div class="card-body gap-0 p-0">
                                    <div class="flex items-center justify-between border-b border-base-300 bg-base-200 px-3 py-2">
                                        <span class="font-semibold">Terpilih</span>
                                        <span class="badge badge-primary badge-sm" id="target-count"><?= count($selectedAnggotaIds) ?></span>
                                    </div>
                                    <div class="overflow-y-auto md:h-[282px] participant-target-list" id="target-list">
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
                                        <div class="flex items-center gap-2 px-3 py-1 border-b border-base-300 transfer-target-item min-h-[42px]"
                                            id="target-<?= $memberId ?>" data-id="<?= $memberId ?>">
                                            <span class="inline-flex items-center justify-center rounded shrink-0 bg-primary text-primary-content w-7 h-7 text-xs font-bold">
                                                <?= esc($initial) ?>
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-semibold truncate"><?= esc($member['name']) ?></div>
                                                <div class="member-detail text-[11px] text-base-content/60 truncate">
                                                    <?= esc($member['jabatan'] ?: '-') ?>
                                                    &middot; <?= esc($komisiLabel) ?>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-ghost btn-circle text-error w-6 h-6 min-h-6"
                                                title="Hapus dari unit" data-remove-member="<?= $memberId ?>">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (! $hasSelected): ?>
                                        <div class="flex flex-col items-center justify-center text-base-content/40 py-4 gap-1" id="target-empty">
                                            <i data-lucide="shuffle" class="w-5 h-5 opacity-40"></i>
                                            <small>Pilih anggota dari panel kiri</small>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                    <div class="text-error text-xs mt-2 hidden" id="anggota-kelompok-error">
                        Kelompok peserta aktif wajib memiliki minimal satu anggota.
                    </div>
                <?php endif; ?>
                </div>
            </section>
        </div>

    </div>

    <div class="flex gap-2 mt-4 participant-form-actions">
        <a href="<?= base_url('admin/unit-rapat') ?>" class="btn btn-outline sm:btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary sm:btn-sm">
            <i data-lucide="check" class="w-4 h-4"></i>
            <?= $unit ? 'Simpan Perubahan' : 'Simpan Kelompok' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
