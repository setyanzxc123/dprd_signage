<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex items-center justify-between">
    <div>
        <h1 class="page-title">Jadwal Rapat</h1>
        <p class="page-subtitle">Kelola jadwal rapat dan notifikasi WhatsApp</p>
    </div>
    <a href="<?= base_url('admin/jadwal/create') ?>" class="btn btn-sm btn-primary gap-1">
        <i data-lucide="plus" class="w-4 h-4"></i>Tambah Jadwal
    </a>
</div>

<?php
    $filters = $filters ?? [
        'tahun'    => date('Y'),
        'semester' => 'all',
        'jenis'    => 'all',
        'status'   => 'all',
    ];
?>

<div class="section-card">
    <div class="section-card-header">
        <div class="header-icon"><i data-lucide="calendar-days"></i></div>
        <div>
            <h6>Daftar Jadwal</h6>
            <p class="header-sub"><?= count($meetings) ?> jadwal ditemukan</p>
        </div>
    </div>

    <form method="get" class="p-3 border-b border-base-200">
        <div class="grid grid-cols-12 gap-3 items-end">
            <div class="md:col-span-2 col-span-6">
                <label class="label-text font-bold text-xs mb-1 block" for="filter-tahun">Tahun</label>
                <select class="select select-sm select-bordered w-full" id="filter-tahun" name="tahun" onchange="this.form.submit()">
                    <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= (int) $filters['tahun'] === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="md:col-span-2 col-span-6">
                <label class="label-text font-bold text-xs mb-1 block" for="filter-semester">Semester</label>
                <select class="select select-sm select-bordered w-full" id="filter-semester" name="semester" onchange="this.form.submit()">
                    <option value="all" <?= $filters['semester'] === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="1" <?= $filters['semester'] === '1' ? 'selected' : '' ?>>Semester I</option>
                    <option value="2" <?= $filters['semester'] === '2' ? 'selected' : '' ?>>Semester II</option>
                </select>
            </div>
            <div class="md:col-span-2 col-span-6">
                <label class="label-text font-bold text-xs mb-1 block" for="filter-jenis">Jenis</label>
                <select class="select select-sm select-bordered w-full" id="filter-jenis" name="jenis" onchange="this.form.submit()">
                    <option value="all" <?= $filters['jenis'] === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="reguler" <?= $filters['jenis'] === 'reguler' ? 'selected' : '' ?>>Reguler</option>
                    <option value="insidental" <?= $filters['jenis'] === 'insidental' ? 'selected' : '' ?>>Insidental</option>
                </select>
            </div>
            <div class="md:col-span-2 col-span-6">
                <label class="label-text font-bold text-xs mb-1 block" for="filter-status">Status</label>
                <select class="select select-sm select-bordered w-full" id="filter-status" name="status" onchange="this.form.submit()">
                    <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="menunggu" <?= $filters['status'] === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                    <option value="persiapan" <?= $filters['status'] === 'persiapan' ? 'selected' : '' ?>>Persiapan</option>
                    <option value="berlangsung" <?= $filters['status'] === 'berlangsung' ? 'selected' : '' ?>>Berlangsung</option>
                    <option value="selesai" <?= $filters['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
            <div class="md:col-span-4 col-span-12 flex gap-2">
                <a href="<?= base_url('admin/jadwal') ?>" class="btn btn-sm btn-outline btn-ghost" title="Reset filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </form>

    <div class="section-card-body p-0">
        <div class="overflow-x-auto w-full">
            <table class="table table-zebra table-md w-full admin-data-table" data-admin-datatable data-dt-order='[[0,"desc"]]'>
                <thead>
                    <tr class="bg-base-200/50">
                        <th>Tanggal & Waktu</th>
                        <th>Judul Rapat</th>
                        <th>Ruangan</th>
                        <th>Peserta</th>
                        <th>Jenis</th>
                        <th>Publik</th>
                        <th>Status</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meetings as $m):
                        $badge = status_badge($m['status']);
                        $dateOrder = $m['tanggal'] . ' ' . $m['waktu_mulai'];
                    ?>
                        <tr class="hover:bg-base-200/30 transition-colors">
                            <td class="whitespace-nowrap" data-order="<?= esc($dateOrder, 'attr') ?>">
                                <span class="badge badge-neutral h-auto py-1 px-2 text-xs font-mono font-bold whitespace-nowrap">
                                    <?= esc(date('d/m/Y', strtotime($m['tanggal']))) ?>
                                </span>
                                <div class="text-xs text-base-content/60 mt-1">
                                    <?= esc($m['waktu_mulai']) ?> - <?= esc($m['waktu_selesai']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="font-bold text-base-content text-sm"><?= esc($m['judul']) ?></div>
                                <div class="text-xs text-base-content/65 mt-0.5 max-w-md truncate" title="<?= esc($m['keterangan'] ?? '') ?>">
                                    <?= esc($m['keterangan'] ?? '') ?>
                                </div>
                            </td>
                            <td class="whitespace-nowrap text-base-content/85"><?= esc($m['ruangan']) ?></td>
                            <td>
                                <span class="badge badge-ghost h-auto py-1 px-2 text-xs whitespace-nowrap">
                                    <?= esc($m['target_peserta']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (($m['jenis'] ?? 'insidental') === 'reguler'): ?>
                                    <span class="badge badge-primary h-auto py-0.5 px-1.5 text-[10px] font-semibold whitespace-nowrap">Reguler</span>
                                <?php else: ?>
                                    <span class="badge badge-ghost h-auto py-0.5 px-1.5 text-[10px] font-semibold text-base-content/60 whitespace-nowrap">Insidental</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($m['is_publik'] ?? 0): ?>
                                    <span class="badge badge-success h-auto py-0.5 px-1.5 text-[10px] font-semibold whitespace-nowrap" title="Ubah publikasi melalui halaman edit">Publik</span>
                                <?php else: ?>
                                    <span class="badge badge-ghost h-auto py-0.5 px-1.5 text-[10px] font-semibold text-base-content/60 whitespace-nowrap" title="Ubah publikasi melalui halaman edit">Internal</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $dotClass = ($m['status'] === 'berlangsung') ? 'bg-current animate-pulse' : 'bg-current'; ?>
                                <span class="badge <?= $badge['class'] ?> h-auto py-1 px-2.5 text-xs font-semibold whitespace-nowrap">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $dotClass ?>"></span>
                                    <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= base_url("admin/jadwal/{$m['id']}/edit") ?>" class="btn btn-sm btn-outline btn-primary gap-1" title="Edit">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>Edit
                                    </a>
                                    <form method="get" action="<?= base_url("admin/jadwal/{$m['id']}/delete") ?>"
                                        onsubmit="return confirm('Hapus jadwal ini?')" class="inline-flex m-0">
                                        <button type="submit" class="btn btn-sm btn-outline btn-error gap-1" title="Hapus">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>Hapus
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
</div>

<?= $this->endSection() ?>
