<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php if ($emergencyOtp = ($emergency_otp ?? null)): ?>
    <div role="alert" class="alert alert-warning mb-4 items-start shadow-sm">
        <i data-lucide="key-round" class="h-5 w-5 shrink-0"></i>
        <div class="min-w-0">
            <h2 class="font-bold">OTP darurat — <?= esc($emergencyOtp['member']) ?></h2>
            <div class="my-1 font-mono text-xl font-black tracking-widest"><?= esc($emergencyOtp['code']) ?></div>
            <p class="text-xs">Berlaku sampai <?= esc($emergencyOtp['expires_at']) ?> dan hanya ditampilkan sekali.</p>
        </div>
    </div>
<?php endif; ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="page-title">Anggota DPRD</h1>
    <a href="<?= base_url('admin/anggota/create') ?>" class="btn btn-primary btn-sm w-full gap-1 sm:w-auto">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah Anggota
    </a>
</div>

<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-5">
        <h2 class="card-title text-base">
            <i data-lucide="users" class="h-5 w-5 text-primary"></i>
            Daftar Anggota
        </h2>
        <span class="badge badge-ghost whitespace-nowrap"><?= count($members) ?> anggota</span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto max-sm:overflow-x-visible">
            <table class="table table-zebra table-md w-full admin-data-table responsive-card-table" id="table-anggota" data-admin-datatable data-dt-order='[[1,"asc"]]'>
                <thead>
                    <tr class="bg-base-200">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Nama Lengkap</th>
                        <th>Fraksi</th>
                        <th>Komisi</th>
                        <th>No WhatsApp</th>
                        <th>Status</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr class="transition-colors hover:bg-base-200/40">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Nama Lengkap">
                                <div class="flex items-center gap-3">
                                    <div class="avatar placeholder">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-content">
                                            <span><?= esc(strtoupper(substr($m['name'], 0, 1))) ?></span>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-base-content"><?= esc($m['name']) ?></div>
                                        <?php if (! empty($m['jabatan'])): ?>
                                            <div class="mt-0.5 text-xs text-base-content/60"><?= esc($m['jabatan']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Fraksi" class="text-base-content/85"><?= esc($m['fraksi']) ?></td>
                            <td data-label="Komisi">
                                <span class="badge badge-ghost h-auto px-2 py-1 text-xs">
                                    <?= esc($m['komisi']) ?>
                                </span>
                            </td>
                            <td data-label="No WhatsApp" class="font-mono text-sm text-base-content/85"><?= esc($m['no_wa']) ?></td>
                            <td data-label="Status">
                                <?php if ($m['aktif']): ?>
                                    <span class="badge badge-success h-auto gap-1.5 whitespace-nowrap px-2 py-0.5 text-xs font-semibold">
                                        <span class="status status-success"></span>
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-ghost h-auto gap-1.5 whitespace-nowrap px-2 py-0.5 text-xs font-semibold text-base-content/60">
                                        <span class="status"></span>
                                        Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <?php if (! empty($m['aktif'])): ?>
                                        <form method="post" action="<?= base_url("admin/anggota/{$m['id']}/otp-darurat") ?>"
                                            class="m-0 inline-flex"
                                            data-confirm-message="Buat OTP darurat untuk anggota ini? Pastikan identitas anggota sudah diverifikasi.">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline btn-warning btn-sm gap-1" title="Buat OTP darurat">
                                                <i data-lucide="key-round" class="h-4 w-4"></i>
                                                OTP
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= base_url("admin/anggota/{$m['id']}/edit") ?>" class="btn btn-outline btn-primary btn-sm gap-1">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Edit
                                    </a>
                                    <form method="post" action="<?= base_url("admin/anggota/{$m['id']}/delete") ?>"
                                        data-confirm-message="Hapus anggota ini?" class="m-0 inline-flex">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline btn-error btn-sm gap-1">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            Hapus
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
