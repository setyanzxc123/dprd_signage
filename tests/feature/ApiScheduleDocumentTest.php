<?php

use App\Libraries\Schedule\ScheduleInvitationStorage;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian endpoint API download dokumen terautentikasi (Fase 5):
 * undangan rapat (anggota; banmus wajib rapat + terjadwal) dan
 * dokumen SK banmus (is_publik atau anggota). Aturan akses identik
 * versi web karena menempuh ScheduleDocumentService.
 *
 * @internal
 */
final class ApiScheduleDocumentTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const MEMBER_TOKEN = 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd';
    private const INVITATION_FILE = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.pdf';
    private const SK_FILE = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb.pdf';

    private BaseConnection $apiDb;
    private Forge $apiForge;

    /** @var list<string> */
    private array $fixturePaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian API dokumen.');
        }

        $this->apiDb = Database::connect('tests');
        $this->apiForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedIdentities();
    }

    protected function tearDown(): void
    {
        foreach ($this->fixturePaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (isset($this->apiForge)) {
            $this->dropTables();
        }

        parent::tearDown();
    }

    public function testUndanganRequiresMemberIdentity(): void
    {
        $this->apiDb->table('jadwal_umum')->insert([
            'judul'               => 'Rapat Koordinasi',
            'undangan_file'       => self::INVITATION_FILE,
            'undangan_nama_asli'  => 'Undangan Rapat Koordinasi.pdf',
        ]);
        $id = (int) $this->apiDb->insertID();

        $this->get("/api/v1/jadwal/jadwal-umum/{$id}/undangan")->assertStatus(401);
        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get("/api/v1/jadwal/jadwal-umum/{$id}/undangan")
            ->assertStatus(401);
    }

    public function testUndanganStreamsPdfForMember(): void
    {
        $contents = "%PDF-1.4\nundangan-fixture\n%%EOF\n";
        $this->writeInvitationFixture(self::INVITATION_FILE, $contents);
        $this->apiDb->table('jadwal_umum')->insert([
            'judul'              => 'Rapat Koordinasi',
            'undangan_file'      => self::INVITATION_FILE,
            'undangan_nama_asli' => 'Undangan Rapat Koordinasi.pdf',
        ]);
        $id = (int) $this->apiDb->insertID();

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get("/api/v1/jadwal/jadwal-umum/{$id}/undangan");

        $response->assertOK();
        $this->assertSame('application/pdf; charset=UTF-8', $response->response()->getHeaderLine('Content-Type'));
        $this->assertSame(
            'inline; filename="Undangan Rapat Koordinasi.pdf"',
            $response->response()->getHeaderLine('Content-Disposition'),
        );
        $this->assertSame($contents, (string) $response->response()->getBody());
    }

    public function testBanmusInvitationRequiresScheduledMeeting(): void
    {
        $this->writeInvitationFixture(self::INVITATION_FILE, "%PDF-1.4\n");
        $this->apiDb->table('dokumen_banmus')->insert(['is_publik' => 1]);
        $documentId = (int) $this->apiDb->insertID();

        $this->apiDb->table('jadwal_banmus')->insert([
            'dokumen_banmus_id'  => $documentId,
            'jenis_agenda'       => 'rapat',
            'status'             => 'proyeksi',
            'undangan_file'      => self::INVITATION_FILE,
            'undangan_nama_asli' => 'Undangan Proyeksi.pdf',
            'deleted_at'         => null,
        ]);
        $projectionId = (int) $this->apiDb->insertID();
        $this->apiDb->table('jadwal_banmus')->insert([
            'dokumen_banmus_id'  => $documentId,
            'jenis_agenda'       => 'rapat',
            'status'             => 'menunggu',
            'undangan_file'      => self::INVITATION_FILE,
            'undangan_nama_asli' => 'Undangan Rapat.pdf',
            'deleted_at'         => null,
        ]);
        $scheduledId = (int) $this->apiDb->insertID();

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get("/api/v1/jadwal/banmus/{$projectionId}/undangan")
            ->assertStatus(404);

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get("/api/v1/jadwal/banmus/{$scheduledId}/undangan")
            ->assertOK();
    }

    public function testSkDocumentStreamsForAnonymousWhenPublic(): void
    {
        $contents = "%PDF-1.4\nsk-publik-fixture\n%%EOF\n";
        $this->writeSkFixture(self::SK_FILE, $contents);
        $this->apiDb->table('dokumen_banmus')->insert([
            'is_publik'         => 1,
            'dokumen_file'      => self::SK_FILE,
            'dokumen_nama_asli' => 'SK Banmus Semester 1.pdf',
            'dokumen_url'       => null,
        ]);
        $id = (int) $this->apiDb->insertID();

        $response = $this->get("/api/v1/dokumen-banmus/{$id}");

        $response->assertOK();
        $this->assertSame('application/pdf; charset=UTF-8', $response->response()->getHeaderLine('Content-Type'));
        $this->assertSame($contents, (string) $response->response()->getBody());
    }

    public function testInternalSkDocumentRequiresMemberIdentity(): void
    {
        $contents = "%PDF-1.4\nsk-internal-fixture\n%%EOF\n";
        $this->writeSkFixture(self::SK_FILE, $contents);
        $this->apiDb->table('dokumen_banmus')->insert([
            'is_publik'         => 0,
            'dokumen_file'      => self::SK_FILE,
            'dokumen_nama_asli' => 'SK Internal.pdf',
            'dokumen_url'       => null,
        ]);
        $id = (int) $this->apiDb->insertID();

        $denied = $this->get("/api/v1/dokumen-banmus/{$id}");
        $denied->assertStatus(403);
        $this->assertSame('error', json_decode((string) $denied->response()->getBody(), true)['status']);

        $allowed = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get("/api/v1/dokumen-banmus/{$id}");
        $allowed->assertOK();
        $this->assertSame($contents, (string) $allowed->response()->getBody());
    }

    public function testSkDocumentExternalUrlRedirects(): void
    {
        $this->apiDb->table('dokumen_banmus')->insert([
            'is_publik'   => 1,
            'dokumen_url' => 'https://drive.example.com/sk-banmus.pdf',
        ]);
        $id = (int) $this->apiDb->insertID();

        $response = $this->get("/api/v1/dokumen-banmus/{$id}");

        $response->assertRedirect();
        $this->assertSame('https://drive.example.com/sk-banmus.pdf', $response->response()->getHeaderLine('Location'));
    }

    public function testMissingDocumentsAreRejected(): void
    {
        $this->get('/api/v1/dokumen-banmus/999')->assertStatus(404);

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get('/api/v1/jadwal/banmus/999/undangan')
            ->assertStatus(404);

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get('/api/v1/jadwal/tidak-dikenal/1/undangan')
            ->assertStatus(404);
    }

    private function writeInvitationFixture(string $fileName, string $contents): void
    {
        $directory = (new ScheduleInvitationStorage())->directory();
        if (! is_dir($directory)) {
            mkdir($directory, 0750, true);
        }
        $path = $directory . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($path, $contents);
        $this->fixturePaths[] = $path;
    }

    private function writeSkFixture(string $fileName, string $contents): void
    {
        $directory = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'sk-banmus';
        if (! is_dir($directory)) {
            mkdir($directory, 0750, true);
        }
        $path = $directory . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($path, $contents);
        $this->fixturePaths[] = $path;
    }

    /** Menerbitkan access token Shield untuk pengujian. */
    private function issueToken(int $userId, string $rawToken): void
    {
        $this->apiDb->table('auth_identities')->insert([
            'user_id'    => $userId,
            'type'       => 'access_token',
            'name'       => 'test',
            'secret'     => hash('sha256', $rawToken),
            'extra'      => serialize(['agenda.read', 'resource.read']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function seedIdentities(): void
    {
        $this->apiDb->table('users')->insert([
            'username' => 'anggota-api',
            'name'     => 'Anggota API',
            'active'   => 1,
        ]);
        $userId = (int) $this->apiDb->insertID();
        $this->apiDb->table('auth_groups_users')->insert([
            'user_id' => $userId, 'group' => 'anggota', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->issueToken($userId, self::MEMBER_TOKEN);

        $this->apiDb->table('anggota')->insert([
            'name'    => 'Anggota API',
            'aktif'   => 1,
            'user_id' => $userId,
        ]);
    }

    private function createTables(): void
    {
        $this->apiForge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'username'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status_message' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'active'         => ['type' => 'INTEGER', 'default' => 0],
            'last_active'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('users');

        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'    => ['type' => 'INTEGER'],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_groups_users');

        $this->apiForge->addField([
            'id'           => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'      => ['type' => 'INTEGER'],
            'type'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'secret'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'secret2'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'expires'      => ['type' => 'DATETIME', 'null' => true],
            'extra'        => ['type' => 'TEXT', 'null' => true],
            'force_reset'  => ['type' => 'INTEGER', 'default' => 0],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_identities');

        // Shield mencatat percobaan login token (termasuk yang gagal).
        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'id_type'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'identifier' => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_id'    => ['type' => 'INTEGER', 'null' => true],
            'date'       => ['type' => 'DATETIME'],
            'success'    => ['type' => 'INTEGER'],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_token_logins');

        $this->apiForge->addField([
            'id'            => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'jabatan'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fraksi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'komisi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_wa'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif'         => ['type' => 'INTEGER', 'default' => 1],
            'foto'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'user_id'       => ['type' => 'INTEGER', 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('anggota');

        $this->apiForge->addField([
            'id'                => ['type' => 'INTEGER', 'auto_increment' => true],
            'is_publik'         => ['type' => 'INTEGER', 'default' => 0],
            'dokumen_file'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dokumen_nama_asli' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dokumen_url'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('dokumen_banmus');

        $this->apiForge->addField([
            'id'                  => ['type' => 'INTEGER', 'auto_increment' => true],
            'dokumen_banmus_id'   => ['type' => 'INTEGER'],
            'jenis_agenda'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'status'              => ['type' => 'VARCHAR', 'constraint' => 20],
            'undangan_file'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'undangan_nama_asli'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('jadwal_banmus');

        $this->apiForge->addField([
            'id'                 => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'undangan_file'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'undangan_nama_asli' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('jadwal_umum');
    }

    private function dropTables(): void
    {
        foreach ([
            'jadwal_umum',
            'jadwal_banmus',
            'dokumen_banmus',
            'anggota',
            'auth_token_logins',
            'auth_identities',
            'auth_groups_users',
            'users',
        ] as $table) {
            $this->apiForge->dropTable($table, true);
        }
    }
}
