<?php

use App\Database\Migrations\ExpandGeneralAgendaFields;
use App\Libraries\Schedule\GeneralAgendaReadService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

require_once APPPATH . 'Database/Migrations/2026-07-29-000006_ExpandGeneralAgendaFields.php';

/**
 * @internal
 */
final class GeneralAgendaCrudTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $testDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian CRUD database.');
        }

        $this->testDb = Database::connect('tests');
        $this->forge = Database::forge('tests');
        $this->forge->dropTable('agenda_umum', true);
        $this->createTable();
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->forge->dropTable('agenda_umum', true);
        }

        parent::tearDown();
    }

    public function testCreateFormOnlyContainsEssentialExternalAgendaFields(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/agenda-umum/create');

        $response->assertOK();
        $body = $response->response()->getBody();

        foreach ([
            'Audiensi / Penerimaan Aspirasi',
            'Aksi Unjuk Rasa / Demonstrasi',
            'Kunjungan Tamu atau Instansi',
            'Undangan / Agenda Luar Gedung',
            'Kegiatan Sosial dan Publik',
            'Lainnya',
        ] as $label) {
            $this->assertStringContainsString($label, $body);
        }

        $this->assertStringContainsString('name="pihak_eksternal"', $body);
        $this->assertStringContainsString('name="sumber_informasi"', $body);
        $this->assertStringContainsString('name="keterangan"', $body);
        $this->assertStringContainsString('name="is_publik"', $body);
        $this->assertStringNotContainsString('name="penanggung_jawab_internal"', $body);
        $this->assertStringNotContainsString('name="lingkup"', $body);
        $this->assertStringNotContainsString('name="status"', $body);
        $this->assertStringNotContainsString('name="perkiraan_peserta"', $body);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*id="is_publik"[^>]*checked/s', $body);
    }

    public function testMigrationSimplifiesLegacySchemaAndCanRollback(): void
    {
        $migration = new ExpandGeneralAgendaFields($this->forge);
        $migration->up();

        $this->assertTrue($this->testDb->fieldExists('pihak_eksternal', 'agenda_umum'));
        foreach (['penanggung_jawab_internal', 'lingkup', 'perkiraan_peserta', 'status'] as $field) {
            $this->assertFalse($this->testDb->fieldExists($field, 'agenda_umum'));
        }

        $row = $this->testDb->table('agenda_umum')->get()->getRowArray();
        $this->assertSame('Audiensi data lama', $row['judul']);
        $this->assertSame('audiensi', $row['kategori']);
        $this->assertSame(1, (int) $row['is_publik']);

        $migration->down();

        $this->assertFalse($this->testDb->fieldExists('pihak_eksternal', 'agenda_umum'));
        $this->assertTrue($this->testDb->fieldExists('perkiraan_peserta', 'agenda_umum'));
        $this->assertTrue($this->testDb->fieldExists('status', 'agenda_umum'));
        $rolledBack = $this->testDb->table('agenda_umum')->get()->getRowArray();
        $this->assertSame('Audiensi data lama', $rolledBack['judul']);
        $this->assertSame('audiensi_publik', $rolledBack['kategori']);
    }

    public function testStorePersistsEssentialFieldsAndDefaultsToInternal(): void
    {
        $this->testDb->table('agenda_umum')->truncate();

        $response = $this->postAgenda([
            'judul'            => 'Audiensi organisasi kepemudaan',
            'kategori'         => 'audiensi',
            'pihak_eksternal'  => 'Forum Pemuda Sulawesi Tengah',
            'tanggal'          => '2099-08-12',
            'waktu_mulai'      => '09:00',
            'waktu_selesai'    => '10:30',
            'lokasi'           => 'Ruang Rapat Utama',
            'sumber_informasi' => 'Surat Nomor 123/FP/VIII/2099',
            'keterangan'       => 'Perkiraan peserta 45 orang.',
        ]);

        $response->assertStatus(303);
        $row = $this->testDb->table('agenda_umum')->get()->getRowArray();
        $this->assertSame('audiensi', $row['kategori']);
        $this->assertSame('Forum Pemuda Sulawesi Tengah', $row['pihak_eksternal']);
        $this->assertSame('Perkiraan peserta 45 orang.', $row['keterangan']);
        $this->assertSame(0, (int) $row['is_publik']);
    }

    public function testStoreRejectsMissingExternalPartyAndInvalidCategory(): void
    {
        $this->testDb->table('agenda_umum')->truncate();

        $base = [
            'judul'            => 'Kunjungan instansi',
            'kategori'         => 'kunjungan',
            'pihak_eksternal'  => 'Pemerintah Kabupaten',
            'tanggal'          => '2099-08-12',
            'waktu_mulai'      => '09:00',
            'waktu_selesai'    => '10:00',
            'lokasi'           => 'Gedung DPRD',
        ];

        $this->postAgenda([...$base, 'pihak_eksternal' => ''])->assertStatus(422);
        $this->postAgenda([...$base, 'kategori' => 'audiensi_publik'])->assertStatus(422);
        $this->assertSame(0, $this->testDb->table('agenda_umum')->countAllResults());
    }

    public function testIndexReadsLegacyCategoryAndUsesSimpleDataTableFilters(): void
    {
        $this->testDb->table('agenda_umum')->update(['kategori' => 'audiensi_publik']);

        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/agenda-umum');

        $response->assertOK();
        $body = $response->response()->getBody();
        $this->assertStringContainsString('Audiensi data lama', $body);
        $this->assertStringContainsString('Audiensi / Penerimaan Aspirasi', $body);
        $this->assertStringContainsString('data-admin-datatable', $body);
        $this->assertStringContainsString('"label":"Jenis"', $body);
        $this->assertStringContainsString('"label":"Publikasi"', $body);
        $this->assertStringNotContainsString('"label":"Status"', $body);
        $this->assertStringNotContainsString('"label":"Lingkup"', $body);
    }

    public function testPublicApiExcludesInternalRowsAndReturnsEssentialMetadata(): void
    {
        $this->testDb->table('agenda_umum')->update([
            'pihak_eksternal' => 'Aliansi Masyarakat',
            'is_publik'       => 1,
        ]);
        $this->insertAgenda('Agenda internal', 0);

        $response = $this->get('/api/v1/publik/agenda-umum?month=2099-08');
        $response->assertOK();
        $payload = json_decode($response->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $payload['data']);
        $this->assertSame('Audiensi data lama', $payload['data'][0]['judul']);
        $this->assertSame('agenda_eksternal', $payload['data'][0]['source']);
        $this->assertSame('Aliansi Masyarakat', $payload['data'][0]['pihak_eksternal']);
        $this->assertArrayNotHasKey('penanggung_jawab_internal', $payload['data'][0]);
        $this->assertArrayNotHasKey('lingkup', $payload['data'][0]);
        $this->assertArrayNotHasKey('status', $payload['data'][0]);
        $this->assertArrayNotHasKey('perkiraan_peserta', $payload['data'][0]);
    }

    public function testMemberReadIncludesInternalExternalAgendaWithVisibilityMarker(): void
    {
        $this->testDb->table('agenda_umum')->update(['is_publik' => 1]);
        $this->insertAgenda('Agenda eksternal internal', 0);

        $result = (new GeneralAgendaReadService())->read(['month' => '2099-08'], true);

        $this->assertCount(2, $result['data']);
        $internal = array_values(array_filter(
            $result['data'],
            static fn (array $item): bool => $item['judul'] === 'Agenda eksternal internal',
        ))[0];
        $this->assertFalse($internal['is_public']);
        $this->assertSame('agenda_eksternal', $internal['source']);
    }

    private function postAgenda(array $payload)
    {
        return $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post('/admin/agenda-umum/store', [
                csrf_token() => csrf_hash(),
                ...$payload,
            ]);
    }

    private function insertAgenda(string $judul, int $isPublik): void
    {
        $this->testDb->table('agenda_umum')->insert([
            'judul'            => $judul,
            'kategori'         => 'kunjungan',
            'pihak_eksternal'  => 'Instansi Tamu',
            'tanggal'          => '2099-08-12',
            'waktu_mulai'      => '11:00:00',
            'waktu_selesai'    => '12:00:00',
            'lokasi'           => 'Gedung DPRD',
            'is_publik'        => $isPublik,
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

    private function createTable(): void
    {
        $this->forge->addField([
            'id'                          => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'                       => ['type' => 'VARCHAR', 'constraint' => 200],
            'kategori'                    => ['type' => 'VARCHAR', 'constraint' => 30],
            'pihak_eksternal'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'penanggung_jawab_internal'   => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'lingkup'                     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'tanggal'                     => ['type' => 'DATE'],
            'waktu_mulai'                 => ['type' => 'TIME'],
            'waktu_selesai'               => ['type' => 'TIME', 'null' => true],
            'lokasi'                      => ['type' => 'VARCHAR', 'constraint' => 200],
            'sumber_informasi'            => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'perkiraan_peserta'           => ['type' => 'INTEGER', 'null' => true],
            'keterangan'                  => ['type' => 'TEXT', 'null' => true],
            'status'                      => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'tentatif'],
            'is_publik'                   => ['type' => 'INTEGER', 'default' => 1],
            'created_at'                  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('agenda_umum');
        $this->testDb->table('agenda_umum')->insert([
            'judul'         => 'Audiensi data lama',
            'kategori'      => 'audiensi_publik',
            'tanggal'       => '2099-08-12',
            'waktu_mulai'   => '09:00:00',
            'waktu_selesai' => '10:00:00',
            'lokasi'        => 'Gedung DPRD',
            'status'        => 'tentatif',
            'is_publik'     => 1,
        ]);
    }
}
