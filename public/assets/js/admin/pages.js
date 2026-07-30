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

        window.renderAdminIcons?.();
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
