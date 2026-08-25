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

            <div class="ondebox" aria-hidden="true">
                <svg class="onde" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28"
                    preserveAspectRatio="none" shape-rendering="auto">
                    <defs>
                        <path id="onda"
                            d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352Z" />
                    </defs>
                    <g class="parallaxonde">
                        <use href="#onda" x="48" y="0" fill="rgba(17,170,159,0.6)" />
                        <use href="#onda" x="48" y="3" fill="rgba(17,170,159,0.4)" />
                        <use href="#onda" x="48" y="5" fill="rgba(17,170,159,0.1)" />
                        <use href="#onda" x="48" y="7" fill="#0b6e67" />
                    </g>
                </svg>
            </div>
        </header>

        <section id="panel-media" class="card rounded-none bg-neutral">
            <canvas ref="mediaBackdrop" class="media-bg" v-if="media.mode === 'video' && media.url"
                aria-hidden="true"></canvas>
            <img class="media-bg" v-if="media.mode === 'image' && media.url"
                :src="media.url" alt="" aria-hidden="true" />
            <video ref="mediaVideo" class="media-main" v-if="media.mode === 'video' && media.url"
                :src="media.url" crossorigin="anonymous" autoplay loop muted playsinline preload="auto"
                @loadedmetadata="ensureMediaPlayback" @canplay="ensureMediaPlayback"
                @timeupdate="handleMediaProgress" @playing="handleMediaPlaying"
                @waiting="handleMediaWaiting" @stalled="handleMediaWaiting"
                @ended="handleMediaEnded" @error="handleMediaError">
            </video>
            <img class="media-main" v-else-if="media.mode === 'image' && media.url"
                :src="media.url" alt="Media Signage DPRD"
                @load="handleMediaImageLoaded" @error="handleMediaError" />

            <div v-if="(!media.url && !mediaStatusPending) || mediaError"
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
                    Agenda Hari Ini
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
                                {{ item.waktu_mulai ? item.waktu_mulai + (item.waktu_selesai ? ' - ' + item.waktu_selesai : '') : 'Sepanjang hari' }}
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
                                    {{ item.waktu_mulai ? item.waktu_mulai + (item.waktu_selesai ? ' - ' + item.waktu_selesai : '') : 'Sepanjang hari' }}
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
                const configuredMediaMode = '<?= esc($mediaMode ?? 'video') ?>';
                const configuredMediaUrl = '<?= esc($mediaUrl ?? '') ?>';
                const waitForCachedMediaStatus = 'serviceWorker' in navigator
                    && navigator.serviceWorker.controller;
                const media = ref({
                    mode: configuredMediaMode,
                    url: waitForCachedMediaStatus ? '' : configuredMediaUrl,
                });
                const mediaVideo = ref(null);
                const mediaBackdrop = ref(null);
                const mediaError = ref(false);
                const mediaStatusPending = ref(Boolean(waitForCachedMediaStatus));
                const mediaOfflineStatus = ref(configuredMediaUrl ? 'checking' : 'unavailable');
                const mediaOfflineSize = ref(0);
                const mediaOfflineFallbackUrl = ref('');
                const mediaOfflineFallbackMode = ref('');
                const storagePersistent = ref(false);
                const connectionStatus = ref(navigator.onLine === false ? 'offline' : 'online');
                const lastSyncAt = ref('');

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
                const SIGNAGE_WORKER_VERSION = '<?= esc((string) ($signageWorkerVersion ?? '1'), 'js') ?>';
                const SNAPSHOT_MAX_AGE_MS = 24 * 60 * 60 * 1000;
                const SNAPSHOT_KEYS = {
                    schedule: 'dprd-signage:snapshot:schedule:v1',
                    weather: 'dprd-signage:snapshot:weather:v1',
                };
                const DIAGNOSTICS_KEY = 'dprd-signage:diagnostics:v1';
                let qrSlideTimer = null;


                let clockTimer = null;
                let dataTimer = null;
                let weatherTimer = null;
                let mediaWatchTimer = null;
                let mediaBackdropFrame = null;
                let lastBackdropPaint = 0;
                let lastMediaCurrentTime = 0;
                let lastMediaProgressAt = Date.now();
                let mediaRecoveryAttempts = 0;
                let mediaRecoveryTimer = null;
                let mediaRecoveryInProgress = false;
                let mediaWaitingForConnection = false;
                let mediaStableSince = 0;
                let mediaCacheWarmupStartedAt = Date.now();
                let mediaPreparationRequested = false;
                let mediaStatusFallbackTimer = null;
                let stagedMediaUrl = '';
                let stagedMediaMode = '';
                let stagedMediaActivationRequested = false;
                let stagedMediaValidationId = 0;
                let pendingMediaCommitUrl = '';
                let pendingMediaCommitSince = 0;
                let mediaCommitRequested = false;
                let mediaCommitTimer = null;
                let mediaRecoveryTotal = 0;
                let mediaWorkerProtocolVersion = 0;
                let diagnosticsTimer = null;
                let dataRequestInFlight = false;
                let weatherRequestInFlight = false;
                let dataRefreshQueued = false;
                let weatherRefreshQueued = false;
                let reconnectRecoveryActive = false;
                let reconnectRetryAttempt = 0;
                let reconnectRetryTimer = null;
                let signageWorkerRegistration = null;
                let waitingServiceWorker = null;
                let workerUpdateTimer = null;
                let workerUpdateActivationRequested = false;
                const apiHealth = { schedule: 'unknown', weather: 'unknown' };
                const MEDIA_STALL_THRESHOLD_MS = 15000;
                const MEDIA_RECOVERY_COOLDOWN_MS = 30000;
                const MEDIA_MAX_RECOVERY_ATTEMPTS = 3;
                const MEDIA_CACHE_MIN_STABLE_MS = 15000;
                const MEDIA_CACHE_MIN_BUFFER_SECONDS = 20;
                const MEDIA_CACHE_MAX_WAIT_MS = 60000;
                const MEDIA_STAGED_VALIDATION_TIMEOUT_MS = 15000;
                const MEDIA_COMMIT_STABLE_MS = 10000;
                const RECONNECT_RETRY_DELAYS_MS = [5000, 15000, 30000, 60000, 120000];

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

                function recordMediaPlaybackStatus(status, error = '') {
                    document.documentElement.dataset.mediaPlaybackStatus = status;
                    if (error) {
                        document.documentElement.dataset.mediaLastError = String(error).slice(0, 500);
                        document.documentElement.dataset.mediaLastErrorAt = new Date().toISOString();
                    }
                    persistSignageDiagnostics();
                }

                function persistSignageDiagnostics() {
                    const state = document.documentElement.dataset;
                    try {
                        localStorage.setItem(DIAGNOSTICS_KEY, JSON.stringify({
                            savedAt: new Date().toISOString(),
                            connectionStatus: connectionStatus.value,
                            scheduleApi: apiHealth.schedule,
                            weatherApi: apiHealth.weather,
                            lastSyncAt: lastSyncAt.value || '',
                            mediaOfflineStatus: mediaOfflineStatus.value,
                            mediaPlaybackStatus: state.mediaPlaybackStatus || '',
                            mediaActiveUrl: state.mediaActiveUrl || '',
                            mediaStagedUrl: state.mediaStagedUrl || '',
                            mediaActivationStatus: state.mediaActivationStatus || '',
                            mediaRecoveryCount: Number(state.mediaRecoveryCount) || 0,
                            mediaLastRecoveryAt: state.mediaLastRecoveryAt || '',
                            mediaLastRecoveryReason: state.mediaLastRecoveryReason || '',
                            mediaLastError: state.mediaLastError || '',
                            mediaLastErrorAt: state.mediaLastErrorAt || '',
                            mediaBufferedSeconds: Number(state.mediaBufferedSeconds) || 0,
                            storageUsageBytes: Number(state.storageUsageBytes) || 0,
                            storageQuotaBytes: Number(state.storageQuotaBytes) || 0,
                            storagePersistent: storagePersistent.value,
                            workerVersion: SIGNAGE_WORKER_VERSION,
                            workerProtocol: mediaWorkerProtocolVersion,
                        }));
                    } catch (error) {
                        console.warn('[Signage] Diagnostik lokal gagal disimpan:', error);
                    }
                }

                function canonicalClientMediaUrl(value) {
                    if (!value) return '';
                    try {
                        const url = new URL(value, window.location.origin);
                        url.searchParams.delete('media_retry');
                        url.hash = '';
                        return url.href;
                    } catch (error) {
                        return String(value);
                    }
                }

                function isConfiguredMediaUrl(value) {
                    return canonicalClientMediaUrl(value) === canonicalClientMediaUrl(configuredMediaUrl);
                }

                function showMedia(mode, url) {
                    if (!url) return;
                    mediaStatusPending.value = false;
                    if (media.value.mode === mode && canonicalClientMediaUrl(media.value.url) === canonicalClientMediaUrl(url)) {
                        nextTick(ensureMediaPlayback);
                        return;
                    }

                    stopMediaBackdrop();
                    media.value = { mode: mode || configuredMediaMode, url };
                    mediaRecoveryAttempts = 0;
                    mediaStableSince = 0;
                    mediaCacheWarmupStartedAt = Date.now();
                    mediaError.value = false;
                    document.documentElement.dataset.mediaDisplayedUrl = canonicalClientMediaUrl(url);
                    nextTick(ensureMediaPlayback);
                }

                function clearMediaRecoveryTimer() {
                    if (mediaRecoveryTimer !== null) {
                        clearTimeout(mediaRecoveryTimer);
                        mediaRecoveryTimer = null;
                    }
                }

                function handleMediaProgress(event = null) {
                    const video = event?.currentTarget instanceof HTMLVideoElement
                        ? event.currentTarget
                        : mediaVideo.value;
                    if (!video) return;

                    const currentTime = Number(video.currentTime) || 0;
                    const loopedToStart = !mediaRecoveryInProgress
                        && lastMediaCurrentTime > 3
                        && currentTime < 3
                        && lastMediaCurrentTime > currentTime + 1;
                    if (loopedToStart) {
                        if (stagedMediaUrl) activateStagedMedia('batas loop video');
                        activateWaitingServiceWorker('batas loop video');
                    }
                    if (Math.abs(currentTime - lastMediaCurrentTime) < 0.1) return;

                    lastMediaCurrentTime = currentTime;
                    lastMediaProgressAt = Date.now();
                    mediaRecoveryAttempts = 0;
                    mediaWaitingForConnection = false;
                    mediaError.value = false;
                    clearMediaRecoveryTimer();
                }

                function resumeRecoveredMedia(video, savedTime) {
                    if (video !== mediaVideo.value) return;

                    if (Number.isFinite(savedTime) && savedTime > 0 && Number.isFinite(video.duration)) {
                        try {
                            video.currentTime = Math.min(savedTime, Math.max(0, video.duration - 0.25));
                        } catch (error) {
                            console.warn('[Signage] Posisi media tidak dapat dipulihkan:', error);
                        }
                    }

                    mediaRecoveryInProgress = false;
                    lastMediaCurrentTime = Number(video.currentTime) || 0;
                    lastMediaProgressAt = Date.now();
                    mediaError.value = false;
                    ensureMediaPlayback();
                }

                function recoverMediaPlayback(reason) {
                    const video = mediaVideo.value;
                    if (!video || media.value.mode !== 'video' || mediaRecoveryInProgress) return;

                    if (navigator.onLine === false) {
                        mediaWaitingForConnection = true;
                        console.warn('[Signage] Recovery media menunggu koneksi kembali.');
                        return;
                    }

                    if (mediaRecoveryAttempts >= MEDIA_MAX_RECOVERY_ATTEMPTS) {
                        mediaError.value = true;
                        clearMediaRecoveryTimer();
                        mediaRecoveryTimer = setTimeout(() => {
                            mediaRecoveryTimer = null;
                            mediaRecoveryAttempts = 0;
                            mediaError.value = false;
                            recoverMediaPlayback('cooldown');
                        }, MEDIA_RECOVERY_COOLDOWN_MS);
                        return;
                    }

                    mediaRecoveryInProgress = true;
                    mediaRecoveryAttempts += 1;
                    mediaRecoveryTotal += 1;
                    document.documentElement.dataset.mediaRecoveryCount = String(mediaRecoveryTotal);
                    document.documentElement.dataset.mediaLastRecoveryAt = new Date().toISOString();
                    document.documentElement.dataset.mediaLastRecoveryReason = reason;
                    const attempt = mediaRecoveryAttempts;
                    const savedTime = Number(video.currentTime) || lastMediaCurrentTime || 0;
                    let resumed = false;

                    console.warn(
                        `[Signage] Memulihkan media (${reason}), percobaan ${attempt}/${MEDIA_MAX_RECOVERY_ATTEMPTS}.`
                    );
                    stopMediaBackdrop();
                    mediaError.value = false;

                    const resume = () => {
                        if (resumed) return;
                        resumed = true;
                        resumeRecoveredMedia(video, savedTime);
                    };

                    video.addEventListener('loadedmetadata', resume, { once: true });
                    video.pause();
                    if (attempt === MEDIA_MAX_RECOVERY_ATTEMPTS) {
                        const separator = media.value.url.includes('?') ? '&' : '?';
                        video.src = `${media.value.url}${separator}media_retry=${Date.now()}`;
                    }
                    video.load();
                    setTimeout(resume, 10000);
                }

                function scheduleMediaRecovery(reason) {
                    if (document.hidden || mediaRecoveryInProgress || mediaRecoveryTimer !== null) return;
                    if (navigator.onLine === false) {
                        mediaWaitingForConnection = true;
                        return;
                    }

                    const remaining = Math.max(
                        0,
                        MEDIA_STALL_THRESHOLD_MS - (Date.now() - lastMediaProgressAt)
                    );
                    mediaRecoveryTimer = setTimeout(() => {
                        mediaRecoveryTimer = null;
                        recoverMediaPlayback(reason);
                    }, remaining);
                }

                function handleMediaWaiting() {
                    stopMediaBackdrop();
                    mediaStableSince = 0;
                    if (pendingMediaCommitUrl) {
                        pendingMediaCommitSince = 0;
                        clearMediaCommitTimer();
                    }
                    recordMediaPlaybackStatus('buffering');
                    scheduleMediaRecovery('buffering');
                }

                function handleMediaError(event) {
                    stopMediaBackdrop();
                    mediaError.value = true;
                    mediaStableSince = 0;
                    recordMediaPlaybackStatus('error', event?.currentTarget?.error?.message || 'Media gagal dimuat.');
                    if (rollbackActivatedMedia('Media baru gagal diputar setelah aktivasi.')) return;
                    console.error('[Signage] Media gagal dimuat:', event?.currentTarget?.error ?? event);
                    activateWaitingServiceWorker('media error');
                    if (event?.currentTarget instanceof HTMLVideoElement) {
                        scheduleMediaRecovery('error');
                    }
                }

                function handleMediaPlaying(event) {
                    clearMediaRecoveryTimer();
                    mediaError.value = false;
                    mediaRecoveryInProgress = false;
                    mediaWaitingForConnection = false;
                    if (mediaStableSince === 0) mediaStableSince = Date.now();
                    if (pendingMediaCommitUrl && isConfiguredMediaUrl(media.value.url)
                        && pendingMediaCommitSince === 0
                    ) scheduleMediaCommit();
                    lastMediaCurrentTime = Number(event?.currentTarget?.currentTime) || lastMediaCurrentTime;
                    lastMediaProgressAt = Date.now();
                    recordMediaPlaybackStatus('playing');
                    startMediaBackdrop();
                }

                function handleMediaImageLoaded() {
                    mediaError.value = false;
                    if (mediaStableSince === 0) mediaStableSince = Date.now();
                    recordMediaPlaybackStatus('showing');
                    if (pendingMediaCommitUrl && isConfiguredMediaUrl(media.value.url)) scheduleMediaCommit();
                    maybePrepareActiveMediaOffline();
                }

                function handleMediaEnded() {
                    if (stagedMediaUrl) {
                        activateStagedMedia('akhir video');
                        return;
                    }
                    const video = mediaVideo.value;
                    if (video) video.loop = true;
                    ensureMediaPlayback();
                }

                function handleMediaVisibilityChange() {
                    if (document.hidden) {
                        clearMediaRecoveryTimer();
                        return;
                    }

                    lastMediaProgressAt = Date.now();
                    ensureMediaPlayback();
                }

                function handleNetworkOffline() {
                    clearMediaRecoveryTimer();
                    clearReconnectRetryTimer();
                    reconnectRecoveryActive = false;
                    dataRefreshQueued = false;
                    weatherRefreshQueued = false;
                    mediaWaitingForConnection = true;
                    updateConnectionStatus();
                    useCachedMediaFallback();
                    recordMediaPlaybackStatus('offline');
                    console.warn('[Signage] Perangkat offline; buffer media dipertahankan.');
                }

                function handleNetworkOnline() {
                    const shouldRecover = mediaWaitingForConnection;
                    mediaWaitingForConnection = false;
                    lastMediaProgressAt = Date.now();
                    apiHealth.schedule = 'unknown';
                    apiHealth.weather = 'unknown';
                    updateConnectionStatus();
                    console.info('[Signage] Koneksi kembali tersedia.');

                    if (shouldRecover && isConfiguredMediaUrl(media.value.url)) {
                        recoverMediaPlayback('online');
                    } else {
                        ensureMediaPlayback();
                    }

                    beginReconnectRecovery();
                    if (['error', 'insufficient', 'unavailable'].includes(mediaOfflineStatus.value)) {
                        mediaPreparationRequested = false;
                    }
                    requestActiveMediaStatus();
                    maybePrepareActiveMediaOffline();
                }

                function clearReconnectRetryTimer() {
                    if (reconnectRetryTimer !== null) {
                        clearTimeout(reconnectRetryTimer);
                        reconnectRetryTimer = null;
                    }
                }

                function beginReconnectRecovery() {
                    clearReconnectRetryTimer();
                    reconnectRecoveryActive = true;
                    reconnectRetryAttempt = 0;
                    loadData({ queueIfBusy: true });
                    loadCuaca({ queueIfBusy: true });
                }

                function continueReconnectRecovery() {
                    if (!reconnectRecoveryActive || navigator.onLine === false
                        || dataRequestInFlight || weatherRequestInFlight
                        || dataRefreshQueued || weatherRefreshQueued
                    ) return;

                    if (apiHealth.schedule === 'online' && apiHealth.weather === 'online') {
                        reconnectRecoveryActive = false;
                        reconnectRetryAttempt = 0;
                        clearReconnectRetryTimer();
                        console.info('[Signage] Seluruh API kembali menggunakan data online.');
                        return;
                    }

                    if (reconnectRetryTimer !== null) return;
                    if (reconnectRetryAttempt >= RECONNECT_RETRY_DELAYS_MS.length) {
                        reconnectRecoveryActive = false;
                        console.warn('[Signage] Recovery API beralih ke pemeriksaan berkala.');
                        return;
                    }

                    const retryDelay = RECONNECT_RETRY_DELAYS_MS[reconnectRetryAttempt];
                    reconnectRetryAttempt += 1;
                    reconnectRetryTimer = setTimeout(() => {
                        reconnectRetryTimer = null;
                        if (navigator.onLine === false) return;

                        if (apiHealth.schedule !== 'online') loadData({ queueIfBusy: true });
                        if (apiHealth.weather !== 'online') loadCuaca({ queueIfBusy: true });
                        continueReconnectRecovery();
                    }, retryDelay);
                }

                function updateClock() {
                    const now = new Date();
                    const opts = { timeZone: 'Asia/Makassar', hour12: false };

                    clock.value = new Intl.DateTimeFormat('id-ID', {
                        ...opts, hour: '2-digit', minute: '2-digit', second: '2-digit'
                    }).format(now).replaceAll('.', ':');

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

                    const url = `${BASE_URL}/go/jadwal-banmus/${activeJadwalId.value}/${activeQR.value}`;
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

                function delay(milliseconds) {
                    return new Promise(resolve => setTimeout(resolve, milliseconds));
                }

                async function fetchJsonWithRetry(url, options = {}) {
                    const timeoutMs = options.timeoutMs ?? 8000;
                    const retryDelays = options.retryDelays ?? [1000, 3000];
                    let lastError = null;

                    for (let attempt = 0; attempt <= retryDelays.length; attempt += 1) {
                        if (navigator.onLine === false) {
                            throw new Error('Perangkat sedang offline.');
                        }

                        const controller = new AbortController();
                        const timeout = setTimeout(() => controller.abort(), timeoutMs);
                        try {
                            const response = await fetch(url, {
                                method: 'GET',
                                headers: { Accept: 'application/json' },
                                cache: 'no-store',
                                signal: controller.signal,
                            });
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}`);
                            }

                            return await response.json();
                        } catch (error) {
                            lastError = error;
                            if (attempt >= retryDelays.length || navigator.onLine === false) break;
                            await delay(retryDelays[attempt]);
                        } finally {
                            clearTimeout(timeout);
                        }
                    }

                    throw lastError ?? new Error('Request gagal.');
                }

                function saveSnapshot(key, payload, sourceTimestamp = null) {
                    const savedAt = new Date().toISOString();
                    const parsedSourceTimestamp = Date.parse(sourceTimestamp ?? '');
                    const freshnessAt = Number.isFinite(parsedSourceTimestamp)
                        ? new Date(Math.min(Date.now(), parsedSourceTimestamp)).toISOString()
                        : savedAt;
                    try {
                        localStorage.setItem(key, JSON.stringify({ savedAt, freshnessAt, payload }));
                        lastSyncAt.value = savedAt;
                        document.documentElement.dataset.lastSyncAt = savedAt;
                    } catch (error) {
                        console.warn('[Signage] Snapshot browser tidak dapat disimpan:', error);
                    }
                }

                function readSnapshot(key) {
                    try {
                        const raw = localStorage.getItem(key);
                        if (!raw) return null;

                        const snapshot = JSON.parse(raw);
                        const savedAt = Date.parse(snapshot?.savedAt ?? '');
                        const freshnessAt = Date.parse(snapshot?.freshnessAt ?? snapshot?.savedAt ?? '');
                        if (!snapshot?.payload || !Number.isFinite(savedAt) || !Number.isFinite(freshnessAt)
                            || Date.now() - freshnessAt > SNAPSHOT_MAX_AGE_MS
                        ) {
                            localStorage.removeItem(key);
                            return null;
                        }

                        lastSyncAt.value = snapshot.savedAt;
                        document.documentElement.dataset.lastSyncAt = snapshot.savedAt;
                        return snapshot.payload;
                    } catch (error) {
                        console.warn('[Signage] Snapshot browser rusak dan diabaikan:', error);
                        localStorage.removeItem(key);
                        return null;
                    }
                }

                function updateConnectionStatus() {
                    if (navigator.onLine === false) {
                        connectionStatus.value = 'offline';
                    } else if (apiHealth.schedule === 'degraded' || apiHealth.weather === 'degraded') {
                        connectionStatus.value = 'degraded';
                    } else {
                        connectionStatus.value = 'online';
                    }
                    document.documentElement.dataset.connectionStatus = connectionStatus.value;
                    persistSignageDiagnostics();
                }

                function applySchedulePayload(data) {
                    if (!data || !Array.isArray(data.jadwal) || !Array.isArray(data.upcoming)) {
                        throw new Error('Format data jadwal tidak valid.');
                    }

                    jadwal.value = data.jadwal;
                    upcoming.value = data.upcoming;
                    const aktif = jadwal.value.find(item => item.status === 'berlangsung');
                    activeJadwalId.value = aktif?.id ?? null;
                    qrBerkas.value = !!aktif?.materi_url;
                    qrLive.value = !!aktif?.stream_url;
                    syncQrSlide();
                }

                function applyWeatherPayload(data) {
                    if (data?.status !== 'success' || !data.cuaca) {
                        throw new Error(data?.message || 'Format data cuaca tidak valid.');
                    }

                    cuaca.value = {
                        suhu:       data.cuaca.suhu,
                        kondisi:    data.cuaca.kondisi,
                        kelembapan: data.cuaca.kelembapan,
                        kec_angin:  data.cuaca.kec_angin,
                        icon_url:   data.cuaca.icon_url,
                        desa:       data.lokasi?.desa || '',
                        kecamatan:  data.lokasi?.kecamatan || '',
                    };
                    prepareWeatherIconOffline(cuaca.value.icon_url);
                }

                async function loadData({ queueIfBusy = false } = {}) {
                    if (dataRequestInFlight) {
                        if (queueIfBusy) dataRefreshQueued = true;
                        return;
                    }
                    dataRequestInFlight = true;
                    try {
                        const data = await fetchJsonWithRetry('/api/signage/jadwal');
                        applySchedulePayload(data);
                        saveSnapshot(SNAPSHOT_KEYS.schedule, data);
                        apiHealth.schedule = 'online';
                    } catch (error) {
                        const snapshot = readSnapshot(SNAPSHOT_KEYS.schedule);
                        if (snapshot) applySchedulePayload(snapshot);
                        apiHealth.schedule = 'degraded';
                        console.error('[Signage] Gagal ambil data jadwal; snapshot dipertahankan:', error);
                    } finally {
                        dataRequestInFlight = false;
                        updateConnectionStatus();
                        if (dataRefreshQueued && navigator.onLine !== false) {
                            dataRefreshQueued = false;
                            loadData({ queueIfBusy: true });
                        }
                        continueReconnectRecovery();
                    }
                }

                async function loadCuaca({ queueIfBusy = false } = {}) {
                    if (weatherRequestInFlight) {
                        if (queueIfBusy) weatherRefreshQueued = true;
                        return;
                    }
                    weatherRequestInFlight = true;
                    try {
                        const data = await fetchJsonWithRetry('/api/signage/cuaca');
                        applyWeatherPayload(data);
                        const weatherFreshness = Number(data.cached_at_epoch) > 0
                            ? Number(data.cached_at_epoch) * 1000
                            : data.cached_at;
                        saveSnapshot(SNAPSHOT_KEYS.weather, data, weatherFreshness);
                        apiHealth.weather = data.stale ? 'degraded' : 'online';
                    } catch (error) {
                        const snapshot = readSnapshot(SNAPSHOT_KEYS.weather);
                        if (snapshot) applyWeatherPayload(snapshot);
                        apiHealth.weather = 'degraded';
                        console.error('[Signage] Gagal ambil cuaca BMKG; snapshot dipertahankan:', error);
                    } finally {
                        weatherRequestInFlight = false;
                        updateConnectionStatus();
                        if (weatherRefreshQueued && navigator.onLine !== false) {
                            weatherRefreshQueued = false;
                            loadCuaca({ queueIfBusy: true });
                        }
                        continueReconnectRecovery();
                    }
                }

                function useCachedMediaFallback() {
                    if (!mediaOfflineFallbackUrl.value
                        || canonicalClientMediaUrl(media.value.url) === canonicalClientMediaUrl(mediaOfflineFallbackUrl.value)
                    ) return;

                    console.warn('[Signage] Menggunakan media lama yang sudah siap offline.');
                    showMedia(
                        mediaOfflineFallbackMode.value || configuredMediaMode,
                        mediaOfflineFallbackUrl.value,
                    );
                }

                function clearMediaStatusFallbackTimer() {
                    if (mediaStatusFallbackTimer !== null) {
                        clearTimeout(mediaStatusFallbackTimer);
                        mediaStatusFallbackTimer = null;
                    }
                }

                function validateStagedImage(url) {
                    return new Promise((resolve, reject) => {
                        const image = new Image();
                        const timeout = setTimeout(
                            () => reject(new Error('Validasi gambar staging timeout.')),
                            MEDIA_STAGED_VALIDATION_TIMEOUT_MS,
                        );
                        image.onload = () => {
                            clearTimeout(timeout);
                            if (image.naturalWidth > 0 && image.naturalHeight > 0) resolve();
                            else reject(new Error('Dimensi gambar staging tidak valid.'));
                        };
                        image.onerror = () => {
                            clearTimeout(timeout);
                            reject(new Error('Gambar staging tidak dapat dibaca.'));
                        };
                        image.src = url;
                    });
                }

                function validateStagedVideo(url) {
                    return new Promise((resolve, reject) => {
                        const probe = document.createElement('video');
                        let settled = false;
                        const finish = (error = null) => {
                            if (settled) return;
                            settled = true;
                            clearTimeout(timeout);
                            probe.removeAttribute('src');
                            probe.load();
                            if (error) reject(error);
                            else resolve();
                        };
                        const timeout = setTimeout(
                            () => finish(new Error('Validasi metadata video staging timeout.')),
                            MEDIA_STAGED_VALIDATION_TIMEOUT_MS,
                        );
                        probe.preload = 'metadata';
                        probe.crossOrigin = 'anonymous';
                        probe.muted = true;
                        probe.onloadedmetadata = () => {
                            const validDuration = Number.isFinite(probe.duration) && probe.duration > 0;
                            const validDimensions = probe.videoWidth > 0 && probe.videoHeight > 0;
                            finish(validDuration && validDimensions
                                ? null
                                : new Error('Metadata video staging tidak valid.'));
                        };
                        probe.onerror = () => finish(new Error('Video staging tidak dapat dibaca.'));
                        probe.src = url;
                        probe.load();
                    });
                }

                function queueStagedMedia(data) {
                    stagedMediaValidationId += 1;
                    stagedMediaUrl = data.url;
                    stagedMediaMode = data.mode || configuredMediaMode;
                    mediaOfflineStatus.value = 'staged';
                    document.documentElement.dataset.mediaOfflineStatus = 'staged';
                    document.documentElement.dataset.mediaStagedUrl = canonicalClientMediaUrl(data.url);
                    document.documentElement.dataset.mediaActivationStatus = 'waiting-boundary';
                    if (data.fallback_url) useCachedMediaFallback();

                    if (media.value.mode === 'video' && media.value.url) {
                        nextTick(() => {
                            const video = mediaVideo.value;
                            if (video) video.loop = false;
                            else activateStagedMedia('video aktif tidak tersedia');
                        });
                    } else {
                        activateStagedMedia('tidak ada batas loop video aktif');
                    }
                    persistSignageDiagnostics();
                }

                function clearMediaCommitTimer() {
                    if (mediaCommitTimer !== null) {
                        clearTimeout(mediaCommitTimer);
                        mediaCommitTimer = null;
                    }
                }

                function scheduleMediaCommit() {
                    if (!pendingMediaCommitUrl || mediaCommitRequested) return;
                    clearMediaCommitTimer();
                    pendingMediaCommitSince = Date.now();
                    mediaCommitTimer = setTimeout(() => {
                        mediaCommitTimer = null;
                        maybeCommitActiveMedia();
                    }, MEDIA_COMMIT_STABLE_MS);
                }

                function maybeCommitActiveMedia() {
                    if (!pendingMediaCommitUrl || mediaCommitRequested || pendingMediaCommitSince <= 0
                        || !isConfiguredMediaUrl(media.value.url)
                        || Date.now() - pendingMediaCommitSince < MEDIA_COMMIT_STABLE_MS
                    ) return;

                    const worker = signageWorkerRegistration?.active || navigator.serviceWorker?.controller;
                    if (!worker) return;
                    mediaCommitRequested = true;
                    clearMediaCommitTimer();
                    document.documentElement.dataset.mediaActivationStatus = 'committing';
                    worker.postMessage({ type: 'COMMIT_ACTIVE_MEDIA', url: pendingMediaCommitUrl });
                }

                function rollbackActivatedMedia(message) {
                    if (!pendingMediaCommitUrl || mediaCommitRequested) return false;
                    const worker = signageWorkerRegistration?.active || navigator.serviceWorker?.controller;
                    if (!worker) return false;

                    mediaCommitRequested = true;
                    clearMediaCommitTimer();
                    document.documentElement.dataset.mediaActivationStatus = 'rolling-back';
                    worker.postMessage({
                        type: 'ROLLBACK_ACTIVE_MEDIA',
                        url: pendingMediaCommitUrl,
                        message,
                    });
                    return true;
                }

                function finalizeActivatedMedia(data) {
                    clearMediaCommitTimer();
                    stagedMediaUrl = '';
                    stagedMediaMode = '';
                    stagedMediaActivationRequested = false;
                    mediaPreparationRequested = true;
                    document.documentElement.dataset.mediaActiveUrl = canonicalClientMediaUrl(data.url);
                    document.documentElement.dataset.mediaStagedUrl = '';
                    pendingMediaCommitUrl = data.rollback_url ? data.url : '';
                    pendingMediaCommitSince = 0;
                    mediaCommitRequested = false;
                    document.documentElement.dataset.mediaActivationStatus = pendingMediaCommitUrl
                        ? 'verifying-playback'
                        : 'active';
                    persistSignageDiagnostics();

                    const video = mediaVideo.value;
                    if (!isConfiguredMediaUrl(media.value.url)) {
                        showMedia(configuredMediaMode, configuredMediaUrl);
                        nextTick(() => {
                            if (mediaVideo.value) mediaVideo.value.loop = true;
                        });
                    } else if (video && configuredMediaMode === 'video') {
                        video.loop = true;
                        const separator = configuredMediaUrl.includes('?') ? '&' : '?';
                        video.src = `${configuredMediaUrl}${separator}media_retry=${Date.now()}`;
                        video.load();
                        ensureMediaPlayback();
                    }
                }

                async function activateStagedMedia(reason) {
                    if (!stagedMediaUrl || stagedMediaActivationRequested) return;
                    const worker = signageWorkerRegistration?.active || navigator.serviceWorker?.controller;
                    if (!worker) return;

                    stagedMediaActivationRequested = true;
                    const validationId = ++stagedMediaValidationId;
                    document.documentElement.dataset.mediaActivationStatus = 'validating';
                    document.documentElement.dataset.mediaActivationReason = reason;
                    try {
                        if (stagedMediaMode === 'image') await validateStagedImage(stagedMediaUrl);
                        else await validateStagedVideo(stagedMediaUrl);
                        if (validationId !== stagedMediaValidationId) return;

                        document.documentElement.dataset.mediaActivationStatus = 'activating';
                        worker.postMessage({ type: 'ACTIVATE_STAGED_MEDIA', url: stagedMediaUrl });
                    } catch (error) {
                        if (validationId !== stagedMediaValidationId) return;
                        document.documentElement.dataset.mediaActivationStatus = 'rejected';
                        worker.postMessage({
                            type: 'REJECT_STAGED_MEDIA',
                            url: stagedMediaUrl,
                            message: error?.message || 'Media staging tidak lolos validasi.',
                        });
                    }
                    persistSignageDiagnostics();
                }

                function handleMediaWorkerMessage(event) {
                    const data = event.data || {};
                    if (data.type === 'SIGNAGE_WEATHER_ICON_STATUS') {
                        document.documentElement.dataset.weatherIconOfflineStatus = data.status || 'error';
                        if (data.message) console.warn('[Signage] Cache ikon cuaca:', data.message);
                        return;
                    }
                    if (data.type !== 'SIGNAGE_MEDIA_STATUS') return;

                    clearMediaStatusFallbackTimer();
                    mediaStatusPending.value = false;
                    mediaWorkerProtocolVersion = Number(data.protocol_version) || 0;
                    document.documentElement.dataset.mediaWorkerProtocol = String(mediaWorkerProtocolVersion);
                    mediaOfflineSize.value = Number(data.size) || 0;
                    if (data.fallback_url) mediaOfflineFallbackUrl.value = data.fallback_url;
                    if (data.fallback_mode) mediaOfflineFallbackMode.value = data.fallback_mode;
                    mediaOfflineStatus.value = data.status || 'error';
                    document.documentElement.dataset.mediaOfflineStatus = mediaOfflineStatus.value;
                    document.documentElement.dataset.mediaOfflineBytes = String(mediaOfflineSize.value);
                    queueMicrotask(persistSignageDiagnostics);

                    if (Number.isFinite(Number(data.available))) {
                        document.documentElement.dataset.storageAvailableBytes = String(Number(data.available));
                    }
                    if (data.message) console.warn('[Signage] Cache media:', data.message);

                    if (data.status === 'ready' && data.url) {
                        if (data.committed && isConfiguredMediaUrl(data.url)) {
                            clearMediaCommitTimer();
                            pendingMediaCommitUrl = '';
                            pendingMediaCommitSince = 0;
                            mediaCommitRequested = false;
                            document.documentElement.dataset.mediaActivationStatus = 'active';
                            persistSignageDiagnostics();
                            return;
                        }

                        if (data.rolled_back) {
                            clearMediaCommitTimer();
                            pendingMediaCommitUrl = '';
                            pendingMediaCommitSince = 0;
                            mediaCommitRequested = false;
                            stagedMediaUrl = '';
                            stagedMediaMode = '';
                            stagedMediaActivationRequested = false;
                            mediaPreparationRequested = true;
                            mediaOfflineFallbackUrl.value = data.url;
                            mediaOfflineFallbackMode.value = data.mode || configuredMediaMode;
                            mediaOfflineStatus.value = 'fallback';
                            document.documentElement.dataset.mediaOfflineStatus = 'fallback';
                            document.documentElement.dataset.mediaActiveUrl = canonicalClientMediaUrl(data.url);
                            document.documentElement.dataset.mediaActivationStatus = 'rolled-back';
                            showMedia(data.mode || configuredMediaMode, data.url);
                            nextTick(() => {
                                if (mediaVideo.value) mediaVideo.value.loop = true;
                            });
                            persistSignageDiagnostics();
                            return;
                        }

                        if (data.activated && isConfiguredMediaUrl(data.url)) {
                            finalizeActivatedMedia(data);
                            return;
                        }

                        mediaOfflineFallbackUrl.value = data.url;
                        mediaOfflineFallbackMode.value = data.mode || configuredMediaMode;
                        document.documentElement.dataset.mediaActiveUrl = canonicalClientMediaUrl(data.url);
                        if (isConfiguredMediaUrl(data.url)) {
                            mediaPreparationRequested = true;
                            if (data.rollback_url) {
                                pendingMediaCommitUrl = data.url;
                                pendingMediaCommitSince = 0;
                                mediaCommitRequested = false;
                                document.documentElement.dataset.mediaActivationStatus = 'verifying-playback';
                            }
                            showMedia(data.mode || configuredMediaMode, data.url);
                        } else {
                            mediaOfflineStatus.value = 'fallback';
                            document.documentElement.dataset.mediaOfflineStatus = 'fallback';
                            mediaPreparationRequested = false;
                            useCachedMediaFallback();
                            maybePrepareActiveMediaOffline();
                        }
                        return;
                    }

                    if (data.status === 'staged' && data.url) {
                        mediaPreparationRequested = true;
                        if (data.fallback_url) useCachedMediaFallback();
                        queueStagedMedia(data);
                        return;
                    }

                    if (['checking', 'downloading'].includes(data.status)) {
                        mediaPreparationRequested = true;
                        if (data.fallback_url) useCachedMediaFallback();
                        return;
                    }

                    if (data.status === 'unavailable') {
                        mediaPreparationRequested = false;
                        if (!media.value.url && configuredMediaUrl) showMedia(configuredMediaMode, configuredMediaUrl);
                        maybePrepareActiveMediaOffline();
                        return;
                    }

                    if (navigator.onLine === false || ['error', 'insufficient'].includes(data.status)) {
                        if (data.rejected) {
                            stagedMediaValidationId += 1;
                            stagedMediaUrl = '';
                            stagedMediaMode = '';
                            document.documentElement.dataset.mediaStagedUrl = '';
                            document.documentElement.dataset.mediaActivationStatus = 'rejected';
                        } else {
                            document.documentElement.dataset.mediaActivationStatus = 'error';
                        }
                        stagedMediaActivationRequested = false;
                        mediaCommitRequested = false;
                        useCachedMediaFallback();
                        const video = mediaVideo.value;
                        if (video) {
                            video.loop = true;
                            ensureMediaPlayback();
                        }
                    }
                    persistSignageDiagnostics();
                }

                async function requestPersistentStorage() {
                    if (!navigator.storage) return;

                    try {
                        storagePersistent.value = navigator.storage.persisted
                            ? await navigator.storage.persisted()
                            : false;
                        if (!storagePersistent.value && navigator.storage.persist) {
                            storagePersistent.value = await navigator.storage.persist();
                        }
                        document.documentElement.dataset.storagePersistent = String(storagePersistent.value);

                        if (navigator.storage.estimate) {
                            const estimate = await navigator.storage.estimate();
                            if (Number.isFinite(Number(estimate.quota))) {
                                document.documentElement.dataset.storageQuotaBytes = String(Number(estimate.quota));
                            }
                            if (Number.isFinite(Number(estimate.usage))) {
                                document.documentElement.dataset.storageUsageBytes = String(Number(estimate.usage));
                            }
                        }
                    } catch (error) {
                        console.warn('[Signage] Status penyimpanan browser tidak dapat diperiksa:', error);
                    }
                }

                function bufferedMediaSeconds(video) {
                    if (!video || !video.buffered) return 0;
                    const currentTime = Number(video.currentTime) || 0;
                    for (let index = 0; index < video.buffered.length; index += 1) {
                        if (video.buffered.start(index) <= currentTime && video.buffered.end(index) >= currentTime) {
                            return Math.max(0, video.buffered.end(index) - currentTime);
                        }
                    }
                    return 0;
                }

                function maybePrepareActiveMediaOffline() {
                    const worker = signageWorkerRegistration?.active || navigator.serviceWorker?.controller;
                    if (!worker || !configuredMediaUrl || mediaPreparationRequested
                        || stagedMediaUrl || navigator.onLine === false || mediaStatusPending.value
                        || mediaWorkerProtocolVersion < 2 || waitingServiceWorker
                    ) return;

                    const now = Date.now();
                    const stableFor = mediaStableSince > 0 ? now - mediaStableSince : 0;
                    const waitedFor = now - mediaCacheWarmupStartedAt;
                    const displayedMediaIsCachedFallback = mediaOfflineFallbackUrl.value
                        && canonicalClientMediaUrl(media.value.url) === canonicalClientMediaUrl(mediaOfflineFallbackUrl.value);
                    const video = mediaVideo.value;
                    const minimumStableMs = media.value.mode === 'image' ? 2000 : MEDIA_CACHE_MIN_STABLE_MS;
                    const hasSafeBuffer = media.value.mode === 'image'
                        || displayedMediaIsCachedFallback
                        || bufferedMediaSeconds(video) >= MEDIA_CACHE_MIN_BUFFER_SECONDS;

                    if (stableFor < minimumStableMs || (!hasSafeBuffer && waitedFor < MEDIA_CACHE_MAX_WAIT_MS)) return;

                    mediaPreparationRequested = true;
                    document.documentElement.dataset.mediaDownloadPolicy = hasSafeBuffer
                        ? 'playback-stable'
                        : 'maximum-wait-reached';
                    worker.postMessage({
                        type: 'CACHE_ACTIVE_MEDIA',
                        url: configuredMediaUrl,
                        mode: configuredMediaMode,
                    });
                }

                function requestActiveMediaStatus() {
                    const worker = signageWorkerRegistration?.active || navigator.serviceWorker?.controller;
                    if (!worker) return;

                    clearMediaStatusFallbackTimer();
                    mediaStatusPending.value = true;
                    worker.postMessage({ type: 'GET_MEDIA_STATUS' });
                    mediaStatusFallbackTimer = setTimeout(() => {
                        mediaStatusFallbackTimer = null;
                        mediaStatusPending.value = false;
                        if (!media.value.url && configuredMediaUrl) showMedia(configuredMediaMode, configuredMediaUrl);
                        maybePrepareActiveMediaOffline();
                    }, 2000);
                }

                function prepareWeatherIconOffline(url = cuaca.value.icon_url) {
                    if (!url) return;

                    const worker = signageWorkerRegistration?.active || navigator.serviceWorker?.controller;
                    worker?.postMessage({ type: 'CACHE_WEATHER_ICON', url });
                }

                function clearWorkerUpdateTimer() {
                    if (workerUpdateTimer !== null) {
                        clearTimeout(workerUpdateTimer);
                        workerUpdateTimer = null;
                    }
                }

                function activateWaitingServiceWorker(reason) {
                    if (!waitingServiceWorker || workerUpdateActivationRequested) return;

                    workerUpdateActivationRequested = true;
                    clearWorkerUpdateTimer();
                    document.documentElement.dataset.serviceWorkerUpdate = 'activating';
                    console.info(`[Signage] Mengaktifkan update service worker (${reason}).`);
                    waitingServiceWorker.postMessage({ type: 'ACTIVATE_UPDATE' });
                }

                function queueServiceWorkerUpdate(worker) {
                    if (!worker || workerUpdateActivationRequested) return;

                    waitingServiceWorker = worker;
                    document.documentElement.dataset.serviceWorkerUpdate = 'waiting';
                    clearWorkerUpdateTimer();

                    const video = mediaVideo.value;
                    if (media.value.mode !== 'video' || !video || mediaError.value) {
                        activateWaitingServiceWorker('tidak ada video aktif');
                        return;
                    }

                    const remainingMs = Number.isFinite(video.duration) && video.duration > 0
                        ? Math.max(1000, (video.duration - video.currentTime + 0.5) * 1000)
                        : 300000;
                    workerUpdateTimer = setTimeout(
                        () => activateWaitingServiceWorker('batas tunggu update'),
                        Math.min(remainingMs, 300000),
                    );
                    console.info('[Signage] Update app shell akan aktif pada loop video berikutnya.');
                }

                function handleServiceWorkerControllerChange() {
                    if (!workerUpdateActivationRequested) return;

                    document.documentElement.dataset.serviceWorkerUpdate = 'activated';
                    window.location.reload();
                }

                async function registerSignageServiceWorker() {
                    if (!('serviceWorker' in navigator)) {
                        mediaOfflineStatus.value = 'unsupported';
                        mediaStatusPending.value = false;
                        if (!media.value.url && configuredMediaUrl) showMedia(configuredMediaMode, configuredMediaUrl);
                        return;
                    }

                    const workerUrl = `/signage-sw.js?v=${encodeURIComponent(SIGNAGE_WORKER_VERSION)}`;
                    try {
                        const registration = await navigator.serviceWorker.register(workerUrl, { scope: '/' });
                        if (registration.waiting) {
                            queueServiceWorkerUpdate(registration.waiting);
                        }
                        registration.addEventListener('updatefound', () => {
                            const worker = registration.installing;
                            worker?.addEventListener('statechange', () => {
                                if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                                    queueServiceWorkerUpdate(worker);
                                }
                            });
                        });

                        signageWorkerRegistration = await navigator.serviceWorker.ready;
                        await requestPersistentStorage();
                        requestActiveMediaStatus();
                        prepareWeatherIconOffline();
                    } catch (error) {
                        mediaOfflineStatus.value = 'unsupported';
                        mediaStatusPending.value = false;
                        if (!media.value.url && configuredMediaUrl) showMedia(configuredMediaMode, configuredMediaUrl);
                        document.documentElement.dataset.mediaOfflineStatus = 'unsupported';
                        console.warn('[Signage] Service worker tidak dapat didaftarkan:', error);
                    }
                }

                onMounted(() => {
                    updateClock();
                    clockTimer = setInterval(updateClock, 1000);
                    document.documentElement.dataset.mediaRecoveryCount = '0';
                    document.documentElement.dataset.mediaActivationStatus = 'checking';
                    document.documentElement.dataset.mediaDisplayedUrl = canonicalClientMediaUrl(media.value.url);
                    persistSignageDiagnostics();
                    diagnosticsTimer = setInterval(persistSignageDiagnostics, 60000);

                    loadData();
                    loadCuaca();
                    updateConnectionStatus();
                    navigator.serviceWorker?.addEventListener('message', handleMediaWorkerMessage);
                    navigator.serviceWorker?.addEventListener('controllerchange', handleServiceWorkerControllerChange);
                    registerSignageServiceWorker();
                    dataTimer = setInterval(() => {
                        loadData();
                        if (apiHealth.weather !== 'online') loadCuaca();
                    }, 60000);
                    // Cuaca refresh setiap 15 menit (cache BMKG 30 menit)
                    weatherTimer = setInterval(loadCuaca, 900000);

                    nextTick(ensureMediaPlayback);
                    mediaWatchTimer = setInterval(() => {
                        const video = mediaVideo.value;
                        if (!video || document.hidden) return;

                        handleMediaProgress();
                        document.documentElement.dataset.mediaCurrentTime = String(Math.round(Number(video.currentTime) || 0));
                        document.documentElement.dataset.mediaBufferedSeconds = String(Math.round(bufferedMediaSeconds(video)));
                        maybePrepareActiveMediaOffline();
                        maybeCommitActiveMedia();
                        if (video.paused || video.ended) {
                            if (!stagedMediaActivationRequested) ensureMediaPlayback();
                        }
                        if (!video.ended && Date.now() - lastMediaProgressAt >= MEDIA_STALL_THRESHOLD_MS) {
                            scheduleMediaRecovery('watchdog');
                        }
                    }, 5000);
                    document.addEventListener('visibilitychange', handleMediaVisibilityChange);
                    window.addEventListener('offline', handleNetworkOffline);
                    window.addEventListener('online', handleNetworkOnline);
                    if (navigator.onLine === false) handleNetworkOffline();
                });

                onUnmounted(() => {
                    clearInterval(clockTimer);
                    clearInterval(dataTimer);
                    clearInterval(weatherTimer);
                    clearInterval(mediaWatchTimer);
                    clearInterval(diagnosticsTimer);
                    clearInterval(qrSlideTimer);
                    clearMediaRecoveryTimer();
                    clearMediaStatusFallbackTimer();
                    clearMediaCommitTimer();
                    clearReconnectRetryTimer();
                    clearWorkerUpdateTimer();
                    document.removeEventListener('visibilitychange', handleMediaVisibilityChange);
                    window.removeEventListener('offline', handleNetworkOffline);
                    window.removeEventListener('online', handleNetworkOnline);
                    navigator.serviceWorker?.removeEventListener('message', handleMediaWorkerMessage);
                    navigator.serviceWorker?.removeEventListener('controllerchange', handleServiceWorkerControllerChange);
                    stopMediaBackdrop();
                });

                return {
                    clock, dateDay, dateFull,
                    connectionStatus, lastSyncAt,
                    mediaOfflineStatus, mediaOfflineSize, storagePersistent, mediaStatusPending,
                    cuaca, qrBerkas, qrLive, activeQR, qrFading,
                    jadwal, upcoming, runningText, runningTextAktif, media,
                    mediaVideo, mediaBackdrop, mediaError,
                    ensureMediaPlayback, handleMediaProgress, handleMediaPlaying,
                    handleMediaWaiting, handleMediaEnded, handleMediaImageLoaded, handleMediaError,
                    statusLabel, statusClasses, statusDotClasses, scheduleItemClasses, upcomingDateLabel
                };
            }
        }).mount('#app');
    </script>

</body>

</html>
