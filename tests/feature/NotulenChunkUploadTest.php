<?php

use App\Libraries\Notulen\NotulenService;
use App\Libraries\Notulen\PostChunkAudioUpload;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * Pengujian chunked upload notulen: nama asli client tersimpan di
 * audio_filename, dan guard purge terhadap job yang sedang berjalan.
 *
 * @internal
 */
final class NotulenChunkUploadTest extends CIUnitTestCase
{
    private BaseConnection $testDb;
    private Forge $forge;
    private string $tempRoot;
    private string $recordingsDir;

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

        $this->tempRoot = sys_get_temp_dir() . '/notulen-chunk-test-' . bin2hex(random_bytes(4));
        $this->recordingsDir = sys_get_temp_dir() . '/notulen-recordings-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $this->dropTables();

        foreach ([$this->tempRoot, $this->recordingsDir, WRITEPATH . 'uploads/audio-chunks'] as $dir) {
            if (is_dir($dir)) {
                $this->removeDir($dir);
            }
        }

        parent::tearDown();
    }

    public function testCreateJobFromChunkStoresOriginalClientFilename(): void
    {
        $ownerToken = str_repeat('ab', 32);
        $clientKey = str_repeat('cd', 32);
        $content = str_repeat('MP3DATA', 150);

        // createJobFromChunk memakai PostChunkAudioUpload dengan root default,
        // jadi sesi chunk harus dibuat di root yang sama.
        $uploader = new PostChunkAudioUpload();
        $start = $uploader->start($ownerToken, $clientKey, 'rapat_dprd_2026_final.mp3', strlen($content), 'audio/mpeg');

        $chunkPath = tempnam(sys_get_temp_dir(), 'notulen-chunk-');
        file_put_contents($chunkPath, $content);
        $chunk = new class($chunkPath, 'chunk.bin', 'application/octet-stream', strlen($content), UPLOAD_ERR_OK) extends UploadedFile {
            public function isValid(): bool
            {
                return $this->getError() === UPLOAD_ERR_OK && is_file($this->getTempName());
            }
        };
        $uploader->append($ownerToken, $start['upload_id'], 0, hash_file('sha256', $chunkPath), $chunk);

        $service = new NotulenService($this->testDb, $this->recordingsDir);
        $result = $service->createJobFromChunk([
            'jadwal_type' => 'umum',
            'jadwal_id'   => null,
            'judul_rapat' => 'Rapat Uji Nama File',
        ], $ownerToken, $start['upload_id'], null);

        $this->assertArrayNotHasKey('error', $result);
        $jobId = (int) $result['job_id'];

        $row = $this->testDb->table('meeting_transcription_jobs')->where('id', $jobId)->get()->getRowArray();
        $this->assertSame('rapat_dprd_2026_final.mp3', $row['audio_filename']);
        $this->assertSame('original.mp3', basename($row['audio_path']));
        $this->assertSame(strlen($content), (int) $row['audio_size']);
    }

    private function insertJob(string $status): int
    {
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'jadwal_type'  => 'umum',
            'audio_filename' => 'uji_purge.mp3',
            'audio_path'   => 'recordings/job_1/audio/original.mp3',
            'audio_size'   => 1000,
            'status'       => $status,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->testDb->insertID();
    }

    private function createTables(): void
    {
        $this->forge->addField([
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
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('meeting_transcription_jobs');

        $this->forge->addField([
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
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('meeting_minutes');
    }

    private function dropTables(): void
    {
        foreach (['meeting_transcription_jobs', 'meeting_minutes'] as $table) {
            if ($this->testDb->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }
    }

    private function removeDir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $item) {
            is_dir($item) ? $this->removeDir($item) : @unlink($item);
        }
        @rmdir($dir);
    }
}
