<?php

use App\Database\Migrations\CreateGeneralSchedules;
use App\Database\Migrations\CreateGeneralScheduleUnits;
use App\Database\Migrations\AddGeneralScheduleResources;
use App\Database\Migrations\AddScheduleInvitations;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

require_once APPPATH . 'Database/Migrations/2026-07-30-000010_CreateGeneralSchedules.php';
require_once APPPATH . 'Database/Migrations/2026-07-30-000011_CreateGeneralScheduleUnits.php';
require_once APPPATH . 'Database/Migrations/2026-08-03-000015_AddGeneralScheduleResources.php';
require_once APPPATH . 'Database/Migrations/2026-08-03-000016_AddScheduleInvitations.php';

/**
 * @internal
 */
final class GeneralScheduleMigrationTest extends CIUnitTestCase
{
    private BaseConnection $testDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian migration.');
        }

        $this->testDb = Database::connect('tests');
        $this->forge = Database::forge('tests');
        $this->dropTables();
        $this->createDependencies();
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->dropTables();
        }
        parent::tearDown();
    }

    public function testCreatesAndRollsBackGeneralScheduleSchema(): void
    {
        $scheduleMigration = new CreateGeneralSchedules($this->forge);
        $unitMigration = new CreateGeneralScheduleUnits($this->forge);
        $resourceMigration = new AddGeneralScheduleResources($this->forge);
        $invitationMigration = new AddScheduleInvitations($this->forge);

        $scheduleMigration->up();
        $unitMigration->up();
        $resourceMigration->up();
        $invitationMigration->up();

        $this->assertTrue($this->testDb->tableExists('jadwal_umum'));
        $this->assertTrue($this->testDb->tableExists('jadwal_umum_unit_rapat'));
        foreach ([
            'judul', 'tanggal', 'waktu_mulai', 'waktu_selesai', 'ruangan_id',
            'lokasi_lainnya', 'pihak_eksternal', 'is_publik', 'keterangan',
            'materi_url', 'materi_akses', 'stream_url', 'stream_akses',
            'undangan_file', 'undangan_nama_asli',
        ] as $field) {
            $this->assertTrue($this->testDb->fieldExists($field, 'jadwal_umum'));
        }
        $this->assertFalse($this->testDb->fieldExists('lingkup', 'jadwal_umum'));
        $this->assertFalse($this->testDb->fieldExists('kategori', 'jadwal_umum'));

        $invitationMigration->down();
        foreach (['undangan_file', 'undangan_nama_asli'] as $field) {
            $this->assertFalse($this->testDb->fieldExists($field, 'jadwal_umum'));
        }

        $resourceMigration->down();
        foreach (['materi_url', 'materi_akses', 'stream_url', 'stream_akses'] as $field) {
            $this->assertFalse($this->testDb->fieldExists($field, 'jadwal_umum'));
        }

        $unitMigration->down();
        $scheduleMigration->down();

        $this->assertFalse($this->testDb->tableExists('jadwal_umum_unit_rapat'));
        $this->assertFalse($this->testDb->tableExists('jadwal_umum'));
    }

    private function createDependencies(): void
    {
        $this->forge->addField([
            'id'        => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'tersedia'  => ['type' => 'INTEGER', 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('ruangan');

        $this->forge->addField([
            'id'     => ['type' => 'INTEGER', 'auto_increment' => true],
            'nama'   => ['type' => 'VARCHAR', 'constraint' => 150],
            'aktif'  => ['type' => 'INTEGER', 'default' => 1],
            'urutan' => ['type' => 'INTEGER', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('unit_rapat');
    }

    private function dropTables(): void
    {
        foreach (['jadwal_umum_unit_rapat', 'jadwal_umum', 'unit_rapat', 'ruangan'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
