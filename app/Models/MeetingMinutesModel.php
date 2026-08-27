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
        'jadwal_type',
        'jadwal_id',
        'judul_rapat',
        'tanggal_rapat',
        'transcripts_dir',
        'ringkasan_eksekutif',
        'agenda_pembahasan',
        'kesimpulan',
        'tindak_lanjut',
        'peserta_terdeteksi',
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
     * Cari risalah terbaru berdasarkan jadwal.
     */
    public function findLatestByJadwal(string $jadwalType, int $jadwalId): ?array
    {
        return $this->where('jadwal_type', $jadwalType)
            ->where('jadwal_id', $jadwalId)
            ->orderBy('id', 'DESC')
            ->first();
    }
}
