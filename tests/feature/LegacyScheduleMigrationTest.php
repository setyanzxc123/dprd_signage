<?php

use App\Database\Migrations\MigrateLegacySchedulesToGeneralSchedules;
use App\Database\Migrations\CleanupLegacyScheduleTables;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

require_once APPPATH . 'Database/Migrations/2026-07-30-000012_MigrateLegacySchedulesToGeneralSchedules.php';
require_once APPPATH . 'Database/Migrations/2026-07-30-000013_CleanupLegacyScheduleTables.php';

/**
 * @internal
 */
final class LegacyScheduleMigrationTest extends CIUnitTestCase
{
    private BaseConnection $testDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan.');
        }

        $this->testDb = Database::connect('tests');
        $this->forge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedLegacyData();
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->dropTables();
        }
        parent::tearDown();
    }

    public function testMigrationCopiesBothLegacySourcesAndParticipantRelationsOnce(): void
    {
        $migration = new MigrateLegacySchedulesToGeneralSchedules($this->forge);
        $migration->up();
        $migration->up();

        $rows = $this->testDb->table('jadwal_umum')->orderBy('id', 'ASC')->get()->getResultArray();
        $this->assertCount(3, $rows);
        $this->assertSame('Data target awal', $rows[0]['judul']);
        $this->assertSame('Insidental internal lama', $rows[1]['judul']);
        $this->assertSame('Audiensi lama', $rows[2]['judul']);
        $this->assertSame('Forum Warga', $rows[2]['pihak_eksternal']);
        $this->assertSame(1, (int) $rows[2]['ruangan_id']);
        $this->assertNull($rows[2]['lokasi_lainnya']);
        $this->assertStringContainsString('Sumber informasi: Surat masuk', $rows[2]['keterangan']);
        $this->assertSame(1, $this->testDb->table('jadwal_umum_unit_rapat')->countAllResults());
        $this->assertSame(2, $this->testDb->table('jadwal_umum_legacy_map')->countAllResults());
    }

    public function testRollbackRemovesOnlyMigratedRows(): void
    {
        $migration = new MigrateLegacySchedulesToGeneralSchedules($this->forge);
        $migration->up();
        $migration->down();

        $rows = $this->testDb->table('jadwal_umum')->get()->getResultArray();
        $this->assertCount(1, $rows);
        $this->assertSame('Data target awal', $rows[0]['judul']);
        $this->assertFalse($this->testDb->tableExists('jadwal_umum_legacy_map'));
    }

    public function testCleanupDropsLegacyTablesAndRollbackRestoresMappedRows(): void
    {
        (new MigrateLegacySchedulesToGeneralSchedules($this->forge))->up();
        $cleanup = new CleanupLegacyScheduleTables($this->forge);

        $cleanup->up();

        $this->assertFalse($this->testDb->tableExists('jadwal'));
        $this->assertFalse($this->testDb->tableExists('jadwal_unit_rapat'));
        $this->assertFalse($this->testDb->tableExists('agenda_umum'));
        $this->assertTrue($this->testDb->tableExists('jadwal_umum_legacy_map'));
        $this->assertSame(3, $this->testDb->table('jadwal_umum')->countAllResults());

        $cleanup->down();

        $this->assertSame(1, $this->testDb->table('jadwal')->countAllResults());
        $this->assertSame(1, $this->testDb->table('jadwal_unit_rapat')->countAllResults());
        $this->assertSame(1, $this->testDb->table('agenda_umum')->countAllResults());
        $this->assertSame(
            'Insidental internal lama',
            $this->testDb->table('jadwal')->where('id', 1)->get()->getRow('judul'),
        );
        $this->assertSame(
            'Audiensi lama',
            $this->testDb->table('agenda_umum')->where('id', 1)->get()->getRow('judul'),
        );
    }

    public function testCleanupRefusesToDropUnmappedLegacyRows(): void
    {
        $cleanup = new CleanupLegacyScheduleTables($this->forge);

        try {
            $cleanup->up();
            $this->fail('Cleanup seharusnya dibatalkan untuk data yang belum dimigrasikan.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('mapping migrasi jadwal tidak tersedia', $exception->getMessage());
        }

        $this->assertTrue($this->testDb->tableExists('jadwal'));
        $this->assertSame(1, $this->testDb->table('jadwal')->countAllResults());
    }

    private function createTables(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('ruangan');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('unit_rapat');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul' => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal' => ['type' => 'DATE'],
            'waktu_mulai' => ['type' => 'TIME', 'null' => true],
            'waktu_selesai' => ['type' => 'TIME', 'null' => true],
            'ruangan_id' => ['type' => 'INTEGER', 'null' => true],
            'lokasi_lainnya' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pihak_eksternal' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_publik' => ['type' => 'INTEGER', 'default' => 0],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal_umum');

        $this->forge->addField([
            'jadwal_umum_id' => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->createTable('jadwal_umum_unit_rapat');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul' => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal' => ['type' => 'DATE'],
            'waktu_mulai' => ['type' => 'TIME'],
            'waktu_selesai' => ['type' => 'TIME'],
            'ruangan_id' => ['type' => 'INTEGER', 'null' => true],
            'lokasi_lainnya' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_publik' => ['type' => 'INTEGER', 'default' => 0],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal');

        $this->forge->addField([
            'jadwal_id' => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
        ]);
        $this->forge->createTable('jadwal_unit_rapat');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul' => ['type' => 'VARCHAR', 'constraint' => 255],
            'kategori' => ['type' => 'VARCHAR', 'constraint' => 30],
            'pihak_eksternal' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'tanggal' => ['type' => 'DATE'],
            'waktu_mulai' => ['type' => 'TIME', 'null' => true],
            'waktu_selesai' => ['type' => 'TIME', 'null' => true],
            'lokasi' => ['type' => 'VARCHAR', 'constraint' => 255],
            'sumber_informasi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'is_publik' => ['type' => 'INTEGER', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('agenda_umum');
    }

    private function seedLegacyData(): void
    {
        $this->testDb->table('ruangan')->insert(['name' => 'Ruang Utama']);
        $this->testDb->table('unit_rapat')->insert(['nama' => 'Komisi I']);
        $this->testDb->table('jadwal_umum')->insert([
            'judul' => 'Data target awal',
            'tanggal' => '2099-08-01',
            'lokasi_lainnya' => 'Lokasi target',
        ]);
        $this->testDb->table('jadwal')->insert([
            'judul' => 'Insidental internal lama',
            'tanggal' => '2099-08-02',
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '10:00:00',
            'ruangan_id' => 1,
            'is_publik' => 0,
        ]);
        $this->testDb->table('jadwal_unit_rapat')->insert([
            'jadwal_id' => 1,
            'unit_rapat_id' => 1,
        ]);
        $this->testDb->table('agenda_umum')->insert([
            'judul' => 'Audiensi lama',
            'kategori' => 'audiensi',
            'pihak_eksternal' => 'Forum Warga',
            'tanggal' => '2099-08-03',
            'waktu_mulai' => '10:00:00',
            'waktu_selesai' => '11:00:00',
            'lokasi' => 'Ruang Utama',
            'sumber_informasi' => 'Surat masuk',
            'keterangan' => 'Catatan audiensi.',
            'is_publik' => 1,
        ]);
    }

    private function dropTables(): void
    {
        foreach ([
            'jadwal_umum_legacy_map',
            'jadwal_umum_unit_rapat',
            'jadwal_umum',
            'jadwal_unit_rapat',
            'jadwal',
            'agenda_umum',
            'unit_rapat',
            'ruangan',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
