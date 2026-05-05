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
                    <span class="weather-icon">{{ cuaca.ikon }}</span>
                    <div>
                        <div class="weather-temp">{{ cuaca.suhu }}</div>
                        <div class="weather-cond">{{ cuaca.kondisi }}</div>
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

            <div class="qr-panel" v-if="qrTarget">
                <div class="qr-label">📥 Unduh Materi Rapat</div>
                <div id="qr-container"></div>
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
                    suhu: '28°C',
                    kondisi: 'Cerah Berawan',
                    ikon: '⛅',
                });

                const qrTarget = ref('');


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

                function renderQR(url) {
                    nextTick(() => {
                        const container = document.getElementById('qr-container');
                        if (!container) return;
                        container.innerHTML = '';
                        if (url) {
                            const theme = document.documentElement.getAttribute('data-signage-theme') || 'dark';
                            new QRCode(container, {
                                text: url,
                                width: 128,
                                height: 128,
                                colorDark:  theme === 'dark' ? '#ffffff' : '#1e293b',
                                colorLight: 'transparent',
                            });
                        }
                    });
                }

                watch(qrTarget, (url) => renderQR(url));

                function loadData() {
                    fetch('<?= base_url('api/signage/jadwal') ?>')
                        .then(r => r.json())
                        .then(data => {
                            jadwal.value = data.jadwal ?? [];
                            const berlangsung = jadwal.value.find(j => j.status === 'berlangsung');
                            qrTarget.value = berlangsung?.materi_url ?? '';
                        })
                        .catch(err => console.error('[Signage] Gagal ambil data jadwal:', err));
                }

                let dataTimer = null;

                onMounted(() => {
                    updateClock();
                    clockTimer = setInterval(updateClock, 1000);

                    loadData();
                    dataTimer = setInterval(loadData, 60000);
                });

                onUnmounted(() => {
                    clearInterval(clockTimer);
                    clearInterval(dataTimer);
                });

                return {
                    clock, dateDay, dateFull,
                    cuaca, qrTarget,
                    jadwal, runningText, runningTextAktif, media,
                    statusLabel
                };
            }
        }).mount('#app');
    </script>

</body>

</html>