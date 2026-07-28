<?= $this->extend('admin/layouts/main') ?>


<?= $this->section('content') ?>

<?php
    $lokasiLainnya = trim((string) ($meeting['lokasi_lainnya'] ?? ''));
    $lokasiMode = $lokasiLainnya !== '' ? 'lainnya' : 'ruangan';
?>

<div class="page-header">
    <h1 class="page-title"><?= esc($pageTitle) ?></h1>
</div>

<form action="<?= esc($action_url) ?>" method="POST" class="schedule-form" data-turbo="true">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="alert alert-error shadow-sm mb-3" role="alert">
            <i data-lucide="triangle-alert" class="w-4 h-4"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-12 gap-3">
        <div class="col-span-12 lg:col-span-8">
            <section class="card card-border bg-base-100 shadow-sm">
                <div class="card-body gap-5 p-4 sm:p-5">
                    <h2 class="card-title text-base">
                        <i data-lucide="calendar-days" class="h-5 w-5 text-primary"></i>
                        Detail Rapat
                    </h2>

                    <fieldset class="fieldset gap-3">
                        <div class="grid grid-cols-12 gap-3">
                            <div class="col-span-12">
                                <label class="label py-1 font-semibold" for="judul">
                                    Judul Rapat <span class="text-error">*</span>
                                </label>
                                <div class="join w-full">
                                    <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                                    <input type="text" class="input join-item flex-1 w-full" id="judul" name="judul"
                                        value="<?= esc($meeting['judul'] ?? '') ?>"
                                        placeholder="Masukkan judul rapat" required />
                                </div>
                            </div>

                            <div class="col-span-12">
                                <div class="label py-1 font-semibold">
                                    Lokasi Rapat <span class="text-error">*</span>
                                </div>

                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <label class="label cursor-pointer justify-start gap-2 rounded-lg border border-base-300 px-3 py-2 has-checked:border-primary has-checked:bg-primary/10" for="lokasi-ruangan">
                                        <input type="radio" name="lokasi_mode" id="lokasi-ruangan" value="ruangan"
                                            class="radio radio-primary radio-sm"
                                            <?= $lokasiMode === 'ruangan' ? 'checked' : '' ?> />
                                        <span class="font-semibold">Ruangan DPRD</span>
                                    </label>

                                    <label class="label cursor-pointer justify-start gap-2 rounded-lg border border-base-300 px-3 py-2 has-checked:border-primary has-checked:bg-primary/10" for="lokasi-lainnya">
                                        <input type="radio" name="lokasi_mode" id="lokasi-lainnya" value="lainnya"
                                            class="radio radio-primary radio-sm"
                                            <?= $lokasiMode === 'lainnya' ? 'checked' : '' ?> />
                                        <span class="font-semibold">Lokasi Lainnya</span>
                                    </label>
                                </div>

                                <div class="mt-2.5">
                                    <div id="ruangan-panel">
                                        <div class="join w-full">
                                            <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                            <select class="select join-item flex-1 w-full" id="ruangan_id" name="ruangan_id">
                                                <option value="">-- Pilih Ruangan --</option>
                                                <?php if (empty($rooms)): ?>
                                                    <option disabled>Belum ada ruangan - tambah di Master Data dulu</option>
                                                <?php else: ?>
                                                    <?php foreach ($rooms as $r):
                                                        $selected = ($meeting['ruangan_id'] ?? '') == $r['id'] ? 'selected' : '';
                                                    ?>
                                                        <option value="<?= $r['id'] ?>" <?= $selected ?>>
                                                            <?= esc($r['name'] ?? '') ?><?= isset($r['kapasitas']) ? ' (Kap. ' . $r['kapasitas'] . ' orang)' : '' ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div id="lokasi-lainnya-panel" hidden>
                                        <div class="join w-full">
                                            <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="map-pinned" class="w-4 h-4"></i></span>
                                            <input type="text" class="input join-item flex-1 w-full" id="lokasi_lainnya" name="lokasi_lainnya"
                                                value="<?= esc($lokasiLainnya) ?>"
                                                placeholder="Masukkan lokasi rapat" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 md:col-span-6">
                                <label class="label py-1 font-semibold" for="waktu_mulai">
                                    Waktu Mulai <span class="text-error">*</span>
                                </label>
                                <div class="join w-full">
                                    <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="calendar-clock" class="w-4 h-4"></i></span>
                                    <input type="datetime-local" class="input join-item flex-1 w-full" id="waktu_mulai" name="waktu_mulai"
                                        value="<?= esc(isset($meeting['tanggal'], $meeting['waktu_mulai'])
                                            ? $meeting['tanggal'] . 'T' . $meeting['waktu_mulai']
                                            : '') ?>" step="60" required />
                                </div>
                            </div>

                            <div class="col-span-12 md:col-span-6">
                                <label class="label py-1 font-semibold" for="waktu_selesai">
                                    Waktu Selesai <span class="text-error">*</span>
                                </label>
                                <div class="join w-full">
                                    <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="calendar-check" class="w-4 h-4"></i></span>
                                    <input type="datetime-local" class="input join-item flex-1 w-full" id="waktu_selesai" name="waktu_selesai"
                                        value="<?= esc(isset($meeting['tanggal'], $meeting['waktu_selesai'])
                                            ? $meeting['tanggal'] . 'T' . $meeting['waktu_selesai']
                                            : '') ?>" step="60" required />
                                </div>
                            </div>

                            <div class="col-span-12">
                                <div class="text-error text-xs hidden" id="waktu-rapat-error">
                                    Waktu selesai harus setelah waktu mulai pada tanggal yang sama.
                                </div>
                            </div>

                            <div class="col-span-12">
                                <label class="label py-1 font-semibold" for="keterangan">Keterangan / Agenda</label>
                                <textarea class="textarea w-full" id="keterangan" name="keterangan" rows="4"
                                    placeholder="Masukkan agenda atau keterangan"><?= esc($meeting['keterangan'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="fieldset gap-3 border-t border-base-300 pt-4">
                        <legend class="fieldset-legend text-sm">Publikasi & Materi</legend>

                        <div class="grid grid-cols-12 gap-3">
                            <div class="col-span-12">
                                <label class="label py-1 font-semibold" for="materi_url">
                                    Link Materi (QR)
                                </label>
                                <div class="join w-full">
                                    <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="qr-code" class="w-4 h-4"></i></span>
                                    <input type="url" class="input join-item flex-1 w-full" id="materi_url" name="materi_url"
                                        value="<?= esc($meeting['materi_url'] ?? '') ?>"
                                        placeholder="https://..." />
                                </div>
                            </div>

                            <div class="col-span-12">
                                <label class="label py-1 font-semibold" for="stream_url">
                                    Link Streaming / Video
                                </label>
                                <div class="join w-full">
                                    <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold"><i data-lucide="square-play" class="w-4 h-4"></i></span>
                                    <input type="url" class="input join-item flex-1 w-full" id="stream_url" name="stream_url"
                                        value="<?= esc($meeting['stream_url'] ?? '') ?>"
                                        placeholder="https://..." />
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </section>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="lg:sticky lg:top-[88px]">
                <section class="card card-border bg-base-100 shadow-sm">
                    <div class="card-body gap-5 p-4 sm:p-5">
                        <h2 class="card-title text-base">
                            <i data-lucide="sliders-horizontal" class="h-5 w-5 text-primary"></i>
                            Pengaturan
                        </h2>

                        <fieldset class="fieldset gap-3">
                            <legend class="fieldset-legend">Jenis Rapat</legend>
                            <?php $jenis = $meeting['jenis'] ?? 'insidental'; ?>

                            <div class="grid grid-cols-2 gap-2">
                                    <label class="label cursor-pointer justify-start gap-2 rounded-lg border border-base-300 px-3 py-2 has-checked:border-primary has-checked:bg-primary/10" for="jenis-reguler">
                                        <input type="radio" name="jenis" id="jenis-reguler" value="reguler"
                                            class="radio radio-primary radio-xs"
                                            <?= $jenis === 'reguler' ? 'checked' : '' ?> />
                                        <span class="font-semibold">Reguler</span>
                                    </label>

                                    <label class="label cursor-pointer justify-start gap-2 rounded-lg border border-base-300 px-3 py-2 has-checked:border-primary has-checked:bg-primary/10" for="jenis-insidental">
                                        <input type="radio" name="jenis" id="jenis-insidental" value="insidental"
                                            class="radio radio-primary radio-xs"
                                            <?= $jenis === 'insidental' ? 'checked' : '' ?> />
                                        <span class="font-semibold">Insidental</span>
                                    </label>
                            </div>

                            <label class="label cursor-pointer justify-between rounded-lg border border-base-300 px-3 py-2" for="is_publik">
                                    <span class="font-semibold">Publikasi</span>
                                    <span class="flex items-center gap-2">
                                        <input class="toggle toggle-primary" type="checkbox" role="switch"
                                            id="is_publik" name="is_publik" value="1"
                                            <?= ($meeting === null || ($meeting['is_publik'] ?? 1)) ? 'checked' : '' ?> />
                                        <span id="publik-label"><?= ($meeting === null || ($meeting['is_publik'] ?? 1)) ? 'Publik' : 'Internal' ?></span>
                                    </span>
                            </label>
                        </fieldset>

                        <fieldset class="fieldset gap-2 border-t border-base-300 pt-4">
                            <legend class="fieldset-legend flex w-full items-center justify-between gap-2 p-0">
                                <span>Peserta Rapat</span>
                                <span class="badge badge-primary badge-sm" id="target-selected-count">0 dipilih</span>
                            </legend>

                             <div class="overflow-hidden rounded-lg border border-base-300 bg-base-100">
                                 <div class="border-b border-base-300 bg-base-100 p-2">
                                     <div class="relative">
                                         <span class="absolute left-0 top-1/2 -translate-y-1/2 pl-3 text-base-content/40">
                                             <i data-lucide="search" class="w-4 h-4"></i>
                                         </span>
                                         <input type="text" class="input input-sm w-full pl-10"
                                             id="target-search" placeholder="Cari kelompok peserta..." autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="max-h-48 overflow-y-auto" id="target-list">
                                 <?php
                                 $targetUnitIds = $meeting['target_unit_ids'] ?? [];
                                 foreach ($unit_rapat_list as $unit):
                                     $unitId = (int) $unit['id'];
                                     $unitName = $unit['nama'];
                                     $activeMemberCount = (int) ($unit['active_member_count'] ?? 0);
                                     $isUnavailable = $activeMemberCount <= 0;
                                     $checked = (!$isUnavailable && in_array($unitId, $targetUnitIds, true)) ? 'checked' : '';
                                     $selectedClass = $checked ? ' is-selected bg-primary/10 text-primary font-semibold' : ' hover:bg-base-200';
                                     $disabledClass = $isUnavailable ? ' is-disabled opacity-60' : '';
                                     $targetId = 'unit-rapat-' . $unitId;
                                 ?>
                                     <label class="target-option mb-0 flex min-h-9 cursor-pointer select-none items-center gap-2 border-b border-base-300 bg-base-100 px-3 py-1.5 text-sm text-base-content/80<?= $selectedClass ?><?= $disabledClass ?>" for="<?= esc($targetId, 'attr') ?>"
                                         data-name="<?= esc(strtolower($unitName), 'attr') ?>"
                                         data-member-count="<?= $activeMemberCount ?>">
                                         <input class="checkbox checkbox-primary checkbox-xs" type="checkbox" name="target_unit_rapat[]"
                                             value="<?= $unitId ?>" data-name="<?= esc($unitName, 'attr') ?>"
                                             id="<?= esc($targetId, 'attr') ?>" <?= $checked ?> <?= $isUnavailable ? 'disabled' : '' ?> />
                                         <span class="flex-1 min-w-0 truncate"><?= esc($unitName) ?></span>
                                         <?php if ($isUnavailable): ?>
                                             <span class="badge badge-warning badge-xs">0 anggota</span>
                                         <?php endif; ?>
                                     </label>
                                 <?php endforeach; ?>
                                     <div class="text-base-content/50 text-center py-3 hidden" id="target-empty">
                                         <small>Kelompok peserta tidak ditemukan.</small>
                                     </div>
                                 </div>
                             </div>
                             <div class="text-error text-xs mt-2 hidden" id="target-peserta-error">
                                 Pilih minimal satu kelompok peserta yang memiliki anggota aktif.
                             </div>

                        </fieldset>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <div class="form-actions-sticky">
        <a href="<?= base_url('admin/jadwal') ?>" class="btn btn-outline sm:btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary sm:btn-sm">
            <i data-lucide="calendar-check" class="w-4 h-4"></i>
            <span class="sm:hidden">Simpan</span>
            <span class="hidden sm:inline">
                <?= $meeting ? 'Simpan Perubahan' : 'Simpan Jadwal' ?>
            </span>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
