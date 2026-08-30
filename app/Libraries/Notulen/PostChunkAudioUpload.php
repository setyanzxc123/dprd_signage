<?php

namespace App\Libraries\Notulen;

use App\Libraries\Media\MediaUploadException;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Chunked upload handler untuk file rekaman audio notulen.
 *
 * Protokol identik dengan PostChunkMediaUpload (pengaturan media) sehingga
 * JS di front-end dapat menggunakan logika yang sama persis.
 */
class PostChunkAudioUpload
{
    public const MAX_BYTES   = 314_572_800; // 300 MB
    public const CHUNK_BYTES =     512_000; // 512 KB per chunk
    public const UPLOAD_TTL  =      86_400; // 24 jam

    /** MIME → ekstensi yang diizinkan */
    private const ALLOWED = [
        'audio/mpeg'   => ['mp3'],
        'audio/mp3'    => ['mp3'],
        'audio/x-m4a'  => ['m4a'],
        'audio/mp4'    => ['m4a', 'mp4'],
        'audio/wav'    => ['wav'],
        'audio/x-wav'  => ['wav'],
        'audio/ogg'    => ['ogg'],
        'audio/aac'    => ['aac'],
        'audio/x-aac'  => ['aac'],
        'audio/flac'   => ['flac'],
        'audio/x-flac' => ['flac'],
        'video/mp4'    => ['mp4'],
    ];

    public function __construct(
        private readonly string $temporaryRoot = WRITEPATH . 'uploads/audio-chunks',
    ) {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    public function start(
        string $ownerToken,
        string $clientKey,
        string $fileName,
        int $fileSize,
        string $fileType
    ): array {
        $this->assertHex($ownerToken, 'Token sesi upload tidak valid.');
        $this->assertHex($clientKey, 'Identitas file tidak valid.');

        $fileName  = basename(trim($fileName));
        $fileType  = strtolower(trim($fileType));
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileName === '' || strlen($fileName) > 255) {
            throw new MediaUploadException('Nama file rekaman tidak valid.');
        }

        if ($fileSize <= 0 || $fileSize > self::MAX_BYTES) {
            $maxMb = round(self::MAX_BYTES / 1_048_576);
            throw new MediaUploadException("Ukuran file harus lebih dari 0 dan maksimal {$maxMb} MB.", 413);
        }

        if (! $this->isAllowedType($fileType, $extension)) {
            throw new MediaUploadException('Format file tidak didukung. Gunakan MP3, M4A, WAV, OGG, AAC, FLAC, atau MP4.');
        }

        $this->ensureDirectory($this->temporaryRoot);
        $this->cleanupExpiredUploads();

        $uploadId  = hash_hmac('sha256', $clientKey, $ownerToken);
        $uploadDir = $this->uploadDirectory($uploadId);
        $this->ensureDirectory($uploadDir);

        return $this->withLock($uploadDir, function () use (
            $uploadDir, $uploadId, $ownerToken, $clientKey,
            $fileName, $fileSize, $fileType
        ): array {
            $metadata = $this->readMetadata($uploadDir);

            if ($metadata !== null) {
                $matches = hash_equals((string) ($metadata['owner_hash'] ?? ''), hash('sha256', $ownerToken))
                    && hash_equals((string) ($metadata['client_key'] ?? ''), $clientKey)
                    && (string) ($metadata['file_name'] ?? '') === $fileName
                    && (int) ($metadata['file_size'] ?? 0) === $fileSize
                    && (string) ($metadata['file_type'] ?? '') === $fileType;

                if (! $matches) {
                    throw new MediaUploadException('Data upload tidak cocok. Pilih ulang file lalu coba lagi.', 409);
                }

                $metadata = $this->syncOffset($uploadDir, $metadata);
                $this->writeMetadata($uploadDir, $metadata);

                return $this->responsePayload($uploadId, $metadata);
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

            if (@file_put_contents($this->partPath($uploadDir), '') === false) {
                throw new MediaUploadException('Server tidak dapat menyiapkan file upload.', 500);
            }

            $this->writeMetadata($uploadDir, $metadata);

            return $this->responsePayload($uploadId, $metadata);
        });
    }

    public function append(
        string $ownerToken,
        string $uploadId,
        int $requestedOffset,
        string $checksum,
        UploadedFile $chunk
    ): array {
        $this->assertHex($ownerToken, 'Token sesi upload tidak valid.');
        $this->assertHex($uploadId, 'Identitas upload tidak valid.');
        $this->assertHex($checksum, 'Checksum chunk tidak valid.');

        if (! $chunk->isValid() || $chunk->hasMoved()) {
            throw new MediaUploadException('Chunk upload tidak lengkap. Periksa koneksi lalu coba lagi.');
        }

        $chunkSize = $chunk->getSize();
        if ($chunkSize <= 0 || $chunkSize > self::CHUNK_BYTES) {
            throw new MediaUploadException('Ukuran chunk tidak valid.', 413);
        }

        $chunkPath = $chunk->getTempName();
        if (! is_file($chunkPath) || ! hash_equals($checksum, hash_file('sha256', $chunkPath))) {
            throw new MediaUploadException('Isi chunk rusak saat dikirim. Upload akan dicoba kembali.', 422);
        }

        $uploadDir = $this->uploadDirectory($uploadId);
        if (! is_dir($uploadDir)) {
            throw new MediaUploadException('Sesi upload tidak ditemukan. Mulai upload kembali.', 404);
        }

        return $this->withLock($uploadDir, function () use (
            $uploadDir, $uploadId, $ownerToken,
            $requestedOffset, $checksum, $chunkPath, $chunkSize
        ): array {
            $metadata      = $this->requireOwnedMetadata($uploadDir, $ownerToken);
            $metadata      = $this->syncOffset($uploadDir, $metadata);
            $currentOffset = (int) $metadata['offset'];
            $fileSize      = (int) $metadata['file_size'];

            if ($requestedOffset < 0 || $requestedOffset + $chunkSize > $fileSize) {
                throw new MediaUploadException('Rentang chunk tidak valid.', 422);
            }

            if ($requestedOffset < $currentOffset) {
                if ($requestedOffset + $chunkSize > $currentOffset
                    || ! $this->existingChunkMatches($uploadDir, $requestedOffset, $chunkSize, $checksum)
                ) {
                    throw $this->offsetException($currentOffset);
                }

                return $this->responsePayload($uploadId, $metadata);
            }

            if ($requestedOffset !== $currentOffset) {
                throw $this->offsetException($currentOffset);
            }

            $input  = @fopen($chunkPath, 'rb');
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

            $metadata['offset']     = $currentOffset + $chunkSize;
            $metadata['updated_at'] = time();

            if ((int) $metadata['offset'] === $fileSize) {
                $this->validateCompletedFile($uploadDir, $metadata);
                $metadata['completed'] = true;
            }

            $this->writeMetadata($uploadDir, $metadata);

            return $this->responsePayload($uploadId, $metadata);
        });
    }

    /**
     * Pindahkan file audio yang sudah lengkap ke $destinationPath.
     * Mengembalikan metadata sesi (file_name, file_size, file_type) dari
     * nama asli yang dikirim client saat start. Setelah berhasil, direktori
     * sesi chunk dihapus.
     *
     * @return array{file_name: string, file_size: int, file_type: string}
     */
    public function consume(string $ownerToken, string $uploadId, string $destinationPath): array
    {
        $this->assertHex($ownerToken, 'Token sesi upload tidak valid.');
        $this->assertHex($uploadId, 'Identitas upload tidak valid.');

        $uploadDir = $this->uploadDirectory($uploadId);
        if (! is_dir($uploadDir)) {
            throw new MediaUploadException('Hasil upload tidak ditemukan. Upload file kembali.', 404);
        }

        $metadata = $this->withLock($uploadDir, function () use ($uploadDir, $ownerToken, $destinationPath) {
            $metadata = $this->requireOwnedMetadata($uploadDir, $ownerToken);
            $metadata = $this->syncOffset($uploadDir, $metadata);

            if (empty($metadata['completed']) || (int) $metadata['offset'] !== (int) $metadata['file_size']) {
                throw new MediaUploadException('Upload audio belum selesai.', 409);
            }

            $destDir = dirname($destinationPath);
            if (! is_dir($destDir) && ! @mkdir($destDir, 0775, true) && ! is_dir($destDir)) {
                throw new MediaUploadException('Folder tujuan tidak dapat dibuat.', 500);
            }

            $partPath = $this->partPath($uploadDir);

            if (! @rename($partPath, $destinationPath)) {
                if (! @copy($partPath, $destinationPath) || ! @unlink($partPath)) {
                    @unlink($destinationPath);
                    throw new MediaUploadException('Server gagal memindahkan audio ke folder tujuan.', 500);
                }
            }

            return $metadata;
        });

        $this->removeUploadDirectory($uploadDir);

        return [
            'file_name' => (string) ($metadata['file_name'] ?? ''),
            'file_size' => (int) ($metadata['file_size'] ?? 0),
            'file_type' => (string) ($metadata['file_type'] ?? ''),
        ];
    }

    public function cancel(string $ownerToken, string $uploadId): void
    {
        $this->assertHex($ownerToken, 'Token sesi upload tidak valid.');
        $this->assertHex($uploadId, 'Identitas upload tidak valid.');

        $uploadDir = $this->uploadDirectory($uploadId);
        if (! is_dir($uploadDir)) {
            return;
        }

        $this->withLock($uploadDir, function () use ($uploadDir, $ownerToken): void {
            $this->requireOwnedMetadata($uploadDir, $ownerToken);
        });

        $this->removeUploadDirectory($uploadDir);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function isAllowedType(string $mimeType, string $extension): bool
    {
        if (! isset(self::ALLOWED[$mimeType])) {
            return false;
        }

        return in_array($extension, self::ALLOWED[$mimeType], true);
    }

    private function validateCompletedFile(string $uploadDir, array $metadata): void
    {
        $partPath = $this->partPath($uploadDir);
        clearstatcache(true, $partPath);

        if (! is_file($partPath) || filesize($partPath) !== (int) $metadata['file_size']) {
            throw new MediaUploadException('Ukuran file hasil upload tidak sesuai.', 422);
        }
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

        if (! hash_equals((string) ($metadata['owner_hash'] ?? ''), hash('sha256', $ownerToken))) {
            throw new MediaUploadException('Sesi upload tidak valid.', 403);
        }

        return $metadata;
    }

    private function responsePayload(string $uploadId, array $metadata): array
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
        return new MediaUploadException(
            'Posisi upload berubah. Lanjutkan dari byte ' . $expectedOffset . '.',
            409,
        );
    }

    private function withLock(string $uploadDir, callable $callback): mixed
    {
        $lock = @fopen($uploadDir . DIRECTORY_SEPARATOR . 'upload.lock', 'c+');
        if ($lock === false || ! flock($lock, LOCK_EX)) {
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
        if (! is_file($path)) {
            return null;
        }

        $metadata = json_decode((string) file_get_contents($path), true);
        if (! is_array($metadata)) {
            throw new MediaUploadException('Metadata upload rusak. Mulai upload kembali.', 500);
        }

        return $metadata;
    }

    private function writeMetadata(string $uploadDir, array $metadata): void
    {
        $path      = $uploadDir . DIRECTORY_SEPARATOR . 'metadata.json';
        $temporary = $path . '.tmp';
        $json      = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (@file_put_contents($temporary, $json, LOCK_EX) === false
            || (! @rename($temporary, $path) && (! @copy($temporary, $path) || ! @unlink($temporary)))
        ) {
            @unlink($temporary);
            throw new MediaUploadException('Server gagal menyimpan status upload.', 500);
        }
    }

    private function cleanupExpiredUploads(): void
    {
        $entries = @scandir($this->temporaryRoot);
        if (! is_array($entries)) {
            return;
        }

        $expiredBefore = time() - self::UPLOAD_TTL;

        foreach ($entries as $entry) {
            if (! preg_match('/^[a-f0-9]{64}$/', $entry)) {
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
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new MediaUploadException('Folder upload tidak dapat dibuat.', 500);
        }

        if (! is_writable($directory)) {
            throw new MediaUploadException('Folder upload tidak dapat ditulis.', 500);
        }
    }

    private function assertHex(string $value, string $message): void
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $value)) {
            throw new MediaUploadException($message, 403);
        }
    }
}
