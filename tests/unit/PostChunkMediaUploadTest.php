<?php

use App\Libraries\Media\MediaUploadException;
use App\Libraries\Media\PostChunkMediaUpload;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PostChunkMediaUploadTest extends CIUnitTestCase
{
    private string $testRoot;
    private string $temporaryRoot;
    private string $destinationRoot;
    private PostChunkMediaUpload $uploader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'post-chunk-upload-' . bin2hex(random_bytes(8));
        $this->temporaryRoot = $this->testRoot . DIRECTORY_SEPARATOR . 'chunks';
        $this->destinationRoot = $this->testRoot . DIRECTORY_SEPARATOR . 'media';
        $this->uploader = new PostChunkMediaUpload($this->temporaryRoot, $this->destinationRoot);
    }

    protected function tearDown(): void
    {
        $this->removeTestDirectory($this->testRoot);
        parent::tearDown();
    }

    public function testUploadCanResumeAndBeConsumed(): void
    {
        $ownerToken = str_repeat('a', 64);
        $clientKey = str_repeat('b', 64);
        $content = $this->pngContent();

        $started = $this->uploader->start(
            $ownerToken,
            $clientKey,
            'layar.png',
            strlen($content),
            'image/png'
        );
        $firstLength = intdiv(strlen($content), 2);
        $first = substr($content, 0, $firstLength);
        $second = substr($content, $firstLength);

        $firstResult = $this->append($ownerToken, $started['upload_id'], 0, $first);
        $this->assertSame($firstLength, $firstResult['offset']);
        $this->assertFalse($firstResult['completed']);

        // Simulasi respons chunk pertama hilang: pengiriman ulang harus idempoten.
        $retried = $this->append($ownerToken, $started['upload_id'], 0, $first);
        $this->assertSame($firstLength, $retried['offset']);

        $completed = $this->append($ownerToken, $started['upload_id'], $firstLength, $second);
        $this->assertSame(strlen($content), $completed['offset']);
        $this->assertTrue($completed['completed']);

        $fileName = $this->uploader->consume($ownerToken, $started['upload_id']);
        $destination = $this->destinationRoot . DIRECTORY_SEPARATOR . $fileName;

        $this->assertStringEndsWith('.png', $fileName);
        $this->assertFileExists($destination);
        $this->assertSame($content, file_get_contents($destination));
        $this->assertDirectoryDoesNotExist(
            $this->temporaryRoot . DIRECTORY_SEPARATOR . $started['upload_id']
        );
    }

    public function testAppendRejectsUnexpectedOffset(): void
    {
        $ownerToken = str_repeat('c', 64);
        $content = $this->pngContent();
        $started = $this->uploader->start(
            $ownerToken,
            str_repeat('d', 64),
            'layar.png',
            strlen($content),
            'image/png'
        );

        try {
            $this->append($ownerToken, $started['upload_id'], 4, substr($content, 0, 8));
            $this->fail('Offset yang melompati posisi server seharusnya ditolak.');
        } catch (MediaUploadException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
    }

    public function testAppendRejectsAnotherOwner(): void
    {
        $content = $this->pngContent();
        $started = $this->uploader->start(
            str_repeat('e', 64),
            str_repeat('f', 64),
            'layar.png',
            strlen($content),
            'image/png'
        );

        try {
            $this->append(str_repeat('1', 64), $started['upload_id'], 0, $content);
            $this->fail('Pemilik sesi lain seharusnya tidak dapat mengirim chunk.');
        } catch (MediaUploadException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    private function append(string $ownerToken, string $uploadId, int $offset, string $content): array
    {
        $path = $this->testRoot . DIRECTORY_SEPARATOR . 'chunk-' . bin2hex(random_bytes(4));
        if (!is_dir($this->testRoot)) {
            mkdir($this->testRoot, 0775, true);
        }
        file_put_contents($path, $content);

        $file = new class(
            $path,
            'chunk.bin',
            'application/octet-stream',
            strlen($content),
            UPLOAD_ERR_OK
        ) extends UploadedFile {
            public function isValid(): bool
            {
                return $this->getError() === UPLOAD_ERR_OK && is_file($this->getTempName());
            }
        };

        return $this->uploader->append(
            $ownerToken,
            $uploadId,
            $offset,
            hash('sha256', $content),
            $file
        );
    }

    private function pngContent(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
    }

    private function removeTestDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($directory);
    }
}
