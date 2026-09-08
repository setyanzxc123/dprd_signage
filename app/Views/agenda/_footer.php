<?php
$footerLogoUrl = $logoUrl ?? base_url('assets/images/logo_dprd.png');
$footerLogoVer = $logoVersion ?? time();
$footerIsMember = ! empty($isMember);
?>
<footer class="border-t border-neutral-800 bg-black text-neutral-300 pt-8 pb-5">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-10">
            <div class="space-y-3 lg:col-span-7 lg:ps-6">
                <div class="flex items-center gap-3">
                    <img class="h-11 w-11 shrink-0 object-contain sm:h-12 sm:w-12" src="<?= esc($footerLogoUrl) ?><?= str_contains($footerLogoUrl, '?') ? '&' : '?' ?>v=<?= $footerLogoVer ?>" alt="Logo DPRD Provinsi Sulawesi Tengah" />
                    <div class="min-w-0">
                        <span class="block text-sm font-extrabold uppercase tracking-wider text-white sm:text-base">
                            Dewan Perwakilan Rakyat Daerah
                        </span>
                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-neutral-300 sm:text-xs">
                            PROVINSI SULAWESI TENGAH
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 pt-0.5">
                    <span class="text-xs font-semibold uppercase tracking-wider text-neutral-300">Media Sosial:</span>
                    <div class="flex items-center gap-1.5">
                        <a href="https://www.facebook.com/p/DPRD-Provinsi-Sulawesi-Tengah-100064552912240" target="_blank" rel="noopener noreferrer" class="flex size-8 items-center justify-center rounded-full border border-neutral-700 bg-neutral-900 text-neutral-300 transition hover:border-neutral-400 hover:bg-neutral-800 hover:text-white" aria-label="Facebook DPRD Provinsi Sulawesi Tengah">
                            <svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                        </a>
                        <a href="https://www.instagram.com/dprd_sultengprov" target="_blank" rel="noopener noreferrer" class="flex size-8 items-center justify-center rounded-full border border-neutral-700 bg-neutral-900 text-neutral-300 transition hover:border-neutral-400 hover:bg-neutral-800 hover:text-white" aria-label="Instagram DPRD Provinsi Sulawesi Tengah">
                            <svg class="size-3.5 fill-none stroke-current" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                        </a>
                        <a href="https://www.youtube.com/@dprdprovinsisulawesitengah4027" target="_blank" rel="noopener noreferrer" class="flex size-8 items-center justify-center rounded-full border border-neutral-700 bg-neutral-900 text-neutral-300 transition hover:border-neutral-400 hover:bg-neutral-800 hover:text-white" aria-label="YouTube DPRD Provinsi Sulawesi Tengah">
                            <svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="space-y-2.5 lg:col-span-5 lg:ps-16">
                <h2 class="text-sm font-bold uppercase tracking-wider text-white">Kontak</h2>
                <ul class="space-y-1.5 text-xs">
                    <li class="flex items-center gap-2.5">
                        <svg class="size-3.5 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <a href="tel:0451423111" class="transition hover:text-white">(0451) 423111</a>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg class="size-3.5 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <a href="mailto:sekretariatdprdsulteng@gmail.com" class="transition hover:text-white">sekretariatdprdsulteng@gmail.com</a>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg class="size-3.5 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <a href="mailto:dprd.sultengprov1@gmail.com" class="transition hover:text-white">dprd.sultengprov1@gmail.com</a>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <svg class="mt-0.5 size-3.5 shrink-0 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="2" x2="22" y1="22" y2="22"/><line x1="4" x2="20" y1="2" y2="2"/><path d="M4 2v20"/><path d="M20 2v20"/><path d="M9 22v-4a2 2 0 0 1 4 0v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M16 10h.01"/><path d="M8 14h.01"/><path d="M16 14h.01"/></svg>
                        <address class="not-italic leading-relaxed text-neutral-300">
                            <span class="block">Jl. Dr. Samratulangi No. 80,</span>
                            <span class="block">Kel. Besusu Barat, Kec. Palu Timur</span>
                        </address>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-6 border-t border-neutral-800/80 pt-4">
            <div class="flex flex-col items-center justify-between gap-2 text-center text-xs text-neutral-400 sm:flex-row sm:text-left">
                <span>&copy; <?= date('Y') ?> Sekretariat DPRD Provinsi Sulawesi Tengah. All rights reserved.</span>
                <span class="text-neutral-400 font-medium"><?= $footerIsMember ? 'Akses anggota' : 'Akses publik' ?></span>
            </div>
        </div>
    </div>
</footer>
