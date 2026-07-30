<?php

namespace App\Libraries\Media;

use CodeIgniter\HTTP\Files\UploadedFile;

class PostChunkMediaUpload
{
    public const MAX_BYTES = 200 * 1024 * 1024;
    public const CHUNK_BYTES = 512 * 1024;
    public const UPLOAD_TTL = 86400;

    private const ALLOWED_MIME_EXTENSIONS = [
        'video/mp4'  => ['mp4'],
        'video/webm' => ['webm'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png'  => ['png'],
        'image/webp' => ['webp'],
    ];

    public function __construct(
        private readonly string $temporaryRoot = WRITEPATH . 'uploads/media-chunks',
        private readonly string $destinationRoot = FCPATH . 'uploads/media'
    ) {
    }

    public function start(
        string $ownerToken,
        string $clientKey,
        string $fileName,
        int $fileSize,
        string $fileType
    ): array {
        $this->assertOwnerToken($ownerToken);
        $this->assertHex($clientKey, 'Identitas file tidak valid.');

        $fileName = basename(trim($fileName));
        $fileType = strtolower(trim($fileType));
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileName === '' || strlen($fileName) > 255) {
            throw new MediaUploadException('Nama file media tidak valid.');
        }

        if ($fileSize <= 0 || $fileSize > self::MAX_BYTES) {
            throw new MediaUploadException('Ukuran file harus lebih dari 0 dan maksimal 200 MB.', 413);
        }

        if (!isset(self::ALLOWED_MIME_EXTENSIONS[$fileType])
            || !in_array($extension, self::ALLOWED_MIME_EXTENSIONS[$fileType], true)
        ) {
            throw new MediaUploadException('Format file tidak didukung. Gunakan MP4, WebM, JPG, PNG, atau WebP.');
        }

        $this->ensureDirectory($this->temporaryRoot);
        $this->cleanupExpiredUploads();

        $uploadId = hash_hmac('sha256', $clientKey, $ownerToken);
        $uploadDir = $this->uploadDirectory($uploadId);
        $this->ensureDirectory($uploadDir);

        return $this->withLock($uploadDir, function () use (
            $uploadDir,
            $uploadId,
            $ownerToken,
            $clientKey,
            $fileName,
            $fileSize,
            $fileType
        ): array {
            $metadata = $this->readMetadata($uploadDir);
            if ($metadata !== null) {
                $matches = hash_equals((string) ($metadata['owner_hash'] ?? ''), hash('sha256', $ownerToken))
                    && hash_equals((string) ($metadata['client_key'] ?? ''), $clientKey)
                    && (string) ($metadata['file_name'] ?? '') === $fileName
                    && (int) ($metadata['file_size'] ?? 0) === $fileSize
                    && (string) ($metadata['file_type'] ?? '') === $fileType;

                if (!$matches) {
                    throw new MediaUploadException('Data upload tidak cocok. Pilih ulang file lalu coba lagi.', 409);
                }

                $metadata = $this->syncOffset($uploadDir, $metadata);
                $this->writeMetadata($uploadDir, $metadata);

                return $this->responseMetadata($uploadId, $metadata);
            }

            $metadata = [
                'owner_hash' => hash('sha256', $ownerToken),
                'client_key' => $clientKey,
                'file_name'  => $fileName,
                'file_size'  => $fileSize,
                'file_type'  => $fileType,
                'offset'     => 0,
                'completed'  => false,
                'created_at' => time(),
                'updated_at' => time(),
            ];

            $partPath = $this->partPath($uploadDir);
            if (@file_put_contents($partPath, '') === false) {
                throw new MediaUploadException('Server tidak dapat menyiapkan file upload.', 500);
            }

            $this->writeMetadata($uploadDir, $metadata);

            return $this->responseMetadata($uploadId, $metadata);
        });
    }

    public function append(
        string $ownerToken,
        string $uploadId,
        int $requestedOffset,
        string $checksum,
        UploadedFile $chunk
    ): array {
        $this->assertOwnerToken($ownerToken);
        $this->assertHex($uploadId, 'Identitas upload tidak valid.');
        $this->assertHex($checksum, 'Checksum chunk tidak valid.');

        if (!$chunk->isValid() || $chunk->hasMoved()) {
            throw new MediaUploadException('Chunk upload tidak lengkap. Periksa koneksi lalu coba lagi.');
        }

        $chunkSize = $chunk->getSize();
        if ($chunkSize <= 0 || $chunkSize > self::CHUNK_BYTES) {
            throw new MediaUploadException('Ukuran chunk tidak valid.', 413);
        }

        $chunkPath = $chunk->getTempName();
        if (!is_file($chunkPath) || !hash_equals($checksum, hash_file('sha256', $chunkPath))) {
            throw new MediaUploadException('Isi chunk rusak saat dikirim. Upload akan dicoba kembali.', 422);
        }

        $uploadDir = $this->uploadDirectory($uploadId);
        if (!is_dir($uploadDir)) {
            throw new MediaUploadException('Sesi upload tidak ditemukan. Mulai upload kembali.', 404);
        }

        return $this->withLock($uploadDir, function () use (
            $uploadDir,
            $uploadId,
            $ownerToken,
            $requestedOffset,
            $checksum,
            $chunkPath,
            $chunkSize
        ): array {
            $metadata = $this->requireOwnedMetadata($uploadDir, $ownerToken);
            $metadata = $this->syncOffset($uploadDir, $metadata);
            $currentOffset = (int) $metadata['offset'];
            $fileSize = (int) $metadata['file_size'];

            if ($requestedOffset < 0 || $requestedOffset + $chunkSize > $fileSize) {
                throw new MediaUploadException('Rentang chunk tidak valid.', 422);
            }

            if ($requestedOffset < $currentOffset) {
                if ($requestedOffset + $chunkSize > $currentOffset
                    || !$this->existingChunkMatches($uploadDir, $requestedOffset, $chunkSize, $checksum)
                ) {
                    throw $this->offsetException($currentOffset);
                }

                return $this->responseMetadata($uploadId, $metadata);
            }

            if ($requestedOffset !== $currentOffset) {
                throw $this->offsetException($currentOffset);
            }

            $input = @fopen($chunkPath, 'rb');
            $output = @fopen($this->partPath($uploadDir), 'c+b');
            if ($input === false || $output === false) {
                if (is_resource($input)) {
                    fclose($input);
                }
                if (is_resource($output)) {
                    fclose($output);
                }
                throw new MediaUploadException('Server gagal menulis chunk upload.', 500);
            }

            if (fseek($output, $currentOffset) !== 0) {
                fclose($input);
                fclose($output);
                throw new MediaUploadException('Server gagal menentukan posisi chunk.', 500);
            }

            $written = stream_copy_to_stream($input, $output);
            fflush($output);

            if ($written !== $chunkSize) {
                ftruncate($output, $currentOffset);
                fclose($input);
                fclose($output);
                throw new MediaUploadException('Chunk tidak tersimpan lengkap di server.', 500);
            }

            fclose($input);
            fclose($output);

            $metadata['offset'] = $currentOffset + $chunkSize;
            $metadata['updated_at'] = time();

            if ((int) $metadata['offset'] === $fileSize) {
                $this->validateCompletedFile($uploadDir, $metadata);
                $metadata['completed'] = true;
            }

            $this->writeMetadata($uploadDir, $metadata);

            return $this->responseMetadata($uploadId, $metadata);
        });
    }

    public function consume(string $ownerToken, string $uploadId): string
    {
        $this->assertOwnerToken($ownerToken);
        $this->assertHex($uploadId, 'Identitas upload tidak valid.');

        $uploadDir = $this->uploadDirectory($uploadId);
        if (!is_dir($uploadDir)) {
            throw new MediaUploadException('Hasil upload tidak ditemukan. Upload file kembali.', 404);
        }

        $newFileName = $this->withLock($uploadDir, function () use ($uploadDir, $ownerToken): string {
            $metadata = $this->requireOwnedMetadata($uploadDir, $ownerToken);
            $metadata = $this->syncOffset($uploadDir, $metadata);
            if (empty($metadata['completed']) || (int) $metadata['offset'] !== (int) $metadata['file_size']) {
                throw new MediaUploadException('Upload media belum selesai.', 409);
            }

            $mime = $this->detectMime($this->partPath($uploadDir));
            $extension = self::ALLOWED_MIME_EXTENSIONS[$mime][0] ?? null;
            if ($extension === null) {
                throw new MediaUploadException('Format file hasil upload tidak didukung.');
            }

            $this->ensureDirectory($this->destinationRoot);
            $newFileName = bin2hex(random_bytes(20)) . '.' . $extension;
            $destination = rtrim($this->destinationRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFileName;

            if (!@rename($this->partPath($uploadDir), $destination)) {
                if (!@copy($this->partPath($uploadDir), $destination) || !@unlink($this->partPath($uploadDir))) {
                    @unlink($destination);
                    throw new MediaUploadException('Server gagal memindahkan media ke folder publik.', 500);
                }
            }

            return $newFileName;
        });

        $this->removeUploadDirectory($uploadDir);

        return $newFileName;
    }

    public function cancel(string $ownerToken, string $uploadId): void
    {
        $this->assertOwnerToken($ownerToken);
        $this->assertHex($uploadId, 'Identitas upload tidak valid.');

        $uploadDir = $this->uploadDirectory($uploadId);
        if (!is_dir($uploadDir)) {
            return;
        }

        $this->withLock($uploadDir, function () use ($uploadDir, $ownerToken): void {
            $this->requireOwnedMetadata($uploadDir, $ownerToken);
        });
        $this->removeUploadDirectory($uploadDir);
    }

    private function validateCompletedFile(string $uploadDir, array $metadata): void
    {
        $partPath = $this->partPath($uploadDir);
        clearstatcache(true, $partPath);
        if (!is_file($partPath) || filesize($partPath) !== (int) $metadata['file_size']) {
            throw new MediaUploadException('Ukuran file hasil upload tidak sesuai.', 422);
        }

        $detectedMime = $this->detectMime($partPath);
        $declaredMime = (string) $metadata['file_type'];
        $extension = strtolower((string) pathinfo((string) $metadata['file_name'], PATHINFO_EXTENSION));

        if ($detectedMime !== $declaredMime
            || !isset(self::ALLOWED_MIME_EXTENSIONS[$detectedMime])
            || !in_array($extension, self::ALLOWED_MIME_EXTENSIONS[$detectedMime], true)
        ) {
            throw new MediaUploadException('Isi file tidak sesuai dengan format media yang dipilih.');
        }
    }

    private function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return strtolower((string) $finfo->file($path));
    }

    private function existingChunkMatches(
        string $uploadDir,
        int $offset,
        int $length,
        string $checksum
    ): bool {
        $handle = @fopen($this->partPath($uploadDir), 'rb');
        if ($handle === false || fseek($handle, $offset) !== 0) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            return false;
        }

        $context = hash_init('sha256');
        hash_update_stream($context, $handle, $length);
        fclose($handle);

        return hash_equals($checksum, hash_final($context));
    }

    private function syncOffset(string $uploadDir, array $metadata): array
    {
        $partPath = $this->partPath($uploadDir);
        clearstatcache(true, $partPath);
        $actualSize = is_file($partPath) ? (int) filesize($partPath) : 0;
        if ($actualSize > (int) ($metadata['file_size'] ?? 0)) {
            throw new MediaUploadException('Data upload di server tidak konsisten.', 500);
        }

        $metadata['offset'] = $actualSize;
        if ($actualSize === (int) ($metadata['file_size'] ?? 0) && empty($metadata['completed'])) {
            $this->validateCompletedFile($uploadDir, $metadata);
            $metadata['completed'] = true;
        } elseif ($actualSize !== (int) ($metadata['file_size'] ?? 0)) {
            $metadata['completed'] = false;
        }

        return $metadata;
    }

    private function requireOwnedMetadata(string $uploadDir, string $ownerToken): array
    {
        $metadata = $this->readMetadata($uploadDir);
        if ($metadata === null) {
            throw new MediaUploadException('Metadata upload tidak ditemukan. Mulai upload kembali.', 404);
        }

        if (!hash_equals((string) ($metadata['owner_hash'] ?? ''), hash('sha256', $ownerToken))) {
            throw new MediaUploadException('Sesi upload tidak valid.', 403);
        }

        return $metadata;
    }

    private function responseMetadata(string $uploadId, array $metadata): array
    {
        return [
            'upload_id'  => $uploadId,
            'offset'     => (int) $metadata['offset'],
            'file_size'  => (int) $metadata['file_size'],
            'chunk_size' => self::CHUNK_BYTES,
            'completed'  => (bool) $metadata['completed'],
        ];
    }

    private function offsetException(int $expectedOffset): MediaUploadException
    {
        return new MediaUploadException('Posisi upload berubah. Lanjutkan dari byte ' . $expectedOffset . '.', 409);
    }

    private function withLock(string $uploadDir, callable $callback): mixed
    {
        $lock = @fopen($uploadDir . DIRECTORY_SEPARATOR . 'upload.lock', 'c+');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            throw new MediaUploadException('Upload sedang tidak dapat dikunci. Coba lagi.', 503);
        }

        try {
            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function readMetadata(string $uploadDir): ?array
    {
        $path = $uploadDir . DIRECTORY_SEPARATOR . 'metadata.json';
        if (!is_file($path)) {
            return null;
        }

        $metadata = json_decode((string) file_get_contents($path), true);
        if (!is_array($metadata)) {
            throw new MediaUploadException('Metadata upload rusak. Mulai upload kembali.', 500);
        }

        return $metadata;
    }

    private function writeMetadata(string $uploadDir, array $metadata): void
    {
        $path = $uploadDir . DIRECTORY_SEPARATOR . 'metadata.json';
        $temporary = $path . '.tmp';
        $json = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (@file_put_contents($temporary, $json, LOCK_EX) === false
            || (!@rename($temporary, $path) && (!@copy($temporary, $path) || !@unlink($temporary)))
        ) {
            @unlink($temporary);
            throw new MediaUploadException('Server gagal menyimpan status upload.', 500);
        }
    }

    private function cleanupExpiredUploads(): void
    {
        $entries = @scandir($this->temporaryRoot);
        if (!is_array($entries)) {
            return;
        }

        $expiredBefore = time() - self::UPLOAD_TTL;
        foreach ($entries as $entry) {
            if (!preg_match('/^[a-f0-9]{64}$/', $entry)) {
                continue;
            }

            $directory = $this->uploadDirectory($entry);
            try {
                $metadata = $this->readMetadata($directory);
            } catch (MediaUploadException) {
                $this->removeUploadDirectory($directory);
                continue;
            }

            $updatedAt = isset($metadata['updated_at'])
                ? (int) $metadata['updated_at']
                : (int) (@filemtime($directory) ?: 0);
            if ($updatedAt > 0 && $updatedAt < $expiredBefore) {
                $this->removeUploadDirectory($directory);
            }
        }
    }

    private function removeUploadDirectory(string $uploadDir): void
    {
        foreach (['upload.part', 'metadata.json', 'metadata.json.tmp', 'upload.lock'] as $file) {
            $path = $uploadDir . DIRECTORY_SEPARATOR . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        @rmdir($uploadDir);
    }

    private function uploadDirectory(string $uploadId): string
    {
        return rtrim($this->temporaryRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uploadId;
    }

    private function partPath(string $uploadDir): string
    {
        return $uploadDir . DIRECTORY_SEPARATOR . 'upload.part';
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new MediaUploadException('Folder upload tidak dapat dibuat.', 500);
        }

        if (!is_writable($directory)) {
            throw new MediaUploadException('Folder upload tidak dapat ditulis.', 500);
        }
    }

    private function assertOwnerToken(string $ownerToken): void
    {
        $this->assertHex($ownerToken, 'Token sesi upload tidak valid.');
    }

    private function assertHex(string $value, string $message): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $value)) {
            throw new MediaUploadException($message, 403);
        }
    }
}
