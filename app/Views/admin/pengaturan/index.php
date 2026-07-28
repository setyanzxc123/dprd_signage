<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header settings-header">
    <div>
        <h1 class="page-title">Pengaturan Sistem</h1>
        <p class="page-subtitle">Kelola tampilan layar TV dan konten signage.</p>
    </div>
</div>

<form action="<?= base_url('admin/pengaturan/save') ?>" method="POST" enctype="multipart/form-data"
    class="settings-form" id="settings-form"
    data-redirect-url="<?= base_url('admin/pengaturan') ?>">
    <?= csrf_field() ?>

    <div class="settings-stack">

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <!-- CARD 1: Pengaturan Signage                  -->
        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div class="form-card stg-card">
            <div class="stg-card-header">
                <div class="stg-card-icon blue">
                    <i data-lucide="tv"></i>
                </div>
                <div>
                    <h2 class="stg-card-title">Pengaturan Signage</h2>
                    <p class="stg-card-sub">Tema, media, dan teks berjalan layar TV</p>
                </div>
            </div>

            <div class="stg-signage-body">
                <div class="stg-signage-choice-row">
                    <div class="stg-section">
                        <div class="stg-section-label">Tema Layar</div>
                        <div class="stg-choice-grid">
                            <label class="stg-choice-option">
                                <input type="radio" name="tema_signage" value="dark" class="radio radio-primary radio-sm"
                                    <?= $settings['tema_signage'] === 'dark' ? 'checked' : '' ?> />
                                <span class="stg-choice-title">Dark</span>
                            </label>
                            <label class="stg-choice-option">
                                <input type="radio" name="tema_signage" value="light" class="radio radio-primary radio-sm"
                                    <?= $settings['tema_signage'] === 'light' ? 'checked' : '' ?> />
                                <span class="stg-choice-title">Light</span>
                            </label>
                        </div>
                    </div>

                    <div class="stg-section">
                        <div class="stg-section-label">Media Tampilan</div>
                        <div class="stg-choice-grid">
                            <label class="stg-choice-option">
                                <input type="radio" name="media_mode" value="video" class="radio radio-primary radio-sm"
                                    <?= $settings['media_mode'] === 'video' ? 'checked' : '' ?> />
                                <span class="stg-choice-title">Video</span>
                            </label>
                            <label class="stg-choice-option">
                                <input type="radio" name="media_mode" value="image" class="radio radio-primary radio-sm"
                                    <?= $settings['media_mode'] === 'image' ? 'checked' : '' ?> />
                                <span class="stg-choice-title">Gambar</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="stg-section stg-media-section">
                    <div class="stg-media-grid">
                        <div class="stg-media-upload stg-media-panel">
                            <label class="label-text font-bold text-sm mb-1 block" for="media_file">Upload File</label>
                            <input type="file" class="file-input file-input-bordered file-input-sm w-full" id="media_file" name="media_file"
                                accept="video/mp4,video/webm,image/jpeg,image/png,image/webp" />
                        </div>

                        <div class="stg-media-status stg-media-panel">
                            <?php if (!empty($settings['media_file'])): ?>
                                <div class="stg-media-file-card" role="status" title="<?= esc(basename($settings['media_file'])) ?>">
                                    <?= esc(basename($settings['media_file'])) ?>
                                </div>
                            <?php else: ?>
                                <div class="stg-media-file-card is-empty" role="status">
                                    Belum ada file
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="stg-section stg-running-section">
                    <div class="stg-running-head">
                        <div class="stg-section-label mb-0">Running Text</div>
                        <div class="flex items-center gap-2 mb-0">
                            <input class="toggle toggle-primary toggle-sm" type="checkbox" role="switch" id="running_text_aktif"
                                name="running_text_aktif" value="1" <?= $settings['running_text_aktif'] ? 'checked' : '' ?> />
                            <label class="label-text font-semibold cursor-pointer" for="running_text_aktif">Aktifkan</label>
                        </div>
                    </div>
                    <textarea class="textarea textarea-bordered w-full mt-2" id="running_text" name="running_text" rows="3"
                        placeholder="Contoh: Selamat datang di Gedung DPRD Provinsi Sulawesi Tengah."><?= esc($settings['running_text']) ?></textarea>

                    <div class="stg-running-preview mt-2">
                        <div class="stg-running-label"><i data-lucide="eye" class="mr-1"></i>Pratinjau</div>
                        <div class="stg-running-track" id="preview-track">
                            <span id="preview-text"><?= esc($settings['running_text']) ?: 'Teks berjalan akan tampil di sini...' ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <?php if (false): ?>
    <!-- Seksi BMKG (hidden) -->
    <div class="form-card stg-card">
        <div class="stg-card-header">
            <div class="stg-card-icon blue"><i data-lucide="cloud-sun"></i></div>
            <div>
                <h2 class="stg-card-title">Integrasi Cuaca BMKG</h2>
                <p class="stg-card-sub">Data cuaca otomatis dari BMKG</p>
            </div>
        </div>
        <div class="stg-section">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 lg:col-span-8">
                    <label class="label-text font-semibold mb-1 block" for="bmkg_adm4">Kode Wilayah (ADM4)</label>
                    <input type="text" class="input input-bordered w-full font-mono" id="bmkg_adm4" name="bmkg_adm4"
                        value="<?= esc(env('BMKG_ADM4') ?: '72.71.01.1004') ?>"
                        placeholder="72.71.01.1004" pattern="\d{2}\.\d{2}\.\d{2}\.\d{4}"
                        <?= env('BMKG_ADM4') ? 'readonly' : '' ?> />
                    <div class="text-xs text-base-content/60 mt-1">
                        Format: <code>PP.KK.KC.LLLL</code><br/>
                        Lokasi: <strong id="bmkg-resolved-location">Memuat...</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Save bar -->
    <div class="settings-save-bar">
        <div class="settings-upload-progress" id="settings-upload-progress" hidden aria-live="polite">
            <div class="settings-upload-row">
                <span class="loading loading-spinner loading-sm" aria-hidden="true"></span>
                <span id="settings-upload-status">Menyiapkan upload...</span>
                <strong id="settings-upload-percent">0%</strong>
            </div>
            <progress class="progress progress-primary w-full" id="settings-upload-bar" value="0" max="100"></progress>
        </div>

        <button type="submit" class="btn btn-primary btn-md shadow-md" id="settings-submit-button">
            <span class="loading loading-spinner loading-sm" id="settings-submit-spinner" hidden aria-hidden="true"></span>
            <i data-lucide="save" class="w-4 h-4 mr-1" id="settings-submit-icon"></i>
            <span id="settings-submit-label">Simpan Semua Pengaturan</span>
        </button>
    </div>

</form>

<?= $this->endSection() ?>
