<div id="agenda-confirm-modal" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="agenda-confirm-modal-label">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-md sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-slate-200 shadow-xl rounded-2xl pointer-events-auto dark:bg-slate-900 dark:border-slate-800">
            <div class="p-5 sm:p-6">
                <div class="flex items-start gap-4">
                    <div id="agenda-confirm-modal-icon-wrap" class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                        <svg id="agenda-confirm-modal-icon" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 id="agenda-confirm-modal-label" class="text-base font-bold text-slate-900 dark:text-white">
                            Konfirmasi Tindakan
                        </h3>
                        <p id="agenda-confirm-modal-message" class="mt-1 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Apakah Anda yakin ingin melanjutkan tindakan ini?
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end items-center gap-x-2">
                    <button type="button" class="py-2 px-3.5 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition cursor-pointer" data-hs-overlay="#agenda-confirm-modal">
                        Batal
                    </button>
                    <button type="button" id="agenda-confirm-modal-submit" class="py-2 px-3.5 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent bg-rose-600 text-white hover:bg-rose-700 focus:outline-hidden focus:bg-rose-700 shadow-xs transition cursor-pointer">
                        Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script {csp-script-nonce}>
(() => {
    let pendingConfirmForm = null;

    function bindAgendaConfirmations() {
        if (document.documentElement.dataset.agendaConfirmBound === '1') return;
        document.documentElement.dataset.agendaConfirmBound = '1';

        const confirmModal = document.getElementById('agenda-confirm-modal');
        const confirmTitle = document.getElementById('agenda-confirm-modal-label');
        const confirmMessage = document.getElementById('agenda-confirm-modal-message');
        const confirmSubmitBtn = document.getElementById('agenda-confirm-modal-submit');
        const confirmIconWrap = document.getElementById('agenda-confirm-modal-icon-wrap');
        const confirmIcon = document.getElementById('agenda-confirm-modal-icon');

        const ICONS = {
            danger: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            warning: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            primary: '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>'
        };

        if (confirmSubmitBtn) {
            confirmSubmitBtn.addEventListener('click', () => {
                if (pendingConfirmForm) {
                    const formToSubmit = pendingConfirmForm;
                    pendingConfirmForm = null;
                    formToSubmit.dataset.confirmed = '1';
                    if (window.HSOverlay && typeof window.HSOverlay.close === 'function' && confirmModal) {
                        try {
                            window.HSOverlay.close(confirmModal);
                        } catch {
                        }
                    }
                    if (confirmModal) {
                        confirmModal.classList.add('hidden');
                        confirmModal.classList.remove('open', 'opened');
                    }
                    const backdrop = document.querySelector('[data-hs-overlay-backdrop-template]');
                    if (backdrop) backdrop.remove();
                    document.body.classList.remove('hs-overlay-body-open');
                    formToSubmit.submit();
                }
            });
        }

        if (confirmModal) {
            confirmModal.addEventListener('close.hs.overlay', () => {
                pendingConfirmForm = null;
            });
        }

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-confirm-message]');
            if (!form) return;

            if (form.dataset.confirmed === '1') {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            pendingConfirmForm = form;

            const msg = form.dataset.confirmMessage || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            const title = form.dataset.confirmTitle || 'Konfirmasi Tindakan';
            const btnText = form.dataset.confirmButton || 'Lanjutkan';
            const variant = form.dataset.confirmVariant || 'danger';

            if (confirmTitle) confirmTitle.textContent = title;
            if (confirmMessage) confirmMessage.textContent = msg;
            if (confirmSubmitBtn) {
                confirmSubmitBtn.textContent = btnText;
                confirmSubmitBtn.className = 'py-2 px-3.5 inline-flex items-center gap-x-2 text-xs font-semibold rounded-xl border border-transparent shadow-xs transition cursor-pointer focus:outline-hidden';
                if (variant === 'primary') {
                    confirmSubmitBtn.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700', 'focus:bg-blue-700');
                } else if (variant === 'warning') {
                    confirmSubmitBtn.classList.add('bg-amber-600', 'text-white', 'hover:bg-amber-700', 'focus:bg-amber-700');
                } else {
                    confirmSubmitBtn.classList.add('bg-rose-600', 'text-white', 'hover:bg-rose-700', 'focus:bg-rose-700');
                }
            }

            if (confirmIconWrap && confirmIcon) {
                confirmIconWrap.className = 'flex size-11 shrink-0 items-center justify-center rounded-xl';
                if (variant === 'primary') {
                    confirmIconWrap.classList.add('bg-blue-50', 'text-blue-600', 'dark:bg-blue-950/40', 'dark:text-blue-400');
                } else if (variant === 'warning') {
                    confirmIconWrap.classList.add('bg-amber-50', 'text-amber-600', 'dark:bg-amber-950/40', 'dark:text-amber-400');
                } else {
                    confirmIconWrap.classList.add('bg-rose-50', 'text-rose-600', 'dark:bg-rose-950/40', 'dark:text-rose-400');
                }
                confirmIcon.innerHTML = ICONS[variant] || ICONS.danger;
            }

            if (window.HSOverlay && typeof window.HSOverlay.open === 'function' && confirmModal) {
                window.HSOverlay.open(confirmModal);
            } else if (confirmModal) {
                confirmModal.classList.remove('hidden');
                confirmModal.classList.add('open', 'opened');
                document.body.classList.add('hs-overlay-body-open');
            } else if (window.confirm(msg)) {
                form.dataset.confirmed = '1';
                form.submit();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindAgendaConfirmations);
    } else {
        bindAgendaConfirmations();
    }
})();
</script>
