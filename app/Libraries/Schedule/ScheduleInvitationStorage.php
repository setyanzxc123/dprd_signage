<?php

namespace App\Libraries\Schedule;

use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

final class ScheduleInvitationStorage
{
    private const MAX_SIZE = 10 * 1024 * 1024;

    /** @return array{file: ?UploadedFile, error?: string} */
    public function validate(?UploadedFile $file): array
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return ['file' => null];
        }
        if (! $file->isValid()) {
            return ['file' => null, 'error' => 'Unggahan undangan gagal diproses. Silakan pilih ulang file.'];
        }
        if ($file->getSize() > self::MAX_SIZE) {
            return ['file' => null, 'error' => 'Ukuran PDF undangan maksimal 10 MB.'];
        }

        $extension = strtolower($file->getClientExtension());
        $mime = strtolower($file->getMimeType());
        if ($extension !== 'pdf' || ! in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            return ['file' => null, 'error' => 'Undangan harus berupa file PDF yang valid.'];
        }

        return ['file' => $file];
    }

    /** @return array{file: string, original_name: string} */
    public function store(UploadedFile $upload): array
    {
        $directory = $this->directory();
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori penyimpanan undangan tidak dapat dibuat.');
        }

        $fileName = bin2hex(random_bytes(20)) . '.pdf';
        $upload->move($directory, $fileName);
        if (! $upload->hasMoved()) {
            throw new RuntimeException('PDF undangan tidak dapat disimpan.');
        }

        return [
            'file' => $fileName,
            'original_name' => mb_substr(basename($upload->getClientName()), 0, 255),
        ];
    }

    public function path(mixed $fileName): ?string
    {
        if (! is_string($fileName) || preg_match('/^[a-f0-9]{40}\.pdf$/', $fileName) !== 1) {
            return null;
        }

        $path = $this->directory() . DIRECTORY_SEPARATOR . $fileName;

        return is_file($path) ? $path : null;
    }

    public function delete(mixed $fileName): void
    {
        $path = $this->path($fileName);
        if ($path !== null) {
            unlink($path);
        }
    }

    public function directory(): string
    {
        return WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'agenda-invitations';
    }
}
