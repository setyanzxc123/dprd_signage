<?php
$fontVersion = is_file(FCPATH . 'assets/vendor/fonts/fonts.css') ? filemtime(FCPATH . 'assets/vendor/fonts/fonts.css') : time();
$cssVersion = is_file(FCPATH . 'assets/css/agenda.css') ? filemtime(FCPATH . 'assets/css/agenda.css') : time();
$isMember = is_array($member ?? null);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Jadwal Banmus - DPRD Provinsi Sulawesi Tengah</title>
    <meta name="description" content="Jadwal kegiatan berdasarkan SK Badan Musyawarah DPRD Provinsi Sulawesi Tengah." />
    <link rel="icon" type="image/jpeg" href="<?= esc($logoUrl) ?>" />
    <link href="<?= base_url('assets/vendor/fonts/fonts.css?v=' . $fontVersion) ?>" rel="stylesheet" />
    <script {csp-script-nonce}>
        (() => {
            const stored = localStorage.getItem('dprd-admin-theme');
            const theme = stored === 'dark' ? 'dark' : 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link href="<?= base_url('assets/css/agenda.css?v=' . $cssVersion) ?>" rel="stylesheet" />
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased">
    <header class="border-b border-base-300 bg-base-100">
        <div class="navbar mx-auto min-h-20 w-full max-w-[1040px] gap-3 px-2.5 sm:px-4">
            <a class="navbar-start w-0 min-w-0 flex-1 gap-3" href="<?= esc($portalUrl) ?>" aria-label="Kembali ke agenda DPRD">
                <img class="h-12 w-12 shrink-0 rounded-lg border border-base-300 bg-base-100 object-contain"
                    src="<?= esc($logoUrl) ?>" alt="Logo DPRD" />
                <span class="min-w-0">
                    <span class="block truncate text-base font-black sm:text-xl">DPRD Provinsi Sulawesi Tengah</span>
                    <span class="block truncate text-xs font-semibold text-base-content/55 sm:text-sm">
                        <?= $isMember ? 'Portal Agenda Anggota' : 'Portal Agenda Publik' ?>
                    </span>
                </span>
            </a>

            <?php if ($isMember): ?>
                <form class="navbar-end w-auto" action="<?= base_url('anggota/logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline btn-sm" type="submit">Keluar</button>
                </form>
            <?php else: ?>
                <a class="btn btn-primary btn-sm shrink-0" href="<?= base_url('login?akses=anggota') ?>">
                    <span class="hidden sm:inline">Masuk Anggota</span>
                    <span class="sm:hidden">Masuk</span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1040px] px-2.5 py-5 sm:px-4 sm:py-8">
        <a class="btn btn-ghost btn-sm mb-4 -ml-2" href="<?= esc($portalUrl) ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Kembali ke Agenda
        </a>

        <section class="card card-border bg-base-100 shadow-sm">
            <div class="card-body gap-5 p-4 sm:p-6">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-widest text-base-content/45">Hasil SK Banmus</p>
                        <h1 class="card-title mt-1 text-2xl font-black uppercase sm:text-3xl">Jadwal Banmus</h1>
                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-base-content/60">
                            Agenda kegiatan per semester sebagaimana ditetapkan dalam SK Badan Musyawarah.
                        </p>
                    </div>

                    <form class="grid min-w-0 grid-cols-1 gap-2 sm:flex" action="<?= base_url('agenda/jadwal-banmus') ?>" method="get">
                        <fieldset class="fieldset min-w-0 sm:min-w-28">
                            <legend class="fieldset-legend">Tahun</legend>
                            <select class="select select-sm w-full max-w-full" name="tahun" aria-label="Pilih tahun Jadwal Banmus">
                                <?php if ($availableYears === []): ?>
                                    <option value="<?= (int) $selectedYear ?>"><?= (int) $selectedYear ?></option>
                                <?php else: ?>
                                    <?php foreach ($availableYears as $year): ?>
                                        <option value="<?= (int) $year ?>" <?= (int) $selectedYear === (int) $year ? 'selected' : '' ?>>
                                            <?= (int) $year ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </fieldset>
                        <fieldset class="fieldset min-w-0 sm:min-w-36">
                            <legend class="fieldset-legend">Semester</legend>
                            <select class="select select-sm w-full max-w-full" name="semester" aria-label="Pilih semester Jadwal Banmus">
                                <option value="" <?= $selectedSemester === null ? 'selected' : '' ?>>Semua semester</option>
                                <option value="1" <?= $selectedSemester === 1 ? 'selected' : '' ?>>Semester 1</option>
                                <option value="2" <?= $selectedSemester === 2 ? 'selected' : '' ?>>Semester 2</option>
                            </select>
                        </fieldset>
                        <button class="btn btn-neutral btn-sm self-end" type="submit">Terapkan</button>
                    </form>
                </div>

                <?php if ($documents === []): ?>
                    <div class="grid min-h-64 place-items-center rounded-box border border-dashed border-base-300 bg-base-200 p-8 text-center">
                        <div>
                            <svg class="mx-auto text-base-content/30" viewBox="0 0 24 24" width="44" height="44"
                                fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round"/>
                            </svg>
                            <h2 class="mt-4 text-lg font-extrabold">Belum ada Jadwal Banmus</h2>
                            <p class="mt-1 max-w-md text-sm font-semibold leading-6 text-base-content/55">
                                Belum ada SK Banmus yang dapat ditampilkan untuk periode pilihan ini.
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid gap-5">
                        <?php foreach ($documents as $document): ?>
                            <article class="card card-border bg-base-100">
                                <div class="card-body gap-4 p-4 sm:p-5">
                                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                        <div class="min-w-0">
                                            <span class="badge badge-neutral badge-outline badge-sm">
                                                Semester <?= (int) $document['semester'] ?> · <?= (int) $document['tahun'] ?>
                                            </span>
                                            <h2 class="mt-3 text-lg font-black leading-snug sm:text-xl">
                                                Jadwal Banmus Semester <?= (int) $document['semester'] ?> Tahun <?= (int) $document['tahun'] ?>
                                            </h2>
                                            <p class="mt-1 text-sm font-semibold text-base-content/60">
                                                Nomor SK: <?= esc($document['nomor_sk']) ?>
                                            </p>
                                        </div>

                                        <a class="btn btn-outline btn-sm shrink-0"
                                            href="<?= base_url("agenda/jadwal-banmus/{$document['id']}/dokumen") ?>"
                                            target="_blank" rel="noopener">
                                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor"
                                                stroke-width="2" aria-hidden="true">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                                                <path d="M14 2v6h6M8 13h8m-8 4h6" stroke-linecap="round"/>
                                            </svg>
                                            Lihat SK Asli
                                        </a>
                                    </div>

                                    <ul class="list rounded-box border border-base-300 bg-base-100">
                                        <?php foreach ($document['items'] as $item): ?>
                                            <?php
                                            $statusLabel = match ($item['status']) {
                                                'proyeksi'    => 'Proyeksi',
                                                'menunggu'    => 'Terjadwal',
                                                'persiapan'   => 'Segera Dimulai',
                                                'berlangsung' => 'Berlangsung',
                                                'selesai'     => 'Selesai',
                                                'ditunda'     => 'Ditunda',
                                                'dibatalkan'  => 'Dibatalkan',
                                                default       => ucfirst((string) $item['status']),
                                            };
                                            $statusClass = match ($item['status']) {
                                                'proyeksi'    => 'badge-warning badge-soft',
                                                'persiapan'   => 'badge-warning badge-soft',
                                                'berlangsung' => 'badge-success badge-soft',
                                                'selesai'     => 'badge-info badge-soft',
                                                'ditunda'     => 'badge-warning badge-outline',
                                                'dibatalkan'  => 'badge-error badge-soft',
                                                default       => 'badge-ghost',
                                            };
                                            ?>
                                            <li class="list-row gap-3 border-b border-base-200 p-4 last:border-b-0">
                                                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-box bg-base-200 text-sm font-black">
                                                    <?= (int) $item['urutan'] ?>
                                                </div>
                                                <div class="list-col-grow min-w-0">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <p class="text-xs font-extrabold uppercase tracking-wide text-base-content/45">
                                                            Tanggal Pelaksanaan
                                                        </p>
                                                        <span class="badge <?= $statusClass ?> badge-sm"><?= esc($statusLabel) ?></span>
                                                    </div>
                                                    <?php if (! empty($item['tanggal'])): ?>
                                                        <p class="mt-1 text-sm font-bold">
                                                            <?= date('d/m/Y', strtotime($item['tanggal'])) ?>
                                                            <?php if (! empty($item['jam_mulai']) && ! empty($item['jam_selesai'])): ?>
                                                                · <?= substr($item['jam_mulai'], 0, 5) ?>–<?= substr($item['jam_selesai'], 0, 5) ?> WITA
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php else: ?>
                                                        <p class="mt-1 text-sm font-bold"><?= esc($item['periode_label'] ?: 'Belum ditentukan') ?></p>
                                                    <?php endif; ?>
                                                    <h3 class="mt-3 text-sm font-extrabold leading-6 sm:text-base">
                                                        <?= nl2br(esc($item['agenda'])) ?>
                                                    </h3>
                                                    <?php if (! empty($item['catatan'])): ?>
                                                        <p class="mt-2 text-sm leading-6 text-base-content/60">
                                                            <span class="font-bold">Keterangan:</span>
                                                            <?= nl2br(esc($item['catatan'])) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
