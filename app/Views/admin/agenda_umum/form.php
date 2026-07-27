<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$categoryLabels = [
    'demonstrasi'     => 'Demonstrasi',
    'audiensi_publik' => 'Audiensi Publik',
    'kunjungan'       => 'Kunjungan',
    'kegiatan_sosial' => 'Kegiatan Sosial',
    'lainnya'         => 'Lainnya',
];
$statusLabels = [
    'tentatif'      => 'Tentatif',
    'terkonfirmasi' => 'Terkonfirmasi',
    'selesai'       => 'Selesai',
    'dibatalkan'    => 'Dibatalkan',
];
$isEdit = is_array($agenda);
$selectedCategory = (string) ($agenda['kategori'] ?? 'demonstrasi');
$selectedStatus = (string) ($agenda['status'] ?? 'tentatif');
?>

<div class="page-header">
    <h1 class="page-title"><?= esc($pageTitle) ?></h1>
    <p class="page-subtitle"><?= $isEdit ? 'Perbarui kegiatan publik nonrapat' : 'Tambahkan kegiatan publik nonrapat' ?></p>
</div>

<form action="<?= esc($action_url) ?>" method="post" data-turbo="true">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div role="alert" class="alert alert-error mb-3 shadow-sm">
            <i data-lucide="triangle-alert" class="h-4 w-4"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-12 gap-3">
        <div class="col-span-12 lg:col-span-8">
            <div class="form-card p-[18px]">
                <div class="form-section-title mb-3 pb-2">Informasi Kegiatan</div>

                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12">
                        <label class="label-text mb-1 block text-sm font-bold" for="judul">
                            Judul kegiatan <span class="text-error">*</span>
                        </label>
                        <input class="input w-full" id="judul" name="judul" type="text" maxlength="200" required
                            value="<?= esc($agenda['judul'] ?? '') ?>"
                            placeholder="Contoh: Penyampaian aspirasi masyarakat di halaman DPRD" />
                    </div>

                    <div class="col-span-12 sm:col-span-6">
                        <label class="label-text mb-1 block text-sm font-bold" for="kategori">
                            Kategori <span class="text-error">*</span>
                        </label>
                        <select class="select w-full" id="kategori" name="kategori" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= esc($category) ?>" <?= $selectedCategory === $category ? 'selected' : '' ?>>
                                    <?= esc($categoryLabels[$category] ?? $category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-span-12 sm:col-span-6">
                        <label class="label-text mb-1 block text-sm font-bold" for="tanggal">
                            Tanggal <span class="text-error">*</span>
                        </label>
                        <input class="input w-full" id="tanggal" name="tanggal" type="date" required
                            value="<?= esc($agenda['tanggal'] ?? date('Y-m-d')) ?>" />
                    </div>

                    <div class="col-span-12 sm:col-span-6">
                        <label class="label-text mb-1 block text-sm font-bold" for="waktu_mulai">
                            Waktu mulai <span class="text-error">*</span>
                        </label>
                        <input class="input w-full" id="waktu_mulai" name="waktu_mulai" type="time" required
                            value="<?= esc(substr((string) ($agenda['waktu_mulai'] ?? '08:00'), 0, 5)) ?>" />
                    </div>

                    <div class="col-span-12 sm:col-span-6">
                        <label class="label-text mb-1 block text-sm font-bold" for="waktu_selesai">Waktu selesai</label>
                        <input class="input w-full" id="waktu_selesai" name="waktu_selesai" type="time"
                            value="<?= esc(substr((string) ($agenda['waktu_selesai'] ?? ''), 0, 5)) ?>" />
                        <p class="mt-1 text-xs text-base-content/55">Boleh dikosongkan jika belum diketahui.</p>
                    </div>

                    <div class="col-span-12">
                        <label class="label-text mb-1 block text-sm font-bold" for="lokasi">
                            Lokasi <span class="text-error">*</span>
                        </label>
                        <input class="input w-full" id="lokasi" name="lokasi" type="text" maxlength="200" required
                            value="<?= esc($agenda['lokasi'] ?? '') ?>"
                            placeholder="Contoh: Halaman Gedung DPRD" />
                    </div>

                    <div class="col-span-12 sm:col-span-7">
                        <label class="label-text mb-1 block text-sm font-bold" for="sumber_informasi">Sumber informasi</label>
                        <input class="input w-full" id="sumber_informasi" name="sumber_informasi" type="text" maxlength="200"
                            value="<?= esc($agenda['sumber_informasi'] ?? '') ?>"
                            placeholder="Contoh: Surat pemberitahuan koordinator lapangan" />
                    </div>

                    <div class="col-span-12 sm:col-span-5">
                        <label class="label-text mb-1 block text-sm font-bold" for="perkiraan_peserta">Perkiraan peserta</label>
                        <input class="input w-full" id="perkiraan_peserta" name="perkiraan_peserta" type="number" min="0" max="1000000"
                            value="<?= esc($agenda['perkiraan_peserta'] ?? '') ?>"
                            placeholder="Contoh: 100" />
                    </div>

                    <div class="col-span-12">
                        <label class="label-text mb-1 block text-sm font-bold" for="keterangan">Keterangan</label>
                        <textarea class="textarea w-full" id="keterangan" name="keterangan" rows="5" maxlength="5000"
                            placeholder="Tambahkan catatan operasional atau informasi yang perlu diketahui publik."><?= esc($agenda['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="form-card p-[18px]">
                <div class="form-section-title mb-3 pb-2">Status dan Publikasi</div>

                <div>
                    <label class="label-text mb-1 block text-sm font-bold" for="status">Status kegiatan</label>
                    <select class="select w-full" id="status" name="status" required>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= esc($status) ?>" <?= $selectedStatus === $status ? 'selected' : '' ?>>
                                <?= esc($statusLabels[$status] ?? $status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-box border border-base-300 bg-base-200 p-3" for="is_publik">
                    <input class="checkbox checkbox-primary checkbox-sm mt-0.5" id="is_publik" name="is_publik" type="checkbox" value="1"
                        <?= (int) ($agenda['is_publik'] ?? 1) === 1 ? 'checked' : '' ?> />
                    <span>
                        <span class="block text-sm font-bold">Tampilkan kepada publik</span>
                        <span class="mt-1 block text-xs leading-5 text-base-content/55">Jika dinonaktifkan, data hanya tersimpan sebagai draf pada panel admin.</span>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <div class="mt-4 flex gap-2">
        <a href="<?= base_url('admin/agenda-umum') ?>" class="btn btn-outline btn-md flex-1 sm:btn-sm sm:flex-none">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Batal
        </a>
        <button type="submit" class="btn btn-primary btn-md flex-1 sm:btn-sm sm:flex-none">
            <i data-lucide="check" class="h-4 w-4"></i>
            <?= $isEdit ? 'Simpan Perubahan' : 'Tambah Jadwal Umum' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
