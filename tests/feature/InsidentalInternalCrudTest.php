<?php

use App\Libraries\Schedule\Persistence\DatabaseScheduleReadRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class InsidentalInternalCrudTest extends CIUnitTestCase
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

    public function testCreateFormIsScopedAndDefaultsToInternalPublication(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/jadwal/create');

        $response->assertOK();
        $body = $response->response()->getBody();
        $this->assertStringContainsString('name="tanggal" type="date"', $body);
        $this->assertStringContainsString('name="waktu_mulai" type="time"', $body);
        $this->assertStringContainsString('Default internal', $body);
        $this->assertStringNotContainsString('name="jenis"', $body);
        $this->assertStringContainsString('name="materi_url"', $body);
        $this->assertStringContainsString('name="materi_akses"', $body);
        $this->assertStringContainsString('name="stream_url"', $body);
        $this->assertStringContainsString('name="stream_akses"', $body);
    }

    public function testStoreForcesInsidentalSourceAndInternalPublication(): void
    {
        $response = $this->postSchedule([
            'judul'               => 'Rapat pimpinan mendadak',
            'tanggal'             => '2099-08-12',
            'waktu_mulai'         => '09:00',
            'waktu_selesai'       => '10:00',
            'lokasi_mode'         => 'ruangan',
            'ruangan_id'          => '1',
            'target_unit_rapat'   => ['1'],
            'jenis'               => 'reguler',
            'keterangan'          => 'Koordinasi internal',
        ]);

        $response->assertStatus(303);
        $row = $this->testDb->table('jadwal')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame('insidental', $row['jenis']);
        $this->assertSame(0, (int) $row['is_publik']);
        $this->assertSame('menunggu', $row['status']);
        $this->assertSame(1, $this->testDb->table('jadwal_unit_rapat')->countAllResults());
    }

    public function testEditUsesTheSameStandardForm(): void
    {
        $this->insertSchedule('Agenda untuk diedit', '2099-08-12', 'insidental');
        $id = (int) $this->testDb->insertID();

        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get("/admin/jadwal/{$id}/edit");

        $response->assertOK();
        $body = $response->response()->getBody();
        $this->assertStringContainsString('Agenda untuk diedit', $body);
        $this->assertStringContainsString('name="tanggal" type="date"', $body);
        $this->assertStringContainsString('Simpan Perubahan', $body);
        $this->assertStringNotContainsString('form-actions-sticky', $body);
    }

    public function testCrudCannotManageNonInsidentalRows(): void
    {
        $this->insertSchedule('Agenda reguler lama', '2099-08-12', 'reguler');
        $id = (int) $this->testDb->insertID();

        $this->withSession(['auth_user' => $this->adminSession()])
            ->get("/admin/jadwal/{$id}/edit")
            ->assertRedirectTo(base_url('admin/jadwal'));

        $this->withSession(['auth_user' => $this->adminSession()])
            ->post("/admin/jadwal/{$id}/delete", [csrf_token() => csrf_hash()])
            ->assertRedirectTo(base_url('admin/jadwal'));

        $this->assertNotNull($this->testDb->table('jadwal')->where('id', $id)->get()->getRowArray());
    }

    public function testStoreRejectsInvalidTimeAndMissingLocation(): void
    {
        $invalidTime = $this->postSchedule([
            'judul'             => 'Rapat dengan waktu salah',
            'tanggal'           => '2099-08-12',
            'waktu_mulai'       => '10:00',
            'waktu_selesai'     => '09:00',
            'lokasi_mode'       => 'ruangan',
            'ruangan_id'        => '1',
            'target_unit_rapat' => ['1'],
        ]);
        $invalidTime->assertStatus(422);

        $missingStartTime = $this->postSchedule([
            'judul'             => 'Rapat tanpa jam mulai',
            'tanggal'           => '2099-08-12',
            'waktu_mulai'       => '',
            'waktu_selesai'     => '10:00',
            'lokasi_mode'       => 'ruangan',
            'ruangan_id'        => '1',
            'target_unit_rapat' => ['1'],
        ]);
        $missingStartTime->assertStatus(422);

        $invalidDate = $this->postSchedule([
            'judul'             => 'Rapat dengan tanggal salah',
            'tanggal'           => '2099-02-31',
            'waktu_mulai'       => '09:00',
            'waktu_selesai'     => '10:00',
            'lokasi_mode'       => 'ruangan',
            'ruangan_id'        => '1',
            'target_unit_rapat' => ['1'],
        ]);
        $invalidDate->assertStatus(422);

        $missingLocation = $this->postSchedule([
            'judul'             => 'Rapat tanpa lokasi',
            'tanggal'           => '2099-08-12',
            'waktu_mulai'       => '09:00',
            'waktu_selesai'     => '10:00',
            'lokasi_mode'       => 'lainnya',
            'lokasi_lainnya'    => '',
            'target_unit_rapat' => ['1'],
        ]);
        $missingLocation->assertStatus(422);

        $this->assertSame(0, $this->testDb->table('jadwal')->countAllResults());
    }

    public function testPublicPublicationMustBeExplicit(): void
    {
        $this->postSchedule([
            'judul'             => 'Agenda internal terbuka',
            'tanggal'           => '2099-08-12',
            'waktu_mulai'       => '09:00',
            'waktu_selesai'     => '10:00',
            'lokasi_mode'       => 'ruangan',
            'ruangan_id'        => '1',
            'target_unit_rapat' => ['1'],
            'is_publik'         => '1',
        ])->assertStatus(303);

        $row = $this->testDb->table('jadwal')->get()->getRowArray();
        $this->assertSame(1, (int) $row['is_publik']);
        $this->assertSame('insidental', $row['jenis']);
    }

    public function testCombinedRepositoryUsesInsidentalSourceAndFiltersInternalRows(): void
    {
        $this->insertSchedule('Agenda internal', '2099-08-12', 'insidental');
        $this->testDb->table('jadwal')->insert([
            'judul'          => 'Agenda publik',
            'tanggal'        => '2099-08-12',
            'waktu_mulai'    => '11:00:00',
            'waktu_selesai'  => '12:00:00',
            'ruangan_id'     => 1,
            'status'         => 'menunggu',
            'jenis'          => 'insidental',
            'is_publik'      => 1,
        ]);

        $repository = new DatabaseScheduleReadRepository($this->testDb);
        $memberRows = $repository->findSchedules(false, '2099-08-12', null, null);
        $publicRows = $repository->findSchedules(true, '2099-08-12', null, null);

        $this->assertCount(2, $memberRows);
        $this->assertSame('insidental_internal', $memberRows[0]['source']);
        $this->assertSame('internal', $memberRows[0]['lingkup']);
        $this->assertCount(1, $publicRows);
        $this->assertSame('Agenda publik', $publicRows[0]['judul']);
    }

    public function testStoreRejectsRoomConflict(): void
    {
        $this->insertSchedule('Agenda yang sudah ada', '2099-08-12', 'insidental');

        $response = $this->postSchedule([
            'judul'             => 'Agenda berbenturan',
            'tanggal'           => '2099-08-12',
            'waktu_mulai'       => '09:30',
            'waktu_selesai'     => '10:30',
            'lokasi_mode'       => 'ruangan',
            'ruangan_id'        => '1',
            'target_unit_rapat' => ['1'],
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, $this->testDb->table('jadwal')->countAllResults());
    }

    public function testStoreRejectsRoomConflictWithScheduledBanmusItem(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INTEGER', 'auto_increment' => true],
            'tanggal'     => ['type' => 'DATE'],
            'jam_mulai'   => ['type' => 'TIME'],
            'jam_selesai' => ['type' => 'TIME'],
            'ruangan_id'  => ['type' => 'INTEGER', 'null' => true],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal_banmus');
        $this->testDb->table('jadwal_banmus')->insert([
            'tanggal'     => '2099-08-12',
            'jam_mulai'   => '09:00:00',
            'jam_selesai' => '10:00:00',
            'ruangan_id'  => 1,
            'status'      => 'menunggu',
        ]);

        $response = $this->postSchedule([
            'judul'             => 'Insidental berbenturan dengan Banmus',
            'tanggal'           => '2099-08-12',
            'waktu_mulai'       => '09:30',
            'waktu_selesai'     => '10:30',
            'lokasi_mode'       => 'ruangan',
            'ruangan_id'        => '1',
            'target_unit_rapat' => ['1'],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, $this->testDb->table('jadwal')->countAllResults());
    }

    public function testFinishedScheduleReturnsToWaitingWhenRescheduled(): void
    {
        $this->insertSchedule('Agenda lama', '2020-01-01', 'insidental');
        $id = (int) $this->testDb->insertID();

        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post("/admin/jadwal/{$id}/update", [
                csrf_token()          => csrf_hash(),
                'judul'               => 'Agenda dijadwalkan ulang',
                'tanggal'             => '2099-08-12',
                'waktu_mulai'         => '09:00',
                'waktu_selesai'       => '10:00',
                'lokasi_mode'         => 'ruangan',
                'ruangan_id'          => '1',
                'target_unit_rapat'   => ['1'],
            ]);

        $response->assertStatus(303);
        $row = $this->testDb->table('jadwal')->where('id', $id)->get()->getRowArray();
        $this->assertSame('2099-08-12', $row['tanggal']);
        $this->assertSame('menunggu', $row['status']);
    }

    public function testIndexUsesDataTableWithoutFilterFormAndHidesOtherSources(): void
    {
        $this->insertSchedule('Agenda pertama', '2099-08-12', 'insidental');
        $this->insertSchedule('Agenda kedua', '2099-09-12', 'insidental');
        $this->insertSchedule('Agenda reguler lama', '2099-08-12', 'reguler');

        $body = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/jadwal')
            ->response()
            ->getBody();

        $this->assertStringContainsString('Agenda pertama', $body);
        $this->assertStringContainsString('Agenda kedua', $body);
        $this->assertStringNotContainsString('Agenda reguler lama', $body);
        $this->assertStringContainsString('data-admin-datatable', $body);
        $this->assertStringContainsString('data-dt-col-filters=', $body);
        $this->assertStringNotContainsString('name="tanggal_mulai"', $body);
        $this->assertStringNotContainsString('name="tanggal_selesai"', $body);
        $this->assertStringNotContainsString('<form method="get"', $body);
    }

    private function postSchedule(array $payload)
    {
        return $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post('/admin/jadwal/store', [
                csrf_token() => csrf_hash(),
                ...$payload,
            ]);
    }

    private function insertSchedule(string $judul, string $tanggal, string $jenis): void
    {
        $this->testDb->table('jadwal')->insert([
            'judul'          => $judul,
            'tanggal'        => $tanggal,
            'waktu_mulai'    => '09:00:00',
            'waktu_selesai'  => '10:00:00',
            'ruangan_id'     => 1,
            'status'         => 'menunggu',
            'jenis'          => $jenis,
            'is_publik'      => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
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
            'id'     => ['type' => 'INTEGER', 'auto_increment' => true],
            'nama'   => ['type' => 'VARCHAR', 'constraint' => 150],
            'aktif'  => ['type' => 'INTEGER', 'default' => 1],
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
            'id'               => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'keterangan'       => ['type' => 'TEXT', 'null' => true],
            'tanggal'          => ['type' => 'DATE'],
            'waktu_mulai'      => ['type' => 'TIME'],
            'waktu_selesai'    => ['type' => 'TIME'],
            'ruangan_id'       => ['type' => 'INTEGER', 'null' => true],
            'lokasi_lainnya'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'menunggu'],
            'materi_url'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'materi_akses'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'peserta'],
            'stream_url'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'stream_akses'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'anggota'],
            'is_publik'        => ['type' => 'INTEGER', 'default' => 0],
            'jenis'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'insidental'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal');

        $this->forge->addField([
            'jadwal_id'     => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey(['jadwal_id', 'unit_rapat_id']);
        $this->forge->createTable('jadwal_unit_rapat');
    }

    private function seedOptions(): void
    {
        $this->testDb->table('ruangan')->insert([
            'name'      => 'Ruang Rapat Utama',
            'kapasitas' => 50,
            'tersedia'  => 1,
        ]);
        $this->testDb->table('unit_rapat')->insert([
            'nama'   => 'Pimpinan DPRD',
            'aktif'  => 1,
            'urutan' => 1,
        ]);
        $this->testDb->table('anggota')->insert([
            'nama'  => 'Anggota Pengujian',
            'aktif' => 1,
        ]);
        $this->testDb->table('anggota_unit_rapat')->insert([
            'anggota_id'    => 1,
            'unit_rapat_id' => 1,
        ]);
    }

    private function dropTables(): void
    {
        foreach ([
            'jadwal_banmus',
            'jadwal_unit_rapat',
            'jadwal',
            'anggota_unit_rapat',
            'anggota',
            'unit_rapat',
            'ruangan',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
