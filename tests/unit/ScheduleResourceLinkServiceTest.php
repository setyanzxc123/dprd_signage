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

    public function testParticipantResourceRequiresMemberUnitRelation(): void
    {
        $this->resourceDb->table('jadwal')->insert([
            'jenis'          => 'insidental',
            'is_publik'      => 1,
            'materi_url'     => 'https://example.com/bahan',
            'materi_akses'   => 'peserta',
            'stream_url'     => 'https://example.com/live',
            'stream_akses'   => 'publik',
        ]);
        $scheduleId = (int) $this->resourceDb->insertID();
        $this->resourceDb->table('jadwal_unit_rapat')->insert([
            'jadwal_id'     => $scheduleId,
            'unit_rapat_id' => 7,
        ]);
        $this->resourceDb->table('anggota_unit_rapat')->insert([
            'anggota_id'    => 10,
            'unit_rapat_id' => 7,
        ]);

        $service = new ScheduleResourceLinkService($this->resourceDb);

        $this->assertNull($service->publicUrl('insidental_internal', $scheduleId, 'materi'));
        $this->assertSame(
            'https://example.com/live',
            $service->publicUrl('insidental_internal', $scheduleId, 'stream'),
        );
        $this->assertSame(
            'https://example.com/bahan',
            $service->memberUrl('insidental_internal', $scheduleId, 'materi', 10),
        );
        $this->assertNull(
            $service->memberUrl('insidental_internal', $scheduleId, 'materi', 11),
        );
    }

    public function testBanmusPublicResourceStillRequiresPublicParentDocument(): void
    {
        $this->resourceDb->table('dokumen_banmus')->insert(['is_publik' => 0]);
        $documentId = (int) $this->resourceDb->insertID();
        $this->resourceDb->table('jadwal_banmus')->insert([
            'dokumen_banmus_id' => $documentId,
            'jenis_agenda'      => 'rapat',
            'status'            => 'menunggu',
            'publikasi'         => 'publik',
            'materi_url'        => 'https://example.com/banmus',
            'materi_akses'      => 'publik',
            'stream_url'        => null,
            'stream_akses'      => 'anggota',
            'deleted_at'        => null,
        ]);
        $scheduleId = (int) $this->resourceDb->insertID();

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

    public function testUnsafeExternalSchemeIsRejected(): void
    {
        $this->resourceDb->table('jadwal')->insert([
            'jenis'          => 'insidental',
            'is_publik'      => 1,
            'materi_url'     => 'javascript:alert(1)',
            'materi_akses'   => 'publik',
            'stream_url'     => null,
            'stream_akses'   => 'anggota',
        ]);

        $this->assertNull((new ScheduleResourceLinkService($this->resourceDb))->publicUrl(
            'insidental_internal',
            (int) $this->resourceDb->insertID(),
            'materi',
        ));
    }

    private function createTables(): void
    {
        $this->resourceForge->addField([
            'id'            => ['type' => 'INTEGER', 'auto_increment' => true],
            'jenis'         => ['type' => 'VARCHAR', 'constraint' => 30],
            'is_publik'     => ['type' => 'INTEGER', 'default' => 0],
            'materi_url'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'materi_akses'  => ['type' => 'VARCHAR', 'constraint' => 20],
            'stream_url'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'stream_akses'  => ['type' => 'VARCHAR', 'constraint' => 20],
        ]);
        $this->resourceForge->addPrimaryKey('id');
        $this->resourceForge->createTable('jadwal');

        $this->resourceForge->addField([
            'jadwal_id'     => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
        ]);
        $this->resourceForge->createTable('jadwal_unit_rapat');

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
            'jadwal_unit_rapat',
            'jadwal',
        ] as $table) {
            $this->resourceForge->dropTable($table, true);
        }
    }
}
