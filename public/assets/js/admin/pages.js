/* Page controllers for the Turbo-powered admin area. */
(() => {
    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const dayNames   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

    function setDate() {
        const now = new Date();
        const str = `${dayNames[now.getDay()]}, ${now.getDate()} ${monthNames[now.getMonth()]} ${now.getFullYear()}`;
        const el = document.getElementById('page-date');
        if (el) el.textContent = str;
    }

    function initDashboardCalendar(signal) {
        if (!document.querySelector('[data-dashboard-day]')) return;

        const buttons = document.querySelectorAll('[data-dashboard-day]');
        const panels = document.querySelectorAll('[data-dashboard-panel]');
        const summaries = document.querySelectorAll('[data-dashboard-summary]');
        const mobileAgendaQuery = window.matchMedia('(max-width: 520px)');
        const openAgendaButtons = document.querySelectorAll('[data-mobile-agenda-open]');
        const closeAgendaButtons = document.querySelectorAll('[data-mobile-agenda-close]');
        const agendaSheet = document.getElementById('dashboard-agenda-sheet');
        const agendaLabel = document.querySelector('[data-mobile-agenda-label]');
        let lastAgendaTrigger = null;

        function setAgendaSheetMode(isOpen) {
            if (!agendaSheet) return;

            if (mobileAgendaQuery.matches) {
                agendaSheet.setAttribute('role', 'dialog');
                agendaSheet.setAttribute('aria-modal', 'true');
                agendaSheet.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                return;
            }

            agendaSheet.setAttribute('role', 'region');
            agendaSheet.removeAttribute('aria-modal');
            agendaSheet.removeAttribute('aria-hidden');
        }

        function updateMobileAgendaLabel(date) {
            if (!agendaLabel) return;

            const activeSummary = document.querySelector(`[data-dashboard-summary="${date}"]`);
            agendaLabel.textContent = activeSummary
                ? activeSummary.textContent.trim()
                : 'Lihat agenda terpilih';
        }

        function openMobileAgenda(trigger) {
            if (mobileAgendaQuery.matches) {
                lastAgendaTrigger = trigger || document.activeElement;
                document.body.classList.add('mobile-agenda-open');
                setAgendaSheetMode(true);

                const closeButton = agendaSheet ? agendaSheet.querySelector('[data-mobile-agenda-close]') : null;
                if (closeButton) {
                    closeButton.focus({ preventScroll: true });
                }
            }
        }

        function closeMobileAgenda() {
            document.body.classList.remove('mobile-agenda-open');
            setAgendaSheetMode(false);

            if (lastAgendaTrigger && typeof lastAgendaTrigger.focus === 'function') {
                lastAgendaTrigger.focus({ preventScroll: true });
            }
        }

        const activeButton = document.querySelector('[data-dashboard-day][aria-selected="true"]');
        if (activeButton) {
            updateMobileAgendaLabel(activeButton.dataset.dashboardDay);
        }
        setAgendaSheetMode(false);

        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const date = button.dataset.dashboardDay;

                buttons.forEach(item => {
                    const active = item === button;
                    item.classList.toggle('active', active);
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                panels.forEach(panel => {
                    const active = panel.dataset.dashboardPanel === date;
                    panel.hidden = !active;
                    panel.classList.toggle('active', active);
                });

                summaries.forEach(summary => {
                    summary.hidden = summary.dataset.dashboardSummary !== date;
                });

                updateMobileAgendaLabel(date);

                if (window.lucide) {
                    window.lucide.createIcons();
                }

                openMobileAgenda(button);
            });
        });

        openAgendaButtons.forEach(button => {
            button.addEventListener('click', () => openMobileAgenda(button));
        });

        closeAgendaButtons.forEach(button => {
            button.addEventListener('click', closeMobileAgenda);
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && document.body.classList.contains('mobile-agenda-open')) {
                closeMobileAgenda();
            }
        }, { signal });

        mobileAgendaQuery.addEventListener('change', event => {
            if (!event.matches) {
                closeMobileAgenda();
            } else {
                setAgendaSheetMode(document.body.classList.contains('mobile-agenda-open'));
            }
        }, { signal });
    }

    let dashboardController = null;
    document.addEventListener('turbo:load', () => {
        dashboardController?.abort();
        dashboardController = new AbortController();
        setDate();
        initDashboardCalendar(dashboardController.signal);
    });
})();

(function() {
    const initScheduleForm = function() {
        const form = document.querySelector('.schedule-form');
        if (!form) return;
        const requiresTargets = form.dataset.requireTargets !== 'false';
        const toggle = document.getElementById('is_publik');
        const label = document.getElementById('publik-label');
        if (toggle && label) {
            toggle.addEventListener('change', function() {
                label.textContent = this.checked
                    ? 'Agenda dapat tampil pada kanal publik.'
                    : 'Default internal, hanya terlihat oleh pengguna berwenang.';
            });
        }

        const lokasiModeInputs = Array.from(document.querySelectorAll('input[name="lokasi_mode"]'));
        const ruanganPanel = document.getElementById('ruangan-panel');
        const lokasiLainnyaPanel = document.getElementById('lokasi-lainnya-panel');
        const ruanganSelect = document.getElementById('ruangan_id');
        const lokasiLainnyaInput = document.getElementById('lokasi_lainnya');

        const syncLocationMode = function() {
            const mode = lokasiModeInputs.find(function(input) {
                return input.checked;
            })?.value || 'ruangan';

            const isOther = mode === 'lainnya';

            if (ruanganPanel) ruanganPanel.hidden = isOther;
            if (lokasiLainnyaPanel) lokasiLainnyaPanel.hidden = !isOther;

            if (ruanganSelect) {
                ruanganSelect.required = !isOther;
                ruanganSelect.disabled = isOther;
            }

            if (lokasiLainnyaInput) {
                lokasiLainnyaInput.required = isOther;
                lokasiLainnyaInput.disabled = !isOther;
            }
        };

        lokasiModeInputs.forEach(function(input) {
            input.addEventListener('change', syncLocationMode);
        });
        syncLocationMode();

        const tanggalInput = document.getElementById('tanggal');
        const waktuMulaiInput = document.getElementById('waktu_mulai');
        const waktuSelesaiInput = document.getElementById('waktu_selesai');
        const waktuError = document.getElementById('waktu-rapat-error');

        const syncTimeValidity = function() {
            if (!waktuMulaiInput || !waktuSelesaiInput) return true;

            const hasSeparateDate = tanggalInput?.value
                && waktuMulaiInput.type === 'time'
                && waktuSelesaiInput.type === 'time';
            const startValue = hasSeparateDate
                ? `${tanggalInput.value}T${waktuMulaiInput.value}`
                : waktuMulaiInput.value;
            const endValue = hasSeparateDate
                ? `${tanggalInput.value}T${waktuSelesaiInput.value}`
                : waktuSelesaiInput.value;
            const start = startValue ? new Date(startValue) : null;
            const end = endValue ? new Date(endValue) : null;
            if (!start || !end) {
                waktuMulaiInput.classList.remove('input-error');
                waktuSelesaiInput.classList.remove('input-error');
                waktuSelesaiInput.setCustomValidity('');
                if (waktuError) waktuError.classList.add('hidden');
                return true;
            }

            const sameDate = hasSeparateDate
                || waktuMulaiInput.value.slice(0, 10) === waktuSelesaiInput.value.slice(0, 10);
            const valid = !!(start && end && end > start && sameDate);

            waktuMulaiInput.classList.toggle('input-error', !valid && !!waktuMulaiInput.value);
            waktuSelesaiInput.classList.toggle('input-error', !valid && !!waktuSelesaiInput.value);
            if (waktuError) waktuError.classList.toggle('hidden', valid || !waktuMulaiInput.value || !waktuSelesaiInput.value);

            waktuSelesaiInput.setCustomValidity(valid ? '' : 'Waktu selesai harus setelah waktu mulai pada tanggal yang sama.');

            return valid;
        };

        tanggalInput?.addEventListener('change', syncTimeValidity);
        waktuMulaiInput?.addEventListener('change', syncTimeValidity);
        waktuSelesaiInput?.addEventListener('change', syncTimeValidity);

        const targetInputs = Array.from(document.querySelectorAll('.target-option input[type="checkbox"]'));
        const targetOptions = Array.from(document.querySelectorAll('.target-option'));
        const targetSearch = document.getElementById('target-search');
        const targetSelectedCount = document.getElementById('target-selected-count');
        const targetEmpty = document.getElementById('target-empty');
        const targetError = document.getElementById('target-peserta-error');

        const syncTargetVisual = function(input) {
            const option = input.closest('.target-option');
            if (!option) return;

            option.classList.toggle('is-selected', input.checked);
            option.classList.toggle('bg-primary/10', input.checked);
            option.classList.toggle('text-primary', input.checked);
            option.classList.toggle('font-semibold', input.checked);
        };

        const syncTargetCount = function() {
            const count = targetInputs.filter(function(input) {
                return input.checked && !input.disabled;
            }).length;

            if (targetSelectedCount) {
                targetSelectedCount.textContent = count + ' dipilih';
            }

            return count;
        };

        const syncTargetValidity = function() {
            const count = syncTargetCount();
            const valid = !requiresTargets || count > 0;

            if (targetError) targetError.classList.toggle('hidden', valid);
            targetInputs.forEach(function(input) {
                if (!input.disabled) input.classList.toggle('checkbox-error', !valid);
            });

            return valid;
        };

        targetInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                syncTargetVisual(this);
                syncTargetValidity();
            });

            syncTargetVisual(input);
        });

        targetSearch?.addEventListener('input', function() {
            const q = (this.value || '').trim().toLowerCase();
            let shown = 0;

            targetOptions.forEach(function(option) {
                const match = (option.getAttribute('data-name') || '').includes(q);
                option.style.display = match ? '' : 'none';
                if (match) shown++;
            });

            if (targetEmpty) {
                targetEmpty.classList.toggle('hidden', shown > 0);
            }
        });

        form?.addEventListener('submit', function(event) {
            const timeValid = syncTimeValidity();
            const targetValid = syncTargetValidity();

            if (!timeValid || !targetValid) {
                event.preventDefault();
                if (!timeValid) {
                    waktuSelesaiInput?.focus();
                } else {
                    targetInputs.find(function(input) { return !input.disabled; })?.focus();
                }
            }
        });

        syncTimeValidity();
        syncTargetCount();
    };

    document.addEventListener('turbo:load', initScheduleForm);
    })();

(() => {
    function initSettingsPage() {
        const form = document.getElementById('settings-form');
        if (!form) return;

        document.getElementById('running_text')?.addEventListener('input', function () {
            document.getElementById('preview-text').textContent = this.value || 'Teks berjalan akan tampil di sini...';
        });

        setupSettingsSubmitProgress(form);
        window.renderAdminIcons?.();
    }

    function setupSettingsSubmitProgress(form) {
        if (form.dataset.settingsUploadBound === 'true') return;

        const panel = document.getElementById('settings-upload-progress');
        const bar = document.getElementById('settings-upload-bar');
        const status = document.getElementById('settings-upload-status');
        const percent = document.getElementById('settings-upload-percent');
        const speed = document.getElementById('settings-upload-speed');
        const submitButton = document.getElementById('settings-submit-button');
        const submitSpinner = document.getElementById('settings-submit-spinner');
        const submitIcon = document.getElementById('settings-submit-icon');
        const submitLabel = document.getElementById('settings-submit-label');
        const mediaInput = document.getElementById('media_file');
        const uploadKeyInput = document.getElementById('media_upload_key');

        if (!panel || !bar || !status || !percent || !submitButton || !mediaInput || !uploadKeyInput
            || !window.FormData || !window.XMLHttpRequest) {
            return;
        }

        form.dataset.settingsUploadBound = 'true';
        const maxBytes = Number(form.dataset.uploadMax) || 200 * 1024 * 1024;
        const configuredChunkSize = Number(form.dataset.uploadChunkSize) || 512 * 1024;
        const retryDelays = [0, 1000, 3000, 5000, 10000, 20000];
        let uploadStartedAt = 0;
        let uploadInitialOffset = 0;
        let currentAcceptedOffset = 0;

        const formatSpeed = (bytesPerSecond) => {
            const formatter = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 1,
            });

            return bytesPerSecond >= 1024 * 1024
                ? `${formatter.format(bytesPerSecond / 1024 / 1024)} MB/dtk`
                : `${formatter.format(bytesPerSecond / 1024)} KB/dtk`;
        };

        const setProgress = (value, message) => {
            const safeValue = Math.max(0, Math.min(100, Math.round(value)));
            bar.value = safeValue;
            percent.textContent = safeValue + '%';
            status.textContent = message;
        };

        const setBusy = (busy) => {
            form.dataset.settingsSubmitting = busy ? '1' : '0';
            form.setAttribute('aria-busy', busy ? 'true' : 'false');
            submitButton.disabled = busy;
            if (submitSpinner) submitSpinner.hidden = !busy;
            if (submitIcon) submitIcon.hidden = busy;
            if (submitLabel) submitLabel.textContent = busy ? 'Menyimpan...' : 'Simpan Pengaturan';
        };

        const preparePanel = (hasMediaFile) => {
            panel.hidden = false;
            panel.classList.remove('alert-error');
            panel.classList.add('alert-info');
            bar.classList.remove('progress-error');
            bar.classList.add('progress-primary');
            if (speed) {
                speed.textContent = 'Mengukur kecepatan...';
                speed.hidden = !hasMediaFile;
            }
        };

        const showError = (message) => {
            const currentValue = Number(bar.value) || 0;
            panel.hidden = false;
            panel.classList.remove('alert-info');
            panel.classList.add('alert-error');
            bar.classList.remove('progress-primary');
            bar.classList.add('progress-error');
            if (speed) speed.hidden = true;
            setProgress(currentValue, message || 'Gagal menyimpan pengaturan.');
            setBusy(false);
        };

        const sleep = (milliseconds) => new Promise((resolve) => window.setTimeout(resolve, milliseconds));

        const bytesToHex = (buffer) => Array.from(new Uint8Array(buffer))
            .map((byte) => byte.toString(16).padStart(2, '0'))
            .join('');

        const sha256 = async (value) => {
            if (!window.crypto?.subtle) {
                throw new Error('Browser tidak mendukung checksum upload yang aman.');
            }

            const buffer = value instanceof ArrayBuffer ? value : await value.arrayBuffer();
            return bytesToHex(await window.crypto.subtle.digest('SHA-256', buffer));
        };

        const fileFingerprint = async (file) => {
            const sampleSize = 64 * 1024;
            const first = await file.slice(0, Math.min(sampleSize, file.size)).arrayBuffer();
            const lastStart = Math.max(0, file.size - sampleSize);
            const last = await file.slice(lastStart, file.size).arrayBuffer();
            const metadata = new TextEncoder().encode(
                `${file.name}\n${file.type}\n${file.size}\n${file.lastModified}\n`,
            );
            const combined = new Uint8Array(metadata.byteLength + first.byteLength + last.byteLength);
            combined.set(metadata, 0);
            combined.set(new Uint8Array(first), metadata.byteLength);
            combined.set(new Uint8Array(last), metadata.byteLength + first.byteLength);

            return sha256(combined.buffer);
        };

        const requestJson = (url, formData, onProgress = null, ajaxRequest = false) => new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            if (ajaxRequest) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
            }
            if (onProgress) xhr.upload.addEventListener('progress', onProgress);

            xhr.addEventListener('load', () => {
                let payload = null;
                try {
                    payload = JSON.parse(xhr.responseText);
                } catch {
                    payload = null;
                }

                if (xhr.status >= 200 && xhr.status < 300 && payload?.status === 'success') {
                    resolve(payload);
                    return;
                }

                reject({
                    status: xhr.status,
                    payload,
                    message: payload?.message || '',
                });
            });
            xhr.addEventListener('error', () => reject({
                status: 0,
                payload: null,
                message: 'Koneksi terputus saat mengunggah file.',
            }));
            xhr.addEventListener('abort', () => reject({
                status: 0,
                payload: null,
                message: 'Upload dibatalkan.',
            }));
            xhr.send(formData);
        });

        const postWithRetry = async (url, createFormData, onProgress = null) => {
            let lastError = null;

            for (let attempt = 0; attempt < retryDelays.length; attempt += 1) {
                if (retryDelays[attempt] > 0) {
                    status.textContent = `Koneksi terputus, mencoba lagi (${attempt + 1}/${retryDelays.length})...`;
                    await sleep(retryDelays[attempt]);
                }

                try {
                    return await requestJson(url, createFormData(), onProgress);
                } catch (error) {
                    lastError = error;
                    if (error.status > 0 && error.status < 500 && error.status !== 409) {
                        throw error;
                    }
                }
            }

            throw lastError || { status: 0, message: 'Upload gagal setelah beberapa percobaan.' };
        };

        const createStartData = (file, clientKey) => {
            const data = new FormData();
            data.append('upload_token', form.dataset.uploadToken || '');
            data.append('client_key', clientKey);
            data.append('file_name', file.name);
            data.append('file_size', String(file.size));
            data.append('file_type', file.type);
            return data;
        };

        const beginUpload = (file, clientKey) => postWithRetry(
            form.dataset.uploadStartUrl,
            () => createStartData(file, clientKey),
        );

        const updateUploadProgress = (fileSize, acceptedOffset, chunkLoaded = 0) => {
            const uploaded = Math.min(fileSize, acceptedOffset + chunkLoaded);
            const value = fileSize > 0 ? (uploaded / fileSize) * 100 : 0;
            const elapsedSeconds = (performance.now() - uploadStartedAt) / 1000;
            const transferred = Math.max(0, uploaded - uploadInitialOffset);

            if (speed && elapsedSeconds >= 0.5 && transferred > 0) {
                speed.textContent = `Kecepatan rata-rata ${formatSpeed(transferred / elapsedSeconds)}`;
            }
            setProgress(value, value >= 100 ? 'Upload selesai, memproses file...' : 'Mengunggah file media...');
        };

        const uploadFileInChunks = async (file) => {
            status.textContent = 'Memeriksa file dan mencari upload sebelumnya...';
            const clientKey = await fileFingerprint(file);
            let uploadState = await beginUpload(file, clientKey);
            let offset = Number(uploadState.offset) || 0;
            const chunkSize = Math.min(Number(uploadState.chunk_size) || configuredChunkSize, configuredChunkSize);

            uploadInitialOffset = offset;
            currentAcceptedOffset = offset;
            uploadStartedAt = performance.now();
            updateUploadProgress(file.size, offset);

            while (offset < file.size) {
                const chunk = file.slice(offset, Math.min(offset + chunkSize, file.size));
                const checksum = await sha256(chunk);
                const chunkOffset = offset;
                const createChunkData = () => {
                    const data = new FormData();
                    data.append('upload_token', form.dataset.uploadToken || '');
                    data.append('upload_id', uploadState.upload_id);
                    data.append('offset', String(chunkOffset));
                    data.append('checksum', checksum);
                    data.append('chunk', chunk, 'chunk.bin');
                    return data;
                };

                try {
                    uploadState = await postWithRetry(
                        form.dataset.uploadChunkUrl,
                        createChunkData,
                        (event) => updateUploadProgress(
                            file.size,
                            currentAcceptedOffset,
                            event.lengthComputable ? Math.min(event.loaded, chunk.size) : 0,
                        ),
                    );
                } catch (error) {
                    if (error.status !== 409) throw error;
                    uploadState = await beginUpload(file, clientKey);
                }

                offset = Number(uploadState.offset) || 0;
                currentAcceptedOffset = offset;
                updateUploadProgress(file.size, offset);
            }

            if (!uploadState.completed) {
                uploadState = await beginUpload(file, clientKey);
            }
            if (!uploadState.completed) {
                throw { status: 409, message: 'Server belum menandai upload sebagai selesai.' };
            }

            return uploadState.upload_id;
        };

        const refreshCsrf = (csrf) => {
            if (!csrf?.name || !csrf?.hash) return;

            const csrfInput = Array.from(form.elements).find((element) => element.name === csrf.name);
            if (csrfInput) csrfInput.value = csrf.hash;
        };

        const saveSettings = async () => {
            const formData = new FormData(form);
            formData.delete('media_file');

            return requestJson(form.action, formData, null, true);
        };

        const errorMessage = (error) => {
            if (error?.message) return error.message;
            if (error?.status === 413) {
                return 'Bagian file masih melebihi batas server. Atur batas request server di atas 512 KB.';
            }
            if (error?.status === 403) {
                return 'Sesi upload ditolak server. Muat ulang halaman lalu coba lagi.';
            }

            return 'Gagal menyimpan pengaturan. Periksa koneksi dan coba lagi.';
        };

        mediaInput.addEventListener('change', () => {
            uploadKeyInput.value = '';
            panel.hidden = true;
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (form.dataset.settingsSubmitting === '1') {
                return;
            }

            const mediaFile = mediaInput?.files?.[0] || null;
            if (mediaFile && mediaFile.size > maxBytes) {
                showError('Ukuran file melebihi batas 200 MB.');
                return;
            }

            preparePanel(mediaFile !== null);
            setBusy(true);
            setProgress(mediaFile ? 0 : 100, mediaFile ? 'Menyiapkan upload...' : 'Menyimpan pengaturan...');

            try {
                if (mediaFile) {
                    uploadKeyInput.value = await uploadFileInChunks(mediaFile);
                }

                status.textContent = 'Menyimpan pengaturan...';
                if (speed) speed.hidden = true;
                const payload = await saveSettings();
                refreshCsrf(payload?.csrf);
                setProgress(100, 'Selesai, memuat ulang halaman...');
                window.location.assign(payload.redirect || form.dataset.redirectUrl || form.action);
            } catch (error) {
                refreshCsrf(error?.payload?.csrf);
                showError(errorMessage(error));
            }
        });
    }

    document.addEventListener('turbo:load', initSettingsPage);
})();

(function() {
    const initUnitForm = function() {
        const form = document.getElementById('unit-form');
        if (!form) return;
        const aktifToggle = document.getElementById('aktif');
        const aktifLabel = document.getElementById('aktif-label');
        const anggotaError = document.getElementById('anggota-kelompok-error');
        const sourceSearch = document.getElementById('source-search');
        const targetList = document.getElementById('target-list');
        const targetCount = document.getElementById('target-count');
        const sourceCount = document.getElementById('source-count');
        const memberCountBadge = document.getElementById('member-count-badge');
        const allSourceItems = document.querySelectorAll('.anggota-source');
        const allCheckboxes = document.querySelectorAll('.source-checkbox');

        const selectedMemberCount = function() {
            return document.querySelectorAll('.source-checkbox:checked').length;
        };

        const syncMemberValidity = function(showError) {
            const invalid = !!(aktifToggle?.checked && selectedMemberCount() === 0);
            const shouldShow = invalid && showError === true;
            if (anggotaError) anggotaError.classList.toggle('hidden', !shouldShow);
            allCheckboxes.forEach(function(cb) {
                cb.classList.toggle('checkbox-error', shouldShow);
            });
            return !invalid;
        };

        aktifToggle?.addEventListener('change', function() {
            if (aktifLabel) aktifLabel.textContent = this.checked ? 'Aktif' : 'Nonaktif';
            syncMemberValidity(true);
        });

        const updateTargetPanel = function() {
            if (!targetList || !targetCount) return;

            allCheckboxes.forEach(function(cb) {
                const src = cb.closest('.anggota-source');
                if (src) {
                    src.classList.toggle('bg-primary/10', cb.checked);
                    src.classList.toggle('text-primary', cb.checked);
                }
            });

            const checked = document.querySelectorAll('.source-checkbox:checked');
            const count = checked.length;

            targetCount.textContent = count;
            if (memberCountBadge) memberCountBadge.textContent = count + ' dipilih';

            targetList.querySelectorAll('.transfer-target-item').forEach(el => el.remove());

            const emptyEl = targetList.querySelector('#target-empty');
            if (emptyEl) emptyEl.remove();

            if (count === 0) {
                const div = document.createElement('div');
                div.className = 'flex flex-col items-center justify-center text-base-content/50 py-4 gap-1';
                div.id = 'target-empty';
                div.innerHTML = '<i data-lucide="shuffle" class="w-5 h-5 opacity-40"></i><small>Pilih anggota dari panel kiri</small>';
                targetList.appendChild(div);
                window.renderAdminIcons?.();
                syncMemberValidity(true);
                return;
            }

            checked.forEach(function(cb) {
                const src = cb.closest('.anggota-source');
                if (!src) return;

                const id = src.getAttribute('data-id');
                const name = src.querySelector('.font-semibold')?.textContent || '';
                const detail = src.querySelector('.member-detail')?.textContent?.trim() || '';
                const initial = name.trim().charAt(0).toUpperCase();

                const el = document.createElement('div');
                el.className = 'flex items-center gap-2 px-3 py-1 border-b transfer-target-item min-h-[42px]';
                el.id = 'target-' + id;
                el.setAttribute('data-id', id);

                const avatar = document.createElement('span');
                avatar.className = 'inline-flex items-center justify-center rounded shrink-0 bg-primary text-primary-content w-7 h-7 text-xs font-bold';
                avatar.textContent = initial;

                const memberContent = document.createElement('div');
                memberContent.className = 'flex-1 min-w-0';

                const memberName = document.createElement('div');
                memberName.className = 'text-xs font-semibold truncate';
                memberName.textContent = name;

                const memberDetail = document.createElement('div');
                memberDetail.className = 'text-[11px] text-base-content/60 truncate';
                memberDetail.textContent = detail;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-sm btn-ghost btn-circle text-error w-6 h-6 min-h-6 leading-none';
                removeButton.title = 'Hapus dari unit';

                const removeIcon = document.createElement('i');
                removeIcon.setAttribute('data-lucide', 'x');
                removeIcon.className = 'w-3.5 h-3.5';

                memberContent.append(memberName, memberDetail);
                removeButton.appendChild(removeIcon);
                el.append(avatar, memberContent, removeButton);

                removeButton.addEventListener('click', function() {
                    window.removeMember?.(parseInt(id, 10));
                });

                targetList.appendChild(el);
            });

            window.renderAdminIcons?.();
            syncMemberValidity(true);
        };

        allCheckboxes.forEach(function(cb) {
            cb.addEventListener('change', updateTargetPanel);
        });

        form?.addEventListener('submit', function(event) {
            if (!syncMemberValidity(true)) {
                event.preventDefault();
                allCheckboxes[0]?.focus();
            }
        });

        form.addEventListener('click', function(event) {
            const removeButton = event.target.closest('[data-remove-member]');
            if (!removeButton) return;
            window.removeMember?.(parseInt(removeButton.getAttribute('data-remove-member'), 10));
        });

        window.removeMember = function(memberId) {
            const cb = document.getElementById('src-' + memberId);
            if (cb) {
                cb.checked = false;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        sourceSearch?.addEventListener('input', function() {
            const q = (this.value || '').trim().toLowerCase();
            let n = 0;
            allSourceItems.forEach(function(item) {
                const name = item.getAttribute('data-name') || '';
                const komisi = item.getAttribute('data-komisi') || '';
                const jabatan = item.getAttribute('data-jabatan') || '';
                const match = name.includes(q) || komisi.includes(q) || jabatan.includes(q);
                item.style.display = match ? '' : 'none';
                if (match) n++;
            });
            if (sourceCount) sourceCount.textContent = n;
        });

        syncMemberValidity(false);
    };

    document.addEventListener('turbo:load', initUnitForm);
    })();

(() => {
        const initializeBanmusForm = () => {
            const form = document.querySelector('[data-banmus-form]');
            if (!form) return;

            const container = form.querySelector('[data-items-container]');
            const template = form.querySelector('[data-item-template]');

            const resizeTextarea = (field) => {
                if (!(field instanceof HTMLTextAreaElement)) return;

                field.style.height = 'auto';
                const borderHeight = field.offsetHeight - field.clientHeight;
                field.style.height = `${Math.max(64, field.scrollHeight + borderHeight)}px`;
            };

            const refreshItems = () => {
                const rows = [...container.querySelectorAll('[data-banmus-item]')];
                rows.forEach((row, position) => {
                    row.querySelector('[data-item-index]').textContent = String(position + 1);
                    row.querySelectorAll('[data-field]').forEach((field) => {
                        field.name = `items[${position}][${field.dataset.field}]`;
                        resizeTextarea(field);
                    });

                    const removeButton = row.querySelector('[data-remove-item]');
                    removeButton.disabled = rows.length === 1;
                    removeButton.setAttribute('aria-label', `Hapus baris ${position + 1}`);
                });
            };

            container.addEventListener('input', (event) => {
                resizeTextarea(event.target.closest('textarea[data-field]'));
            });

            form.querySelectorAll('[data-add-item]').forEach((button) => {
                button.addEventListener('click', () => {
                    const wrapper = document.createElement('tbody');
                    wrapper.innerHTML = template.innerHTML.trim();
                    const row = wrapper.firstElementChild;
                    container.appendChild(row);
                    refreshItems();
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                    row.querySelector('[data-field]')?.focus();
                });
            });

            container.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-item]');
                if (!removeButton || container.querySelectorAll('[data-banmus-item]').length <= 1) {
                    return;
                }

                removeButton.closest('[data-banmus-item]')?.remove();
                refreshItems();
            });

            refreshItems();
        };

        document.addEventListener('turbo:load', initializeBanmusForm);
    })();

(() => {
    const initializeBanmusItemWorkspace = () => {
        const dialog = document.querySelector('[data-banmus-item-dialog]');
        if (!(dialog instanceof HTMLDialogElement)) return;
        if (dialog.dataset.initialized === 'true') return;
        dialog.dataset.initialized = 'true';

        const form = dialog.querySelector('#item_form');
        const title = dialog.querySelector('#modal_title span');
        const dateField = dialog.querySelector('#field_tanggal');
        const roomField = dialog.querySelector('#field_ruangan_id');
        const locationField = dialog.querySelector('#field_lokasi_lainnya');
        const locationWrapper = dialog.querySelector('#field_lokasi_lainnya_wrapper');
        const unitCheckboxes = [...dialog.querySelectorAll('.unit-checkbox')];
        const agendaTypeFields = [...dialog.querySelectorAll('input[name="jenis_agenda"]')];
        const invitationExisting = dialog.querySelector('#field_undangan_existing');
        const invitationName = dialog.querySelector('#field_undangan_name');

        if (!(form instanceof HTMLFormElement)
            || !(dateField instanceof HTMLInputElement)
            || !(roomField instanceof HTMLSelectElement)) {
            return;
        }

        const field = (id) => dialog.querySelector(`#${id}`);

        const syncLocationDisclosure = () => {
            locationWrapper?.classList.toggle('hidden', roomField.value !== 'other');
        };

        const showDialog = () => {
            syncLocationDisclosure();
            if (!dialog.open) dialog.showModal();
        };

        const openCreateDialog = () => {
            form.reset();
            invitationExisting?.classList.add('hidden');
            form.action = dialog.dataset.storeUrl || '';
            if (title) title.textContent = 'Tambah Item Agenda Banmus';
            showDialog();
        };

        const parseUnitIds = (value) => {
            if (Array.isArray(value)) return value.map(Number);
            if (typeof value !== 'string' || value.trim() === '') return [];

            try {
                const parsed = JSON.parse(value);
                return Array.isArray(parsed) ? parsed.map(Number) : [];
            } catch {
                return [];
            }
        };

        const openEditDialog = (item) => {
            form.reset();
            form.action = (dialog.dataset.updateUrlTemplate || '')
                .replace('__ITEM_ID__', encodeURIComponent(String(item.id || '')));

            if (title) title.textContent = 'Edit Item Agenda Banmus';
            field('field_agenda').value = item.agenda || '';
            field('field_periode_label').value = item.periode_label || '';
            const agendaType = ['rapat', 'non_rapat'].includes(item.jenis_agenda)
                ? item.jenis_agenda
                : 'rapat';
            agendaTypeFields.forEach((input) => {
                input.checked = input.value === agendaType;
            });
            dateField.value = item.tanggal || '';
            field('field_jam_mulai').value = item.jam_mulai ? item.jam_mulai.substring(0, 5) : '';
            field('field_jam_selesai').value = item.jam_selesai ? item.jam_selesai.substring(0, 5) : '';
            field('field_catatan').value = item.catatan || '';
            field('field_publikasi').value = item.publikasi || 'publik';
            field('field_materi_url').value = item.materi_url || '';
            field('field_materi_akses').value = item.materi_akses || 'publik';
            field('field_stream_url').value = item.stream_url || '';
            field('field_stream_akses').value = item.stream_akses || 'publik';
            if (invitationName) invitationName.textContent = item.undangan_nama_asli || 'undangan-rapat.pdf';
            invitationExisting?.classList.toggle('hidden', !item.undangan_file);

            if (item.ruangan_id) {
                roomField.value = String(item.ruangan_id);
            } else if (item.lokasi_lainnya) {
                roomField.value = 'other';
                if (locationField) locationField.value = item.lokasi_lainnya;
            } else {
                roomField.value = '';
            }

            const unitIds = parseUnitIds(item.unit_ids);
            unitCheckboxes.forEach((checkbox) => {
                checkbox.checked = unitIds.includes(Number(checkbox.value));
            });

            showDialog();
        };

        document.querySelectorAll('[data-banmus-item-open]').forEach((button) => {
            button.addEventListener('click', openCreateDialog);
        });

        document.querySelectorAll('[data-banmus-item-edit]').forEach((button) => {
            button.addEventListener('click', () => {
                try {
                    openEditDialog(JSON.parse(button.dataset.item || '{}'));
                } catch {
                    // Payload edit invalid: biarkan dialog tetap tertutup.
                }
            });
        });

        dialog.querySelectorAll('[data-banmus-item-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });

        roomField.addEventListener('change', syncLocationDisclosure);
    };

    document.addEventListener('turbo:load', initializeBanmusItemWorkspace);
    document.addEventListener('turbo:before-cache', () => {
        const dialog = document.querySelector('[data-banmus-item-dialog]');
        if (dialog instanceof HTMLDialogElement && dialog.open) dialog.close();
    });
})();

(() => {
    const initializeNotulenUploadWorkspace = () => {
        const modal = document.getElementById('modal_upload_notulen');
        if (!(modal instanceof HTMLDialogElement)) return;
        if (modal.dataset.initialized === 'true') return;
        modal.dataset.initialized = 'true';

        const openBtn     = document.getElementById('btn_open_upload_modal');
        const submitBtn   = document.getElementById('um_submit_btn');
        const cancelBtn   = document.getElementById('um_cancel_btn');
        const closeBtn    = document.getElementById('um_close_btn');
        const backdropBtn = document.getElementById('um_backdrop_btn');
        const retryBtn    = document.getElementById('um_retry_btn');
        const fileInput   = document.getElementById('modal_audio_file');

        const judulInput  = document.getElementById('modal_judul_rapat');

        const jadwalType  = document.getElementById('modal_jadwal_type');
        const jadwalId    = document.getElementById('modal_jadwal_id');
        const agendaDropdown      = document.getElementById('um_agenda_dropdown');
        const agendaTrigger       = document.getElementById('um_agenda_trigger');
        const agendaSelectedLabel = document.getElementById('um_agenda_selected_label');
        const agendaSearchInput   = document.getElementById('um_agenda_search_input');
        const agendaOptionsList   = document.getElementById('um_agenda_options_list');




        // Dropzone refs
        const dropzone    = document.getElementById('um_dropzone');
        const dzIdle      = document.getElementById('um_dz_idle');
        const dzSelected  = document.getElementById('um_dz_selected');
        const dzFilename  = document.getElementById('um_dz_filename');
        const dzFilemeta  = document.getElementById('um_dz_filemeta');
        const dzChangeBtn = document.getElementById('um_dz_change_btn');
        const dzIconWrap  = document.getElementById('um_dz_icon_wrap');

        // Confirm dialog refs
        const confirmDialog    = document.getElementById('um_confirm_dialog');
        const confirmKeepBtn   = document.getElementById('um_confirm_keep_btn');
        const confirmCancelBtn = document.getElementById('um_confirm_cancel_btn');
        let pendingCloseAction = null;

        // Server config dari data attributes
        const UPLOAD_TOKEN = modal.dataset.uploadToken || '';
        const START_URL    = modal.dataset.startUrl    || '';
        const CHUNK_URL    = modal.dataset.chunkUrl    || '';
        const CANCEL_URL   = modal.dataset.cancelUrl   || '';
        const COMMIT_URL   = modal.dataset.commitUrl   || '';
        const CHUNK_SIZE   = parseInt(modal.dataset.chunkSize, 10) || 524288;
        const CSRF_NAME    = modal.dataset.csrfName    || '';
        const CSRF_VALUE   = modal.dataset.csrfValue   || '';
        const MAX_SIZE     = parseInt(modal.dataset.maxSize, 10) || 314572800; // 300 MB

        const ALLOWED_TYPES = ['audio/mpeg', 'audio/mp4', 'audio/x-m4a', 'audio/wav', 'audio/wave',
            'audio/ogg', 'audio/aac', 'audio/flac', 'audio/x-flac', 'video/mp4'];
        const ALLOWED_EXT   = /\.(mp3|m4a|wav|ogg|aac|flac|mp4)$/i;

        const retryDelays           = [0, 1500, 4000, 8000];
        let activeUploadId        = null;
        let uploadStartedAt       = 0;
        let uploadInitialOffset   = 0;
        let currentAcceptedOffset = 0;
        let isCancelling          = false;
        let isUploading           = false;
        let lastFile              = null;

        function rerenderIcons() {
            if (window.lucide && window.lucide.createIcons) {
                window.lucide.createIcons({ attrs: { 'stroke-width': 1.75 } });
            }
        }

        function openConfirmDialog(onConfirm) {
            pendingCloseAction = onConfirm;
            if (confirmDialog instanceof HTMLDialogElement) {
                confirmDialog.showModal();
                rerenderIcons();
            }
        }

        if (confirmKeepBtn) {
            confirmKeepBtn.addEventListener('click', () => {
                pendingCloseAction = null;
                if (confirmDialog instanceof HTMLDialogElement) confirmDialog.close();
            });
        }

        if (confirmCancelBtn) {
            confirmCancelBtn.addEventListener('click', () => {
                if (confirmDialog instanceof HTMLDialogElement) confirmDialog.close();
                if (typeof pendingCloseAction === 'function') {
                    pendingCloseAction();
                    pendingCloseAction = null;
                }
            });
        }

        function showDropzoneIdle() {
            if (!dzIdle || !dzSelected || !dropzone) return;
            dzIdle.classList.remove('hidden');
            dzIdle.classList.add('flex');
            dzSelected.classList.remove('flex');
            dzSelected.classList.add('hidden');
            dropzone.classList.remove('border-success', 'bg-success/5');
            dropzone.classList.add('border-dashed', 'border-base-300', 'bg-base-200/50');
        }

        function showDropzoneSelected(file) {
            if (!dzIdle || !dzSelected || !dropzone) return;
            const mb  = (file.size / 1048576).toFixed(1);
            const ext = file.name.split('.').pop().toUpperCase();
            if (dzFilename) dzFilename.textContent = file.name;
            if (dzFilemeta) dzFilemeta.textContent = mb + ' MB · ' + ext;
            dzIdle.classList.remove('flex');
            dzIdle.classList.add('hidden');
            dzSelected.classList.remove('hidden');
            dzSelected.classList.add('flex');
            dropzone.classList.remove('border-dashed', 'border-base-300', 'bg-base-200/50');
            dropzone.classList.add('border-success', 'bg-success/5');
        }

        if (dropzone) {
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (fileInput && fileInput.files && fileInput.files[0]) return;
                dropzone.classList.add('border-primary', 'bg-primary/5', 'scale-[1.01]');
                dropzone.classList.remove('border-base-300');
                if (dzIconWrap) dzIconWrap.classList.add('bg-primary/20');
            });

            dropzone.addEventListener('dragleave', (e) => {
                if (dropzone.contains(e.relatedTarget)) return;
                dropzone.classList.remove('border-primary', 'bg-primary/5', 'scale-[1.01]');
                dropzone.classList.add('border-base-300');
                if (dzIconWrap) dzIconWrap.classList.remove('bg-primary/20');
            });

            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('border-primary', 'bg-primary/5', 'scale-[1.01]');
                dropzone.classList.add('border-base-300');
                if (dzIconWrap) dzIconWrap.classList.remove('bg-primary/20');
                const files = e.dataTransfer ? e.dataTransfer.files : null;
                if (files && files[0] && fileInput) {
                    try {
                        const dt = new DataTransfer();
                        dt.items.add(files[0]);
                        fileInput.files = dt.files;
                    } catch (err) { /* fallback skip */ }
                    fileInput.dispatchEvent(new Event('change'));
                }
            });

            dropzone.addEventListener('click', (e) => {
                if (dzChangeBtn && dzChangeBtn.contains(e.target)) return;
                if (fileInput) fileInput.click();
            });

            dropzone.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (fileInput) fileInput.click();
                }
            });
        }

        if (dzChangeBtn) {
            dzChangeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (fileInput) fileInput.click();
            });
        }

        const presetType  = modal.dataset.presetType || '';
        const presetId    = parseInt(modal.dataset.presetId || '0', 10);
        const presetTitle = modal.dataset.presetTitle || '';
        const presetLabel = modal.dataset.presetLabel || '';

        function applyPresetIfAvailable() {
            if (presetId > 0 && presetType) {
                if (jadwalType) {
                    jadwalType.value = presetType;
                    jadwalType.disabled = true;
                }
                if (agendaTrigger) {
                    agendaTrigger.disabled = true;
                    agendaTrigger.classList.add('cursor-not-allowed', 'opacity-70', 'bg-base-200');
                }
                selectAgendaItem(String(presetId), presetTitle, presetLabel);
                return true;
            }
            return false;
        }

        function resetForm() {
            activeUploadId        = null;
            isCancelling          = false;
            isUploading           = false;
            lastFile              = null;
            uploadStartedAt       = 0;
            uploadInitialOffset   = 0;
            currentAcceptedOffset = 0;

            const progressBox = document.getElementById('upload_progress_box');
            const warningBanner = document.getElementById('upload_warning_banner');
            const errorBox = document.getElementById('um_error_box');
            const previewContainer = document.getElementById('audio_preview_container');
            const infoNote = document.getElementById('um_info_note');
            const retryBtnEl = document.getElementById('um_retry_btn');

            if (progressBox) progressBox.classList.add('hidden');
            if (warningBanner) warningBanner.classList.add('hidden');
            if (errorBox) errorBox.classList.add('hidden');
            if (previewContainer) previewContainer.classList.add('hidden');
            if (infoNote) infoNote.classList.add('hidden');
            if (retryBtnEl) retryBtnEl.classList.add('hidden');
            if (fileInput) fileInput.value = '';

            if (presetId > 0 && presetType) {
                applyPresetIfAvailable();
            } else {
                if (judulInput) judulInput.value = '';
                if (jadwalId) jadwalId.value = '';
                if (agendaSearchInput) agendaSearchInput.value = '';
                if (agendaSelectedLabel) agendaSelectedLabel.textContent = '— Tanpa Relasi Agenda —';
                if (typeof renderAgendaOptions === 'function') renderAgendaOptions('');
            }

            showDropzoneIdle();

            if (submitBtn) submitBtn.disabled = false;
            const spinner = document.getElementById('um_spinner');
            const btnIcon = document.getElementById('um_btn_icon');
            const btnLabel = document.getElementById('um_btn_label');
            if (spinner) spinner.classList.add('hidden');
            if (btnIcon) btnIcon.classList.remove('hidden');
            if (btnLabel) btnLabel.textContent = 'Unggah Rekaman';

            setProgress(0, 'Mengunggah rekaman ke server...');
            const transferInfo = document.getElementById('upload_transfer_info');
            const speedInfo = document.getElementById('upload_speed_info');
            const etaInfo = document.getElementById('upload_eta_info');
            if (transferInfo) transferInfo.textContent = '0 MB / 0 MB';
            if (speedInfo) speedInfo.textContent = '— MB/s';
            if (etaInfo) etaInfo.textContent = '';

            const player = document.getElementById('audio_preview_player');
            if (player && player.src) {
                URL.revokeObjectURL(player.src);
                player.src = '';
            }
        }

        let currentAgendaItems = [];

        function selectAgendaItem(id, title, label) {
            if (jadwalId) jadwalId.value = id || '';
            if (agendaSelectedLabel) {
                agendaSelectedLabel.textContent = label || '— Tanpa Relasi Agenda —';
            }
            if (judulInput) {
                judulInput.value = title || '';
            }

            if (document.activeElement && typeof document.activeElement.blur === 'function') {
                document.activeElement.blur();
            }
        }

        function renderAgendaOptions(searchTerm = '') {
            if (!agendaOptionsList) return;
            agendaOptionsList.innerHTML = '';

            const term = (searchTerm || '').trim().toLowerCase();
            const filtered = currentAgendaItems.filter((item) => {
                if (!term) return true;
                const titleMatch = (item.title || '').toLowerCase().includes(term);
                const labelMatch = (item.label || '').toLowerCase().includes(term);
                const dateMatch  = (item.date || '').toLowerCase().includes(term);
                return titleMatch || labelMatch || dateMatch;
            });

            if (!term || 'tanpa relasi agenda'.includes(term)) {
                const liNone = document.createElement('li');
                const btnNone = document.createElement('button');
                btnNone.type = 'button';
                const isSelected = !jadwalId || !jadwalId.value;
                btnNone.className = 'flex items-center justify-between py-1.5 px-2 rounded hover:bg-base-200 text-xs ' + (isSelected ? 'active font-bold bg-base-200 text-base-content' : 'text-base-content/70');
                btnNone.innerHTML = '<span>— Tanpa Relasi Agenda —</span>';
                btnNone.addEventListener('click', (e) => {
                    e.preventDefault();
                    selectAgendaItem('', '', '— Tanpa Relasi Agenda —');
                });
                liNone.appendChild(btnNone);
                agendaOptionsList.appendChild(liNone);
            }

            if (filtered.length === 0 && term) {
                const liEmpty = document.createElement('li');
                liEmpty.className = 'py-3 text-center text-xs text-base-content/40 italic';
                liEmpty.textContent = 'Tidak ada agenda yang cocok';
                agendaOptionsList.appendChild(liEmpty);
                return;
            }

            filtered.forEach((item) => {
                const isSelected = jadwalId && jadwalId.value === item.id;
                const li = document.createElement('li');
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'flex flex-col items-start gap-0.5 py-1.5 px-2 rounded hover:bg-base-200 text-left ' + (isSelected ? 'active bg-primary/10 text-primary font-semibold' : 'text-base-content');

                const titleSpan = document.createElement('span');
                titleSpan.className = 'text-xs font-semibold leading-snug line-clamp-2';
                titleSpan.textContent = item.title;

                const dateSpan = document.createElement('span');
                dateSpan.className = 'text-[10px] font-mono text-base-content/50';
                dateSpan.textContent = item.date || item.label;

                btn.appendChild(titleSpan);
                btn.appendChild(dateSpan);

                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    selectAgendaItem(item.id, item.title, item.label);
                });

                li.appendChild(btn);
                agendaOptionsList.appendChild(li);
            });
        }

        function updateJadwalOptions() {
            if (!jadwalId || !jadwalType) return;
            let generalOpts = [];
            let banmusOpts = [];
            try {
                generalOpts = JSON.parse(jadwalId.dataset.generalOptions || '[]');
                banmusOpts = JSON.parse(jadwalId.dataset.banmusOptions || '[]');
            } catch (e) { /* fallback empty */ }

            currentAgendaItems = (jadwalType.value === 'banmus') ? banmusOpts : generalOpts;
            if (agendaSearchInput) agendaSearchInput.value = '';
            selectAgendaItem('', '', '— Tanpa Relasi Agenda —');
            renderAgendaOptions('');
        }

        if (jadwalType) {
            jadwalType.addEventListener('change', () => {
                updateJadwalOptions();
            });
        }

        if (agendaSearchInput) {
            agendaSearchInput.addEventListener('input', () => {
                renderAgendaOptions(agendaSearchInput.value);
            });
            agendaSearchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    e.stopPropagation();
                    if (document.activeElement && typeof document.activeElement.blur === 'function') {
                        document.activeElement.blur();
                    }
                }
            });
        }

        if (!applyPresetIfAvailable()) {
            updateJadwalOptions();
        } else {
            // Auto open modal on page load if preset was requested
            setTimeout(() => {
                if (modal && !modal.open) {
                    modal.showModal();
                    rerenderIcons();
                }
            }, 100);
        }

        if (openBtn) {
            openBtn.addEventListener('click', () => {
                resetForm();
                if (!applyPresetIfAvailable()) {
                    updateJadwalOptions();
                }
                modal.showModal();
                rerenderIcons();
            });
        }


        function confirmCancelUpload(onConfirmed) {
            if (isUploading) {
                openConfirmDialog(() => {
                    isCancelling = true;
                    if (activeUploadId) doCancel(activeUploadId);
                    onConfirmed();
                });
            } else {
                onConfirmed();
            }
        }

        // Penjagaan tombol Escape native HTML5 <dialog>
        modal.addEventListener('cancel', (e) => {
            if (isUploading) {
                e.preventDefault();
                confirmCancelUpload(() => modal.close());
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                confirmCancelUpload(() => modal.close());
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                confirmCancelUpload(() => modal.close());
            });
        }

        if (backdropBtn) {
            backdropBtn.addEventListener('click', () => {
                if (isUploading) return;
                modal.close();
            });
        }

        if (retryBtn) {
            retryBtn.addEventListener('click', () => {
                if (!lastFile) return;
                startUpload(lastFile);
            });
        }

        modal.addEventListener('close', () => {
            if (activeUploadId && !isCancelling) {
                doCancel(activeUploadId);
            }
            resetForm();
        });

        if (fileInput) {
            fileInput.addEventListener('change', () => {
                const container = document.getElementById('audio_preview_container');
                const player    = document.getElementById('audio_preview_player');
                const info      = document.getElementById('audio_preview_info');
                const infoNote  = document.getElementById('um_info_note');

                const errBox = document.getElementById('um_error_box');
                const retryB = document.getElementById('um_retry_btn');
                if (errBox) errBox.classList.add('hidden');
                if (retryB) retryB.classList.add('hidden');

                if (fileInput.files && fileInput.files[0]) {
                    const f = fileInput.files[0];
                    showDropzoneSelected(f);

                    if (info) info.textContent = f.name + ' · ' + (f.size / 1048576).toFixed(1) + ' MB';
                    if (player) {
                        if (player.src) URL.revokeObjectURL(player.src);
                        player.src = URL.createObjectURL(f);
                    }
                    if (container) container.classList.remove('hidden');
                    if (infoNote) infoNote.classList.remove('hidden');
                    rerenderIcons();
                } else {
                    showDropzoneIdle();
                    if (container) container.classList.add('hidden');
                    if (infoNote) infoNote.classList.add('hidden');
                    if (player && player.src) {
                        URL.revokeObjectURL(player.src);
                        player.src = '';
                    }
                }
            });
        }

        if (jadwalType) {
            jadwalType.addEventListener('change', () => {
                updateJadwalOptions();
            });
        }





        function validateFile(file) {

            if (!file) return 'Pilih berkas rekaman audio terlebih dahulu.';
            if (file.size > MAX_SIZE) {
                return 'Berkas terlalu besar (' + (file.size / 1048576).toFixed(1) + ' MB). Maksimum 300 MB.';
            }
            const extOk  = ALLOWED_EXT.test(file.name);
            const typeOk = file.type === '' || ALLOWED_TYPES.includes(file.type);
            if (!extOk && !typeOk) {
                return 'Format berkas tidak didukung. Gunakan MP3, M4A, WAV, OGG, AAC, FLAC, atau MP4.';
            }
            return null;
        }

        function categorizeError(err) {
            if (!err) return 'Terjadi kesalahan tidak diketahui. Coba lagi.';
            const status = err.status || 0;
            const msg    = err.message || '';

            if (status === 0 || msg.includes('Koneksi')) {
                return 'Koneksi terputus. Periksa jaringan Anda, lalu coba lagi.';
            }
            if (status === 413) {
                return 'Berkas terlalu besar untuk diterima server. Kompres rekaman dan coba lagi.';
            }
            if (status === 422 || status === 400) {
                return msg || 'Format berkas tidak diterima server. Pastikan format audio valid.';
            }
            if (status >= 500) {
                return 'Server sedang mengalami gangguan (' + status + '). Coba lagi dalam beberapa menit.';
            }
            if (msg) return msg;
            return 'Gagal mengunggah rekaman (HTTP ' + status + '). Coba lagi.';
        }

        function setProgress(pct, msg) {
            const bar = document.getElementById('upload_progress_bar');
            const percent = document.getElementById('upload_progress_percent');
            const text = document.getElementById('upload_status_text');
            if (bar) bar.value = pct;
            if (percent) percent.textContent = Math.round(pct) + '%';
            if (text && msg !== undefined) text.textContent = msg;
        }

        function showError(msg, allowRetry) {
            isUploading = false;
            if (submitBtn) submitBtn.disabled = false;
            const spinner = document.getElementById('um_spinner');
            const btnIcon = document.getElementById('um_btn_icon');
            const btnLabel = document.getElementById('um_btn_label');
            const warningBanner = document.getElementById('upload_warning_banner');
            const retryEl = document.getElementById('um_retry_btn');
            const errorText = document.getElementById('um_error_text');
            const errorBox = document.getElementById('um_error_box');

            if (spinner) spinner.classList.add('hidden');
            if (btnIcon) btnIcon.classList.remove('hidden');
            if (btnLabel) btnLabel.textContent = 'Kirim Rekaman';
            if (warningBanner) warningBanner.classList.add('hidden');

            if (retryEl) {
                if (allowRetry && lastFile) retryEl.classList.remove('hidden');
                else retryEl.classList.add('hidden');
            }

            if (errorText) errorText.textContent = msg;
            if (errorBox) errorBox.classList.remove('hidden');
        }

        function updateProgress(fileSize, acceptedOffset, chunkLoaded = 0) {
            const uploaded    = Math.min(fileSize, acceptedOffset + chunkLoaded);
            const pct         = fileSize > 0 ? (uploaded / fileSize) * 100 : 0;
            const elapsedSec  = (performance.now() - uploadStartedAt) / 1000;
            const transferred = Math.max(0, uploaded - uploadInitialOffset);

            if (elapsedSec >= 0.5 && transferred > 0) {
                const speedMBs = transferred / elapsedSec / 1048576;
                const speedInfo = document.getElementById('upload_speed_info');
                if (speedInfo) speedInfo.textContent = speedMBs.toFixed(1) + ' MB/s';

                const remaining = fileSize - uploaded;
                const etaInfo = document.getElementById('upload_eta_info');
                if (remaining > 0 && speedMBs > 0 && etaInfo) {
                    const etaSec = remaining / (speedMBs * 1048576);
                    const etaStr = etaSec >= 60
                        ? Math.ceil(etaSec / 60) + ' mnt tersisa'
                        : Math.ceil(etaSec) + ' dtk tersisa';
                    etaInfo.textContent = '~' + etaStr;
                } else if (etaInfo) {
                    etaInfo.textContent = '';
                }
            }

            const transferInfo = document.getElementById('upload_transfer_info');
            if (transferInfo) {
                transferInfo.textContent = (uploaded / 1048576).toFixed(1) + ' MB / ' + (fileSize / 1048576).toFixed(1) + ' MB';
            }

            const statusMsg = pct >= 100
                ? 'Berkas diterima! Mendaftarkan ke antrean AI...'
                : 'Mengunggah rekaman ke server...';
            setProgress(pct, statusMsg);
        }

        function bytesToHex(buffer) {
            return Array.from(new Uint8Array(buffer))
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');
        }

        function sha256(value) {
            if (!window.crypto || !window.crypto.subtle) {
                return Promise.reject(new Error('Browser tidak mendukung checksum upload yang aman.'));
            }
            const p = value instanceof ArrayBuffer ? Promise.resolve(value) : value.arrayBuffer();
            return p.then(buf => window.crypto.subtle.digest('SHA-256', buf)).then(bytesToHex);
        }

        function fileFingerprint(file) {
            const sampleSize = 65536;
            const first      = file.slice(0, Math.min(sampleSize, file.size)).arrayBuffer();
            const lastStart  = Math.max(0, file.size - sampleSize);
            const last       = file.slice(lastStart, file.size).arrayBuffer();
            const meta       = new TextEncoder().encode(
                file.name + '\n' + file.type + '\n' + file.size + '\n' + file.lastModified + '\n'
            );

            return Promise.all([first, last]).then(([f, l]) => {
                const combined = new Uint8Array(meta.byteLength + f.byteLength + l.byteLength);
                combined.set(meta, 0);
                combined.set(new Uint8Array(f), meta.byteLength);
                combined.set(new Uint8Array(l), meta.byteLength + f.byteLength);
                return sha256(combined.buffer);
            });
        }

        function postJson(url, formData, onProgress) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', url, true);
                xhr.setRequestHeader('Accept', 'application/json');
                if (onProgress) xhr.upload.addEventListener('progress', onProgress);

                xhr.addEventListener('load', () => {
                    let payload = null;
                    try { payload = JSON.parse(xhr.responseText); } catch (e) { /* noop */ }
                    if (xhr.status >= 200 && xhr.status < 300 && payload && payload.status === 'success') {
                        resolve(payload);
                    } else {
                        reject({ status: xhr.status, payload: payload, message: (payload && payload.message) || '' });
                    }
                });
                xhr.addEventListener('error', () => {
                    reject({ status: 0, payload: null, message: 'Koneksi terputus saat mengunggah file.' });
                });
                xhr.send(formData);
            });
        }

        function sleep(ms) {
            return new Promise(res => setTimeout(res, ms));
        }

        function postWithRetry(url, createFormData, onProgress) {
            let lastError = null;
            let attempt   = 0;

            function tryOnce() {
                if (isCancelling) return Promise.reject({ status: 0, message: 'Upload dibatalkan.' });
                const delay = retryDelays[attempt] || 0;
                const p = delay > 0
                    ? sleep(delay).then(() => postJson(url, createFormData(), onProgress))
                    : postJson(url, createFormData(), onProgress);

                return p.catch(err => {
                    lastError = err;
                    attempt++;
                    if (attempt >= retryDelays.length) throw lastError;
                    if (err.status > 0 && err.status < 500 && err.status !== 409) throw err;
                    return tryOnce();
                });
            }

            return tryOnce();
        }

        function beginUpload(file, clientKey) {
            return postWithRetry(START_URL, () => {
                const fd = new FormData();
                fd.append('upload_token', UPLOAD_TOKEN);
                fd.append(CSRF_NAME, CSRF_VALUE);
                fd.append('client_key', clientKey);
                fd.append('file_name', file.name);
                fd.append('file_size', String(file.size));
                fd.append('file_type', file.type);
                return fd;
            });
        }

        function uploadInChunks(file) {
            setProgress(0, 'Memeriksa berkas dan mencari sesi upload sebelumnya...');

            return fileFingerprint(file).then(clientKey => {
                return beginUpload(file, clientKey).then(state => {
                    activeUploadId = state.upload_id;
                    let offset     = Number(state.offset) || 0;
                    const chunkSize  = Math.min(Number(state.chunk_size) || CHUNK_SIZE, CHUNK_SIZE);

                    uploadInitialOffset   = offset;
                    currentAcceptedOffset = offset;
                    uploadStartedAt       = performance.now();
                    updateProgress(file.size, offset);

                    function nextChunk() {
                        if (isCancelling) return Promise.reject({ status: 0, message: 'Upload dibatalkan.' });
                        if (offset >= file.size) return Promise.resolve(state);

                        const chunk       = file.slice(offset, Math.min(offset + chunkSize, file.size));
                        const chunkOffset = offset;

                        return sha256(chunk).then(checksum => {
                            return postWithRetry(CHUNK_URL, () => {
                                const fd = new FormData();
                                fd.append('upload_token', UPLOAD_TOKEN);
                                fd.append(CSRF_NAME, CSRF_VALUE);
                                fd.append('upload_id', state.upload_id);
                                fd.append('offset', String(chunkOffset));
                                fd.append('checksum', checksum);
                                fd.append('chunk', chunk, 'chunk.bin');
                                return fd;
                            }, ev => {
                                updateProgress(file.size, currentAcceptedOffset,
                                    ev.lengthComputable ? Math.min(ev.loaded, chunk.size) : 0);
                            }).catch(err => {
                                if (err.status !== 409) throw err;
                                return beginUpload(file, clientKey).then(s => { state = s; return s; });
                            });
                        }).then(newState => {
                            state                 = newState;
                            offset                = Number(newState.offset) || 0;
                            currentAcceptedOffset = offset;
                            updateProgress(file.size, offset);
                            return nextChunk();
                        });
                    }

                    return nextChunk().then(() => {
                        if (!state.completed) {
                            return beginUpload(file, clientKey).then(s => { state = s; return state; });
                        }
                        return state;
                    }).then(finalState => {
                        if (!finalState.completed) {
                            throw { status: 409, message: 'Server belum menandai upload sebagai selesai.' };
                        }
                        return finalState.upload_id;
                    });
                });
            });
        }

        function doCancel(uploadId) {
            const fd = new FormData();
            fd.append('upload_token', UPLOAD_TOKEN);
            fd.append(CSRF_NAME, CSRF_VALUE);
            fd.append('upload_id', uploadId);
            postJson(CANCEL_URL, fd).catch(() => { /* best effort */ });
        }

        function commitUpload(uploadId) {
            setProgress(100, 'Berhasil! Mendaftarkan job ke antrean AI...');
            const fd = new FormData();
            fd.append('upload_id', uploadId);

            const actualJadwalType = (presetId > 0 && presetType) ? presetType : (jadwalType ? jadwalType.value : 'umum');
            const actualJadwalId   = (presetId > 0) ? String(presetId) : (jadwalId ? jadwalId.value : '');
            fd.append('jadwal_type', actualJadwalType);
            fd.append('jadwal_id', actualJadwalId);

            let finalTitle = judulInput && judulInput.value ? judulInput.value.trim() : (presetTitle || '');
            if (!finalTitle) {
                const todayFormatted = new Intl.DateTimeFormat('id-ID', {
                    day: 'numeric', month: 'long', year: 'numeric'
                }).format(new Date());
                finalTitle = 'Rekaman Rapat — ' + todayFormatted;
            }
            fd.append('judul_rapat', finalTitle);
            fd.append(CSRF_NAME, CSRF_VALUE);
            return postJson(COMMIT_URL, fd);
        }

        function startUpload(file) {
            const validationError = validateFile(file);
            if (validationError) {
                showError(validationError, false);
                return;
            }

            lastFile     = file;
            isCancelling = false;
            isUploading  = true;
            activeUploadId = null;

            const errBox = document.getElementById('um_error_box');
            const retryB = document.getElementById('um_retry_btn');
            const progressBox = document.getElementById('upload_progress_box');
            const warningBanner = document.getElementById('upload_warning_banner');

            if (errBox) errBox.classList.add('hidden');
            if (retryB) retryB.classList.add('hidden');
            if (progressBox) progressBox.classList.remove('hidden');
            if (warningBanner) warningBanner.classList.remove('hidden');

            if (submitBtn) submitBtn.disabled = true;
            const spinner = document.getElementById('um_spinner');
            const btnIcon = document.getElementById('um_btn_icon');
            const btnLabel = document.getElementById('um_btn_label');

            if (spinner) spinner.classList.remove('hidden');
            if (btnIcon) btnIcon.classList.add('hidden');
            if (btnLabel) btnLabel.textContent = 'Mengunggah rekaman...';

            uploadInChunks(file)
                .then(uploadId => commitUpload(uploadId))
                .then(res => {
                    isUploading    = false;
                    activeUploadId = null;
                    if (warningBanner) warningBanner.classList.add('hidden');
                    setProgress(100, 'Selesai! Mengalihkan ke halaman notulensi...');
                    if (res.redirect) {
                        setTimeout(() => {
                            if (window.Turbo) {
                                window.Turbo.visit(res.redirect);
                            } else {
                                window.location.href = res.redirect;
                            }
                        }, 500);
                    }
                })
                .catch(err => {
                    activeUploadId = null;
                    if (!isCancelling) {
                        showError(categorizeError(err), true);
                    } else {
                        isUploading = false;
                        if (warningBanner) warningBanner.classList.add('hidden');
                    }
                });
        }

        if (submitBtn) {
            submitBtn.addEventListener('click', () => {
                if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                    showError('Pilih berkas rekaman audio terlebih dahulu.', false);
                    return;
                }
                startUpload(fileInput.files[0]);
            });
        }
    };

    document.addEventListener('turbo:load', initializeNotulenUploadWorkspace);
    if (document.readyState !== 'loading') {
        initializeNotulenUploadWorkspace();
    } else {
        document.addEventListener('DOMContentLoaded', initializeNotulenUploadWorkspace);
    }
    document.addEventListener('turbo:before-cache', () => {
        const modal = document.getElementById('modal_upload_notulen');
        if (modal instanceof HTMLDialogElement && modal.open) modal.close();
        const confirmDialog = document.getElementById('um_confirm_dialog');
        if (confirmDialog instanceof HTMLDialogElement && confirmDialog.open) confirmDialog.close();
    });
})();


(() => {
    let notulenPollTimer = null;
    let notulenPollAbort = null;
    let isNotulenDirty = false;

    const initializeNotulenShowWorkspace = () => {
        // Reset timers & controllers
        if (notulenPollTimer) {
            clearInterval(notulenPollTimer);
            notulenPollTimer = null;
        }
        if (notulenPollAbort) {
            notulenPollAbort.abort();
            notulenPollAbort = null;
        }
        isNotulenDirty = false;

        const textarea = document.getElementById('ringkasan_eksekutif');
        const form = document.getElementById('form_update_minutes');
        const dirtyBadge = document.getElementById('dirty_indicator');
        const audioPlayer = document.getElementById('audio_player');

        // Dirty State Tracking
        if (textarea) {
            const originalValue = textarea.value;
            textarea.addEventListener('input', () => {
                isNotulenDirty = textarea.value !== originalValue;
                if (dirtyBadge) {
                    dirtyBadge.classList.toggle('hidden', !isNotulenDirty);
                }
            });
        }

        // Reset dirty flag on direct form submit
        if (form) {
            form.addEventListener('submit', () => {
                isNotulenDirty = false;
            });
        }

        // Quick Save (Ctrl+S) via AJAX
        const handleQuickSave = async () => {
            if (!form || !textarea) return;
            const submitBtn = document.getElementById('btn_save_draft');
            const lastSavedTime = document.getElementById('last_saved_time');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

            try {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="loading loading-spinner loading-xs mr-1"></span> Menyimpan...';
                }

                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const data = await response.json();
                if (response.ok && data.status === 'success') {
                    isNotulenDirty = false;
                    if (dirtyBadge) dirtyBadge.classList.add('hidden');
                    if (lastSavedTime) {
                        const now = new Date();
                        lastSavedTime.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WITA';
                    }
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i data-lucide="check" class="h-4 w-4"></i> Tersimpan!';
                        if (window.lucide) window.lucide.createIcons();
                    }
                } else {
                    alert(data.message || 'Gagal menyimpan draf risalah.');
                }
            } catch (err) {
                console.error('Quick save error:', err);
                form.submit(); // Fallback to standard MPA submit
            } finally {
                setTimeout(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                        if (window.lucide) window.lucide.createIcons();
                    }
                }, 1500);
            }
        };

        // Hotkeys Stenografer (Ctrl+S, Ctrl+Space, Alt+J, Alt+K)
        const keyHandler = (e) => {
            const hasOpenModal = document.querySelector('dialog[open]');
            if (hasOpenModal) return;

            // Ctrl + S / Cmd + S -> Quick Save
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.code === 'KeyS')) {
                if (form) {
                    e.preventDefault();
                    handleQuickSave();
                }
            }

            // Ctrl + Space atau Alt + Space -> Play/Pause Audio
            if ((e.ctrlKey || e.altKey) && (e.code === 'Space' || e.key === ' ')) {
                if (audioPlayer) {
                    e.preventDefault();
                    if (audioPlayer.paused) {
                        audioPlayer.play();
                    } else {
                        audioPlayer.pause();
                    }
                }
            }

            // Alt + J / Alt + ArrowLeft -> Seek -5s
            if (e.altKey && (e.code === 'KeyJ' || e.key === 'j' || e.code === 'ArrowLeft' || e.key === 'ArrowLeft')) {
                if (audioPlayer) {
                    e.preventDefault();
                    audioPlayer.currentTime = Math.max(0, audioPlayer.currentTime - 5);
                }
            }

            // Alt + K / Alt + ArrowRight -> Seek +5s
            if (e.altKey && (e.code === 'KeyK' || e.key === 'k' || e.code === 'ArrowRight' || e.key === 'ArrowRight')) {
                if (audioPlayer) {
                    e.preventDefault();
                    audioPlayer.currentTime = Math.min(audioPlayer.duration || Infinity, audioPlayer.currentTime + 5);
                }
            }
        };

        document.removeEventListener('keydown', window.__notulenKeyHandler);
        window.__notulenKeyHandler = keyHandler;
        document.addEventListener('keydown', keyHandler);

        // Polling Progress Lifecycle
        const pollElement = document.querySelector('[data-notulen-poll]');
        if (!pollElement) return;

        const statusUrl = pollElement.dataset.statusUrl;
        const initialStatus = pollElement.dataset.status;
        const activeStatuses = ['queued', 'chunking', 'transcribing', 'summarizing'];

        if (!activeStatuses.includes(initialStatus) || !statusUrl) return;

        const poll = () => {
            if (notulenPollAbort) notulenPollAbort.abort();
            notulenPollAbort = new AbortController();

            fetch(statusUrl, { signal: notulenPollAbort.signal })
                .then(r => r.ok ? r.json() : null)
                .then(json => {
                    if (!json || json.status !== 'success' || !json.data) return;
                    const d = json.data;

                    const pct = document.getElementById('live_progress_percent');
                    const bar = document.getElementById('live_progress_bar');
                    const step = document.getElementById('live_current_step');
                    const chunks = document.getElementById('live_chunk_info');
                    const title = document.getElementById('live_status_title');

                    if (pct) pct.textContent = d.progress_percent + '%';
                    if (bar) bar.value = d.progress_percent;
                    if (step) step.textContent = d.current_step || '-';
                    if (chunks) chunks.textContent = d.completed_chunks + ' / ' + d.total_chunks + ' segmen';
                    if (title && d.current_step) title.textContent = d.current_step;

                    if (d.ai_model_label) {
                        const modelLabelEl = document.getElementById('ai_model_label_text');
                        if (modelLabelEl) modelLabelEl.textContent = d.ai_model_label;
                        const modelMetaEl = document.getElementById('ai_model_meta_text');
                        if (modelMetaEl) modelMetaEl.textContent = d.ai_model_label;
                    }

                    if (d.status === 'completed' || d.status === 'failed' || d.status === 'cancelled') {
                        if (notulenPollTimer) {
                            clearInterval(notulenPollTimer);
                            notulenPollTimer = null;
                        }
                        // Jika notulis sedang mengetik draf, jangan reload paksa yang merusak editan
                        if (isNotulenDirty) return;

                        setTimeout(() => {
                            if (window.Turbo) {
                                window.Turbo.visit(window.location.href, { action: 'replace' });
                            } else {
                                window.location.reload();
                            }
                        }, 1200);
                    }
                })
                .catch(e => {
                    if (e.name !== 'AbortError') {
                        console.warn('Poll error:', e);
                    }
                });
        };

        notulenPollTimer = setInterval(poll, 3500);
    };

    // Navigation and Unload Guards
    window.addEventListener('beforeunload', (e) => {
        if (isNotulenDirty) {
            e.preventDefault();
            e.returnValue = 'Terdapat perubahan draf risalah yang belum disimpan!';
        }
    });

    document.addEventListener('turbo:before-visit', (e) => {
        if (isNotulenDirty) {
            const confirmLeave = window.confirm('Terdapat perubahan draf risalah yang belum disimpan. Yakin ingin berpindah halaman?');
            if (!confirmLeave) {
                e.preventDefault();
            }
        }
    });

    document.addEventListener('turbo:load', initializeNotulenShowWorkspace);
    document.addEventListener('turbo:before-cache', () => {
        if (notulenPollTimer) {
            clearInterval(notulenPollTimer);
            notulenPollTimer = null;
        }
        if (notulenPollAbort) {
            notulenPollAbort.abort();
            notulenPollAbort = null;
        }
        if (window.__notulenKeyHandler) {
            document.removeEventListener('keydown', window.__notulenKeyHandler);
        }
    });
})();
