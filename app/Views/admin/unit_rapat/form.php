<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
    $members = $members ?? [];
    $selectedAnggotaIds = array_map('intval', $selectedAnggotaIds ?? []);
    $unitNama = $unit['nama'] ?? '';
?>

<div class="mb-5">
    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?= esc($pageTitle) ?></h1>
</div>

<form action="<?= esc($action_url) ?>" method="POST" id="unit-form" class="participant-group-form max-w-5xl">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300 flex items-center gap-3">
            <i data-lucide="triangle-alert" class="size-5 shrink-0 text-rose-500"></i>
            <span class="text-sm font-medium"><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="space-y-5 participant-group-grid">
        <!-- Informasi Kelompok -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 p-5 sm:p-6 space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i data-lucide="users-round" class="size-4 text-emerald-500"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Informasi Kelompok</h2>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 lg:col-span-8">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="nama">
                        Nama Kelompok <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="nama" name="nama"
                        value="<?= esc($unitNama) ?>"
                        placeholder="Contoh: Komisi I, Pansus LKPJ, atau Bapemperda"
                        required />
                </div>

                <div class="col-span-12 lg:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="aktif">Status Kelompok</label>
                    <label class="flex items-center justify-between p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer h-[42px]" for="aktif">
                        <span class="text-xs font-semibold text-slate-800 dark:text-slate-200" id="aktif-label">
                            <?= ($unit['aktif'] ?? 1) ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                        <input class="size-4 text-emerald-600 rounded focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700" type="checkbox"
                            id="aktif" name="aktif" value="1"
                            <?= ($unit['aktif'] ?? 1) ? 'checked' : '' ?> />
                    </label>
                </div>
            </div>
        </div>

        <!-- Anggota Kelompok (Transfer List Panels) -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <i data-lucide="contact-round" class="size-4 text-emerald-500"></i>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Anggota Kelompok</h2>
                </div>
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400" id="member-count-badge">
                    <?= count($selectedAnggotaIds) ?> dipilih
                </span>
            </div>

            <?php if (empty($members)): ?>
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-300 flex items-center gap-3">
                    <i data-lucide="triangle-alert" class="size-5 shrink-0 text-amber-500"></i>
                    <span class="text-sm">Belum ada data anggota DPRD aktif. Silakan tambahkan anggota di modul Anggota terlebih dahulu.</span>
                </div>
                <div class="text-rose-600 text-xs mt-2 hidden" id="anggota-kelompok-error">
                    Kelompok peserta aktif wajib memiliki minimal satu anggota.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 participant-member-grid">
                    <!-- Panel Kiri: Sumber Anggota -->
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-white dark:bg-slate-900 flex flex-col h-full">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/40 px-3.5 py-2.5">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Tersedia</span>
                            <span class="py-0.5 px-2 rounded-full text-[11px] font-medium bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300" id="source-count"><?= count($members) ?></span>
                        </div>
                        <div class="p-2 border-b border-slate-100 dark:border-slate-800">
                            <div class="relative">
                                <input type="text" class="py-1.5 px-2.5 ps-8 block w-full border border-slate-200 rounded-lg text-xs placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="source-search"
                                    placeholder="Cari nama, komisi, jabatan..." autocomplete="off" aria-label="Cari anggota" />
                                <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-2.5">
                                    <i data-lucide="search" class="size-3.5 text-slate-400"></i>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-y-auto max-h-72 md:h-72 divide-y divide-slate-100 dark:divide-slate-800" id="source-list">
                            <?php foreach ($members as $member):
                                $memberId = (int) $member['id'];
                                $checked = in_array($memberId, $selectedAnggotaIds, true);
                                $inputId = 'src-' . $memberId;
                                $komisiLabel = $member['komisi'] ?: 'Tanpa komisi';
                            ?>
                                <label class="flex items-center gap-2.5 px-3 py-2.5 anggota-source min-h-[44px] hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition <?= $checked ? 'bg-emerald-50/70 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 font-semibold' : '' ?>"
                                    for="<?= esc($inputId, 'attr') ?>"
                                    data-id="<?= $memberId ?>"
                                    data-name="<?= esc(strtolower($member['name']), 'attr') ?>"
                                    data-komisi="<?= esc(strtolower($komisiLabel), 'attr') ?>"
                                    data-jabatan="<?= esc(strtolower($member['jabatan'] ?? ''), 'attr') ?>">
                                    <input class="size-4 text-emerald-600 rounded focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 source-checkbox"
                                        type="checkbox"
                                        id="<?= esc($inputId, 'attr') ?>"
                                        name="anggota_unit_rapat[]"
                                        value="<?= $memberId ?>"
                                        <?= $checked ? 'checked' : '' ?> />
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-semibold text-slate-900 dark:text-white truncate"><?= esc($member['name']) ?></div>
                                        <div class="member-detail text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                            <?= esc($member['jabatan'] ?: '-') ?>
                                            &middot; <?= esc($komisiLabel) ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Panel Kanan: Anggota Terpilih -->
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-white dark:bg-slate-900 flex flex-col h-full">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/40 px-3.5 py-2.5">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Terpilih</span>
                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400" id="target-count"><?= count($selectedAnggotaIds) ?></span>
                        </div>
                        <div class="overflow-y-auto max-h-84 md:h-[326px] divide-y divide-slate-100 dark:divide-slate-800" id="target-list">
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
                                <div class="flex items-center gap-2.5 px-3 py-2 transfer-target-item min-h-[44px]"
                                    id="target-<?= $memberId ?>" data-id="<?= $memberId ?>">
                                    <span class="inline-flex items-center justify-center rounded-lg shrink-0 bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 size-7 text-xs font-bold">
                                        <?= esc($initial) ?>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-semibold text-slate-900 dark:text-white truncate"><?= esc($member['name']) ?></div>
                                        <div class="member-detail text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                            <?= esc($member['jabatan'] ?: '-') ?>
                                            &middot; <?= esc($komisiLabel) ?>
                                        </div>
                                    </div>
                                    <button type="button" class="size-8 inline-flex items-center justify-center rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer"
                                        title="Hapus dari kelompok" aria-label="Hapus <?= esc($member['name']) ?> dari kelompok" data-remove-member="<?= $memberId ?>">
                                        <i data-lucide="x" class="size-4"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                            <?php if (! $hasSelected): ?>
                                <div class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 py-8 gap-1.5" id="target-empty">
                                    <i data-lucide="shuffle" class="size-5 text-slate-300 dark:text-slate-600"></i>
                                    <span class="text-xs">Pilih anggota dari panel kiri</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="text-rose-600 dark:text-rose-400 text-xs mt-2 hidden font-semibold" id="anggota-kelompok-error">
                    Kelompok peserta aktif wajib memiliki minimal satu anggota.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3 max-w-5xl">
        <a href="<?= base_url('admin/unit-rapat') ?>" class="py-2.5 px-4 inline-flex items-center text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition">
            Batal
        </a>
        <button type="submit" class="py-2 px-4 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer">
            <i data-lucide="check" class="size-4"></i>
            <?= $unit ? 'Simpan Perubahan' : 'Simpan Kelompok' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
