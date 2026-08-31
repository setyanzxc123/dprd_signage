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

        $this->testDb->table('jadwal_umum')->insert([
            'id'          => 10,
            'judul'       => 'RDP Komisi I',
            'tanggal'     => '2026-08-27',
            'waktu_mulai' => '09:00:00',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_minutes')->insert([
            'job_id'              => 1,
            'transcripts_dir'     => 'recordings/job_1/transcripts',
            'ringkasan_eksekutif' => 'Ringkasan pembahasan RDP...',
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
        $response->assertSee('data-admin-datatable');
        $response->assertSee('data-dt-col-filters');
        $response->assertSee('data-filter="Selesai"');
        $response->assertSee('data-filter="Draft"');
        $response->assertSee('id="table-notulen"');
        $response->assertSee('Buka');
        $response->assertSee('Hapus');
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
            'ringkasan_eksekutif' => 'Ringkasan awal',
            'status_verifikasi'   => 'draft',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        // 1. Update minutes fields
        $updateResp = $this->adminPost('/admin/notulen/update-minutes/10', [
            'ringkasan_eksekutif' => "I. RINGKASAN UTAMA\nRingkasan telah diperbaiki.\n\nII. POIN-POIN PEMBAHASAN\n1. Topik: Revisi Anggaran\n\nIII. KESIMPULAN & KEPUTUSAN AKHIR\n1. Disetujui",
        ]);

        $updateResp->assertStatus(302);

        $rowAfterUpdate = (new MeetingMinutesModel($this->testDb))->find(10);
        $this->assertStringContainsString('Ringkasan telah diperbaiki', $rowAfterUpdate['ringkasan_eksekutif']);

        // 2. Finalize minutes
        $finalizeResp = $this->adminPost('/admin/notulen/finalize/10', []);

        $finalizeResp->assertStatus(302);

        $rowFinal = (new MeetingMinutesModel($this->testDb))->find(10);
        $this->assertSame('final', $rowFinal['status_verifikasi']);
        $this->assertNotNull($rowFinal['verified_at']);
    }

    public function testUnfinalizeMinutesAndEditProtection(): void
    {
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'             => 31,
            'jadwal_type'    => 'umum',
            'audio_filename' => 'rapat_unfinalize.mp3',
            'status'         => 'completed',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_minutes')->insert([
            'id'                  => 15,
            'job_id'              => 31,
            'ringkasan_eksekutif' => 'Ringkasan awal untuk tes finalisasi',
            'status_verifikasi'   => 'final',
            'verified_by'         => 1,
            'verified_at'         => date('Y-m-d H:i:s'),
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        // 1. Coba update naskah saat status final via AJAX -> ditolak 422
        $blockedResp = $this->withHeaders(['Accept' => 'application/json'])
            ->adminPost('/admin/notulen/update-minutes/15', [
                'ringkasan_eksekutif' => 'Perubahan yang harusnya ditolak',
            ]);

        $blockedResp->assertStatus(422);
        $blockedJson = json_decode($blockedResp->response()->getBody(), true);
        $this->assertSame('error', $blockedJson['status']);
        $this->assertStringContainsString('buka kunci revisi', $blockedJson['message']);

        // 2. Buka kunci revisi (unfinalize)
        $unfinalizeResp = $this->adminPost('/admin/notulen/unfinalize/15', []);
        $unfinalizeResp->assertStatus(302);

        $rowDraft = (new MeetingMinutesModel($this->testDb))->find(15);
        $this->assertSame('draft', $rowDraft['status_verifikasi']);
        $this->assertNull($rowDraft['verified_at']);

        // 3. Sekarang update naskah via AJAX berhasil (200 OK)
        $allowedResp = $this->withHeaders(['Accept' => 'application/json'])
            ->adminPost('/admin/notulen/update-minutes/15', [
                'ringkasan_eksekutif' => 'Naskah berhasil direvisi setelah unfinalize',
            ]);

        $allowedResp->assertStatus(200);
        $allowedJson = json_decode($allowedResp->response()->getBody(), true);
        $this->assertSame('success', $allowedJson['status']);

        $rowUpdated = (new MeetingMinutesModel($this->testDb))->find(15);
        $this->assertSame('Naskah berhasil direvisi setelah unfinalize', $rowUpdated['ringkasan_eksekutif']);
    }

    public function testAudioStreamingEndpointReturnsByteRange(): void
    {
        $jobDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'recordings' . DIRECTORY_SEPARATOR . 'job_50' . DIRECTORY_SEPARATOR . 'audio';
        if (! is_dir($jobDir)) {
            mkdir($jobDir, 0777, true);
        }
        $dummyAudio = $jobDir . DIRECTORY_SEPARATOR . 'original.mp3';
        file_put_contents($dummyAudio, str_repeat('A', 5000));

        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'             => 50,
            'jadwal_type'    => 'umum',
            'audio_filename' => 'audio_test.mp3',
            'status'         => 'completed',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        try {
            // Request with Range header
            $response = $this->withHeaders(['Range' => 'bytes=0-99'])
                ->adminGet('/admin/notulen/audio/50');

            $response->assertStatus(206);
            $response->assertHeader('Content-Range', 'bytes 0-99/5000');
            $response->assertHeader('Content-Length', '100');
            $this->assertSame(100, strlen($response->response()->getBody()));
        } finally {
            if (is_file($dummyAudio)) {
                @unlink($dummyAudio);
            }
            if (is_dir($jobDir)) {
                @rmdir($jobDir);
                @rmdir(dirname($jobDir));
            }
        }
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
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'             => 5,
            'jadwal_type'    => 'umum',
            'jadwal_id'      => 20,
            'audio_filename' => 'rdp_20.mp3',
            'status'         => 'completed',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('jadwal_umum')->insert([
            'id'          => 20,
            'judul'       => 'Rapat Dengar Pendapat',
            'tanggal'     => '2026-08-27',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_minutes')->insert([
            'job_id'              => 5,
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
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'             => 6,
            'jadwal_type'    => 'umum',
            'jadwal_id'      => 21,
            'audio_filename' => 'sidang_21.mp3',
            'status'         => 'completed',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('jadwal_umum')->insert([
            'id'          => 21,
            'judul'       => 'Sidang Paripurna Pembahasan APBD',
            'tanggal'     => '2026-08-27',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_minutes')->insert([
            'job_id'              => 6,
            'ringkasan_eksekutif' => "I. RINGKASAN UTAMA\nSidang paripurna menyetujui...\n\nII. POIN-POIN PEMBAHASAN\n1. Topik: APBD\n\nIII. KESIMPULAN & KEPUTUSAN AKHIR\n1. Disahkan",
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
        $this->assertStringContainsString('RINGKASAN UTAMA', $json['ringkasan_eksekutif']);
        $this->assertStringContainsString('KESIMPULAN', $json['ringkasan_eksekutif']);
        $this->assertIsArray($json['tiga_pilar']);
        $this->assertArrayHasKey('ringkasan_utama', $json['tiga_pilar']);
        $this->assertArrayHasKey('poin_pembahasan', $json['tiga_pilar']);
        $this->assertArrayHasKey('kesimpulan_akhir', $json['tiga_pilar']);
    }

    public function testIndexRedirectsToExistingJobWhenScheduleReferenced(): void
    {
        $this->testDb->table('jadwal_umum')->insert([
            'id'          => 33,
            'judul'       => 'Rapat Komisi IV',
            'tanggal'     => '2026-08-28',
            'waktu_mulai' => '10:00:00',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'             => 77,
            'jadwal_type'    => 'umum',
            'jadwal_id'      => 33,
            'audio_filename' => 'komisi4.mp3',
            'status'         => 'completed',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $response = $this->adminGet('/admin/notulen?jadwal_type=umum&jadwal_id=33');
        $response->assertRedirectTo(base_url('admin/notulen/77'));
    }

    public function testIndexPresetsModalWhenScheduleNotYetRecorded(): void
    {
        $this->testDb->table('jadwal_umum')->insert([
            'id'          => 44,
            'judul'       => 'Rapat Paripurna Istimewa HUT Sulteng',
            'tanggal'     => '2026-08-29',
            'waktu_mulai' => '08:30:00',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $response = $this->adminGet('/admin/notulen?jadwal_type=umum&jadwal_id=44');
        $response->assertOK();
        $response->assertSee('data-preset-id="44"');
        $response->assertSee('data-preset-type="umum"');
        $response->assertSee('Rapat Paripurna Istimewa HUT Sulteng');
        $response->assertSee('Terkunci');
    }

    public function testWebAdminShowAndStatusRendersDynamicAiModel(): void
    {
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'               => 88,
            'jadwal_type'      => 'umum',
            'jadwal_id'        => 44,
            'audio_filename'   => 'sidang_hut.mp3',
            'status'           => 'completed',
            'progress_percent' => 100,
            'current_step'     => 'Selesai: Transkrip dan draft risalah siap ditinjau.',
            'ai_model'         => 'gemini-3.5-flash-lite',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_minutes')->insert([
            'job_id'              => 88,
            'ringkasan_eksekutif' => 'I. RINGKASAN UTAMA\nRisalah rapat HUT...',
            'status_verifikasi'   => 'draft',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        // Verifikasi render HTML show.php memuat label dinamis
        $showResponse = $this->adminGet('/admin/notulen/88');
        $showResponse->assertOK();
        $showResponse->assertSee('Gemini 3.5 Flash Lite');
        $showResponse->assertDontSee('Gemini 2.5 Pro');

        // Verifikasi response polling JSON memuat ai_model dan ai_model_label
        $statusResponse = $this->adminGet('/admin/notulen/status/88');
        $statusResponse->assertOK();
        $json = json_decode($statusResponse->response()->getBody(), true);
        $this->assertSame('success', $json['status']);
        $this->assertSame('gemini-3.5-flash-lite', $json['data']['ai_model']);
        $this->assertSame('Gemini 3.5 Flash Lite', $json['data']['ai_model_label']);
    }

    public function testResolveAudioPathPrefersMasterAudioOverChunks(): void
    {
        $jobId = 99;
        $service = new NotulenService($this->testDb);
        $jobDir = $service->getJobDir($jobId);
        $audioDir = $jobDir . DIRECTORY_SEPARATOR . 'audio';

        if (! is_dir($audioDir)) {
            mkdir($audioDir, 0777, true);
        }

        // Buat file chunk dan file original
        file_put_contents($audioDir . DIRECTORY_SEPARATOR . 'chunk_001.mp3', 'dummy chunk 1');
        file_put_contents($audioDir . DIRECTORY_SEPARATOR . 'chunk_002.mp3', 'dummy chunk 2');
        file_put_contents($audioDir . DIRECTORY_SEPARATOR . 'original.mp3', 'master original audio');

        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'             => $jobId,
            'jadwal_type'    => 'umum',
            'jadwal_id'      => 44,
            'audio_filename' => 'rapat_komisi.mp3',
            'audio_path'     => 'recordings/job_99/audio/original.mp3',
            'status'         => 'completed',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $resolved = $service->resolveAudioPath($jobId);
        $this->assertNotNull($resolved);
        $this->assertStringEndsWith('original.mp3', str_replace('\\', '/', $resolved));
        $this->assertStringNotContainsString('chunk_', $resolved);

        // Bersihkan file testing
        @unlink($audioDir . DIRECTORY_SEPARATOR . 'chunk_001.mp3');
        @unlink($audioDir . DIRECTORY_SEPARATOR . 'chunk_002.mp3');
        @unlink($audioDir . DIRECTORY_SEPARATOR . 'original.mp3');
        @rmdir($audioDir);
        @rmdir($jobDir);
    }

    public function testDownloadTranscriptAndExportPdfBlockedWhenNotCompleted(): void
    {
        // 1. Buat job yang masih berjalan (status: transcribing)
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'             => 101,
            'jadwal_type'    => 'umum',
            'jadwal_id'      => 44,
            'audio_filename' => 'rapat_in_progress.mp3',
            'status'         => 'transcribing',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_minutes')->insert([
            'id'                  => 101,
            'job_id'              => 101,
            'ringkasan_eksekutif' => null,
            'status_verifikasi'   => 'draft',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        // Coba unduh transkrip saat belum completed -> harus diblokir & redirect
        $transcriptResponse = $this->adminGet('/admin/notulen/download-transcript/101');
        $transcriptResponse->assertRedirect();
        $this->assertSame('Berkas transkrip belum dapat diunduh karena proses transkripsi AI belum selesai.', session()->getFlashdata('error'));

        // Coba cetak PDF saat belum completed / teks kosong -> harus diblokir & redirect
        $pdfResponse = $this->adminGet('/admin/notulen/export-pdf/101');
        $pdfResponse->assertRedirectTo(base_url('admin/notulen/101'));
        $this->assertSame('Risalah rapat belum dapat dicetak karena proses penyusunan AI belum selesai.', session()->getFlashdata('error'));

        // Di halaman show.php, tombol unduh transkrip dan cetak risalah harus disabled
        $showResponse = $this->adminGet('/admin/notulen/101');
        $showResponse->assertOK();
        $showResponse->assertSee('(Setelah proses selesai)');
        // Tombol Hentikan harus muncul saat job in-progress
        $showResponse->assertSee('Hentikan');
    }

    public function testExportPdfReturnsServerSidePdfMatchingPreview(): void
    {
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'             => 102,
            'jadwal_type'    => 'umum',
            'jadwal_id'      => 45,
            'audio_filename' => 'rapat_selesai.mp3',
            'status'         => 'completed',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('jadwal_umum')->insert([
            'id'          => 45,
            'judul'       => 'RDP Komisi II',
            'tanggal'     => '2026-08-30',
            'waktu_mulai' => '13:30:00',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_minutes')->insert([
            'id'                  => 102,
            'job_id'              => 102,
            'ringkasan_eksekutif' => "I. RINGKASAN UTAMA\nRapat membahas Ranperda apotek hidup.\n\nII. POIN PEMBAHASAN\n1. [13:35] Topik: Keuangan\n\nIII. KESIMPULAN AKHIR\n1. Ranperda disetujui.",
            'status_verifikasi'   => 'final',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        $response = $this->adminGet('/admin/notulen/export-pdf/102');

        $response->assertOK();
        $response->assertHeader('Content-Type');
        $this->assertStringContainsString('application/pdf', $response->response()->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('no-store', $response->response()->getHeaderLine('Cache-Control'));
        $this->assertSame('inline', str_getcsv($response->response()->getHeaderLine('Content-Disposition'), ';')[0]);
        $this->assertStringContainsString('Risalah_RDP_Komisi_II_20260830.pdf', $response->response()->getHeaderLine('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->response()->getBody());
    }

    public function testStopAndResumeButtonsAndLifecycleInNotulenShow(): void
    {
        $jobId = 105;
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'             => $jobId,
            'jadwal_type'    => 'umum',
            'jadwal_id'      => 44,
            'audio_filename' => 'rapat_stop_resume.mp3',
            'status'         => 'transcribing',
            'progress_percent' => 45,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_minutes')->insert([
            'id'                  => $jobId,
            'job_id'              => $jobId,
            'ringkasan_eksekutif' => null,
            'status_verifikasi'   => 'draft',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        // 1. Verifikasi halaman show menampilkan tombol Hentikan saat job berjalan
        $showResponse = $this->adminGet("/admin/notulen/{$jobId}");
        $showResponse->assertOK();
        $showResponse->assertSee('Hentikan');

        // 2. Kirim request cancel/stop
        $cancelResponse = $this->adminPost("/admin/notulen/cancel/{$jobId}", []);
        $cancelResponse->assertRedirect();

        // Verifikasi cancel_requested menjadi 1
        $job = $this->testDb->table('meeting_transcription_jobs')->where('id', $jobId)->get()->getRowArray();
        $this->assertSame(1, (int) $job['cancel_requested']);

        // 3. Simulasikan worker menandai status menjadi cancelled
        $this->testDb->table('meeting_transcription_jobs')->where('id', $jobId)->update([
            'status'           => 'cancelled',
            'cancel_requested' => 0,
            'current_step'     => 'Dibatalkan oleh admin',
        ]);

        // 4. Verifikasi halaman show sekarang menampilkan badge Dihentikan dan tombol Lanjutkan
        $cancelledShowResponse = $this->adminGet("/admin/notulen/{$jobId}");
        $cancelledShowResponse->assertOK();
        $cancelledShowResponse->assertSee('Dihentikan');
        $cancelledShowResponse->assertSee('Lanjutkan');

        // 5. Kirim request resume/retry
        $resumeResponse = $this->adminPost("/admin/notulen/retry/{$jobId}", []);
        $resumeResponse->assertRedirect();

        // Verifikasi job kembali ke antrean queued untuk melanjutkan checkpoint
        $resumedJob = $this->testDb->table('meeting_transcription_jobs')->where('id', $jobId)->get()->getRowArray();
        $this->assertSame('queued', $resumedJob['status']);
        $this->assertSame(0, (int) $resumedJob['cancel_requested']);
    }

    public function testMinutesReviewAndMinimalistEditorWorkflowInShow(): void
    {
        $jobId = 106;
        $this->testDb->table('meeting_transcription_jobs')->insert([
            'id'               => $jobId,
            'jadwal_type'      => 'umum',
            'jadwal_id'        => 45,
            'audio_filename'   => 'rapat_editor_test.mp3',
            'status'           => 'completed',
            'progress_percent' => 100,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->testDb->table('meeting_minutes')->insert([
            'id'                  => $jobId,
            'job_id'              => $jobId,
            'ringkasan_eksekutif' => 'I. RINGKASAN UTAMA\nDraf awal risalah.',
            'status_verifikasi'   => 'draft',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        // 1. Tampilan awal berstatus draf: tombol sunting dan finalisasi tersedia
        $showResponse = $this->adminGet("/admin/notulen/{$jobId}");
        $showResponse->assertOK();
        $showResponse->assertSee('Draf Risalah');
        $showResponse->assertSee('Sunting Naskah');
        $showResponse->assertSee('Sahkan / Finalisasi');

        // 2. Lakukan penyuntingan naskah melalui endpoint update-minutes
        $updateResp = $this->adminPost("/admin/notulen/update-minutes/{$jobId}", [
            'ringkasan_eksekutif' => 'I. RINGKASAN UTAMA\nNaskah telah disunting oleh notulis.',
        ]);
        $updateResp->assertRedirect();

        $updatedMinutes = $this->testDb->table('meeting_minutes')->where('id', $jobId)->get()->getRowArray();
        $this->assertStringContainsString('Naskah telah disunting oleh notulis.', $updatedMinutes['ringkasan_eksekutif']);
        $this->assertNotEmpty($updatedMinutes['struktur_json']);
        $decodedJson = json_decode((string) $updatedMinutes['struktur_json'], true);
        $this->assertIsArray($decodedJson);
        $this->assertArrayHasKey('ringkasan_utama', $decodedJson);

        // 3. Finalisasi risalah
        $finalizeResp = $this->adminPost("/admin/notulen/finalize/{$jobId}", []);
        $finalizeResp->assertRedirect();

        // 4. Halaman show sekarang berstatus final: tombol Buka Kunci Revisi muncul
        $finalShowResponse = $this->adminGet("/admin/notulen/{$jobId}");
        $finalShowResponse->assertOK();
        $finalShowResponse->assertSee('Naskah Final');
        $finalShowResponse->assertSee('Buka Kunci Revisi');

        // 5. Buka kunci revisi (unfinalize)
        $unfinalizeResp = $this->adminPost("/admin/notulen/unfinalize/{$jobId}", []);
        $unfinalizeResp->assertRedirect();

        $unfinalizedMinutes = $this->testDb->table('meeting_minutes')->where('id', $jobId)->get()->getRowArray();
        $this->assertSame('draft', $unfinalizedMinutes['status_verifikasi']);

        // 6. Uji simpan via 3 seksi terkunci (sectioned editor)
        $sectionResp = $this->withHeaders(['Accept' => 'application/json'])
            ->adminPost("/admin/notulen/update-minutes/{$jobId}", [
                'section_ringkasan'   => 'Intisari rapat di seksi 1',
                'section_pembahasan'  => "1. Topik: Raperda APBD\n   - Pembicara: Sekda\n   - Uraian: Penjelasan postur.",
                'section_kesimpulan'  => "1. Disetujui bersama.",
            ]);
        $sectionResp->assertStatus(200);

        $sectionMinutes = $this->testDb->table('meeting_minutes')->where('id', $jobId)->get()->getRowArray();
        $this->assertStringContainsString('I. RINGKASAN UTAMA', $sectionMinutes['ringkasan_eksekutif']);
        $this->assertStringContainsString('Intisari rapat di seksi 1', $sectionMinutes['ringkasan_eksekutif']);
        $this->assertStringContainsString('II. POIN-POIN PEMBAHASAN', $sectionMinutes['ringkasan_eksekutif']);
        $this->assertStringContainsString('III. KESIMPULAN & KEPUTUSAN AKHIR', $sectionMinutes['ringkasan_eksekutif']);

        $secPillars = json_decode((string) $sectionMinutes['struktur_json'], true);
        $this->assertSame('Intisari rapat di seksi 1', $secPillars['ringkasan_utama']);
        $this->assertCount(1, $secPillars['poin_pembahasan']);
        $this->assertSame('Raperda APBD', $secPillars['poin_pembahasan'][0]['topik']);
    }

    public function testParsePillarsRobustExtraction(): void
    {
        $service = new \App\Libraries\Notulen\NotulenService($this->testDb);
        $sampleText = <<<EOT
I. RINGKASAN UTAMA
Rapat Paripurna DPRD Provinsi Sulawesi Tengah membahas RAPBD TA 2026.

II. POIN-POIN PEMBAHASAN
1. [00:15:00] Topik: Pidato Pengantar Nota Keuangan RAPBD TA 2026
   - Pembicara: Sekretaris Daerah Provinsi Sulawesi Tengah
   - Uraian: Menjelaskan arsitektur keuangan daerah dan target PAD.
2. Topik: Pandangan Umum Fraksi Partai Golkar
   - Pembicara: Henrikus Sumanga
   - Uraian: Menyetujui pembahasan dilanjutkan dengan catatan PAD.

III. KESIMPULAN & KEPUTUSAN AKHIR
1. Ranperda disetujui dibahas pada tahap selanjutnya.
2. Rapat kerja Banggar dan Komisi dijadwalkan berikutnya.
EOT;

        $pillars = $service->parsePillarsFromText($sampleText);
        $this->assertSame('Rapat Paripurna DPRD Provinsi Sulawesi Tengah membahas RAPBD TA 2026.', $pillars['ringkasan_utama']);
        $this->assertCount(2, $pillars['poin_pembahasan']);
        $this->assertSame('00:15:00', $pillars['poin_pembahasan'][0]['waktu']);
        $this->assertSame('Pidato Pengantar Nota Keuangan RAPBD TA 2026', $pillars['poin_pembahasan'][0]['topik']);
        $this->assertSame('Sekretaris Daerah Provinsi Sulawesi Tengah', $pillars['poin_pembahasan'][0]['pembicara']);
        $this->assertStringContainsString('arsitektur keuangan', $pillars['poin_pembahasan'][0]['uraian']);

        $this->assertCount(2, $pillars['kesimpulan_akhir']);
        $this->assertStringContainsString('Ranperda disetujui', $pillars['kesimpulan_akhir'][0]);
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
            'ai_model'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_by'       => ['type' => 'INTEGER', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('meeting_transcription_jobs');

        $this->forge->addField([
            'id'                  => ['type' => 'INTEGER', 'auto_increment' => true],
            'job_id'              => ['type' => 'INTEGER', 'null' => true],
            'transcripts_dir'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'ringkasan_eksekutif' => ['type' => 'TEXT', 'null' => true],
            'struktur_json'       => ['type' => 'TEXT', 'null' => true],
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
