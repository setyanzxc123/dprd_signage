<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian endpoint API CRUD kelompok peserta (unit rapat): otorisasi
 * bearer per grup, paginasi/pencarian, sinkronisasi keanggotaan via
 * anggota_unit_rapat, dan aturan delete = deaktivasi.
 *
 * @internal
 */
final class ApiUnitRapatCrudTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const ADMIN_TOKEN = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';
    private const ANGGOTA_TOKEN = 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd';

    private BaseConnection $apiDb;
    private Forge $apiForge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian API unit rapat.');
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

    public function testEndpointRequiresBearerToken(): void
    {
        $this->get('/api/v1/unit-rapat')->assertStatus(401);
        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get('/api/v1/unit-rapat')
            ->assertStatus(401);
    }

    public function testAnggotaTokenIsForbidden(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ANGGOTA_TOKEN])
            ->get('/api/v1/unit-rapat');

        $response->assertStatus(403);
        $this->assertSame('error', json_decode((string) $response->response()->getBody(), true)['status']);
    }

    public function testListPaginatesAndSearches(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/unit-rapat');

        $response->assertOK();
        $bodyJson = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('success', $bodyJson['status']);
        $this->assertCount(3, $bodyJson['data']);
        $this->assertSame(3, $bodyJson['meta']['total']);
        // Urutan mengikuti kolom urutan, sama seperti halaman web.
        $this->assertSame('Komisi I', $bodyJson['data'][0]['nama']);
        $this->assertSame('Badan Anggaran', $bodyJson['data'][2]['nama']);

        $search = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/unit-rapat?q=Komisi');
        $searchBody = json_decode((string) $search->response()->getBody(), true);
        $this->assertSame(2, $searchBody['meta']['total']);

        $paged = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/unit-rapat?per_page=2&page=2');
        $pagedBody = json_decode((string) $paged->response()->getBody(), true);
        $this->assertCount(1, $pagedBody['data']);
        $this->assertSame(2, $pagedBody['meta']['total_pages']);
    }

    public function testCrudFlowViaApi(): void
    {
        $created = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/unit-rapat', [
                'nama'               => 'Pansus APBD',
                'aktif'              => '1',
                'anggota_unit_rapat' => [1, 2],
            ]);

        $created->assertStatus(201);
        $createdBody = json_decode((string) $created->response()->getBody(), true);
        $this->assertSame([1, 2], $createdBody['anggota_ids']);

        $row = $this->apiDb->table('unit_rapat')->where('nama', 'Pansus APBD')->get()->getRowArray();
        $this->assertNotNull($row);
        $id = (int) $row['id'];
        $this->assertSame(2, $this->apiDb->table('anggota_unit_rapat')->where('unit_rapat_id', $id)->countAllResults());

        $invalid = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/unit-rapat', ['nama' => '']);
        $invalid->assertStatus(422);

        $updated = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put("/api/v1/unit-rapat/{$id}", [
                'nama'               => 'Pansus APBD Perubahan',
                'aktif'              => true,
                'anggota_unit_rapat' => [1],
            ]);
        $updated->assertOK();
        $this->assertSame('Pansus APBD Perubahan', $this->apiDb->table('unit_rapat')->where('id', $id)->get()->getRowArray()['nama']);
        $this->assertSame(1, $this->apiDb->table('anggota_unit_rapat')->where('unit_rapat_id', $id)->countAllResults());

        $duplicate = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put("/api/v1/unit-rapat/{$id}", ['nama' => 'Komisi I', 'aktif' => 1, 'anggota_unit_rapat' => [1]]);
        $duplicate->assertStatus(422);

        $shown = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get("/api/v1/unit-rapat/{$id}");
        $shown->assertOK();
        $shownBody = json_decode((string) $shown->response()->getBody(), true);
        $this->assertSame([1], $shownBody['anggota_ids']);

        $this->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/unit-rapat/999')
            ->assertStatus(404);

        $deleted = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->delete("/api/v1/unit-rapat/{$id}");
        $deleted->assertOK();
        $this->assertSame('deactivated', json_decode((string) $deleted->response()->getBody(), true)['outcome']);
        $remaining = $this->apiDb->table('unit_rapat')->where('id', $id)->get()->getRowArray();
        $this->assertNotNull($remaining);
        $this->assertSame(0, (int) $remaining['aktif']);
    }

    public function testActiveUnitRequiresValidMembers(): void
    {
        $noMembers = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->post('/api/v1/unit-rapat', ['nama' => 'Unit Aktif Kosong', 'aktif' => 1]);
        $noMembers->assertStatus(422);

        $inactiveMember = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->post('/api/v1/unit-rapat', ['nama' => 'Unit Anggota Nonaktif', 'aktif' => 1, 'anggota_unit_rapat' => [3]]);
        $inactiveMember->assertStatus(422);

        // Unit nonaktif boleh tanpa anggota — cocok untuk arsip.
        $inactiveUnit = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->post('/api/v1/unit-rapat', ['nama' => 'Unit Arsip', 'aktif' => 0]);
        $inactiveUnit->assertStatus(201);
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

        $this->apiDb->table('anggota')->insertBatch([
            ['name' => 'Anggota Satu', 'komisi' => 'Komisi I', 'aktif' => 1],
            ['name' => 'Anggota Dua', 'komisi' => 'Komisi II', 'aktif' => 1],
            ['name' => 'Anggota Nonaktif', 'komisi' => 'Komisi I', 'aktif' => 0],
        ]);

        $now = date('Y-m-d H:i:s');
        $this->apiDb->table('unit_rapat')->insertBatch([
            ['nama' => 'Komisi I', 'jenis' => 'komisi', 'aktif' => 1, 'urutan' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Komisi II', 'jenis' => 'komisi', 'aktif' => 1, 'urutan' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Badan Anggaran', 'jenis' => 'badan', 'aktif' => 1, 'urutan' => 50, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->apiDb->table('anggota_unit_rapat')->insert([
            'anggota_id'    => 1,
            'unit_rapat_id' => 1,
            'created_at'    => $now,
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
            'id'          => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'jabatan'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fraksi'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'komisi'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_wa'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif'       => ['type' => 'INTEGER', 'default' => 1],
            'foto'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'user_id'     => ['type' => 'INTEGER', 'null' => true],
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

        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'jenis'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'lainnya'],
            'aktif'      => ['type' => 'INTEGER', 'default' => 1],
            'urutan'     => ['type' => 'INTEGER', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('unit_rapat');

        $this->apiForge->addField([
            'anggota_id'    => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->createTable('anggota_unit_rapat');
    }

    private function dropTables(): void
    {
        foreach (['anggota_unit_rapat', 'unit_rapat', 'anggota', 'auth_token_logins', 'auth_identities', 'auth_groups_users', 'users'] as $table) {
            $this->apiForge->dropTable($table, true);
        }
    }
}
