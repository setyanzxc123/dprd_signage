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
    <div class="flex min-w-0 items-start gap-2.5">
        <a href="<?= base_url('admin/jadwal-banmus') ?>" class="size-9 inline-flex justify-center items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition shrink-0" title="Kembali ke daftar SK">
            <i data-lucide="arrow-left" class="size-4"></i>
        </a>
        <div class="min-w-0">
            <h1 class="page-title">SK No. <?= esc($document['nomor_sk']) ?></h1>
            <p class="mt-1 max-w-3xl text-xs leading-relaxed text-slate-500 dark:text-slate-400"><?= esc($document['judul']) ?></p>
        </div>
    </div>
    <button type="button" data-banmus-item-open aria-haspopup="dialog" aria-expanded="false" aria-controls="item_modal" class="py-2 px-3.5 inline-flex items-center justify-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition w-full sm:w-auto cursor-pointer">
        <i data-lucide="plus" class="size-4"></i>
        Tambah Item Agenda
    </button>
</div>

<div class="mb-4 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center py-1 px-2.5 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
            Semester <?= (int) $document['semester'] ?> &bull; <?= (int) $document['tahun'] ?>
        </span>
        <span class="inline-flex items-center py-1 px-2.5 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
            <?= ! empty($document['is_publik']) ? 'Dokumen publik' : 'Dokumen internal' ?>
        </span>
        <span class="inline-flex items-center py-1 px-2.5 rounded-lg text-xs font-medium bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">
            <?= $projectionCount ?> proyeksi
        </span>
        <span class="inline-flex items-center py-1 px-2.5 rounded-lg text-xs font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
            <?= $scheduledCount ?> terjadwal
        </span>
    </div>
    <div class="flex items-center gap-1.5">
        <?php if (! empty($document['dokumen_file'])): ?>
            <a
                href="<?= base_url('uploads/sk-banmus/' . $document['dokumen_file']) ?>"
                target="_blank"
                rel="noopener"
                class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-rose-200/80 bg-rose-50 text-rose-700 hover:bg-rose-100 hover:text-rose-800 hover:border-rose-300 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-600 dark:hover:text-white dark:hover:border-rose-600 shadow-2xs transition"
                title="Buka PDF SK">
                <i data-lucide="file-text" class="size-3.5 text-rose-500"></i>
                PDF SK
            </a>
        <?php endif; ?>
        <a href="<?= base_url('admin/jadwal-banmus/' . $document['id'] . '/edit') ?>" class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-blue-200/80 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 hover:border-blue-300 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-600 dark:hover:text-white dark:hover:border-blue-600 shadow-2xs transition" title="Edit Metadata SK">
            <i data-lucide="pencil" class="size-3.5"></i>
            Edit SK
        </a>
    </div>
</div>

<!-- DataTable Item Agenda Banmus -->
<section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden min-w-0">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 px-4 py-3 sm:px-5">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                <i data-lucide="list-todo" class="size-4.5"></i>
            </span>
            <h2 class="text-sm sm:text-base font-bold text-slate-800 dark:text-slate-100">Item Agenda</h2>
        </div>
        <span class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= count($items) ?> item</span>
    </div>

    <?php if ($items === []): ?>
        <div class="p-12 text-center text-slate-500 dark:text-slate-400">
            <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400">
                <i data-lucide="calendar-plus" class="size-7"></i>
            </span>
            <p class="mt-3 font-semibold text-slate-800 dark:text-slate-200">Belum ada item agenda dalam SK ini.</p>
            <button type="button" data-banmus-item-open aria-haspopup="dialog" aria-expanded="false" aria-controls="item_modal" class="mt-4 py-2 px-3.5 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer">
                <i data-lucide="plus" class="size-4"></i>
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
                    class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 admin-data-table"
                    data-admin-datatable
                    data-dt-page-length="10"
                    data-dt-col-filters='[{"col":2,"label":"Jenis","all":"Semua Jenis"},{"col":4,"label":"Publikasi","all":"Semua Publikasi"},{"col":5,"label":"Status","all":"Semua Status"}]'>
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/50">
                            <th class="dt-row-number no-sort">No</th>
                            <th>Agenda</th>
                            <th>Jenis</th>
                            <th>Jadwal</th>
                            <th class="mobile-hidden">Publikasi</th>
                            <th>Status</th>
                            <th class="text-end no-sort">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $hasDate = ! empty($item['tanggal']);
                            $hasFixedSchedule = $item['status'] !== 'proyeksi' && $hasDate;
                            $statusBadgeClass = match ($item['status']) {
                                'proyeksi'   => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60',
                                'menunggu'   => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300',
                                'persiapan'  => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60',
                                'berlangsung' => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60',
                                'selesai'    => 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/60',
                                'ditunda'    => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-dashed border-amber-400 dark:border-amber-600',
                                'dibatalkan' => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 line-through',
                                default      => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300',
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


                            $scheduleOrder = $hasFixedSchedule
                                ? $item['tanggal'] . ' ' . ($item['jam_mulai'] ?? '00:00:00')
                                : '9999-12-31 ' . str_pad((string) ($item['urutan'] ?? 0), 6, '0', STR_PAD_LEFT);
                            ?>
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition align-top">
                                <td class="dt-row-number px-4 py-3.5" data-label="No"></td>
                                <td class="px-4 py-3.5" data-label="Agenda">
                                    <div class="max-w-xl text-sm font-bold leading-snug text-slate-900 dark:text-white">
                                        <?= nl2br(esc($item['agenda'])) ?>
                                    </div>

                                    <?php if (! empty($item['materi_url']) || ! empty($item['stream_url'])): ?>
                                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                                            <?php if (! empty($item['materi_url'])): ?>
                                                <a
                                                    href="<?= esc($item['materi_url'], 'attr') ?>"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="py-0.5 px-2 inline-flex items-center gap-x-1 text-[11px] font-medium rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-2xs transition"
                                                    title="Materi (<?= esc($resourceAccessLabels[$item['materi_akses'] ?? 'peserta'] ?? 'Terbatas') ?>)">
                                                    <i data-lucide="paperclip" class="size-3 text-slate-400"></i>
                                                    Materi
                                                </a>
                                            <?php endif; ?>
                                            <?php if (! empty($item['stream_url'])): ?>
                                                <a
                                                    href="<?= esc($item['stream_url'], 'attr') ?>"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="py-0.5 px-2 inline-flex items-center gap-x-1 text-[11px] font-medium rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-2xs transition"
                                                    title="Live (<?= esc($resourceAccessLabels[$item['stream_akses'] ?? 'anggota'] ?? 'Terbatas') ?>)">
                                                    <i data-lucide="radio" class="size-3 text-rose-500"></i>
                                                    Live
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap" data-label="Jenis">
                                    <?php if (($item['jenis_agenda'] ?? 'rapat') === 'non_rapat'): ?>
                                        <span class="inline-flex items-center py-0.5 px-2 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Non-rapat</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center py-0.5 px-2 rounded-md text-[11px] font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">Rapat</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap" data-label="Jadwal" data-order="<?= esc($scheduleOrder, 'attr') ?>">
                                    <?php if ($hasFixedSchedule): ?>
                                        <div class="whitespace-nowrap text-xs font-bold text-slate-900 dark:text-white">
                                            <?= esc(date('d/m/Y', strtotime($item['tanggal']))) ?>
                                        </div>
                                        <div class="mt-0.5 whitespace-nowrap font-mono text-[11px] text-slate-500 dark:text-slate-400">
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
                                        <div class="max-w-48 text-xs font-semibold text-slate-600 dark:text-slate-400">
                                            <?= esc($projectionLabel !== '' ? $projectionLabel : 'Periode belum diisi') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="mobile-hidden px-4 py-3.5 whitespace-nowrap" data-label="Publikasi">
                                    <?php if (($item['publikasi'] ?? 'internal') === 'publik'): ?>
                                        <span class="inline-flex items-center py-0.5 px-2 rounded-md text-[11px] font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 whitespace-nowrap">Publik</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center py-0.5 px-2 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 whitespace-nowrap">Internal</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap" data-label="Status">
                                    <span class="inline-flex items-center py-0.5 px-2 rounded-md text-[11px] font-semibold whitespace-nowrap <?= $statusBadgeClass ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-end" data-label="Aksi">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <?php if ($item['status'] === 'proyeksi'): ?>
                                            <button
                                                type="button"
                                                data-banmus-item-schedule
                                                data-item="<?= esc(json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>"
                                                class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-emerald-300/80 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800 hover:border-emerald-400 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-600 dark:hover:text-white dark:hover:border-emerald-600 shadow-2xs transition cursor-pointer"
                                                title="Tetapkan tanggal dan ruangan rapat">
                                                <i data-lucide="calendar-plus" class="size-3.5"></i>
                                                Jadwalkan
                                            </button>
                                        <?php endif; ?>

                                        <button
                                            type="button"
                                            data-banmus-item-edit
                                            data-item="<?= esc(json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>"
                                            class="py-1.5 px-2.5 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-lg border border-blue-200/80 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 hover:border-blue-300 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-600 dark:hover:text-white dark:hover:border-blue-600 shadow-2xs transition cursor-pointer"
                                            title="Edit item agenda">
                                            <i data-lucide="pencil" class="size-3.5"></i>
                                            Edit
                                        </button>

                                        <a
                                            href="<?= base_url('admin/notulen?jadwal_type=banmus&jadwal_id=' . (int) $item['id']) ?>"
                                            class="p-1.5 inline-flex items-center justify-center rounded-lg border border-emerald-200/80 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800 hover:border-emerald-300 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-600 dark:hover:text-white dark:hover:border-emerald-600 shadow-2xs transition"
                                            title="Buka / Buat Notulensi AI">
                                            <i data-lucide="mic" class="size-4"></i>
                                        </a>

                                        <form
                                            action="<?= base_url("admin/jadwal-banmus/{$document['id']}/item/{$item['id']}/delete") ?>"
                                            method="post"
                                            class="m-0 inline-flex"
                                            data-confirm-message="Hapus item agenda ini?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1.5 inline-flex items-center justify-center rounded-lg border border-rose-200/80 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 hover:border-rose-300 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-600 dark:hover:text-white dark:hover:border-rose-600 shadow-2xs transition cursor-pointer" title="Hapus item agenda">
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
    <?php endif; ?>
</section>

<!-- Modal editor satu-record: wizard branching Step 1 (Proyeksi vs Pasti) & Step 2 (Form) -->
<div
    id="item_modal"
    class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none"
    role="dialog"
    tabindex="-1"
    aria-labelledby="modal_title"
    data-banmus-item-dialog
    data-store-url="<?= base_url("admin/jadwal-banmus/{$document['id']}/item/store") ?>"
    data-update-url-template="<?= base_url("admin/jadwal-banmus/{$document['id']}/item/__ITEM_ID__/update") ?>">
    <div id="item_modal_dialog" class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-4xl sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-slate-200 shadow-2xl rounded-2xl pointer-events-auto dark:bg-slate-900 dark:border-slate-800">
            <!-- Header Dialog -->
            <div class="flex shrink-0 items-center justify-between border-b border-slate-100 dark:border-slate-800 px-5 py-4">
                <div class="flex items-center gap-2.5">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <i data-lucide="calendar-plus" class="size-5"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white" id="modal_title">
                        Tambah Item Agenda Banmus
                    </h3>
                </div>
                <button type="button" class="size-8 inline-flex justify-center items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 transition cursor-pointer" data-hs-overlay="#item_modal" aria-label="Tutup dialog">
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </div>

            <!-- STEP 1: Branching Cards (Hanya saat Tambah Baru) -->
            <div id="banmus-wizard-step1" class="p-5 sm:p-6">
                <h4 class="text-base font-bold text-slate-900 dark:text-white">Pilih Kesiapan Agenda</h4>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Pilih format input sesuai kesiapan data agenda.</p>

                <div class="grid sm:grid-cols-2 gap-4 mt-4">
                    <!-- Kartu 1: Proyeksi -->
                    <button type="button" id="btn-select-proyeksi" class="p-4 sm:p-5 text-start rounded-2xl border-2 border-slate-200 dark:border-slate-700 hover:border-amber-500 dark:hover:border-amber-500 bg-white dark:bg-slate-800/60 hover:bg-amber-50/20 dark:hover:bg-amber-950/20 shadow-xs transition group cursor-pointer">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400 group-hover:scale-110 transition">
                            <i data-lucide="calendar-clock" class="size-5"></i>
                        </div>
                        <h5 class="mt-3.5 text-sm font-bold text-slate-900 dark:text-white group-hover:text-amber-600 dark:hover:text-amber-400 transition">
                            Proyeksi
                        </h5>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Cukup isi uraian agenda dan perkiraan periode. Jadwal dan lokasi dilengkapi kemudian.
                        </p>
                        <span class="mt-3.5 inline-flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                            Pilih Proyeksi &rarr;
                        </span>
                    </button>

                    <!-- Kartu 2: Jadwal Pasti -->
                    <button type="button" id="btn-select-pasti" class="p-4 sm:p-5 text-start rounded-2xl border-2 border-slate-200 dark:border-slate-700 hover:border-emerald-500 dark:hover:border-emerald-500 bg-white dark:bg-slate-800/60 hover:bg-emerald-50/20 dark:hover:bg-emerald-950/20 shadow-xs transition group cursor-pointer">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 group-hover:scale-110 transition">
                            <i data-lucide="calendar-check-2" class="size-5"></i>
                        </div>
                        <h5 class="mt-3.5 text-sm font-bold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition">
                            Jadwal Pasti
                        </h5>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Entri lengkap mencakup tanggal, waktu, ruangan rapat, dan peserta.
                        </p>
                        <span class="mt-3.5 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            Pilih Jadwal Pasti &rarr;
                        </span>
                    </button>
                </div>
            </div>

            <!-- STEP 2: Form Input -->
            <form id="item_form" action="" method="post" enctype="multipart/form-data" class="hidden flex-col">
                <?= csrf_field() ?>

                <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/60 dark:bg-slate-800/30">
                    <button type="button" id="btn-back-to-step1" class="inline-flex items-center gap-x-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition cursor-pointer">
                        <i data-lucide="arrow-left" class="size-3.5"></i>
                        <span>Kembali</span>
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" id="btn-switch-to-pasti" class="hidden inline-flex items-center gap-1 py-1 px-2.5 rounded-lg text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/60 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition cursor-pointer">
                            <i data-lucide="calendar-plus" class="size-3.5"></i>
                            <span>Lengkapi Jadwal Pasti &rarr;</span>
                        </button>
                        <span id="form-mode-indicator" class="inline-flex items-center gap-1 py-0.5 px-2 rounded-md text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            Mode
                        </span>
                    </div>
                </div>

                <div class="max-h-[calc(100dvh-15rem)] overflow-y-auto p-5 sm:p-6 space-y-5">
                    <div id="banmus-form-grid" class="grid gap-5 lg:grid-cols-2">
                        <!-- Informasi Agenda Dasar -->
                        <div id="banmus-info-agenda-card" class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                <i data-lucide="file-text" class="size-4 text-emerald-500"></i>
                                Informasi Agenda SK
                            </h4>

                            <div>
                                <label for="field_agenda" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Uraian Agenda SK <span class="text-rose-500">*</span>
                                </label>
                                <textarea class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 min-h-24 resize-none" id="field_agenda" name="agenda" rows="4" required
                                    placeholder="Tuliskan uraian agenda sesuai SK Banmus"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Jenis Item <span class="text-rose-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="flex items-center gap-x-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer">
                                        <input
                                            class="size-4 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700"
                                            id="field_jenis_agenda_rapat"
                                            name="jenis_agenda"
                                            type="radio"
                                            value="rapat"
                                            checked
                                            required />
                                        <div class="leading-tight">
                                            <span class="block text-xs font-bold text-slate-900 dark:text-white">Rapat</span>
                                            <span class="block text-[10px] text-slate-400">Masuk Agenda Rapat</span>
                                        </div>
                                    </label>
                                    <label class="flex items-center gap-x-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer">
                                        <input
                                            class="size-4 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700"
                                            id="field_jenis_agenda_non_rapat"
                                            name="jenis_agenda"
                                            type="radio"
                                            value="non_rapat"
                                            required />
                                        <div class="leading-tight">
                                            <span class="block text-xs font-bold text-slate-900 dark:text-white">Non-rapat</span>
                                            <span class="block text-[10px] text-slate-400">Kegiatan luar / reses</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div id="banmus-periode-wrapper">
                                <label for="field_periode_label" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Periode SK
                                </label>
                                <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="field_periode_label" name="periode_label" type="text"
                                    placeholder="Contoh: Juni–Juli 2026 atau Minggu ke-2 Juli" />
                            </div>

                            <div>
                                <label for="field_catatan" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Catatan
                                </label>
                                <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="field_catatan" name="catatan" type="text" placeholder="Catatan tambahan untuk item ini" />
                            </div>
                        </div>

                        <!-- Pelaksanaan, Ruangan & Peserta (Dinamis) -->
                        <div id="banmus-schedule-fields" class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                <i data-lucide="calendar-cog" class="size-4 text-emerald-500"></i>
                                Pelaksanaan &amp; Peserta
                            </h4>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <label for="field_tanggal" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Tanggal Rapat
                                    </label>
                                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 font-semibold" id="field_tanggal" name="tanggal" type="date" />
                                </div>
                                <div>
                                    <label for="field_jam_mulai" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Jam Mulai
                                    </label>
                                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="field_jam_mulai" name="jam_mulai" type="time" />
                                </div>
                                <div>
                                    <label for="field_jam_selesai" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Jam Selesai
                                    </label>
                                    <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="field_jam_selesai" name="jam_selesai" type="time" />
                                </div>
                            </div>

                            <div id="banmus-room-wrapper">
                                <label for="field_ruangan_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Ruangan Rapat
                                </label>
                                <select class="py-2.5 px-3.5 pe-9 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="field_ruangan_id" name="ruangan_id">
                                    <option value="">Pilih ruangan</option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?= $room['id'] ?>"><?= esc($room['name']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">Lokasi lainnya</option>
                                </select>
                            </div>

                            <div class="hidden" id="field_lokasi_lainnya_wrapper">
                                <label for="field_lokasi_lainnya" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Nama Lokasi Lainnya
                                </label>
                                <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="field_lokasi_lainnya" name="lokasi_lainnya" type="text" placeholder="Contoh: Hotel Santika Palu" />
                            </div>

                            <div id="banmus-units-wrapper">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Kelompok Peserta
                                </label>
                                <div class="grid max-h-36 grid-cols-2 gap-x-3 gap-y-1.5 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-3">
                                    <?php foreach ($units as $unit): ?>
                                        <label class="flex items-center gap-x-2 py-0.5 cursor-pointer">
                                            <input type="checkbox" name="unit_ids[]" value="<?= $unit['id'] ?>" class="size-3.5 text-emerald-600 rounded focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 unit-checkbox" />
                                            <span class="text-xs text-slate-700 dark:text-slate-300 truncate"><?= esc($unit['nama']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Publikasi & Bahan -->
                    <div id="banmus-publication-fields" class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 space-y-4">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <i data-lucide="eye" class="size-4 text-emerald-500"></i>
                            Publikasi &amp; Tautan Dokumen
                        </h4>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="field_publikasi" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Akses Publikasi Agenda
                                </label>
                                <select class="py-2.5 px-3.5 pe-9 block w-full sm:max-w-xs border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="field_publikasi" name="publikasi">
                                    <option value="internal">Internal DPRD Saja</option>
                                    <option value="publik" selected>Publik</option>
                                </select>
                            </div>

                            <div id="banmus-materi-wrapper">
                                <label for="field_materi_url" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Tautan Materi / Bahan Rapat
                                </label>
                                <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="field_materi_url" name="materi_url" type="url" placeholder="https://..." />
                                <div class="mt-2">
                                    <label class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-1" for="field_materi_akses">Akses Materi</label>
                                    <select class="py-2 px-3 pe-9 block w-full border border-slate-200 rounded-lg text-xs focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="field_materi_akses" name="materi_akses">
                                        <option value="peserta">Peserta rapat saja</option>
                                        <option value="anggota">Seluruh anggota DPRD</option>
                                        <option value="publik" selected>Publik</option>
                                    </select>
                                </div>
                            </div>

                            <div id="banmus-stream-wrapper">
                                <label for="field_stream_url" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Tautan Live Streaming
                                </label>
                                <input class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="field_stream_url" name="stream_url" type="url" placeholder="https://..." />
                                <div class="mt-2">
                                    <label class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-1" for="field_stream_akses">Akses Streaming</label>
                                    <select class="py-2 px-3 pe-9 block w-full border border-slate-200 rounded-lg text-xs focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="field_stream_akses" name="stream_akses">
                                        <option value="anggota">Seluruh anggota DPRD</option>
                                        <option value="peserta">Peserta rapat saja</option>
                                        <option value="publik" selected>Publik</option>
                                    </select>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="field_undangan_file" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                    Surat Undangan Rapat (PDF)
                                </label>
                                <input class="block w-full border border-slate-200 shadow-xs rounded-xl text-sm focus:z-10 focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-400 file:bg-slate-50 file:border-0 file:me-4 file:py-2.5 file:px-4 dark:file:bg-slate-800 dark:file:text-slate-400" id="field_undangan_file" name="undangan_file" type="file"
                                    accept="application/pdf,.pdf" />
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">PDF maksimal 10 MB. Hanya dapat diakses oleh anggota DPRD yang sudah login.</p>
                                <div class="flex items-center gap-2 p-3 mt-2 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300 hidden" id="field_undangan_existing">
                                    <i data-lucide="file-check-2" class="size-4 shrink-0"></i>
                                    <span class="text-xs font-semibold truncate flex-1" id="field_undangan_name"></span>
                                    <label class="flex items-center gap-1.5 text-xs text-rose-600 dark:text-rose-400 cursor-pointer" for="field_hapus_undangan">
                                        <input class="size-3.5 text-rose-600 rounded focus:ring-rose-500" id="field_hapus_undangan" name="hapus_undangan" type="checkbox" value="1" />
                                        <span>Hapus</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Footer -->
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800 px-5 py-4 bg-slate-50/50 dark:bg-slate-900/50 rounded-b-2xl">
                    <button type="button" data-banmus-item-close class="py-2 px-3.5 inline-flex items-center text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="py-2 px-4 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer">
                        <i data-lucide="save" class="size-4"></i>
                        <span>Simpan Item Agenda</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
