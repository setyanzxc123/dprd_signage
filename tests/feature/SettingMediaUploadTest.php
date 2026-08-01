<?php

use App\Controllers\Admin\SettingController;
use App\Libraries\Media\PostChunkMediaUpload;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class SettingMediaUploadTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $settingsDb;
    private Forge $forge;
    /** @var list<string> */
    private array $mediaFilesToClean = [];

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan.');
        }

        $this->settingsDb = Database::connect('tests');
        $this->forge = Database::forge('tests');
        $this->forge->dropTable('settings', true);
        $this->forge->addField([
            'key_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'value'    => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('key_name');
        $this->forge->createTable('settings');
    }

    protected function tearDown(): void
    {
        foreach ($this->mediaFilesToClean as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (isset($this->forge)) {
            $this->forge->dropTable('settings', true);
        }
        parent::tearDown();
    }

    public function testSettingsPageUsesPostChunkUploadWithStandardMultipartFallback(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/pengaturan');

        $response->assertOK();
        $body = $response->response()->getBody();

        $this->assertStringContainsString('enctype="multipart/form-data"', $body);
        $this->assertStringContainsString('name="media_file"', $body);
        $this->assertStringContainsString('Maksimal 200 MB', $body);
        $this->assertStringContainsString('id="settings-upload-bar"', $body);
        $this->assertStringContainsString('id="settings-upload-speed"', $body);
        $this->assertStringContainsString('name="media_upload_key"', $body);
        $this->assertStringContainsString('data-upload-start-url="/admin/pengaturan/media-upload/start"', $body);
        $this->assertStringContainsString('data-upload-chunk-url="/admin/pengaturan/media-upload/chunk"', $body);
        $this->assertStringContainsString('data-upload-chunk-size="524288"', $body);
        $this->assertMatchesRegularExpression('/data-upload-token="[a-f0-9]{64}"/', $body);
        $this->assertStringNotContainsString('media/tus', $body);
        $this->assertStringNotContainsString('PATCH', $body);
        $this->assertStringNotContainsString('settings-upload.js', $body);
        $this->assertStringNotContainsString('uppy-status-bar', $body);
    }

    public function testControllerKeepsTwoHundredMegabyteApplicationLimit(): void
    {
        $reflection = new ReflectionClass(SettingController::class);
        $limit = $reflection->getReflectionConstant('MEDIA_MAX_BYTES')?->getValue();

        $this->assertSame(200 * 1024 * 1024, $limit);
    }

    public function testSavingThemeWithoutNewUploadKeepsExistingMedia(): void
    {
        $oldFileName = 'setting-test-old-' . bin2hex(random_bytes(6)) . '.png';
        $oldPath = $this->createMediaFile($oldFileName, $this->pngContent());
        $this->insertSetting('media_file', $oldFileName);

        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post('/admin/pengaturan/save', $this->settingsPayload(['tema_signage' => 'light']));

        $response->assertRedirectTo(base_url('admin/pengaturan'));
        $this->assertFileExists($oldPath);
        $this->assertSame($oldFileName, $this->settingValue('media_file'));
        $this->assertSame('light', $this->settingValue('tema_signage'));
    }

    public function testReplacingMediaDeletesOldFileAfterNewMediaIsSaved(): void
    {
        $oldFileName = 'setting-test-old-' . bin2hex(random_bytes(6)) . '.png';
        $oldPath = $this->createMediaFile($oldFileName, $this->pngContent());
        $this->insertSetting('media_file', $oldFileName);

        $uploadToken = bin2hex(random_bytes(32));
        $uploadId = $this->prepareCompletedChunkUpload($uploadToken);

        $response = $this
            ->withSession([
                'auth_user'        => $this->adminSession(),
                'media_chunk_token' => $uploadToken,
            ])
            ->post('/admin/pengaturan/save', $this->settingsPayload([
                'media_upload_key' => $uploadId,
            ]));

        $response->assertRedirectTo(base_url('admin/pengaturan'));
        $newFileName = (string) $this->settingValue('media_file');
        $newPath = FCPATH . 'uploads/media/' . $newFileName;
        $this->mediaFilesToClean[] = $newPath;

        $this->assertNotSame($oldFileName, $newFileName);
        $this->assertFileExists($newPath);
        $this->assertFileDoesNotExist($oldPath);
    }

    /** @param array<string, string> $overrides */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            csrf_token()         => csrf_hash(),
            'running_text'       => 'Informasi pengujian',
            'running_text_aktif' => '1',
            'media_mode'         => 'image',
            'tema_signage'       => 'dark',
            'media_upload_key'   => '',
        ], $overrides);
    }

    private function insertSetting(string $key, string $value): void
    {
        $this->settingsDb->table('settings')->insert([
            'key_name' => $key,
            'value'    => $value,
        ]);
    }

    private function settingValue(string $key): ?string
    {
        $row = $this->settingsDb->table('settings')->where('key_name', $key)->get()->getRowArray();

        return isset($row['value']) ? (string) $row['value'] : null;
    }

    private function createMediaFile(string $fileName, string $content): string
    {
        $directory = FCPATH . 'uploads/media';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = $directory . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($path, $content);
        $this->mediaFilesToClean[] = $path;

        return $path;
    }

    private function prepareCompletedChunkUpload(string $uploadToken): string
    {
        $content = $this->pngContent();
        $temporaryPath = tempnam(sys_get_temp_dir(), 'setting-media-chunk-');
        if ($temporaryPath === false) {
            $this->fail('Gagal membuat file sementara untuk pengujian upload.');
        }
        file_put_contents($temporaryPath, $content);

        $chunk = new class(
            $temporaryPath,
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

        $uploader = new PostChunkMediaUpload();
        $started = $uploader->start(
            $uploadToken,
            bin2hex(random_bytes(32)),
            'pengganti.png',
            strlen($content),
            'image/png'
        );
        $completed = $uploader->append(
            $uploadToken,
            $started['upload_id'],
            0,
            hash('sha256', $content),
            $chunk
        );
        unlink($temporaryPath);

        $this->assertTrue($completed['completed']);

        return $started['upload_id'];
    }

    private function pngContent(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
    }

    /** @return array<string, mixed> */
    private function adminSession(): array
    {
        return [
            'id'       => 1,
            'name'     => 'Admin Pengujian',
            'username' => 'admin-test',
            'role'     => 'superadmin',
        ];
    }
}
