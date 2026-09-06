<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-5 flex items-center justify-between">
    <div>
        <nav class="flex items-center gap-x-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
            <a href="<?= base_url('admin/dashboard') ?>" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Dashboard</a>
            <i data-lucide="chevron-right" class="size-3 text-slate-400"></i>
            <a href="<?= base_url('admin/ruangan') ?>" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Ruangan Rapat</a>
            <i data-lucide="chevron-right" class="size-3 text-slate-400"></i>
            <span class="font-medium text-slate-800 dark:text-slate-200"><?= $room ? 'Edit Ruangan' : 'Tambah Ruangan' ?></span>
        </nav>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?= esc($pageTitle) ?></h1>
    </div>
</div>

<form action="<?= esc($action_url) ?>" method="POST" class="room-form">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300 flex items-center gap-3">
            <i data-lucide="triangle-alert" class="size-5 shrink-0 text-rose-500"></i>
            <span class="text-sm font-medium"><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-slate-200 shadow-xs rounded-2xl dark:bg-slate-900 dark:border-slate-800 p-5 sm:p-6 space-y-6 max-w-4xl">
        <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
            <i data-lucide="door-open" class="size-4 text-emerald-500"></i>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Data Ruangan</h2>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 lg:col-span-8">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="name">
                    Nama Ruangan <span class="text-rose-500">*</span>
                </label>
                <input type="text" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="name" name="name"
                    value="<?= esc($room['name'] ?? '') ?>" placeholder="Masukkan nama ruangan"
                    required />
            </div>

            <div class="col-span-12 lg:col-span-4">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="kapasitas">
                    Kapasitas <span class="text-rose-500">*</span>
                </label>
                <div class="flex rounded-xl shadow-xs">
                    <input type="number" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-s-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="kapasitas" name="kapasitas"
                        value="<?= esc($room['kapasitas'] ?? '') ?>" placeholder="0" min="1" required />
                    <span class="px-3.5 inline-flex items-center min-w-fit rounded-e-xl border border-s-0 border-slate-200 bg-slate-50 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 font-mono">
                        orang
                    </span>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-8">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="keterangan">Keterangan</label>
                <textarea class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200 resize-none min-h-20" id="keterangan" name="keterangan" rows="2"
                    placeholder="Masukkan keterangan ruangan (fasilitas, audio, AC, dll)"><?= esc($room['keterangan'] ?? '') ?></textarea>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="tersedia">Status Ketersediaan</label>
                <select class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" id="tersedia" name="tersedia">
                    <option value="1" <?= ($room['tersedia'] ?? 1) ? 'selected' : '' ?>>
                        Tersedia
                    </option>
                    <option value="0" <?= !($room['tersedia'] ?? 1) ? 'selected' : '' ?>>
                        Tidak Tersedia
                    </option>
                </select>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-6 flex items-center justify-end gap-3 sticky bottom-4 z-10 p-3 rounded-2xl bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800 shadow-lg max-w-4xl">
        <a href="<?= base_url('admin/ruangan') ?>" class="py-2 px-3.5 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition">
            <i data-lucide="arrow-left" class="size-4"></i>
            Batal
        </a>
        <button type="submit" class="py-2 px-4 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition cursor-pointer">
            <i data-lucide="check" class="size-4"></i>
            <?= $room ? 'Simpan Perubahan' : 'Tambah Ruangan' ?>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
