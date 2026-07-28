<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1 class="page-title">Pengaturan Sistem</h1>
</div>

<form action="<?= base_url('admin/pengaturan/save') ?>" method="post" enctype="multipart/form-data"
    class="space-y-5" id="settings-form" data-redirect-url="<?= base_url('admin/pengaturan') ?>">
    <?= csrf_field() ?>

    <section class="card card-border bg-base-100 shadow-sm">
        <div class="card-body gap-5 p-4 sm:p-5">
            <h2 class="card-title text-base">
                <i data-lucide="tv" class="h-5 w-5 text-primary"></i>
                Pengaturan Signage
            </h2>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <fieldset class="fieldset rounded-box border border-base-300 bg-base-200 p-4">
                    <legend class="fieldset-legend">Tema Layar</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="label cursor-pointer justify-start gap-3 rounded-field border border-base-300 bg-base-100 px-3 py-2">
                            <input type="radio" name="tema_signage" value="dark" class="radio radio-primary radio-sm"
                                <?= $settings['tema_signage'] === 'dark' ? 'checked' : '' ?> />
                            <span class="font-semibold">Dark</span>
                        </label>
                        <label class="label cursor-pointer justify-start gap-3 rounded-field border border-base-300 bg-base-100 px-3 py-2">
                            <input type="radio" name="tema_signage" value="light" class="radio radio-primary radio-sm"
                                <?= $settings['tema_signage'] === 'light' ? 'checked' : '' ?> />
                            <span class="font-semibold">Light</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="fieldset rounded-box border border-base-300 bg-base-200 p-4">
                    <legend class="fieldset-legend">Media Tampilan</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="label cursor-pointer justify-start gap-3 rounded-field border border-base-300 bg-base-100 px-3 py-2">
                            <input type="radio" name="media_mode" value="video" class="radio radio-primary radio-sm"
                                <?= $settings['media_mode'] === 'video' ? 'checked' : '' ?> />
                            <span class="font-semibold">Video</span>
                        </label>
                        <label class="label cursor-pointer justify-start gap-3 rounded-field border border-base-300 bg-base-100 px-3 py-2">
                            <input type="radio" name="media_mode" value="image" class="radio radio-primary radio-sm"
                                <?= $settings['media_mode'] === 'image' ? 'checked' : '' ?> />
                            <span class="font-semibold">Gambar</span>
                        </label>
                    </div>
                </fieldset>
            </div>

            <div class="grid grid-cols-12 gap-4 border-t border-base-300 pt-4">
                <fieldset class="fieldset col-span-12 min-w-0 lg:col-span-8">
                    <legend class="fieldset-legend">Upload Media</legend>
                    <input type="file" class="file-input file-input-sm w-full min-w-0" id="media_file" name="media_file"
                        accept="video/mp4,video/webm,image/jpeg,image/png,image/webp" />
                    <p class="label">MP4, WebM, JPG, PNG, atau WebP. Maksimal 50 MB.</p>
                </fieldset>

                <fieldset class="fieldset col-span-12 min-w-0 lg:col-span-4">
                    <legend class="fieldset-legend">File Aktif</legend>
                    <?php if (! empty($settings['media_file'])): ?>
                        <div class="alert alert-info min-h-8 overflow-hidden py-2 text-sm" role="status"
                            title="<?= esc(basename($settings['media_file'])) ?>">
                            <i data-lucide="file-check-2" class="h-4 w-4 shrink-0"></i>
                            <span class="truncate"><?= esc(basename($settings['media_file'])) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="alert min-h-8 py-2 text-sm" role="status">
                            <i data-lucide="file-x-2" class="h-4 w-4 shrink-0"></i>
                            <span>Belum ada file</span>
                        </div>
                    <?php endif; ?>
                </fieldset>
            </div>

            <fieldset class="fieldset rounded-box border border-base-300 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span class="font-bold">Running Text</span>
                    <label class="label cursor-pointer gap-2">
                        <span class="font-semibold">Aktif</span>
                        <input class="toggle toggle-primary toggle-sm" type="checkbox" role="switch" id="running_text_aktif"
                            name="running_text_aktif" value="1" <?= $settings['running_text_aktif'] ? 'checked' : '' ?> />
                    </label>
                </div>

                <textarea class="textarea mt-2 min-h-16 w-full" id="running_text" name="running_text" rows="2"
                    placeholder="Contoh: Selamat datang di Gedung DPRD Provinsi Sulawesi Tengah."><?= esc($settings['running_text']) ?></textarea>

                <div class="mt-2 overflow-hidden rounded-box bg-neutral p-3 text-neutral-content">
                    <div class="mb-2 flex items-center gap-1 text-xs font-bold uppercase tracking-wider opacity-60">
                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                        Pratinjau
                    </div>
                    <div class="settings-running-track" id="preview-track">
                        <span id="preview-text"><?= esc($settings['running_text']) ?: 'Teks berjalan akan tampil di sini...' ?></span>
                    </div>
                </div>
            </fieldset>
        </div>
    </section>

    <div class="form-actions-sticky mt-5 flex flex-col gap-3 sm:items-end">
        <div class="alert alert-info w-full max-w-xl" id="settings-upload-progress" hidden aria-live="polite">
            <div class="w-full">
                <div class="mb-2 grid grid-cols-[auto_1fr_auto] items-center gap-2 text-sm font-bold">
                    <span class="loading loading-spinner loading-sm" aria-hidden="true"></span>
                    <span id="settings-upload-status">Menyiapkan upload...</span>
                    <strong id="settings-upload-percent">0%</strong>
                </div>
                <progress class="progress progress-primary w-full" id="settings-upload-bar" value="0" max="100"></progress>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-full gap-1 sm:btn-sm sm:w-auto" id="settings-submit-button">
            <span class="loading loading-spinner loading-sm" id="settings-submit-spinner" hidden aria-hidden="true"></span>
            <i data-lucide="save" class="h-4 w-4" id="settings-submit-icon"></i>
            <span id="settings-submit-label">Simpan Pengaturan</span>
        </button>
    </div>
</form>

<?= $this->endSection() ?>
