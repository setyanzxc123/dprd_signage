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
            font-family: "Times New Roman", Times, "Times-Roman", serif;
            color: #000;
            font-size: 11pt;
            line-height: 1.5;
        }
        .doc-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }
        .doc-header .instansi {
            margin: 0;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #000;
        }
        .doc-header .judul-dokumen {
            margin: 6px 0 4px;
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #000;
        }
        .doc-header .judul-rapat {
            margin: 0;
            font-size: 11.5pt;
            font-weight: bold;
            color: #000;
        }
        .doc-header .metadata {
            margin: 6px 0 0;
            font-size: 9.5pt;
            color: #000;
        }
        .naskah {
            text-align: justify;
            text-justify: inter-word;
            overflow-wrap: break-word;
            color: #000;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 16px 0 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #000;
            page-break-after: avoid;
            color: #000;
        }
        .para {
            margin: 0 0 10px;
            text-indent: 2em;
            orphans: 3;
            widows: 3;
        }
        .list-item {
            margin: 0 0 4px 1.5em;
            text-indent: -1.5em;
            orphans: 2;
            widows: 2;
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

    <div class="naskah"><?php
        $lines = preg_split('/\r\n|\r|\n/', (string) ($minutes['ringkasan_eksekutif'] ?? ''));
        $currentParagraph = [];

        $flushParagraph = static function () use (&$currentParagraph): void {
            if ($currentParagraph !== []) {
                $text = implode(' ', $currentParagraph);
                $text = preg_replace('/ {2,}/', ' ', trim($text)) ?? $text;
                if ($text !== '') {
                    echo '<p class="para">' . esc($text) . '</p>';
                }
                $currentParagraph = [];
            }
        };

        foreach ($lines as $rawLine) {
            $trimmed = trim($rawLine);
            if ($trimmed === '') {
                $flushParagraph();
                continue;
            }

            if (preg_match('/^(?:I|II|III|IV|V|VI|VII|VIII|IX|X)\.\s+\S/i', $trimmed)) {
                $flushParagraph();
                echo '<h3 class="section-title">' . esc($trimmed) . '</h3>';
                continue;
            }

            if (preg_match('/^(?:\d+\.|\-|\*|•)\s+/i', $trimmed)) {
                $flushParagraph();
                echo '<p class="list-item">' . esc($trimmed) . '</p>';
                continue;
            }

            $currentParagraph[] = $trimmed;
        }
        $flushParagraph();
    ?></div>
</body>
</html>
