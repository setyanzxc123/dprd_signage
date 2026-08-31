<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= esc($pageTitle) ?></title>
    <style>
        @page {
            margin: 20mm 18mm;
        }
        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #111;
            font-size: 11pt;
            line-height: 1.55;
        }
        .doc-header {
            text-align: center;
            border-bottom: 2px solid #111;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .doc-header .instansi {
            margin: 0;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #333;
        }
        .doc-header .judul-dokumen {
            margin: 6px 0 4px;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-header .judul-rapat {
            margin: 0;
            font-size: 12pt;
            font-weight: bold;
        }
        .doc-header .metadata {
            margin: 4px 0 0;
            font-size: 10pt;
            color: #333;
        }
        .naskah {
            text-align: justify;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div class="doc-header">
        <p class="instansi">Dewan Perwakilan Rakyat Daerah Provinsi Sulawesi Tengah</p>
        <p class="judul-dokumen">Risalah Rapat</p>
        <p class="judul-rapat"><?= esc($judulRapat) ?></p>
        <p class="metadata">Hari/Tanggal: <strong><?= esc($tanggalRapat) ?></strong> &nbsp;|&nbsp; Waktu: <strong><?= esc($waktuMulai) ?></strong></p>
    </div>

    <div class="naskah"><?= esc($minutes['ringkasan_eksekutif']) ?></div>
</body>
</html>
