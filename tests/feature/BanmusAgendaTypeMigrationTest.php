<?php

use App\Database\Migrations\AddBanmusAgendaType;
use App\Database\Migrations\ClassifyExistingNonMeetingBanmusItems;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

require_once APPPATH . 'Database/Migrations/2026-07-30-000007_AddBanmusAgendaType.php';
require_once APPPATH . 'Database/Migrations/2026-07-30-000008_ClassifyExistingNonMeetingBanmusItems.php';

/**
 * @internal
 */
final class BanmusAgendaTypeMigrationTest extends CIUnitTestCase
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
        $this->forge->dropTable('jadwal_banmus', true);
        $this->forge->addField([
            'id'     => ['type' => 'INTEGER', 'auto_increment' => true],
            'agenda' => ['type' => 'TEXT'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal_banmus');
        $this->testDb->table('jadwal_banmus')->insertBatch([
            ['agenda' => 'Rapat Paripurna DPRD'],
            ['agenda' => 'Pelaksanaan Reses Anggota DPRD'],
            ['agenda' => 'Cuti bersama'],
            ['agenda' => 'Koordinasi dan Komunikasi Antar Daerah'],
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->forge->dropTable('jadwal_banmus', true);
        }

        parent::tearDown();
    }

    public function testMigrationClassifiesExistingResesAndCanRollback(): void
    {
        $this->assertTrue($this->testDb->tableExists('jadwal_banmus'));
        $this->assertFalse($this->testDb->fieldExists('jenis_agenda', 'jadwal_banmus'));

        $migration = new AddBanmusAgendaType($this->forge);
        $migration->up();
        (new ClassifyExistingNonMeetingBanmusItems($this->forge))->up();

        $this->testDb->resetDataCache();
        $this->assertTrue($this->testDb->fieldExists('jenis_agenda', 'jadwal_banmus'));
        $rows = $this->testDb->table('jadwal_banmus')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        $this->assertSame('rapat', $rows[0]['jenis_agenda']);
        $this->assertSame('non_rapat', $rows[1]['jenis_agenda']);
        $this->assertSame('non_rapat', $rows[2]['jenis_agenda']);
        $this->assertSame('non_rapat', $rows[3]['jenis_agenda']);

        $migration->down();

        $this->testDb->resetDataCache();
        $this->assertFalse($this->testDb->fieldExists('jenis_agenda', 'jadwal_banmus'));
    }
}
