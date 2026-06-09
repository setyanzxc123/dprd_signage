<!DOCTYPE html>
<html lang="id" data-signage-theme="<?= esc($signageTema ?? 'dark') ?>">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Layar Informasi — DPRD Sulawesi Tengah</title>
    <meta name="robots" content="noindex, nofollow" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />

    <link href="<?= base_url('assets/css/signage.css') ?>" rel="stylesheet" />
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>

<body>

    <div id="app">

        <div id="panel-header">
            <div class="header-logo">
                <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>" alt="Logo DPRD Provinsi Sulawesi Tengah" class="logo-img" />
                <div class="logo-text">
                    <span class="logo-name">DPRD Provinsi</span>
                    <span class="logo-province">Sulawesi Tengah</span>
                </div>
            </div>

            <div class="header-meta">
                <div class="weather-widget">
                    <div class="weather-left-block">
                        <div class="weather-top-row">
                            <img v-if="cuaca.icon_url" :src="cuaca.icon_url" class="weather-img" alt="ikon cuaca" />
                            <span v-else class="weather-icon"></span>
                            <span class="weather-temp">{{ cuaca.suhu }}</span>
                        </div>
                        <div class="weather-cond">{{ cuaca.kondisi }}</div>
                    </div>
                    
                    <div class="weather-details">
                        <div class="weather-details-row">
                            <span class="weather-loc" v-if="cuaca.desa">{{ cuaca.desa }}, {{ cuaca.kecamatan }}</span>
                        </div>
                        <div class="weather-details-row">
                            <span class="weather-hum">💧 {{ cuaca.kelembapan }} &nbsp;&nbsp;💨 {{ cuaca.kec_angin }}</span>
                            <span class="weather-separator">·</span>
                            <span class="weather-src">Sumber: BMKG</span>
                        </div>
                    </div>
                </div>
                <div class="header-divider"></div>
                <div class="signage-date">
                    <div class="date-day">{{ dateDay }}</div>
                    <div class="date-full">{{ dateFull }}</div>
                </div>
                <div class="header-divider"></div>
                <div class="header-datetime">
                    <div class="signage-clock">{{ clock }}</div>
                    <div class="signage-timezone">WITA</div>
                </div>
            </div>
        </div>

        <div id="panel-media">
            <video v-if="media.mode === 'video' && media.url" :src="media.url" autoplay loop muted playsinline></video>
            <img v-else-if="media.mode === 'image' && media.url" :src="media.url" alt="Media Signage DPRD" />

            <!-- Panel QR — satu slot, slide bergantian jika keduanya ada -->
            <div class="qr-panel" v-if="qrBerkas || qrLive">
                <!-- Label dinamis -->
                <div class="qr-label" v-if="activeQR === 'berkas'">📥 Unduh Berkas Rapat</div>
                <div class="qr-label" v-else><span class="live-badge">🔴 LIVE</span>&nbsp;Tonton Siaran</div>
                <!-- Container QR tunggal -->
                <div id="qr-display" :class="{ 'qr-fading': qrFading }"></div>
                <!-- Dot indicator — hanya muncul jika keduanya ada -->
                <div v-if="qrBerkas && qrLive" class="qr-dots">
                    <span :class="['qr-dot', { active: activeQR === 'berkas' }]"></span>
                    <span :class="['qr-dot', { active: activeQR === 'live' }]"></span>
                </div>
            </div>
        </div>


        <div id="panel-info">
            <div class="signage-schedule">
                <div class="schedule-title">⬡ Agenda Rapat Hari Ini</div>

                <div v-if="jadwal.length === 0" class="schedule-empty">
                    <div class="empty-icon">📅</div>
                    <p>Tidak ada jadwal rapat hari ini</p>
                </div>

                <div v-for="item in jadwal" :key="item.id" :class="['schedule-item', item.status]">
                    <div class="item-time">
                        <div class="time-range">{{ item.waktu_mulai }} – {{ item.waktu_selesai }}</div>
                        <div class="time-room">{{ item.ruangan }}</div>
                    </div>
                    <div class="item-content">
                        <div class="item-title">{{ item.judul }}</div>
                        <div class="item-group">{{ item.komisi }}</div>
                    </div>
                    <div class="item-status">
                        <span :class="['status-pill', item.status]">
                            <span :class="['status-dot', item.status === 'berlangsung' ? 'pulse' : '']"></span>
                            {{ statusLabel(item.status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div id="panel-ticker" v-if="runningTextAktif">
            <div class="ticker-label">📢 Pengumuman</div>
            <div class="ticker-track">
                <span class="ticker-text">{{ runningText }}</span>
            </div>
        </div>

    </div>

    <script>
        const { createApp, ref, watch, nextTick, onMounted, onUnmounted } = Vue;

        createApp({
            setup() {


                const clock = ref('--:--:--');
                const dateDay = ref('');
                const dateFull = ref('');
                const jadwal = ref([]);
                const runningText = ref('<?= esc($runningText ?? 'Selamat datang di Gedung DPRD Provinsi Sulawesi Tengah') ?>');
                const runningTextAktif = ref(<?= ($runningTextAktif ?? false) ? 'true' : 'false' ?>);
                const media = ref({
                    mode: '<?= esc($mediaMode ?? 'video') ?>',
                    url:  '<?= esc($mediaUrl  ?? '') ?>',
                });

                const cuaca = ref({
                    suhu: '--°C',
                    kondisi: 'Memuat...',
                    kelembapan: '--%',
                    kec_angin: '-- km/j',
                    icon_url: '',
                    desa: '',
                    kecamatan: '',
                });

                const qrBerkas  = ref(false);
                const qrLive    = ref(false);
                const activeQR  = ref('berkas'); // 'berkas' | 'live'
                const qrFading  = ref(false);
                const activeJadwalId = ref(null);
                const BASE_URL  = '<?= rtrim(base_url(), '/') ?>';
                let qrSlideTimer = null;


                let clockTimer = null;

                function updateClock() {
                    const now = new Date();
                    const opts = { timeZone: 'Asia/Makassar', hour12: false };

                    clock.value = new Intl.DateTimeFormat('id-ID', {
                        ...opts, hour: '2-digit', minute: '2-digit', second: '2-digit'
                    }).format(now);

                    dateDay.value = new Intl.DateTimeFormat('id-ID', {
                        ...opts, weekday: 'long'
                    }).format(now).toUpperCase();

                    dateFull.value = new Intl.DateTimeFormat('id-ID', {
                        ...opts, day: 'numeric', month: 'long', year: 'numeric'
                    }).format(now);
                }


                function statusLabel(status) {
                    const map = {
                        berlangsung: 'Berlangsung',
                        persiapan: 'Persiapan',
                        menunggu: 'Menunggu',
                        selesai: 'Selesai',
                    };
                    return map[status] ?? status;
                }

                function makeQR(containerId, url, size = 120) {
                    nextTick(() => {
                        const container = document.getElementById(containerId);
                        if (!container) return;
                        container.innerHTML = '';
                        if (url) {
                            const theme = document.documentElement.getAttribute('data-signage-theme') || 'dark';
                            new QRCode(container, {
                                text: url,
                                width: size,
                                height: size,
                                colorDark:  theme === 'dark' ? '#ffffff' : '#1e293b',
                                colorLight: 'transparent',
                            });
                        }
                    });
                }

                // Render QR ke container tunggal #qr-display
                function renderActiveQR() {
                    if (!activeJadwalId.value || (!qrBerkas.value && !qrLive.value)) {
                        makeQR('qr-display', '', 110);
                        return;
                    }

                    const url = `${BASE_URL}/go/jadwal/${activeJadwalId.value}/${activeQR.value}`;
                    makeQR('qr-display', url, 110);
                }

                // Fade-out → ganti → fade-in
                function switchQR() {
                    qrFading.value = true;
                    setTimeout(() => {
                        activeQR.value = activeQR.value === 'berkas' ? 'live' : 'berkas';
                        renderActiveQR();
                        setTimeout(() => { qrFading.value = false; }, 50);
                    }, 300);
                }

                // Kelola slide timer berdasarkan ketersediaan QR
                function syncQrSlide() {
                    clearInterval(qrSlideTimer);
                    qrSlideTimer = null;

                    if (!qrBerkas.value && !qrLive.value) {
                        makeQR('qr-display', '', 110);
                        return;
                    }

                    if (qrBerkas.value && qrLive.value) {
                        // Keduanya ada — jalankan slide setiap 8 detik
                        qrSlideTimer = setInterval(switchQR, 8000);
                    }
                    // Pastikan activeQR valid (jika salah satu hilang)
                    if (!qrBerkas.value && activeQR.value === 'berkas') activeQR.value = 'live';
                    if (!qrLive.value   && activeQR.value === 'live')   activeQR.value = 'berkas';

                    renderActiveQR();
                }

                function loadData() {
                    fetch('/api/signage/jadwal')
                        .then(r => r.json())
                        .then(data => {
                            jadwal.value = data.jadwal ?? [];
                            const aktif  = jadwal.value.find(j => j.status === 'berlangsung');
                            activeJadwalId.value = aktif?.id ?? null;
                            qrBerkas.value = !!(aktif?.materi_url);
                            qrLive.value   = !!(aktif?.stream_url);
                            syncQrSlide();
                        })
                        .catch(err => console.error('[Signage] Gagal ambil data jadwal:', err));
                }

                function loadCuaca() {
                    fetch('/api/signage/cuaca')
                        .then(r => r.json())
                        .then(data => {
                            if (data.status === 'success' && data.cuaca) {
                                cuaca.value = {
                                    suhu:       data.cuaca.suhu,
                                    kondisi:    data.cuaca.kondisi,
                                    kelembapan: data.cuaca.kelembapan,
                                    kec_angin:  data.cuaca.kec_angin,
                                    icon_url:   data.cuaca.icon_url,
                                    desa:       data.lokasi?.desa || '',
                                    kecamatan:  data.lokasi?.kecamatan || '',
                                };
                            }
                        })
                        .catch(err => console.error('[Signage] Gagal ambil cuaca BMKG:', err));
                }

                let dataTimer = null;

                onMounted(() => {
                    updateClock();
                    clockTimer = setInterval(updateClock, 1000);

                    loadData();
                    loadCuaca();
                    dataTimer = setInterval(loadData, 60000);
                    // Cuaca refresh setiap 15 menit (cache BMKG 30 menit)
                    setInterval(loadCuaca, 900000);
                });

                onUnmounted(() => {
                    clearInterval(clockTimer);
                    clearInterval(dataTimer);
                    clearInterval(qrSlideTimer);
                });

                return {
                    clock, dateDay, dateFull,
                    cuaca, qrBerkas, qrLive, activeQR, qrFading,
                    jadwal, runningText, runningTextAktif, media,
                    statusLabel
                };
            }
        }).mount('#app');
    </script>

</body>

</html>
