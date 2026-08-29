<?php

namespace App\Models;

use CodeIgniter\Model;

class MeetingMinutesModel extends Model
{
    public const STATUS_DRAFT    = 'draft';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_FINAL    = 'final';

    public const TYPE_UMUM   = 'umum';
    public const TYPE_BANMUS = 'banmus';

    public const VALID_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REVIEWED,
        self::STATUS_FINAL,
    ];

    protected $table         = 'meeting_minutes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'job_id',
        'transcripts_dir',
        'ringkasan_eksekutif',
        'struktur_json',
        'status_verifikasi',
        'verified_by',
        'verified_at',
    ];

    /**
     * Cari risalah berdasarkan job_id.
     */
    public function findByJobId(int $jobId): ?array
    {
        return $this->where('job_id', $jobId)->first();
    }

    /**
     * Cari risalah terbaru berdasarkan jadwal (JOIN via meeting_transcription_jobs).
     */
    public function findLatestByJadwal(string $jadwalType, int $jadwalId): ?array
    {
        return $this->select('meeting_minutes.*, meeting_transcription_jobs.jadwal_type, meeting_transcription_jobs.jadwal_id')
            ->join('meeting_transcription_jobs', 'meeting_transcription_jobs.id = meeting_minutes.job_id')
            ->where('meeting_transcription_jobs.jadwal_type', $jadwalType)
            ->where('meeting_transcription_jobs.jadwal_id', $jadwalId)
            ->orderBy('meeting_minutes.id', 'DESC')
            ->first();
    }
}
