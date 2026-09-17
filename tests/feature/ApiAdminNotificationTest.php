<?php

use App\Libraries\Notification\NotificationService;
use App\Libraries\Otp\Providers\BaileysProvider;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian endpoint REST API Pusat Notifikasi Admin (/api/v1/admin/notifications).
 *
 * @internal
 */
final class ApiAdminNotificationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const MEMBER_TOKEN = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';
    private const ADMIN_TOKEN  = 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd';

    private BaseConnection $apiDb;
    private Forge $apiForge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian API notifikasi admin.');
        }

        $this->apiDb = Database::connect('tests');
        $this->apiForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedIdentities();
    }

    protected function tearDown(): void
    {
        if (isset($this->apiForge)) {
            $this->dropTables();
        }

        try {
            cache()->delete(BaileysProvider::OFFLINE_CACHE_KEY);
            cache()->delete(NotificationService::ALERTS_CACHE_KEY);
            cache()->delete(NotificationService::WA_STATUS_CACHE_KEY);
        } catch (\Throwable) {
        }

        parent::tearDown();
    }

    public function testEndpointRequiresAdminToken(): void
    {
        $this->get('/api/v1/admin/notifications')->assertStatus(401);

        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get('/api/v1/admin/notifications')
            ->assertStatus(401);
    }

    public function testMemberTokenReceivesForbidden403(): void
    {
        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get('/api/v1/admin/notifications')
            ->assertStatus(403);
    }

    public function testAdminTokenReturnsStructuredFeed(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/admin/notifications');

        $response->assertStatus(200);
        $body = json_decode((string) $response->response()->getBody(), true);

        $this->assertSame('success', $body['status'] ?? null);
        $this->assertArrayHasKey('data', $body);

        $data = $body['data'];
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('alerts', $data);
        $this->assertArrayHasKey('ai_tasks', $data);

        $this->assertArrayHasKey('unread_critical_count', $data['summary']);
        $this->assertArrayHasKey('warning_count', $data['summary']);
        $this->assertArrayHasKey('alerts_count', $data['summary']);
        $this->assertArrayHasKey('active_tasks_count', $data['summary']);
        $this->assertArrayHasKey('badge_tone', $data['summary']);
        $this->assertArrayHasKey('badge_count', $data['summary']);
        $this->assertIsArray($data['alerts']);
        $this->assertIsArray($data['ai_tasks']);
    }

    public function testAdminTokenReflectsWhatsAppAlert(): void
    {
        cache()->save(BaileysProvider::OFFLINE_CACHE_KEY, [
            'configured' => true,
            'connected'  => false,
            'status'     => 'offline',
            'phone'      => null,
            'name'       => null,
            'qr_url'     => null,
            'error'      => 'Nomor WhatsApp belum terhubung ke sistem.',
        ], 60);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/admin/notifications');

        $response->assertStatus(200);
        $body = json_decode((string) $response->response()->getBody(), true);

        $data = $body['data'];
        $this->assertGreaterThanOrEqual(1, $data['summary']['unread_critical_count']);
        $this->assertSame('danger', $data['summary']['badge_tone']);

        $alertIds = array_column($data['alerts'], 'id');
        $this->assertContains('wa-gateway-disconnected', $alertIds);
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
