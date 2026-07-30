<?php

use App\Libraries\Media\ResumableMediaUpload;
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
    /** @var list<string> */
    private array $createdMediaFiles = [];

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
        foreach ($this->createdMediaFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        if (isset($this->forge)) {
            $this->forge->dropTable('settings', true);
        }
        parent::tearDown();
    }

    public function testSettingsPageAdvertisesResumableUploadWithFiftyMegabyteLimit(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/pengaturan');

        $response->assertOK();
        $body = $response->response()->getBody();
        $this->assertStringContainsString('data-media-max-bytes="209715200"', $body);
        $this->assertStringContainsString('data-media-chunk-bytes="5242880"', $body);
        $this->assertStringContainsString(
            'data-media-upload-endpoint="/admin/pengaturan/media/tus"',
            $body,
        );
        $this->assertStringNotContainsString(
            'data-media-upload-endpoint="http://',
            $body,
        );
        $this->assertStringContainsString('settings-upload.js', $body);
        $this->assertStringContainsString('uppy-status-bar.min.css', $body);
        $this->assertStringContainsString('Maksimal 200 MB', $body);
        $this->assertStringContainsString('Upload dapat dilanjutkan saat koneksi kembali', $body);
        $this->assertStringContainsString('id="settings-upload-statusbar"', $body);
        $this->assertStringContainsString('id="settings-upload-speed"', $body);
        $this->assertStringNotContainsString('id="settings-upload-pause"', $body);
        $this->assertMatchesRegularExpression('/data-media-upload-token="[a-f0-9]{64}"/', $body);
    }

    public function testTusEndpointRequiresAdminAuthentication(): void
    {
        $this->post('/admin/pengaturan/media/tus')
            ->assertRedirectTo(base_url('login?akses=admin'));
    }

    public function testTusEndpointRejectsMissingStableUploadToken(): void
    {
        $response = $this
            ->withSession([
                'auth_user'          => $this->adminSession(),
                'media_upload_token' => str_repeat('a', 64),
            ])
            ->post('/admin/pengaturan/media/tus');

        $response->assertStatus(403);
        $this->assertStringContainsString('Token upload media tidak valid', $response->response()->getBody());
        $this->assertSame('1.0.0', $response->response()->getHeaderLine('Tus-Resumable'));
    }

    public function testTusLocationUsesSameHttpsOriginBehindProxy(): void
    {
        $controller = new ReflectionClass(App\Controllers\Admin\SettingController::class);
        $method = $controller->getMethod('sameOriginTusLocation');
        $instance = $controller->newInstanceWithoutConstructor();

        $request = service('request');
        $request->setHeader('Host', 'jadwaldprd.aicepalu.com');
        $request->setHeader('Origin', 'https://jadwaldprd.aicepalu.com');

        $requestProperty = $controller->getParentClass()?->getProperty('request');
        $this->assertNotNull($requestProperty);
        $requestProperty->setValue($instance, $request);

        $location = $method->invoke(
            $instance,
            'http://jadwaldprd.aicepalu.com/admin/pengaturan/media/tus/upload-id',
        );

        $this->assertSame(
            'https://jadwaldprd.aicepalu.com/admin/pengaturan/media/tus/upload-id',
            $location,
        );
    }

    public function testCompletedResumableUploadCanBeSavedAsActiveMedia(): void
    {
        $service = new ResumableMediaUpload();
        $uploadKey = '44444444-4444-4444-8444-' . bin2hex(random_bytes(6));
        $ownerToken = bin2hex(random_bytes(32));
        $temporaryPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR
            . 'media-tus' . DIRECTORY_SEPARATOR . $uploadKey . '.png';
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        $this->assertIsString($contents);
        file_put_contents($temporaryPath, $contents);
        $service->cache()->set($uploadKey, [
            'name'        => basename($temporaryPath),
            'size'        => strlen($contents),
            'offset'      => strlen($contents),
            'checksum'    => '',
            'location'    => '/admin/pengaturan/media/tus/' . $uploadKey,
            'file_path'   => $temporaryPath,
            'metadata'    => [
                'originalName' => 'media-pengaturan.png',
                'ownerToken'   => $ownerToken,
            ],
            'upload_type' => 'normal',
            'created_at'  => date('D, d M Y H:i:s \G\M\T'),
            'expires_at'  => date('D, d M Y H:i:s \G\M\T', time() + 3600),
        ]);

        $response = $this
            ->withSession([
                'auth_user'          => $this->adminSession(),
                'media_upload_token' => $ownerToken,
            ])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('/admin/pengaturan/save', [
                csrf_token()       => csrf_hash(),
                'media_upload_key' => $uploadKey,
                'running_text'     => 'Pengujian upload resumable',
                'media_mode'       => 'image',
                'tema_signage'     => 'dark',
            ]);

        $response->assertOK();
        $response->assertJSONFragment(['status' => 'success']);
        $row = $this->settingsDb->table('settings')
            ->where('key_name', 'media_file')
            ->get()
            ->getRowArray();
        $this->assertIsArray($row);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}\.png$/', (string) $row['value']);
        $finalPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'media'
            . DIRECTORY_SEPARATOR . $row['value'];
        $this->createdMediaFiles[] = $finalPath;
        $this->assertFileExists($finalPath);
        $this->assertFileDoesNotExist($temporaryPath);
        $this->assertNull($service->cache()->get($uploadKey));
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
