<?php

namespace App\Models;

use CodeIgniter\Model;

class MeetingMinutesModel extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINAL = 'final';

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
}
