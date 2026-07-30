<?php

namespace App\Libraries\Media;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use TusPhp\Cache\Cacheable;
use TusPhp\Cache\FileStore;
use TusPhp\Tus\Server;

final class ResumableMediaUpload
{
    public const MAX_BYTES = 200 * 1024 * 1024;
    public const CHUNK_BYTES = 5 * 1024 * 1024;
    public const API_PATH = '/admin/pengaturan/media/tus';

    /** @var array<string, list<string>> */
    private const MIME_EXTENSIONS = [
        'video/mp4'  => ['mp4'],
        'video/webm' => ['webm'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png'  => ['png'],
        'image/webp' => ['webp'],
    ];

    private string $temporaryDirectory;
    private string $targetDirectory;
    private Server $server;

    public function __construct(
        ?string $temporaryDirectory = null,
        ?string $cacheDirectory = null,
        ?string $targetDirectory = null,
    ) {
        $this->temporaryDirectory = rtrim(
            $temporaryDirectory ?? WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'media-tus',
            '/\\',
        );
        $cacheDirectory = rtrim(
            $cacheDirectory ?? WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'tus-media',
            '/\\',
        );
        $this->targetDirectory = rtrim(
            $targetDirectory ?? FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'media',
            '/\\',
        );

        $this->ensureDirectory($this->temporaryDirectory);
        $this->ensureDirectory($cacheDirectory);
        $this->ensureDirectory($this->targetDirectory);

        $cache = new FileStore($cacheDirectory . DIRECTORY_SEPARATOR, 'server.cache');
        $cache->setTtl(86400);
        $this->server = new Server($cache);
        $this->server
            ->setUploadDir($this->temporaryDirectory)
            ->setApiPath(self::API_PATH)
            ->setMaxUploadSize(self::MAX_BYTES);
    }

    public function serve(): SymfonyResponse
    {
        if ($this->server->getRequest()->method() === 'POST') {
            $this->server->handleExpiration();
        }

        return $this->server->serve();
    }

    public function cache(): Cacheable
    {
        return $this->server->getCache();
    }

    /**
     * @return array{file_name: string, original_name: string, mime: string, size: int}
     */
    public function consumeCompletedUpload(string $uploadKey, ?string $expectedOwnerToken = null): array
    {
        if (preg_match('/^[a-f0-9-]{36}$/i', $uploadKey) !== 1) {
            throw new RuntimeException('Identitas upload media tidak valid.');
        }

        $metadata = $this->cache()->get($uploadKey);
        if (! is_array($metadata)) {
            throw new RuntimeException('Upload media tidak ditemukan atau sudah kedaluwarsa.');
        }

        $expectedSize = (int) ($metadata['size'] ?? 0);
        $offset = (int) ($metadata['offset'] ?? -1);
        if ($expectedSize < 1 || $expectedSize > self::MAX_BYTES || $offset !== $expectedSize) {
            throw new RuntimeException('Upload media belum selesai atau melebihi batas 200 MB.');
        }

        $sourcePath = $this->safeTemporaryPath((string) ($metadata['file_path'] ?? ''));
        if ($sourcePath === null || ! is_file($sourcePath)) {
            throw new RuntimeException('File hasil upload media tidak ditemukan.');
        }

        $actualSize = (int) filesize($sourcePath);
        if ($actualSize !== $expectedSize) {
            $this->discard($uploadKey, $sourcePath);
            throw new RuntimeException('Ukuran file hasil upload tidak sesuai.');
        }

        $mime = $this->detectMime($sourcePath);
        if (! isset(self::MIME_EXTENSIONS[$mime])) {
            $this->discard($uploadKey, $sourcePath);
            throw new RuntimeException('Format file tidak didukung. Gunakan MP4, WebM, JPG, PNG, atau WebP.');
        }

        $uploadMetadata = is_array($metadata['metadata'] ?? null) ? $metadata['metadata'] : [];
        if ($expectedOwnerToken !== null) {
            $ownerToken = (string) ($uploadMetadata['ownerToken'] ?? '');
            if ($ownerToken === '' || ! hash_equals($expectedOwnerToken, $ownerToken)) {
                throw new RuntimeException('Upload media tidak dimiliki oleh sesi admin ini.');
            }
        }
        $originalName = basename((string) ($uploadMetadata['originalName'] ?? $metadata['name'] ?? 'media'));
        $originalExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! in_array($originalExtension, self::MIME_EXTENSIONS[$mime], true)) {
            $this->discard($uploadKey, $sourcePath);
            throw new RuntimeException('Ekstensi file tidak sesuai dengan isi media.');
        }

        $targetExtension = self::MIME_EXTENSIONS[$mime][0];
        $newFileName = bin2hex(random_bytes(20)) . '.' . $targetExtension;
        $targetPath = $this->targetDirectory . DIRECTORY_SEPARATOR . $newFileName;
        if (! @rename($sourcePath, $targetPath)) {
            if (! @copy($sourcePath, $targetPath) || ! @unlink($sourcePath)) {
                @unlink($targetPath);
                throw new RuntimeException('File media selesai diupload tetapi gagal dipindahkan.');
            }
        }

        $this->cache()->delete($uploadKey);

        return [
            'file_name'     => $newFileName,
            'original_name' => $originalName,
            'mime'          => $mime,
            'size'          => $actualSize,
        ];
    }

    private function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory)
            && ! mkdir($directory, 0750, true)
            && ! is_dir($directory)) {
            throw new RuntimeException("Folder upload tidak dapat dibuat: {$directory}");
        }
        if (! is_writable($directory)) {
            throw new RuntimeException("Folder upload tidak dapat ditulis: {$directory}");
        }
    }

    private function safeTemporaryPath(string $path): ?string
    {
        $base = realpath($this->temporaryDirectory);
        $resolved = realpath($path);
        if ($base === false || $resolved === false) {
            return null;
        }

        $normalizedBase = strtolower(str_replace('\\', '/', rtrim($base, '/\\'))) . '/';
        $normalizedPath = strtolower(str_replace('\\', '/', $resolved));

        return str_starts_with($normalizedPath, $normalizedBase) ? $resolved : null;
    }

    private function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return strtolower((string) $finfo->file($path));
    }

    private function discard(string $uploadKey, string $path): void
    {
        if ($this->safeTemporaryPath($path) !== null && is_file($path)) {
            @unlink($path);
        }
        $this->cache()->delete($uploadKey);
    }
}
