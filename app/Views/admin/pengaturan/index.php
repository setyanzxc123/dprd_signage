<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header flex items-center justify-between">
    <div>
        <h1 class="page-title">Pengaturan Signage</h1>
        <p class="page-subtitle">Atur tampilan layar TV digital di ruang sidang DPRD</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= base_url('signage?tema=dark') ?>" target="_blank"
            class="ta-btn ta-btn-sm ta-btn-outline-gray">
            <i data-lucide="moon" class="mr-1"></i>Preview Dark
        </a>
        <a href="<?= base_url('signage?tema=light') ?>" target="_blank"
            class="ta-btn ta-btn-sm ta-btn-outline-gray">
            <i data-lucide="sun" class="mr-1"></i>Preview Light
        </a>
    </div>
</div>

<form action="<?= base_url('admin/pengaturan/save') ?>" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="grid grid-cols-12 gap-4">

        <div class="lg:col-span-8">

            <div class="form-card mb-3">
                <div class="form-section-title">Tema Warna Layar TV</div>

                <label class="ta-label font-semibold">Pilih Tema</label>
                <div class="flex gap-3">

                    <div class="media-mode-card <?= $settings['tema_signage'] === 'dark' ? 'selected' : '' ?>"
                        id="tema-dark" onclick="selectTema('dark')">
                        <input type="radio" name="tema_signage" value="dark" id="radio-tema-dark"
                            <?= $settings['tema_signage'] === 'dark' ? 'checked' : '' ?>
                        class="hidden" />
                        <i data-lucide="moon-star" class="icon-tema-dark"></i>
                        <div class="font-semibold">Dark</div>
                        <div class="text-gray-500 text-xs">Navy gelap</div>
                    </div>

                    <div class="media-mode-card <?= $settings['tema_signage'] === 'light' ? 'selected' : '' ?>"
                        id="tema-light" onclick="selectTema('light')">
                        <input type="radio" name="tema_signage" value="light" id="radio-tema-light"
                            <?= $settings['tema_signage'] === 'light' ? 'checked' : '' ?>
                        class="hidden" />
                        <i data-lucide="sun" class="icon-tema-light"></i>
                        <div class="font-semibold">Light</div>
                        <div class="text-gray-500 text-xs">Putih bersih</div>
                    </div>

                </div>
                <div class="ta-help mt-2">
                    <i data-lucide="info" class="mr-1"></i>
                    Perubahan tema berlaku setelah klik Simpan. Pratinjau tersedia di tombol kanan atas.
                </div>
            </div>

            <div class="form-card mb-3">
                <div class="form-section-title">Teks Berjalan (Running Text)</div>

                <div class="mb-3">
                    <div class="flex items-center justify-between mb-2">
                        <label class="ta-label font-semibold mb-0" for="running_text">
                            Isi Teks
                        </label>
                        <div class="ta-check ta-switch mb-0">
                            <input class="ta-check-input" type="checkbox" role="switch" id="running_text_aktif"
                                name="running_text_aktif" value="1" <?= $settings['running_text_aktif'] ? 'checked' : '' ?> />
                            <label class="ta-check-label font-semibold" for="running_text_aktif">
                                Aktifkan
                            </label>
                        </div>
                    </div>

                    <textarea class="ta-input" id="running_text" name="running_text" rows="3"
                        placeholder="Contoh: Selamat datang di Gedung DPRD Provinsi Sulawesi Tengah. Jadwal rapat dapat berubah sewaktu-waktu."><?= esc($settings['running_text']) ?></textarea>
                    <div class="ta-help">
                        Teks ini akan berjalan di bagian bawah layar TV signage secara terus-menerus.
                    </div>
                </div>

                <div class="running-text-preview">
                    <div class="running-text-label">
                        <i data-lucide="eye" class="mr-1"></i>Pratinjau
                    </div>
                    <div class="running-text-track" id="preview-track">
                        <span id="preview-text">
                            <?= esc($settings['running_text']) ?: 'Teks berjalan akan tampil di sini...' ?>
                        </span>
                    </div>
                </div>

            </div>

            <div class="form-card">
                <div class="form-section-title">Panel Media (Layar Kiri TV)</div>

                <div class="mb-4">
                    <label class="ta-label font-semibold">Mode Tampilan</label>
                    <div class="flex gap-3">

                        <div class="media-mode-card <?= $settings['media_mode'] === 'video' ? 'selected' : '' ?>"
                            id="mode-video" onclick="selectMode('video')">
                            <input type="radio" name="media_mode" value="video" id="radio-video"
                                <?= $settings['media_mode'] === 'video' ? 'checked' : '' ?>
                            class="hidden" />
                            <i data-lucide="circle-play"></i>
                            <div class="font-semibold">Video</div>
                            <div class="text-gray-500 text-xs">MP4, WebM</div>
                        </div>

                        <div class="media-mode-card <?= $settings['media_mode'] === 'image' ? 'selected' : '' ?>"
                            id="mode-image" onclick="selectMode('image')">
                            <input type="radio" name="media_mode" value="image" id="radio-image"
                                <?= $settings['media_mode'] === 'image' ? 'checked' : '' ?>
                            class="hidden" />
                            <i data-lucide="image"></i>
                            <div class="font-semibold">Gambar</div>
                            <div class="text-gray-500 text-xs">JPG, PNG, WebP</div>
                        </div>

                    </div>
                </div>

                <div class="mb-3">
                    <label class="ta-label font-semibold" for="media_file">
                        Upload File Media
                    </label>
                    <input type="file" class="ta-input" id="media_file" name="media_file"
                        accept="video/mp4,video/webm,image/jpeg,image/png,image/webp" />
                    <div class="ta-help">
                        Maks. 50MB. File lama akan digantikan oleh file baru.
                    </div>
                </div>

                <?php if (!empty($settings['media_file'])): ?>
                    <div class="ta-alert ta-alert-gray ta-alert-sm py-2 px-3">
                        <i data-lucide="file-video" class="mr-1"></i>
                        File aktif saat ini:
                        <strong>
                            <?= esc(basename($settings['media_file'])) ?>
                        </strong>
                    </div>
                <?php else: ?>
                    <div class="ta-alert ta-alert-warning ta-alert-sm py-2 px-3">
                        <i data-lucide="triangle-alert" class="mr-1"></i>
                        Belum ada file media. Upload file untuk ditampilkan di layar TV.
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <div class="lg:col-span-4">
            <div class="form-card">

                <div class="form-section-title">Informasi Layar TV</div>

                <ul class="info-list">
                    <li><i data-lucide="monitor" class="text-brand-600 mr-2"></i>Resolusi: <strong>1920 × 1080</strong></li>
                    <li><i data-lucide="maximize" class="text-brand-600 mr-2"></i>Rasio: <strong>16:9</strong></li>
                    <li><i data-lucide="refresh-cw" class="text-brand-600 mr-2"></i>Refresh data: <strong>setiap 1
                            menit</strong></li>
                    <li><i data-lucide="wifi" class="text-brand-600 mr-2"></i>Koneksi: <strong>Jaringan lokal</strong></li>
                </ul>

                <hr />

                <div class="form-section-title">Tips</div>
                <ul class="tips-list">
                    <li>Gunakan video resolusi 1080p untuk kualitas terbaik</li>
                    <li>Running text disarankan tidak lebih dari 200 karakter</li>
                    <li>Perubahan tampil di layar TV dalam 1 menit setelah disimpan</li>
                    <li>Nonaktifkan running text jika tidak ada pengumuman</li>
                </ul>

            </div>
        </div>

    </div>

    <?php if (false): ?>
    <!-- Seksi BMKG -->
    <div class="form-card mt-3 mb-3">
        <div class="form-section-title">🌤️ Integrasi Cuaca BMKG</div>

        <div class="grid grid-cols-12 gap-4">
            <div class="lg:col-span-8">
                <label class="ta-label font-semibold" for="bmkg_adm4">
                    Kode Wilayah (ADM4 — Kelurahan/Desa)
                </label>
                <input type="text" class="ta-input font-mono" id="bmkg_adm4" name="bmkg_adm4"
                    value="<?= esc(env('BMKG_ADM4') ?: '72.71.01.1004') ?>"
                    placeholder="Contoh: 72.71.01.1004"
                    pattern="\d{2}\.\d{2}\.\d{2}\.\d{4}"
                    <?= env('BMKG_ADM4') ? 'readonly' : '' ?> />
                <?php if (env('BMKG_ADM4')): ?>
                    <div class="text-emerald-600 mt-1" style="font-size: 0.8rem;">
                        <i data-lucide="shield-lock" class="mr-1"></i> Terkunci via file <code>.env</code> (<code>BMKG_ADM4</code>).
                    </div>
                <?php endif; ?>
                <div class="ta-help">
                    <i data-lucide="info" class="mr-1"></i>
                    Format: <code>PP.KK.KC.LLLL</code> (Provinsi.Kab-Kota.Kecamatan.Kelurahan).<br/>
                    Kode saat ini merujuk ke: <strong id="bmkg-resolved-location">Memuat lokasi...</strong>.<br/>
                    Data diperbarui BMKG 2x sehari. Sistem men-cache respons selama <strong>30 menit</strong>.
                </div>
            </div>
            <div class="lg:col-span-4">
                <div class="ta-alert ta-alert-info py-2 px-3" style="font-size:.8rem;">
                    <i data-lucide="cloud-sun" class="mr-1"></i>
                    <strong>Wajib:</strong> Atribusi <em>Sumber: BMKG</em> ditampilkan otomatis
                    di layar signage sesuai syarat penggunaan API BMKG.
                </div>
            </div>
        </div>
    </div>

    <!-- Seksi WhatsApp Notification -->
    <div class="form-card mt-3 mb-3" id="wa-notif-card">
        <div class="flex items-center justify-between mb-3">
            <div class="form-section-title mb-0">📲 Notifikasi WhatsApp</div>
            <div class="flex items-center gap-2">
                <!-- Badge status realtime -->
                <span id="wa-status-badge" class="ta-badge <?= $settings['wa_notif_aktif'] ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500' ?> wa-status-badge">
                    <i data-lucide="circle" class="mr-1" style="width:.5rem;height:.5rem;vertical-align:middle;fill:currentColor;"></i>
                    <?= $settings['wa_notif_aktif'] ? 'Aktif' : 'Nonaktif' ?>
                </span>
                <!-- Toggle aktif/nonaktif -->
                <div class="ta-check ta-switch mb-0">
                    <input class="ta-check-input" type="checkbox" role="switch"
                        id="wa_notif_aktif" name="wa_notif_aktif" value="1"
                        <?= $settings['wa_notif_aktif'] ? 'checked' : '' ?>
                        onchange="updateWaBadge(this.checked)" />
                    <label class="ta-check-label font-semibold" for="wa_notif_aktif">
                        Aktifkan
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="lg:col-span-8">

                <!-- API Key -->
                <div class="mb-3">
                    <label class="ta-label font-semibold" for="wa_api_key">
                        Token API Fonnte
                        <?php if (!empty($settings['wa_from_env'])): ?>
                            <span class="ta-badge bg-emerald-50 ml-1" style="font-size:.65rem;">
                                <i data-lucide="shield-lock" class="mr-1"></i>.env
                            </span>
                        <?php endif; ?>
                    </label>
                    <div class="ta-input-group">
                        <span class="ta-input-addon"><i data-lucide="key-round"></i></span>
                        <input type="password" class="ta-input font-mono" id="wa_api_key"
                            name="wa_api_key"
                            value="<?= esc($settings['wa_api_key']) ?>"
                            placeholder="<?= !empty($settings['wa_from_env']) ? 'Dikonfigurasi via .env — tidak dapat diubah dari sini' : 'Masukkan token Fonnte Anda' ?>"
                            <?= !empty($settings['wa_from_env']) ? 'readonly' : '' ?> />
                        <?php if (empty($settings['wa_from_env'])): ?>
                            <button class="ta-btn ta-btn-outline-gray" type="button"
                                onclick="toggleWaKey()" id="wa-key-toggle-btn" title="Tampilkan/sembunyikan token">
                                <i data-lucide="eye" id="wa-key-eye"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($settings['wa_from_env'])): ?>
                        <div class="text-emerald-600 mt-1" style="font-size:.8rem;">
                            <i data-lucide="shield-lock" class="mr-1"></i>
                            Terkunci via <code>.env</code> (<code>WA_API_KEY</code>). Kosongkan env untuk mengatur dari sini.
                        </div>
                    <?php else: ?>
                        <div class="ta-help">
                            <i data-lucide="info" class="mr-1"></i>
                            Dapatkan token di <a href="https://fonnte.com" target="_blank" rel="noopener">fonnte.com</a>.
                            Biarkan kosong jika tidak ingin mengubah token yang sudah tersimpan.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sender Name -->
                <div class="mb-3">
                    <label class="ta-label font-semibold" for="wa_sender_name">
                        Nama Pengirim
                    </label>
                    <div class="ta-input-group">
                        <span class="ta-input-addon"><i data-lucide="contact"></i></span>
                        <input type="text" class="ta-input" id="wa_sender_name" name="wa_sender_name"
                            value="<?= esc($settings['wa_sender_name'] ?? 'Sekretariat DPRD') ?>"
                            placeholder="Contoh: Sekretariat DPRD" maxlength="60" />
                    </div>
                    <div class="ta-help">
                        Nama ini muncul di teks pesan undangan sebagai identitas pengirim.
                    </div>
                </div>

                <!-- Test kirim pesan -->
                <?php if (empty($settings['wa_from_env']) || !empty($settings['wa_api_key'])): ?>
                <div class="mb-1">
                    <label class="ta-label font-semibold" for="wa_test_number">
                        Uji Kirim Pesan
                    </label>
                    <div class="ta-input-group" style="max-width:380px;">
                        <span class="ta-input-addon"><i data-lucide="message-circle" class="text-emerald-600"></i></span>
                        <input type="text" class="ta-input font-mono" id="wa_test_number"
                            placeholder="628xxxxxxxxxx" maxlength="15" />
                        <button class="ta-btn ta-btn-outline-success" type="button"
                            onclick="kirimWaTest()" id="wa-test-btn">
                            <i data-lucide="send" class="mr-1"></i>Kirim Test
                        </button>
                    </div>
                    <div class="ta-help">Masukkan nomor format <code>628xxx</code> untuk menguji koneksi API Fonnte.</div>
                    <div id="wa-test-result" class="mt-2" style="display:none;"></div>
                </div>
                <?php endif; ?>

            </div>

            <div class="lg:col-span-4">
                <div class="wa-info-box">
                    <div class="wa-info-title">
                        <i data-lucide="message-circle" class="mr-1"></i> Cara Kerja
                    </div>
                    <ul class="tips-list mt-2 mb-0">
                        <li>Notifikasi dikirim otomatis saat layar signage aktif dan polling data jadwal setiap <strong>1 menit</strong></li>
                        <li>Pesan dikirim ke nomor WA anggota sesuai <strong>reminder_time</strong> jadwal</li>
                        <li>Status pengiriman dapat dilihat di menu <a href="<?= base_url('admin/notifikasi') ?>">Log Notifikasi</a></li>
                        <li>Gunakan <a href="https://fonnte.com" target="_blank" rel="noopener">Fonnte</a> sebagai gateway WhatsApp</li>
                    </ul>
                </div>

                <!-- Status koneksi API -->
                <div class="mt-3" id="wa-conn-status">
                    <div class="flex items-center gap-2 text-gray-500" style="font-size:.82rem;">
                        <span class="ta-spinner ta-spinner-sm" role="status"></span>
                        Memeriksa koneksi API...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <div class="flex gap-2 mt-3">
        <button type="submit" class="ta-btn ta-btn-primary">
            <i data-lucide="save" class="mr-1"></i>Simpan Semua Pengaturan
        </button>
    </div>

</form>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    .running-text-preview {
        background: #0f1f3d;
        border-radius: 8px;
        padding: 10px 16px;
        overflow: hidden;
    }

    .running-text-label {
        font-size: .7rem;
        color: rgba(255, 255, 255, .4);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .running-text-track {
        overflow: hidden;
        white-space: nowrap;
    }

    .running-text-track span {
        display: inline-block;
        color: #fbbf24;
        font-size: .9rem;
        font-weight: 600;
        animation: marquee-preview 12s linear infinite;
    }

    @keyframes marquee-preview {
        0% {
            transform: translateX(100%);
        }

        100% {
            transform: translateX(-100%);
        }
    }

    .media-mode-card {
        flex: 1;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-card);
        padding: 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--text-muted);
    }

    .media-mode-card .lucide,
    .media-mode-card [data-lucide] {
        width: 28px;
        height: 28px;
        display: block;
        margin-bottom: 6px;
    }

    .media-mode-card:hover {
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .media-mode-card.selected {
        border-color: #3b82f6;
        background: #eff6ff;
        color: #2563eb;
    }

    /* WhatsApp Notification Card */
    #wa-notif-card {
        border-left: 3px solid #25d366;
    }

    .wa-info-box {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border: 1px solid #86efac;
        border-radius: var(--radius-card);
        padding: 14px 16px;
    }

    .wa-info-title {
        font-weight: 700;
        color: #15803d;
        font-size: .85rem;
        display: flex;
        align-items: center;
    }

    .wa-info-title [data-lucide="message-circle"] {
        color: #25d366;
        font-size: 1rem;
    }

    .wa-status-badge {
        font-size: .75rem;
        padding: .35em .65em;
        transition: all .3s ease;
    }

    #wa-conn-status .conn-ok {
        color: #15803d;
        font-size: .82rem;
        font-weight: 600;
    }

    #wa-conn-status .conn-err {
        color: #dc2626;
        font-size: .82rem;
        font-weight: 600;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.getElementById('running_text')?.addEventListener('input', function () {
        const preview = document.getElementById('preview-text');
        preview.textContent = this.value || 'Teks berjalan akan tampil di sini...';
    });

    function selectTema(tema) {
        document.querySelectorAll('[id^="tema-"]').forEach(el => el.classList.remove('selected'));
        document.getElementById('tema-' + tema).classList.add('selected');
        document.getElementById('radio-tema-' + tema).checked = true;
    }

    function selectMode(mode) {
        document.querySelectorAll('.media-mode-card:not([id^="tema-"])').forEach(el => el.classList.remove('selected'));
        document.getElementById('mode-' + mode).classList.add('selected');
        document.getElementById('radio-' + mode).checked = true;
    }

    // Dynamic fetch resolved BMKG location
    document.addEventListener('DOMContentLoaded', function() {
        const locationEl = document.getElementById('bmkg-resolved-location');
        if (locationEl) {
            fetch('/api/signage/cuaca')
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success' && data.lokasi) {
                        const lok = data.lokasi;
                        const desa = lok.desa || '-';
                        const kec = lok.kecamatan || '-';
                        const kab = lok.kotkab || '-';
                        locationEl.innerHTML = `<strong>${desa}, ${kec}, ${kab}</strong>`;
                    } else {
                        locationEl.innerHTML = '<span class="text-red-600">Gagal mendeteksi lokasi. Pastikan kode ADM4 benar dan internet terhubung.</span>';
                    }
                })
                .catch(err => {
                    console.error('[Settings] Gagal memuat detail lokasi BMKG:', err);
                    locationEl.innerHTML = '<span class="text-red-600">Gagal memuat detail lokasi.</span>';
                });
        }

        // Auto-check koneksi Fonnte API
        checkWaConn();
    });

    // Toggle show/hide WA API key
    function toggleWaKey() {
        const input = document.getElementById('wa_api_key');
        const eye   = document.getElementById('wa-key-eye');
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            eye.setAttribute('data-lucide', 'eye-off');
        } else {
            input.type = 'password';
            eye.setAttribute('data-lucide', 'eye');
        }
        window.renderAdminIcons?.();
    }

    // Update badge Aktif/Nonaktif secara realtime saat toggle diubah
    function updateWaBadge(isChecked) {
        const badge = document.getElementById('wa-status-badge');
        if (!badge) return;
        if (isChecked) {
            badge.className = 'ta-badge bg-emerald-50 text-emerald-600 wa-status-badge';
            badge.innerHTML = '<i data-lucide="circle" class="mr-1" style="width:.5rem;height:.5rem;vertical-align:middle;fill:currentColor;"></i>Aktif';
        } else {
            badge.className = 'ta-badge bg-gray-100 text-gray-500 wa-status-badge';
            badge.innerHTML = '<i data-lucide="circle" class="mr-1" style="width:.5rem;height:.5rem;vertical-align:middle;fill:currentColor;"></i>Nonaktif';
        }
    }

    // Kirim pesan WA test via endpoint
    async function kirimWaTest() {
        const noWa   = document.getElementById('wa_test_number')?.value.trim();
        const result = document.getElementById('wa-test-result');
        const btn    = document.getElementById('wa-test-btn');

        if (!noWa || !/^62\d{8,13}$/.test(noWa)) {
            result.style.display = 'block';
            result.innerHTML = '<div class="ta-alert ta-alert-warning py-2 px-3 mb-0"><i data-lucide="triangle-alert" class="mr-1"></i>Nomor tidak valid. Gunakan format <code>628xxxxxxxxxx</code>.</div>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="ta-spinner ta-spinner-sm mr-1"></span>Mengirim...';
        result.style.display = 'none';

        try {
            const resp = await fetch('/admin/pengaturan/wa-test', {
                method : 'POST',
                headers: {
                    'Content-Type'     : 'application/x-www-form-urlencoded',
                    'X-Requested-With' : 'XMLHttpRequest',
                    'X-CSRF-TOKEN'     : document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                },
                body: new URLSearchParams({
                    no_wa            : noWa,
                    <?= csrf_token() ?>: document.querySelector('input[name="<?= csrf_token() ?>"]')?.value ?? ''
                })
            });
            const data = await resp.json();
            result.style.display = 'block';
            if (data.success) {
                result.innerHTML = '<div class="ta-alert ta-alert-success py-2 px-3 mb-0"><i data-lucide="circle-check" class="mr-1"></i>Pesan test berhasil dikirim ke <strong>' + noWa + '</strong>.</div>';
            } else {
                result.innerHTML = '<div class="ta-alert ta-alert-danger py-2 px-3 mb-0"><i data-lucide="circle-x" class="mr-1"></i>Gagal: ' + (data.error || 'Unknown error') + '</div>';
            }
        } catch(e) {
            result.style.display = 'block';
            result.innerHTML = '<div class="ta-alert ta-alert-danger py-2 px-3 mb-0"><i data-lucide="circle-x" class="mr-1"></i>Error jaringan: ' + e.message + '</div>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="send" class="mr-1"></i>Kirim Test';
        }
    }

    // Cek status koneksi Fonnte API (apakah token valid)
    async function checkWaConn() {
        const connEl = document.getElementById('wa-conn-status');
        if (!connEl) return;
        try {
            const resp = await fetch('/admin/pengaturan/wa-status', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await resp.json();
            if (data.configured && data.connected) {
                connEl.innerHTML = '<div class="conn-ok"><i data-lucide="circle-check" class="mr-1"></i>Token valid — terhubung ke Fonnte</div>';
            } else if (data.configured && !data.connected) {
                connEl.innerHTML = '<div class="conn-err"><i data-lucide="circle-x" class="mr-1"></i>Token tidak valid atau Fonnte tidak dapat dijangkau.<br><small class="text-gray-500">' + (data.error ?? '') + '</small></div>';
            } else {
                connEl.innerHTML = '<div class="text-gray-500" style="font-size:.82rem;"><i data-lucide="circle-minus" class="mr-1"></i>Token API belum dikonfigurasi.</div>';
            }
        } catch(e) {
            connEl.innerHTML = '<div class="text-gray-500" style="font-size:.82rem;"><i data-lucide="wifi-off" class="mr-1"></i>Tidak dapat memeriksa status koneksi.</div>';
        }
    }
</script>
<?= $this->endSection() ?>
