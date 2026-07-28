<?php
$signageCssVersion = is_file(FCPATH . 'assets/css/signage.css') ? filemtime(FCPATH . 'assets/css/signage.css') : time();
$fontVersion       = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$vueVersion        = is_file(FCPATH . 'assets/vendor/vue/vue.global.prod.js') ? filemtime(FCPATH . 'assets/vendor/vue/vue.global.prod.js') : time();
$qrcodeVersion     = is_file(FCPATH . 'assets/vendor/qrcodejs/qrcode.min.js') ? filemtime(FCPATH . 'assets/vendor/qrcodejs/qrcode.min.js') : time();
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= esc($signageTema ?? 'dark') ?>">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Layar Informasi - DPRD Sulawesi Tengah</title>
    <meta name="robots" content="noindex, nofollow" />

    <link rel="icon" type="image/jpeg" href="<?= base_url('assets/images/logo_dprd.jpg') ?>" />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <link href="<?= base_url('assets/css/signage.css?v=' . $signageCssVersion) ?>" rel="stylesheet" />
    <script src="<?= base_url('assets/vendor/vue/vue.global.prod.js?v=' . $vueVersion) ?>"></script>
    <script src="<?= base_url('assets/vendor/qrcodejs/qrcode.min.js?v=' . $qrcodeVersion) ?>"></script>
</head>

<body class="bg-base-200 text-base-content">

    <div id="app" v-cloak>

        <header id="panel-header" class="navbar border-b border-base-300 bg-base-100">
            <div class="navbar-start min-w-0 gap-[1vw]">
                <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>"
                    alt="Logo DPRD Provinsi Sulawesi Tengah"
                    class="h-[clamp(52px,9vh,112px)] w-auto rounded-box object-contain" />
                <div class="min-w-0 leading-tight">
                    <div class="text-[clamp(17px,1.08vw,24px)] font-bold uppercase tracking-[0.08em]">
                        DPRD Provinsi
                    </div>
                    <div class="text-[clamp(13px,0.82vw,18px)] uppercase tracking-[0.08em] text-base-content/70">
                        Sulawesi Tengah
                    </div>
                </div>
            </div>

            <div class="navbar-end w-auto min-w-max">
                <div class="stats stats-horizontal border border-base-300 bg-base-200 shadow-sm">
                    <div class="stat place-items-center px-[1vw] py-[0.7vh]">
                        <div class="stat-value flex items-center gap-[0.4vw] text-[clamp(20px,1.2vw,28px)]">
                            <img v-if="cuaca.icon_url" :src="cuaca.icon_url"
                                class="h-[clamp(24px,1.8vw,34px)] w-[clamp(24px,1.8vw,34px)] object-contain"
                                alt="Ikon cuaca" />
                            <span v-else class="status status-info status-lg"></span>
                            <span>{{ cuaca.suhu }}</span>
                        </div>
                        <div class="stat-desc text-[clamp(11px,0.68vw,14px)] font-semibold">
                            {{ cuaca.kondisi }}
                        </div>
                    </div>

                    <div class="stat px-[1vw] py-[0.7vh]">
                        <div class="stat-title text-[clamp(11px,0.72vw,15px)] font-bold" v-if="cuaca.desa">
                            {{ cuaca.desa }}, {{ cuaca.kecamatan }}
                        </div>
                        <div class="stat-value mt-[0.25vh] text-[clamp(10px,0.62vw,13px)] font-medium">
                            Kelembapan {{ cuaca.kelembapan }} · Angin {{ cuaca.kec_angin }}
                        </div>
                        <div class="stat-desc text-[clamp(9px,0.58vw,12px)] italic">
                            Sumber: BMKG
                        </div>
                    </div>

                    <div class="stat place-items-center px-[1.4vw] py-[0.7vh] text-center">
                        <div class="stat-title text-[clamp(11px,0.72vw,15px)] font-bold uppercase tracking-[0.12em]">
                            {{ dateDay }}
                        </div>
                        <div class="stat-value text-[clamp(14px,1vw,20px)]">{{ dateFull }}</div>
                    </div>

                    <div class="stat place-items-center px-[1.4vw] py-[0.7vh] text-center">
                        <div class="stat-value font-mono text-[clamp(34px,3vw,60px)] tabular-nums leading-none">
                            {{ clock }}
                        </div>
                        <div class="stat-desc mt-[0.25vh] uppercase tracking-[0.18em]">WITA</div>
                    </div>
                </div>
            </div>
        </header>

        <section id="panel-media" class="card rounded-none bg-neutral">
            <canvas ref="mediaBackdrop" class="media-bg" v-if="media.mode === 'video' && media.url"
                aria-hidden="true"></canvas>
            <img class="media-bg" v-if="media.mode === 'image' && media.url"
                :src="media.url" alt="" aria-hidden="true" />
            <video ref="mediaVideo" class="media-main" v-if="media.mode === 'video' && media.url"
                :src="media.url" autoplay loop muted playsinline preload="auto"
                @loadedmetadata="ensureMediaPlayback" @canplay="ensureMediaPlayback"
                @playing="handleMediaPlaying" @stalled="ensureMediaPlayback" @error="handleMediaError">
            </video>
            <img class="media-main" v-else-if="media.mode === 'image' && media.url"
                :src="media.url" alt="Media Signage DPRD"
                @load="mediaError = false" @error="handleMediaError" />

            <div v-if="!media.url || mediaError"
                class="media-state card-body absolute inset-0 z-[2] items-center justify-center text-center text-neutral-content">
                <span class="badge badge-warning">Media tidak tersedia</span>
                <p class="max-w-[28vw] text-[clamp(11px,0.75vw,15px)] text-neutral-content/70">
                    Periksa file media pada Pengaturan Sistem.
                </p>
            </div>

            <aside class="qr-panel card card-border bg-base-100/90 shadow-xl backdrop-blur-md"
                v-if="qrBerkas || qrLive">
                <div class="card-body items-center gap-[0.6vh] p-[clamp(10px,1vw,18px)]">
                    <div class="card-title text-[clamp(10px,0.65vw,13px)] uppercase tracking-[0.1em] text-base-content/60"
                        v-if="activeQR === 'berkas'">
                        Unduh Berkas Rapat
                    </div>
                    <div class="card-title gap-[0.35vw] text-[clamp(10px,0.65vw,13px)] uppercase tracking-[0.1em] text-base-content/60"
                        v-else>
                        <span class="badge badge-error badge-sm">LIVE</span>
                        Tonton Siaran
                    </div>
                    <div id="qr-display" :class="{ 'qr-fading': qrFading }"></div>
                    <div v-if="qrBerkas && qrLive" class="flex gap-1">
                        <span :class="['status status-xs', activeQR === 'berkas' ? 'status-primary' : 'status-neutral']"></span>
                        <span :class="['status status-xs', activeQR === 'live' ? 'status-primary' : 'status-neutral']"></span>
                    </div>
                </div>
            </aside>
        </section>

        <section id="panel-info" class="card rounded-none bg-base-200">
            <div class="card-body signage-schedule gap-0">
                <h2 class="card-title border-b border-base-300 pb-[0.8vh] text-[clamp(11px,0.75vw,15px)] uppercase tracking-[0.14em] text-base-content/70">
                    Agenda Rapat Hari Ini
                </h2>

                <div v-if="jadwal.length === 0 && upcoming.length === 0"
                    class="flex flex-1 flex-col items-center justify-center gap-2 text-base-content/35">
                    <span class="text-[3vw] leading-none">—</span>
                    <p class="text-[clamp(12px,0.9vw,18px)]">Tidak ada jadwal rapat hari ini</p>
                </div>

                <div v-else-if="jadwal.length === 0"
                    class="py-[1vh] text-[clamp(11px,0.78vw,15px)] text-base-content/65">
                    Tidak ada jadwal rapat hari ini
                </div>

                <ul v-if="jadwal.length > 0" class="list mt-[1vh] gap-[0.8vh] p-0">
                    <li v-for="item in jadwal" :key="item.id"
                        :class="['list-row grid-cols-[8vw_minmax(0,1fr)_auto] gap-[1.2vw] border border-base-300 bg-base-100 px-[1.4vw] py-[1.2vh] shadow-sm', scheduleItemClasses(item.status)]">
                        <div>
                            <div class="text-[1.1vw] font-bold tabular-nums text-primary">
                                {{ item.waktu_mulai }} - {{ item.waktu_selesai }}
                            </div>
                            <div class="mt-0.5 text-[0.75vw] text-base-content/65">{{ item.ruangan }}</div>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[clamp(17px,1.18vw,28px)] font-bold leading-tight">
                                {{ item.judul }}
                            </div>
                            <div class="mt-0.5 text-[0.75vw] text-base-content/65">{{ item.komisi }}</div>
                        </div>
                        <div class="self-center">
                            <span :class="statusClasses(item.status)">
                                <span :class="statusDotClasses(item.status)"></span>
                                {{ statusLabel(item.status) }}
                            </span>
                        </div>
                    </li>
                </ul>

                <div v-if="upcoming.length > 0" class="upcoming-section">
                    <h2 class="card-title border-b border-base-300 pb-[0.8vh] text-[clamp(11px,0.75vw,15px)] uppercase tracking-[0.14em] text-base-content/70">
                        Agenda Berikutnya
                    </h2>

                    <ul class="list mt-[0.8vh] gap-[0.55vh] p-0">
                        <li v-for="item in upcoming" :key="'upcoming-' + item.id"
                            class="list-row grid-cols-[8.2vw_minmax(0,1fr)_auto] gap-[0.9vw] border border-base-300 bg-base-100 px-[1.05vw] py-[0.85vh] shadow-sm">
                            <div>
                                <div class="text-[0.62vw] font-bold uppercase tracking-[0.1em] text-base-content/65">
                                    {{ upcomingDateLabel(item.tanggal) }}
                                </div>
                                <div class="text-[0.9vw] font-bold tabular-nums text-primary">
                                    {{ item.waktu_mulai }} - {{ item.waktu_selesai }}
                                </div>
                                <div class="text-[0.68vw] text-base-content/65">{{ item.ruangan }}</div>
                            </div>
                            <div class="min-w-0">
                                <div class="text-[clamp(14px,0.92vw,20px)] font-bold leading-tight">
                                    {{ item.judul }}
                                </div>
                                <div class="mt-0.5 text-[0.68vw] text-base-content/65">{{ item.komisi }}</div>
                            </div>
                            <div class="self-center">
                                <span class="badge badge-info gap-1 text-[0.7vw] font-bold uppercase tracking-wide">
                                    <span class="status status-info status-xs"></span>
                                    Mendatang
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <div id="panel-ticker" class="alert alert-horizontal rounded-none border-x-0 border-b-0 border-base-300 bg-neutral p-0 text-neutral-content"
            role="status" v-if="runningTextAktif">
            <span class="badge badge-primary h-full rounded-none border-0 px-[1.2vw] text-[0.75vw] font-bold uppercase tracking-[0.12em]">
                Pengumuman
            </span>
            <div class="ticker-track min-w-0 flex-1 overflow-hidden">
                <span class="ticker-text">{{ runningText }}</span>
            </div>
        </div>

    </div>

    <script {csp-script-nonce}>
        const { createApp, ref, watch, nextTick, onMounted, onUnmounted } = Vue;

        createApp({
            setup() {


                const clock = ref('--:--:--');
                const dateDay = ref('');
                const dateFull = ref('');
                const jadwal = ref([]);
                const upcoming = ref([]);
                const runningText = ref('<?= esc($runningText ?? 'Selamat datang di Gedung DPRD Provinsi Sulawesi Tengah') ?>');
                const runningTextAktif = ref(<?= ($runningTextAktif ?? false) ? 'true' : 'false' ?>);
                const media = ref({
                    mode: '<?= esc($mediaMode ?? 'video') ?>',
                    url:  '<?= esc($mediaUrl  ?? '') ?>',
                });
                const mediaVideo = ref(null);
                const mediaBackdrop = ref(null);
                const mediaError = ref(false);

                const cuaca = ref({
                    suhu: '-- C',
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
                let dataTimer = null;
                let weatherTimer = null;
                let mediaWatchTimer = null;
                let mediaBackdropFrame = null;
                let lastBackdropPaint = 0;

                function paintMediaBackdrop(timestamp = 0) {
                    const video = mediaVideo.value;
                    const canvas = mediaBackdrop.value;

                    if (!video || !canvas || media.value.mode !== 'video') {
                        mediaBackdropFrame = null;
                        return;
                    }

                    if (timestamp - lastBackdropPaint >= 66 && video.readyState >= 2 && video.videoWidth > 0) {
                        const bounds = canvas.getBoundingClientRect();
                        if (bounds.width <= 0 || bounds.height <= 0) {
                            mediaBackdropFrame = requestAnimationFrame(paintMediaBackdrop);
                            return;
                        }
                        const canvasWidth = Math.max(1, Math.min(720, Math.round(bounds.width)));
                        const canvasHeight = Math.max(1, Math.round(canvasWidth * bounds.height / bounds.width));

                        if (canvas.width !== canvasWidth || canvas.height !== canvasHeight) {
                            canvas.width = canvasWidth;
                            canvas.height = canvasHeight;
                        }

                        const context = canvas.getContext('2d', { alpha: false });
                        if (context) {
                            const scale = Math.max(
                                canvas.width / video.videoWidth,
                                canvas.height / video.videoHeight
                            );
                            const sourceWidth = canvas.width / scale;
                            const sourceHeight = canvas.height / scale;
                            const sourceX = (video.videoWidth - sourceWidth) / 2;
                            const sourceY = (video.videoHeight - sourceHeight) / 2;

                            context.drawImage(
                                video,
                                sourceX,
                                sourceY,
                                sourceWidth,
                                sourceHeight,
                                0,
                                0,
                                canvas.width,
                                canvas.height
                            );
                        }

                        lastBackdropPaint = timestamp;
                    }

                    mediaBackdropFrame = requestAnimationFrame(paintMediaBackdrop);
                }

                function startMediaBackdrop() {
                    if (mediaBackdropFrame !== null) return;
                    lastBackdropPaint = 0;
                    mediaBackdropFrame = requestAnimationFrame(paintMediaBackdrop);
                }

                function stopMediaBackdrop() {
                    if (mediaBackdropFrame !== null) {
                        cancelAnimationFrame(mediaBackdropFrame);
                        mediaBackdropFrame = null;
                    }
                }

                function ensureMediaPlayback(event = null) {
                    const video = event?.currentTarget instanceof HTMLVideoElement
                        ? event.currentTarget
                        : mediaVideo.value;

                    if (!video || media.value.mode !== 'video') return;

                    video.muted = true;
                    const playback = video.play();
                    if (playback && typeof playback.catch === 'function') {
                        playback.catch((error) => {
                            console.warn('[Signage] Autoplay media tertunda, akan dicoba ulang:', error);
                        });
                    }
                }

                function handleMediaError(event) {
                    stopMediaBackdrop();
                    mediaError.value = true;
                    console.error('[Signage] Media gagal dimuat:', event?.currentTarget?.error ?? event);
                }

                function handleMediaPlaying() {
                    mediaError.value = false;
                    startMediaBackdrop();
                }

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

                function statusClasses(status) {
                    const map = {
                        berlangsung: 'badge badge-error gap-1 text-[0.7vw] font-bold uppercase tracking-wide',
                        persiapan: 'badge badge-warning gap-1 text-[0.7vw] font-bold uppercase tracking-wide',
                        menunggu: 'badge badge-neutral gap-1 text-[0.7vw] font-bold uppercase tracking-wide',
                        selesai: 'badge badge-success gap-1 text-[0.7vw] font-bold uppercase tracking-wide',
                    };
                    return map[status] ?? 'badge gap-1 text-[0.7vw] font-bold uppercase tracking-wide';
                }

                function statusDotClasses(status) {
                    const map = {
                        berlangsung: 'status status-error status-xs pulse',
                        persiapan: 'status status-warning status-xs',
                        menunggu: 'status status-neutral status-xs',
                        selesai: 'status status-success status-xs',
                    };
                    return map[status] ?? 'status status-xs';
                }

                function scheduleItemClasses(status) {
                    const map = {
                        berlangsung: 'border-error/30 bg-error/10',
                        selesai: 'opacity-50',
                    };
                    return map[status] ?? '';
                }

                function parseDateOnly(ymd) {
                    const parts = String(ymd || '').split('-').map(Number);
                    if (parts.length !== 3 || parts.some(Number.isNaN)) return null;
                    return new Date(parts[0], parts[1] - 1, parts[2]);
                }

                function upcomingDateLabel(ymd) {
                    const date = parseDateOnly(ymd);
                    if (!date) return '';

                    const now = new Date();
                    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    const diffDays = Math.round((date - today) / 86400000);

                    if (diffDays === 1) return 'Besok';

                    return new Intl.DateTimeFormat('id-ID', {
                        weekday: 'short',
                        day: 'numeric',
                        month: 'short',
                    }).format(date);
                }

                function makeQR(containerId, url, size = 120) {
                    nextTick(() => {
                        const container = document.getElementById(containerId);
                        if (!container) return;
                        container.innerHTML = '';
                        if (url) {
                            const theme = document.documentElement.getAttribute('data-theme') || 'dark';
                            const themeStyles = getComputedStyle(document.documentElement);
                            const qrColor = themeStyles.getPropertyValue('--color-base-content').trim();
                            new QRCode(container, {
                                text: url,
                                width: size,
                                height: size,
                                colorDark: qrColor || (theme === 'dark' ? '#ffffff' : '#1f2937'),
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

                // Fade-out, ganti QR, lalu fade-in
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
                        // Keduanya ada - jalankan slide setiap 8 detik
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
                            upcoming.value = data.upcoming ?? [];
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

                onMounted(() => {
                    updateClock();
                    clockTimer = setInterval(updateClock, 1000);

                    loadData();
                    loadCuaca();
                    dataTimer = setInterval(loadData, 60000);
                    // Cuaca refresh setiap 15 menit (cache BMKG 30 menit)
                    weatherTimer = setInterval(loadCuaca, 900000);

                    nextTick(ensureMediaPlayback);
                    mediaWatchTimer = setInterval(() => {
                        const video = mediaVideo.value;
                        if (video && (video.paused || video.ended)) {
                            ensureMediaPlayback();
                        }
                    }, 5000);
                });

                onUnmounted(() => {
                    clearInterval(clockTimer);
                    clearInterval(dataTimer);
                    clearInterval(weatherTimer);
                    clearInterval(mediaWatchTimer);
                    clearInterval(qrSlideTimer);
                    stopMediaBackdrop();
                });

                return {
                    clock, dateDay, dateFull,
                    cuaca, qrBerkas, qrLive, activeQR, qrFading,
                    jadwal, upcoming, runningText, runningTextAktif, media,
                    mediaVideo, mediaBackdrop, mediaError,
                    ensureMediaPlayback, handleMediaPlaying, handleMediaError,
                    statusLabel, statusClasses, statusDotClasses, scheduleItemClasses, upcomingDateLabel
                };
            }
        }).mount('#app');
    </script>

</body>

</html>
