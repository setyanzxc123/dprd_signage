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

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?= esc($pageTitle) ?></h1>
    </div>
    <a href="<?= base_url('admin/anggota') ?>" class="py-2 px-3 inline-flex items-center gap-x-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition sm:w-auto">
        <i data-lucide="arrow-left" class="size-4"></i>
        Kembali ke Anggota DPRD
    </a>
</div>

<form action="<?= esc($action_url) ?>" method="POST" id="anggota-form" class="member-form min-w-0">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300 flex items-center gap-3">
            <i data-lucide="triangle-alert" class="size-5 shrink-0 text-rose-500"></i>
            <span class="text-sm font-medium"><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl dark:bg-slate-900 dark:border-slate-800 p-5 sm:p-6 space-y-6 max-w-4xl">
        <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
            <i data-lucide="user-round" class="size-4 text-emerald-500"></i>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Data Anggota</h2>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="name">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <input type="text" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="name" name="name"
                    value="<?= esc($member['name'] ?? '') ?>" placeholder="Masukkan nama lengkap"
                    required />
            </div>

            <div class="col-span-12 md:col-span-6">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="jabatan">Jabatan</label>
                <input type="text" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="jabatan" name="jabatan"
                    value="<?= esc($member['jabatan'] ?? '') ?>" placeholder="Masukkan jabatan (misal: Ketua Komisi I)" />
            </div>

            <div class="col-span-12 md:col-span-6">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="fraksi">
                    Fraksi <span class="text-rose-500">*</span>
                </label>
                <select class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="fraksi" name="fraksi" required>
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
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="komisi">Komisi</label>
                <select class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="komisi" name="komisi">
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
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="status">Status</label>
                <select class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="status" name="aktif">
                    <option value="1" <?= ($member['aktif'] ?? 1) ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= !($member['aktif'] ?? 1) ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
            <div class="flex items-center gap-2">
                <i data-lucide="message-square" class="size-4 text-emerald-500"></i>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Kontak WhatsApp</h3>
            </div>

            <div class="max-w-md">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="no_wa">
                    Nomor WhatsApp <span class="text-rose-500">*</span>
                </label>
                <div class="flex rounded-xl shadow-xs">
                    <span class="px-3.5 inline-flex items-center min-w-fit rounded-s-xl border border-e-0 border-slate-200 bg-slate-50 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400">
                        +62
                    </span>
                    <input type="text" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-e-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="no_wa" name="no_wa"
                        value="<?= esc($whatsAppValue) ?>" placeholder="8123456789"
                        inputmode="numeric" pattern="8[0-9]{7,11}" maxlength="12"
                        title="Gunakan maksimal 12 digit setelah +62." required />
                </div>
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Masukkan nomor tanpa awalan 0 atau 62 (contoh: 8123456789).</p>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3 max-w-4xl">
        <a href="<?= base_url('admin/anggota') ?>" class="py-2 px-3.5 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition">
            <i data-lucide="arrow-left" class="size-4"></i>
            Batal
        </a>
        <button type="submit" class="py-2 px-4 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer">
            <i data-lucide="check" class="size-4"></i>
            <?= $member ? 'Simpan Perubahan' : 'Tambah Anggota' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
