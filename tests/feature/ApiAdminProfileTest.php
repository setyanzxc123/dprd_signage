<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian profil admin API (Fase 5): otorisasi bearer per grup,
 * pembacaan profil, pembaruan nama, dan matriks validasi ganti password
 * via AdminProfileService — identik aturan halaman web.
 *
 * @internal
 */
final class ApiAdminProfileTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const ADMIN_TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const ANGGOTA_TOKEN = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    private const CURRENT_PASSWORD = 'password-lama';

    private BaseConnection $apiDb;
    private Forge $apiForge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian API profil admin.');
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

        parent::tearDown();
    }

    public function testEndpointRequiresAdminToken(): void
    {
        $this->get('/api/v1/admin/profil')->assertStatus(401);
        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get('/api/v1/admin/profil')
            ->assertStatus(401);

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ANGGOTA_TOKEN])
            ->get('/api/v1/admin/profil')
            ->assertStatus(403);
        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ANGGOTA_TOKEN])
            ->withBodyFormat('json')
            ->put('/api/v1/admin/profil', ['name' => 'Nama Baru'])
            ->assertStatus(403);
    }

    public function testShowsCurrentUserProfile(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/admin/profil');

        $response->assertOK();
        $body = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('admin-api', $body['user']['username']);
        $this->assertSame('Admin API', $body['user']['name']);
        $this->assertContains('superadmin', $body['user']['groups']);
    }

    public function testUpdatesNameOnly(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put('/api/v1/admin/profil', ['name' => 'Admin Diubah']);

        $response->assertOK();
        $body = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('Profil berhasil diperbarui.', $body['message']);
        $this->assertSame('Admin Diubah', $body['user']['name']);
        $this->assertSame('Admin Diubah', $this->apiDb->table('users')->where('id', 1)->get()->getRowArray()['name']);

        // Tanpa kolom password, hash password tidak boleh berubah.
        $hash = $this->currentPasswordHash();
        $this->assertTrue(password_verify(self::CURRENT_PASSWORD, $hash));
    }

    public function testValidatesNameAndPasswordRules(): void
    {
        $shortName = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put('/api/v1/admin/profil', ['name' => 'ab']);
        $shortName->assertStatus(422);
        $this->assertSame(
            'Nama admin harus terdiri dari 3 sampai 100 karakter.',
            json_decode((string) $shortName->response()->getBody(), true)['message'],
        );

        $incompletePassword = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put('/api/v1/admin/profil', [
                'name'          => 'Admin API',
                'new_password'  => 'password-baru',
            ]);
        $incompletePassword->assertStatus(422);
        $this->assertSame(
            'Semua kolom password wajib diisi.',
            json_decode((string) $incompletePassword->response()->getBody(), true)['message'],
        );

        $wrongCurrent = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put('/api/v1/admin/profil', [
                'name'                     => 'Admin API',
                'current_password'         => 'salah-total',
                'new_password'             => 'password-baru',
                'new_password_confirmation' => 'password-baru',
            ]);
        $wrongCurrent->assertStatus(422);
        $this->assertSame(
            'Password saat ini tidak sesuai.',
            json_decode((string) $wrongCurrent->response()->getBody(), true)['message'],
        );

        $mismatchedConfirmation = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put('/api/v1/admin/profil', [
                'name'                      => 'Admin API',
                'current_password'          => self::CURRENT_PASSWORD,
                'new_password'              => 'password-baru',
                'new_password_confirmation' => 'beda-konfirmasi',
            ]);
        $mismatchedConfirmation->assertStatus(422);
        $this->assertSame(
            'Konfirmasi password baru tidak sesuai.',
            json_decode((string) $mismatchedConfirmation->response()->getBody(), true)['message'],
        );

        $samePassword = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put('/api/v1/admin/profil', [
                'name'                      => 'Admin API',
                'current_password'          => self::CURRENT_PASSWORD,
                'new_password'              => self::CURRENT_PASSWORD,
                'new_password_confirmation' => self::CURRENT_PASSWORD,
            ]);
        $samePassword->assertStatus(422);
        $this->assertSame(
            'Password baru harus berbeda dari password saat ini.',
            json_decode((string) $samePassword->response()->getBody(), true)['message'],
        );
    }

    public function testChangesPasswordWithCurrentPassword(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put('/api/v1/admin/profil', [
                'name'                      => 'Admin Baru',
                'current_password'          => self::CURRENT_PASSWORD,
                'new_password'              => 'password-baru',
                'new_password_confirmation' => 'password-baru',
            ]);

        $response->assertOK();
        $body = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('Profil dan password berhasil diperbarui.', $body['message']);

        $this->assertTrue(password_verify('password-baru', $this->currentPasswordHash()));
        $this->assertFalse(password_verify(self::CURRENT_PASSWORD, $this->currentPasswordHash()));
    }

    private function currentPasswordHash(): string
    {
        $identity = $this->apiDb->table('auth_identities')
            ->where('user_id', 1)
            ->where('type', 'email_password')
            ->get()
            ->getRowArray();

        return (string) ($identity['secret2'] ?? '');
    }

    /** Menerbitkan access token Shield untuk pengujian. */
    private function issueToken(int $userId, string $rawToken): void
    {
        $this->apiDb->table('auth_identities')->insert([
            'user_id'    => $userId,
            'type'       => 'access_token',
            'name'       => 'test',
            'secret'     => hash('sha256', $rawToken),
            'extra'      => serialize(['*']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function seedIdentities(): void
    {
        $this->apiDb->table('users')->insert([
            'username' => 'admin-api',
            'name'     => 'Admin API',
            'active'   => 1,
        ]);
        $adminId = (int) $this->apiDb->insertID();
        $this->apiDb->table('auth_groups_users')->insert([
            'user_id' => $adminId, 'group' => 'superadmin', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->issueToken($adminId, self::ADMIN_TOKEN);
        $this->apiDb->table('auth_identities')->insert([
            'user_id'    => $adminId,
            'type'       => 'email_password',
            'name'       => 'email',
            'secret'     => 'admin@api.test',
            'secret2'    => password_hash(self::CURRENT_PASSWORD, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->apiDb->table('users')->insert([
            'username' => 'anggota-api',
            'name'     => 'Anggota API',
            'active'   => 1,
        ]);
        $anggotaId = (int) $this->apiDb->insertID();
        $this->apiDb->table('auth_groups_users')->insert([
            'user_id' => $anggotaId, 'group' => 'anggota', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->issueToken($anggotaId, self::ANGGOTA_TOKEN);
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

        // Shield mencatat percobaan login token (termasuk yang gagal).
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
    }

    private function dropTables(): void
    {
        foreach (['auth_token_logins', 'anggota', 'auth_identities', 'auth_groups_users', 'users'] as $table) {
            $this->apiForge->dropTable($table, true);
        }
    }
}
