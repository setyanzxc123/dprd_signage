<?php

use App\Libraries\Notulen\NotulenService;
use App\Models\MeetingMinutesModel;
use App\Models\MeetingTranscriptionJobModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian komprehensif modul Notulensi & Risalah AI:
 * 1. Web Admin Controller & Flow (Index, Polling, Update, Finalize, Retry, Cancel)
 * 2. Mobile REST API Endpoint (/api/v1/jadwal/{sumber}/{id}/risalah)
 *
 * @internal
 */
final class NotulenCrudAndApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const MEMBER_TOKEN = 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd';

    private BaseConnection $testDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian Notulen.');
        }

        $this->testDb = Database::connect('tests');
        $this->forge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedIdentities();
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->dropTables();
        }

        parent::tearDown();
    }

    public function testWebAdminRequiresAuthentication(): void
    {
        $loginUrl = base_url('login?akses=admin');
        $this->get('/admin/notulen')->assertRedirectTo($loginUrl);
        $this->get('/admin/notulen/1')->assertRedirectTo($loginUrl);
    }

    public function testWebAdminIndexRendersJobsAndStatus(): void
    {
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'               => 1,
            'jadwal_type'      => 'umum',
            'jadwal_id'        => 10,
            'audio_filename'   => 'rapat_dengar_pendapat.mp3',
            'audio_path'       => 'recordings/job_1/audio/original.mp3',
            'audio_size'       => 15000000,
            'status'           => 'completed',
            'progress_percent' => 100,
            'current_step'     => 'Selesai',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_minutes')->insert([
            'job_id'              => 1,
            'jadwal_type'         => 'umum',
            'jadwal_id'           => 10,
            'judul_rapat'         => 'RDP Komisi I',
            'tanggal_rapat'       => '2026-08-27',
            'transcripts_dir'     => 'recordings/job_1/transcripts',
            'ringkasan_eksekutif' => 'Ringkasan pembahasan RDP...',
            'agenda_pembahasan'   => json_encode([['topik' => 'Evaluasi', 'uraian' => 'Penjelasan...']]),
            'kesimpulan'          => json_encode(['Poin kesimpulan']),
            'tindak_lanjut'       => json_encode(['Poin tindak lanjut']),
            'peserta_terdeteksi'  => json_encode(['Ketua Komisi I']),
            'status_verifikasi'   => 'draft',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        $response = $this->adminGet('/admin/notulen');

        $response->assertOK();
        $response->assertSee('Notulensi');
        $response->assertSee('Risalah AI');
        $response->assertSee('RDP Komisi I');
        $response->assertSee('rapat_dengar_pendapat.mp3');
    }

    public function testStatusAjaxEndpointReturnsJobProgress(): void
    {
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'               => 2,
            'jadwal_type'      => 'umum',
            'audio_filename'   => 'sidang_paripurna.mp3',
            'status'           => 'transcribing',
            'progress_percent' => 45,
            'current_step'     => 'Mentranskripsikan chunk 2/4...',
            'total_chunks'     => 4,
            'completed_chunks' => 1,
            'cancel_requested' => 0,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $response = $this->adminGet('/admin/notulen/status/2');

        $response->assertOK();
        $json = json_decode($response->response()->getBody(), true);

        $this->assertSame('success', $json['status']);
        $this->assertSame(2, $json['data']['id']);
        $this->assertSame('transcribing', $json['data']['status']);
        $this->assertSame(45, $json['data']['progress_percent']);
        $this->assertSame(4, $json['data']['total_chunks']);
        $this->assertSame(1, $json['data']['completed_chunks']);
    }

    public function testUpdateMinutesAndFinalize(): void
    {
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'             => 3,
            'jadwal_type'    => 'umum',
            'audio_filename' => 'rapat_komisi.mp3',
            'status'         => 'completed',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_minutes')->insert([
            'id'                  => 10,
            'job_id'              => 3,
            'jadwal_type'         => 'umum',
            'judul_rapat'         => 'Judul Awal',
            'tanggal_rapat'       => '2026-08-20',
            'ringkasan_eksekutif' => 'Ringkasan awal',
            'agenda_pembahasan'   => json_encode([]),
            'kesimpulan'          => json_encode([]),
            'tindak_lanjut'       => json_encode([]),
            'peserta_terdeteksi'  => json_encode([]),
            'status_verifikasi'   => 'draft',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        // 1. Update minutes fields
        $updateResp = $this->adminPost('/admin/notulen/update-minutes/10', [
            'judul_rapat'         => 'Judul Rapat Baru Direvisi',
            'tanggal_rapat'       => '2026-08-25',
            'ringkasan_eksekutif' => 'Ringkasan eksekutif telah diperbaiki.',
            'agenda_pembahasan'   => json_encode([['topik' => 'Revisi Anggaran', 'uraian' => 'Uraian detail']]),
            'kesimpulan'          => json_encode(['Disetujui bersama']),
            'tindak_lanjut'       => json_encode(['Diteruskan ke Banggar']),
            'peserta_terdeteksi'  => json_encode(['Anggota A', 'Anggota B']),
        ]);

        $updateResp->assertStatus(302);

        $rowAfterUpdate = (new MeetingMinutesModel($this->testDb))->find(10);
        $this->assertSame('Judul Rapat Baru Direvisi', $rowAfterUpdate['judul_rapat']);
        $this->assertSame('2026-08-25', $rowAfterUpdate['tanggal_rapat']);
        $this->assertSame('Ringkasan eksekutif telah diperbaiki.', $rowAfterUpdate['ringkasan_eksekutif']);

        // 2. Finalize minutes
        $finalizeResp = $this->adminPost('/admin/notulen/finalize/10', []);

        $finalizeResp->assertStatus(302);

        $rowFinal = (new MeetingMinutesModel($this->testDb))->find(10);
        $this->assertSame('final', $rowFinal['status_verifikasi']);
        $this->assertNotNull($rowFinal['verified_at']);
    }

    public function testRetryAndCancelEndpoints(): void
    {
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'               => 4,
            'jadwal_type'      => 'umum',
            'audio_filename'   => 'job_gagal.mp3',
            'status'           => 'failed',
            'error_message'    => 'Koneksi error',
            'cancel_requested' => 0,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        // Retry failed job
        $retryResp = $this->adminPost('/admin/notulen/retry/4', []);

        $retryResp->assertStatus(302);

        $jobAfterRetry = (new MeetingTranscriptionJobModel($this->testDb))->find(4);
        $this->assertSame('queued', $jobAfterRetry['status']);
        $this->assertNull($jobAfterRetry['error_message']);

        // Cancel queued job
        $cancelResp = $this->adminPost('/admin/notulen/cancel/4', []);

        $cancelResp->assertStatus(302);

        $jobAfterCancel = (new MeetingTranscriptionJobModel($this->testDb))->find(4);
        $this->assertSame('cancelled', $jobAfterCancel['status']);
    }

    public function testMobileApiRejectsUnauthenticated(): void
    {
        $this->get('/api/v1/jadwal/umum/1/risalah')->assertStatus(401);
        $this->withHeaders(['Authorization' => 'Bearer invalid-token'])
            ->get('/api/v1/jadwal/umum/1/risalah')
            ->assertStatus(401);
    }

    public function testMobileApiReturnsNotAvailableWhenDraft(): void
    {
        $this->testDb->table('meeting_minutes')->insert([
            'job_id'              => 5,
            'jadwal_type'         => 'umum',
            'jadwal_id'           => 20,
            'judul_rapat'         => 'Rapat Dengar Pendapat',
            'ringkasan_eksekutif' => 'Ringkasan draft',
            'status_verifikasi'   => 'draft',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get('/api/v1/jadwal/umum/20/risalah');

        $response->assertOK();
        $json = json_decode($response->response()->getBody(), true);

        $this->assertSame('success', $json['status']);
        $this->assertFalse($json['risalah_tersedia']);
        $this->assertSame('draft', $json['status_verifikasi']);
        $this->assertNull($json['risalah']);
    }

    public function testMobileApiReturnsFullMinutesWhenFinal(): void
    {
        $this->testDb->table('meeting_minutes')->insert([
            'job_id'              => 6,
            'jadwal_type'         => 'umum',
            'jadwal_id'           => 21,
            'judul_rapat'         => 'Sidang Paripurna Pembahasan APBD',
            'tanggal_rapat'       => '2026-08-27',
            'ringkasan_eksekutif' => 'Sidang paripurna menyetujui seluruh rancangan anggaran belanja daerah...',
            'agenda_pembahasan'   => json_encode([
                ['topik' => 'Anggaran Pendidikan', 'uraian' => 'Alokasi minimal 20% terpenuhi.', 'pembicara' => 'Fraksi A'],
            ]),
            'kesimpulan'          => json_encode(['Rancangan disahkan']),
            'tindak_lanjut'       => json_encode(['Penyampaian ke Kemendagri']),
            'peserta_terdeteksi'  => json_encode(['Ketua DPRD', 'Gubernur']),
            'status_verifikasi'   => 'final',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . self::MEMBER_TOKEN])
            ->get('/api/v1/jadwal/umum/21/risalah');

        $response->assertOK();
        $json = json_decode($response->response()->getBody(), true);

        $this->assertSame('success', $json['status']);
        $this->assertTrue($json['risalah_tersedia']);
        $this->assertSame('final', $json['status_verifikasi']);
        $this->assertSame('Sidang Paripurna Pembahasan APBD', $json['judul_rapat']);
        $this->assertSame('2026-08-27', $json['tanggal_rapat']);
        $this->assertCount(1, $json['agenda_pembahasan']);
        $this->assertSame('Anggaran Pendidikan', $json['agenda_pembahasan'][0]['topik']);
        $this->assertCount(1, $json['kesimpulan']);
        $this->assertSame('Rancangan disahkan', $json['kesimpulan'][0]);
        $this->assertCount(2, $json['peserta_terdeteksi']);
    }

    private function adminGet(string $path)
    {
        return $this->withSession(['auth_user' => ['id' => 1, 'name' => 'Administrator', 'username' => 'admin']])->get($path);
    }

    private function adminPost(string $path, array $payload)
    {
        return $this->withSession(['auth_user' => ['id' => 1, 'name' => 'Administrator', 'username' => 'admin']])->post($path, [
            csrf_token() => csrf_hash(),
            ...$payload,
        ]);
    }

    private function seedIdentities(): void
    {
        $this->testDb->table('users')->insert([
            'id'         => 1,
            'username'   => 'admin',
            'name'       => 'Administrator',
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->testDb->table('auth_groups_users')->insert([
            'user_id'    => 1,
            'group'      => 'superadmin',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('users')->insert([
            'id'         => 2,
            'username'   => 'anggota_1',
            'name'       => 'Anggota Uji',
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->testDb->table('auth_groups_users')->insert([
            'user_id'    => 2,
            'group'      => 'anggota',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->testDb->table('auth_identities')->insert([
            'user_id'    => 2,
            'type'       => 'access_token',
            'name'       => 'mobile',
            'secret'     => hash('sha256', self::MEMBER_TOKEN),
            'extra'      => json_encode(['scopes' => ['agenda.read', 'resource.read']]),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->testDb->table('anggota')->insert([
            'id'      => 1,
            'user_id' => 2,
            'name'    => 'Anggota Uji',
            'no_wa'   => '08123456789',
            'aktif'   => 1,
        ]);
    }

    private function createTables(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'username'       => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status_message' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'active'         => ['type' => 'INTEGER', 'default' => 1],
            'last_active'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('users');

        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'    => ['type' => 'INTEGER'],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('auth_groups_users');

        $this->forge->addField([
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
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('auth_identities');

        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'id_type'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'identifier' => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_id'    => ['type' => 'INTEGER', 'null' => true],
            'date'       => ['type' => 'DATETIME'],
            'success'    => ['type' => 'INTEGER'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('auth_token_logins');

        $this->forge->addField([
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
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('anggota');

        $this->forge->addField([
            'id'          => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal'     => ['type' => 'DATE'],
            'waktu_mulai' => ['type' => 'TIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal_umum');

        $this->forge->addField([
            'id'          => ['type' => 'INTEGER', 'auto_increment' => true],
            'agenda'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal'     => ['type' => 'DATE', 'null' => true],
            'jam_mulai'   => ['type' => 'TIME', 'null' => true],
            'jam_selesai' => ['type' => 'TIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal_banmus');

        $this->forge->addField([
            'id'               => ['type' => 'INTEGER', 'auto_increment' => true],
            'jadwal_type'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'jadwal_id'        => ['type' => 'INTEGER', 'null' => true],
            'audio_filename'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'audio_path'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'audio_size'       => ['type' => 'BIGINT', 'default' => 0],
            'audio_duration'   => ['type' => 'INTEGER', 'null' => true],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'progress_percent' => ['type' => 'INTEGER', 'default' => 0],
            'current_step'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'total_chunks'     => ['type' => 'INTEGER', 'default' => 0],
            'completed_chunks' => ['type' => 'INTEGER', 'default' => 0],
            'cancel_requested' => ['type' => 'INTEGER', 'default' => 0],
            'error_message'    => ['type' => 'TEXT', 'null' => true],
            'created_by'       => ['type' => 'INTEGER', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('meeting_transcription_jobs');

        $this->forge->addField([
            'id'                  => ['type' => 'INTEGER', 'auto_increment' => true],
            'job_id'              => ['type' => 'INTEGER', 'null' => true],
            'jadwal_type'         => ['type' => 'VARCHAR', 'constraint' => 20],
            'jadwal_id'           => ['type' => 'INTEGER', 'null' => true],
            'judul_rapat'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal_rapat'       => ['type' => 'DATE', 'null' => true],
            'transcripts_dir'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'ringkasan_eksekutif' => ['type' => 'TEXT', 'null' => true],
            'agenda_pembahasan'   => ['type' => 'TEXT', 'null' => true],
            'kesimpulan'          => ['type' => 'TEXT', 'null' => true],
            'tindak_lanjut'       => ['type' => 'TEXT', 'null' => true],
            'peserta_terdeteksi'  => ['type' => 'TEXT', 'null' => true],
            'status_verifikasi'   => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'verified_by'         => ['type' => 'INTEGER', 'null' => true],
            'verified_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('meeting_minutes');
    }

    private function dropTables(): void
    {
        foreach ([
            'meeting_minutes',
            'meeting_transcription_jobs',
            'jadwal_banmus',
            'jadwal_umum',
            'anggota',
            'auth_token_logins',
            'auth_identities',
            'auth_groups_users',
            'users',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
