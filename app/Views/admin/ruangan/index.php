<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Ruangan Rapat</h1>
    </div>
    <a href="<?= base_url('admin/ruangan/create') ?>" class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition">
        <i data-lucide="plus" class="size-4"></i>
        Tambah Ruangan
    </a>
</div>

<section class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 overflow-hidden">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 px-5 py-4">
        <div class="flex items-center gap-2.5">
            <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                <i data-lucide="door-open" class="size-4"></i>
            </div>
            <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Daftar Ruangan</h2>
        </div>
        <span class="py-0.5 px-2.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
            <?= count($rooms) ?> ruangan
        </span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto">
            <table class="table w-full text-start divide-y divide-slate-200 dark:divide-slate-800 admin-data-table" data-admin-datatable data-dt-order='[[1,"asc"]]'>
                <thead class="bg-slate-50/75 dark:bg-slate-800/40">
                    <tr>
                        <th class="dt-row-number no-sort px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">No</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Nama Ruangan</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Kapasitas</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                        <th class="px-4 py-3 text-end text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs sm:text-sm">
                    <?php foreach ($rooms as $r): ?>
                        <tr class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="dt-row-number px-4 py-3.5" data-label="No"></td>
                            <td class="px-4 py-3.5" data-label="Nama Ruangan">
                                <div class="text-sm font-bold text-slate-900 dark:text-white"><?= esc($r['name']) ?></div>
                                <?php if (! empty($r['keterangan'])): ?>
                                    <div class="mt-0.5 max-w-sm truncate text-xs text-slate-500 dark:text-slate-400" title="<?= esc($r['keterangan']) ?>">
                                        <?= esc($r['keterangan']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Kapasitas">
                                <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-md text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    <i data-lucide="users" class="size-3.5 text-slate-400"></i>
                                    <?= esc($r['kapasitas']) ?> orang
                                </span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Status">
                                <?php if ($r['tersedia']): ?>
                                    <span class="inline-flex items-center gap-1.5 py-0.5 px-2 rounded-md text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                        Tersedia
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 py-0.5 px-2 rounded-md text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                        <span class="size-1.5 rounded-full bg-slate-400"></span>
                                        Tidak Tersedia
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-end" data-label="Aksi">
                                <div class="room-row-actions flex items-center justify-end gap-1.5">
                                    <a href="<?= base_url("admin/ruangan/{$r['id']}/edit") ?>" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-blue-200/80 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 hover:border-blue-300 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-600 dark:hover:text-white dark:hover:border-blue-600 shadow-2xs transition" title="Edit Ruangan" aria-label="Edit ruangan <?= esc($r['name']) ?>">
                                        <i data-lucide="pencil" class="size-3.5"></i>
                                        Edit
                                    </a>
                                    <form method="post" action="<?= base_url("admin/ruangan/{$r['id']}/delete") ?>"
                                        data-confirm-message="Hapus ruangan <?= esc($r['name']) ?>?" class="m-0 inline-flex">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="p-1.5 inline-flex items-center justify-center rounded-lg border border-rose-200/80 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 hover:border-rose-300 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-600 dark:hover:text-white dark:hover:border-rose-600 shadow-2xs transition cursor-pointer" title="Hapus Ruangan" aria-label="Hapus ruangan <?= esc($r['name']) ?>">
                                            <i data-lucide="trash-2" class="size-4"></i>
                                        </button>
                                    </form>
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
