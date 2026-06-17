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
            <?php $scope = $data_scope ?? ['label' => 'filter aktif']; ?>
            <p class="header-sub">
                <?= count($meetings) ?> jadwal ditemukan
                <span class="text-base-content/50">
                    &bull; <?= esc($scope['label']) ?>
                </span>
            </p>
        </div>
    </div>

    <form method="get" class="p-3 border-b border-base-200">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="label-text font-bold text-xs mb-1 block" for="filter-tahun">Tahun</label>
                <select class="select select-sm select-bordered" id="filter-tahun" name="tahun" onchange="this.form.submit()">
                    <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= (int) $filters['tahun'] === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="label-text font-bold text-xs mb-1 block" for="filter-semester">Semester</label>
                <select class="select select-sm select-bordered" id="filter-semester" name="semester" onchange="this.form.submit()">
                    <option value="all" <?= $filters['semester'] === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="1" <?= $filters['semester'] === '1' ? 'selected' : '' ?>>Semester I</option>
                    <option value="2" <?= $filters['semester'] === '2' ? 'selected' : '' ?>>Semester II</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <a href="<?= base_url('admin/jadwal') ?>" class="btn btn-sm btn-outline btn-ghost" title="Reset semua filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
            <p class="text-xs text-base-content/40 self-end pb-1 ml-auto hidden md:block">


                Filter Jenis &amp; Status tersedia langsung di tabel ↓
            </p>
        </div>
    </form>

    <div class="section-card-body p-0">
        <div class="overflow-x-auto w-full">
            <table class="table table-zebra table-md w-full admin-data-table responsive-card-table" id="table-jadwal" data-admin-datatable data-dt-order='[[1,"desc"]]'
                data-dt-col-filters='[{"col":5,"label":"Jenis","all":"Semua Jenis"},{"col":7,"label":"Status","all":"Semua Status"}]'>
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Tanggal & Waktu</th>
                        <th>Judul Rapat</th>
                        <th>Ruangan</th>
                        <th class="mobile-hidden">Peserta</th>
                        <th class="mobile-hidden">Jenis</th>
                        <th class="mobile-hidden">Publik</th>
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
                            <td class="dt-row-number" data-label="No"></td>
                            <td class="whitespace-nowrap text-right sm:text-left" data-order="<?= esc($dateOrder, 'attr') ?>" data-label="Tanggal & Waktu">
                                <div class="flex flex-col items-end sm:items-start gap-0.5">
                                    <span class="font-bold text-sm text-base-content">
                                        <?= esc(date('d/m/Y', strtotime($m['tanggal']))) ?>
                                    </span>
                                    <span class="text-xs text-base-content/60 font-medium">
                                        <?= esc($m['waktu_mulai']) ?> - <?= esc($m['waktu_selesai']) ?>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Judul Rapat">
                                <div class="font-bold text-base-content text-sm"><?= esc($m['judul']) ?></div>
                                <div class="text-xs text-base-content/60 mt-1 max-w-md truncate hidden sm:block" title="<?= esc($m['keterangan'] ?? '') ?>">
                                    <?= esc($m['keterangan'] ?? '') ?>
                                </div>
                            </td>
                            <td class="whitespace-nowrap text-base-content/85" data-label="Ruangan"><?= esc($m['ruangan']) ?></td>
                            <td data-label="Peserta" class="mobile-hidden">
                                <span class="badge badge-ghost h-auto py-1 px-2 text-xs whitespace-nowrap">
                                    <?= esc($m['target_peserta']) ?>
                                </span>
                            </td>
                            <td data-label="Jenis" class="mobile-hidden">
                                <?php if (($m['jenis'] ?? 'insidental') === 'reguler'): ?>
                                    <span class="badge badge-primary h-auto py-0.5 px-1.5 text-[10px] font-semibold whitespace-nowrap">Reguler</span>
                                <?php else: ?>
                                    <span class="badge badge-ghost h-auto py-0.5 px-1.5 text-[10px] font-semibold text-base-content/60 whitespace-nowrap">Insidental</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Publik" class="mobile-hidden">
                                <?php if ($m['is_publik'] ?? 0): ?>
                                    <span class="badge badge-success h-auto py-0.5 px-1.5 text-[10px] font-semibold whitespace-nowrap" title="Ubah publikasi melalui halaman edit">Publik</span>
                                <?php else: ?>
                                    <span class="badge badge-ghost h-auto py-0.5 px-1.5 text-[10px] font-semibold text-base-content/60 whitespace-nowrap" title="Ubah publikasi melalui halaman edit">Internal</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status">
                                <?php $dotClass = ($m['status'] === 'berlangsung') ? 'bg-current animate-pulse' : 'bg-current'; ?>
                                <span class="badge <?= $badge['class'] ?> h-auto py-1 px-2.5 text-xs font-semibold whitespace-nowrap">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $dotClass ?>"></span>
                                    <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td data-label="Aksi">
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
