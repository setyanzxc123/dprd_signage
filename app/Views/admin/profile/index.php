<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">Profil Admin</h1>
</div>

<form action="<?= base_url('admin/profile/update') ?>" method="post" class="space-y-5">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300" role="alert">
            <i data-lucide="triangle-alert" class="size-5 shrink-0 text-rose-500"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <section class="bg-white border border-slate-200 rounded-xl shadow-sm dark:bg-slate-900 dark:border-slate-800 min-w-0 max-w-full">
        <div class="p-4 sm:p-5 min-w-0 space-y-5">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <i data-lucide="user-round" class="size-5 text-emerald-600 dark:text-emerald-400"></i>
                Informasi Profil
            </h2>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Nama Admin <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200"
                        value="<?= esc($form_name) ?>" minlength="3" maxlength="100"
                        autocomplete="name" required />
                </div>

                <div>
                    <label for="username" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Username</label>
                    <input type="text" id="username" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm bg-slate-50 text-slate-500 cursor-not-allowed dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400" value="<?= esc($user['username']) ?>" disabled />
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl shadow-sm dark:bg-slate-900 dark:border-slate-800 min-w-0 max-w-full">
        <div class="p-4 sm:p-5 min-w-0 space-y-5">
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="shield-check" class="size-5 text-emerald-600 dark:text-emerald-400"></i>
                    Keamanan Akun
                </h2>
                <span class="inline-flex items-center gap-x-1 py-0.5 px-2 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">Opsional</span>
            </div>

            <div>
                <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Password Saat Ini</label>
                <input type="password" id="current_password" name="current_password" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200"
                    autocomplete="current-password" placeholder="Password saat ini" />
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                    <label for="new_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200"
                        autocomplete="new-password" minlength="8" maxlength="72"
                        placeholder="Minimal 8 karakter" />
                </div>

                <div>
                    <label for="new_password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Konfirmasi Password Baru</label>
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200"
                        autocomplete="new-password" minlength="8" maxlength="72"
                        placeholder="Ulangi password baru" />
                </div>
            </div>
        </div>
    </section>

    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a href="<?= base_url('admin/dashboard') ?>" class="py-2.5 px-4 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-xl border border-slate-200 bg-white text-slate-800 shadow-xs hover:bg-slate-50 focus:outline-hidden dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:bg-slate-700 w-full sm:w-auto">Batal</a>
        <button type="submit" class="py-2.5 px-4 inline-flex items-center justify-center gap-x-2 text-sm font-semibold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-hidden focus:bg-emerald-700 w-full sm:w-auto shadow-xs">
            <i data-lucide="save" class="size-4"></i>
            Simpan Profil
        </button>
    </div>
</form>

<?= $this->endSection() ?>
