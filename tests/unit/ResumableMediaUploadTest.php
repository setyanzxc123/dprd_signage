<?php

use App\Libraries\Media\ResumableMediaUpload;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ResumableMediaUploadTest extends CIUnitTestCase
{
    private string $testRoot;
    private string $temporaryDirectory;
    private string $cacheDirectory;
    private string $targetDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testRoot = WRITEPATH . 'tests' . DIRECTORY_SEPARATOR
            . 'resumable-media-' . bin2hex(random_bytes(6));
        $this->temporaryDirectory = $this->testRoot . DIRECTORY_SEPARATOR . 'temporary';
        $this->cacheDirectory = $this->testRoot . DIRECTORY_SEPARATOR . 'cache';
        $this->targetDirectory = $this->testRoot . DIRECTORY_SEPARATOR . 'target';
    }

    protected function tearDown(): void
    {
        if (isset($this->testRoot) && is_dir($this->testRoot)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->testRoot, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            $entries = [];
            foreach ($iterator as $entry) {
                $entries[] = [$entry->getPathname(), $entry->isDir()];
            }
            unset($iterator);
            foreach ($entries as [$path, $isDirectory]) {
                $isDirectory ? rmdir($path) : unlink($path);
            }
            rmdir($this->testRoot);
        }

        parent::tearDown();
    }

    public function testCompletedValidMediaIsMovedAndCacheIsConsumed(): void
    {
        $service = $this->service();
        $uploadKey = '11111111-1111-4111-8111-111111111111';
        $sourcePath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'temporary.png';
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        $this->assertIsString($contents);
        file_put_contents($sourcePath, $contents);
        $this->rememberUpload($service, $uploadKey, $sourcePath, strlen($contents), [
            'originalName' => 'layar-utama.png',
        ]);

        $result = $service->consumeCompletedUpload($uploadKey);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}\.png$/', $result['file_name']);
        $this->assertSame('layar-utama.png', $result['original_name']);
        $this->assertSame('image/png', $result['mime']);
        $this->assertFileExists($this->targetDirectory . DIRECTORY_SEPARATOR . $result['file_name']);
        $this->assertFileDoesNotExist($sourcePath);
        $this->assertNull($service->cache()->get($uploadKey));
    }

    public function testIncompleteUploadCannotBeConsumed(): void
    {
        $service = $this->service();
        $uploadKey = '22222222-2222-4222-8222-222222222222';
        $sourcePath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'partial.mp4';
        file_put_contents($sourcePath, 'partial');
        $this->rememberUpload($service, $uploadKey, $sourcePath, 100, [
            'originalName' => 'video.mp4',
        ], 7);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Upload media belum selesai');

        $service->consumeCompletedUpload($uploadKey);
    }

    public function testMismatchedExtensionIsRejectedAndTemporaryFileRemoved(): void
    {
        $service = $this->service();
        $uploadKey = '33333333-3333-4333-8333-333333333333';
        $sourcePath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'temporary.png';
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        file_put_contents($sourcePath, $contents);
        $this->rememberUpload($service, $uploadKey, $sourcePath, strlen($contents), [
            'originalName' => 'bukan-gambar.mp4',
        ]);

        try {
            $service->consumeCompletedUpload($uploadKey);
            $this->fail('Ekstensi yang tidak sesuai seharusnya ditolak.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Ekstensi file tidak sesuai', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($sourcePath);
        $this->assertNull($service->cache()->get($uploadKey));
    }

    public function testCompletedUploadCannotBeConsumedByAnotherAdminSession(): void
    {
        $service = $this->service();
        $uploadKey = '55555555-5555-4555-8555-555555555555';
        $sourcePath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'owned.png';
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        file_put_contents($sourcePath, $contents);
        $this->rememberUpload($service, $uploadKey, $sourcePath, strlen($contents), [
            'originalName' => 'milik-admin.png',
            'ownerToken'   => str_repeat('a', 64),
        ]);

        try {
            $service->consumeCompletedUpload($uploadKey, str_repeat('b', 64));
            $this->fail('Sesi admin lain seharusnya tidak dapat memakai upload ini.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('tidak dimiliki oleh sesi admin ini', $exception->getMessage());
        }

        $this->assertFileExists($sourcePath);
        $this->assertIsArray($service->cache()->get($uploadKey));
    }

    public function testTusServerRejectsCreationLargerThanTwoHundredMegabytes(): void
    {
        $originalServer = $_SERVER;
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_METHOD'       => 'POST',
            'REQUEST_URI'          => '/admin/pengaturan/media/tus',
            'HTTP_HOST'            => 'localhost:8081',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => '8081',
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'HTTP_TUS_RESUMABLE'   => '1.0.0',
            'HTTP_UPLOAD_LENGTH'   => (string) (ResumableMediaUpload::MAX_BYTES + 1),
            'HTTP_UPLOAD_METADATA' => 'name ' . base64_encode('oversize.mp4'),
        ]);

        try {
            $response = $this->service()->serve();
            $this->assertSame(413, $response->getStatusCode());
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testTusServerCreatesAndDiscoversResumableUploadAtTwoHundredMegabytes(): void
    {
        $originalServer = $_SERVER;
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_METHOD'       => 'POST',
            'REQUEST_URI'          => '/admin/pengaturan/media/tus',
            'HTTP_HOST'            => 'localhost:8081',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => '8081',
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'HTTP_TUS_RESUMABLE'   => '1.0.0',
            'HTTP_UPLOAD_LENGTH'   => (string) ResumableMediaUpload::MAX_BYTES,
            'HTTP_UPLOAD_METADATA' => implode(',', [
                'name ' . base64_encode('temporary.mp4'),
                'originalName ' . base64_encode('layar-dprd.mp4'),
            ]),
        ]);

        try {
            $created = $this->service()->serve();
            $this->assertSame(201, $created->getStatusCode());
            $location = (string) $created->headers->get('Location');
            $this->assertStringContainsString('/admin/pengaturan/media/tus/', $location);

            $_SERVER['REQUEST_METHOD'] = 'HEAD';
            $_SERVER['REQUEST_URI'] = (string) parse_url($location, PHP_URL_PATH);
            unset($_SERVER['HTTP_UPLOAD_LENGTH'], $_SERVER['HTTP_UPLOAD_METADATA']);

            $head = $this->service()->serve();
            $this->assertSame(200, $head->getStatusCode());
            $this->assertSame('0', $head->headers->get('Upload-Offset'));
            $this->assertSame((string) ResumableMediaUpload::MAX_BYTES, $head->headers->get('Upload-Length'));
        } finally {
            $_SERVER = $originalServer;
        }
    }

    private function service(): ResumableMediaUpload
    {
        return new ResumableMediaUpload(
            $this->temporaryDirectory,
            $this->cacheDirectory,
            $this->targetDirectory,
        );
    }

    /**
     * @param array<string, string> $metadata
     */
    private function rememberUpload(
        ResumableMediaUpload $service,
        string $uploadKey,
        string $path,
        int $size,
        array $metadata,
        ?int $offset = null,
    ): void {
        $stored = $service->cache()->set($uploadKey, [
            'name'        => basename($path),
            'size'        => $size,
            'offset'      => $offset ?? $size,
            'checksum'    => '',
            'location'    => '/admin/pengaturan/media/tus/' . $uploadKey,
            'file_path'   => $path,
            'metadata'    => $metadata,
            'upload_type' => 'normal',
            'created_at'  => date('D, d M Y H:i:s \G\M\T'),
            'expires_at'  => date('D, d M Y H:i:s \G\M\T', time() + 3600),
        ]);
        $this->assertNotFalse($stored);
        $this->assertIsArray($service->cache()->get($uploadKey));
    }
}
