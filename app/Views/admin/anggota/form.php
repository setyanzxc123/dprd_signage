<?= $this->extend('admin/layouts/main') ?>



<?= $this->section('content') ?>


<div class="page-header">
    <h1 class="page-title">
        <?= esc($pageTitle) ?>
    </h1>
    <p class="page-subtitle">
        <?= $member ? 'Perbarui data anggota DPRD' : 'Tambahkan anggota baru ke sistem' ?>
    </p>
</div>

<form action="<?= esc($action_url) ?>" method="POST" id="anggota-form" class="member-form" data-turbo="true">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="alert alert-error shadow-sm mb-3" role="alert">
            <i data-lucide="triangle-alert" class="w-4 h-4"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-12 gap-3">

        <!-- Kolom kiri: Data Utama -->
        <div class="col-span-12">
            <div class="form-card p-[18px]">

                <div class="form-section-title mb-3 pb-2">Informasi Anggota</div>

                <div class="grid grid-cols-12 gap-3">

                    <div class="col-span-12">
                        <label class="label-text font-bold text-sm mb-1 block" for="name">
                            Nama Lengkap <span class="text-error">*</span>
                        </label>
                        <input type="text" class="input input-bordered w-full" id="name" name="name"
                            value="<?= esc($member['name'] ?? '') ?>" placeholder="Contoh: H. Ahmad Fauzi, S.H., M.M."
                            required />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="label-text font-bold text-sm mb-1 block" for="jabatan">Jabatan</label>
                        <input type="text" class="input input-bordered w-full" id="jabatan" name="jabatan"
                            value="<?= esc($member['jabatan'] ?? '') ?>" placeholder="Contoh: Ketua Komisi III" />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="label-text font-bold text-sm mb-1 block" for="fraksi">
                            Fraksi <span class="text-error">*</span>
                        </label>
                        <select class="select select-bordered w-full" id="fraksi" name="fraksi" required>
                            <option value="">-- Pilih Fraksi --</option>
                            <?php foreach ($fraksi_list as $f):
                                $selected = ($member['fraksi'] ?? '') === $f ? 'selected' : '';
                            ?>
                                <option value="<?= $f ?>" <?= $selected ?>>
                                    <?= $f ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="label-text font-bold text-sm mb-1 block" for="komisi">Komisi</label>
                        <select class="select select-bordered w-full" id="komisi" name="komisi">
                            <option value="">Tidak dalam komisi</option>
                            <?php foreach ($komisi_list as $k):
                                $selected = ($member['komisi'] ?? '') === $k ? 'selected' : '';
                            ?>
                                <option value="<?= $k ?>" <?= $selected ?>>
                                    <?= $k ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="label-text font-bold text-sm mb-1 block" for="status">Status</label>
                        <select class="select select-bordered w-full" id="status" name="aktif">
                            <option value="1" <?= ($member['aktif'] ?? 1) ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= !($member['aktif'] ?? 1) ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="form-card mt-2 p-[18px]">
                <div class="form-section-title mb-3 pb-2">Kontak WhatsApp</div>

                <div class="grid grid-cols-12 gap-3 items-start">
                    <div class="col-span-12 md:col-span-6">
                        <label class="label-text font-bold text-sm mb-1 block" for="no_wa">
                            Nomor WhatsApp <span class="text-error">*</span>
                        </label>
                        <div class="join w-full">
                            <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold font-mono">+62</span>
                            <input type="text" class="input input-bordered join-item flex-1 w-full" id="no_wa" name="no_wa"
                                value="<?= esc($member['no_wa'] ?? '') ?>" placeholder="8123456789"
                                inputmode="numeric" pattern="8[0-9]{7,12}" title="Gunakan format 8123456789 tanpa 0 di depan." required />
                        </div>
                        <div class="label-text-alt text-base-content/60 mt-1">Format tanpa 0 di depan. Contoh: 8123456789</div>
                    </div>

                    <?php if ($member): ?>
                        <div class="col-span-12 md:col-span-6">
                            <div class="alert alert-info py-2 px-3 text-xs mb-0 flex gap-2">
                                <i data-lucide="info" class="w-4 h-4"></i>
                                <span>Gunakan nomor WhatsApp aktif milik anggota.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-card mt-2 p-[18px]">
                <div class="form-section-title mb-3 pb-2">Akun Login Anggota</div>

                <div class="grid grid-cols-12 gap-3 items-start">
                    <div class="col-span-12">
                        <div class="alert alert-info text-xs">
                            <i data-lucide="shield-check" class="w-4 h-4 shrink-0"></i>
                            <span>Setiap anggota berstatus aktif dapat masuk menggunakan kode OTP enam digit yang dikirim ke nomor WhatsApp terdaftar.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi -->
    <div class="form-actions-sticky">
        <a href="<?= base_url('admin/anggota') ?>" class="btn btn-outline sm:btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>Batal
        </a>
        <button type="submit" class="btn btn-primary sm:btn-sm">
            <i data-lucide="check" class="w-4 h-4"></i>
            <?= $member ? 'Simpan Perubahan' : 'Tambah Anggota' ?>
        </button>
    </div>

</form>

<?= $this->endSection() ?>
