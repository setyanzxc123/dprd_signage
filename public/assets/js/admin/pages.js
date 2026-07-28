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
        const toggle = document.getElementById('is_publik');
        const label = document.getElementById('publik-label');
        if (toggle && label) {
            toggle.addEventListener('change', function() {
                label.textContent = this.checked ? 'Publik' : 'Internal';
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

        const waktuMulaiInput = document.getElementById('waktu_mulai');
        const waktuSelesaiInput = document.getElementById('waktu_selesai');
        const waktuError = document.getElementById('waktu-rapat-error');

        const syncTimeValidity = function() {
            if (!waktuMulaiInput || !waktuSelesaiInput) return true;

            const start = waktuMulaiInput.value ? new Date(waktuMulaiInput.value) : null;
            const end = waktuSelesaiInput.value ? new Date(waktuSelesaiInput.value) : null;
            if (!start || !end) {
                waktuMulaiInput.classList.remove('input-error');
                waktuSelesaiInput.classList.remove('input-error');
                waktuSelesaiInput.setCustomValidity('');
                if (waktuError) waktuError.classList.add('hidden');
                return true;
            }

            const valid = !!(start && end && end > start && waktuMulaiInput.value.slice(0, 10) === waktuSelesaiInput.value.slice(0, 10));

            waktuMulaiInput.classList.toggle('input-error', !valid && !!waktuMulaiInput.value);
            waktuSelesaiInput.classList.toggle('input-error', !valid && !!waktuSelesaiInput.value);
            if (waktuError) waktuError.classList.toggle('hidden', valid || !waktuMulaiInput.value || !waktuSelesaiInput.value);

            waktuSelesaiInput.setCustomValidity(valid ? '' : 'Waktu selesai harus setelah waktu mulai pada tanggal yang sama.');

            return valid;
        };

        waktuMulaiInput?.addEventListener('change', syncTimeValidity);
        waktuSelesaiInput?.addEventListener('change', syncTimeValidity);

        const targetInputs = Array.from(document.querySelectorAll('.target-option input[type="checkbox"]'));
        const targetOptions = Array.from(document.querySelectorAll('.target-option'));
        const targetSearch = document.getElementById('target-search');
        const targetSelectedCount = document.getElementById('target-selected-count');
        const targetVisibleCount = document.getElementById('target-visible-count');
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
            const valid = count > 0;

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

            if (targetVisibleCount) {
                targetVisibleCount.textContent = shown;
            }
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
        const panel = document.getElementById('settings-upload-progress');
        const bar = document.getElementById('settings-upload-bar');
        const status = document.getElementById('settings-upload-status');
        const percent = document.getElementById('settings-upload-percent');
        const submitButton = document.getElementById('settings-submit-button');
        const submitSpinner = document.getElementById('settings-submit-spinner');
        const submitIcon = document.getElementById('settings-submit-icon');
        const submitLabel = document.getElementById('settings-submit-label');

        if (!panel || !bar || !status || !percent || !submitButton || !window.FormData || !window.XMLHttpRequest) {
            return;
        }

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
            if (submitLabel) submitLabel.textContent = busy ? 'Menyimpan...' : 'Simpan Semua Pengaturan';
        };

        const showError = (message) => {
            const currentValue = Number(bar.value) || 0;
            panel.hidden = false;
            panel.classList.add('is-error');
            setProgress(currentValue, message || 'Gagal menyimpan pengaturan.');
            setBusy(false);
        };

        const refreshCsrf = (csrf) => {
            if (!csrf?.name || !csrf?.hash) return;

            const csrfInput = Array.from(form.elements).find((element) => element.name === csrf.name);
            if (csrfInput) {
                csrfInput.value = csrf.hash;
            }
        };

        form.addEventListener('submit', (event) => {
            if (form.dataset.settingsSubmitting === '1') {
                event.preventDefault();
                return;
            }

            event.preventDefault();

            const mediaInput = document.getElementById('media_file');
            const hasMediaFile = !!(mediaInput && mediaInput.files && mediaInput.files.length > 0);
            const xhr = new XMLHttpRequest();
            const formData = new FormData(form);

            panel.hidden = false;
            panel.classList.remove('is-error');
            bar.classList.remove('progress-error');
            bar.classList.add('progress-primary');
            setBusy(true);
            setProgress(hasMediaFile ? 1 : 100, hasMediaFile ? 'Mengunggah file media...' : 'Menyimpan pengaturan...');

            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.addEventListener('progress', (progressEvent) => {
                if (!hasMediaFile) return;

                if (progressEvent.lengthComputable) {
                    const value = (progressEvent.loaded / progressEvent.total) * 100;
                    if (value >= 100) {
                        setProgress(100, 'Upload selesai, memproses file...');
                    } else {
                        setProgress(value, 'Mengunggah file media...');
                    }
                } else {
                    status.textContent = 'Mengunggah file media...';
                }
            });

            xhr.addEventListener('load', () => {
                let payload = null;

                try {
                    payload = JSON.parse(xhr.responseText);
                } catch (error) {
                    payload = null;
                }

                refreshCsrf(payload?.csrf);

                if (xhr.status >= 200 && xhr.status < 300 && payload?.status === 'success') {
                    setProgress(100, 'Selesai, memuat ulang halaman...');
                    window.location.assign(payload.redirect || form.dataset.redirectUrl || form.action);
                    return;
                }

                bar.classList.remove('progress-primary');
                bar.classList.add('progress-error');
                const fallbackMessage = xhr.status === 413
                    ? 'Ukuran upload melebihi batas server.'
                    : (xhr.status === 403
                        ? 'Sesi keamanan kedaluwarsa. Muat ulang halaman lalu coba lagi.'
                        : 'Gagal menyimpan pengaturan. Periksa file dan coba lagi.');
                showError(payload?.message || fallbackMessage);
            });

            xhr.addEventListener('error', () => {
                bar.classList.remove('progress-primary');
                bar.classList.add('progress-error');
                showError('Koneksi terputus saat mengunggah file.');
            });

            xhr.send(formData);
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
        const sidebarSelectedCount = document.getElementById('sidebar-selected-count');
        const allSourceItems = document.querySelectorAll('.anggota-source');
        const allCheckboxes = document.querySelectorAll('.source-checkbox');

        const selectedMemberCount = function() {
            return document.querySelectorAll('.source-checkbox:checked').length;
        };

        const syncMemberValidity = function() {
            const invalid = !!(aktifToggle?.checked && selectedMemberCount() === 0);
            if (anggotaError) anggotaError.classList.toggle('hidden', !invalid);
            allCheckboxes.forEach(function(cb) {
                cb.classList.toggle('checkbox-error', invalid);
            });
            return !invalid;
        };

        aktifToggle?.addEventListener('change', function() {
            if (aktifLabel) aktifLabel.textContent = this.checked ? 'Aktif' : 'Nonaktif';
            syncMemberValidity();
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
            if (sidebarSelectedCount) sidebarSelectedCount.textContent = count;

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
                syncMemberValidity();
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
                el.innerHTML =
                    '<span class="inline-flex items-center justify-center rounded shrink-0 bg-primary text-primary-content w-7 h-7 text-xs font-bold">' + initial + '</span>' +
                    '<div class="flex-1 min-w-0">' +
                        '<div class="text-xs font-semibold truncate">' + name + '</div>' +
                        '<div class="text-[11px] text-base-content/60 truncate">' + detail + '</div>' +
                    '</div>' +
                    '<button type="button" class="btn btn-sm btn-ghost btn-circle text-error w-6 h-6 min-h-6 leading-none" title="Hapus dari unit">' +
                        '<i data-lucide="x" class="w-3.5 h-3.5"></i>' +
                    '</button>';

                el.querySelector('button').addEventListener('click', function() {
                    window.removeMember?.(parseInt(id, 10));
                });

                targetList.appendChild(el);
            });

            window.renderAdminIcons?.();
            syncMemberValidity();
        };

        allCheckboxes.forEach(function(cb) {
            cb.addEventListener('change', updateTargetPanel);
        });

        form?.addEventListener('submit', function(event) {
            if (!syncMemberValidity()) {
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

        syncMemberValidity();
    };

    document.addEventListener('turbo:load', initUnitForm);
    })();

(() => {
        const initializeBanmusForm = () => {
            const form = document.querySelector('[data-banmus-form]');
            if (!form) return;

            const container = form.querySelector('[data-items-container]');
            const template = form.querySelector('[data-item-template]');

            const refreshItems = () => {
                const rows = [...container.querySelectorAll('[data-banmus-item]')];
                rows.forEach((row, position) => {
                    row.querySelector('[data-item-index]').textContent = String(position + 1);
                    row.querySelectorAll('[data-field]').forEach((field) => {
                        field.name = `items[${position}][${field.dataset.field}]`;
                    });

                    const removeButton = row.querySelector('[data-remove-item]');
                    removeButton.disabled = rows.length === 1;
                    removeButton.setAttribute('aria-label', `Hapus baris ${position + 1}`);
                });
            };

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
