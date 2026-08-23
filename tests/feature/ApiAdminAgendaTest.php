<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian kalender agenda terpadu API admin (Fase 5): otorisasi bearer
 * per grup, gabungan dua sumber + penandaan konflik via AgendaWorkspaceService
 * (identik halaman web), serta perilaku filter dan fallback bulan.
 *
 * @internal
 */
final class ApiAdminAgendaTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const ADMIN_TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const ANGGOTA_TOKEN = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    private BaseConnection $apiDb;
    private Forge $apiForge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian API agenda admin.');
        }

        $this->apiDb = Database::connect('tests');
        $this->apiForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedIdentities();
        $this->seedSchedules();
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
        $this->get('/api/v1/admin/agenda')->assertStatus(401);
        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get('/api/v1/admin/agenda')
            ->assertStatus(401);

        $forbidden = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ANGGOTA_TOKEN])
            ->get('/api/v1/admin/agenda');
        $forbidden->assertStatus(403);
        $this->assertSame('error', json_decode((string) $forbidden->response()->getBody(), true)['status']);
    }

    public function testReturnsCombinedMonthAgendaWithConflictMarking(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/admin/agenda?month=2099-08');

        $response->assertOK();
        $body = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('2099-08', $body['month']);
        $this->assertSame(['total' => 2, 'banmus' => 1, 'jadwal_umum' => 1, 'conflicts' => 2], $body['counts']);

        $titles = array_column($body['data'], 'judul');
        $this->assertContains('Audiensi umum', $titles);
        $this->assertContains('Banmus terjadwal', $titles);
        $this->assertNotContains('Proyeksi Banmus', $titles);
        $this->assertNotContains('Reses terjadwal', $titles);

        foreach ($body['data'] as $agenda) {
            $this->assertArrayNotHasKey('edit_url', $agenda);
            $this->assertSame([1], $agenda['unit_ids']);
            $this->assertSame(['Pimpinan DPRD'], $agenda['units']);
            $this->assertTrue($agenda['has_conflict']);
            $this->assertNotEmpty($agenda['conflicts']);
        }

        $this->assertArrayHasKey('sources', $body['options']);
        $this->assertArrayHasKey('publications', $body['options']);
    }

    public function testAppliesWorkspaceFilters(): void
    {
        $bySource = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/admin/agenda?month=2099-08&source=jadwal_umum');
        $bySourceBody = json_decode((string) $bySource->response()->getBody(), true);
        $this->assertCount(1, $bySourceBody['data']);
        $this->assertSame('Audiensi umum', $bySourceBody['data'][0]['judul']);

        $byPublication = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/admin/agenda?month=2099-08&publikasi=publik');
        $byPublicationBody = json_decode((string) $byPublication->response()->getBody(), true);
        $this->assertCount(1, $byPublicationBody['data']);
        $this->assertSame('Banmus terjadwal', $byPublicationBody['data'][0]['judul']);
        $this->assertSame('publik', $byPublicationBody['filters']['publikasi']);

        $byUnit = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/admin/agenda?month=2099-08&unit=1');
        $this->assertCount(2, json_decode((string) $byUnit->response()->getBody(), true)['data']);
    }

    public function testInvalidMonthFallsBackToCurrentMonth(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/admin/agenda?month=bukan-bulan');

        $response->assertOK();
        $body = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame(date('Y-m'), $body['month']);
        $this->assertSame(
            ['total' => 0, 'banmus' => 0, 'jadwal_umum' => 0, 'conflicts' => 0],
            $body['counts'],
        );
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
    }

    private function seedSchedules(): void
    {
        $this->apiDb->table('ruangan')->insert(['name' => 'Ruang Rapat Utama', 'tersedia' => 1]);
        $this->apiDb->table('unit_rapat')->insert([
            'nama'   => 'Pimpinan DPRD',
            'aktif'  => 1,
            'urutan' => 1,
        ]);

        $this->apiDb->table('jadwal_umum')->insert([
            'judul'           => 'Audiensi umum',
            'tanggal'         => '2099-08-12',
            'waktu_mulai'     => '09:00:00',
            'waktu_selesai'   => '10:00:00',
            'ruangan_id'      => 1,
            'pihak_eksternal' => 'Pemerintah Kabupaten',
            'is_publik'       => 0,
        ]);
        $this->apiDb->table('jadwal_umum_unit_rapat')->insert([
            'jadwal_umum_id' => 1,
            'unit_rapat_id'  => 1,
        ]);

        $this->apiDb->table('dokumen_banmus')->insert(['is_publik' => 1]);
        $this->apiDb->table('jadwal_banmus')->insertBatch([
            [
                'dokumen_banmus_id' => 1,
                'agenda'            => 'Banmus terjadwal',
                'jenis_agenda'      => 'rapat',
                'tanggal'           => '2099-08-12',
                'jam_mulai'         => '09:30:00',
                'jam_selesai'       => '10:30:00',
                'ruangan_id'        => 1,
                'lokasi_lainnya'    => null,
                'status'            => 'menunggu',
                'publikasi'         => 'publik',
            ],
            [
                'dokumen_banmus_id' => 1,
                'agenda'            => 'Proyeksi Banmus',
                'jenis_agenda'      => 'rapat',
                'tanggal'           => null,
                'jam_mulai'         => null,
                'jam_selesai'       => null,
                'ruangan_id'        => null,
                'lokasi_lainnya'    => null,
                'status'            => 'proyeksi',
                'publikasi'         => 'internal',
            ],
            [
                'dokumen_banmus_id' => 1,
                'agenda'            => 'Reses terjadwal',
                'jenis_agenda'      => 'non_rapat',
                'tanggal'           => '2099-08-13',
                'jam_mulai'         => '09:00:00',
                'jam_selesai'       => '10:00:00',
                'ruangan_id'        => null,
                'lokasi_lainnya'    => 'Daerah pemilihan',
                'status'            => 'menunggu',
                'publikasi'         => 'publik',
            ],
        ]);
        $this->apiDb->table('jadwal_banmus_unit_rapat')->insert([
            'jadwal_banmus_id' => 1,
            'unit_rapat_id'    => 1,
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

        $this->apiForge->addField([
            'id'       => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'tersedia' => ['type' => 'INTEGER', 'default' => 1],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('ruangan');

        $this->apiForge->addField([
            'id'      => ['type' => 'INTEGER', 'auto_increment' => true],
            'nama'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'aktif'   => ['type' => 'INTEGER', 'default' => 1],
            'urutan'  => ['type' => 'INTEGER', 'default' => 0],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('unit_rapat');

        $this->apiForge->addField([
            'id'              => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal'         => ['type' => 'DATE'],
            'waktu_mulai'     => ['type' => 'TIME', 'null' => true],
            'waktu_selesai'   => ['type' => 'TIME', 'null' => true],
            'ruangan_id'      => ['type' => 'INTEGER', 'null' => true],
            'lokasi_lainnya'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pihak_eksternal' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_publik'       => ['type' => 'INTEGER', 'default' => 0],
            'keterangan'      => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('jadwal_umum');

        $this->apiForge->addField([
            'jadwal_umum_id' => ['type' => 'INTEGER'],
            'unit_rapat_id'  => ['type' => 'INTEGER'],
        ]);
        $this->apiForge->createTable('jadwal_umum_unit_rapat');

        $this->apiForge->addField([
            'id'        => ['type' => 'INTEGER', 'auto_increment' => true],
            'is_publik' => ['type' => 'INTEGER', 'default' => 0],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('dokumen_banmus');

        $this->apiForge->addField([
            'id'                => ['type' => 'INTEGER', 'auto_increment' => true],
            'dokumen_banmus_id' => ['type' => 'INTEGER'],
            'agenda'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'jenis_agenda'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'rapat'],
            'catatan'           => ['type' => 'TEXT', 'null' => true],
            'tanggal'           => ['type' => 'DATE', 'null' => true],
            'jam_mulai'         => ['type' => 'TIME', 'null' => true],
            'jam_selesai'       => ['type' => 'TIME', 'null' => true],
            'ruangan_id'        => ['type' => 'INTEGER', 'null' => true],
            'lokasi_lainnya'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'publikasi'         => ['type' => 'VARCHAR', 'constraint' => 20],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('jadwal_banmus');

        $this->apiForge->addField([
            'jadwal_banmus_id' => ['type' => 'INTEGER'],
            'unit_rapat_id'    => ['type' => 'INTEGER'],
        ]);
        $this->apiForge->createTable('jadwal_banmus_unit_rapat');
    }

    private function dropTables(): void
    {
        foreach ([
            'jadwal_banmus_unit_rapat',
            'jadwal_banmus',
            'dokumen_banmus',
            'jadwal_umum_unit_rapat',
            'jadwal_umum',
            'unit_rapat',
            'ruangan',
            'auth_token_logins',
            'anggota',
            'auth_identities',
            'auth_groups_users',
            'users',
        ] as $table) {
            $this->apiForge->dropTable($table, true);
        }
    }
}
