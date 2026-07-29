<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

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
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="w-12 text-center">No</th>
                        <th>Uraian Agenda SK</th>
                        <th class="w-44 text-center">Periode / Tanggal</th>
                        <th class="w-48">Ruangan & Peserta</th>
                        <th class="w-28 text-center">Status</th>
                        <th class="w-36 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <?php
                        $hasDate = ! empty($item['tanggal']);
                        $statusClass = match ($item['status']) {
                            'fixed'      => 'badge-success text-white',
                            'proyeksi'   => 'badge-warning',
                            'selesai'    => 'badge-info',
                            'ditunda'    => 'badge-error',
                            'dibatalkan' => 'badge-ghost line-through',
                            default      => 'badge-ghost',
                        };
                        $statusLabel = match ($item['status']) {
                            'fixed'      => 'Fixed',
                            'proyeksi'   => 'Proyeksi',
                            'selesai'    => 'Selesai',
                            'ditunda'    => 'Ditunda',
                            'dibatalkan' => 'Dibatalkan',
                            default      => ucfirst($item['status']),
                        };

                        $roomName = '';
                        if (! empty($item['ruangan_id'])) {
                            foreach ($rooms as $r) {
                                if ((int) $r['id'] === (int) $item['ruangan_id']) {
                                    $roomName = $r['name'];
                                    break;
                                }
                            }
                        }
                        if ($roomName === '' && ! empty($item['lokasi_lainnya'])) {
                            $roomName = $item['lokasi_lainnya'];
                        }

                        $unitIds = array_map('intval', $item['unit_ids'] ?? []);
                        $unitNames = [];
                        if ($unitIds !== []) {
                            foreach ($units as $u) {
                                if (in_array((int) $u['id'], array_map('intval', $unitIds), true)) {
                                    $unitNames[] = $u['nama'];
                                }
                            }
                        }
                        ?>
                        <tr class="hover align-top">
                            <td class="text-center font-medium text-base-content/50 pt-4"><?= $index + 1 ?></td>
                            <td class="pt-4">
                                <div class="font-bold text-base-content text-sm leading-snug">
                                    <?= nl2br(esc($item['agenda'])) ?>
                                </div>
                                <?php if (! empty($item['catatan'])): ?>
                                    <div class="mt-1 text-xs text-base-content/60">
                                        <i data-lucide="info" class="inline h-3 w-3 mr-0.5"></i> <?= esc($item['catatan']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pt-4">
                                <?php if ($hasDate): ?>
                                    <div class="font-bold text-sm text-primary">
                                        <?= date('d/m/Y', strtotime($item['tanggal'])) ?>
                                    </div>
                                    <?php if (! empty($item['jam_mulai'])): ?>
                                        <div class="text-xs text-base-content/60 font-mono">
                                            <?= substr($item['jam_mulai'], 0, 5) ?> - <?= ! empty($item['jam_selesai']) ? substr($item['jam_selesai'], 0, 5) : 'Selesai' ?> WITA
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-xs font-semibold text-base-content/70">
                                        <?= esc($item['periode_label'] ?: 'Belum diisi') ?>
                                    </div>
                                    <div class="text-[10px] text-base-content/40 italic">Rentang SK</div>
                                <?php endif; ?>
                            </td>
                            <td class="pt-4 text-xs">
                                <?php if ($roomName !== ''): ?>
                                    <div class="font-medium text-base-content">
                                        <i data-lucide="door-open" class="inline h-3.5 w-3.5 text-primary mr-1"></i><?= esc($roomName) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-base-content/40 italic">- Ruangan belum set -</span>
                                <?php endif; ?>

                                <?php if ($unitNames !== []): ?>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <?php foreach ($unitNames as $uName): ?>
                                            <span class="badge badge-ghost badge-xs"><?= esc($uName) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pt-4">
                                <span class="badge <?= $statusClass ?> badge-sm font-semibold">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td class="text-right pt-4">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        data-banmus-item-edit
                                        data-item="<?= esc(json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>"
                                        class="btn btn-ghost btn-xs btn-square"
                                        title="Edit Jadwal Banmus">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    </button>

                                    <!-- Quick Status Toggle -->
                                    <div class="dropdown dropdown-end">
                                        <div tabindex="0" role="button" class="btn btn-ghost btn-xs btn-square" title="Ubah Status">
                                            <i data-lucide="ellipsis-vertical" class="h-3.5 w-3.5"></i>
                                        </div>
                                        <ul tabindex="0" class="dropdown-content menu z-[10] p-1 shadow bg-base-100 rounded-box w-36 text-xs">
                                            <li>
                                                <form action="<?= base_url("admin/jadwal-banmus/{$document['id']}/item/{$item['id']}/status") ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="status" value="selesai">
                                                    <button type="submit" class="text-info w-full text-left">Tandai Selesai</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="<?= base_url("admin/jadwal-banmus/{$document['id']}/item/{$item['id']}/status") ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="status" value="ditunda">
                                                    <button type="submit" class="text-warning w-full text-left">Ditunda</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="<?= base_url("admin/jadwal-banmus/{$document['id']}/item/{$item['id']}/status") ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="status" value="dibatalkan">
                                                    <button type="submit" class="text-error w-full text-left">Dibatalkan</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>

                                    <form action="<?= base_url("admin/jadwal-banmus/{$document['id']}/item/{$item['id']}/delete") ?>" method="post" class="inline" data-confirm-message="Hapus item agenda ini?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-xs btn-square text-error" title="Hapus Item">
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
    <?php endif; ?>
</section>

<!-- Modal editor satu-record: Proyeksi dan Fixed memakai jadwal_banmus yang sama. -->
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
        <p class="mt-1 text-sm text-base-content/60">Satu item yang sama dapat dilengkapi bertahap sampai siap ditetapkan sebagai fixed.</p>

        <form id="item_form" action="" method="post" class="mt-4 flex flex-col gap-4" data-turbo="false">
            <?= csrf_field() ?>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Uraian Agenda SK <span class="text-error">*</span></legend>
                <textarea class="textarea min-h-20 w-full resize-none" id="field_agenda" name="agenda" rows="3" required
                    placeholder="Contoh: Rapat Paripurna Penjelasan Kepala Daerah Mengenai Ranperda..."></textarea>
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
                    <p class="label block text-[11px] text-base-content/50">Tanggal dapat disimpan tanpa otomatis mengubah status menjadi fixed.</p>
                </fieldset>
            </div>

            <div role="alert" class="alert alert-info alert-soft text-sm">
                <i data-lucide="info" class="h-5 w-5 shrink-0"></i>
                <span><strong>Simpan Proyeksi</strong> menerima data yang belum lengkap. Validasi lengkap baru dijalankan saat <strong>Tetapkan Fixed</strong>.</span>
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
                    </fieldset>
                    <fieldset class="fieldset col-span-12 sm:col-span-6">
                        <legend class="fieldset-legend">Tautan Live Streaming</legend>
                        <input class="input w-full" id="field_stream_url" name="stream_url" type="url" placeholder="https://..." />
                    </fieldset>
                </div>
            </div>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Catatan Item (Opsional)</legend>
                <input class="input w-full" id="field_catatan" name="catatan" type="text" placeholder="Catatan tambahan untuk item ini..." />
            </fieldset>

            <div class="modal-action flex-wrap">
                <button type="button" data-banmus-item-close class="btn btn-ghost">Batal</button>
                <button type="submit" name="action" value="save_projection" class="btn btn-neutral gap-1" data-banmus-save-draft>
                    <i data-lucide="save" class="h-4 w-4"></i>
                    <span>Simpan Proyeksi</span>
                </button>
                <button type="submit" name="action" value="set_fixed" class="btn btn-primary gap-1" data-banmus-set-fixed>
                    <i data-lucide="badge-check" class="h-4 w-4"></i>
                    Tetapkan Fixed
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>Tutup dialog</button>
    </form>
</dialog>

<?= $this->endSection() ?>
