<?php

use App\Libraries\Schedule\ScheduleInvitationStorage;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;

final class ScheduleInvitationStorageTest extends CIUnitTestCase
{
    public function testStoresAndDeletesValidPdfUsingPrivateRandomName(): void
    {
        $temporary = tempnam(WRITEPATH . 'cache', 'invitation-test-');
        $this->assertIsString($temporary);
        file_put_contents($temporary, "%PDF-1.4\n%%EOF\n");
        $upload = new ScheduleInvitationUploadedFileStub(
            $temporary,
            'Undangan Rapat.pdf',
            'application/pdf',
            null,
            UPLOAD_ERR_OK,
        );
        $storage = new ScheduleInvitationStorage();

        $validation = $storage->validate($upload);
        $this->assertArrayNotHasKey('error', $validation);
        $stored = $storage->store($validation['file']);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}\.pdf$/', $stored['file']);
        $this->assertSame('Undangan Rapat.pdf', $stored['original_name']);
        $this->assertNotNull($storage->path($stored['file']));

        $storage->delete($stored['file']);
        $this->assertNull($storage->path($stored['file']));
    }

    public function testRejectsNonPdfUpload(): void
    {
        $temporary = tempnam(WRITEPATH . 'cache', 'invitation-test-');
        $this->assertIsString($temporary);
        file_put_contents($temporary, 'not a pdf');
        $upload = new ScheduleInvitationUploadedFileStub(
            $temporary,
            'undangan.txt',
            'text/plain',
            null,
            UPLOAD_ERR_OK,
        );

        $result = (new ScheduleInvitationStorage())->validate($upload);

        $this->assertSame('Undangan harus berupa file PDF yang valid.', $result['error']);
        @unlink($temporary);
    }
}

/**
 * PHP CLI cannot create a real HTTP upload, so this double only replaces the
 * transport checks performed by UploadedFile. MIME detection and all storage
 * validation continue to use the real file contents.
 */
final class ScheduleInvitationUploadedFileStub extends UploadedFile
{
    public function isValid(): bool
    {
        return $this->getError() === UPLOAD_ERR_OK && is_file($this->path);
    }

    public function move(string $targetPath, ?string $name = null, bool $overwrite = false)
    {
        if ($this->hasMoved || ! $this->isValid()) {
            return false;
        }

        $targetPath = rtrim($targetPath, '/\\') . DIRECTORY_SEPARATOR;
        if (! is_dir($targetPath) && ! mkdir($targetPath, 0750, true) && ! is_dir($targetPath)) {
            return false;
        }

        $name ??= $this->getName();
        $destination = $targetPath . basename($name);
        if (! $overwrite && is_file($destination)) {
            return false;
        }
        if (! rename($this->path, $destination)) {
            return false;
        }

        $this->hasMoved = true;
        $this->path = $targetPath;
        $this->name = basename($destination);

        return true;
    }
}
