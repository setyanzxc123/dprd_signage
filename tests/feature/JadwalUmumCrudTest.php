<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class JadwalUmumCrudTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $testDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian CRUD database.');
        }

        $this->testDb = Database::connect('tests');
        $this->forge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedOptions();
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->dropTables();
        }
        parent::tearDown();
    }

    public function testRoutesRequireAdminAuthentication(): void
    {
        $loginUrl = base_url('login?akses=admin');
        $this->get('/admin/jadwal-umum')->assertRedirectTo($loginUrl);
        $this->get('/admin/jadwal-umum/create')->assertRedirectTo($loginUrl);
    }

    public function testFormUsesUnifiedOptionalDimensions(): void
    {
        $response = $this->adminGet('/admin/jadwal-umum/create');
        $response->assertOK();
        $body = $response->response()->getBody();

        $this->assertStringContainsString('name="pihak_eksternal"', $body);
        $this->assertStringContainsString('name="target_unit_rapat[]"', $body);
        $this->assertStringContainsString('data-require-targets="false"', $body);
        $this->assertStringContainsString('name="is_publik"', $body);
        $this->assertStringNotContainsString('name="lingkup"', $body);
        $this->assertStringNotContainsString('name="kategori"', $body);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*id="is_publik"[^>]*checked/s', $body);
    }

    public function testStoresMixedAudienceScheduleAndUnitRelation(): void
    {
        $response = $this->adminPost('/admin/jadwal-umum/store', [
            'judul'             => 'Audiensi Forum Pemuda bersama Komisi I',
            'tanggal'           => '2099-08-12',
            'waktu_mulai'       => '09:00',
            'waktu_selesai'     => '10:30',
            'lokasi_mode'       => 'ruangan',
            'ruangan_id'        => '1',
            'pihak_eksternal'   => 'Forum Pemuda Sulawesi Tengah',
            'target_unit_rapat' => ['1'],
            'is_publik'         => '1',
            'keterangan'        => 'Penerimaan aspirasi pemuda.',
        ]);

        $response->assertStatus(303);
        $row = $this->testDb->table('jadwal_umum')->get()->getRowArray();
        $this->assertSame('Forum Pemuda Sulawesi Tengah', $row['pihak_eksternal']);
        $this->assertSame(1, (int) $row['ruangan_id']);
        $this->assertSame(1, (int) $row['is_publik']);
        $pivot = $this->testDb->table('jadwal_umum_unit_rapat')->get()->getRowArray();
        $this->assertSame(1, (int) $pivot['unit_rapat_id']);
    }

    public function testStoresAllDayScheduleWithoutUnitsAtOtherLocation(): void
    {
        $response = $this->adminPost('/admin/jadwal-umum/store', [
            'judul'           => 'Kegiatan sosial masyarakat',
            'tanggal'         => '2099-08-13',
            'waktu_mulai'     => '',
            'waktu_selesai'   => '',
            'lokasi_mode'     => 'lainnya',
            'lokasi_lainnya'  => 'Lapangan Vatulemo',
            'pihak_eksternal' => 'Komunitas Masyarakat',
        ]);

        $response->assertStatus(303);
        $row = $this->testDb->table('jadwal_umum')->get()->getRowArray();
        $this->assertNull($row['waktu_mulai']);
        $this->assertNull($row['waktu_selesai']);
        $this->assertSame('Lapangan Vatulemo', $row['lokasi_lainnya']);
        $this->assertSame(0, $this->testDb->table('jadwal_umum_unit_rapat')->countAllResults());
    }

    public function testRejectsRoomWithoutCompleteTimesAndUnitWithoutMembers(): void
    {
        $base = [
            'judul'         => 'Kegiatan pengujian',
            'tanggal'       => '2099-08-12',
            'lokasi_mode'   => 'ruangan',
            'ruangan_id'    => '1',
            'waktu_mulai'   => '09:00',
            'waktu_selesai' => '',
        ];

        $this->adminPost('/admin/jadwal-umum/store', $base)->assertStatus(422);
        $this->adminPost('/admin/jadwal-umum/store', [
            ...$base,
            'waktu_selesai'     => '10:00',
            'target_unit_rapat' => ['2'],
        ])->assertStatus(422);
        $this->assertSame(0, $this->testDb->table('jadwal_umum')->countAllResults());
    }

    public function testRejectsRoomConflictAndAllowsUpdateOfSameRecord(): void
    {
        $this->testDb->table('jadwal_umum')->insert([
            'judul'          => 'Jadwal awal',
            'tanggal'        => '2099-08-12',
            'waktu_mulai'    => '09:00:00',
            'waktu_selesai'  => '10:00:00',
            'ruangan_id'     => 1,
            'is_publik'      => 0,
        ]);

        $payload = [
            'judul'         => 'Jadwal bentrok',
            'tanggal'       => '2099-08-12',
            'waktu_mulai'   => '09:30',
            'waktu_selesai' => '10:30',
            'lokasi_mode'   => 'ruangan',
            'ruangan_id'    => '1',
        ];
        $this->adminPost('/admin/jadwal-umum/store', $payload)->assertStatus(422);

        $this->adminPost('/admin/jadwal-umum/1/update', [
            ...$payload,
            'judul'         => 'Jadwal awal diperbarui',
            'waktu_mulai'   => '09:00',
            'waktu_selesai' => '10:00',
        ])->assertStatus(303);
        $this->assertSame(1, $this->testDb->table('jadwal_umum')->countAllResults());
    }

    public function testRejectsRoomConflictWithScheduledBanmusMeeting(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'auto_increment' => true],
            'jenis_agenda'  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'rapat'],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'tanggal'       => ['type' => 'DATE'],
            'jam_mulai'     => ['type' => 'TIME'],
            'jam_selesai'   => ['type' => 'TIME'],
            'ruangan_id'    => ['type' => 'INTEGER', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal_banmus');
        $this->testDb->table('jadwal_banmus')->insert([
            'jenis_agenda' => 'rapat',
            'status'       => 'menunggu',
            'tanggal'      => '2099-08-12',
            'jam_mulai'    => '09:00:00',
            'jam_selesai'  => '10:00:00',
            'ruangan_id'   => 1,
        ]);

        $response = $this->adminPost('/admin/jadwal-umum/store', [
            'judul'         => 'Jadwal bentrok Banmus',
            'tanggal'       => '2099-08-12',
            'waktu_mulai'   => '09:30',
            'waktu_selesai' => '10:30',
            'lokasi_mode'   => 'ruangan',
            'ruangan_id'    => '1',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, $this->testDb->table('jadwal_umum')->countAllResults());
    }

    public function testIndexAndDeleteUseUnifiedModule(): void
    {
        $this->testDb->table('jadwal_umum')->insert([
            'judul'           => 'Kunjungan organisasi',
            'tanggal'         => '2099-08-14',
            'lokasi_lainnya'  => 'Gedung DPRD',
            'pihak_eksternal' => 'Organisasi Pengujian',
            'is_publik'       => 0,
        ]);

        $response = $this->adminGet('/admin/jadwal-umum');
        $response->assertOK();
        $body = $response->response()->getBody();
        $this->assertStringContainsString('Kunjungan organisasi', $body);
        $this->assertStringContainsString('Tanpa kelompok peserta khusus', $body);
        $this->assertStringContainsString('data-admin-datatable', $body);

        $this->adminPost('/admin/jadwal-umum/1/delete', [])->assertStatus(303);
        $this->assertSame(0, $this->testDb->table('jadwal_umum')->countAllResults());
    }

    private function adminGet(string $path)
    {
        return $this->withSession(['auth_user' => $this->adminSession()])->get($path);
    }

    private function adminPost(string $path, array $payload)
    {
        return $this->withSession(['auth_user' => $this->adminSession()])->post($path, [
            csrf_token() => csrf_hash(),
            ...$payload,
        ]);
    }

    private function adminSession(): array
    {
        return [
            'id'       => 1,
            'name'     => 'Admin Pengujian',
            'username' => 'admin-test',
            'role'     => 'superadmin',
        ];
    }

    private function createTables(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'kapasitas'  => ['type' => 'INTEGER', 'default' => 0],
            'tersedia'   => ['type' => 'INTEGER', 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('ruangan');

        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'aktif'      => ['type' => 'INTEGER', 'default' => 1],
            'urutan'     => ['type' => 'INTEGER', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('unit_rapat');

        $this->forge->addField([
            'id'    => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'  => ['type' => 'VARCHAR', 'constraint' => 150],
            'aktif' => ['type' => 'INTEGER', 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('anggota');

        $this->forge->addField([
            'anggota_id'    => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
        ]);
        $this->forge->addPrimaryKey(['anggota_id', 'unit_rapat_id']);
        $this->forge->createTable('anggota_unit_rapat');

        $this->forge->addField([
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
            'materi_url'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'materi_akses'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'stream_url'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'stream_akses'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'undangan_file'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'undangan_nama_asli' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal_umum');

        $this->forge->addField([
            'jadwal_umum_id' => ['type' => 'INTEGER'],
            'unit_rapat_id'  => ['type' => 'INTEGER'],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey(['jadwal_umum_id', 'unit_rapat_id']);
        $this->forge->createTable('jadwal_umum_unit_rapat');
    }

    private function seedOptions(): void
    {
        $this->testDb->table('ruangan')->insert([
            'name' => 'Ruang Rapat Utama', 'kapasitas' => 50, 'tersedia' => 1,
        ]);
        $this->testDb->table('unit_rapat')->insertBatch([
            ['nama' => 'Komisi I', 'aktif' => 1, 'urutan' => 1],
            ['nama' => 'Komisi II', 'aktif' => 1, 'urutan' => 2],
        ]);
        $this->testDb->table('anggota')->insert([
            'name' => 'Anggota Pengujian', 'aktif' => 1,
        ]);
        $this->testDb->table('anggota_unit_rapat')->insert([
            'anggota_id' => 1, 'unit_rapat_id' => 1,
        ]);
    }

    private function dropTables(): void
    {
        foreach ([
            'jadwal_banmus', 'jadwal', 'jadwal_umum_unit_rapat', 'jadwal_umum',
            'anggota_unit_rapat', 'anggota', 'unit_rapat', 'ruangan',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
