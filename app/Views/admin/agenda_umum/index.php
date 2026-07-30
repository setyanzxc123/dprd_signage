<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-base-content/50">Layanan Publik</p>
        <h1 class="page-title">Agenda Eksternal & Layanan Publik</h1>
        <p class="mt-1 text-sm text-base-content/60">Kegiatan yang melibatkan masyarakat, tamu, instansi, atau pihak luar.</p>
    </div>
    <a href="<?= base_url('admin/agenda-umum/create') ?>" class="btn btn-primary btn-sm w-full gap-1 sm:w-auto">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah Kegiatan
    </a>
</div>

<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-5">
        <h2 class="card-title text-base">
            <i data-lucide="calendar-range" class="h-5 w-5 text-primary"></i>
            Daftar Agenda Eksternal
        </h2>
        <span class="badge badge-ghost whitespace-nowrap"><?= count($agendas) ?> kegiatan</span>
    </div>

    <div role="alert" class="alert alert-info rounded-none border-x-0 border-t-0 py-2 text-sm">
        <i data-lucide="info" class="h-4 w-4"></i>
        <span>Khusus kegiatan nonrapat. Rapat mendadak dicatat sebagai <strong>Agenda Insidental</strong>.</span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto max-sm:overflow-x-visible">
            <table class="table table-zebra table-md w-full admin-data-table responsive-card-table"
                data-admin-datatable
                data-dt-order='[[1,"desc"]]'
                data-dt-col-filters='[{"column":2,"label":"Jenis"},{"column":5,"label":"Publikasi"}]'>
                <thead>
                    <tr class="bg-base-200">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Kegiatan</th>
                        <th>Lokasi</th>
                        <th>Publikasi</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendas as $agenda): ?>
                        <tr class="transition-colors hover:bg-base-200/40">
                            <td class="dt-row-number" data-label="No"></td>
                            <td data-label="Tanggal" data-order="<?= esc($agenda['tanggal'] . ' ' . $agenda['waktu_mulai']) ?>">
                                <div class="whitespace-nowrap text-sm font-bold">
                                    <?= esc(date('d/m/Y', strtotime($agenda['tanggal']))) ?>
                                </div>
                                <div class="mt-0.5 whitespace-nowrap text-xs text-base-content/55">
                                    <?= esc(substr($agenda['waktu_mulai'], 0, 5)) ?>
                                    <?php if (! empty($agenda['waktu_selesai'])): ?>
                                        –<?= esc(substr($agenda['waktu_selesai'], 0, 5)) ?> WITA
                                    <?php else: ?>
                                        WITA
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Jenis">
                                <span class="badge badge-ghost badge-sm whitespace-nowrap">
                                    <?= esc($category_labels[$agenda['kategori']] ?? $agenda['kategori']) ?>
                                </span>
                            </td>
                            <td data-label="Kegiatan">
                                <div class="max-w-md text-sm font-bold"><?= esc($agenda['judul']) ?></div>
                                <div class="mt-1 max-w-md text-xs text-base-content/55">
                                    <?= esc($agenda['pihak_eksternal'] ?: 'Pihak eksternal belum dicatat') ?>
                                </div>
                            </td>
                            <td data-label="Lokasi">
                                <div class="max-w-xs text-sm font-semibold"><?= esc($agenda['lokasi']) ?></div>
                            </td>
                            <td data-label="Publikasi">
                                <?php if ((int) $agenda['is_publik'] === 1): ?>
                                    <span class="badge badge-success badge-sm">Publik</span>
                                <?php else: ?>
                                    <span class="badge badge-ghost badge-sm">Internal</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="<?= base_url("admin/agenda-umum/{$agenda['id']}/edit") ?>" class="btn btn-sm btn-outline btn-primary gap-1">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Edit
                                    </a>
                                    <form method="post" action="<?= base_url("admin/agenda-umum/{$agenda['id']}/delete") ?>" class="m-0 inline-flex"
                                                data-confirm-message="Hapus agenda eksternal ini?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline btn-error gap-1">
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
