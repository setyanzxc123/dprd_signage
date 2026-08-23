<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian endpoint API resolve materi/stream jadwal (Fase 5):
 * otorisasi sesi/bearer anggota dan aturan akses resource yang
 * menempuh ScheduleResourceLinkService — identik versi web.
 *
 * @internal
 */
final class ApiScheduleResourceTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const MEMBER_TOKEN = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

    private BaseConnection $apiDb;
    private Forge $apiForge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian API resource jadwal.');
        }

        $this->apiDb = Database::connect('tests');
        $this->apiForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedIdentities();
    }

    protected function tearDown(): void
    {
        if (isset($this->apiForge)) {
            $this->dropTables();
        }

        parent::tearDown();
    }

    public function testEndpointRequiresMemberIdentity(): void
    {
        $this->get('/api/v1/jadwal/banmus/1/materi')->assertStatus(401);
        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get('/api/v1/jadwal/banmus/1/materi')
            ->assertStatus(401);
    }

    public function testResolvesAccessibleResourceUrl(): void
    {
        $this->apiDb->table('jadwal_umum')->insert([
            'judul'        => 'Sosialisasi Anggaran',
            'materi_url'   => 'https://example.com/materi-umum.pdf',
            'materi_akses' => 'anggota',
            'stream_url'   => 'https://example.com/live-umum',
            'stream_akses' => 'anggota',
            'is_publik'    => 0,
        ]);
        $id = (int) $this->apiDb->insertID();

        $materi = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get("/api/v1/jadwal/jadwal-umum/{$id}/materi");
        $materi->assertOK();
        $body = json_decode((string) $materi->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('https://example.com/materi-umum.pdf', $body['url']);

        $stream = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get("/api/v1/jadwal/jadwal-umum/{$id}/stream");
        $stream->assertOK();
        $this->assertSame(
            'https://example.com/live-umum',
            json_decode((string) $stream->response()->getBody(), true)['url'],
        );
    }

    public function testParticipantResourceFollowsUnitRelation(): void
    {
        $this->apiDb->table('dokumen_banmus')->insert(['is_publik' => 1]);
        $this->apiDb->table('jadwal_banmus')->insert([
            'dokumen_banmus_id' => (int) $this->apiDb->insertID(),
            'jenis_agenda'      => 'rapat',
            'status'            => 'menunggu',
            'publikasi'         => 'publik',
            'materi_url'        => 'https://example.com/bahan-rapat.pdf',
            'materi_akses'      => 'peserta',
            'stream_url'        => null,
            'stream_akses'      => 'anggota',
            'deleted_at'        => null,
        ]);
        $scheduleId = (int) $this->apiDb->insertID();

        $denied = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get("/api/v1/jadwal/banmus/{$scheduleId}/materi");
        $denied->assertStatus(403);
        $this->assertSame('error', json_decode((string) $denied->response()->getBody(), true)['status']);

        $this->apiDb->table('jadwal_banmus_unit_rapat')->insert([
            'jadwal_banmus_id' => $scheduleId,
            'unit_rapat_id'    => 7,
        ]);
        $this->apiDb->table('anggota_unit_rapat')->insert([
            'anggota_id'    => 1,
            'unit_rapat_id' => 7,
        ]);

        $allowed = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get("/api/v1/jadwal/banmus/{$scheduleId}/materi");
        $allowed->assertOK();
        $this->assertSame(
            'https://example.com/bahan-rapat.pdf',
            json_decode((string) $allowed->response()->getBody(), true)['url'],
        );
    }

    public function testUnknownSourceAndMissingResourceAreRejected(): void
    {
        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get('/api/v1/jadwal/does-not-exist/1/materi')
            ->assertStatus(404);

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get('/api/v1/jadwal/banmus/999/materi')
            ->assertStatus(403);

        $this->apiDb->table('jadwal_umum')->insert([
            'judul'        => 'Rapat Tanpa Stream',
            'materi_url'   => 'https://example.com/materi.pdf',
            'materi_akses' => 'anggota',
            'stream_url'   => null,
            'stream_akses' => 'anggota',
            'is_publik'    => 0,
        ]);
        $id = (int) $this->apiDb->insertID();

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get("/api/v1/jadwal/jadwal-umum/{$id}/stream")
            ->assertStatus(403);
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
            'name'     => 'Anggota API',
            'aktif'    => 1,
            'user_id'  => $userId,
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
            'id'        => ['type' => 'INTEGER', 'auto_increment' => true],
            'is_publik' => ['type' => 'INTEGER', 'default' => 0],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('dokumen_banmus');

        $this->apiForge->addField([
            'id'                  => ['type' => 'INTEGER', 'auto_increment' => true],
            'dokumen_banmus_id'   => ['type' => 'INTEGER'],
            'jenis_agenda'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'status'              => ['type' => 'VARCHAR', 'constraint' => 20],
            'publikasi'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'materi_url'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'materi_akses'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'stream_url'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'stream_akses'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('jadwal_banmus');

        $this->apiForge->addField([
            'jadwal_banmus_id' => ['type' => 'INTEGER'],
            'unit_rapat_id'    => ['type' => 'INTEGER'],
        ]);
        $this->apiForge->createTable('jadwal_banmus_unit_rapat');

        $this->apiForge->addField([
            'anggota_id'    => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
        ]);
        $this->apiForge->createTable('anggota_unit_rapat');

        $this->apiForge->addField([
            'id'           => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'materi_url'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'materi_akses' => ['type' => 'VARCHAR', 'constraint' => 20],
            'stream_url'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'stream_akses' => ['type' => 'VARCHAR', 'constraint' => 20],
            'is_publik'    => ['type' => 'INTEGER', 'default' => 0],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('jadwal_umum');
    }

    private function dropTables(): void
    {
        foreach ([
            'jadwal_umum',
            'anggota_unit_rapat',
            'jadwal_banmus_unit_rapat',
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
