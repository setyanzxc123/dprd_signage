<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Kelompok Peserta</h1>
    </div>
    <a href="<?= base_url('admin/unit-rapat/create') ?>" class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition">
        <i data-lucide="plus" class="size-4"></i>
        Tambah Kelompok
    </a>
</div>

<section class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 overflow-hidden">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 px-5 py-4">
        <div class="flex items-center gap-2.5">
            <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                <i data-lucide="users-round" class="size-4"></i>
            </div>
            <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Daftar Kelompok Peserta</h2>
        </div>
        <span class="py-0.5 px-2.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
            <?= count($units) ?> kelompok
        </span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto">
            <table class="table w-full text-start divide-y divide-slate-200 dark:divide-slate-800 admin-data-table" data-admin-datatable data-dt-order='[[1,"asc"]]'>
                <thead class="bg-slate-50/75 dark:bg-slate-800/40">
                    <tr>
                        <th class="dt-row-number no-sort px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">No</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Nama Kelompok</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                        <th class="px-4 py-3 text-end text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs sm:text-sm">
                    <?php foreach ($units as $unit): ?>
                        <tr class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="dt-row-number px-4 py-3.5" data-label="No"></td>
                            <td class="px-4 py-3.5" data-label="Nama Kelompok">
                                <div class="text-sm font-bold text-slate-900 dark:text-white"><?= esc($unit['nama']) ?></div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Status">
                                <?php if ($unit['aktif']): ?>
                                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Aktif</span>
                                <?php else: ?>
                                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-end" data-label="Aksi">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= base_url("admin/unit-rapat/{$unit['id']}/edit") ?>" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-blue-200/80 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 hover:border-blue-300 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-600 dark:hover:text-white dark:hover:border-blue-600 shadow-2xs transition" title="Edit Kelompok" aria-label="Edit kelompok <?= esc($unit['nama']) ?>">
                                        <i data-lucide="pencil" class="size-3.5"></i>
                                        Edit
                                    </a>
                                    <?php if ($unit['aktif']): ?>
                                        <form method="post" action="<?= base_url("admin/unit-rapat/{$unit['id']}/delete") ?>"
                                            data-confirm-message="Nonaktifkan kelompok peserta <?= esc($unit['nama']) ?>? Kelompok tidak muncul di pilihan jadwal baru, tetapi riwayat jadwal lama tetap aman."
                                            class="m-0 inline-flex">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-rose-200/80 bg-rose-50 text-rose-700 hover:bg-rose-100 hover:text-rose-800 hover:border-rose-300 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-600 dark:hover:text-white dark:hover:border-rose-600 shadow-2xs transition cursor-pointer" title="Nonaktifkan" aria-label="Nonaktifkan kelompok <?= esc($unit['nama']) ?>">
                                                <i data-lucide="circle-off" class="size-3.5"></i>
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
