<?php

use App\Controllers\Api\V1\AdminWhatsAppController;
use App\Libraries\Otp\Providers\BaileysProvider;
use App\Libraries\WhatsApp\Contracts\HttpTransportInterface;
use App\Libraries\WhatsApp\ValueObjects\HttpResponse;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Config\Otp;

/**
 * Pengujian endpoint REST API Pengaturan WhatsApp Gateway & Device:
 * otorisasi bearer admin, respons status & fallback, permintaan pairing code,
 * dan logout sesi device.
 *
 * @internal
 */
final class ApiAdminWhatsAppTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const MEMBER_TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const ADMIN_TOKEN  = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    private BaseConnection $apiDb;
    private Forge $apiForge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian API WhatsApp admin.');
        }

        $this->apiDb = Database::connect('tests');
        $this->apiForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedIdentities();
    }

    protected function tearDown(): void
    {
        AdminWhatsAppController::setMockProvider(null);

        if (isset($this->apiForge)) {
            $this->dropTables();
        }

        try {
            cache()->delete(BaileysProvider::OFFLINE_CACHE_KEY);
        } catch (\Throwable) {
        }

        parent::tearDown();
    }

    public function testEndpointsRequireAdminToken(): void
    {
        $this->get('/api/v1/admin/pengaturan/whatsapp/status')->assertStatus(401);
        $this->post('/api/v1/admin/pengaturan/whatsapp/pair-code', ['phone' => '08123456789'])->assertStatus(401);
        $this->post('/api/v1/admin/pengaturan/whatsapp/logout')->assertStatus(401);

        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get('/api/v1/admin/pengaturan/whatsapp/status')
            ->assertStatus(401);
    }

    public function testMemberTokenReceivesForbidden403(): void
    {
        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get('/api/v1/admin/pengaturan/whatsapp/status')
            ->assertStatus(403);

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->post('/api/v1/admin/pengaturan/whatsapp/pair-code', ['phone' => '081234567890'])
            ->assertStatus(403);

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->post('/api/v1/admin/pengaturan/whatsapp/logout')
            ->assertStatus(403);
    }

    public function testStatusEndpointReturnsStructuredPayload(): void
    {
        $this->setMockGatewayTransport([
            'status' => 'success',
            'data'   => [
                'connected' => true,
                'status'    => 'connected',
                'user'      => [
                    'phone' => '6281234567890',
                    'name'  => 'Humas DPRD Sulteng',
                ],
            ],
        ]);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/admin/pengaturan/whatsapp/status?refresh=1');

        $response->assertStatus(200);
        $body = json_decode((string) $response->response()->getBody(), true);

        $this->assertSame('success', $body['status'] ?? null);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('provider', $body['data']);
        $this->assertArrayHasKey('fallback', $body['data']);
        $this->assertArrayHasKey('gateway', $body['data']);
        $this->assertArrayHasKey('qr', $body['data']);
        $this->assertArrayHasKey('enabled', $body['data']['fallback']);
        $this->assertArrayHasKey('can_fallback', $body['data']['fallback']);
        $this->assertSame('connected', $body['data']['gateway']['status']);
        $this->assertTrue($body['data']['gateway']['connected']);
        $this->assertSame('6281234567890', $body['data']['gateway']['phone']);
    }

    public function testStatusEndpointHandlesOfflineGateway(): void
    {
        $this->setMockGatewayTransport([
            'status'  => 'error',
            'message' => 'WhatsApp Gateway belum terhubung.',
            'code'    => 'WA_GATEWAY_OFFLINE',
        ], 503);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/admin/pengaturan/whatsapp/status?refresh=1');

        $response->assertStatus(200);
        $body = json_decode((string) $response->response()->getBody(), true);

        $this->assertSame('success', $body['status'] ?? null);
        $this->assertSame('offline', $body['data']['gateway']['status']);
        $this->assertFalse($body['data']['gateway']['connected']);
    }

    public function testStatusAliasEndpointWorksIdentically(): void
    {
        $this->setMockGatewayTransport([
            'status' => 'success',
            'data'   => [
                'connected' => true,
                'status'    => 'connected',
            ],
        ]);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/admin/whatsapp/status');

        $response->assertStatus(200);
        $body = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('success', $body['status'] ?? null);
    }

    public function testPairCodeValidatesPhoneNumber(): void
    {
        $empty = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/admin/pengaturan/whatsapp/pair-code', []);

        $empty->assertStatus(422);
        $bodyEmpty = json_decode((string) $empty->response()->getBody(), true);
        $this->assertStringContainsString('wajib diisi', (string) ($bodyEmpty['message'] ?? ''));

        $short = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/admin/pengaturan/whatsapp/pair-code', ['phone' => '123']);

        $short->assertStatus(422);
        $bodyShort = json_decode((string) $short->response()->getBody(), true);
        $this->assertStringContainsString('tidak valid', (string) ($bodyShort['message'] ?? ''));
    }

    public function testPairCodeReturnsGeneratedCodeOnSuccess(): void
    {
        $this->setMockGatewayTransport([
            'status'  => 'success',
            'message' => 'Kode pairing berhasil dibuat.',
            'data'    => [
                'pairing_code' => 'ABCD-1234',
                'phone'        => '6281234567890',
            ],
        ]);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/admin/pengaturan/whatsapp/pair-code', ['phone' => '081234567890']);

        $response->assertStatus(200);
        $body = json_decode((string) $response->response()->getBody(), true);

        $this->assertSame('success', $body['status'] ?? null);
        $this->assertSame('ABCD-1234', $body['data']['pairing_code'] ?? null);
        $this->assertSame('6281234567890', $body['data']['phone'] ?? null);
    }

    public function testPairCodeReturns422WhenGatewayFails(): void
    {
        $this->setMockGatewayTransport([
            'status'  => 'error',
            'message' => 'Gagal meminta pairing code dari WhatsApp gateway.',
        ], 500);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/admin/pengaturan/whatsapp/pair-code', ['phone' => '081234567890']);

        $response->assertStatus(422);
        $body = json_decode((string) $response->response()->getBody(), true);

        $this->assertSame('error', $body['status'] ?? null);
        $this->assertStringContainsString('Gagal meminta pairing code', (string) ($body['message'] ?? ''));
    }

    public function testLogoutDeviceReturnsSuccessPayload(): void
    {
        $this->setMockGatewayTransport([
            'status'  => 'success',
            'message' => 'Sesi WhatsApp telah diputus.',
            'data'    => [
                'connected' => false,
                'status'    => 'disconnected',
            ],
        ]);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/admin/pengaturan/whatsapp/logout');

        $response->assertStatus(200);
        $body = json_decode((string) $response->response()->getBody(), true);

        $this->assertSame('success', $body['status'] ?? null);
        $this->assertFalse($body['data']['gateway']['connected'] ?? true);
        $this->assertSame('disconnected', $body['data']['gateway']['status'] ?? null);
    }

    public function testLogoutReturns422WhenGatewayFails(): void
    {
        $this->setMockGatewayTransport([
            'status'  => 'error',
            'message' => 'Gateway error saat memutus sesi.',
        ], 500);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/admin/pengaturan/whatsapp/logout');

        $response->assertStatus(422);
        $body = json_decode((string) $response->response()->getBody(), true);

        $this->assertSame('error', $body['status'] ?? null);
        $this->assertStringContainsString('Gateway error', (string) ($body['message'] ?? ''));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function setMockGatewayTransport(array $payload, int $statusCode = 200): void
    {
        $config = new Otp();
        $config->baileysApiUrl = 'http://127.0.0.1:3001';
        $config->baileysApiKey = 'mock-key';

        $transport = new ApiAdminWhatsAppRecordingTransport(
            new HttpResponse($statusCode, json_encode($payload, JSON_THROW_ON_ERROR))
        );
        $provider = new BaileysProvider($transport, $config);
        AdminWhatsAppController::setMockProvider($provider);
    }

    private function seedIdentities(): void
    {
        $this->apiDb->table('users')->insert([
            'username' => 'dewan_user',
            'name'     => 'Anggota Dewan',
            'active'   => 1,
        ]);
        $memberId = (int) $this->apiDb->insertID();

        $this->apiDb->table('auth_groups_users')->insert([
            'user_id'    => $memberId,
            'group'      => 'anggota',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->issueToken($memberId, self::MEMBER_TOKEN);

        $this->apiDb->table('users')->insert([
            'username' => 'superadmin',
            'name'     => 'Super Administrator',
            'active'   => 1,
        ]);
        $adminId = (int) $this->apiDb->insertID();

        $this->apiDb->table('auth_groups_users')->insert([
            'user_id'    => $adminId,
            'group'      => 'superadmin',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->issueToken($adminId, self::ADMIN_TOKEN);
    }

    private function issueToken(int $userId, string $rawToken): void
    {
        $this->apiDb->table('auth_identities')->insert([
            'user_id'    => $userId,
            'type'       => 'access_token',
            'name'       => 'test-token',
            'secret'     => hash('sha256', $rawToken),
            'extra'      => serialize(['*']),
            'expires'    => date('Y-m-d H:i:s', time() + 86400),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createTables(): void
    {
        $this->apiForge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'username'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status_message' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'active'         => ['type' => 'INTEGER', 'default' => 0],
            'last_active'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('users');

        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'    => ['type' => 'INTEGER'],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_groups_users');

        $this->apiForge->addField([
            'id'           => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'      => ['type' => 'INTEGER'],
            'type'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'secret'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'secret2'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'expires'      => ['type' => 'DATETIME', 'null' => true],
            'extra'        => ['type' => 'TEXT', 'null' => true],
            'force_reset'  => ['type' => 'INTEGER', 'default' => 0],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_identities');

        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'id_type'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'identifier' => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_id'    => ['type' => 'INTEGER', 'null' => true],
            'date'       => ['type' => 'DATETIME'],
            'success'    => ['type' => 'INTEGER'],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_token_logins');

        $this->apiForge->addField([
            'id'            => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'jabatan'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fraksi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'komisi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_wa'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif'         => ['type' => 'INTEGER', 'default' => 1],
            'foto'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'user_id'       => ['type' => 'INTEGER', 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('anggota');
    }

    private function dropTables(): void
    {
        foreach ([
            'anggota',
            'auth_token_logins',
            'auth_identities',
            'auth_groups_users',
            'users',
        ] as $table) {
            $this->apiForge->dropTable($table, true);
        }
    }
}

final class ApiAdminWhatsAppRecordingTransport implements HttpTransportInterface
{
    public string $url = '';
    /** @var array<string, string> */
    public array $headers = [];
    /** @var array<string, mixed> */
    public array $payload = [];
    public int $timeoutSeconds = 0;
    public int $callCount = 0;

    public function __construct(private readonly HttpResponse $response)
    {
    }

    public function post(string $url, array $headers, array $fields, int $timeoutSeconds): HttpResponse
    {
        $this->callCount++;
        return $this->response;
    }

    public function postJson(string $url, array $headers, array $payload, int $timeoutSeconds): HttpResponse
    {
        $this->callCount++;
        $this->url = $url;
        $this->headers = $headers;
        $this->payload = $payload;
        $this->timeoutSeconds = $timeoutSeconds;

        return $this->response;
    }

    public function get(string $url, array $headers, int $timeoutSeconds): HttpResponse
    {
        $this->callCount++;
        $this->url = $url;
        $this->headers = $headers;
        $this->timeoutSeconds = $timeoutSeconds;

        return $this->response;
    }
}
