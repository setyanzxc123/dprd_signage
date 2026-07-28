<?= $this->extend('admin/layouts/main') ?>



<?= $this->section('content') ?>

<?php
$whatsAppValue = preg_replace('/\D/', '', (string) ($member['no_wa'] ?? '')) ?? '';
if (str_starts_with($whatsAppValue, '62')) {
    $whatsAppValue = substr($whatsAppValue, 2);
} elseif (str_starts_with($whatsAppValue, '0')) {
    $whatsAppValue = substr($whatsAppValue, 1);
}
?>

<div class="page-header">
    <h1 class="page-title">
        <?= esc($pageTitle) ?>
    </h1>
</div>

<form action="<?= esc($action_url) ?>" method="POST" id="anggota-form" class="member-form" data-turbo="true">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="alert alert-error shadow-sm mb-3" role="alert">
            <i data-lucide="triangle-alert" class="w-4 h-4"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <section class="card card-border bg-base-100 shadow-sm">
        <div class="card-body gap-5 p-4 sm:p-5">
            <h2 class="card-title text-base">
                <i data-lucide="user-round" class="h-5 w-5 text-primary"></i>
                Data Anggota
            </h2>

            <fieldset class="fieldset gap-3">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12">
                        <label class="label py-1 font-semibold" for="name">
                            Nama Lengkap <span class="text-error">*</span>
                        </label>
                        <input type="text" class="input w-full" id="name" name="name"
                            value="<?= esc($member['name'] ?? '') ?>" placeholder="Masukkan nama lengkap"
                            required />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="label py-1 font-semibold" for="jabatan">Jabatan</label>
                        <input type="text" class="input w-full" id="jabatan" name="jabatan"
                            value="<?= esc($member['jabatan'] ?? '') ?>" placeholder="Masukkan jabatan" />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="label py-1 font-semibold" for="fraksi">
                            Fraksi <span class="text-error">*</span>
                        </label>
                        <select class="select w-full" id="fraksi" name="fraksi" required>
                            <option value="">Pilih fraksi</option>
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
                        <label class="label py-1 font-semibold" for="komisi">Komisi</label>
                        <select class="select w-full" id="komisi" name="komisi">
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
                        <label class="label py-1 font-semibold" for="status">Status</label>
                        <select class="select w-full" id="status" name="aktif">
                            <option value="1" <?= ($member['aktif'] ?? 1) ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= !($member['aktif'] ?? 1) ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="fieldset gap-3 border-t border-base-300 pt-4">
                <legend class="fieldset-legend">Kontak WhatsApp</legend>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="label py-1 font-semibold" for="no_wa">
                            Nomor WhatsApp <span class="text-error">*</span>
                        </label>
                        <div class="join w-full">
                            <span class="join-item bg-base-200 border border-base-300 border-r-0 px-3 flex items-center text-xs font-semibold font-mono">+62</span>
                            <input type="text" class="input join-item flex-1 w-full" id="no_wa" name="no_wa"
                                value="<?= esc($whatsAppValue) ?>" placeholder="8123456789"
                                inputmode="numeric" pattern="8[0-9]{7,11}" maxlength="12"
                                title="Gunakan maksimal 12 digit setelah +62." required />
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>
    </section>

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
