<?php

use App\Libraries\Notulen\PostChunkAudioUpload;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian endpoint read-only notulensi API (pola ApiRuanganCrudTest):
 * otorisasi bearer per grup, daftar job berpaginasi, polling status,
 * transkrip per segmen, dan streaming audio dengan Range.
 *
 * @internal
 */
final class ApiNotulenReadTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const ADMIN_TOKEN   = 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee';
    private const ANGGOTA_TOKEN = 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff';

    private BaseConnection $apiDb;
    private Forge $apiForge;
    private string $jobFixturesDir;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian API notulen.');
        }

        $this->apiDb = Database::connect('tests');
        $this->apiForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedIdentities();
        $this->seedJobFixtures();
    }

    protected function tearDown(): void
    {
        if (isset($this->apiForge)) {
            $this->dropTables();
        }

        if (isset($this->jobFixturesDir) && is_dir($this->jobFixturesDir)) {
            $this->removeDir($this->jobFixturesDir);
        }

        parent::tearDown();
    }

    public function testEndpointRequiresBearerToken(): void
    {
        $this->get('/api/v1/notulen/jobs')->assertStatus(401);
        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get('/api/v1/notulen/jobs')
            ->assertStatus(401);
    }

    public function testAnggotaTokenIsForbidden(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ANGGOTA_TOKEN])
            ->get('/api/v1/notulen/jobs');

        $response->assertStatus(403);
        $this->assertSame('error', json_decode((string) $response->response()->getBody(), true)['status']);
    }

    public function testListJobsReturnsPaginatedDataWithScheduleAndRisalahStatus(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/notulen/jobs');

        $response->assertOK();
        $body = json_decode((string) $response->response()->getBody(), true);

        $this->assertSame('success', $body['status']);
        $this->assertSame(2, $body['meta']['total']);
        $this->assertSame(40, $body['data'][0]['id'], 'urutan DESC: job terbaru lebih dulu');
        $this->assertSame('transcribing', $body['data'][0]['status']);
        $this->assertSame('final', $body['data'][0]['risalah_status']);
        $this->assertNull($body['data'][0]['jadwal'], 'job tanpa jadwal_id tidak mereferensi jadwal');
        $this->assertSame('draft', $body['data'][1]['risalah_status']);
    }

    public function testListJobsSearchesByFilename(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/notulen/jobs?q=paripurna');

        $response->assertOK();
        $body = json_decode((string) $response->response()->getBody(), true);

        $this->assertSame(1, $body['meta']['total']);
        $this->assertSame('sidang_paripurna.mp3', $body['data'][0]['audio_filename']);
    }

    public function testShowJobReturnsProgressPayload(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/notulen/jobs/40');

        $response->assertOK();
        $data = json_decode((string) $response->response()->getBody(), true)['data'];

        $this->assertSame(40, $data['id']);
        $this->assertSame('transcribing', $data['status']);
        $this->assertSame(45, $data['progress_percent']);
        $this->assertSame(2, $data['completed_chunks']);
        $this->assertFalse($data['cancel_requested']);
    }

    public function testShowMissingJobReturns404(): void
    {
        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/notulen/jobs/9999')
            ->assertStatus(404);
    }

    public function testTranskripReturnsChunksAndFullText(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/notulen/jobs/40/transkrip');

        $response->assertOK();
        $data = json_decode((string) $response->response()->getBody(), true)['data'];

        $this->assertSame(2, $data['total_chunks']);
        $this->assertCount(2, $data['chunks']);
        $this->assertSame('Menit 00:00 - 30:00', $data['chunks'][0]['time_label']);
        $this->assertStringContainsString('=== BAGIAN 1', $data['full_text']);
    }

    public function testAudioSupportsRangeRequests(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN, 'Range' => 'bytes=0-99'])
            ->get('/api/v1/notulen/jobs/40/audio');

        $response->assertStatus(206);
        $response->assertHeader('Content-Range', 'bytes 0-99/5000');
        $this->assertSame(100, strlen((string) $response->response()->getBody()));
    }

    public function testRetryFailedJobReturnsQueued(): void
    {
        $this->apiDb->table('meeting_transcription_jobs')->where('id', 39)->update(['status' => 'failed']);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/notulen/jobs/39/retry');

        $response->assertOK();
        $data = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('success', $data['status']);
        $this->assertSame('queued', $data['data']['status']);
        $this->assertSame('queued', $this->apiDb->table('meeting_transcription_jobs')->where('id', 39)->get()->getRowArray()['status']);
    }

    public function testRetryCompletedJobIsRejected(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/notulen/jobs/39/retry');

        $response->assertStatus(422);
        $this->assertSame('error', json_decode((string) $response->response()->getBody(), true)['status']);
    }

    public function testCancelCompletedJobIsRejected(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/notulen/jobs/39/cancel');

        $response->assertStatus(422);
        $this->assertSame('error', json_decode((string) $response->response()->getBody(), true)['status']);
    }

    public function testCancelQueuedJobSetsCancelled(): void
    {
        $this->apiDb->table('meeting_transcription_jobs')
            ->where('id', 39)
            ->update(['status' => 'queued', 'cancel_requested' => 0]);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/notulen/jobs/39/cancel');

        $response->assertOK();
        $row = $this->apiDb->table('meeting_transcription_jobs')->where('id', 39)->get()->getRowArray();
        $this->assertSame('cancelled', $row['status']);
    }

    public function testPurgeRecordingRejectedWhileJobRunning(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->delete('/api/v1/notulen/jobs/40/rekaman');

        $response->assertStatus(422);
        $this->assertSame('error', json_decode((string) $response->response()->getBody(), true)['status']);
    }

    public function testDeleteJobRemovesRow(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->delete('/api/v1/notulen/jobs/39');

        $response->assertOK();
        $this->assertSame(0, $this->apiDb->table('meeting_transcription_jobs')->where('id', 39)->countAllResults());
        $this->assertSame(0, $this->apiDb->table('meeting_minutes')->where('job_id', 39)->countAllResults());
    }

    public function testShowMinutesReturnsPillars(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/notulen/risalah/1');

        $response->assertOK();
        $data = json_decode((string) $response->response()->getBody(), true)['data'];

        $this->assertSame('final', $data['status_verifikasi']);
        $this->assertSame('Rapat membahas APBD.', $data['tiga_pilar']['ringkasan_utama']);
        $this->assertSame('Anggaran', $data['tiga_pilar']['poin_pembahasan'][0]['topik']);
    }

    public function testShowMissingMinutesReturns404(): void
    {
        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/notulen/risalah/999')
            ->assertStatus(404);
    }

    public function testUpdateFinalMinutesIsRejectedUntilUnfinalized(): void
    {
        $body = ['section_ringkasan' => 'Ringkasan direvisi.', 'section_pembahasan' => 'Poin baru.', 'section_kesimpulan' => 'Kesimpulan baru.'];

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBody(json_encode($body))
            ->put('/api/v1/notulen/risalah/1')
            ->assertStatus(422);

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/notulen/risalah/1/unfinalisasi')
            ->assertOK();

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBody(json_encode($body))
            ->put('/api/v1/notulen/risalah/1')
            ->assertOK();

        $row = $this->apiDb->table('meeting_minutes')->where('id', 1)->get()->getRowArray();
        $this->assertStringContainsString('Ringkasan direvisi.', $row['ringkasan_eksekutif']);
        $pillars = json_decode($row['struktur_json'], true);
        $this->assertSame('Ringkasan direvisi.', $pillars['ringkasan_utama']);
    }

    public function testFinalizeDraftMinutes(): void
    {
        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/notulen/risalah/1/unfinalisasi')
            ->assertOK();

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/notulen/risalah/1/finalisasi');

        $response->assertOK();
        $row = $this->apiDb->table('meeting_minutes')->where('id', 1)->get()->getRowArray();
        $this->assertSame('final', $row['status_verifikasi']);
        $this->assertSame(1, (int) $row['verified_by']);
    }

    public function testUploadStartIsResumableWithSameOwner(): void
    {
        $payload = $this->startUploadSession();

        $this->assertSame(0, $payload['offset']);
        $this->assertSame(1000, $payload['file_size']);

        $second = $this->startUploadSession();
        $this->assertSame($payload['upload_id'], $second['upload_id'], 'owner dari bearer yang sama harus resume sesi yang sama');
    }

    public function testUploadChunkAppendThenResumeFromHttpStart(): void
    {
        $payload = $this->startUploadSession();
        $this->appendChunk($payload['upload_id'], 1000);

        $resumed = $this->startUploadSession();
        $this->assertSame(1000, $resumed['offset']);
        $this->assertTrue($resumed['completed']);
    }

    public function testUploadCommitCreatesJobWithOriginalFilename(): void
    {
        $payload = $this->startUploadSession();
        $this->appendChunk($payload['upload_id'], 1000);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBody(json_encode([
                'upload_id'   => $payload['upload_id'],
                'jadwal_type' => 'umum',
                'jadwal_id'   => '',
                'judul_rapat' => 'Rapat Uji Commit',
            ]))
            ->post('/api/v1/notulen/upload/commit');

        $response->assertOK();
        $data = json_decode((string) $response->response()->getBody(), true)['data'];

        $row = $this->apiDb->table('meeting_transcription_jobs')->where('id', (int) $data['job_id'])->get()->getRowArray();
        $this->assertSame('queued', $row['status']);
        $this->assertSame('rapat_kerja_uji.mp3', $row['audio_filename']);
        $this->assertSame(1000, (int) $row['audio_size']);
    }

    public function testUploadCommitBeforeCompleteReturns409(): void
    {
        $payload = $this->startUploadSession();

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBody(json_encode(['upload_id' => $payload['upload_id'], 'jadwal_type' => 'umum']))
            ->post('/api/v1/notulen/upload/commit');

        $response->assertStatus(409);
    }

    public function testUploadCancelRemovesSession(): void
    {
        $payload = $this->startUploadSession();

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBody(json_encode(['upload_id' => $payload['upload_id']]))
            ->post('/api/v1/notulen/upload/cancel');

        $response->assertOK();
        $this->assertFalse(is_dir(WRITEPATH . 'uploads/audio-chunks/' . $payload['upload_id']));
    }

    /**
     * Owner sesi diturunkan di controller dari user pemilik bearer token
     * dengan rumus yang direplikasi persis di sini (user id 1 = admin seed).
     */
    private function startUploadSession(): array
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBody(json_encode([
                'client_key' => str_repeat('cd', 32),
                'file_name'  => 'rapat_kerja_uji.mp3',
                'file_size'  => 1000,
                'file_type'  => 'audio/mpeg',
            ]))
            ->post('/api/v1/notulen/upload/start');

        $response->assertOK();

        return json_decode((string) $response->response()->getBody(), true)['data'];
    }

    private function appendChunk(string $uploadId, int $size): void
    {
        $content = str_repeat('X', $size);
        $chunkPath = tempnam(sys_get_temp_dir(), 'notulen-api-chunk-');
        file_put_contents($chunkPath, $content);

        $chunk = new class($chunkPath, 'chunk.bin', 'application/octet-stream', strlen($content), UPLOAD_ERR_OK) extends UploadedFile {
            public function isValid(): bool
            {
                return $this->getError() === UPLOAD_ERR_OK && is_file($this->getTempName());
            }
        };

        (new PostChunkAudioUpload())->append(
            hash('sha256', 'notulen-upload:1'),
            $uploadId,
            0,
            hash_file('sha256', $chunkPath),
            $chunk
        );

        unlink($chunkPath);
    }

    private function seedIdentities(): void
    {
        $this->apiDb->table('users')->insert([
            'username' => 'admin-api', 'name' => 'Admin API', 'active' => 1,
        ]);
        $adminId = (int) $this->apiDb->insertID();
        $this->apiDb->table('auth_groups_users')->insert([
            'user_id' => $adminId, 'group' => 'superadmin', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->issueToken($adminId, self::ADMIN_TOKEN);

        $this->apiDb->table('users')->insert([
            'username' => 'anggota-api', 'name' => 'Anggota API', 'active' => 1,
        ]);
        $anggotaId = (int) $this->apiDb->insertID();
        $this->apiDb->table('auth_groups_users')->insert([
            'user_id' => $anggotaId, 'group' => 'anggota', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->issueToken($anggotaId, self::ANGGOTA_TOKEN);

        $this->apiDb->table('meeting_transcription_jobs')->insertBatch([
            [
                'id' => 40, 'jadwal_type' => 'umum', 'jadwal_id' => null,
                'audio_filename' => 'sidang_paripurna.mp3', 'audio_path' => '', 'audio_size' => 5000,
                'status' => 'transcribing', 'total_chunks' => 4, 'completed_chunks' => 2,
                'progress_percent' => 45, 'current_step' => 'Mentranskripsikan bagian 3 dari 4...',
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 39, 'jadwal_type' => 'banmus', 'jadwal_id' => null,
                'audio_filename' => 'rapat_banmus.mp3', 'audio_path' => '', 'audio_size' => 3000,
                'status' => 'completed', 'total_chunks' => 1, 'completed_chunks' => 1,
                'progress_percent' => 100, 'current_step' => 'Selesai',
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);

        $this->apiDb->table('meeting_minutes')->insert([
            'job_id' => 40, 'status_verifikasi' => 'final',
            'ringkasan_eksekutif' => "I. RINGKASAN UTAMA\nRapat membahas APBD.\n\nII. POIN-POIN PEMBAHASAN\n1. Topik: Anggaran\n   - Pembicara: Ketua Komisi\n   - Uraian: Pendapat umum fraksi.\n\nIII. KESIMPULAN & KEPUTUSAN AKHIR\n1. Disetujui bersama",
            'struktur_json' => json_encode([
                'ringkasan_utama' => 'Rapat membahas APBD.',
                'poin_pembahasan' => [
                    ['waktu' => '10:30', 'topik' => 'Anggaran', 'pembicara' => 'Ketua Komisi', 'uraian' => 'Pendapat umum fraksi.', 'full_text' => '1. [10:30] Topik: Anggaran'],
                ],
                'kesimpulan_akhir' => ['Disetujui bersama'],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->apiDb->table('meeting_minutes')->insert([
            'job_id' => 39, 'status_verifikasi' => 'draft', 'ringkasan_eksekutif' => null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Transkrip dan audio dibaca dari filesystem (bukan DB), jadi fixture
     * job_40 dibuat nyata di writable/uploads/recordings.
     */
    private function seedJobFixtures(): void
    {
        $this->jobFixturesDir = WRITEPATH . 'uploads/recordings/job_40';

        $transcriptsDir = $this->jobFixturesDir . '/transcripts';
        $audioDir = $this->jobFixturesDir . '/audio';
        if (! is_dir($transcriptsDir)) {
            mkdir($transcriptsDir, 0777, true);
        }
        if (! is_dir($audioDir)) {
            mkdir($audioDir, 0777, true);
        }

        file_put_contents($transcriptsDir . '/chunk_001.txt', "Isi transkrip bagian pertama rapat.");
        file_put_contents($transcriptsDir . '/chunk_002.txt', "Isi transkrip bagian kedua rapat.");
        file_put_contents($audioDir . '/original.mp3', str_repeat('A', 5000));
    }

    private function removeDir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $item) {
            is_dir($item) ? $this->removeDir($item) : @unlink($item);
        }
        @rmdir($dir);
    }

    private function issueToken(int $userId, string $rawToken): void
    {
        $this->apiDb->table('auth_identities')->insert([
            'user_id'    => $userId,
            'type'       => 'access_token',
            'name'       => 'test',
            'secret'     => hash('sha256', $rawToken),
            'extra'      => serialize(['*']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
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

        $this->apiForge->addField([
            'id'            => ['type' => 'INTEGER', 'auto_increment' => true],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_agent'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'id_type'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'identifier'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_id'       => ['type' => 'INTEGER', 'null' => true],
            'date'          => ['type' => 'DATETIME'],
            'success'       => ['type' => 'INTEGER'],
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
            'id'               => ['type' => 'INTEGER', 'auto_increment' => true],
            'jadwal_type'      => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'umum'],
            'jadwal_id'        => ['type' => 'INTEGER', 'null' => true],
            'audio_filename'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'audio_path'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'audio_size'       => ['type' => 'BIGINT', 'default' => 0],
            'audio_duration'   => ['type' => 'INTEGER', 'null' => true],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'queued'],
            'cancel_requested' => ['type' => 'INTEGER', 'default' => 0],
            'total_chunks'     => ['type' => 'INTEGER', 'default' => 0],
            'completed_chunks' => ['type' => 'INTEGER', 'default' => 0],
            'progress_percent' => ['type' => 'INTEGER', 'default' => 0],
            'current_step'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'error_message'    => ['type' => 'TEXT', 'null' => true],
            'ai_model'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_by'       => ['type' => 'INTEGER', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('meeting_transcription_jobs');

        $this->apiForge->addField([
            'id'                  => ['type' => 'INTEGER', 'auto_increment' => true],
            'job_id'              => ['type' => 'INTEGER', 'null' => true],
            'transcripts_dir'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ringkasan_eksekutif' => ['type' => 'TEXT', 'null' => true],
            'struktur_json'       => ['type' => 'TEXT', 'null' => true],
            'status_verifikasi'   => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'draft'],
            'verified_by'         => ['type' => 'INTEGER', 'null' => true],
            'verified_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('meeting_minutes');

        $this->apiForge->addField([
            'id'     => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal' => ['type' => 'DATE'],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('jadwal_umum');
    }

    private function dropTables(): void
    {
        foreach (['jadwal_umum', 'meeting_minutes', 'meeting_transcription_jobs', 'anggota', 'auth_token_logins', 'auth_identities', 'auth_groups_users', 'users'] as $table) {
            if ($this->apiDb->tableExists($table)) {
                $this->apiForge->dropTable($table, true);
            }
        }
    }
}
