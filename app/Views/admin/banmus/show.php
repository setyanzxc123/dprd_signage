<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$resourceAccessLabels = [
    'publik'  => 'Publik',
    'anggota' => 'Anggota DPRD',
    'peserta' => 'Peserta',
];
?>

<div class="page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-bold uppercase tracking-wider text-base-content/50">Agenda Internal DPRD</p>
        <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= base_url('admin/jadwal-banmus') ?>" class="btn btn-ghost btn-sm gap-1">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Daftar SK
        </a>
        <?php if (! empty($document['dokumen_file'])): ?>
            <a href="<?= base_url('uploads/sk-banmus/' . $document['dokumen_file']) ?>" target="_blank" class="btn btn-outline btn-sm gap-1">
                <i data-lucide="file-pdf" class="h-4 w-4 text-error"></i>
                Buka PDF SK
            </a>
        <?php endif; ?>
        <a href="<?= base_url('admin/jadwal-banmus/' . $document['id'] . '/edit') ?>" class="btn btn-ghost btn-sm btn-square" title="Edit Metadata SK">
            <i data-lucide="pencil" class="h-4 w-4"></i>
        </a>
    </div>
</div>

<!-- Header Info SK -->
<div class="card card-border bg-base-100 shadow-sm mb-6">
    <div class="card-body p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="badge badge-primary font-bold">SK No. <?= esc($document['nomor_sk']) ?></span>
                <span class="badge badge-outline">Semester <?= (int) $document['semester'] ?> / <?= (int) $document['tahun'] ?></span>
            </div>
            <h2 class="mt-2 text-base font-semibold text-base-content"><?= esc($document['judul']) ?></h2>
            <?php if (! empty($document['catatan'])): ?>
                <p class="mt-1 text-xs text-base-content/60"><?= esc($document['catatan']) ?></p>
            <?php endif; ?>
        </div>
        <button type="button" data-banmus-item-open class="btn btn-primary btn-sm gap-1 shrink-0">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Tambah Item Agenda
        </button>
    </div>
</div>

<!-- DataTable Item Agenda Banmus -->
<section class="card card-border min-w-0 overflow-hidden bg-base-100 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-4 sm:px-5">
        <h2 class="card-title text-base">
            <i data-lucide="list-todo" class="h-5 w-5 text-primary"></i>
            Daftar Item Agenda Banmus
        </h2>
        <span class="badge badge-ghost font-semibold"><?= count($items) ?> Item Total</span>
    </div>

    <?php if ($items === []): ?>
        <div class="p-8 text-center text-base-content/60">
            <i data-lucide="calendar-plus" class="mx-auto h-12 w-12 text-base-content/30"></i>
            <p class="mt-3 font-semibold">Belum ada item agenda dalam SK ini.</p>
            <p class="mt-1 text-sm text-base-content/50">Klik tombol di bawah untuk menambahkan item agenda dari SK Banmus.</p>
            <button type="button" data-banmus-item-open class="btn btn-primary btn-sm mt-4 gap-1">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Tambah Item Agenda Pertama
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
            <div class="w-full overflow-x-auto max-sm:overflow-x-visible">
                <table
                    id="table-jadwal-banmus"
                    class="table table-zebra table-md w-full admin-data-table responsive-card-table"
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
                                        <span class="badge badge-ghost badge-sm whitespace-nowrap">Kegiatan non-rapat</span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral badge-soft badge-sm whitespace-nowrap">Rapat</span>
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
                                        <div class="mt-2 flex max-w-xs flex-wrap gap-1">
                                            <?php foreach ($unitNames as $unitName): ?>
                                                <span class="badge badge-ghost badge-xs h-auto py-1"><?= esc($unitName) ?></span>
                                            <?php endforeach; ?>
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
                                    <span class="badge <?= $statusClass ?> badge-sm whitespace-nowrap font-semibold">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td data-label="Aksi">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <?php if ($item['status'] === 'proyeksi' && $missingFields !== []): ?>
                                            <span
                                                class="tooltip tooltip-left inline-flex"
                                                data-tip="<?= esc($projectionWarning, 'attr') ?>"
                                                aria-label="<?= esc($projectionWarning, 'attr') ?>">
                                                <i data-lucide="triangle-alert" class="h-4 w-4 text-warning"></i>
                                            </span>
                                        <?php endif; ?>
                                        <button
                                            type="button"
                                            data-banmus-item-edit
                                            data-item="<?= esc(json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>"
                                            class="btn btn-outline btn-primary btn-xs gap-1"
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
                                            <button type="submit" class="btn btn-ghost btn-error btn-xs btn-square" title="Hapus item">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
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
    <div class="modal-box max-w-4xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2" id="modal_title">
            <i data-lucide="calendar-plus" class="h-5 w-5 text-primary"></i>
            <span>Tambah Item Agenda Banmus</span>
        </h3>
        <p class="mt-1 text-sm text-base-content/60">Simpan item kapan saja; status jadwal ditentukan otomatis dari kelengkapan data pelaksanaan.</p>

        <form id="item_form" action="" method="post" class="mt-4 flex flex-col gap-4" data-turbo="false">
            <?= csrf_field() ?>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Uraian Agenda SK <span class="text-error">*</span></legend>
                <textarea class="textarea min-h-20 w-full resize-none" id="field_agenda" name="agenda" rows="3" required
                    placeholder="Contoh: Rapat Paripurna Penjelasan Kepala Daerah Mengenai Ranperda..."></textarea>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Jenis Item Banmus <span class="text-error">*</span></legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label class="label cursor-pointer items-start justify-start gap-3 rounded-box border border-base-300 bg-base-100 p-3">
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
                            <span class="mt-0.5 block text-xs leading-relaxed text-base-content/55">Masuk ke daftar Agenda Rapat di portal.</span>
                        </span>
                    </label>
                    <label class="label cursor-pointer items-start justify-start gap-3 rounded-box border border-base-300 bg-base-100 p-3">
                        <input
                            class="radio radio-sm mt-0.5"
                            id="field_jenis_agenda_non_rapat"
                            name="jenis_agenda"
                            type="radio"
                            value="non_rapat"
                            required />
                        <span>
                            <span class="block text-sm font-bold text-base-content">Kegiatan non-rapat</span>
                            <span class="mt-0.5 block text-xs leading-relaxed text-base-content/55">Reses dan kegiatan lain tetap ada di Proyeksi Banmus, tetapi tidak masuk Agenda Rapat.</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <div class="grid grid-cols-12 gap-3">
                <fieldset class="fieldset col-span-12 sm:col-span-6">
                    <legend class="fieldset-legend">Periode SK (Label Teks)</legend>
                    <input class="input w-full" id="field_periode_label" name="periode_label" type="text"
                        placeholder="Contoh: Juni–Juli 2026 atau Minggu ke-2 Juli" />
                    <p class="label block text-[11px] text-base-content/50">Teks periode asli yang tertulis di SK.</p>
                </fieldset>

                <fieldset class="fieldset col-span-12 sm:col-span-6">
                    <legend class="fieldset-legend">Tanggal Pasti (Opsional)</legend>
                    <input class="input w-full font-semibold" id="field_tanggal" name="tanggal" type="date" />
                    <p class="label block text-[11px] text-base-content/50">Item tetap dapat disimpan meskipun tanggal belum diketahui.</p>
                </fieldset>
            </div>

            <div role="alert" class="alert alert-info alert-soft text-sm">
                <i data-lucide="info" class="h-5 w-5 shrink-0"></i>
                <span>Jika tanggal, jam, lokasi, dan kelompok peserta belum lengkap, item tersimpan sebagai <strong>Proyeksi</strong>. Setelah lengkap, status jadwal mengikuti waktu pelaksanaan secara otomatis.</span>
            </div>

            <div class="space-y-4 border-t border-base-200 pt-4">
                <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-primary">
                    <i data-lucide="calendar-cog" class="h-4 w-4"></i>
                    <span>Rencana Pelaksanaan</span>
                </div>

                <div class="grid grid-cols-12 gap-3">
                    <fieldset class="fieldset col-span-6">
                        <legend class="fieldset-legend">Jam Mulai</legend>
                        <input class="input w-full" id="field_jam_mulai" name="jam_mulai" type="time" />
                    </fieldset>

                    <fieldset class="fieldset col-span-6">
                        <legend class="fieldset-legend">Jam Selesai</legend>
                        <input class="input w-full" id="field_jam_selesai" name="jam_selesai" type="time" />
                    </fieldset>
                </div>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Ruangan Rapat</legend>
                    <select class="select w-full" id="field_ruangan_id" name="ruangan_id">
                        <option value="">-- Pilih Ruangan --</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= $room['id'] ?>"><?= esc($room['name']) ?></option>
                        <?php endforeach; ?>
                        <option value="other">-- Lokasi Lainnya --</option>
                    </select>
                </fieldset>

                <fieldset class="fieldset hidden" id="field_lokasi_lainnya_wrapper">
                    <legend class="fieldset-legend">Nama Lokasi Lainnya</legend>
                    <input class="input w-full" id="field_lokasi_lainnya" name="lokasi_lainnya" type="text" placeholder="Contoh: Hotel Santika Palu" />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Kelompok Peserta (Komisi / Alat Kelengkapan)</legend>
                    <div class="grid grid-cols-2 gap-2 rounded-box border border-base-300 p-3 max-h-36 overflow-y-auto">
                        <?php foreach ($units as $unit): ?>
                            <label class="cursor-pointer label justify-start gap-2 py-1">
                                <input type="checkbox" name="unit_ids[]" value="<?= $unit['id'] ?>" class="checkbox checkbox-xs unit-checkbox" />
                                <span class="label-text text-xs"><?= esc($unit['nama']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Visibilitas Publikasi</legend>
                    <select class="select select-sm w-full" id="field_publikasi" name="publikasi">
                        <option value="internal">Internal DPRD</option>
                        <option value="publik">Publik</option>
                    </select>
                </fieldset>

                <div class="grid grid-cols-12 gap-3">
                    <fieldset class="fieldset col-span-12 sm:col-span-6">
                        <legend class="fieldset-legend">Tautan Materi / Dokumen</legend>
                        <input class="input w-full" id="field_materi_url" name="materi_url" type="url" placeholder="https://..." />
                        <label class="label" for="field_materi_akses">Akses bahan</label>
                        <select class="select select-sm w-full" id="field_materi_akses" name="materi_akses">
                            <option value="peserta">Peserta rapat</option>
                            <option value="anggota">Seluruh anggota DPRD</option>
                            <option value="publik">Publik</option>
                        </select>
                    </fieldset>
                    <fieldset class="fieldset col-span-12 sm:col-span-6">
                        <legend class="fieldset-legend">Tautan Live Streaming</legend>
                        <input class="input w-full" id="field_stream_url" name="stream_url" type="url" placeholder="https://..." />
                        <label class="label" for="field_stream_akses">Akses live/video</label>
                        <select class="select select-sm w-full" id="field_stream_akses" name="stream_akses">
                            <option value="anggota">Seluruh anggota DPRD</option>
                            <option value="peserta">Peserta rapat</option>
                            <option value="publik">Publik</option>
                        </select>
                    </fieldset>
                </div>
            </div>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Catatan Item (Opsional)</legend>
                <input class="input w-full" id="field_catatan" name="catatan" type="text" placeholder="Catatan tambahan untuk item ini..." />
            </fieldset>

            <div class="modal-action flex-wrap">
                <button type="button" data-banmus-item-close class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary gap-1">
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
