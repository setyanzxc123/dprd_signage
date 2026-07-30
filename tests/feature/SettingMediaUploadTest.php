<?php

use App\Controllers\Admin\SettingController;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
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
