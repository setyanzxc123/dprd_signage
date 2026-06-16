<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header settings-header flex items-center justify-between">
    <div>
        <h1 class="page-title">Pengaturan Sistem</h1>
        <p class="page-subtitle">Kelola tampilan layar TV dan notifikasi WhatsApp.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= base_url('signage') ?>" target="_blank"
            class="btn btn-sm btn-outline btn-primary">
            <i data-lucide="monitor" class="w-4 h-4 mr-1"></i>Preview Signage
        </a>
    </div>
</div>

<form action="<?= base_url('admin/pengaturan/save') ?>" method="POST" enctype="multipart/form-data" class="settings-form">
    <?= csrf_field() ?>

    <div class="settings-grid-2col">

        <!-- ═══════════════════════════════════════════ -->
        <!-- CARD 1: Pengaturan Signage                  -->
        <!-- ═══════════════════════════════════════════ -->
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

            <!-- Sub-section: Tema -->
            <div class="stg-section">
                <div class="stg-section-label">Tema Layar</div>
                <div class="stg-tema-grid">
                    <div class="stg-tema-card <?= $settings['tema_signage'] === 'dark' ? 'selected' : '' ?>"
                        id="tema-dark" onclick="selectTema('dark')">
                        <input type="radio" name="tema_signage" value="dark" id="radio-tema-dark"
                            <?= $settings['tema_signage'] === 'dark' ? 'checked' : '' ?> class="hidden" />
                        <div class="stg-tema-icon dark"><i data-lucide="moon-star"></i></div>
                        <span class="stg-tema-name">Dark</span>
                        <span class="stg-tema-desc">Gelap</span>
                    </div>
                    <div class="stg-tema-card <?= $settings['tema_signage'] === 'light' ? 'selected' : '' ?>"
                        id="tema-light" onclick="selectTema('light')">
                        <input type="radio" name="tema_signage" value="light" id="radio-tema-light"
                            <?= $settings['tema_signage'] === 'light' ? 'checked' : '' ?> class="hidden" />
                        <div class="stg-tema-icon light"><i data-lucide="sun"></i></div>
                        <span class="stg-tema-name">Light</span>
                        <span class="stg-tema-desc">Terang</span>
                    </div>
                </div>
            </div>

            <div class="stg-divider"></div>

            <!-- Sub-section: Media -->
            <div class="stg-section">
                <div class="stg-section-label">Media</div>
                <div class="stg-tema-grid mb-3">
                    <div class="stg-tema-card <?= $settings['media_mode'] === 'video' ? 'selected' : '' ?>"
                        id="mode-video" onclick="selectMode('video')">
                        <input type="radio" name="media_mode" value="video" id="radio-video"
                            <?= $settings['media_mode'] === 'video' ? 'checked' : '' ?> class="hidden" />
                        <div class="stg-tema-icon media"><i data-lucide="circle-play"></i></div>
                        <span class="stg-tema-name">Video</span>
                        <span class="stg-tema-desc">MP4, WebM</span>
                    </div>
                    <div class="stg-tema-card <?= $settings['media_mode'] === 'image' ? 'selected' : '' ?>"
                        id="mode-image" onclick="selectMode('image')">
                        <input type="radio" name="media_mode" value="image" id="radio-image"
                            <?= $settings['media_mode'] === 'image' ? 'checked' : '' ?> class="hidden" />
                        <div class="stg-tema-icon media"><i data-lucide="image"></i></div>
                        <span class="stg-tema-name">Gambar</span>
                        <span class="stg-tema-desc">JPG, PNG, WebP</span>
                    </div>
                </div>

                <label class="label-text font-bold text-sm mb-1 block" for="media_file">Upload File</label>
                <input type="file" class="file-input file-input-bordered file-input-sm w-full" id="media_file" name="media_file"
                    accept="video/mp4,video/webm,image/jpeg,image/png,image/webp" />
                <div class="label-text-alt text-base-content/60 mt-1 block">Maks. 50MB. File baru menggantikan file aktif.</div>

                <?php if (!empty($settings['media_file'])): ?>
                    <div class="alert alert-sm bg-base-200 border-base-300 py-2 px-3 mt-2">
                        <i data-lucide="file-video" class="w-4 h-4 mr-1"></i>
                        <span>Aktif: <strong><?= esc(basename($settings['media_file'])) ?></strong></span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-sm alert-warning py-2 px-3 mt-2">
                        <i data-lucide="triangle-alert" class="w-4 h-4 mr-1"></i>
                        <span>Belum ada file media.</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="stg-divider"></div>

            <!-- Sub-section: Running Text -->
            <div class="stg-section">
                <div class="flex items-center justify-between">
                    <div class="stg-section-label mb-0">Running Text</div>
                    <div class="flex items-center gap-2 mb-0">
                        <input class="toggle toggle-primary toggle-sm" type="checkbox" role="switch" id="running_text_aktif"
                            name="running_text_aktif" value="1" <?= $settings['running_text_aktif'] ? 'checked' : '' ?> />
                        <label class="label-text font-semibold cursor-pointer" for="running_text_aktif">Aktifkan</label>
                    </div>
                </div>
                <textarea class="textarea textarea-bordered w-full mt-2" id="running_text" name="running_text" rows="2"
                    placeholder="Contoh: Selamat datang di Gedung DPRD Provinsi Sulawesi Tengah."><?= esc($settings['running_text']) ?></textarea>

                <div class="stg-running-preview mt-2">
                    <div class="stg-running-label"><i data-lucide="eye" class="mr-1"></i>Pratinjau</div>
                    <div class="stg-running-track" id="preview-track">
                        <span id="preview-text"><?= esc($settings['running_text']) ?: 'Teks berjalan akan tampil di sini...' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- CARD 2: Notifikasi WhatsApp                 -->
        <!-- ═══════════════════════════════════════════ -->
        <?php
            $waTemplateValue = old('wa_template_reminder', $settings['wa_template_reminder'] ?? '');
            $waSenderNameValue = old('wa_sender_name', $settings['wa_sender_name'] ?? 'Sekretariat DPRD');
        ?>
        <div class="form-card stg-card" id="wa-notif-card">
            <div class="stg-card-header">
                <div class="stg-card-icon green">
                    <i data-lucide="message-circle"></i>
                </div>
                <div class="flex-1">
                    <h2 class="stg-card-title">Notifikasi WhatsApp</h2>
                    <p class="stg-card-sub">Pengaturan reminder otomatis peserta rapat</p>
                </div>
            </div>


            <!-- Sub-section: Nama Pengirim -->
            <div class="stg-section">
                <label class="label-text font-bold text-sm mb-1 block" for="wa_sender_name">Nama Pengirim</label>
                <div class="join w-full">
                    <span class="join-item bg-base-200 border border-base-300 px-3 flex items-center text-base-content/60">
                        <i data-lucide="contact" class="w-4 h-4"></i>
                    </span>
                    <input type="text" class="join-item input input-bordered w-full" id="wa_sender_name" name="wa_sender_name"
                        value="<?= esc($waSenderNameValue) ?>"
                        placeholder="Contoh: Sekretariat DPRD" maxlength="60" />
                </div>
            </div>

            <div class="stg-divider"></div>

            <!-- Sub-section: Template Pesan -->
            <div class="stg-section">
                <div class="flex items-center justify-between mb-2">
                    <div class="stg-section-label mb-0">Template Pesan</div>
                    <div class="flex items-center gap-2 mb-0">
                        <input class="toggle toggle-primary toggle-sm" type="checkbox" role="switch" id="wa_template_default_aktif"
                            name="wa_template_default_aktif" value="1" <?= $settings['wa_template_default_aktif'] ? 'checked' : '' ?> />
                        <label class="label-text font-semibold cursor-pointer" for="wa_template_default_aktif">Gunakan Template Default</label>
                    </div>
                </div>
                <div class="stg-wa-template-row">
                    <div class="stg-wa-template-left" id="wa-template-editor-wrapper" style="display: <?= $settings['wa_template_default_aktif'] ? 'none' : 'block' ?>;">
                        <!-- WA Formatting Toolbar -->
                        <div class="wa-toolbar" id="wa-toolbar">
                            <button type="button" class="wa-toolbar-btn" id="wa-btn-bold"
                                onmousedown="event.preventDefault(); waExecFormat('strong')" title="Bold">
                                <strong>B</strong>
                            </button>
                            <button type="button" class="wa-toolbar-btn" id="wa-btn-italic"
                                onmousedown="event.preventDefault(); waExecFormat('em')" title="Italic">
                                <em>I</em>
                            </button>
                            <button type="button" class="wa-toolbar-btn" id="wa-btn-strike"
                                onmousedown="event.preventDefault(); waExecFormat('strike')" title="Coret">
                                <s>S</s>
                            </button>
                            <button type="button" class="wa-toolbar-btn" id="wa-btn-mono"
                                onmousedown="event.preventDefault(); waExecMono()" title="Monospace">
                                <code style="font-size:.72rem;">&lt;/&gt;</code>
                            </button>
                            <span class="wa-toolbar-sep"></span>
                            <span class="wa-toolbar-hint">Format WhatsApp</span>
                            
                            <button type="button" class="btn btn-xs btn-outline flex items-center py-1 px-2 ml-auto" style="font-size:.7rem; height:24px; border:none;" onclick="resetWaTemplateToDefault()">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 mr-1"></i>Atur Ulang
                            </button>
                        </div>

                        <!-- ProseMirror Rich Editor -->
                        <div class="wa-rich-editor" id="wa_template_editor"
                            data-placeholder="Tulis template pesan WA..."></div>

                        <!-- Hidden input for form submission -->
                        <textarea class="hidden" id="wa_template_reminder"
                            name="wa_template_reminder"><?= esc($waTemplateValue) ?></textarea>

                        <div class="stg-wa-chips mt-2" id="wa-placeholder-list">
                            <?php foreach (($waPlaceholders ?? []) as $key => $label): ?>
                                <button type="button" class="stg-wa-chip"
                                    data-token="<?= esc($key) ?>"
                                    data-label="<?= esc($label) ?>"
                                    title="Variabel: {<?= esc($key) ?>}"
                                    onmousedown="event.preventDefault(); insertWaToken('<?= esc($key) ?>', '<?= esc($label) ?>')">
                                    <?= esc($label) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="label-text-alt text-base-content/60 mt-1 block">Klik untuk menyisipkan variabel ke posisi kursor.</div>
                        <div class="alert alert-warning py-2 px-3 mt-2 mb-0" id="wa-template-warning" style="display:none;"></div>
                    </div>
                    <div class="stg-wa-template-right">
                        <div class="stg-wa-preview-label"><i data-lucide="eye" class="mr-1"></i>Preview</div>
                        <div id="wa-template-preview" class="stg-wa-preview"></div>
                    </div>
                </div>
            </div>

        </div>

    </div>

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
        <button type="submit" class="btn btn-primary btn-md shadow-md">
            <i data-lucide="save" class="w-4 h-4 mr-1"></i>Simpan Semua Pengaturan
        </button>
    </div>

</form>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    /* ─── Header ─── */
    .settings-header { margin-bottom: 20px; }

    /* ─── 2-Column Grid ─── */
    .settings-grid-2col {
        display: grid;
        grid-template-columns: 2fr 3fr;
        gap: 20px;
        align-items: start;
    }

    /* ─── Card shell ─── */
    .stg-card {
        padding: 0 !important;
        overflow: hidden;
    }

    .stg-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 18px 22px;
        border-bottom: 1px solid var(--od-border);
        background: var(--od-border-soft);
    }

    .stg-card-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .stg-card-icon .lucide,
    .stg-card-icon [data-lucide] { width: 18px; height: 18px; }
    .stg-card-icon.blue  { background: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary); }
    .stg-card-icon.green { background: color-mix(in srgb, var(--color-success) 12%, transparent); color: var(--color-success); }

    .stg-card-title {
        font-size: .95rem;
        font-weight: 800;
        color: var(--od-fg);
        margin: 0;
        line-height: 1.3;
    }
    .stg-card-sub {
        font-size: .78rem;
        color: var(--od-muted);
        margin: 2px 0 0;
    }

    /* ─── Sections inside cards ─── */
    .stg-section {
        padding: 16px 22px;
    }

    .stg-divider {
        height: 1px;
        background: var(--od-border-soft);
        margin: 0 22px;
    }

    .stg-section-label {
        font-size: .76rem;
        font-weight: 800;
        color: var(--od-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 10px;
    }

    /* ─── Tema / Media cards ─── */
    .stg-tema-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .stg-tema-card {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 2px solid var(--od-border);
        border-radius: 12px;
        padding: 12px 14px;
        cursor: pointer;
        transition: all .18s ease;
        color: var(--od-muted);
    }
    .stg-tema-card:hover {
        border-color: color-mix(in srgb, var(--od-accent) 50%, var(--od-border));
        color: var(--od-fg2);
    }
    .stg-tema-card.selected {
        border-color: var(--od-accent);
        background: color-mix(in srgb, var(--od-accent) 10%, var(--od-surface));
        color: var(--od-accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--od-accent) 15%, transparent);
    }

    .stg-tema-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: var(--od-border-soft);
        transition: background .18s ease;
    }
    .stg-tema-icon .lucide,
    .stg-tema-icon [data-lucide] { width: 18px; height: 18px; }
    .stg-tema-icon.dark  { color: #465fff; }
    .stg-tema-icon.light { color: #f79009; }
    .stg-tema-icon.media { color: var(--od-muted); }
    .stg-tema-card.selected .stg-tema-icon { background: color-mix(in srgb, var(--color-primary) 20%, transparent); }
    .stg-tema-card.selected .stg-tema-icon.dark  { color: #465fff; }
    .stg-tema-card.selected .stg-tema-icon.light { color: #f79009; }
    .stg-tema-card.selected .stg-tema-icon.media { color: var(--od-accent); }

    .stg-tema-name {
        font-size: .86rem;
        font-weight: 700;
        line-height: 1;
    }
    .stg-tema-desc {
        font-size: .72rem;
        color: var(--od-muted);
        margin-left: auto;
    }

    /* ─── Running text preview ─── */
    .stg-running-preview {
        background: linear-gradient(135deg, #0f1f3d 0%, #1a2942 100%);
        border-radius: 10px;
        padding: 10px 14px;
        overflow: hidden;
    }
    .stg-running-label {
        font-size: .68rem;
        color: rgba(255,255,255,.35);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
    }
    .stg-running-label .lucide,
    .stg-running-label [data-lucide] { width: 12px; height: 12px; }
    .stg-running-track {
        overflow: hidden;
        white-space: nowrap;
    }
    .stg-running-track span {
        display: inline-block;
        color: #fbbf24;
        font-size: .85rem;
        font-weight: 600;
        animation: marquee-preview 12s linear infinite;
    }
    @keyframes marquee-preview {
        0%   { transform: translateX(100%); }
        100% { transform: translateX(-100%); }
    }

    /* ─── WA Status row ─── */
    .stg-wa-status-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .stg-wa-conn {
        flex: 1;
        background: var(--od-border-soft);
        border: 1px solid var(--od-border);
        border-radius: 10px;
        padding: 12px 14px;
    }
    .stg-wa-conn .conn-ok { color: var(--color-success); font-size: .82rem; font-weight: 600; }
    .stg-wa-conn .conn-err { color: var(--color-error); font-size: .82rem; font-weight: 600; }

    /* ─── WA Template row ─── */
    .stg-wa-template-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 14px;
    }
    /* ─── WA Toolbar ─── */
    .wa-toolbar {
        display: flex;
        align-items: center;
        gap: 3px;
        padding: 5px 8px;
        background: var(--od-border-soft);
        border: 1px solid var(--od-border);
        border-bottom: none;
        border-radius: 8px 8px 0 0;
    }
    .wa-toolbar-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 28px;
        border: 1px solid transparent;
        border-radius: 5px;
        background: transparent;
        color: var(--od-fg2);
        cursor: pointer;
        font-size: .82rem;
        transition: all .15s ease;
    }
    .wa-toolbar-btn:hover {
        background: var(--od-border-soft);
        border-color: var(--od-border);
        color: var(--od-fg);
    }
    .wa-toolbar-btn.active {
        background: color-mix(in srgb, var(--color-primary) 15%, transparent);
        border-color: var(--color-primary);
        color: var(--color-primary);
    }
    .wa-toolbar-sep {
        width: 1px;
        height: 18px;
        background: var(--od-border);
        margin: 0 4px;
    }
    .wa-toolbar-hint {
        font-size: .68rem;
        color: var(--od-muted);
        margin-left: 2px;
    }

    /* ─── ProseMirror Rich Editor ─── */
    .wa-rich-editor {
        max-height: 420px;
        overflow-y: auto;
        border: 1px solid var(--od-border);
        border-radius: 0 0 8px 8px;
        background: var(--od-surface);
        font-size: .84rem;
        font-family: var(--od-font, 'Inter', sans-serif);
        line-height: 1.7;
        color: var(--od-fg);
        cursor: text;
    }
    .wa-rich-editor:focus-within {
        border-color: var(--od-accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--od-accent) 15%, transparent);
    }
    .wa-rich-editor .ProseMirror {
        min-height: 200px;
        padding: 10px 12px;
        outline: none;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .wa-rich-editor .ProseMirror p {
        margin: 0;
    }
    .wa-editor-placeholder {
        color: var(--od-muted);
        pointer-events: none;
        float: left;
        height: 0;
    }
    /* Locked placeholder token inside editor */
    .wa-rich-editor .wa-var {
        display: inline-flex;
        align-items: center;
        background: color-mix(in srgb, var(--color-info) 15%, transparent);
        color: var(--color-info);
        border: 1px solid color-mix(in srgb, var(--color-info) 30%, transparent);
        border-radius: 5px;
        padding: 1px 7px;
        font-size: .76rem;
        font-weight: 700;
        font-family: var(--od-font, 'Inter', sans-serif);
        line-height: 1.5;
        pointer-events: none;
        user-select: none;
        cursor: default;
        white-space: nowrap;
        vertical-align: baseline;
    }
    /* Monospace / code in editor */
    .wa-rich-editor code, .wa-rich-editor .wa-code {
        background: var(--od-border-soft);
        border: 1px solid var(--od-border);
        border-radius: 4px;
        padding: 1px 5px;
        font-family: 'Courier New', Courier, monospace;
        font-size: .82em;
        color: var(--od-fg);
    }

    /* ─── WA Placeholder chips ─── */
    .stg-wa-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    .stg-wa-chip {
        border: 1px solid color-mix(in srgb, var(--color-info) 30%, transparent);
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-info) 12%, transparent);
        color: var(--color-info);
        cursor: pointer;
        font-family: var(--od-mono);
        font-size: .7rem;
        font-weight: 700;
        line-height: 1;
        padding: 5px 8px;
        transition: all .16s ease;
    }
    .stg-wa-chip:hover {
        border-color: var(--color-info);
        background: color-mix(in srgb, var(--color-info) 20%, transparent);
    }

    /* ─── WA Preview ─── */
    .stg-wa-preview-label {
        font-weight: 700;
        font-size: .8rem;
        color: var(--od-fg2);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
    }
    .stg-wa-preview-label .lucide,
    .stg-wa-preview-label [data-lucide] { width: 14px; height: 14px; }
    .stg-wa-preview {
        background: var(--od-border-soft);
        border: 1px solid var(--od-border);
        border-radius: 8px;
        color: var(--od-fg);
        font-family: var(--od-mono);
        font-size: .76rem;
        line-height: 1.5;
        margin: 0;
        min-height: 200px;
        padding: 12px;
        white-space: pre-wrap;
    }

    /* ─── WA Badge ─── */
    .wa-status-badge {
        font-size: .72rem;
        padding: .3em .6em;
        transition: all .3s ease;
    }

    /* ─── Save bar ─── */
    .settings-save-bar {
        position: sticky;
        bottom: 0;
        background: linear-gradient(180deg, transparent 0%, var(--od-bg) 24%);
        padding: 24px 0 4px;
        z-index: 10;
        margin-top: 4px;
    }

    /* ─── Responsive ─── */
    @media (max-width: 1024px) {
        .settings-grid-2col {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 600px) {
        .stg-tema-grid,
        .stg-wa-template-row {
            grid-template-columns: 1fr;
        }
        .stg-card-header {
            flex-wrap: wrap;
            gap: 10px;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php $waEditorVersion = is_file(FCPATH . 'assets/js/admin/wa-template-editor.js') ? filemtime(FCPATH . 'assets/js/admin/wa-template-editor.js') : time(); ?>
<script src="<?= base_url('assets/js/admin/wa-template-editor.js?v=' . $waEditorVersion) ?>" data-turbo-track="reload"></script>
<script>
(() => {
    const waTemplatePlaceholders = <?= json_encode(array_keys($waPlaceholders ?? []), JSON_UNESCAPED_SLASHES) ?>;
    const waTemplateSample = <?= json_encode([
        'nama_peserta'  => 'Bapak/Ibu Anggota',
        'judul_rapat'   => 'Rapat Badan Musyawarah',
        'tanggal'       => 'Senin, 15 Juni 2026',
        'waktu_mulai'   => '09:00',
        'waktu_selesai' => '11:00',
        'ruangan'       => 'Ruang Sidang Utama',
        'unit_rapat'    => 'Badan Musyawarah',
        'catatan'       => 'Mohon hadir 15 menit sebelum rapat dimulai.',
        'link_jadwal'   => base_url('jadwal'),
        'link_berkas'   => 'Materi: https://contoh.go.id/materi-rapat.pdf',
        'sender_name'   => $waSenderNameValue ?? 'Sekretariat DPRD',
    ], JSON_UNESCAPED_SLASHES) ?>;

    const waDefaultTemplate = <?= json_encode(\App\Libraries\WhatsappService::defaultReminderTemplate(), JSON_UNESCAPED_SLASHES) ?>;

    // ══════════════════════════════════════════════════════════════
    // WA Rich Editor — ProseMirror bridge
    // ══════════════════════════════════════════════════════════════

    /**
     * Sync ProseMirror doc to hidden textarea before preview or submit.
     */
    function syncEditorToHidden() {
        const hidden = document.getElementById('wa_template_reminder');
        if (!hidden) return;

        if (window.WaTemplateEditor) {
            hidden.value = window.WaTemplateEditor.sync();
        }
        renderWaTemplatePreview();
    }

    /**
     * Build the label map from chip buttons (so we don't duplicate PHP data).
     */
    function getPlaceholderLabels() {
        const map = {};
        document.querySelectorAll('.stg-wa-chip[data-token]').forEach(btn => {
            map[btn.dataset.token] = btn.dataset.label || btn.dataset.token;
        });
        return map;
    }

    // ══════════════════════════════════════════════════════════════
    // Toolbar actions
    // ══════════════════════════════════════════════════════════════

    function waExecFormat(markName) {
        window.WaTemplateEditor?.format(markName);
    }

    function waExecMono() {
        window.WaTemplateEditor?.format('code');
    }

    function updateToolbarState(active = null) {
        const marks = active || window.WaTemplateEditor?.activeMarks?.() || {};
        const map = {
            'wa-btn-bold': 'strong',
            'wa-btn-italic': 'em',
            'wa-btn-strike': 'strike',
            'wa-btn-mono': 'code',
        };
        for (const [id, markName] of Object.entries(map)) {
            const btn = document.getElementById(id);
            if (btn) btn.classList.toggle('active', !!marks[markName]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Insert locked placeholder chip at cursor
    // ══════════════════════════════════════════════════════════════

    function insertWaToken(tokenKey, tokenLabel) {
        window.WaTemplateEditor?.insertToken(tokenKey, tokenLabel);
    }

    // ══════════════════════════════════════════════════════════════
    // Reset & Preview
    // ══════════════════════════════════════════════════════════════

    function resetWaTemplateToDefault() {
        const hidden = document.getElementById('wa_template_reminder');
        if (!hidden) return;

        hidden.value = waDefaultTemplate;
        window.WaTemplateEditor?.setText(waDefaultTemplate);
        renderWaTemplatePreview();
    }

    function renderWaTemplatePreview() {
        const defaultToggle = document.getElementById('wa_template_default_aktif');
        const isDefault = defaultToggle ? defaultToggle.checked : true;

        const hidden = document.getElementById('wa_template_reminder');
        const preview = document.getElementById('wa-template-preview');
        const warning = document.getElementById('wa-template-warning');
        const senderInput = document.getElementById('wa_sender_name');
        if (!hidden || !preview) return;

        const allowed = new Set(waTemplatePlaceholders);
        const sample = { ...waTemplateSample, sender_name: senderInput?.value.trim() || 'Sekretariat DPRD' };
        const unknown = new Set();

        const templateText = isDefault ? waDefaultTemplate : hidden.value;

        // Fill placeholders with sample data
        let text = templateText.replace(/\{([a-zA-Z0-9_]+)\}/g, (match, key) => {
            if (!allowed.has(key)) { unknown.add(match); return match; }
            return sample[key] ?? '';
        });

        // Escape & render WA formatting for preview
        text = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        text = text.replace(/```([^`]*)```/g, '<code style="background:var(--od-border-soft);color:var(--od-fg);padding:1px 5px;border-radius:3px;font-family:monospace;">$1</code>');
        text = text.replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>');
        text = text.replace(/_([^_\n]+)_/g, '<em>$1</em>');
        text = text.replace(/~([^~\n]+)~/g, '<del>$1</del>');
        text = text.replace(/\n/g, '<br>');
        preview.innerHTML = text;

        if (warning) {
            if (!isDefault && unknown.size > 0) {
                warning.style.display = 'block';
                warning.textContent = 'Variabel tidak dikenal: ' + Array.from(unknown).join(', ');
            } else {
                warning.style.display = 'none';
                warning.textContent = '';
            }
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Editor event listeners
    // ══════════════════════════════════════════════════════════════

    // Tema & Mode selection
    function selectTema(tema) {
        document.querySelectorAll('[id^="tema-"]').forEach(el => el.classList.remove('selected'));
        document.getElementById('tema-' + tema).classList.add('selected');
        document.getElementById('radio-tema-' + tema).checked = true;
    }

    function selectMode(mode) {
        document.querySelectorAll('.stg-tema-card:not([id^="tema-"])').forEach(el => el.classList.remove('selected'));
        document.getElementById('mode-' + mode).classList.add('selected');
        document.getElementById('radio-' + mode).checked = true;
    }

    window.waExecFormat = waExecFormat;
    window.waExecMono = waExecMono;
    window.insertWaToken = insertWaToken;
    window.resetWaTemplateToDefault = resetWaTemplateToDefault;
    window.selectTema = selectTema;
    window.selectMode = selectMode;

    function initSettingsPage() {
        const form = document.querySelector('.settings-form');
        if (!form) return;

        if (form.dataset.settingsBootstrapped === '1') {
            renderWaTemplatePreview();
            window.renderAdminIcons?.();
            return;
        }
        form.dataset.settingsBootstrapped = '1';

        document.getElementById('running_text')?.addEventListener('input', function () {
            document.getElementById('preview-text').textContent = this.value || 'Teks berjalan akan tampil di sini...';
        });

        document.getElementById('wa_sender_name')?.addEventListener('input', renderWaTemplatePreview);

        const editor = document.getElementById('wa_template_editor');
        const hidden = document.getElementById('wa_template_reminder');

        if (editor && hidden) {
            window.WaTemplateEditor?.mount({
                editor,
                hidden,
                labels: getPlaceholderLabels(),
                onUpdate: () => renderWaTemplatePreview(),
                onSelectionUpdate: updateToolbarState,
            });

            editor.closest('form')?.addEventListener('submit', () => {
                syncEditorToHidden();
                localStorage.removeItem('dprd_wa_status_cache');
                localStorage.removeItem('dprd_wa_status_cache_time');
            });
        }
        renderWaTemplatePreview();

        document.getElementById('wa_template_default_aktif')?.addEventListener('change', function() {
            const wrapper = document.getElementById('wa-template-editor-wrapper');
            if (wrapper) {
                wrapper.style.display = this.checked ? 'none' : 'block';
            }
            renderWaTemplatePreview();
        });

        const locationEl = document.getElementById('bmkg-resolved-location');
        if (locationEl) {
            fetch('/api/signage/cuaca').then(r => r.json()).then(data => {
                if (data.status === 'success' && data.lokasi) {
                    const l = data.lokasi;
                    locationEl.innerHTML = `<strong>${l.desa||'-'}, ${l.kecamatan||'-'}, ${l.kotkab||'-'}</strong>`;
                } else {
                    locationEl.innerHTML = '<span class="text-red-600">Gagal mendeteksi lokasi.</span>';
                }
            }).catch(() => {
                locationEl.innerHTML = '<span class="text-red-600">Gagal memuat lokasi.</span>';
            });
        }
        window.renderAdminIcons?.();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSettingsPage, { once: true });
    } else {
        initSettingsPage();
    }
})();




</script>
<?= $this->endSection() ?>
