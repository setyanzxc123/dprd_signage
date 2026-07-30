import Uppy from '@uppy/core';
import StatusBar from '@uppy/status-bar';
import Tus from '@uppy/tus';

const ALLOWED_TYPES = [
    'video/mp4',
    'video/webm',
    'image/jpeg',
    'image/png',
    'image/webp',
];

function randomUploadName(originalName) {
    const extension = String(originalName).split('.').pop().toLowerCase();
    const identifier = window.crypto?.randomUUID?.()
        || `${Date.now()}-${Math.random().toString(16).slice(2)}`;

    return `${identifier}.${extension}`;
}

function uploadKeyFromUrl(uploadUrl) {
    try {
        const segments = new URL(uploadUrl, window.location.origin).pathname
            .split('/')
            .filter(Boolean);
        const key = segments[segments.length - 1] || '';

        return /^[a-f0-9-]{36}$/i.test(key) ? key : '';
    } catch {
        return '';
    }
}

function formatUploadSpeed(bytesPerSecond) {
    const formatter = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 1,
    });
    if (bytesPerSecond >= 1024 * 1024) {
        return `${formatter.format(bytesPerSecond / 1024 / 1024)} MB/dtk`;
    }

    return `${formatter.format(bytesPerSecond / 1024)} KB/dtk`;
}

function initSettingsUpload() {
    window.__dprdSettingsUpload?.destroy();

    const form = document.getElementById('settings-form');
    if (!form) {
        window.__dprdSettingsUpload = null;
        return;
    }

    const mediaInput = document.getElementById('media_file');
    const uploadKeyInput = document.getElementById('media_upload_key');
    const panel = document.getElementById('settings-upload-progress');
    const statusBarTarget = document.getElementById('settings-upload-statusbar');
    const message = document.getElementById('settings-upload-message');
    const messageSpinner = document.getElementById('settings-upload-message-spinner');
    const status = document.getElementById('settings-upload-status');
    const speed = document.getElementById('settings-upload-speed');
    const submitButton = document.getElementById('settings-submit-button');
    const submitSpinner = document.getElementById('settings-submit-spinner');
    const submitIcon = document.getElementById('settings-submit-icon');
    const submitLabel = document.getElementById('settings-submit-label');

    if (!mediaInput
        || !uploadKeyInput
        || !panel
        || !statusBarTarget
        || !message
        || !status
        || !speed
        || !submitButton) {
        return;
    }

    const endpoint = form.dataset.mediaUploadEndpoint || '';
    const uploadToken = form.dataset.mediaUploadToken || '';
    const maxBytes = Number(form.dataset.mediaMaxBytes) || 200 * 1024 * 1024;
    const chunkBytes = Number(form.dataset.mediaChunkBytes) || 5 * 1024 * 1024;
    let currentFileId = '';
    let completedUploadKey = '';
    let destroyed = false;
    let speedTimer = null;
    let speedStartedAt = 0;
    let speedStartedBytes = 0;

    const uppy = new Uppy({
        autoProceed: false,
        restrictions: {
            maxFileSize: maxBytes,
            maxNumberOfFiles: 1,
            minNumberOfFiles: 1,
            allowedFileTypes: ALLOWED_TYPES,
        },
    });

    uppy.use(Tus, {
        endpoint,
        chunkSize: chunkBytes,
        limit: 1,
        retryDelays: [0, 1000, 3000, 5000, 10000, 20000],
        removeFingerprintOnSuccess: true,
        withCredentials: true,
        headers: {
            'X-Media-Upload-Token': uploadToken,
        },
        allowedMetaFields: ['name', 'filename', 'filetype', 'originalName', 'ownerToken'],
    });

    uppy.use(StatusBar, {
        target: statusBarTarget,
        showProgressDetails: true,
        hideUploadButton: true,
        hideRetryButton: true,
        hidePauseResumeButton: true,
        hideCancelButton: false,
        hideAfterFinish: false,
        locale: {
            strings: {
                uploading: 'Mengunggah',
                complete: 'Upload selesai',
                uploadFailed: 'Upload gagal',
                cancel: 'Batalkan',
                dataUploadedOfTotal: '%{complete} dari %{total}',
                dataUploadedOfUnknown: '%{complete} terkirim',
                xTimeLeft: 'tersisa %{time}',
                showErrorDetails: 'Lihat detail error',
            },
        },
    });

    const syncStatusBarTheme = () => {
        statusBarTarget.dataset.uppyTheme = document.documentElement.dataset.theme === 'dark'
            ? 'dark'
            : 'light';
    };
    syncStatusBarTheme();
    const themeObserver = new MutationObserver(syncStatusBarTheme);
    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme'],
    });

    const setBusy = (busy) => {
        form.dataset.settingsSubmitting = busy ? '1' : '0';
        form.setAttribute('aria-busy', busy ? 'true' : 'false');
        submitButton.disabled = busy;
        if (submitSpinner) submitSpinner.hidden = !busy;
        if (submitIcon) submitIcon.hidden = busy;
        if (submitLabel) submitLabel.textContent = busy ? 'Menyimpan...' : 'Simpan Pengaturan';
    };

    const hideMessage = () => {
        message.hidden = true;
        message.classList.remove('alert-info', 'alert-error', 'alert-success');
    };

    const showMessage = (text, type = 'info', loading = false) => {
        panel.hidden = false;
        message.classList.remove('alert-info', 'alert-error', 'alert-success');
        message.classList.add(`alert-${type}`);
        message.hidden = false;
        if (messageSpinner) messageSpinner.hidden = !loading;
        status.textContent = text;
    };

    const stopSpeed = () => {
        if (speedTimer !== null) {
            window.clearInterval(speedTimer);
            speedTimer = null;
        }
        speed.hidden = true;
    };

    const startSpeed = () => {
        stopSpeed();
        const file = currentFileId ? uppy.getFile(currentFileId) : null;
        speedStartedAt = performance.now();
        speedStartedBytes = Number(file?.progress?.bytesUploaded) || 0;
        speed.textContent = 'Mengukur kecepatan...';
        speed.hidden = false;
        speedTimer = window.setInterval(() => {
            const latestFile = currentFileId ? uppy.getFile(currentFileId) : null;
            const uploadedBytes = Number(latestFile?.progress?.bytesUploaded) || 0;
            const elapsedSeconds = (performance.now() - speedStartedAt) / 1000;
            const uploadedSinceStart = uploadedBytes - speedStartedBytes;
            if (elapsedSeconds >= 0.5 && uploadedSinceStart > 0) {
                speed.textContent = `Kecepatan rata-rata ${formatUploadSpeed(uploadedSinceStart / elapsedSeconds)}`;
            }
        }, 500);
    };

    const showError = (errorMessage) => {
        stopSpeed();
        showMessage(errorMessage || 'Upload media gagal.', 'error');
        setBusy(false);
    };

    const clearSelectedUpload = () => {
        stopSpeed();
        currentFileId = '';
        completedUploadKey = '';
        uploadKeyInput.value = '';
        mediaInput.value = '';
        hideMessage();
        panel.hidden = true;
        setBusy(false);
    };

    const selectFile = () => {
        const file = mediaInput.files?.[0];
        uppy.getFiles().forEach((uppyFile) => uppy.removeFile(uppyFile.id));
        currentFileId = '';
        completedUploadKey = '';
        uploadKeyInput.value = '';
        stopSpeed();
        hideMessage();
        if (!file) {
            panel.hidden = true;
            return;
        }

        try {
            currentFileId = uppy.addFile({
                name: file.name,
                type: file.type,
                data: file,
                source: 'settings-media',
            });
            const temporaryName = randomUploadName(file.name);
            uppy.setFileMeta(currentFileId, {
                name: temporaryName,
                filename: temporaryName,
                filetype: file.type,
                originalName: file.name,
                ownerToken: uploadToken,
            });
            panel.hidden = true;
        } catch (error) {
            mediaInput.value = '';
            showError(error?.message || 'File media tidak dapat dipilih.');
        }
    };

    const refreshCsrf = (csrf) => {
        if (!csrf?.name || !csrf?.hash) return;
        const csrfInput = Array.from(form.elements).find((element) => element.name === csrf.name);
        if (csrfInput) csrfInput.value = csrf.hash;
    };

    const saveSettings = async () => {
        const formData = new FormData(form);
        formData.delete('media_file');
        formData.set('media_upload_key', completedUploadKey);

        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });
        const payload = await response.json().catch(() => null);
        refreshCsrf(payload?.csrf);
        if (!response.ok || payload?.status !== 'success') {
            throw new Error(
                payload?.message
                || (response.status === 403
                    ? 'Sesi keamanan kedaluwarsa. Muat ulang halaman lalu coba lagi.'
                    : 'Gagal menyimpan pengaturan.'),
            );
        }

        showMessage('Selesai, memuat ulang halaman...', 'success');
        window.location.assign(payload.redirect || form.dataset.redirectUrl || form.action);
    };

    const submit = async (event) => {
        event.preventDefault();
        if (form.dataset.settingsSubmitting === '1') return;

        setBusy(true);
        try {
            if (currentFileId && !completedUploadKey) {
                if (!navigator.onLine) {
                    throw new Error('Perangkat sedang offline. Upload dapat dilanjutkan setelah koneksi kembali.');
                }
                panel.hidden = false;
                hideMessage();
                startSpeed();

                const file = uppy.getFile(currentFileId);
                const result = file?.error ? await uppy.retryAll() : await uppy.upload();
                if (!currentFileId) {
                    setBusy(false);
                    return;
                }
                const successful = result?.successful?.[0];
                completedUploadKey = uploadKeyFromUrl(successful?.uploadURL || '');
                if (!completedUploadKey) {
                    throw new Error('Upload belum selesai. Klik Simpan untuk melanjutkan dari progres terakhir.');
                }
                uploadKeyInput.value = completedUploadKey;
            }

            stopSpeed();
            showMessage(
                completedUploadKey
                    ? 'Upload selesai, memvalidasi dan menyimpan media...'
                    : 'Menyimpan pengaturan...',
                'info',
                true,
            );
            await saveSettings();
        } catch (error) {
            showError(error?.message || 'Upload media gagal. Klik Simpan untuk mencoba kembali.');
        }
    };

    uppy.on('upload-success', (file, response) => {
        completedUploadKey = uploadKeyFromUrl(response?.uploadURL || '');
        uploadKeyInput.value = completedUploadKey;
        stopSpeed();
    });

    uppy.on('upload-error', () => {
        showError('Koneksi upload terputus. Klik Simpan untuk melanjutkan dari progres terakhir.');
    });

    uppy.on('cancel-all', clearSelectedUpload);

    const online = () => {
        if (panel.hidden || !currentFileId || completedUploadKey) return;
        const file = uppy.getFile(currentFileId);
        if (file?.error) {
            showMessage('Koneksi kembali. Klik Simpan untuk melanjutkan upload.', 'info');
            return;
        }
        hideMessage();
        startSpeed();
    };

    const offline = () => {
        if (!panel.hidden && currentFileId && !completedUploadKey) {
            stopSpeed();
            showMessage('Koneksi terputus. Progres tetap tersimpan.', 'info');
        }
    };

    mediaInput.addEventListener('change', selectFile);
    form.addEventListener('submit', submit);
    window.addEventListener('online', online);
    window.addEventListener('offline', offline);

    window.__dprdSettingsUpload = {
        destroy() {
            if (destroyed) return;
            destroyed = true;
            stopSpeed();
            themeObserver.disconnect();
            mediaInput.removeEventListener('change', selectFile);
            form.removeEventListener('submit', submit);
            window.removeEventListener('online', online);
            window.removeEventListener('offline', offline);
            uppy.destroy();
        },
    };
}

if (!window.__dprdSettingsUploadLifecycleBound) {
    window.__dprdSettingsUploadLifecycleBound = true;
    document.addEventListener('turbo:load', initSettingsUpload);
    document.addEventListener('turbo:before-cache', () => {
        window.__dprdSettingsUpload?.destroy();
        window.__dprdSettingsUpload = null;
    });
}

if (document.readyState !== 'loading') {
    initSettingsUpload();
}
