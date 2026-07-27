<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class BanmusProjectionCrudTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $banmusDb;
    private Forge $banmusForge;

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
    }

    protected function tearDown(): void
    {
        if (isset($this->banmusForge)) {
            $this->dropTables();
        }

        parent::tearDown();
    }

    public function testAdminCanRenderSimpleBanmusTableForm(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/jadwal-banmus/create');

        $response->assertOK();
        $body = $response->response()->getBody();
        $this->assertStringContainsString('name="nomor_sk"', $body);
        $this->assertStringContainsString('name="dokumen_file"', $body);
        $this->assertStringContainsString('name="items[0][tanggal_pelaksanaan]"', $body);
        $this->assertStringContainsString('name="items[0][uraian_kegiatan]"', $body);
        $this->assertStringContainsString('name="items[0][keterangan]"', $body);
        $this->assertStringContainsString('data-add-item', $body);
        $this->assertStringNotContainsString('name="tanggal_sk"', $body);
        $this->assertStringNotContainsString('name="status"', $body);
        $this->assertStringNotContainsString('name="unit_rapat_id"', $body);
        $this->assertStringContainsString('name="csrf_test_name"', $body);
    }

    public function testAdminCanStoreSemesterDocumentAndActivityRows(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post('/admin/jadwal-banmus/store', [
                csrf_token()  => csrf_hash(),
                'nomor_sk'    => '160/TEST/2026',
                'tahun'       => '2026',
                'semester'    => '2',
                'dokumen_url' => 'https://example.com/sk-banmus.pdf',
                'items'       => [
                    [
                        'tanggal_pelaksanaan' => 'Juni–Juli 2026',
                        'uraian_kegiatan'     => 'Pembahasan agenda semester',
                        'keterangan'          => 'Menyesuaikan hasil rapat.',
                    ],
                    [
                        'tanggal_pelaksanaan' => 'Rabu, 1–5 Juli 2026',
                        'uraian_kegiatan'     => 'Kunjungan kerja Badan Musyawarah.',
                        'keterangan'          => '',
                    ],
                ],
            ]);

        $response->assertStatus(303);
        $response->assertRedirectTo(base_url('admin/jadwal-banmus'));

        $document = $this->banmusDb->table('dokumen_banmus')->get()->getRowArray();
        $items = $this->banmusDb->table('proyeksi_banmus')
            ->orderBy('urutan', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertNotNull($document);
        $this->assertSame('Jadwal Banmus Semester 2 Tahun 2026', $document['judul']);
        $this->assertSame('160/TEST/2026', $document['nomor_sk']);
        $this->assertNull($document['tanggal_sk']);
        $this->assertSame('disahkan', $document['status']);
        $this->assertSame(1, (int) $document['is_publik']);
        $this->assertCount(2, $items);
        $this->assertSame('Juni–Juli 2026', $items[0]['periode_label']);
        $this->assertSame('Pembahasan agenda semester', $items[0]['agenda']);
        $this->assertSame('Menyesuaikan hasil rapat.', $items[0]['catatan']);
        $this->assertSame(2, (int) $items[1]['urutan']);
        $this->assertNull($items[1]['tanggal_mulai']);

        $portal = $this->get('/agenda/jadwal-banmus?tahun=2026&semester=2');
        $portal->assertOK();
        $portalBody = $portal->response()->getBody();
        $this->assertStringContainsString('Jadwal Banmus Semester 2 Tahun 2026', $portalBody);
        $this->assertStringContainsString('Juni–Juli 2026', $portalBody);
        $this->assertStringContainsString('Pembahasan agenda semester', $portalBody);
        $this->assertStringContainsString('Menyesuaikan hasil rapat.', $portalBody);
        $this->assertStringContainsString(
            base_url("agenda/jadwal-banmus/{$document['id']}/dokumen"),
            $portalBody,
        );

        $source = $this->get("/agenda/jadwal-banmus/{$document['id']}/dokumen");
        $source->assertStatus(302);
        $source->assertRedirectTo('https://example.com/sk-banmus.pdf');
    }

    public function testMissingActivityDescriptionIsRejected(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post('/admin/jadwal-banmus/store', [
                csrf_token()  => csrf_hash(),
                'nomor_sk'    => '160/INVALID/2026',
                'tahun'       => '2026',
                'semester'    => '2',
                'dokumen_url' => 'https://example.com/sk-rancangan.pdf',
                'items'       => [[
                    'tanggal_pelaksanaan' => 'September 2026',
                    'uraian_kegiatan'     => '',
                    'keterangan'          => '',
                ]],
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'Uraian kegiatan pada baris ke-1 wajib diisi',
            $response->response()->getBody(),
        );
        $this->assertSame(0, $this->banmusDb->table('dokumen_banmus')->countAllResults());
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
            'periode_label'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'tanggal_mulai'     => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai'   => ['type' => 'DATE', 'null' => true],
            'unit_rapat_id'     => ['type' => 'INTEGER', 'null' => true],
            'urutan'            => ['type' => 'INTEGER'],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'catatan'           => ['type' => 'TEXT', 'null' => true],
            'jadwal_id'         => ['type' => 'INTEGER', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->banmusForge->addPrimaryKey('id');
        $this->banmusForge->createTable('proyeksi_banmus');
    }

    private function dropTables(): void
    {
        foreach (['proyeksi_banmus', 'dokumen_banmus'] as $table) {
            $this->banmusForge->dropTable($table, true);
        }
    }
}
