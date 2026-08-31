<?php

namespace App\Libraries\Notulen;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Penjawab streaming berkas audio rekaman untuk web admin dan API mobile.
 * Range eksplisit dilayani 206; Range terbuka dibatasi jendela 2 MB agar
 * memori tetap terkendali; request tanpa Range memakai DownloadResponse
 * yang mengirim file per 1 MB tanpa memuat seluruh isi ke memori.
 */
final class AudioStreamResponder
{
    private const RANGE_WINDOW = 2_097_152;

    public static function respond(IncomingRequest $request, ResponseInterface $response, ?string $audioPath): ResponseInterface
    {
        if (! $audioPath || ! is_file($audioPath)) {
            return $response->setStatusCode(404)->setBody('Berkas audio tidak ditemukan atau telah dibersihkan.');
        }

        $mime = mime_content_type($audioPath) ?: 'audio/mpeg';
        $size = filesize($audioPath);

        $fp = @fopen($audioPath, 'rb');
        if (! $fp) {
            return $response->setStatusCode(500)->setBody('Gagal membuka berkas audio.');
        }

        $rangeHeader = $request->getHeaderLine('Range');
        if (empty($rangeHeader)) {
            fclose($fp);

            $download = service('response')->download($audioPath, null, true);
            $download->setHeader('Accept-Ranges', 'bytes');
            $download->setHeader('Cache-Control', 'private, max-age=3600');

            return $download;
        }

        if (preg_match('/bytes=(\d+)-(\d+)?/i', $rangeHeader, $matches)) {
            $start = (int) $matches[1];
            $end   = ! empty($matches[2]) ? (int) $matches[2] : ($size - 1);

            if ($start > $end || $start >= $size) {
                fclose($fp);
                return $response
                    ->setStatusCode(416)
                    ->setHeader('Content-Range', "bytes */{$size}");
            }

            $end    = min($end, $start + self::RANGE_WINDOW - 1, $size - 1);
            $length = $end - $start + 1;
            fseek($fp, $start);
            $content = fread($fp, $length);
            fclose($fp);

            return $response
                ->setStatusCode(206)
                ->setHeader('Content-Type', $mime)
                ->setHeader('Content-Range', "bytes {$start}-{$end}/{$size}")
                ->setHeader('Content-Length', (string) $length)
                ->setHeader('Accept-Ranges', 'bytes')
                ->setHeader('Cache-Control', 'private, max-age=3600')
                ->setBody($content);
        }

        $content = fread($fp, $size);
        fclose($fp);

        return $response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string) $size)
            ->setHeader('Accept-Ranges', 'bytes')
            ->setHeader('Cache-Control', 'private, max-age=3600')
            ->setBody($content);
    }
}
