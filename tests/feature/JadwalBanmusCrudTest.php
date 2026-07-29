<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class JadwalBanmusCrudTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $banmusDb;
    private Forge $banmusForge;
    private int $documentId;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian CRUD database.');
        }

        $this->banmusDb = Database::connect('tests');
        $this->banmusForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedOptions();
    }

    protected function tearDown(): void
    {
        if (isset($this->banmusForge)) {
            $this->dropTables();
        }

        parent::tearDown();
    }

    public function testEditorExposesSingleAutomaticSaveAction(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get("/admin/jadwal-banmus/{$this->documentId}");

        $response->assertOK();
        $body = $response->response()->getBody();
        $this->assertStringContainsString('name="tanggal"', $body);
        $this->assertStringContainsString('name="jam_mulai"', $body);
        $this->assertStringContainsString('name="unit_ids[]"', $body);
        $this->assertStringContainsString('name="materi_url"', $body);
        $this->assertStringContainsString('Simpan Item Agenda', $body);
        $this->assertStringNotContainsString('value="save_projection"', $body);
        $this->assertStringNotContainsString('value="set_schedule"', $body);
        $this->assertStringNotContainsString('name="target_unit_ids[]"', $body);
    }

    public function testDateCanBeSavedWhileItemRemainsProjection(): void
    {
        $response = $this->postItem([
            'agenda'        => 'Pembahasan agenda semester',
            'periode_label' => 'Agustus 2026',
            'tanggal'       => '2026-08-12',
            'jam_mulai'     => '',
            'jam_selesai'   => '',
            'ruangan_id'    => '',
            'publikasi'     => 'internal',
            'unit_ids'      => ['1'],
        ]);

        $response->assertStatus(303);
        $row = $this->banmusDb->table('jadwal_banmus')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame('2026-08-12', $row['tanggal']);
        $this->assertSame('proyeksi', $row['status']);
        $this->assertSame(1, $this->banmusDb->table('jadwal_banmus_unit_rapat')->countAllResults());

        $body = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get("/admin/jadwal-banmus/{$this->documentId}")
            ->response()
            ->getBody();
        $this->assertStringNotContainsString('Belum pasti', $body);
        $this->assertStringContainsString('Agustus 2026', $body);
        $this->assertStringNotContainsString('12/08/2026', $body);
    }

    public function testIncompleteOperationalDataIsStoredAsProjection(): void
    {
        $response = $this->postItem([
            'agenda'      => 'Rapat yang belum lengkap',
            'tanggal'     => '2026-08-12',
            'jam_mulai'   => '',
            'jam_selesai' => '',
            'ruangan_id'  => '',
            'publikasi'   => 'internal',
        ]);

        $response->assertStatus(303);
        $row = $this->banmusDb->table('jadwal_banmus')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame('proyeksi', $row['status']);
    }

    public function testPublicProjectionPortalShowsProjectionAndOriginalSkLink(): void
    {
        $this->banmusDb->table('dokumen_banmus')
            ->where('id', $this->documentId)
            ->update(['dokumen_url' => 'https://example.com/sk-banmus.pdf']);

        $this->postItem([
            'agenda'        => 'Proyeksi pembahasan rancangan peraturan',
            'periode_label' => 'Agustus 2026',
            'tanggal'       => '',
            'jam_mulai'     => '',
            'jam_selesai'   => '',
            'ruangan_id'    => '',
            'publikasi'     => 'publik',
        ])->assertStatus(303);

        $response = $this->get('/agenda/jadwal-banmus?tahun=2026');

        $response->assertOK();
        $body = $response->response()->getBody();
        $this->assertStringContainsString('Proyeksi Banmus', $body);
        $this->assertStringContainsString('SK Banmus Pengujian', $body);
        $this->assertStringContainsString('Proyeksi pembahasan rancangan peraturan', $body);
        $this->assertStringContainsString('Agustus 2026', $body);
        $this->assertStringContainsString('Periode Proyeksi', $body);
        $this->assertStringContainsString('Lihat SK Asli', $body);
        $this->assertStringContainsString(
            base_url("agenda/jadwal-banmus/{$this->documentId}/dokumen"),
            $body,
        );

        $this->get("/agenda/jadwal-banmus/{$this->documentId}/dokumen")
            ->assertRedirectTo('https://example.com/sk-banmus.pdf');
    }

    public function testCompleteOperationalDataAutomaticallyBecomesSchedule(): void
    {
        $response = $this->postItem([
            'agenda'        => 'Rapat Banmus terjadwal',
            'periode_label' => 'Agustus 2026',
            'tanggal'       => '2026-08-12',
            'jam_mulai'     => '09:00',
            'jam_selesai'   => '11:00',
            'ruangan_id'    => '1',
            'publikasi'     => 'publik',
            'unit_ids'      => ['1', '2'],
            'materi_url'    => 'https://example.com/materi.pdf',
            'stream_url'    => 'https://example.com/live',
        ]);

        $response->assertStatus(303);
        $row = $this->banmusDb->table('jadwal_banmus')->get()->getRowArray();
        $this->assertSame('menunggu', $row['status']);
        $this->assertSame('publik', $row['publikasi']);
        $this->assertSame('https://example.com/live', $row['stream_url']);
        $this->assertSame(2, $this->banmusDb->table('jadwal_banmus_unit_rapat')->countAllResults());
        $this->assertSame(0, $this->banmusDb->table('jadwal')->countAllResults());

        $body = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get("/admin/jadwal-banmus/{$this->documentId}")
            ->response()
            ->getBody();
        $this->assertStringNotContainsString('Periode SK: Agustus 2026', $body);
        $this->assertStringContainsString('12/08/2026', $body);
        $this->assertStringNotContainsString('Ubah status pelaksanaan', $body);
        $this->assertStringNotContainsString("/item/{$row['id']}/status", $body);
    }

    public function testEditingScheduleToIncompleteDataAutomaticallyReturnsToProjection(): void
    {
        $this->postItem([
            'agenda'       => 'Rapat Banmus terjadwal',
            'tanggal'      => '2026-08-12',
            'jam_mulai'    => '09:00',
            'jam_selesai'  => '11:00',
            'ruangan_id'   => '1',
            'publikasi'    => 'internal',
            'unit_ids'     => ['1'],
        ])->assertStatus(303);

        $item = $this->banmusDb->table('jadwal_banmus')->get()->getRowArray();
        $this->assertNotNull($item);
        $this->assertSame('menunggu', $item['status']);

        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post("/admin/jadwal-banmus/{$this->documentId}/item/{$item['id']}/update", [
                csrf_token()  => csrf_hash(),
                'agenda'      => 'Rapat Banmus yang dijadwalkan ulang',
                'tanggal'     => '',
                'jam_mulai'   => '',
                'jam_selesai' => '',
                'ruangan_id'  => '',
                'publikasi'   => 'internal',
            ]);

        $response->assertStatus(303);
        $updatedItem = $this->banmusDb->table('jadwal_banmus')
            ->where('id', $item['id'])
            ->get()
            ->getRowArray();
        $this->assertSame('proyeksi', $updatedItem['status']);
    }

    public function testReschedulingCompletedItemRecalculatesLifecycleStatus(): void
    {
        $this->postItem([
            'agenda'      => 'Rapat Banmus selesai',
            'tanggal'     => '2026-08-12',
            'jam_mulai'   => '09:00',
            'jam_selesai' => '11:00',
            'ruangan_id'  => '1',
            'publikasi'   => 'internal',
            'unit_ids'    => ['1'],
        ])->assertStatus(303);

        $item = $this->banmusDb->table('jadwal_banmus')->get()->getRowArray();
        $this->assertNotNull($item);
        $this->banmusDb->table('jadwal_banmus')
            ->where('id', $item['id'])
            ->update(['status' => 'selesai']);

        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post("/admin/jadwal-banmus/{$this->documentId}/item/{$item['id']}/update", [
                csrf_token()    => csrf_hash(),
                'agenda'        => 'Rapat Banmus dijadwalkan ulang',
                'periode_label' => 'Agustus 2026',
                'tanggal'       => '2026-08-13',
                'jam_mulai'     => '09:00',
                'jam_selesai'   => '11:00',
                'ruangan_id'    => '1',
                'publikasi'     => 'internal',
                'unit_ids'      => ['1'],
            ]);

        $response->assertStatus(303);
        $updatedItem = $this->banmusDb->table('jadwal_banmus')
            ->where('id', $item['id'])
            ->get()
            ->getRowArray();
        $this->assertSame('menunggu', $updatedItem['status']);
    }

    public function testPublicBanmusLinksRequirePublicParentDocument(): void
    {
        $this->postItem([
            'agenda'       => 'Rapat Banmus publik',
            'tanggal'      => '2026-08-12',
            'jam_mulai'    => '09:00',
            'jam_selesai'  => '11:00',
            'ruangan_id'   => '1',
            'publikasi'    => 'publik',
            'unit_ids'     => ['1'],
            'materi_url'   => 'https://example.com/materi.pdf',
            'stream_url'   => 'https://example.com/live',
        ])->assertStatus(303);

        $item = $this->banmusDb->table('jadwal_banmus')->get()->getRowArray();
        $this->assertNotNull($item);
        $this->get("/go/jadwal-banmus/{$item['id']}/live")
            ->assertRedirectTo('https://example.com/live');

        $this->banmusDb->table('dokumen_banmus')
            ->where('id', $this->documentId)
            ->update(['is_publik' => 0]);

        $liveResponse = $this->get("/go/jadwal-banmus/{$item['id']}/live");
        $liveResponse->assertOK();
        $this->assertStringContainsString(
            'Siaran langsung untuk rapat ini belum tersedia.',
            $liveResponse->response()->getBody(),
        );

        $documentResponse = $this->get("/go/jadwal-banmus/{$item['id']}/berkas");
        $documentResponse->assertOK();
        $this->assertStringContainsString(
            'Berkas untuk rapat ini belum tersedia.',
            $documentResponse->response()->getBody(),
        );
    }

    public function testAutomaticScheduleRejectsRoomConflictWithNonBanmusSchedule(): void
    {
        $this->banmusDb->table('jadwal')->insert([
            'judul'         => 'Rapat insidental pada ruangan yang sama',
            'tanggal'       => '2026-08-12',
            'waktu_mulai'   => '09:30',
            'waktu_selesai' => '10:30',
            'ruangan_id'    => 1,
            'status'        => 'menunggu',
        ]);

        $response = $this->postItem([
            'agenda'       => 'Rapat Banmus bentrok',
            'tanggal'      => '2026-08-12',
            'jam_mulai'    => '09:00',
            'jam_selesai'  => '11:00',
            'ruangan_id'   => '1',
            'publikasi'    => 'internal',
            'unit_ids'     => ['1'],
        ]);

        $response->assertStatus(302);
        $this->assertSame(0, $this->banmusDb->table('jadwal_banmus')->countAllResults());
    }

    private function postItem(array $payload)
    {
        return $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post("/admin/jadwal-banmus/{$this->documentId}/item/store", [
                csrf_token() => csrf_hash(),
                ...$payload,
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
        $this->banmusForge->addField([
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
        $this->banmusForge->addPrimaryKey('id');
        $this->banmusForge->createTable('dokumen_banmus');

        $this->banmusForge->addField([
            'id'                => ['type' => 'INTEGER', 'auto_increment' => true],
            'dokumen_banmus_id' => ['type' => 'INTEGER'],
            'agenda'            => ['type' => 'TEXT'],
            'periode_label'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'tanggal_mulai'     => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai'   => ['type' => 'DATE', 'null' => true],
            'teks_tanggal_asli' => ['type' => 'TEXT', 'null' => true],
            'bulan_mulai'       => ['type' => 'CHAR', 'constraint' => 7, 'null' => true],
            'bulan_selesai'     => ['type' => 'CHAR', 'constraint' => 7, 'null' => true],
            'jumlah_pelaksanaan_rencana' => ['type' => 'INTEGER', 'default' => 1],
            'halaman_sumber'    => ['type' => 'INTEGER', 'null' => true],
            'urutan'            => ['type' => 'INTEGER', 'default' => 0],
            'tanggal'           => ['type' => 'DATE', 'null' => true],
            'jam_mulai'         => ['type' => 'TIME', 'null' => true],
            'jam_selesai'       => ['type' => 'TIME', 'null' => true],
            'ruangan_id'        => ['type' => 'INTEGER', 'null' => true],
            'lokasi_lainnya'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'publikasi'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'internal'],
            'materi_url'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'stream_url'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'proyeksi'],
            'catatan'           => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->banmusForge->addPrimaryKey('id');
        $this->banmusForge->createTable('jadwal_banmus');

        $this->banmusForge->addField([
            'jadwal_banmus_id' => ['type' => 'INTEGER'],
            'unit_rapat_id'    => ['type' => 'INTEGER'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->banmusForge->addPrimaryKey(['jadwal_banmus_id', 'unit_rapat_id']);
        $this->banmusForge->createTable('jadwal_banmus_unit_rapat');

        $this->banmusForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'kapasitas'  => ['type' => 'INTEGER', 'default' => 0],
            'tersedia'   => ['type' => 'INTEGER', 'default' => 1],
        ]);
        $this->banmusForge->addPrimaryKey('id');
        $this->banmusForge->createTable('ruangan');

        $this->banmusForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'aktif'      => ['type' => 'INTEGER', 'default' => 1],
            'urutan'     => ['type' => 'INTEGER', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->banmusForge->addPrimaryKey('id');
        $this->banmusForge->createTable('unit_rapat');

        $this->banmusForge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal'        => ['type' => 'DATE'],
            'waktu_mulai'    => ['type' => 'TIME'],
            'waktu_selesai'  => ['type' => 'TIME'],
            'ruangan_id'     => ['type' => 'INTEGER', 'null' => true],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'menunggu'],
        ]);
        $this->banmusForge->addPrimaryKey('id');
        $this->banmusForge->createTable('jadwal');
    }

    private function seedOptions(): void
    {
        $this->banmusDb->table('dokumen_banmus')->insert([
            'judul'       => 'SK Banmus Pengujian',
            'nomor_sk'    => '160/TEST/2026',
            'tahun'       => 2026,
            'semester'    => 2,
            'status'      => 'disahkan',
            'is_publik'   => 1,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        $this->documentId = (int) $this->banmusDb->insertID();

        $this->banmusDb->table('ruangan')->insert([
            'name' => 'Ruang Banmus',
            'kapasitas' => 40,
            'tersedia' => 1,
        ]);
        foreach (['Badan Musyawarah', 'Seluruh Anggota DPRD'] as $index => $name) {
            $this->banmusDb->table('unit_rapat')->insert([
                'nama' => $name,
                'aktif' => 1,
                'urutan' => $index + 1,
            ]);
        }
    }

    private function dropTables(): void
    {
        foreach ([
            'jadwal_banmus_unit_rapat',
            'jadwal_banmus',
            'jadwal',
            'unit_rapat',
            'ruangan',
            'dokumen_banmus',
        ] as $table) {
            $this->banmusForge->dropTable($table, true);
        }
    }
}
