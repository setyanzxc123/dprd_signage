<?php

use App\Libraries\Schedule\ScheduleResourceLinkService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * @internal
 */
final class ScheduleResourceLinkServiceTest extends CIUnitTestCase
{
    private BaseConnection $resourceDb;
    private Forge $resourceForge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian akses sumber daya.');
        }

        $this->resourceDb = Database::connect('tests');
        $this->resourceForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        if (isset($this->resourceForge)) {
            $this->dropTables();
        }

        parent::tearDown();
    }

    public function testBanmusPublicResourceRequiresPublicParentDocument(): void
    {
        $this->resourceDb->table('dokumen_banmus')->insert(['is_publik' => 0]);
        $documentId = (int) $this->resourceDb->insertID();
        $scheduleId = $this->insertBanmus($documentId, 'https://example.com/banmus', 'publik');
        $service = new ScheduleResourceLinkService($this->resourceDb);

        $this->assertNull($service->publicUrl('banmus', $scheduleId, 'materi'));
        $this->assertSame(
            'https://example.com/banmus',
            $service->memberUrl('banmus', $scheduleId, 'materi', 10),
        );

        $this->resourceDb->table('dokumen_banmus')
            ->where('id', $documentId)
            ->update(['is_publik' => 1]);

        $this->assertSame(
            'https://example.com/banmus',
            $service->publicUrl('banmus', $scheduleId, 'materi'),
        );
    }

    public function testParticipantResourceRequiresMemberUnitRelation(): void
    {
        $this->resourceDb->table('dokumen_banmus')->insert(['is_publik' => 1]);
        $scheduleId = $this->insertBanmus(
            (int) $this->resourceDb->insertID(),
            'https://example.com/bahan',
            'peserta',
        );
        $this->resourceDb->table('jadwal_banmus_unit_rapat')->insert([
            'jadwal_banmus_id' => $scheduleId,
            'unit_rapat_id'    => 7,
        ]);
        $this->resourceDb->table('anggota_unit_rapat')->insert([
            'anggota_id'    => 10,
            'unit_rapat_id' => 7,
        ]);

        $service = new ScheduleResourceLinkService($this->resourceDb);

        $this->assertNull($service->publicUrl('banmus', $scheduleId, 'materi'));
        $this->assertSame(
            'https://example.com/bahan',
            $service->memberUrl('banmus', $scheduleId, 'materi', 10),
        );
        $this->assertNull($service->memberUrl('banmus', $scheduleId, 'materi', 11));
    }

    public function testUnsafeExternalSchemeAndUnknownSourceAreRejected(): void
    {
        $this->resourceDb->table('dokumen_banmus')->insert(['is_publik' => 1]);
        $scheduleId = $this->insertBanmus(
            (int) $this->resourceDb->insertID(),
            'javascript:alert(1)',
            'publik',
        );
        $service = new ScheduleResourceLinkService($this->resourceDb);

        $this->assertNull($service->publicUrl('banmus', $scheduleId, 'materi'));
        $this->assertNull($service->publicUrl('jadwal_umum', $scheduleId, 'materi'));
    }

    private function insertBanmus(int $documentId, string $materialUrl, string $access): int
    {
        $this->resourceDb->table('jadwal_banmus')->insert([
            'dokumen_banmus_id' => $documentId,
            'jenis_agenda'      => 'rapat',
            'status'            => 'menunggu',
            'publikasi'         => 'publik',
            'materi_url'        => $materialUrl,
            'materi_akses'      => $access,
            'stream_url'        => null,
            'stream_akses'      => 'anggota',
            'deleted_at'        => null,
        ]);

        return (int) $this->resourceDb->insertID();
    }

    private function createTables(): void
    {
        $this->resourceForge->addField([
            'anggota_id'    => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
        ]);
        $this->resourceForge->createTable('anggota_unit_rapat');

        $this->resourceForge->addField([
            'id'        => ['type' => 'INTEGER', 'auto_increment' => true],
            'is_publik' => ['type' => 'INTEGER', 'default' => 0],
        ]);
        $this->resourceForge->addPrimaryKey('id');
        $this->resourceForge->createTable('dokumen_banmus');

        $this->resourceForge->addField([
            'id'                  => ['type' => 'INTEGER', 'auto_increment' => true],
            'dokumen_banmus_id'  => ['type' => 'INTEGER'],
            'jenis_agenda'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 20],
            'publikasi'          => ['type' => 'VARCHAR', 'constraint' => 20],
            'materi_url'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'materi_akses'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'stream_url'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'stream_akses'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'undangan_file'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'undangan_nama_asli' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->resourceForge->addPrimaryKey('id');
        $this->resourceForge->createTable('jadwal_banmus');

        $this->resourceForge->addField([
            'jadwal_banmus_id' => ['type' => 'INTEGER'],
            'unit_rapat_id'    => ['type' => 'INTEGER'],
        ]);
        $this->resourceForge->createTable('jadwal_banmus_unit_rapat');
    }

    private function dropTables(): void
    {
        foreach ([
            'jadwal_banmus_unit_rapat',
            'jadwal_banmus',
            'dokumen_banmus',
            'anggota_unit_rapat',
        ] as $table) {
            $this->resourceForge->dropTable($table, true);
        }
    }
}
