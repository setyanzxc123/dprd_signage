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
            font-family: "Times New Roman", Times, serif;
            color: #111;
            font-size: 11pt;
            line-height: 1.5;
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
            overflow-wrap: anywhere;
        }
        .naskah .line {
            margin: 0;
        }
        .naskah .gap {
            margin: 0;
            height: 1.5em;
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
        foreach (preg_split('/\n/', (string) $minutes['ringkasan_eksekutif']) as $rawLine) {
            $line   = rtrim(str_replace("\t", '    ', $rawLine));
            $core   = ltrim($line, ' ');
            $indent = min(strlen($line) - strlen($core), 8);
            $core   = preg_replace('/ {2,}/', ' ', $core) ?? $core;
            $core   = implode(' ', array_map(
                static fn (string $token): string => strlen($token) > 30
                    ? implode("\u{00AD}", mb_str_split($token, 25))
                    : $token,
                explode(' ', $core),
            ));
            $line = str_repeat("\u{00A0}", $indent) . $core;

            if ($core === '') {
                echo '<div class="gap"></div>';
                continue;
            }

            if (preg_match('/^(?:I|II|III|IV|V|VI|VII|VIII|IX|X)\.\s+\S/i', $core)) {
                echo '<div class="line"><strong>' . esc($line) . '</strong></div>';
            } else {
                echo '<div class="line">' . esc($line) . '</div>';
            }
        }
    ?></div>
</body>
</html>
