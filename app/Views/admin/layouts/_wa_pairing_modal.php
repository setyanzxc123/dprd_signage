<div id="modal_wa_pairing" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="modal_wa_pairing_label">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-3.5rem)] flex items-center">
        <div class="w-full flex flex-col bg-white border border-slate-200 shadow-xl rounded-2xl pointer-events-auto dark:bg-slate-900 dark:border-slate-800">
            <div class="flex justify-between items-center py-3.5 px-4 sm:px-6 border-b border-slate-200 dark:border-slate-800">
                <h3 id="modal_wa_pairing_label" class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="smartphone" class="size-5 text-blue-600 dark:text-blue-400"></i>
                    Tautkan Nomor WhatsApp
                </h3>
                <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-slate-100 text-slate-800 hover:bg-slate-200 focus:outline-hidden focus:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-400" data-hs-overlay="#modal_wa_pairing">
                    <span class="sr-only">Tutup</span>
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-4">
                <nav class="grid grid-cols-2 p-1 bg-slate-100 rounded-xl dark:bg-slate-800/80" aria-label="Tabs" role="tablist">
                    <button type="button" class="hs-tab-active:bg-white hs-tab-active:text-slate-800 hs-tab-active:shadow-xs dark:hs-tab-active:bg-slate-900 dark:hs-tab-active:text-slate-200 py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition active" id="tab-btn-qr" aria-selected="true" data-hs-tab="#panel-wa-qr" aria-controls="panel-wa-qr" role="tab">
                        <i data-lucide="qr-code" class="size-3.5"></i> Scan QR Code
                    </button>
                    <button type="button" class="hs-tab-active:bg-white hs-tab-active:text-slate-800 hs-tab-active:shadow-xs dark:hs-tab-active:bg-slate-900 dark:hs-tab-active:text-slate-200 py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition" id="tab-btn-pair" aria-selected="false" data-hs-tab="#panel-wa-pair" aria-controls="panel-wa-pair" role="tab">
                        <i data-lucide="key-round" class="size-3.5"></i> Pairing Code (8 Digit)
                    </button>
                </nav>

                <div id="panel-wa-qr" class="space-y-3" role="tabpanel" aria-labelledby="tab-btn-qr">
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Buka aplikasi WhatsApp di ponsel pengirim, pilih menu <strong>Perangkat Tertaut</strong> &gt; <strong>Tautkan Perangkat</strong>, lalu pindai kode QR berikut:
                    </p>

                    <div class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-200 rounded-xl min-h-56 dark:bg-slate-950/50 dark:border-slate-800">
                        <div id="wa-qr-loading" class="flex flex-col items-center gap-2">
                            <span class="animate-spin inline-block size-6 border-2 border-current border-t-transparent text-blue-600 rounded-full dark:text-blue-400"></span>
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Menyiapkan QR Code...</span>
                        </div>
                        <img id="wa-qr-image" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E" alt="WhatsApp QR Code" class="max-w-48 max-h-48 rounded-lg shadow-xs bg-white p-2 border border-slate-200 dark:border-slate-700" hidden />
                        <div id="wa-qr-error" class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs mt-2 w-full dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-300" hidden></div>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5" id="wa-qr-timer">
                            <i data-lucide="clock" class="size-3.5"></i> Auto-refresh tiap 15 detik
                        </span>
                        <button type="button" class="py-2 px-3 inline-flex items-center gap-x-1.5 text-xs font-medium rounded-lg border border-slate-200 bg-white text-slate-800 hover:bg-slate-50 shadow-xs dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer" id="btn-reload-qr">
                            <i data-lucide="refresh-cw" class="size-3.5"></i> Muat Ulang QR
                        </button>
                    </div>
                </div>

                <div id="panel-wa-pair" class="space-y-3 hidden" role="tabpanel" aria-labelledby="tab-btn-pair">
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Masukkan nomor WhatsApp resmi DPRD (contoh: <code>081234567890</code>) untuk menerima 8 digit kode pairing:
                    </p>

                    <div class="space-y-2">
                        <input type="tel" id="input-pair-phone" class="py-2.5 px-3.5 block w-full border border-slate-200 rounded-xl text-base sm:text-sm font-mono placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:placeholder:text-slate-500"
                            placeholder="Contoh: 081234567890" aria-label="Nomor WhatsApp untuk pairing code" />
                        <button type="button" class="py-2.5 px-4 w-full inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-xl border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none shadow-xs cursor-pointer" id="btn-request-pair-code">
                            <span class="animate-spin inline-block size-4 border-2 border-current border-t-transparent text-white rounded-full" id="spinner-pair-code" hidden></span>
                            <i data-lucide="send" class="size-4" id="icon-pair-send"></i>
                            <span>Dapatkan Pairing Code</span>
                        </button>
                    </div>

                    <div id="box-pair-result" class="p-4 bg-blue-50 border border-blue-200 rounded-xl text-center space-y-2 dark:bg-blue-950/30 dark:border-blue-800" hidden>
                        <div class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400">Kode Pairing Anda</div>
                        <div class="text-2xl font-black font-mono tracking-widest text-blue-800 dark:text-blue-300 select-all" id="text-pairing-code">-</div>
                        <p class="text-xs text-slate-600 dark:text-slate-300">
                            Buka WhatsApp di HP, masuk ke <strong>Perangkat Tertaut</strong>, pilih <strong>Tautkan dengan nomor telepon</strong>, lalu masukkan 8 digit kode di atas.
                        </p>
                    </div>

                    <div id="box-pair-error" class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-lg text-xs dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300" hidden></div>
                </div>
            </div>

            <div class="flex justify-end items-center py-3 px-4 sm:px-6 border-t border-slate-200 dark:border-slate-800">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-slate-200 bg-white text-slate-800 shadow-xs hover:bg-slate-50 focus:outline-hidden dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer" data-hs-overlay="#modal_wa_pairing">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
