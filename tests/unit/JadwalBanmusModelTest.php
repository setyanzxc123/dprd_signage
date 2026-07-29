<?php

use App\Models\BanmusDocumentModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * @internal
 */
final class JadwalBanmusModelTest extends CIUnitTestCase
{
    private BaseConnection $banmusDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian model database.');
        }

        $this->banmusDb = Database::connect('tests');
        $this->forge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        $this->dropTables();

        parent::tearDown();
    }

    public function testPublicPortalHidesInternalDocumentAndOrdersRows(): void
    {
        $publicDocumentId = $this->insertDocument('SK Publik', 1);
        $this->insertDocument('SK Internal', 0);
        $this->insertBanmusSchedule($publicDocumentId, 2);
        $this->insertBanmusSchedule($publicDocumentId, 1);

        $documents = (new BanmusDocumentModel())->findForPortal(false, 2026);

        $this->assertCount(1, $documents);
        $this->assertSame('SK Publik', $documents[0]['judul']);
        $this->assertSame([1, 2], array_map(
            'intval',
            array_column($documents[0]['items'], 'urutan'),
        ));
        $this->assertSame('Juni 2026', $documents[0]['items'][0]['periode_label']);
    }

    public function testMemberPortalIncludesInternalDocuments(): void
    {
        $this->insertDocument('SK Publik', 1);
        $this->insertDocument('SK Internal', 0);

        $documents = (new BanmusDocumentModel())->findForPortal(true, 2026);

        $this->assertCount(2, $documents);
        $this->assertSame(
            ['SK Internal', 'SK Publik'],
            array_column($documents, 'judul'),
        );
    }

    public function testAvailableYearsRespectsPublicationAndSortsDescending(): void
    {
        $this->insertDocument('SK 2025 Publik', 1, 2025);
        $this->insertDocument('SK 2026 Internal', 0, 2026);
        $this->insertDocument('SK 2027 Publik', 1, 2027);

        $model = new BanmusDocumentModel();

        $this->assertSame([2027, 2025], $model->availableYears(false));
        $this->assertSame([2027, 2026, 2025], $model->availableYears(true));
    }

    private function createTables(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'             => ['type' => 'VARCHAR', 'constraint' => 200],
            'nomor_sk'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'tanggal_sk'        => ['type' => 'DATE', 'null' => true],
            'tahun'             => ['type' => 'INTEGER'],
            'semester'          => ['type' => 'INTEGER'],
            'masa_persidangan'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'periode_mulai'     => ['type' => 'DATE', 'null' => true],
            'periode_selesai'   => ['type' => 'DATE', 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'is_publik'         => ['type' => 'INTEGER', 'default' => 0],
            'dokumen_file'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dokumen_nama_asli' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dokumen_url'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'catatan'           => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('dokumen_banmus');

        $this->forge->addField([
            'id'                => ['type' => 'INTEGER', 'auto_increment' => true],
            'dokumen_banmus_id' => ['type' => 'INTEGER'],
            'agenda'            => ['type' => 'TEXT'],
            'periode_label'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'tanggal_mulai'     => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai'   => ['type' => 'DATE', 'null' => true],
            'unit_rapat_id'     => ['type' => 'INTEGER', 'null' => true],
            'urutan'            => ['type' => 'INTEGER'],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'catatan'           => ['type' => 'TEXT', 'null' => true],
            'tanggal'           => ['type' => 'DATE', 'null' => true],
            'jam_mulai'         => ['type' => 'TIME', 'null' => true],
            'jam_selesai'       => ['type' => 'TIME', 'null' => true],
            'ruangan_id'        => ['type' => 'INTEGER', 'null' => true],
            'lokasi_lainnya'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'publikasi'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'internal'],
            'materi_url'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'stream_url'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal_banmus');
    }

    private function dropTables(): void
    {
        foreach (['jadwal_banmus', 'dokumen_banmus'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function insertDocument(string $title, int $isPublic, int $year = 2026): int
    {
        $this->banmusDb->table('dokumen_banmus')->insert([
            'judul'       => $title,
            'nomor_sk'    => '160/TEST/' . $year . '/' . $isPublic,
            'tanggal_sk'  => null,
            'tahun'       => $year,
            'semester'    => 1,
            'status'      => 'disahkan',
            'is_publik'   => $isPublic,
            'dokumen_url' => 'https://example.com/sk.pdf',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->banmusDb->insertID();
    }

    private function insertBanmusSchedule(int $documentId, int $order): int
    {
        $this->banmusDb->table('jadwal_banmus')->insert([
            'dokumen_banmus_id' => $documentId,
            'agenda'            => 'Uraian kegiatan ' . $order,
            'periode_label'     => 'Juni 2026',
            'urutan'            => $order,
            'status'            => 'proyeksi',
            'publikasi'         => 'publik',
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->banmusDb->insertID();
    }
}
