<?php

namespace App\Libraries\Notulen;

use App\Models\JadwalBanmusItemModel;
use App\Models\JadwalUmumModel;
use App\Models\MeetingMinutesModel;
use App\Models\MeetingTranscriptionJobModel;
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

            // Inisialisasi draft record meeting_minutes jika belum ada
            $minutesModel = new MeetingMinutesModel($this->db);
            $existingMinutes = $minutesModel->where('job_id', $jobId)->first();

            if (! $existingMinutes) {
                $minutesModel->insert([
                    'job_id'             => $jobId,
                    'jadwal_type'        => $validated['jadwal_type'],
                    'jadwal_id'          => $validated['jadwal_id'],
                    'judul_rapat'        => $validated['judul_rapat'],
                    'tanggal_rapat'      => $this->resolveScheduleDate($validated['jadwal_type'], $validated['jadwal_id']),
                    'transcripts_dir'    => 'recordings/job_' . $jobId . '/transcripts',
                    'ringkasan_eksekutif'=> null,
                    'agenda_pembahasan'  => null,
                    'kesimpulan'         => null,
                    'tindak_lanjut'      => null,
                    'peserta_terdeteksi' => null,
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

        $judulRapat = trim((string) ($input['judul_rapat'] ?? $minutes['judul_rapat']));
        if ($judulRapat === '') {
            return ['error' => 'Judul rapat wajib diisi.'];
        }

        $ringkasan = trim((string) ($input['ringkasan_eksekutif'] ?? ''));

        // Normalisasi struktur JSON agenda, kesimpulan, tindak lanjut, peserta
        $agenda = $this->normalizeJsonField($input['agenda_pembahasan'] ?? null);
        $kesimpulan = $this->normalizeJsonField($input['kesimpulan'] ?? null);
        $tindakLanjut = $this->normalizeJsonField($input['tindak_lanjut'] ?? null);
        $peserta = $this->normalizeJsonField($input['peserta_terdeteksi'] ?? null);

        $updateData = [
            'judul_rapat'        => $judulRapat,
            'tanggal_rapat'      => ! empty($input['tanggal_rapat']) ? $input['tanggal_rapat'] : $minutes['tanggal_rapat'],
            'ringkasan_eksekutif'=> $ringkasan,
            'agenda_pembahasan'  => $agenda,
            'kesimpulan'         => $kesimpulan,
            'tindak_lanjut'      => $tindakLanjut,
            'peserta_terdeteksi' => $peserta,
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
     * Dapatkan detail lengkap notulen (Job + Minutes + Transkrip).
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

        // Decode JSON fields untuk tampilan web
        $decodedAgenda = ! empty($minutes['agenda_pembahasan']) ? json_decode((string) $minutes['agenda_pembahasan'], true) : [];
        $decodedKesimpulan = ! empty($minutes['kesimpulan']) ? json_decode((string) $minutes['kesimpulan'], true) : [];
        $decodedTindakLanjut = ! empty($minutes['tindak_lanjut']) ? json_decode((string) $minutes['tindak_lanjut'], true) : [];
        $decodedPeserta = ! empty($minutes['peserta_terdeteksi']) ? json_decode((string) $minutes['peserta_terdeteksi'], true) : [];

        return [
            'job'                => $job,
            'minutes'            => $minutes,
            'transcripts'        => $transcripts,
            'agenda_items'       => is_array($decodedAgenda) ? $decodedAgenda : [],
            'kesimpulan_items'   => is_array($decodedKesimpulan) ? $decodedKesimpulan : [],
            'tindak_lanjut_items'=> is_array($decodedTindakLanjut) ? $decodedTindakLanjut : [],
            'peserta_items'      => is_array($decodedPeserta) ? $decodedPeserta : [],
        ];
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

            $command = 'node ' . escapeshellarg($workerScript) . ' --job-id=' . (int) $jobId;

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen('start /B ' . $command, 'r'));
            } else {
                exec($command . ' > /dev/null 2>&1 &');
            }
        } catch (\Throwable) {
            // Jangan gagalkan alur jika background trigger gagal dipanggil
        }
    }

    private function resolveScheduleTitle(string $type, int $id): string
    {
        try {
            if ($type === MeetingTranscriptionJobModel::TYPE_UMUM) {
                $item = (new JadwalUmumModel($this->db))->find($id);
                return (string) ($item['judul'] ?? '');
            }
            if ($type === MeetingTranscriptionJobModel::TYPE_BANMUS) {
                $item = (new JadwalBanmusItemModel($this->db))->find($id);
                return (string) ($item['agenda'] ?? '');
            }
        } catch (\Throwable) {
            return '';
        }
        return '';
    }

    private function resolveScheduleDate(string $type, ?int $id): ?string
    {
        if ($id === null) {
            return date('Y-m-d');
        }

        try {
            if ($type === MeetingTranscriptionJobModel::TYPE_UMUM) {
                $item = (new JadwalUmumModel($this->db))->find($id);
                return $item['tanggal'] ?? date('Y-m-d');
            }
            if ($type === MeetingTranscriptionJobModel::TYPE_BANMUS) {
                $item = (new JadwalBanmusItemModel($this->db))->find($id);
                return $item['tanggal'] ?? date('Y-m-d');
            }
        } catch (\Throwable) {
            return date('Y-m-d');
        }
        return date('Y-m-d');
    }

    private function normalizeJsonField(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }
            // Jika sudah format JSON valid
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
            // Jika teks multiline dipisah baris baru
            $lines = array_filter(array_map('trim', explode("\n", $trimmed)), fn ($line) => $line !== '');
            return json_encode(array_values($lines), JSON_UNESCAPED_UNICODE);
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
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
