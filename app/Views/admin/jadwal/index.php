<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-base-content/50">Pengelolaan Agenda</p>
        <h1 class="page-title">Agenda Insidental</h1>
        <p class="mt-1 text-sm text-base-content/60">Rapat atau kegiatan mendadak di luar agenda Banmus.</p>
    </div>
    <a href="<?= base_url('admin/jadwal/create') ?>" class="btn btn-primary btn-sm w-full gap-1 sm:w-auto">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah Agenda
    </a>
</div>

<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-5">
        <h2 class="card-title text-base">
            <i data-lucide="calendar-days" class="h-5 w-5 text-primary"></i>
            Daftar Agenda Insidental
        </h2>
        <span class="badge badge-ghost whitespace-nowrap"><?= count($meetings) ?> agenda</span>
    </div>

    <div class="min-w-0">
        <div class="w-full overflow-x-auto max-sm:overflow-x-visible">
            <table class="table table-zebra table-md w-full admin-data-table responsive-card-table" id="table-jadwal"
                data-admin-datatable data-dt-order='[[1,"desc"]]'
                data-dt-col-filters='[{"col":6,"label":"Status","all":"Semua Status"}]'>
                <thead>
                    <tr class="bg-base-200">
                        <th class="dt-row-number no-sort">No</th>
                        <th>Tanggal & Waktu</th>
                        <th>Judul Rapat</th>
                        <th>Ruangan</th>
                        <th class="mobile-hidden">Peserta</th>
                        <th class="mobile-hidden">Publikasi</th>
                        <th>Status</th>
                        <th class="text-right no-sort">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meetings as $m):
                        $badge = status_badge($m['status']);
                        $dateOrder = $m['tanggal'] . ' ' . $m['waktu_mulai'];
                    ?>
                        <tr class="transition-colors hover:bg-base-200/40">
                            <td class="dt-row-number" data-label="No"></td>
                            <td class="whitespace-nowrap text-right sm:text-left" data-order="<?= esc($dateOrder, 'attr') ?>" data-label="Tanggal & Waktu">
                                <div class="flex flex-col items-end gap-0.5 sm:items-start">
                                    <span class="text-sm font-bold text-base-content">
                                        <?= esc(date('d/m/Y', strtotime($m['tanggal']))) ?>
                                    </span>
                                    <span class="text-xs font-medium text-base-content/60">
                                        <?= esc($m['waktu_mulai']) ?>&ndash;<?= esc($m['waktu_selesai']) ?>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Judul Rapat">
                                <div class="text-sm font-bold text-base-content"><?= esc($m['judul']) ?></div>
                                <?php if (! empty($m['keterangan'])): ?>
                                    <div class="mt-1 hidden max-w-md truncate text-xs text-base-content/60 sm:block" title="<?= esc($m['keterangan']) ?>">
                                        <?= esc($m['keterangan']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap text-base-content/85" data-label="Ruangan"><?= esc($m['ruangan']) ?></td>
                            <td data-label="Peserta" class="mobile-hidden">
                                <span class="badge badge-ghost h-auto whitespace-nowrap px-2 py-1 text-xs">
                                    <?= esc($m['target_peserta']) ?>
                                </span>
                            </td>
                            <td data-label="Publikasi" class="mobile-hidden">
                                <?php if ($m['is_publik'] ?? 0): ?>
                                    <span class="badge badge-success h-auto whitespace-nowrap px-1.5 py-0.5 text-[10px] font-semibold" title="Ubah publikasi melalui halaman edit">Publik</span>
                                <?php else: ?>
                                    <span class="badge badge-ghost h-auto whitespace-nowrap px-1.5 py-0.5 text-[10px] font-semibold text-base-content/60" title="Ubah publikasi melalui halaman edit">Internal</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status">
                                <?php
                                $statusClass = match ($m['status']) {
                                    'berlangsung' => 'status-success animate-pulse',
                                    'persiapan'   => 'status-warning',
                                    'selesai'     => 'status-info',
                                    default       => 'status-neutral',
                                };
                                ?>
                                <span class="badge <?= $badge['class'] ?> h-auto whitespace-nowrap px-2.5 py-1 text-xs font-semibold">
                                    <span class="status <?= $statusClass ?>"></span>
                                    <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td data-label="Aksi">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="<?= base_url("admin/jadwal/{$m['id']}/edit") ?>" class="btn btn-outline btn-primary btn-sm gap-1">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Edit
                                    </a>
                                    <form method="post" action="<?= base_url("admin/jadwal/{$m['id']}/delete") ?>"
                                        data-confirm-message="Hapus jadwal ini?" class="m-0 inline-flex">
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
