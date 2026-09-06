<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Anggota DPRD</h1>
    </div>
    <a href="<?= base_url('admin/anggota/create') ?>" class="py-2 px-3 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition">
        <i data-lucide="plus" class="size-4"></i>
        Tambah Anggota
    </a>
</div>

<section class="bg-white border border-slate-200 shadow-xs rounded-2xl dark:bg-slate-900 dark:border-slate-800 overflow-hidden">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 px-5 py-4">
        <div class="flex items-center gap-2.5">
            <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                <i data-lucide="users" class="size-4"></i>
            </div>
            <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Daftar Anggota</h2>
        </div>
        <span class="py-0.5 px-2.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
            <?= count($members) ?> anggota
        </span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto">
            <table class="table w-full text-start divide-y divide-slate-200 dark:divide-slate-800 admin-data-table" id="table-anggota" data-admin-datatable data-dt-order='[[1,"asc"]]'>
                <thead class="bg-slate-50/75 dark:bg-slate-800/40">
                    <tr>
                        <th class="dt-row-number no-sort px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">No</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Nama Lengkap</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Fraksi</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Komisi</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">No WhatsApp</th>
                        <th class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                        <th class="px-4 py-3 text-end text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs sm:text-sm">
                    <?php foreach ($members as $m): ?>
                        <tr class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="dt-row-number px-4 py-3.5" data-label="No"></td>
                            <td class="px-4 py-3.5" data-label="Nama Lengkap">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 text-xs font-bold">
                                        <?= esc(strtoupper(substr($m['name'], 0, 1))) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-slate-900 dark:text-white"><?= esc($m['name']) ?></div>
                                        <?php if (! empty($m['jabatan'])): ?>
                                            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"><?= esc($m['jabatan']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-slate-600 dark:text-slate-400" data-label="Fraksi"><?= esc($m['fraksi']) ?></td>
                            <td class="px-4 py-3.5" data-label="Komisi">
                                <span class="py-0.5 px-2 rounded-md text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    <?= esc($m['komisi']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-slate-600 dark:text-slate-400" data-label="No WhatsApp"><?= esc($m['no_wa']) ?></td>
                            <td class="px-4 py-3.5 whitespace-nowrap" data-label="Status">
                                <?php if ($m['aktif']): ?>
                                    <span class="inline-flex items-center gap-1.5 py-0.5 px-2 rounded-md text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 py-0.5 px-2 rounded-md text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                        <span class="size-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-end" data-label="Aksi">
                                <div class="member-row-actions flex items-center justify-end gap-1.5">
                                    <?php if (! empty($m['aktif'])): ?>
                                        <form method="post" action="<?= base_url("admin/anggota/{$m['id']}/otp-darurat") ?>"
                                            class="m-0 inline-flex"
                                            data-confirm-message="Buat OTP darurat untuk <?= esc($m['name']) ?>? Pastikan identitas anggota sudah diverifikasi.">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50 shadow-xs transition cursor-pointer" title="Buat OTP darurat">
                                                <i data-lucide="key-round" class="size-3.5"></i>
                                                OTP
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= base_url("admin/anggota/{$m['id']}/edit") ?>" class="py-1.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition" title="Edit Anggota">
                                        <i data-lucide="pencil" class="size-3.5"></i>
                                        Edit
                                    </a>
                                    <form method="post" action="<?= base_url("admin/anggota/{$m['id']}/delete") ?>"
                                        data-confirm-message="Hapus anggota <?= esc($m['name']) ?>?" class="m-0 inline-flex">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="p-1.5 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer" title="Hapus Anggota">
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
