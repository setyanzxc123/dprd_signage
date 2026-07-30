<?php

use App\Database\Migrations\BackfillBanmusProjectionPeriods;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

require_once APPPATH . 'Database/Migrations/2026-07-30-000014_BackfillBanmusProjectionPeriods.php';

/**
 * @internal
 */
final class BanmusProjectionPeriodMigrationTest extends CIUnitTestCase
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
        $this->forge->dropTable('jadwal_banmus', true);
        $this->forge->dropTable('dokumen_banmus', true);

        $this->forge->addField([
            'id'    => ['type' => 'INTEGER', 'auto_increment' => true],
            'tahun' => ['type' => 'INTEGER'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('dokumen_banmus');

        $this->forge->addField([
            'id'                => ['type' => 'INTEGER', 'auto_increment' => true],
            'dokumen_banmus_id' => ['type' => 'INTEGER'],
            'periode_label'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'tanggal_mulai'     => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai'   => ['type' => 'DATE', 'null' => true],
            'bulan_mulai'       => ['type' => 'CHAR', 'constraint' => 7, 'null' => true],
            'bulan_selesai'     => ['type' => 'CHAR', 'constraint' => 7, 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal_banmus');

        $this->testDb->table('dokumen_banmus')->insert(['tahun' => 2027]);
        $documentId = (int) $this->testDb->insertID();
        $this->testDb->table('jadwal_banmus')->insertBatch([
            [
                'dokumen_banmus_id' => $documentId,
                'periode_label'     => 'Januari–April 2027',
                'status'            => 'proyeksi',
            ],
            [
                'dokumen_banmus_id' => $documentId,
                'periode_label'     => 'Menyesuaikan keputusan Banmus',
                'status'            => 'proyeksi',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->forge->dropTable('jadwal_banmus', true);
            $this->forge->dropTable('dokumen_banmus', true);
        }
        parent::tearDown();
    }

    public function testMigrationBackfillsKnownRangesAndLeavesUnknownLabelsUnbounded(): void
    {
        (new BackfillBanmusProjectionPeriods($this->forge))->up();

        $rows = $this->testDb->table('jadwal_banmus')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        $this->assertSame('2027-01', $rows[0]['bulan_mulai']);
        $this->assertSame('2027-04', $rows[0]['bulan_selesai']);
        $this->assertNull($rows[1]['bulan_mulai']);
        $this->assertNull($rows[1]['bulan_selesai']);
    }
}
