<?php

use App\Libraries\Schedule\AgendaWorkspaceService;
use App\Libraries\Schedule\Persistence\DatabaseScheduleReadRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class AgendaWorkspaceTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $testDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian kalender terpadu.');
        }

        $this->testDb = Database::connect('tests');
        $this->forge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedSchedules();
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->dropTables();
        }

        parent::tearDown();
    }

    public function testWorkspaceCombinesOnlyTheTwoTargetSourcesAndExcludesBanmusProjection(): void
    {
        $result = (new AgendaWorkspaceService($this->testDb))->loadMonth('2099-08');

        $this->assertCount(2, $result['agendas']);
        $this->assertSame(1, $result['counts']['banmus']);
        $this->assertSame(1, $result['counts']['jadwal_umum']);
        $this->assertSame(
            ['banmus', 'jadwal_umum'],
            $this->sortedSources($result['agendas']),
        );
        $this->assertNotContains('Proyeksi Banmus', array_column($result['agendas'], 'judul'));
        $this->assertNotContains('Reses terjadwal', array_column($result['agendas'], 'judul'));

        $upcoming = (new DatabaseScheduleReadRepository($this->testDb))
            ->findUpcomingPublic('2099-08-01', 10);
        $this->assertNotContains('Reses terjadwal', array_column($upcoming, 'judul'));

        $repository = new DatabaseScheduleReadRepository($this->testDb);
        $this->assertSame([1], $repository->findMemberUnitIds(9));
        $memberScheduleIds = $repository->findScheduleIdsForMember(9);
        sort($memberScheduleIds);
        $this->assertSame([-1, 1], $memberScheduleIds);
    }

    public function testWorkspaceMarksCrossSourceRoomAndTimeConflicts(): void
    {
        $result = (new AgendaWorkspaceService($this->testDb))->loadMonth('2099-08');
        $conflicts = array_values(array_filter(
            $result['agendas'],
            static fn (array $agenda): bool => $agenda['has_conflict'],
        ));

        $this->assertCount(2, $conflicts);
        $this->assertSame(2, $result['counts']['conflicts']);
        $this->assertSame(
            ['banmus', 'jadwal_umum'],
            $this->sortedSources($conflicts),
        );
        $labels = array_column(array_merge(...array_column($conflicts, 'conflicts')), 'label');
        $this->assertStringContainsString('Jadwal Umum', implode(' ', $labels));
        $this->assertStringContainsString('Agenda Banmus', implode(' ', $labels));
    }

    public function testWorkspaceFiltersBySourceUnitLocationStatusAndPublication(): void
    {
        $service = new AgendaWorkspaceService($this->testDb);

        $general = $service->loadMonth('2099-08', ['source' => 'jadwal_umum']);
        $this->assertCount(1, $general['agendas']);
        $this->assertSame('Audiensi umum', $general['agendas'][0]['judul']);

        $unit = $service->loadMonth('2099-08', ['unit' => '1']);
        $this->assertCount(2, $unit['agendas']);

        $location = $service->loadMonth('2099-08', ['lokasi' => 'ruang rapat utama']);
        $this->assertCount(2, $location['agendas']);

        $status = $service->loadMonth('2099-08', ['status' => 'menunggu']);
        $this->assertCount(2, $status['agendas']);

        $public = $service->loadMonth('2099-08', ['publikasi' => 'publik']);
        $this->assertCount(1, $public['agendas']);
        $this->assertSame('Banmus terjadwal', $public['agendas'][0]['judul']);
    }

    public function testAgendaWorkspaceUsesListByDefaultAndKeepsCompactCalendarAvailable(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/kalender?month=2099-08');

        $response->assertOK();
        $body = $response->response()->getBody();
        $this->assertStringContainsString('Kalender Agenda', $body);
        $this->assertStringContainsString('Agenda Banmus terjadwal dan Jadwal Umum', $body);
        $this->assertStringContainsString('name="source"', $body);
        $this->assertStringContainsString('name="unit"', $body);
        $this->assertStringContainsString('Konflik', $body);
        $this->assertStringContainsString('Audiensi umum', $body);
        $this->assertStringContainsString('Banmus terjadwal', $body);
        $this->assertStringContainsString('data-admin-datatable', $body);
        $this->assertStringContainsString('Konflik lintas sumber', $body);
        $this->assertStringNotContainsString('dashboard-calendar-grid', $body);

        $calendarBody = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/kalender?month=2099-08&view=calendar')
            ->response()
            ->getBody();
        $this->assertStringContainsString('dashboard-calendar-grid', $calendarBody);
        $this->assertStringNotContainsString('data-admin-datatable', $calendarBody);
    }

    /** @param list<array<string, mixed>> $agendas @return list<string> */
    private function sortedSources(array $agendas): array
    {
        $sources = array_column($agendas, 'source');
        sort($sources);

        return $sources;
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

    private function createTables(): void
    {
        $this->forge->addField([
            'id'        => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'tersedia'  => ['type' => 'INTEGER', 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('ruangan');

        $this->forge->addField([
            'id'      => ['type' => 'INTEGER', 'auto_increment' => true],
            'nama'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'aktif'   => ['type' => 'INTEGER', 'default' => 1],
            'urutan'  => ['type' => 'INTEGER', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('unit_rapat');

        $this->forge->addField([
            'anggota_id'    => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
        ]);
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
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal_umum');

        $this->forge->addField([
            'jadwal_umum_id' => ['type' => 'INTEGER'],
            'unit_rapat_id'  => ['type' => 'INTEGER'],
        ]);
        $this->forge->createTable('jadwal_umum_unit_rapat');

        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'is_publik'  => ['type' => 'INTEGER', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('dokumen_banmus');

        $this->forge->addField([
            'id'                   => ['type' => 'INTEGER', 'auto_increment' => true],
            'dokumen_banmus_id'    => ['type' => 'INTEGER'],
            'agenda'               => ['type' => 'VARCHAR', 'constraint' => 255],
            'jenis_agenda'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'rapat'],
            'catatan'              => ['type' => 'TEXT', 'null' => true],
            'tanggal'              => ['type' => 'DATE', 'null' => true],
            'jam_mulai'            => ['type' => 'TIME', 'null' => true],
            'jam_selesai'          => ['type' => 'TIME', 'null' => true],
            'ruangan_id'           => ['type' => 'INTEGER', 'null' => true],
            'lokasi_lainnya'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'               => ['type' => 'VARCHAR', 'constraint' => 20],
            'publikasi'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'materi_url'           => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'materi_akses'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'peserta'],
            'stream_url'           => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'stream_akses'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'anggota'],
            'deleted_at'           => ['type' => 'DATETIME', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal_banmus');

        $this->forge->addField([
            'jadwal_banmus_id' => ['type' => 'INTEGER'],
            'unit_rapat_id'    => ['type' => 'INTEGER'],
        ]);
        $this->forge->createTable('jadwal_banmus_unit_rapat');

    }

    private function seedSchedules(): void
    {
        $this->testDb->table('ruangan')->insert(['name' => 'Ruang Rapat Utama']);
        $this->testDb->table('unit_rapat')->insert([
            'nama'   => 'Pimpinan DPRD',
            'aktif'  => 1,
            'urutan' => 1,
        ]);
        $this->testDb->table('anggota_unit_rapat')->insert([
            'anggota_id' => 9,
            'unit_rapat_id' => 1,
        ]);
        $this->testDb->table('jadwal_umum')->insert([
            'judul'           => 'Audiensi umum',
            'tanggal'         => '2099-08-12',
            'waktu_mulai'     => '09:00:00',
            'waktu_selesai'   => '10:00:00',
            'ruangan_id'      => 1,
            'pihak_eksternal' => 'Pemerintah Kabupaten',
            'is_publik'       => 0,
        ]);
        $this->testDb->table('jadwal_umum_unit_rapat')->insert([
            'jadwal_umum_id' => 1,
            'unit_rapat_id'  => 1,
        ]);
        $this->testDb->table('dokumen_banmus')->insert(['is_publik' => 1]);
        $this->testDb->table('jadwal_banmus')->insertBatch([
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
        $this->testDb->table('jadwal_banmus_unit_rapat')->insert([
            'jadwal_banmus_id' => 1,
            'unit_rapat_id'    => 1,
        ]);

    }

    private function dropTables(): void
    {
        foreach ([
            'jadwal_banmus_unit_rapat',
            'jadwal_banmus',
            'dokumen_banmus',
            'jadwal_umum_unit_rapat',
            'jadwal_umum',
            'anggota_unit_rapat',
            'unit_rapat',
            'ruangan',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
