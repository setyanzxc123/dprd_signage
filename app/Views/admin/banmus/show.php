<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$resourceAccessLabels = [
    'publik'  => 'Publik',
    'anggota' => 'Anggota DPRD',
    'peserta' => 'Peserta',
];

$projectionCount = count(array_filter(
    $items,
    static fn (array $item): bool => ($item['status'] ?? 'proyeksi') === 'proyeksi',
));
$scheduledCount = count($items) - $projectionCount;
?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div class="flex min-w-0 items-start gap-2">
        <a href="<?= base_url('admin/jadwal-banmus') ?>" class="btn btn-ghost btn-sm btn-square shrink-0" title="Kembali ke daftar SK">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
        </a>
        <div class="min-w-0">
            <h1 class="page-title">SK No. <?= esc($document['nomor_sk']) ?></h1>
            <p class="mt-1 max-w-3xl text-sm leading-relaxed text-base-content/60"><?= esc($document['judul']) ?></p>
        </div>
    </div>
    <button type="button" data-banmus-item-open class="btn btn-primary btn-sm w-full gap-1.5 sm:w-auto">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Tambah Item Agenda
    </button>
</div>

<div class="card card-sm card-border mb-4 bg-base-100 shadow-sm">
    <div class="card-body flex-row flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-5">
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge badge-outline badge-sm">Semester <?= (int) $document['semester'] ?> · <?= (int) $document['tahun'] ?></span>
            <span class="badge badge-ghost badge-sm"><?= ! empty($document['is_publik']) ? 'Dokumen publik' : 'Dokumen internal' ?></span>
            <span class="badge badge-ghost badge-sm"><?= $projectionCount ?> proyeksi</span>
            <span class="badge badge-ghost badge-sm"><?= $scheduledCount ?> terjadwal</span>
        </div>
        <div class="flex items-center gap-1">
            <?php if (! empty($document['dokumen_file'])): ?>
                <a
                    href="<?= base_url('uploads/sk-banmus/' . $document['dokumen_file']) ?>"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-ghost btn-xs gap-1"
                    title="Buka PDF SK">
                    <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                    PDF SK
                </a>
            <?php endif; ?>
            <a href="<?= base_url('admin/jadwal-banmus/' . $document['id'] . '/edit') ?>" class="btn btn-ghost btn-xs gap-1" title="Edit Metadata SK">
                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                Edit SK
            </a>
        </div>
    </div>
</div>

<!-- DataTable Item Agenda Banmus -->
<section class="card card-sm card-border banmus-item-card min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-3 sm:px-5">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-base-200 text-base-content/70">
                <i data-lucide="list-todo" class="h-4.5 w-4.5"></i>
            </span>
            <h2 class="card-title text-sm sm:text-base">Item Agenda</h2>
        </div>
        <span class="badge badge-ghost badge-sm whitespace-nowrap"><?= count($items) ?> item</span>
    </div>

    <?php if ($items === []): ?>
        <div class="p-8 text-center text-base-content/60">
            <i data-lucide="calendar-plus" class="mx-auto h-12 w-12 text-base-content/30"></i>
            <p class="mt-3 font-semibold">Belum ada item agenda dalam SK ini.</p>
            <button type="button" data-banmus-item-open class="btn btn-primary btn-sm mt-4 gap-1">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Tambah Item Agenda
            </button>
        </div>
    <?php else: ?>
        <?php
        $roomNames = [];
        foreach ($rooms as $room) {
            $roomNames[(int) $room['id']] = $room['name'];
        }

        $unitNamesById = [];
        foreach ($units as $unit) {
            $unitNamesById[(int) $unit['id']] = $unit['nama'];
        }
        ?>
        <div class="min-w-0">
            <div class="w-full overflow-x-auto">
                <table
                    id="table-jadwal-banmus"
                    class="banmus-item-table table table-zebra table-sm w-full admin-data-table"
                    data-admin-datatable
                    data-dt-page-length="10"
                    data-dt-col-filters='[{"col":2,"label":"Jenis","all":"Semua Jenis"},{"col":5,"label":"Publikasi","all":"Semua Publikasi"},{"col":6,"label":"Status","all":"Semua Status"}]'>
                    <thead>
                        <tr class="bg-base-200">
                            <th class="dt-row-number no-sort">No</th>
                            <th>Agenda</th>
                            <th>Jenis</th>
                            <th>Jadwal</th>
                            <th>Lokasi & Peserta</th>
                            <th class="mobile-hidden">Publikasi</th>
                            <th>Status</th>
                            <th class="text-right no-sort">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php
                            $hasDate = ! empty($item['tanggal']);
                            $hasFixedSchedule = $item['status'] !== 'proyeksi' && $hasDate;
                            $statusClass = match ($item['status']) {
                                'proyeksi'   => 'badge-warning badge-soft',
                                'menunggu'   => 'badge-ghost',
                                'persiapan'  => 'badge-warning badge-soft',
                                'berlangsung' => 'badge-success badge-soft',
                                'selesai'    => 'badge-info badge-soft',
                                'ditunda'    => 'badge-warning badge-outline',
                                'dibatalkan' => 'badge-error badge-soft line-through',
                                default      => 'badge-ghost',
                            };
                            $statusLabel = match ($item['status']) {
                                'proyeksi'   => 'Proyeksi',
                                'menunggu'   => 'Menunggu',
                                'persiapan'  => 'Persiapan',
                                'berlangsung' => 'Berlangsung',
                                'selesai'    => 'Selesai',
                                'ditunda'    => 'Ditunda',
                                'dibatalkan' => 'Dibatalkan',
                                default      => ucfirst($item['status']),
                            };

                            $roomName = '';
                            if (! empty($item['ruangan_id'])) {
                                $roomName = $roomNames[(int) $item['ruangan_id']] ?? '';
                            }
                            if ($roomName === '' && ! empty($item['lokasi_lainnya'])) {
                                $roomName = $item['lokasi_lainnya'];
                            }

                            $unitNames = [];
                            foreach (array_map('intval', $item['unit_ids'] ?? []) as $unitId) {
                                if (isset($unitNamesById[$unitId])) {
                                    $unitNames[] = $unitNamesById[$unitId];
                                }
                            }

                            $projectionLabel = trim((string) ($item['periode_label'] ?? ''));
                            if ($projectionLabel === '') {
                                $projectionLabel = trim((string) ($item['teks_tanggal_asli'] ?? ''));
                            }
                            if ($projectionLabel === '' && ! empty($item['tanggal_mulai'])) {
                                $projectionLabel = date('d/m/Y', strtotime($item['tanggal_mulai']));
                                if (! empty($item['tanggal_selesai']) && $item['tanggal_selesai'] !== $item['tanggal_mulai']) {
                                    $projectionLabel .= '–' . date('d/m/Y', strtotime($item['tanggal_selesai']));
                                }
                            }

                            $missingFields = [];
                            if (! $hasDate) {
                                $missingFields[] = 'tanggal';
                            }
                            if (empty($item['jam_mulai']) || empty($item['jam_selesai'])) {
                                $missingFields[] = 'waktu';
                            }
                            if ($roomName === '') {
                                $missingFields[] = 'lokasi';
                            }
                            if ($unitNames === []) {
                                $missingFields[] = 'peserta';
                            }
                            $projectionWarning = 'Belum siap ditetapkan sebagai jadwal: '
                                . implode(', ', $missingFields)
                                . ' belum diisi.';
                            $scheduleOrder = $hasFixedSchedule
                                ? $item['tanggal'] . ' ' . ($item['jam_mulai'] ?? '00:00:00')
                                : '9999-12-31 ' . str_pad((string) ($item['urutan'] ?? 0), 6, '0', STR_PAD_LEFT);
                            ?>
                            <tr class="align-top transition-colors hover:bg-base-200/40">
                                <td class="dt-row-number" data-label="No"></td>
                                <td data-label="Agenda">
                                    <div class="max-w-xl text-sm font-bold leading-snug text-base-content">
                                        <?= nl2br(esc($item['agenda'])) ?>
                                    </div>

                                    <?php if (! empty($item['catatan'])): ?>
                                        <div class="mt-1 max-w-xl truncate text-xs text-base-content/60" title="<?= esc($item['catatan']) ?>">
                                            <?= esc($item['catatan']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (! empty($item['materi_url']) || ! empty($item['stream_url'])): ?>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            <?php if (! empty($item['materi_url'])): ?>
                                                <a
                                                    href="<?= esc($item['materi_url'], 'attr') ?>"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="btn btn-ghost btn-xs gap-1"
                                                    title="Buka materi atau dokumen">
                                                    <i data-lucide="paperclip" class="h-3.5 w-3.5"></i>
                                                    Materi
                                                    <span class="badge badge-ghost badge-xs">
                                                        <?= esc($resourceAccessLabels[$item['materi_akses'] ?? 'peserta'] ?? 'Terbatas') ?>
                                                    </span>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (! empty($item['stream_url'])): ?>
                                                <a
                                                    href="<?= esc($item['stream_url'], 'attr') ?>"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="btn btn-ghost btn-xs gap-1"
                                                    title="Buka tautan live streaming">
                                                    <i data-lucide="radio" class="h-3.5 w-3.5"></i>
                                                    Live
                                                    <span class="badge badge-ghost badge-xs">
                                                        <?= esc($resourceAccessLabels[$item['stream_akses'] ?? 'anggota'] ?? 'Terbatas') ?>
                                                    </span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Jenis">
                                    <?php if (($item['jenis_agenda'] ?? 'rapat') === 'non_rapat'): ?>
                                        <span class="badge badge-outline badge-sm whitespace-nowrap font-semibold">Non-rapat</span>
                                    <?php else: ?>
                                        <span class="badge badge-outline badge-sm whitespace-nowrap font-semibold">Rapat</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Jadwal" data-order="<?= esc($scheduleOrder, 'attr') ?>">
                                    <?php if ($hasFixedSchedule): ?>
                                        <div class="whitespace-nowrap text-sm font-bold text-base-content">
                                            <?= esc(date('d/m/Y', strtotime($item['tanggal']))) ?>
                                        </div>
                                        <div class="mt-0.5 whitespace-nowrap font-mono text-xs text-base-content/60">
                                            <?php if (! empty($item['jam_mulai'])): ?>
                                                <?= esc(substr($item['jam_mulai'], 0, 5)) ?>
                                                <?php if (! empty($item['jam_selesai'])): ?>
                                                    –<?= esc(substr($item['jam_selesai'], 0, 5)) ?>
                                                <?php endif; ?>
                                                WITA
                                            <?php else: ?>
                                                Waktu belum diisi
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="max-w-48 text-xs font-semibold text-base-content/70">
                                            <?= esc($projectionLabel !== '' ? $projectionLabel : 'Periode belum diisi') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Lokasi & Peserta">
                                    <?php if ($roomName !== ''): ?>
                                        <div class="flex items-start gap-1.5 text-sm font-semibold text-base-content">
                                            <i data-lucide="map-pin" class="mt-0.5 h-3.5 w-3.5 text-primary"></i>
                                            <span><?= esc($roomName) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs italic text-base-content/45">Lokasi belum diisi</span>
                                    <?php endif; ?>

                                    <?php if ($unitNames !== []): ?>
                                        <?php
                                        $visibleUnitNames = array_slice($unitNames, 0, 2);
                                        $remainingUnitCount = count($unitNames) - count($visibleUnitNames);
                                        ?>
                                        <div
                                            class="mt-1.5 flex max-w-xs items-start gap-1.5 text-xs leading-relaxed text-base-content/60"
                                            title="<?= esc(implode(', ', $unitNames), 'attr') ?>">
                                            <i data-lucide="users" class="mt-0.5 h-3.5 w-3.5 shrink-0"></i>
                                            <span>
                                                <?= esc(implode(', ', $visibleUnitNames)) ?>
                                                <?php if ($remainingUnitCount > 0): ?>
                                                    <span class="font-semibold">+<?= $remainingUnitCount ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-1 text-xs italic text-base-content/45">Peserta belum dipilih</div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Publikasi" class="mobile-hidden">
                                    <?php if (($item['publikasi'] ?? 'internal') === 'publik'): ?>
                                        <span class="badge badge-success badge-soft badge-sm whitespace-nowrap">Publik</span>
                                    <?php else: ?>
                                        <span class="badge badge-ghost badge-sm whitespace-nowrap">Internal</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="badge <?= $statusClass ?> badge-sm whitespace-nowrap font-semibold">
                                            <?= $statusLabel ?>
                                        </span>
                                        <?php if ($item['status'] === 'proyeksi' && $missingFields !== []): ?>
                                            <span
                                                class="tooltip tooltip-left inline-flex"
                                                data-tip="<?= esc($projectionWarning, 'attr') ?>"
                                                aria-label="<?= esc($projectionWarning, 'attr') ?>">
                                                <span class="badge badge-warning badge-soft badge-xs gap-1 whitespace-nowrap">
                                                    <i data-lucide="triangle-alert" class="h-3 w-3"></i>
                                                    Belum lengkap
                                                </span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Aksi">
                                    <div class="banmus-item-actions flex flex-wrap items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            data-banmus-item-edit
                                            data-item="<?= esc(json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>"
                                            class="btn btn-xs w-20 gap-1"
                                            title="Edit item agenda">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                            Edit
                                        </button>

                                        <form
                                            action="<?= base_url("admin/jadwal-banmus/{$document['id']}/item/{$item['id']}/delete") ?>"
                                            method="post"
                                            class="m-0 inline-flex"
                                            data-confirm-message="Hapus item agenda ini?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-ghost btn-error btn-xs w-20 gap-1" title="Hapus item agenda">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
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
    <?php endif; ?>
</section>

<!-- Modal editor satu-record: proyeksi dan jadwal terkonfirmasi memakai record yang sama. -->
<dialog
    id="item_modal"
    class="modal"
    tabindex="0"
    data-banmus-item-dialog
    data-store-url="<?= base_url("admin/jadwal-banmus/{$document['id']}/item/store") ?>"
    data-update-url-template="<?= base_url("admin/jadwal-banmus/{$document['id']}/item/__ITEM_ID__/update") ?>">
    <div class="modal-box flex max-h-[calc(100dvh-2rem)] max-w-5xl flex-col overflow-hidden p-0">
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-base-300 px-4 py-3 sm:px-6">
            <h3 class="flex items-center gap-2 text-base font-bold sm:text-lg" id="modal_title">
                <i data-lucide="calendar-plus" class="h-5 w-5 text-primary"></i>
                <span>Tambah Item Agenda Banmus</span>
            </h3>
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost" aria-label="Tutup dialog">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </form>
        </div>

        <form id="item_form" action="" method="post" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col" data-turbo="false">
            <?= csrf_field() ?>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-6">
                <div class="grid gap-4 lg:grid-cols-2">
                    <section class="card card-sm card-border border-base-300 bg-base-200 shadow-sm">
                        <div class="card-body gap-0 p-3 sm:p-4">
                            <div class="mb-3 flex items-center gap-2 text-sm font-bold">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-base-300">
                                    <i data-lucide="file-text" class="h-4 w-4 text-base-content/70"></i>
                                </span>
                                Informasi Agenda
                            </div>

                            <div class="space-y-3">
                                <fieldset class="fieldset">
                                    <legend class="fieldset-legend">Uraian Agenda SK <span class="text-error">*</span></legend>
                                    <textarea class="textarea textarea-sm min-h-24 w-full resize-none" id="field_agenda" name="agenda" rows="4" required
                                        placeholder="Tuliskan uraian agenda sesuai SK Banmus"></textarea>
                                </fieldset>

                                <fieldset class="fieldset">
                                    <legend class="fieldset-legend">Jenis Item <span class="text-error">*</span></legend>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="label cursor-pointer items-start justify-start gap-2 rounded-box border border-base-300 bg-base-100 p-3">
                                            <input
                                                class="radio radio-sm mt-0.5"
                                                id="field_jenis_agenda_rapat"
                                                name="jenis_agenda"
                                                type="radio"
                                                value="rapat"
                                                checked
                                                required />
                                            <span>
                                                <span class="block text-sm font-bold text-base-content">Rapat</span>
                                                <span class="mt-0.5 hidden text-xs leading-relaxed text-base-content/55 sm:block">Masuk Agenda Rapat.</span>
                                            </span>
                                        </label>
                                        <label class="label cursor-pointer items-start justify-start gap-2 rounded-box border border-base-300 bg-base-100 p-3">
                                            <input
                                                class="radio radio-sm mt-0.5"
                                                id="field_jenis_agenda_non_rapat"
                                                name="jenis_agenda"
                                                type="radio"
                                                value="non_rapat"
                                                required />
                                            <span>
                                                <span class="block text-sm font-bold text-base-content">Non-rapat</span>
                                                <span class="mt-0.5 hidden text-xs leading-relaxed text-base-content/55 sm:block">Tidak masuk Agenda Rapat.</span>
                                            </span>
                                        </label>
                                    </div>
                                </fieldset>

                                <fieldset class="fieldset">
                                    <legend class="fieldset-legend">Periode SK</legend>
                                    <input class="input input-sm w-full" id="field_periode_label" name="periode_label" type="text"
                                        placeholder="Contoh: Juni–Juli 2026 atau Minggu ke-2 Juli" />
                                </fieldset>

                                <fieldset class="fieldset">
                                    <legend class="fieldset-legend">Catatan</legend>
                                    <input class="input input-sm w-full" id="field_catatan" name="catatan" type="text" placeholder="Catatan tambahan untuk item ini" />
                                </fieldset>
                            </div>
                        </div>
                    </section>

                    <section class="card card-sm card-border border-base-300 bg-base-200 shadow-sm">
                        <div class="card-body gap-0 p-3 sm:p-4">
                            <div class="mb-3 flex items-center gap-2 text-sm font-bold">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-base-300">
                                    <i data-lucide="calendar-cog" class="h-4 w-4 text-base-content/70"></i>
                                </span>
                                Pelaksanaan &amp; Peserta
                            </div>

                            <div class="space-y-3">
                                <div role="alert" class="alert alert-info alert-soft px-3 py-2 text-xs">
                                    <i data-lucide="info" class="h-4 w-4 shrink-0"></i>
                                    <span>Status dihitung otomatis. Data yang belum lengkap disimpan sebagai <strong>Proyeksi</strong>.</span>
                                </div>

                            <div class="grid grid-cols-2 gap-3">
                                <fieldset class="fieldset col-span-2">
                                    <legend class="fieldset-legend">Tanggal</legend>
                                    <input class="input input-sm w-full font-semibold" id="field_tanggal" name="tanggal" type="date" />
                                </fieldset>
                                <fieldset class="fieldset">
                                    <legend class="fieldset-legend">Jam Mulai</legend>
                                    <input class="input input-sm w-full" id="field_jam_mulai" name="jam_mulai" type="time" />
                                </fieldset>
                                <fieldset class="fieldset">
                                    <legend class="fieldset-legend">Jam Selesai</legend>
                                    <input class="input input-sm w-full" id="field_jam_selesai" name="jam_selesai" type="time" />
                                </fieldset>
                            </div>

                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">Ruangan atau Lokasi</legend>
                                <select class="select select-sm w-full" id="field_ruangan_id" name="ruangan_id">
                                    <option value="">Pilih ruangan</option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?= $room['id'] ?>"><?= esc($room['name']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">Lokasi lainnya</option>
                                </select>
                            </fieldset>

                            <fieldset class="fieldset hidden" id="field_lokasi_lainnya_wrapper">
                                <legend class="fieldset-legend">Nama Lokasi Lainnya</legend>
                                <input class="input input-sm w-full" id="field_lokasi_lainnya" name="lokasi_lainnya" type="text" placeholder="Contoh: Hotel Santika Palu" />
                            </fieldset>

                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">Kelompok Peserta</legend>
                                <div class="grid max-h-40 grid-cols-2 gap-x-3 gap-y-1 overflow-y-auto rounded-box border border-base-300 p-3">
                                    <?php foreach ($units as $unit): ?>
                                        <label class="label cursor-pointer justify-start gap-2 py-1">
                                            <input type="checkbox" name="unit_ids[]" value="<?= $unit['id'] ?>" class="checkbox checkbox-xs unit-checkbox" />
                                            <span class="label-text text-xs"><?= esc($unit['nama']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>

                            </div>
                        </div>
                    </section>
                </div>

                <section class="card card-sm card-border mt-4 border-base-300 bg-base-200 shadow-sm">
                    <div class="card-body gap-0 p-3 sm:p-4">
                        <div class="mb-3 flex items-center gap-2 text-sm font-bold">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-base-300">
                                <i data-lucide="eye" class="h-4 w-4 text-base-content/70"></i>
                            </span>
                            Publikasi &amp; Tautan
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <fieldset class="fieldset sm:col-span-2">
                                <legend class="fieldset-legend">Publikasi Agenda</legend>
                                <select class="select select-sm w-full sm:max-w-xs" id="field_publikasi" name="publikasi">
                                    <option value="internal">Internal DPRD</option>
                                    <option value="publik" selected>Publik</option>
                                </select>
                            </fieldset>
                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">Materi / Dokumen</legend>
                                <input class="input input-sm w-full" id="field_materi_url" name="materi_url" type="url" placeholder="https://..." />
                                <label class="label text-xs" for="field_materi_akses">Akses bahan</label>
                                <select class="select select-sm w-full" id="field_materi_akses" name="materi_akses">
                                    <option value="peserta">Peserta rapat</option>
                                    <option value="anggota">Seluruh anggota DPRD</option>
                                    <option value="publik" selected>Publik</option>
                                </select>
                            </fieldset>
                            <fieldset class="fieldset">
                                <legend class="fieldset-legend">Live Streaming</legend>
                                <input class="input input-sm w-full" id="field_stream_url" name="stream_url" type="url" placeholder="https://..." />
                                <label class="label text-xs" for="field_stream_akses">Akses live/video</label>
                                <select class="select select-sm w-full" id="field_stream_akses" name="stream_akses">
                                    <option value="anggota">Seluruh anggota DPRD</option>
                                    <option value="peserta">Peserta rapat</option>
                                    <option value="publik" selected>Publik</option>
                                </select>
                            </fieldset>
                            <fieldset class="fieldset sm:col-span-2">
                                <legend class="fieldset-legend">Undangan rapat</legend>
                                <input class="file-input file-input-sm w-full" id="field_undangan_file" name="undangan_file" type="file"
                                    accept="application/pdf,.pdf" />
                                <p class="label text-xs">PDF maksimal 10 MB. Hanya dapat dibuka oleh anggota yang sudah login.</p>
                                <div class="alert alert-soft hidden" id="field_undangan_existing">
                                    <i data-lucide="file-check-2" class="h-4 w-4"></i>
                                    <span class="min-w-0 flex-1 truncate" id="field_undangan_name"></span>
                                    <label class="label cursor-pointer gap-2" for="field_hapus_undangan">
                                        <input class="checkbox checkbox-sm" id="field_hapus_undangan" name="hapus_undangan" type="checkbox" value="1" />
                                        Hapus
                                    </label>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </section>
            </div>

            <div class="modal-action m-0 shrink-0 flex-wrap border-t border-base-300 bg-base-100 px-4 py-3 sm:px-6">
                <button type="button" data-banmus-item-close class="btn btn-ghost btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm gap-1">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    <span>Simpan Item Agenda</span>
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>Tutup dialog</button>
    </form>
</dialog>

<?= $this->endSection() ?>
