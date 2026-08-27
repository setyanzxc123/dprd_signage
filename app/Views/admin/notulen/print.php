<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?></title>
    <style>
        * {
            box-sizing: border-box;
            font-family: "Times New Roman", Times, serif;
        }
        body {
            margin: 0;
            padding: 20mm;
            color: #111;
            background: #fff;
            font-size: 12pt;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            position: relative;
            padding-bottom: 8px;
            border-bottom: 3px double #000;
            margin-bottom: 20px;
        }
        .header img {
            position: absolute;
            left: 0;
            top: 0;
            width: 75px;
            height: auto;
        }
        .header h2 {
            margin: 0;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h1 {
            margin: 2px 0;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 10pt;
            color: #333;
        }
        .doc-title {
            text-align: center;
            margin: 20px 0 25px;
        }
        .doc-title h3 {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .doc-title p {
            margin: 4px 0 0;
            font-size: 11pt;
        }
        .section-title {
            font-weight: bold;
            font-size: 11pt;
            text-transform: uppercase;
            margin-top: 18px;
            margin-bottom: 6px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 2px;
        }
        .content-box {
            margin-bottom: 15px;
            text-align: justify;
            font-size: 11pt;
        }
        table.custom-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11pt;
        }
        table.custom-table th, table.custom-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.custom-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        ol, ul {
            margin: 6px 0 12px 20px;
            padding: 0;
            font-size: 11pt;
        }
        li {
            margin-bottom: 4px;
            text-align: justify;
        }
        .signature-grid {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 45%;
            text-align: center;
            font-size: 11pt;
        }
        .signature-space {
            height: 70px;
        }
        .no-print-bar {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 20px;
            margin: -20mm -20mm 20px -20mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: sans-serif;
            font-size: 13px;
        }
        .btn-print {
            background: #0284c7;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                padding: 10mm;
            }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <span>Dokumen Resmi Risalah Rapat DPRD Sulawesi Tengah</span>
    <button class="btn-print" onclick="window.print()">Cetak Dokumen (PDF)</button>
</div>

<!-- Kop Surat Resmi -->
<div class="header">
    <img src="<?= base_url('assets/images/logo_dprd.jpg') ?>" alt="Logo DPRD">
    <h2>Dewan Perwakilan Rakyat Daerah</h2>
    <h1>Provinsi Sulawesi Tengah</h1>
    <p>Jl. Sam Ratulangi No. 78, Palu, Sulawesi Tengah · Telp: (0451) 421211 · Laman: dprd.sultengprov.go.id</p>
</div>

<!-- Judul Dokumen -->
<div class="doc-title">
    <h3>Risalah Rapat</h3>
    <p><?= esc($minutes['judul_rapat']) ?></p>
</div>

<!-- Metadata Pelaksanaan -->
<div class="section-title">I. Keterangan Pelaksanaan</div>
<div class="content-box">
    <table style="width: 100%; border: none; font-size: 11pt;">
        <tr>
            <td style="width: 25%; padding: 2px 0;">Hari / Tanggal</td>
            <td style="width: 2%; padding: 2px 0;">:</td>
            <td style="padding: 2px 0;"><?= esc($minutes['tanggal_rapat'] ?? '-') ?></td>
        </tr>
        <tr>
            <td style="padding: 2px 0;">Agenda / Acara</td>
            <td style="padding: 2px 0;">:</td>
            <td style="padding: 2px 0;"><?= esc($minutes['judul_rapat']) ?></td>
        </tr>
        <tr>
            <td style="padding: 2px 0;">Status Verifikasi</td>
            <td style="padding: 2px 0;">:</td>
            <td style="padding: 2px 0; font-weight: bold; text-transform: uppercase;"><?= esc($minutes['status_verifikasi']) ?></td>
        </tr>
    </table>
</div>

<!-- Ringkasan Eksekutif -->
<?php if (! empty($minutes['ringkasan_eksekutif'])): ?>
    <div class="section-title">II. Ringkasan Eksekutif</div>
    <div class="content-box">
        <?= nl2br(esc($minutes['ringkasan_eksekutif'])) ?>
    </div>
<?php endif; ?>

<!-- Pokok-Pokok Pembahasan -->
<?php if (! empty($agendaItems)): ?>
    <div class="section-title">III. Pokok-Pokok Pembahasan Rapat</div>
    <table class="custom-table">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 30%;">Topik / Pembicara</th>
                <th>Uraian Pandangan & Pembahasan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($agendaItems as $i => $item): ?>
                <tr>
                    <td style="text-align: center;"><?= $i + 1 ?></td>
                    <td>
                        <strong><?= esc($item['topik'] ?? "Pokok Bahasan " . ($i + 1)) ?></strong>
                        <?php if (! empty($item['pembicara'])): ?>
                            <br><small style="color: #444;">(<?= esc($item['pembicara']) ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: justify;">
                        <?= nl2br(esc($item['uraian'] ?? (is_string($item) ? $item : ''))) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- Kesimpulan & Keputusan -->
<?php if (! empty($kesimpulanItems)): ?>
    <div class="section-title">IV. Kesimpulan dan Keputusan</div>
    <ol>
        <?php foreach ($kesimpulanItems as $kItem): ?>
            <li><?= esc(is_string($kItem) ? $kItem : json_encode($kItem)) ?></li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<!-- Rencana Tindak Lanjut -->
<?php if (! empty($tindakLanjutItems)): ?>
    <div class="section-title">V. Rencana Tindak Lanjut / Rekomendasi</div>
    <ol>
        <?php foreach ($tindakLanjutItems as $tItem): ?>
            <li><?= esc(is_string($tItem) ? $tItem : json_encode($tItem)) ?></li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<!-- Kolom Tanda Tangan -->
<div class="signature-grid">
    <div class="signature-box">
        <p>Mengetahui,<br><strong>Pimpinan Sidang / Rapat</strong></p>
        <div class="signature-space"></div>
        <p>( ..................................................... )</p>
    </div>
    <div class="signature-box">
        <p>Palu, <?= date('d F Y') ?><br><strong>Notulis / Sekretariat Rapat</strong></p>
        <div class="signature-space"></div>
        <p>( ..................................................... )</p>
    </div>
</div>

</body>
</html>
