<?php

namespace App\Libraries\Notulen;

use App\Libraries\Media\MediaUploadException;
use App\Models\JadwalBanmusModel;
use App\Models\JadwalUmumModel;
use App\Models\MeetingMinutesModel;
use App\Models\MeetingTranscriptionJobModel;
use App\Models\RuanganModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Service Layer Pengelolaan Notulensi & Risalah Rapat DPRD (Google Gemini AI).
 * Dipakai bersama oleh Web Admin MPA dan REST API Mobile.
 */
class NotulenService
{
    public const MAX_AUDIO_SIZE = 314572800; // 300 MB

    public const ALLOWED_EXTENSIONS = [
        'mp3',
        'm4a',
        'wav',
        'ogg',
        'aac',
        'wma',
        'flac',
        'mp4',
    ];

    public const ALLOWED_MIME_TYPES = [
        'audio/mpeg',
        'audio/mp3',
        'audio/x-m4a',
        'audio/mp4',
        'audio/wav',
        'audio/x-wav',
        'audio/ogg',
        'audio/aac',
        'audio/x-aac',
        'audio/flac',
        'audio/x-flac',
        'video/mp4',
    ];

    private BaseConnection $db;
    private string $recordingsBaseDir;

    public function __construct(?BaseConnection $db = null, ?string $recordingsBaseDir = null)
    {
        $this->db = $db ?? db_connect();
        $this->recordingsBaseDir = $recordingsBaseDir ?? (WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'recordings');
    }

    /**
     * Memformat identifier teknis model Gemini AI menjadi label display yang rapi.
     */
    public static function formatAiModelLabel(?string $modelIdentifier): string
    {
        $raw = trim((string) $modelIdentifier);
        if ($raw === '') {
            return 'Gemini Flash AI';
        }

        $knownLabels = [
            'gemini-3.5-flash-lite'               => 'Gemini 3.5 Flash Lite',
            'gemini-3.5-flash'                    => 'Gemini 3.5 Flash',
            'gemini-3.1-flash'                    => 'Gemini 3.1 Flash',
            'gemini-3.7-flash'                    => 'Gemini 3.7 Flash',
            'gemini-2.5-flash-lite-preview-06-17' => 'Gemini 2.5 Flash Lite',
            'gemini-2.5-flash-lite'               => 'Gemini 2.5 Flash Lite',
            'gemini-2.5-flash'                    => 'Gemini 2.5 Flash',
            'gemini-2.5-pro'                      => 'Gemini 2.5 Pro',
            'gemini-2.0-flash'                    => 'Gemini 2.0 Flash',
            'gemini-2.0-flash-lite'               => 'Gemini 2.0 Flash Lite',
            'gemini-1.5-flash'                    => 'Gemini 1.5 Flash',
            'gemini-1.5-pro'                      => 'Gemini 1.5 Pro',
        ];

        if (isset($knownLabels[$raw])) {
            return $knownLabels[$raw];
        }

        // General fallback formatter: "gemini-x.y-foo-bar" -> "Gemini X.Y Foo Bar"
        $parts = explode('-', $raw);
        $capitalized = array_map(static function ($p) {
            if (preg_match('/^\d+(\.\d+)*$/', $p)) {
                return $p;
            }
            return ucfirst($p);
        }, $parts);

        return implode(' ', $capitalized);
    }

    /**
     * Dapatkan path absolut direktori root recordings.
     */
    public function getRecordingsBaseDir(): string
    {
        return rtrim($this->recordingsBaseDir, '\\/');
    }

    /**
     * Dapatkan path absolut direktori job tertentu.
     */
    public function getJobDir(int $jobId): string
    {
        return $this->getRecordingsBaseDir() . DIRECTORY_SEPARATOR . 'job_' . $jobId;
    }

    /**
     * Validasi berkas upload rekaman audio rapat.
     *
     * @return array<string, mixed> ['error' => pesan] atau info berkas valid
     */
    public function validateAudioUpload(?UploadedFile $file): array
    {
        if ($file === null || ! $file->isValid()) {
            return ['error' => 'File rekaman audio wajib diunggah dan valid.'];
        }

        if ($file->hasMoved()) {
            return ['error' => 'File rekaman sudah pernah dipindahkan.'];
        }

        $size = $file->getSize();
        if ($size <= 0) {
            return ['error' => 'File rekaman audio kosong atau korup.'];
        }

        if ($size > self::MAX_AUDIO_SIZE) {
            $maxMb = round(self::MAX_AUDIO_SIZE / (1024 * 1024));
            return ['error' => "Ukuran file rekaman melebihi batas maksimal {$maxMb} MB."];
        }

        $ext = strtolower((string) $file->getClientExtension());
        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            $allowedList = implode(', ', self::ALLOWED_EXTENSIONS);
            return ['error' => "Format file .{$ext} tidak didukung. Gunakan salah satu dari: {$allowedList}."];
        }

        return [
            'valid'     => true,
            'client_name' => $file->getClientName(),
            'size'      => $size,
            'extension' => $ext,
        ];
    }

    /**
     * Validasi input pembuatan job notulen baru.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function validatedJobInput(array $input, ?UploadedFile $file): array
    {
        $fileValidation = $this->validateAudioUpload($file);
        if (isset($fileValidation['error'])) {
            return $fileValidation;
        }

        $jadwalType = trim((string) ($input['jadwal_type'] ?? 'umum'));
        if (! in_array($jadwalType, [MeetingTranscriptionJobModel::TYPE_UMUM, MeetingTranscriptionJobModel::TYPE_BANMUS], true)) {
            $jadwalType = MeetingTranscriptionJobModel::TYPE_UMUM;
        }

        $jadwalIdRaw = $input['jadwal_id'] ?? null;
        $jadwalId = ($jadwalIdRaw !== null && is_numeric($jadwalIdRaw) && (int) $jadwalIdRaw > 0)
            ? (int) $jadwalIdRaw
            : null;

        $judulRapat = trim((string) ($input['judul_rapat'] ?? ''));
        if ($judulRapat === '') {
            // Coba ambil dari konteks jadwal bila ada
            if ($jadwalId !== null) {
                $judulRapat = $this->resolveScheduleTitle($jadwalType, $jadwalId);
            }
            if ($judulRapat === '') {
                $judulRapat = pathinfo($fileValidation['client_name'], PATHINFO_FILENAME);
            }
        }

        return [
            'jadwal_type'    => $jadwalType,
            'jadwal_id'      => $jadwalId,
            'judul_rapat'    => $judulRapat,
            'audio_filename' => $fileValidation['client_name'],
            'audio_size'     => $fileValidation['size'],
            'extension'      => $fileValidation['extension'],
        ];
    }

    /**
     * Buat Job Transkripsi Baru & Simpan File Rekaman ke Storage.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createJob(array $input, UploadedFile $file, ?int $userId = null): array
    {
        $validated = $this->validatedJobInput($input, $file);
        if (isset($validated['error'])) {
            return $validated;
        }

        $this->db->transBegin();

        try {
            $jobModel = new MeetingTranscriptionJobModel($this->db);
            $jobId = (int) $jobModel->insert([
                'jadwal_type'      => $validated['jadwal_type'],
                'jadwal_id'        => $validated['jadwal_id'],
                'audio_filename'   => $validated['audio_filename'],
                'audio_path'       => '', // Diset setelah direktori job dibuat
                'audio_size'       => $validated['audio_size'],
                'audio_duration'   => null,
                'status'           => MeetingTranscriptionJobModel::STATUS_QUEUED,
                'cancel_requested' => 0,
                'total_chunks'     => 0,
                'completed_chunks' => 0,
                'progress_percent' => 0,
                'current_step'     => 'Menunggu antrean pemrosesan worker AI...',
                'error_message'    => null,
                'created_by'       => $userId,
            ], true);

            if ($jobId <= 0) {
                $this->db->transRollback();
                $this->db->resetTransStatus();
                return ['error' => 'Gagal membuat antrean job transkripsi di database.'];
            }

            // Siapkan struktur folder: writable/uploads/recordings/job_{id}/{audio,transcripts}/
            $jobDir = $this->getJobDir($jobId);
            $audioDir = $jobDir . DIRECTORY_SEPARATOR . 'audio';
            $transcriptsDir = $jobDir . DIRECTORY_SEPARATOR . 'transcripts';

            if (! is_dir($audioDir)) {
                mkdir($audioDir, 0777, true);
            }
            if (! is_dir($transcriptsDir)) {
                mkdir($transcriptsDir, 0777, true);
            }

            // Simpan file asli sebagai original.mp3 / original.{ext}
            $targetExt = $validated['extension'] ?: 'mp3';
            $targetFilename = 'original.' . $targetExt;
            $file->move($audioDir, $targetFilename, true);

            $relativeAudioPath = 'writable/uploads/recordings/job_' . $jobId . '/audio/' . $targetFilename;

            // Update path audio di record job
            $jobModel->update($jobId, [
                'audio_path' => $relativeAudioPath,
            ]);

            // Inisialisasi draft record meeting_minutes jika belum ada (Single Source of Truth)
            $minutesModel = new MeetingMinutesModel($this->db);
            $existingMinutes = $minutesModel->where('job_id', $jobId)->first();

            if (! $existingMinutes) {
                $minutesModel->insert([
                    'job_id'             => $jobId,
                    'transcripts_dir'    => 'recordings/job_' . $jobId . '/transcripts',
                    'ringkasan_eksekutif'=> null,
                    'status_verifikasi'  => MeetingMinutesModel::STATUS_DRAFT,
                ]);
            }

            $this->db->transCommit();

            // Pemicu async worker lokal (opsional bila tidak pakai daemon PM2)
            $this->triggerWorkerAsync($jobId);

            return [
                'success' => true,
                'job_id'  => $jobId,
                'status'  => MeetingTranscriptionJobModel::STATUS_QUEUED,
                'message' => 'Rekaman rapat berhasil diunggah dan masuk dalam antrean pemrosesan AI.',
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->db->resetTransStatus();
            return ['error' => 'Gagal memproses rekaman: ' . $e->getMessage()];
        }
    }

    /**
     * Buat Job Transkripsi dari hasil chunked upload yang sudah selesai.
     *
     * Setelah semua chunk dikirim via PostChunkAudioUpload, controller memanggil
     * method ini dengan upload_id. File dipindahkan dari temp chunk ke folder job.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     *
     * @throws MediaUploadException
     */
    public function createJobFromChunk(
        array $input,
        string $ownerToken,
        string $uploadId,
        ?int $userId = null
    ): array {
        $jadwalType = trim((string) ($input['jadwal_type'] ?? 'umum'));
        if (! in_array($jadwalType, [MeetingTranscriptionJobModel::TYPE_UMUM, MeetingTranscriptionJobModel::TYPE_BANMUS], true)) {
            $jadwalType = MeetingTranscriptionJobModel::TYPE_UMUM;
        }

        $jadwalIdRaw = $input['jadwal_id'] ?? null;
        $jadwalId    = ($jadwalIdRaw !== null && is_numeric($jadwalIdRaw) && (int) $jadwalIdRaw > 0)
            ? (int) $jadwalIdRaw
            : null;

        $judulRapat = trim((string) ($input['judul_rapat'] ?? ''));

        $uploader = new PostChunkAudioUpload();

        // Buat job terlebih dahulu untuk mendapat job_id (diperlukan untuk path folder)
        $this->db->transBegin();

        try {
            $jobModel = new MeetingTranscriptionJobModel($this->db);

            // Placeholder job, audio_path diisi setelah file dipindahkan
            $jobId = (int) $jobModel->insert([
                'jadwal_type'      => $jadwalType,
                'jadwal_id'        => $jadwalId,
                'audio_filename'   => '', // diisi setelah consume
                'audio_path'       => '',
                'audio_size'       => 0,
                'audio_duration'   => null,
                'status'           => MeetingTranscriptionJobModel::STATUS_QUEUED,
                'cancel_requested' => 0,
                'total_chunks'     => 0,
                'completed_chunks' => 0,
                'progress_percent' => 0,
                'current_step'     => 'Menunggu antrean pemrosesan worker AI...',
                'error_message'    => null,
                'created_by'       => $userId,
            ], true);

            if ($jobId <= 0) {
                $this->db->transRollback();
                $this->db->resetTransStatus();
                return ['error' => 'Gagal membuat antrean job transkripsi di database.'];
            }

            // Siapkan direktori job
            $jobDir        = $this->getJobDir($jobId);
            $audioDir      = $jobDir . DIRECTORY_SEPARATOR . 'audio';
            $transcriptsDir = $jobDir . DIRECTORY_SEPARATOR . 'transcripts';

            if (! is_dir($audioDir)) {
                mkdir($audioDir, 0777, true);
            }
            if (! is_dir($transcriptsDir)) {
                mkdir($transcriptsDir, 0777, true);
            }

            // Pindahkan file chunk ke folder audio job
            // Nama file sementara untuk consume, akan kita rename sesuai ekstensi asli
            $tempDest = $audioDir . DIRECTORY_SEPARATOR . 'original.tmp';
            $uploader->consume($ownerToken, $uploadId, $tempDest);

            // Deteksi ekstensi dari MIME aktual file
            $finfo     = new \finfo(FILEINFO_MIME_TYPE);
            $detectedMime = strtolower((string) $finfo->file($tempDest));
            $extMap    = [
                'audio/mpeg'   => 'mp3',
                'audio/mp3'    => 'mp3',
                'audio/x-m4a'  => 'm4a',
                'audio/mp4'    => 'm4a',
                'audio/wav'    => 'wav',
                'audio/x-wav'  => 'wav',
                'audio/ogg'    => 'ogg',
                'audio/aac'    => 'aac',
                'audio/x-aac'  => 'aac',
                'audio/flac'   => 'flac',
                'audio/x-flac' => 'flac',
                'video/mp4'    => 'mp4',
            ];
            $ext      = $extMap[$detectedMime] ?? 'mp3';
            $filename = 'original.' . $ext;
            $finalDest = $audioDir . DIRECTORY_SEPARATOR . $filename;

            rename($tempDest, $finalDest);

            $fileSize            = filesize($finalDest) ?: 0;
            $relativeAudioPath   = 'writable/uploads/recordings/job_' . $jobId . '/audio/' . $filename;

            if ($judulRapat === '') {
                if ($jadwalId !== null) {
                    $judulRapat = $this->resolveScheduleTitle($jadwalType, $jadwalId);
                }
                if ($judulRapat === '') {
                    $judulRapat = 'Rekaman Rapat ' . date('d-m-Y');
                }
            }

            $jobModel->update($jobId, [
                'audio_filename' => $filename,
                'audio_path'     => $relativeAudioPath,
                'audio_size'     => $fileSize,
            ]);

            // Inisialisasi draft meeting_minutes (Single Source of Truth)
            $minutesModel   = new MeetingMinutesModel($this->db);
            $existingMinutes = $minutesModel->where('job_id', $jobId)->first();

            if (! $existingMinutes) {
                $minutesModel->insert([
                    'job_id'              => $jobId,
                    'transcripts_dir'     => 'recordings/job_' . $jobId . '/transcripts',
                    'ringkasan_eksekutif' => null,
                    'status_verifikasi'   => 'draft',
                ]);
            }

            $this->db->transCommit();

            return [
                'job_id'  => $jobId,
                'status'  => MeetingTranscriptionJobModel::STATUS_QUEUED,
                'message' => 'Rekaman rapat berhasil diunggah dan masuk dalam antrean pemrosesan AI.',
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->db->resetTransStatus();

            if ($e instanceof MediaUploadException) {
                throw $e;
            }

            return ['error' => 'Gagal memproses rekaman: ' . $e->getMessage()];
        }
    }

    /**
     * Antrekan ulang job yang gagal atau dibatalkan (Resume dari checkpoint terakhir).
     */
    public function requeueJob(int $jobId): array
    {
        $jobModel = new MeetingTranscriptionJobModel($this->db);
        $job = $jobModel->find($jobId);

        if (! $job) {
            return ['error' => 'Data antrean job notulen tidak ditemukan.'];
        }

        if (! in_array($job['status'], [MeetingTranscriptionJobModel::STATUS_FAILED, MeetingTranscriptionJobModel::STATUS_CANCELLED], true)) {
            return ['error' => 'Hanya job berstatus gagal atau dibatalkan yang dapat diproses ulang.'];
        }

        $jobModel->update($jobId, [
            'status'           => MeetingTranscriptionJobModel::STATUS_QUEUED,
            'cancel_requested' => 0,
            'error_message'    => null,
            'current_step'     => 'Menunggu antrean untuk diproses ulang (melanjutkan checkpoint)...',
        ]);

        $this->triggerWorkerAsync($jobId);

        return [
            'success' => true,
            'job_id'  => $jobId,
            'status'  => MeetingTranscriptionJobModel::STATUS_QUEUED,
            'message' => 'Job berhasil diantrekan ulang untuk diproses kembali.',
        ];
    }

    /**
     * Batalkan job yang sedang mengantre atau sedang berjalan.
     */
    public function requestCancel(int $jobId): array
    {
        $jobModel = new MeetingTranscriptionJobModel($this->db);
        $job = $jobModel->find($jobId);

        if (! $job) {
            return ['error' => 'Data job notulen tidak ditemukan.'];
        }

        if ($job['status'] === MeetingTranscriptionJobModel::STATUS_COMPLETED) {
            return ['error' => 'Job yang sudah selesai tidak dapat dibatalkan.'];
        }

        if ($job['status'] === MeetingTranscriptionJobModel::STATUS_QUEUED) {
            $jobModel->update($jobId, [
                'status'           => MeetingTranscriptionJobModel::STATUS_CANCELLED,
                'cancel_requested' => 0,
                'current_step'     => 'Dibatalkan oleh admin sebelum diproses.',
            ]);

            return [
                'success' => true,
                'status'  => MeetingTranscriptionJobModel::STATUS_CANCELLED,
                'message' => 'Job dalam antrean berhasil dibatalkan.',
            ];
        }

        // Job sedang in-progress (chunking, transcribing, summarizing) -> Set flag kooperatif
        $jobModel->update($jobId, [
            'cancel_requested' => 1,
            'current_step'     => 'Meminta pembatalan ke worker AI...',
        ]);

        return [
            'success' => true,
            'status'  => $job['status'],
            'message' => 'Permintaan pembatalan telah dikirim ke worker AI.',
        ];
    }

    /**
     * Hapus berkas rekaman audio lokal (Purge folder audio/ untuk menghemat disk storage).
     * Transkrip dan data risalah dipertahankan 100%.
     */
    public function purgeAudioFiles(int $jobId): array
    {
        $audioDir = $this->getJobDir($jobId) . DIRECTORY_SEPARATOR . 'audio';

        if (is_dir($audioDir)) {
            $files = glob($audioDir . DIRECTORY_SEPARATOR . '*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Berkas audio rekaman berhasil dibersihkan dari penyimpanan server.',
        ];
    }

    /**
     * Hapus record notulen dan purge seluruh folder job secara rekursif.
     */
    public function deleteNotulen(int $jobId): array
    {
        $this->db->transBegin();

        try {
            $minutesModel = new MeetingMinutesModel($this->db);
            $jobModel = new MeetingTranscriptionJobModel($this->db);

            $minutesModel->where('job_id', $jobId)->delete();
            $jobModel->delete($jobId);

            $this->db->transCommit();

            // Purge direktori fisik writable/uploads/recordings/job_{id}/
            $jobDir = $this->getJobDir($jobId);
            $this->deleteDirectoryRecursive($jobDir);

            return [
                'success' => true,
                'outcome' => 'deleted',
                'message' => 'Notulen dan seluruh berkas rekaman berhasil dihapus permanen.',
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->db->resetTransStatus();
            return ['error' => 'Gagal menghapus notulen: ' . $e->getMessage()];
        }
    }

    /**
     * Bersihkan notulen terkait saat jadwal-umum dihapus fisik.
     */
    public function purgeBySchedule(string $jadwalType, int $jadwalId): void
    {
        if (! $this->db->tableExists('meeting_transcription_jobs')) {
            return;
        }

        $jobModel = new MeetingTranscriptionJobModel($this->db);
        $jobs = $jobModel->where('jadwal_type', $jadwalType)
            ->where('jadwal_id', $jadwalId)
            ->findAll();

        foreach ($jobs as $job) {
            $this->deleteNotulen((int) $job['id']);
        }
    }

    /**
     * Membaca daftar transkrip per segmen waktu dari folder transcripts/.
     *
     * @return array{chunks: array<int, array<string, mixed>>, full_text: string, total_chunks: int}
     */
    public function readTranscripts(int $jobId): array
    {
        $transcriptsDir = $this->getJobDir($jobId) . DIRECTORY_SEPARATOR . 'transcripts';

        if (! is_dir($transcriptsDir)) {
            return [
                'chunks'       => [],
                'full_text'    => '',
                'total_chunks' => 0,
            ];
        }

        $files = glob($transcriptsDir . DIRECTORY_SEPARATOR . 'chunk_*.txt');
        if ($files === false || empty($files)) {
            return [
                'chunks'       => [],
                'full_text'    => '',
                'total_chunks' => 0,
            ];
        }

        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        $chunks = [];
        $fullTextParts = [];

        foreach ($files as $filePath) {
            // Abaikan jika file sementara .part
            if (str_ends_with($filePath, '.part')) {
                continue;
            }

            $filename = basename($filePath);
            $content = (string) file_get_contents($filePath);
            $trimmed = trim($content);

            preg_match('/chunk_(\d+)\.txt/i', $filename, $matches);
            $chunkIndex = isset($matches[1]) ? (int) $matches[1] : count($chunks) + 1;

            $startMin = ($chunkIndex - 1) * 30;
            $endMin = $startMin + 30;
            $timeLabel = sprintf('Menit %02d:00 - %02d:00', $startMin, $endMin);

            $chunks[] = [
                'index'      => $chunkIndex,
                'filename'   => $filename,
                'time_label' => $timeLabel,
                'text'       => $trimmed,
            ];

            if ($trimmed !== '') {
                $fullTextParts[] = "=== BAGIAN {$chunkIndex} ({$timeLabel}) ===\n" . $trimmed;
            }
        }

        return [
            'chunks'       => $chunks,
            'full_text'    => implode("\n\n", $fullTextParts),
            'total_chunks' => count($chunks),
        ];
    }

    /**
     * Memperbarui isi Risalah Rapat (editan notulis).
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function updateMinutes(int $minutesId, array $input, ?int $userId = null): array
    {
        $minutesModel = new MeetingMinutesModel($this->db);
        $minutes = $minutesModel->find($minutesId);

        if (! $minutes) {
            return ['error' => 'Data risalah rapat tidak ditemukan.'];
        }

        if ($minutes['status_verifikasi'] === MeetingMinutesModel::STATUS_FINAL) {
            return ['error' => 'Risalah rapat yang telah difinalisasi tidak dapat disunting. Silakan buka kunci revisi terlebih dahulu.'];
        }

        $ringkasan = trim((string) ($input['ringkasan_eksekutif'] ?? ''));

        $updateData = [
            'ringkasan_eksekutif' => $ringkasan,
        ];

        $minutesModel->update($minutesId, $updateData);

        return [
            'success' => true,
            'message' => 'Risalah rapat berhasil diperbarui.',
        ];
    }

    /**
     * Finalisasi status risalah rapat menjadi 'final'.
     */
    public function finalizeMinutes(int $minutesId, int $userId): array
    {
        $minutesModel = new MeetingMinutesModel($this->db);
        $minutes = $minutesModel->find($minutesId);

        if (! $minutes) {
            return ['error' => 'Data risalah rapat tidak ditemukan.'];
        }

        $minutesModel->update($minutesId, [
            'status_verifikasi' => MeetingMinutesModel::STATUS_FINAL,
            'verified_by'       => $userId,
            'verified_at'       => date('Y-m-d H:i:s'),
        ]);

        return [
            'success'           => true,
            'status_verifikasi' => MeetingMinutesModel::STATUS_FINAL,
            'message'           => 'Risalah rapat telah difinalisasi dan siap diakses.',
        ];
    }

    /**
     * Buka kunci revisi risalah rapat yang telah difinalisasi (kembali ke draft).
     */
    public function unfinalizeMinutes(int $minutesId, ?int $userId = null, string $alasan = ''): array
    {
        $minutesModel = new MeetingMinutesModel($this->db);
        $minutes = $minutesModel->find($minutesId);

        if (! $minutes) {
            return ['error' => 'Data risalah rapat tidak ditemukan.'];
        }

        $minutesModel->update($minutesId, [
            'status_verifikasi' => MeetingMinutesModel::STATUS_DRAFT,
            'verified_by'       => null,
            'verified_at'       => null,
        ]);

        return [
            'success'           => true,
            'status_verifikasi' => MeetingMinutesModel::STATUS_DRAFT,
            'message'           => 'Kunci naskah risalah berhasil dibuka. Mode penyuntingan/revisi kini aktif.',
        ];
    }

    /**
     * Dapatkan detail lengkap notulen (Job + Minutes + Transkrip + Resolved Schedule SSOT).
     */
    public function getNotulenDetail(int $jobId): ?array
    {
        $jobModel = new MeetingTranscriptionJobModel($this->db);
        $minutesModel = new MeetingMinutesModel($this->db);

        $job = $jobModel->find($jobId);
        if (! $job) {
            return null;
        }

        $minutes = $minutesModel->where('job_id', $jobId)->first();
        $transcripts = $this->readTranscripts($jobId);
        $schedule = $this->resolveScheduleInfo((string) ($job['jadwal_type'] ?? 'umum'), $job['jadwal_id'] ? (int) $job['jadwal_id'] : null);
        $pillars = $this->parsePillarsFromText($minutes['ringkasan_eksekutif'] ?? null);

        return [
            'job'         => $job,
            'minutes'     => $minutes,
            'schedule'    => $schedule,
            'transcripts' => $transcripts,
            'pillars'     => $pillars,
        ];
    }

    /**
     * Mengekstrak 3 Pilar (Ringkasan Utama, Poin Pembahasan ber-cap waktu, dan Kesimpulan) dari teks risalah.
     *
     * @return array{ringkasan_utama: string, poin_pembahasan: list<array{waktu: ?string, topik: string}>, kesimpulan_akhir: list<string>}
     */
    public function parsePillarsFromText(?string $rawText): array
    {
        $default = [
            'ringkasan_utama'  => '',
            'poin_pembahasan'  => [],
            'kesimpulan_akhir' => [],
        ];

        if (empty($rawText)) {
            return $default;
        }

        $text = trim($rawText);

        // Pola pemisahan 3 Bagian Romawi
        $p1Pattern = '/(?:^|\n)(?:I\.\s*RINGKASAN\s*UTAMA[^\n]*\n)([\s\S]*?)(?=(?:\nII\.\s*POIN|\nIII\.\s*KESIMPULAN|$))/i';
        $p2Pattern = '/(?:^|\n)(?:II\.\s*POIN[^\n]*\n)([\s\S]*?)(?=(?:\nIII\.\s*KESIMPULAN|$))/i';
        $p3Pattern = '/(?:^|\n)(?:III\.\s*KESIMPULAN[^\n]*\n)([\s\S]*?)$/i';

        $p1Match = [];
        $p2Match = [];
        $p3Match = [];

        preg_match($p1Pattern, $text, $p1Match);
        preg_match($p2Pattern, $text, $p2Match);
        preg_match($p3Pattern, $text, $p3Match);

        $p1Raw = trim($p1Match[1] ?? '');
        $p2Raw = trim($p2Match[1] ?? '');
        $p3Raw = trim($p3Match[1] ?? '');

        // Jika tidak cocok dengan pola 3 pilar romawi, jadikan seluruh teks sebagai ringkasan utama
        if (empty($p1Raw) && empty($p2Raw) && empty($p3Raw)) {
            $default['ringkasan_utama'] = $text;
            return $default;
        }

        $default['ringkasan_utama'] = $p1Raw;

        // Parse Poin-Poin Pembahasan (mencari cap waktu atau penomoran)
        if (! empty($p2Raw)) {
            $lines = explode("\n", $p2Raw);
            $currentTopic = null;
            $currentTime = null;

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }

                // Cek apakah baris diawali cap waktu (e.g. 00:12:45, [00:12:45], (12:45), 1. [00:12:45] Topik)
                if (preg_match('/^(?:(?:\d+[\.\)]\s*)?\[?(\d{1,2}:\d{2}(?::\d{2})?)\]?\s*[-:]?\s*)(.*)$/i', $trimmed, $timeMatches)) {
                    if ($currentTopic !== null) {
                        $default['poin_pembahasan'][] = [
                            'waktu' => $currentTime,
                            'topik' => trim($currentTopic),
                        ];
                    }
                    $currentTime = $timeMatches[1];
                    $currentTopic = $timeMatches[2];
                } elseif (preg_match('/^(?:\d+[\.\)]|\-|\*)\s+(.*)$/', $trimmed, $bulletMatches)) {
                    if ($currentTopic !== null) {
                        $default['poin_pembahasan'][] = [
                            'waktu' => $currentTime,
                            'topik' => trim($currentTopic),
                        ];
                    }
                    $currentTime = null;
                    $currentTopic = $bulletMatches[1];
                } else {
                    if ($currentTopic !== null) {
                        $currentTopic .= ' ' . $trimmed;
                    } else {
                        $currentTopic = $trimmed;
                    }
                }
            }

            if ($currentTopic !== null) {
                $default['poin_pembahasan'][] = [
                    'waktu' => $currentTime,
                    'topik' => trim($currentTopic),
                ];
            }
        }

        // Parse Kesimpulan & Keputusan Akhir (checklist butir)
        if (! empty($p3Raw)) {
            $lines = explode("\n", $p3Raw);
            $currentConclusion = null;

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }

                if (preg_match('/^(?:\d+[\.\)]|\-|\*|✔|✓)\s+(.*)$/u', $trimmed, $matches)) {
                    if ($currentConclusion !== null) {
                        $default['kesimpulan_akhir'][] = trim($currentConclusion);
                    }
                    $currentConclusion = $matches[1];
                } else {
                    if ($currentConclusion !== null) {
                        $currentConclusion .= ' ' . $trimmed;
                    } else {
                        $currentConclusion = $trimmed;
                    }
                }
            }

            if ($currentConclusion !== null) {
                $default['kesimpulan_akhir'][] = trim($currentConclusion);
            }
        }

        return $default;
    }

    /**
     * Pemicu asinkron worker lokal (berguna untuk lingkungan dev tanpa PM2 daemon).
     */
    public function triggerWorkerAsync(int $jobId): void
    {
        try {
            $workerScript = ROOTPATH . 'ai_worker' . DIRECTORY_SEPARATOR . 'worker.js';
            if (! file_exists($workerScript)) {
                return;
            }

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = 'cmd /c start "" /B node ' . escapeshellarg($workerScript) . ' --job-id=' . (int) $jobId;
                pclose(popen($command, 'r'));
            } else {
                $command = 'node ' . escapeshellarg($workerScript) . ' --job-id=' . (int) $jobId . ' > /dev/null 2>&1 &';
                exec($command);
            }
        } catch (\Throwable) {
            // Jangan gagalkan alur jika background trigger gagal dipanggil
        }
    }

    /**
     * Dapatkan judul, tanggal, dan metadata rapat dari SSOT jadwal.
     *
     * @return array{judul: string, tanggal: string, waktu_mulai: string, lokasi: string, ruangan: string}
     */
    public function resolveScheduleInfo(string $type, ?int $id): array
    {
        if ($id === null) {
            return [
                'judul'        => 'Rapat / Sidang DPRD',
                'tanggal'      => date('Y-m-d'),
                'waktu_mulai'  => '-',
                'lokasi'       => '-',
                'ruangan'      => 'Ruang Rapat Paripurna DPRD Provinsi Sulawesi Tengah',
            ];
        }

        try {
            if ($type === MeetingTranscriptionJobModel::TYPE_UMUM) {
                $item = (new JadwalUmumModel($this->db))->find($id);
                if ($item) {
                    $ruanganName = null;
                    if (! empty($item['ruangan_id'])) {
                        $room = (new RuanganModel($this->db))->find((int) $item['ruangan_id']);
                        $ruanganName = $room['nama'] ?? null;
                    }
                    $lokasi = (string) ($item['lokasi_lainnya'] ?? '');
                    return [
                        'judul'        => (string) ($item['judul'] ?? 'Rapat Umum DPRD'),
                        'tanggal'      => (string) ($item['tanggal'] ?? date('Y-m-d')),
                        'waktu_mulai'  => (string) ($item['waktu_mulai'] ?? '-'),
                        'lokasi'       => $lokasi !== '' ? $lokasi : ($ruanganName ?? '-'),
                        'ruangan'      => $ruanganName ?? ($lokasi !== '' ? $lokasi : 'Ruang Rapat Paripurna DPRD Provinsi Sulawesi Tengah'),
                    ];
                }
            }

            if ($type === MeetingTranscriptionJobModel::TYPE_BANMUS) {
                $item = (new JadwalBanmusModel($this->db))->find($id);
                if ($item) {
                    $ruanganName = null;
                    if (! empty($item['ruangan_id'])) {
                        $room = (new RuanganModel($this->db))->find((int) $item['ruangan_id']);
                        $ruanganName = $room['nama'] ?? null;
                    }
                    $lokasi = (string) ($item['lokasi_lainnya'] ?? '');
                    return [
                        'judul'        => (string) ($item['agenda'] ?? 'Rapat Badan Musyawarah'),
                        'tanggal'      => (string) ($item['tanggal'] ?? date('Y-m-d')),
                        'waktu_mulai'  => (string) ($item['jam_mulai'] ?? '-'),
                        'lokasi'       => $lokasi !== '' ? $lokasi : ($ruanganName ?? '-'),
                        'ruangan'      => $ruanganName ?? ($lokasi !== '' ? $lokasi : 'Ruang Rapat Paripurna DPRD Provinsi Sulawesi Tengah'),
                    ];
                }
            }
        } catch (\Throwable) {
            // Fallback default
        }

        return [
            'judul'        => 'Rapat DPRD',
            'tanggal'      => date('Y-m-d'),
            'waktu_mulai'  => '-',
            'lokasi'       => '-',
            'ruangan'      => 'Ruang Rapat Paripurna DPRD Provinsi Sulawesi Tengah',
        ];
    }

    /**
     * Mengambil peta jadwal (SSOT) untuk daftar jobs transkripsi.
     *
     * @param list<array<string, mixed>> $jobs
     * @return array<string, array<int, array{judul: string, tanggal: string, waktu_mulai: string, lokasi: string}>>
     */
    public function resolveSchedulesForJobs(array $jobs): array
    {
        $umumIds = [];
        $banmusIds = [];

        foreach ($jobs as $job) {
            $jid = ! empty($job['jadwal_id']) ? (int) $job['jadwal_id'] : null;
            if ($jid === null) {
                continue;
            }
            if (($job['jadwal_type'] ?? 'umum') === MeetingTranscriptionJobModel::TYPE_BANMUS) {
                $banmusIds[$jid] = true;
            } else {
                $umumIds[$jid] = true;
            }
        }

        $schedulesMap = [
            'umum'   => [],
            'banmus' => [],
        ];

        if (! empty($umumIds)) {
            $items = (new JadwalUmumModel($this->db))
                ->whereIn('id', array_keys($umumIds))
                ->findAll();
            foreach ($items as $item) {
                $schedulesMap['umum'][(int) $item['id']] = [
                    'judul'       => (string) ($item['judul'] ?? 'Rapat Umum DPRD'),
                    'tanggal'     => (string) ($item['tanggal'] ?? date('Y-m-d')),
                    'waktu_mulai' => (string) ($item['waktu_mulai'] ?? '-'),
                    'lokasi'      => (string) ($item['lokasi_lainnya'] ?? '-'),
                ];
            }
        }

        if (! empty($banmusIds)) {
            $items = (new JadwalBanmusModel($this->db))
                ->whereIn('id', array_keys($banmusIds))
                ->findAll();
            foreach ($items as $item) {
                $schedulesMap['banmus'][(int) $item['id']] = [
                    'judul'       => (string) ($item['agenda'] ?? 'Rapat Badan Musyawarah'),
                    'tanggal'     => (string) ($item['tanggal'] ?? date('Y-m-d')),
                    'waktu_mulai' => (string) ($item['jam_mulai'] ?? '-'),
                    'lokasi'      => (string) ($item['lokasi_lainnya'] ?? '-'),
                ];
            }
        }

        return $schedulesMap;
    }

    private function resolveScheduleTitle(string $type, int $id): string
    {
        return $this->resolveScheduleInfo($type, $id)['judul'];
    }

    private function resolveScheduleDate(string $type, ?int $id): ?string
    {
        return $this->resolveScheduleInfo($type, $id)['tanggal'];
    }

    /**
     * Mencari path absolut file audio fisik rekaman job transkripsi.
     */
    public function resolveAudioPath(int $jobId): ?string
    {
        $job = (new MeetingTranscriptionJobModel($this->db))->find($jobId);
        if (! $job) {
            return null;
        }

        $jobDir = $this->getJobDir($jobId);
        $audioDir = $jobDir . DIRECTORY_SEPARATOR . 'audio';

        // Cek file audio langsung di folder audio job
        if (is_dir($audioDir)) {
            $files = glob($audioDir . DIRECTORY_SEPARATOR . '*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (in_array($ext, ['mp3', 'm4a', 'wav', 'aac', 'flac', 'ogg', 'mp4', 'webm'], true)) {
                            return $file;
                        }
                    }
                }
            }
        }

        // Cek fallback audio_path di database
        if (! empty($job['audio_path'])) {
            $candidate1 = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . ltrim(str_replace('writable/uploads/', '', $job['audio_path']), '/\\');
            if (is_file($candidate1)) {
                return $candidate1;
            }
            $candidate2 = WRITEPATH . ltrim($job['audio_path'], '/\\');
            if (is_file($candidate2)) {
                return $candidate2;
            }
            $candidate3 = FCPATH . ltrim($job['audio_path'], '/\\');
            if (is_file($candidate3)) {
                return $candidate3;
            }
        }

        return null;
    }

    private function deleteDirectoryRecursive(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectoryRecursive($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
