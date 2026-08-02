import { createPartialResponse } from 'workbox-range-requests';

const CACHE_PREFIX = 'dprd-signage-shell-';
const MEDIA_CACHE_NAME = 'dprd-signage-media-v1';
const MEDIA_MANIFEST_URL = '/__signage_media_manifest__';
const WEATHER_ICON_CACHE_NAME = 'dprd-signage-weather-icons-v1';
const BMKG_ICON_ORIGIN = 'https://api-apps.bmkg.go.id';
const BMKG_ICON_PATH_PREFIX = '/storage/icon/cuaca/';
const MAX_MEDIA_BYTES = 200 * 1024 * 1024;
const STORAGE_RESERVE_BYTES = 10 * 1024 * 1024;
const workerVersion = new URL(self.location.href).searchParams.get('v') || '1';
const CACHE_NAME = `${CACHE_PREFIX}${workerVersion.replace(/[^a-zA-Z0-9._-]/g, '')}`;
const APP_SHELL = [
    '/signage',
    '/assets/css/signage.css',
    '/assets/vendor/vue/vue.global.prod.js',
    '/assets/vendor/qrcodejs/qrcode.min.js',
    '/assets/vendor/fonts/fonts.css',
    '/assets/vendor/fonts/files/inter-latin-400-normal.woff2',
    '/assets/vendor/fonts/files/inter-latin-500-normal.woff2',
    '/assets/vendor/fonts/files/inter-latin-600-normal.woff2',
    '/assets/vendor/fonts/files/inter-latin-700-normal.woff2',
    '/assets/vendor/fonts/files/inter-latin-800-normal.woff2',
    '/assets/vendor/fonts/files/inter-latin-900-normal.woff2',
    '/assets/vendor/fonts/files/ibm-plex-mono-latin-400-normal.woff2',
    '/assets/vendor/fonts/files/ibm-plex-mono-latin-500-normal.woff2',
    '/assets/vendor/fonts/files/ibm-plex-mono-latin-700-normal.woff2',
    '/assets/vendor/fonts/files/outfit-latin-400-normal.woff2',
    '/assets/vendor/fonts/files/outfit-latin-500-normal.woff2',
    '/assets/vendor/fonts/files/outfit-latin-600-normal.woff2',
    '/assets/vendor/fonts/files/outfit-latin-700-normal.woff2',
    '/assets/vendor/fonts/files/outfit-latin-800-normal.woff2',
    '/assets/images/logo_dprd.jpg',
];

let activeMediaDownload = null;

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(APP_SHELL)));
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys
                    .filter(key => key.startsWith(CACHE_PREFIX) && key !== CACHE_NAME)
                    .map(key => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

function fetchWithTimeout(request, timeoutMs) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);

    return fetch(request, { signal: controller.signal })
        .finally(() => clearTimeout(timeout));
}

async function networkFirstSignage(request) {
    const cache = await caches.open(CACHE_NAME);
    try {
        const response = await fetchWithTimeout(request, 8000);
        if (response.ok) {
            const url = new URL(request.url);
            const cacheKey = url.searchParams.has('tema') ? request : '/signage';
            await cache.put(cacheKey, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await cache.match(request) || await cache.match('/signage');
        if (cached) return cached;

        return new Response('Signage belum pernah dibuka saat online.', {
            status: 503,
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
    }
}

async function cacheFirstAsset(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request, { ignoreSearch: true });
    if (cached) return cached;

    const response = await fetch(request);
    if (response.ok) await cache.put(request, response.clone());
    return response;
}

function canonicalMediaUrl(value) {
    if (!value) return '';

    const url = new URL(value, self.location.origin);
    if (url.origin !== self.location.origin || !url.pathname.startsWith('/uploads/media/')) {
        throw new Error('URL media harus berasal dari folder media same-origin.');
    }
    url.searchParams.delete('media_retry');
    url.hash = '';
    return url.href;
}

function canonicalWeatherIconUrl(value) {
    if (!value) return '';

    const url = new URL(value);
    if (url.origin !== BMKG_ICON_ORIGIN || !url.pathname.startsWith(BMKG_ICON_PATH_PREFIX)) {
        throw new Error('URL ikon cuaca tidak termasuk allowlist BMKG.');
    }
    url.hash = '';
    return url.href;
}

function isBmkgWeatherIcon(url) {
    return url.origin === BMKG_ICON_ORIGIN && url.pathname.startsWith(BMKG_ICON_PATH_PREFIX);
}

async function cacheWeatherIcon(client, rawUrl) {
    try {
        const url = canonicalWeatherIconUrl(rawUrl);
        if (!url) return;

        const cache = await caches.open(WEATHER_ICON_CACHE_NAME);
        if (!await cache.match(url, { ignoreSearch: true })) {
            const response = await fetch(new Request(url, { mode: 'no-cors', cache: 'no-store' }));
            if (!response.ok && response.type !== 'opaque') {
                throw new Error(`Ikon BMKG merespons HTTP ${response.status}.`);
            }
            await cache.put(url, response);

            const keys = await cache.keys();
            if (keys.length > 32) {
                await Promise.all(keys.slice(0, keys.length - 32).map(request => cache.delete(request)));
            }
        }
        client?.postMessage({ type: 'SIGNAGE_WEATHER_ICON_STATUS', status: 'ready', url });
    } catch (error) {
        client?.postMessage({
            type: 'SIGNAGE_WEATHER_ICON_STATUS',
            status: 'error',
            message: error?.message || 'Ikon BMKG gagal disimpan.',
        });
    }
}

async function weatherIconResponse(request) {
    const cache = await caches.open(WEATHER_ICON_CACHE_NAME);
    const cached = await cache.match(request, { ignoreSearch: true });
    if (cached) return cached;

    const response = await fetch(request);
    if (response.ok || response.type === 'opaque') {
        await cache.put(request, response.clone());
    }
    return response;
}

async function readMediaManifest(cache = null) {
    const mediaCache = cache || await caches.open(MEDIA_CACHE_NAME);
    const response = await mediaCache.match(MEDIA_MANIFEST_URL);
    if (!response) return null;

    try {
        const manifest = await response.json();
        return manifest?.status === 'ready' && manifest.url ? manifest : null;
    } catch (error) {
        await mediaCache.delete(MEDIA_MANIFEST_URL);
        return null;
    }
}

async function postMediaStatus(client, status, details = {}) {
    const payload = { type: 'SIGNAGE_MEDIA_STATUS', status, ...details };
    if (client && typeof client.postMessage === 'function') {
        client.postMessage(payload);
    }

    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    clients
        .filter(item => !client?.id || item.id !== client.id)
        .forEach(item => item.postMessage(payload));
}

async function storageAvailability() {
    if (!self.navigator.storage?.estimate) return { quota: null, usage: null, available: null };

    const estimate = await self.navigator.storage.estimate();
    const quota = Number(estimate.quota);
    const usage = Number(estimate.usage);
    return {
        quota: Number.isFinite(quota) ? quota : null,
        usage: Number.isFinite(usage) ? usage : null,
        available: Number.isFinite(quota) && Number.isFinite(usage) ? Math.max(0, quota - usage) : null,
    };
}

async function clearActiveMedia(client) {
    if (activeMediaDownload) activeMediaDownload.controller.abort();
    await caches.delete(MEDIA_CACHE_NAME);
    await postMediaStatus(client, 'unavailable');
}

async function prepareMedia(client, rawUrl, mode) {
    const url = canonicalMediaUrl(rawUrl);
    if (!url) {
        await clearActiveMedia(client);
        return;
    }

    if (activeMediaDownload?.url === url) {
        await postMediaStatus(client, 'downloading', activeMediaDownload.details);
        return activeMediaDownload.promise;
    }
    if (activeMediaDownload) activeMediaDownload.controller.abort();

    const controller = new AbortController();
    const details = { url, mode };
    const promise = downloadAndActivateMedia(client, url, mode, controller.signal, details)
        .finally(() => {
            if (activeMediaDownload?.promise === promise) activeMediaDownload = null;
        });
    activeMediaDownload = { url, controller, promise, details };
    return promise;
}

async function downloadAndActivateMedia(client, url, mode, signal, details) {
    const cache = await caches.open(MEDIA_CACHE_NAME);
    const previous = await readMediaManifest(cache);
    const fallback = previous ? { fallback_url: previous.url, fallback_mode: previous.mode } : {};
    const existing = await cache.match(url);

    if (previous?.url === url && existing) {
        await postMediaStatus(client, 'ready', { ...previous, ...fallback });
        return;
    }

    await postMediaStatus(client, 'checking', fallback);
    try {
        const response = await fetch(new Request(url, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            signal,
        }));
        if (response.status !== 200) {
            throw new Error(`Media lengkap harus merespons HTTP 200, diterima ${response.status}.`);
        }

        const size = Number(response.headers.get('Content-Length'));
        if (!Number.isFinite(size) || size <= 0 || size > MAX_MEDIA_BYTES) {
            throw new Error('Content-Length media tidak tersedia atau melewati batas 200 MB.');
        }

        const storage = await storageAvailability();
        Object.assign(details, { size, ...fallback, ...storage });
        if (storage.available !== null && storage.available < size + STORAGE_RESERVE_BYTES) {
            await postMediaStatus(client, 'insufficient', details);
            return;
        }

        await postMediaStatus(client, 'downloading', details);
        await cache.put(url, response);
        const stored = await cache.match(url);
        if (!stored || Number(stored.headers.get('Content-Length')) !== size) {
            await cache.delete(url);
            throw new Error('Media tersimpan tidak lolos validasi ukuran.');
        }

        const pathname = new URL(url).pathname;
        const manifest = {
            file: decodeURIComponent(pathname.substring(pathname.lastIndexOf('/') + 1)),
            url,
            mode: mode === 'image' ? 'image' : 'video',
            size,
            status: 'ready',
            cached_at: new Date().toISOString(),
        };
        await cache.put(MEDIA_MANIFEST_URL, new Response(JSON.stringify(manifest), {
            headers: { 'Content-Type': 'application/json' },
        }));

        const keep = new Set([new URL(url).href, new URL(MEDIA_MANIFEST_URL, self.location.origin).href]);
        const keys = await cache.keys();
        await Promise.all(keys.filter(request => !keep.has(request.url)).map(request => cache.delete(request)));
        await postMediaStatus(client, 'ready', manifest);
    } catch (error) {
        if (error?.name === 'AbortError') return;
        await postMediaStatus(client, 'error', { message: error?.message || 'Media gagal disimpan.', ...fallback });
    }
}

async function reportMediaStatus(client) {
    const cache = await caches.open(MEDIA_CACHE_NAME);
    const manifest = await readMediaManifest(cache);
    if (manifest && await cache.match(manifest.url)) {
        await postMediaStatus(client, 'ready', manifest);
        return;
    }
    await postMediaStatus(client, 'unavailable');
}

async function mediaResponse(request) {
    const cache = await caches.open(MEDIA_CACHE_NAME);
    let url = '';
    try {
        url = canonicalMediaUrl(request.url);
    } catch (error) {
        return fetch(request);
    }

    const cached = await cache.match(url);
    if (!cached) return fetch(request);
    if (request.headers.has('Range')) return createPartialResponse(request, cached);
    return cached;
}

self.addEventListener('message', event => {
    const data = event.data || {};
    if (data.type === 'ACTIVATE_UPDATE') {
        event.waitUntil(self.skipWaiting());
    } else if (data.type === 'CACHE_ACTIVE_MEDIA') {
        event.waitUntil(prepareMedia(event.source, data.url, data.mode));
    } else if (data.type === 'CACHE_WEATHER_ICON') {
        event.waitUntil(cacheWeatherIcon(event.source, data.url));
    } else if (data.type === 'GET_MEDIA_STATUS') {
        event.waitUntil(reportMediaStatus(event.source));
    }
});

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (isBmkgWeatherIcon(url)) {
        event.respondWith(weatherIconResponse(request));
        return;
    }
    if (url.origin !== self.location.origin) return;

    if (request.mode === 'navigate' && url.pathname === '/signage') {
        event.respondWith(networkFirstSignage(request));
        return;
    }
    if (url.pathname.startsWith('/uploads/media/')) {
        event.respondWith(mediaResponse(request));
        return;
    }
    if (url.pathname.startsWith('/assets/')) {
        event.respondWith(cacheFirstAsset(request));
    }
});
