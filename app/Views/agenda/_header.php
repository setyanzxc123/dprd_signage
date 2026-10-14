<?php
$headerLogoUrl = $logoUrl ?? base_url('assets/images/logo_dprd.png');
$headerLogoVer = $logoVersion
    ?? (is_file(FCPATH . 'assets/images/logo_dprd.png') ? filemtime(FCPATH . 'assets/images/logo_dprd.png') : time());
$headerPortalUrl = $portalUrl ?? base_url('agenda');
$headerMember = $member ?? null;
$headerIsMember = ! empty($isMember) || is_array($headerMember);
$headerIsAdmin = ! $headerIsMember && ! empty($isAdmin);
?>
<div class="relative overflow-hidden">
    <div class="agenda-header-motif" aria-hidden="true"></div>
    <div class="relative z-10 mx-auto flex min-h-16 w-full flex-wrap items-center justify-between gap-3 px-3 py-2.5 sm:min-h-20 sm:px-6 xl:px-8">
        <a class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1" href="<?= esc($headerPortalUrl) ?>" aria-label="Halaman agenda DPRD">
            <img class="h-16 w-16 shrink-0 object-contain" width="64" height="64" src="<?= esc($headerLogoUrl) ?><?= str_contains($headerLogoUrl, '?') ? '&' : '?' ?>v=<?= $headerLogoVer ?>" alt="Logo DPRD Provinsi Sulawesi Tengah" />
            <span class="min-w-0 leading-tight">
                <span class="block min-w-0 truncate text-sm font-black uppercase tracking-[0.06em] text-slate-900 dark:text-white sm:text-[clamp(17px,1.08vw,22px)] sm:tracking-[0.08em]">
                    AGENDA DPRD
                </span>
                <span class="block min-w-0 truncate text-[10px] uppercase tracking-[0.02em] text-slate-500 dark:text-slate-400 sm:text-[clamp(12px,0.82vw,16px)] sm:tracking-[0.06em]">
                    Provinsi Sulawesi Tengah
                </span>
            </span>
        </a>

        <div class="ml-auto flex items-center gap-2 shrink-0">
            <div class="hidden xl:inline-flex liquid-glass">
                <div id="agenda-weather-card" class="inline-flex items-center gap-3.5 text-slate-900 dark:text-white px-4 py-2.5 transition-colors duration-300">
                    <div class="flex items-center gap-2">
                        <div class="size-8 rounded-full bg-slate-900/5 dark:bg-white/10 backdrop-blur-md flex items-center justify-center shrink-0 border border-slate-900/10 dark:border-white/25 shadow-inner">
                            <img v-if="weather.icon_url" :src="weather.icon_url" class="size-5 object-contain filter drop-shadow-xs" alt="Ikon cuaca" />
                            <span v-else class="size-2 rounded-full bg-amber-500"></span>
                        </div>
                        <div class="text-left leading-tight">
                            <span class="block text-sm font-black text-slate-900 dark:text-white leading-tight">{{ weather.suhu }}</span>
                            <span class="block max-w-24 truncate text-[10px] font-semibold text-slate-600 dark:text-white/80">{{ weather.kondisi }}</span>
                        </div>
                    </div>

                    <div class="h-5 w-px bg-slate-900/15 dark:bg-white/20"></div>

                    <div class="text-left leading-tight">
                        <span class="block max-w-52 truncate text-sm font-bold text-slate-900 dark:text-white">{{ headerDay }}, {{ headerDate }}</span>
                    </div>

                    <div class="h-5 w-px bg-slate-900/15 dark:bg-white/20"></div>

                    <div class="flex items-center gap-1.5">
                        <span class="font-mono text-xl font-black tabular-nums tracking-tight text-slate-950 dark:text-white leading-none">{{ headerTime }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider bg-slate-900/10 text-slate-800 dark:bg-white/20 dark:text-white uppercase backdrop-blur-xs">WITA</span>
                    </div>
                </div>
            </div>
            <button class="js-aktifkan-notif max-md:hidden inline-flex items-center gap-x-2 py-2 px-3 rounded-xl border border-blue-500 bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-semibold shadow-xs transition cursor-pointer" type="button" aria-label="Aktifkan notifikasi">
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                <span>Aktifkan Notifikasi</span>
            </button>
            <button class="js-install-pwa max-md:hidden inline-flex items-center gap-x-2 py-2 px-3 rounded-xl border border-blue-600 bg-blue-600 text-white hover:bg-blue-700 text-xs font-semibold shadow-xs transition cursor-pointer" type="button" aria-label="Install Aplikasi">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Install App</span>
            </button>

            <button class="inline-flex justify-center items-center size-10 sm:size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700/80 transition cursor-pointer" type="button" @click="toggleTheme" :aria-label="isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap'">
                <svg v-if="!isDark" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 15.5A8.5 8.5 0 0 1 8.5 4a7 7 0 1 0 11.5 11.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <svg v-else viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v2m0 14v2M4.2 4.2l1.4 1.4m12.8 12.8 1.4 1.4M3 12h2m14 0h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" stroke-linecap="round"/></svg>
            </button>

            <?php if ($headerIsMember): ?>
                <div class="hs-dropdown relative inline-flex [--placement:bottom-right]">
                    <button id="hs-dropdown-member-profile" type="button" class="hs-dropdown-toggle inline-flex items-center justify-center size-10 sm:size-9 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 transition cursor-pointer" aria-haspopup="menu" aria-expanded="false" aria-label="Menu akun anggota dewan">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </button>

                    <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 sm:min-w-64 bg-white dark:bg-slate-900 shadow-xl rounded-2xl p-2 border border-slate-200/80 dark:border-slate-800 z-50 mt-2" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-member-profile">
                        <div class="py-2 px-2.5 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl">
                            <p class="text-xs font-extrabold text-slate-900 dark:text-white leading-snug"><?= esc((string) ($headerMember['name'] ?? 'Anggota DPRD')) ?></p>
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                <?php if (! empty($headerMember['komisi'])): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/20"><?= esc((string) $headerMember['komisi']) ?></span>
                                <?php endif; ?>
                                <?php if (! empty($headerMember['fraksi'])): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-200"><?= esc((string) $headerMember['fraksi']) ?></span>
                                <?php endif; ?>
                                <?php if (empty($headerMember['fraksi']) && empty($headerMember['komisi'])): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-200"><?= esc((string) ($headerMember['jabatan'] ?? 'Dewan')) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="pt-1.5">
                            <form action="<?= base_url('anggota/logout') ?>" method="post"
                                  data-confirm-title="Keluar dari Akun Anggota"
                                  data-confirm-message="Yakin ingin keluar dari sesi akun Anggota DPRD?"
                                  data-confirm-button="Keluar"
                                  data-confirm-variant="danger">
                                <?= csrf_field() ?>
                                <button type="submit" class="flex w-full items-center gap-x-2 py-2 px-2.5 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>
                                    </svg>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php elseif ($headerIsAdmin): ?>
                <a class="inline-flex items-center gap-x-2 min-h-10 sm:min-h-9 py-2 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700 transition" href="<?= base_url('admin/dashboard') ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    <span class="hidden sm:inline">Panel Admin</span>
                </a>
            <?php else: ?>
                <a class="inline-flex items-center gap-x-2 min-h-10 sm:min-h-9 py-2 px-3 rounded-xl bg-slate-900 text-white hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-500 text-xs font-semibold shadow-xs transition" href="<?= base_url('login?akses=anggota') ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-4-4 5-5-5-5m5 5H3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="hidden sm:inline">Masuk Anggota</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div id="cta-banner" class="relative z-10 md:hidden flex items-center gap-2 border-t border-slate-200/80 dark:border-slate-800/80 bg-blue-50/90 dark:bg-blue-950/40 px-3 py-2">
        <button class="js-aktifkan-notif flex min-w-0 items-center gap-x-2.5 rounded-xl bg-blue-600 py-2.5 px-3.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-700 transition cursor-pointer" type="button">
            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            <span class="truncate">Aktifkan Notifikasi</span>
        </button>
        <button class="js-install-pwa inline-flex shrink-0 items-center gap-x-2 rounded-xl border border-blue-200 bg-white py-2.5 px-3 text-xs font-semibold text-blue-700 shadow-xs transition hover:bg-blue-100 dark:border-blue-800 dark:bg-slate-900 dark:text-blue-300 dark:hover:bg-slate-800 cursor-pointer" type="button" aria-label="Install aplikasi">
            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Install</span>
        </button>
        <button class="js-tutup-cta ml-auto inline-flex size-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-300 cursor-pointer" type="button" aria-label="Tutup saran notifikasi dan install">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>

    <div class="border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/70 dark:bg-slate-900/50 xl:hidden px-3 py-1.5 text-center">
        <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-200 tabular-nums">{{ weatherLabel }} · {{ headerShortDate }} · {{ headerTime }} WITA</span>
    </div>
</div>
