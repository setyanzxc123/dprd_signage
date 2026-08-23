<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian endpoint API CRUD anggota DPRD: otorisasi bearer per grup,
 * paginasi/pencarian, normalisasi + keunikan no_wa, serta outcome
 * delete (fisik vs deaktivasi bila terkait unit rapat).
 *
 * @internal
 */
final class ApiAnggotaCrudTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const ADMIN_TOKEN = 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee';
    private const ANGGOTA_TOKEN = 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff';

    private BaseConnection $apiDb;
    private Forge $apiForge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian API anggota.');
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
        $this->get('/api/v1/anggota')->assertStatus(401);
        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get('/api/v1/anggota')
            ->assertStatus(401);
    }

    public function testAnggotaTokenIsForbidden(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ANGGOTA_TOKEN])
            ->get('/api/v1/anggota');

        $response->assertStatus(403);
        $this->assertSame('error', json_decode((string) $response->response()->getBody(), true)['status']);
    }

    public function testListPaginatesAndSearches(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/anggota');

        $response->assertOK();
        $bodyJson = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('success', $bodyJson['status']);
        $this->assertCount(3, $bodyJson['data']);
        $this->assertSame(3, $bodyJson['meta']['total']);
        // Urutan nama naik, sama seperti halaman web.
        $this->assertSame('Ani Rahmawati', $bodyJson['data'][0]['name']);
        $this->assertSame('Citra Lestari', $bodyJson['data'][2]['name']);

        $search = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/anggota?q=PDIP');
        $searchBody = json_decode((string) $search->response()->getBody(), true);
        $this->assertSame(1, $searchBody['meta']['total']);
        $this->assertSame('Budi Santoso', $searchBody['data'][0]['name']);

        $paged = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/anggota?per_page=2&page=2');
        $pagedBody = json_decode((string) $paged->response()->getBody(), true);
        $this->assertCount(1, $pagedBody['data']);
        $this->assertSame(2, $pagedBody['meta']['total_pages']);
    }

    public function testCrudFlowViaApi(): void
    {
        $created = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->post('/api/v1/anggota', [
                'name'    => 'Dewi Baru',
                'jabatan' => 'Anggota',
                'fraksi'  => 'Golongan Karya',
                'komisi'  => 'Komisi III',
                'no_wa'   => '081234567892',
                'aktif'   => true,
            ]);

        $created->assertStatus(201);
        $row = $this->apiDb->table('anggota')->where('name', 'Dewi Baru')->get()->getRowArray();
        $this->assertNotNull($row);
        $id = (int) $row['id'];
        // Nomor dinormalisasi: awalan 0/62 dibuang.
        $this->assertSame('81234567892', $row['no_wa']);
        $this->assertSame(1, (int) $row['aktif']);

        $invalidPayloads = [
            'duplikat no_wa'  => ['name' => 'Duplikat', 'fraksi' => 'PDIP', 'no_wa' => '81234567890'],
            'fraksi tak dikenal' => ['name' => 'Fraksi Oktan', 'fraksi' => 'Fraksi Oktan', 'no_wa' => '81234567899'],
            'no_wa invalid'   => ['name' => 'Salah Nomor', 'fraksi' => 'PDIP', 'no_wa' => '123'],
            'nama kosong'     => ['name' => '', 'fraksi' => 'PDIP', 'no_wa' => '81234567898'],
        ];

        foreach ($invalidPayloads as $payload) {
            $this
                ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
                ->withBodyFormat('json')
                ->post('/api/v1/anggota', $payload)
                ->assertStatus(422);
        }

        // Bool false dari JSON harus tersimpan sebagai nonaktif.
        $updated = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put("/api/v1/anggota/{$id}", [
                'name'   => 'Dewi Baru',
                'fraksi' => 'Golongan Karya',
                'no_wa'  => '6281234567892',
                'aktif'  => false,
            ]);
        $updated->assertOK();
        $row = $this->apiDb->table('anggota')->where('id', $id)->get()->getRowArray();
        $this->assertSame('81234567892', $row['no_wa']);
        $this->assertSame(0, (int) $row['aktif']);

        $shown = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get("/api/v1/anggota/{$id}");
        $shown->assertOK();
        $this->assertSame('Dewi Baru', json_decode((string) $shown->response()->getBody(), true)['data']['name']);

        $this->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/anggota/999')
            ->assertStatus(404);
        $this->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put('/api/v1/anggota/999', ['name' => 'X', 'fraksi' => 'PDIP', 'no_wa' => '81234567897'])
            ->assertStatus(404);

        $deleted = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->delete("/api/v1/anggota/{$id}");
        $deleted->assertOK();
        $this->assertSame('deleted', json_decode((string) $deleted->response()->getBody(), true)['outcome']);
        $this->assertSame(0, $this->apiDb->table('anggota')->where('id', $id)->countAllResults());
    }

    public function testDeleteDeactivatesMemberWithRelations(): void
    {
        $this->apiDb->table('anggota_unit_rapat')->insert([
            'anggota_id'    => 1,
            'unit_rapat_id' => 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->delete('/api/v1/anggota/1');

        $response->assertOK();
        $this->assertSame('deactivated', json_decode((string) $response->response()->getBody(), true)['outcome']);
        $row = $this->apiDb->table('anggota')->where('id', 1)->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame(0, (int) $row['aktif']);
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
            ['name' => 'Budi Santoso', 'jabatan' => 'Ketua Komisi', 'fraksi' => 'PDIP', 'komisi' => 'Komisi I', 'no_wa' => '81234567890', 'aktif' => 1],
            ['name' => 'Ani Rahmawati', 'jabatan' => 'Anggota', 'fraksi' => 'Demokrat', 'komisi' => 'Komisi II', 'no_wa' => '81234567891', 'aktif' => 1],
            ['name' => 'Citra Lestari', 'jabatan' => 'Anggota', 'fraksi' => 'Gerindra', 'komisi' => 'Komisi III', 'no_wa' => '81234567893', 'aktif' => 0],
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

        $this->apiForge->addField([
            'anggota_id'    => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->createTable('anggota_unit_rapat');
    }

    private function dropTables(): void
    {
        foreach (['anggota_unit_rapat', 'anggota', 'auth_token_logins', 'auth_identities', 'auth_groups_users', 'users'] as $table) {
            $this->apiForge->dropTable($table, true);
        }
    }
}
