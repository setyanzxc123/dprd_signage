<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header settings-header flex items-center justify-between">
    <div>
        <h1 class="page-title">Pengaturan Sistem</h1>
        <p class="page-subtitle">Kelola tampilan layar TV dan notifikasi WhatsApp.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= base_url('signage') ?>" target="_blank"
            class="ta-btn ta-btn-sm ta-btn-outline-gray">
            <i data-lucide="monitor" class="mr-1"></i>Preview Signage
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

                <label class="ta-label font-semibold" for="media_file">Upload File</label>
                <input type="file" class="ta-input" id="media_file" name="media_file"
                    accept="video/mp4,video/webm,image/jpeg,image/png,image/webp" />
                <div class="ta-help">Maks. 50MB. File baru menggantikan file aktif.</div>

                <?php if (!empty($settings['media_file'])): ?>
                    <div class="ta-alert ta-alert-gray ta-alert-sm py-2 px-3 mt-2">
                        <i data-lucide="file-video" class="mr-1"></i>
                        Aktif: <strong><?= esc(basename($settings['media_file'])) ?></strong>
                    </div>
                <?php else: ?>
                    <div class="ta-alert ta-alert-warning ta-alert-sm py-2 px-3 mt-2">
                        <i data-lucide="triangle-alert" class="mr-1"></i>
                        Belum ada file media.
                    </div>
                <?php endif; ?>
            </div>

            <div class="stg-divider"></div>

            <!-- Sub-section: Running Text -->
            <div class="stg-section">
                <div class="flex items-center justify-between">
                    <div class="stg-section-label mb-0">Running Text</div>
                    <div class="ta-check ta-switch mb-0">
                        <input class="ta-check-input" type="checkbox" role="switch" id="running_text_aktif"
                            name="running_text_aktif" value="1" <?= $settings['running_text_aktif'] ? 'checked' : '' ?> />
                        <label class="ta-check-label font-semibold" for="running_text_aktif">Aktifkan</label>
                    </div>
                </div>
                <textarea class="ta-input mt-2" id="running_text" name="running_text" rows="2"
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

            <!-- Sub-section: Status koneksi -->
            <div class="stg-section">
                <div class="stg-wa-status-row">
                    <div class="stg-wa-conn" id="wa-conn-status">
                        <div class="flex items-center gap-2 text-gray-500" style="font-size:.82rem;">
                            <span class="ta-spinner ta-spinner-sm" role="status"></span>
                            Memeriksa status pengiriman...
                        </div>
                    </div>
                </div>
            </div>

            <div class="stg-divider"></div>

            <!-- Sub-section: Nama Pengirim -->
            <div class="stg-section">
                <label class="ta-label font-semibold" for="wa_sender_name">Nama Pengirim</label>
                <div class="ta-input-group">
                    <span class="ta-input-addon"><i data-lucide="contact"></i></span>
                    <input type="text" class="ta-input" id="wa_sender_name" name="wa_sender_name"
                        value="<?= esc($waSenderNameValue) ?>"
                        placeholder="Contoh: Sekretariat DPRD" maxlength="60" />
                </div>
            </div>

            <div class="stg-divider"></div>

            <!-- Sub-section: Template Pesan -->
            <div class="stg-section">
                <div class="flex items-center justify-between mb-2">
                    <div class="stg-section-label mb-0">Template Pesan</div>
                    <button type="button" class="ta-btn ta-btn-xs ta-btn-outline-gray flex items-center py-1 px-2" style="font-size:.7rem;" onclick="resetWaTemplateToDefault()">
                        <i data-lucide="rotate-ccw" class="mr-1" style="width:12px;height:12px;"></i>Atur Ulang
                    </button>
                </div>
                <div class="stg-wa-template-row">
                    <div class="stg-wa-template-left">
                        <!-- WA Formatting Toolbar -->
                        <div class="wa-toolbar" id="wa-toolbar">
                            <button type="button" class="wa-toolbar-btn" id="wa-btn-bold"
                                onmousedown="event.preventDefault(); waExecFormat('bold')" title="Bold">
                                <strong>B</strong>
                            </button>
                            <button type="button" class="wa-toolbar-btn" id="wa-btn-italic"
                                onmousedown="event.preventDefault(); waExecFormat('italic')" title="Italic">
                                <em>I</em>
                            </button>
                            <button type="button" class="wa-toolbar-btn" id="wa-btn-strike"
                                onmousedown="event.preventDefault(); waExecFormat('strikethrough')" title="Coret">
                                <s>S</s>
                            </button>
                            <button type="button" class="wa-toolbar-btn" id="wa-btn-mono"
                                onmousedown="event.preventDefault(); waExecMono()" title="Monospace">
                                <code style="font-size:.72rem;">&lt;/&gt;</code>
                            </button>
                            <span class="wa-toolbar-sep"></span>
                            <span class="wa-toolbar-hint">Format WhatsApp</span>
                        </div>

                        <!-- Contenteditable Rich Editor -->
                        <div class="wa-rich-editor" id="wa_template_editor"
                            contenteditable="true"
                            spellcheck="false"
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
                        <div class="ta-help mt-1">Klik untuk menyisipkan variabel ke posisi kursor.</div>
                        <div class="ta-alert ta-alert-warning py-2 px-3 mt-2 mb-0" id="wa-template-warning" style="display:none;"></div>
                    </div>
                    <div class="stg-wa-template-right">
                        <div class="stg-wa-preview-label"><i data-lucide="eye" class="mr-1"></i>Preview</div>
                        <div id="wa-template-preview" class="stg-wa-preview"></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($settings['wa_from_env'])): ?>
            <div class="stg-divider"></div>

            <!-- Sub-section: Uji Kirim -->
            <div class="stg-section">
                <div class="stg-section-label">Uji Kirim Pesan</div>
                <div class="ta-input-group" style="max-width:400px;">
                    <span class="ta-input-addon"><i data-lucide="send" class="text-emerald-600"></i></span>
                    <input type="text" class="ta-input font-mono" id="wa_test_number"
                        placeholder="628xxxxxxxxxx" maxlength="15" />
                    <button class="ta-btn ta-btn-outline-success" type="button"
                        onclick="kirimWaTest()" id="wa-test-btn">
                        <i data-lucide="send" class="mr-1"></i>Kirim
                    </button>
                </div>
                <div class="ta-help">Format: <code>628xxxxxxxxxx</code></div>
                <div id="wa-test-result" class="mt-2" style="display:none;"></div>
            </div>
            <?php endif; ?>
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
                    <label class="ta-label font-semibold" for="bmkg_adm4">Kode Wilayah (ADM4)</label>
                    <input type="text" class="ta-input font-mono" id="bmkg_adm4" name="bmkg_adm4"
                        value="<?= esc(env('BMKG_ADM4') ?: '72.71.01.1004') ?>"
                        placeholder="72.71.01.1004" pattern="\d{2}\.\d{2}\.\d{2}\.\d{4}"
                        <?= env('BMKG_ADM4') ? 'readonly' : '' ?> />
                    <div class="ta-help">
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
        <button type="submit" class="ta-btn ta-btn-primary">
            <i data-lucide="save" class="mr-1"></i>Simpan Semua Pengaturan
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
        background: linear-gradient(180deg, #fcfdff 0%, #fff 100%);
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
    .stg-card-icon.blue  { background: #ecf3ff; color: #465fff; }
    .stg-card-icon.green { background: #ecfdf3; color: #059669; }

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
        border-color: #93b4fd;
        color: var(--od-fg2);
    }
    .stg-tema-card.selected {
        border-color: var(--od-accent);
        background: linear-gradient(135deg, #eef4ff 0%, #fff 100%);
        color: var(--od-accent);
        box-shadow: 0 0 0 3px rgba(70, 95, 255, .08);
    }

    .stg-tema-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: #f3f4f6;
        transition: background .18s ease;
    }
    .stg-tema-icon .lucide,
    .stg-tema-icon [data-lucide] { width: 18px; height: 18px; }
    .stg-tema-icon.dark  { color: #465fff; }
    .stg-tema-icon.light { color: #f79009; }
    .stg-tema-icon.media { color: var(--od-muted); }
    .stg-tema-card.selected .stg-tema-icon { background: #dbeafe; }
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
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px 14px;
    }
    .stg-wa-conn .conn-ok { color: #15803d; font-size: .82rem; font-weight: 600; }
    .stg-wa-conn .conn-err { color: #dc2626; font-size: .82rem; font-weight: 600; }

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
        background: #f9fafb;
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
        color: #374151;
        cursor: pointer;
        font-size: .82rem;
        transition: all .15s ease;
    }
    .wa-toolbar-btn:hover {
        background: #e5e7eb;
        border-color: #d1d5db;
        color: #111827;
    }
    .wa-toolbar-btn.active {
        background: #dbeafe;
        border-color: #93c5fd;
        color: #1d4ed8;
    }
    .wa-toolbar-sep {
        width: 1px;
        height: 18px;
        background: #d1d5db;
        margin: 0 4px;
    }
    .wa-toolbar-hint {
        font-size: .68rem;
        color: var(--od-muted);
        margin-left: 2px;
    }

    /* ─── Contenteditable Rich Editor ─── */
    .wa-rich-editor {
        min-height: 200px;
        max-height: 420px;
        overflow-y: auto;
        padding: 10px 12px;
        border: 1px solid var(--od-border);
        border-radius: 0 0 8px 8px;
        background: #fff;
        font-size: .84rem;
        font-family: var(--od-font, 'Inter', sans-serif);
        line-height: 1.7;
        outline: none;
        white-space: pre-wrap;
        word-break: break-word;
        color: var(--od-fg);
        cursor: text;
    }
    .wa-rich-editor:focus {
        border-color: var(--od-accent);
        box-shadow: 0 0 0 3px rgba(70,95,255,.08);
    }
    .wa-rich-editor:empty::before {
        content: attr(data-placeholder);
        color: var(--od-muted);
        pointer-events: none;
    }
    /* Locked placeholder token inside editor */
    .wa-rich-editor .wa-var {
        display: inline-flex;
        align-items: center;
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        border-radius: 5px;
        padding: 1px 7px;
        font-size: .76rem;
        font-weight: 700;
        font-family: var(--od-font, 'Inter', sans-serif);
        line-height: 1.5;
        user-select: none;
        cursor: default;
        white-space: nowrap;
        vertical-align: baseline;
    }
    /* Monospace / code in editor */
    .wa-rich-editor code, .wa-rich-editor .wa-code {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        padding: 1px 5px;
        font-family: 'Courier New', Courier, monospace;
        font-size: .82em;
        color: #374151;
    }

    /* ─── WA Placeholder chips ─── */
    .stg-wa-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    .stg-wa-chip {
        border: 1px solid #bae6fd;
        border-radius: 999px;
        background: #e0f2fe;
        color: #0369a1;
        cursor: pointer;
        font-family: var(--od-mono);
        font-size: .7rem;
        font-weight: 700;
        line-height: 1;
        padding: 5px 8px;
        transition: all .16s ease;
    }
    .stg-wa-chip:hover {
        border-color: #7dd3fc;
        color: #075985;
        background: #bae6fd;
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
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        color: #111827;
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
        background: linear-gradient(180deg, rgba(249,250,251,0) 0%, rgba(249,250,251,1) 24%);
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
<script>
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

    // Running text preview
    document.getElementById('running_text')?.addEventListener('input', function () {
        document.getElementById('preview-text').textContent = this.value || 'Teks berjalan akan tampil di sini...';
    });

    const waDefaultTemplate = <?= json_encode(\App\Libraries\WhatsappService::defaultReminderTemplate(), JSON_UNESCAPED_SLASHES) ?>;

    // ══════════════════════════════════════════════════════════════
    // WA Rich Editor — DOM ↔ WA-format conversion
    // ══════════════════════════════════════════════════════════════

    /**
     * Convert WA plain-text (with *, _, ~, ``` symbols + {placeholders})
     * into HTML suitable for the contenteditable editor.
     */
    function waTextToHtml(waText, placeholderLabels) {
        if (!waText) return '';

        // Process line by line so block-level formatting is correct
        const lines = waText.split('\n');
        const htmlLines = lines.map(line => {
            // Step 1: Escape raw HTML chars first
            let s = line
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Step 2: Replace placeholders FIRST — before any formatting regex
            // This prevents underscores inside {waktu_mulai} etc. from
            // being mistakenly matched by the italic _text_ regex below.
            s = s.replace(/\{([a-zA-Z0-9_]+)\}/g, (match, key) => {
                const label = placeholderLabels[key] || key;
                return `<span class="wa-var" contenteditable="false" data-token="${key}">${label}</span>`;
            });

            // Step 3: Apply WA formatting (safe — no raw {key} underscores left)
            // Monospace  ```text``` → <code class="wa-code">text</code>
            s = s.replace(/```([^`]*)```/g, '<code class="wa-code">$1</code>');
            // Bold  *text* → <strong>text</strong>
            s = s.replace(/\*([^*<>]+)\*/g, '<strong>$1</strong>');
            // Italic  _text_ → <em>text</em>
            s = s.replace(/_([^_<>]+)_/g, '<em>$1</em>');
            // Strikethrough  ~text~ → <del>text</del>
            s = s.replace(/~([^~<>]+)~/g, '<del>$1</del>');

            return s;
        });

        return htmlLines.join('<br>');
    }

    /**
     * Walk the editor DOM and convert it back to WA plain-text.
     * Handles: <strong>/<b> → *x*, <em>/<i> → _x_, <del>/<s> → ~x~,
     *          <code> → ```x```, .wa-var → {token}, <br>/<div> → \n
     */
    function domToWaText(node) {
        let out = '';

        for (const child of node.childNodes) {
            if (child.nodeType === Node.TEXT_NODE) {
                out += child.nodeValue;
                continue;
            }
            if (child.nodeType !== Node.ELEMENT_NODE) continue;

            const tag = child.tagName.toLowerCase();

            // Locked placeholder token
            if (child.classList?.contains('wa-var')) {
                const key = child.dataset.token || '';
                out += key ? `{${key}}` : '';
                continue;
            }

            if (tag === 'br') { out += '\n'; continue; }

            // Block-level wrappers (contenteditable inserts divs on Enter)
            if (tag === 'div' || tag === 'p') {
                const inner = domToWaText(child);
                // Only add newline if there's already content (not first line)
                out += (out && !out.endsWith('\n') ? '\n' : '') + inner;
                if (!out.endsWith('\n')) out += '\n';
                continue;
            }

            const inner = domToWaText(child);

            if (tag === 'strong' || tag === 'b') { out += `*${inner}*`; continue; }
            if (tag === 'em'     || tag === 'i') { out += `_${inner}_`; continue; }
            if (tag === 'del'    || tag === 's' || tag === 'strike') { out += `~${inner}~`; continue; }
            if (tag === 'code') { out += `\`\`\`${inner}\`\`\``; continue; }

            // Any other tag — just recurse
            out += inner;
        }
        return out;
    }

    /**
     * Sync editor DOM → hidden textarea → trigger preview update.
     */
    function syncEditorToHidden() {
        const editor = document.getElementById('wa_template_editor');
        const hidden = document.getElementById('wa_template_reminder');
        if (!editor || !hidden) return;

        let waText = domToWaText(editor);
        // Trim trailing newlines but keep single trailing \n
        waText = waText.replace(/\n+$/, '');
        hidden.value = waText;
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

    function waExecFormat(command) {
        const editor = document.getElementById('wa_template_editor');
        editor?.focus();
        document.execCommand(command, false, null);
        updateToolbarState();
        syncEditorToHidden();
    }

    function waExecMono() {
        const editor = document.getElementById('wa_template_editor');
        if (!editor) return;
        editor.focus();

        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return;
        const range = sel.getRangeAt(0);
        const selected = range.toString();

        if (!selected) return;

        // Wrap selection in <code class="wa-code">
        const code = document.createElement('code');
        code.className = 'wa-code';
        code.textContent = selected;
        range.deleteContents();
        range.insertNode(code);

        // Move cursor after inserted code
        const newRange = document.createRange();
        newRange.setStartAfter(code);
        newRange.collapse(true);
        sel.removeAllRanges();
        sel.addRange(newRange);

        syncEditorToHidden();
    }

    function updateToolbarState() {
        const map = {
            'wa-btn-bold':   'bold',
            'wa-btn-italic': 'italic',
            'wa-btn-strike': 'strikethrough',
        };
        for (const [id, cmd] of Object.entries(map)) {
            const btn = document.getElementById(id);
            if (btn) btn.classList.toggle('active', document.queryCommandState(cmd));
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Insert locked placeholder chip at cursor
    // ══════════════════════════════════════════════════════════════

    function insertWaToken(tokenKey, tokenLabel) {
        const editor = document.getElementById('wa_template_editor');
        if (!editor) return;

        editor.focus();
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return;

        const range = sel.getRangeAt(0);
        range.deleteContents();

        // Create locked chip
        const chip = document.createElement('span');
        chip.className = 'wa-var';
        chip.setAttribute('contenteditable', 'false');
        chip.dataset.token = tokenKey;
        chip.textContent = tokenLabel;

        // Insert chip + trailing zero-width space so cursor can move past it
        const zwsp = document.createTextNode('\u200B');
        const frag = document.createDocumentFragment();
        frag.appendChild(chip);
        frag.appendChild(zwsp);
        range.insertNode(frag);

        // Place cursor after the zero-width space
        const newRange = document.createRange();
        newRange.setStartAfter(zwsp);
        newRange.collapse(true);
        sel.removeAllRanges();
        sel.addRange(newRange);

        syncEditorToHidden();
    }

    // ══════════════════════════════════════════════════════════════
    // Reset & Preview
    // ══════════════════════════════════════════════════════════════

    function resetWaTemplateToDefault() {
        if (!confirm('Kembalikan template pesan ke bawaan sistem?')) return;
        const editor = document.getElementById('wa_template_editor');
        const hidden = document.getElementById('wa_template_reminder');
        if (!editor || !hidden) return;

        hidden.value = waDefaultTemplate;
        editor.innerHTML = waTextToHtml(waDefaultTemplate, getPlaceholderLabels());
        renderWaTemplatePreview();
    }

    function renderWaTemplatePreview() {
        const hidden = document.getElementById('wa_template_reminder');
        const preview = document.getElementById('wa-template-preview');
        const warning = document.getElementById('wa-template-warning');
        const senderInput = document.getElementById('wa_sender_name');
        if (!hidden || !preview) return;

        const allowed = new Set(waTemplatePlaceholders);
        const sample = { ...waTemplateSample, sender_name: senderInput?.value.trim() || 'Sekretariat DPRD' };
        const unknown = new Set();

        // Fill placeholders with sample data
        let text = hidden.value.replace(/\{([a-zA-Z0-9_]+)\}/g, (match, key) => {
            if (!allowed.has(key)) { unknown.add(match); return match; }
            return sample[key] ?? '';
        });

        // Escape & render WA formatting for preview
        text = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        text = text.replace(/```([^`]*)```/g, '<code style="background:#f3f4f6;padding:1px 5px;border-radius:3px;font-family:monospace;">$1</code>');
        text = text.replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>');
        text = text.replace(/_([^_\n]+)_/g, '<em>$1</em>');
        text = text.replace(/~([^~\n]+)~/g, '<del>$1</del>');
        text = text.replace(/\n/g, '<br>');
        preview.innerHTML = text;

        if (warning) {
            if (unknown.size > 0) {
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

    document.getElementById('wa_sender_name')?.addEventListener('input', renderWaTemplatePreview);

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

    // DOMContentLoaded — bootstrap editor
    document.addEventListener('DOMContentLoaded', function() {
        const editor = document.getElementById('wa_template_editor');
        const hidden = document.getElementById('wa_template_reminder');

        if (editor && hidden) {
            // Render stored WA text into visual editor
            editor.innerHTML = waTextToHtml(hidden.value, getPlaceholderLabels());

            // Sync back on every edit
            editor.addEventListener('input', () => {
                syncEditorToHidden();
                updateToolbarState();
            });
            editor.addEventListener('keyup', updateToolbarState);
            editor.addEventListener('mouseup', updateToolbarState);
            editor.addEventListener('selectionchange', updateToolbarState);

            // Prevent form submission from submitting div HTML
            editor.closest('form')?.addEventListener('submit', () => {
                syncEditorToHidden();
            });
        }
        renderWaTemplatePreview();

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
        checkWaConn();
        window.renderAdminIcons?.();
    });

    // WA test send
    async function kirimWaTest() {
        const noWa = document.getElementById('wa_test_number')?.value.trim();
        const result = document.getElementById('wa-test-result');
        const btn = document.getElementById('wa-test-btn');

        if (!noWa || !/^62\d{8,13}$/.test(noWa)) {
            result.style.display = 'block';
            result.innerHTML = '<div class="ta-alert ta-alert-warning py-2 px-3 mb-0"><i data-lucide="triangle-alert" class="mr-1"></i>Nomor tidak valid.</div>';
            window.renderAdminIcons?.();
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="ta-spinner ta-spinner-sm mr-1"></span>Mengirim...';
        result.style.display = 'none';

        try {
            const resp = await fetch('/admin/pengaturan/wa-test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                },
                body: new URLSearchParams({
                    no_wa: noWa,
                    <?= csrf_token() ?>: document.querySelector('input[name="<?= csrf_token() ?>"]')?.value ?? ''
                })
            });
            const data = await resp.json();
            result.style.display = 'block';
            result.innerHTML = data.success
                ? '<div class="ta-alert ta-alert-success py-2 px-3 mb-0"><i data-lucide="circle-check" class="mr-1"></i>Berhasil dikirim ke <strong>' + noWa + '</strong>.</div>'
                : '<div class="ta-alert ta-alert-danger py-2 px-3 mb-0"><i data-lucide="circle-x" class="mr-1"></i>Gagal: ' + (data.error || 'Unknown') + '</div>';
        } catch(e) {
            result.style.display = 'block';
            result.innerHTML = '<div class="ta-alert ta-alert-danger py-2 px-3 mb-0"><i data-lucide="circle-x" class="mr-1"></i>Error: ' + e.message + '</div>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="send" class="mr-1"></i>Kirim';
            window.renderAdminIcons?.();
        }
    }

    // WA connection check
    async function checkWaConn() {
        const el = document.getElementById('wa-conn-status');
        if (!el) return;
        try {
            const resp = await fetch('/admin/pengaturan/wa-status', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await resp.json();
            if (data.configured && data.connected) {
                el.innerHTML = '<div class="conn-ok"><i data-lucide="circle-check" class="mr-1"></i>Pengiriman WhatsApp siap</div>';
            } else if (data.configured && !data.connected) {
                el.innerHTML = '<div class="conn-err"><i data-lucide="circle-x" class="mr-1"></i>Belum siap<br><small class="text-gray-500">' + (data.error ?? '') + '</small></div>';
            } else {
                el.innerHTML = '<div class="text-gray-500" style="font-size:.82rem;"><i data-lucide="circle-minus" class="mr-1"></i>Belum dikonfigurasi.</div>';
            }
        } catch(e) {
            el.innerHTML = '<div class="text-gray-500" style="font-size:.82rem;"><i data-lucide="wifi-off" class="mr-1"></i>Tidak dapat memeriksa status.</div>';
        }
        window.renderAdminIcons?.();
    }
</script>
<?= $this->endSection() ?>
