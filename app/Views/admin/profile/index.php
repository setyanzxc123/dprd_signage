<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">Profil Admin</h1>
</div>

<form action="<?= base_url('admin/profile/update') ?>" method="post" class="space-y-5">
    <?= csrf_field() ?>

    <?php if (! empty($form_error)): ?>
        <div class="alert alert-error alert-soft" role="alert">
            <i data-lucide="triangle-alert" class="h-5 w-5"></i>
            <span><?= esc($form_error) ?></span>
        </div>
    <?php endif; ?>

    <section class="card card-border bg-base-100 shadow-sm">
        <div class="card-body gap-5 p-4 sm:p-5">
            <h2 class="card-title text-base">
                <i data-lucide="user-round" class="h-5 w-5"></i>
                Informasi Profil
            </h2>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Nama Admin</legend>
                    <input type="text" name="name" class="input validator w-full"
                        value="<?= esc($form_name) ?>" minlength="3" maxlength="100"
                        autocomplete="name" required />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Username</legend>
                    <input type="text" class="input w-full" value="<?= esc($user['username']) ?>" disabled />
                </fieldset>
            </div>
        </div>
    </section>

    <section class="card card-border bg-base-100 shadow-sm">
        <div class="card-body gap-5 p-4 sm:p-5">
            <h2 class="card-title text-base">
                <i data-lucide="shield-check" class="h-5 w-5"></i>
                Keamanan Akun
                <span class="badge badge-sm font-medium">Opsional</span>
            </h2>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Password Saat Ini</legend>
                <input type="password" name="current_password" class="input w-full"
                    autocomplete="current-password" placeholder="Password saat ini" />
            </fieldset>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Password Baru</legend>
                    <input type="password" name="new_password" class="input w-full"
                        autocomplete="new-password" minlength="8" maxlength="72"
                        placeholder="Minimal 8 karakter" />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Konfirmasi Password Baru</legend>
                    <input type="password" name="new_password_confirmation" class="input w-full"
                        autocomplete="new-password" minlength="8" maxlength="72"
                        placeholder="Ulangi password baru" />
                </fieldset>
            </div>
        </div>
    </section>

    <div class="form-actions-sticky mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-ghost w-full sm:btn-sm sm:w-auto">Batal</a>
        <button type="submit" class="btn btn-primary w-full gap-1 sm:btn-sm sm:w-auto">
            <i data-lucide="save" class="h-4 w-4"></i>
            Simpan Profil
        </button>
    </div>
</form>

<?= $this->endSection() ?>
