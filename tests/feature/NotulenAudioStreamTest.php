<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pengujian mekanisme streaming audio: DownloadResponse mengirim isi file
 * per bagian (1 MB) tanpa memuat seluruh berkas ke memori.
 *
 * @internal
 */
final class NotulenAudioStreamTest extends CIUnitTestCase
{
    public function testDownloadResponseStreamsFullFileWithoutBuffering(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'notulen-audio-');
        file_put_contents($path, str_repeat('A', 3 * 1024 * 1024));

        $download = service('response')->download($path, null, true);
        $download->setHeader('Accept-Ranges', 'bytes');

        ob_start();
        $download->sendBody();
        $sent = ob_get_clean();

        $this->assertSame(3 * 1024 * 1024, strlen($sent));
        $this->assertSame('bytes', $download->getHeaderLine('Accept-Ranges'));

        unlink($path);
    }
}
