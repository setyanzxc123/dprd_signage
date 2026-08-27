<?php

namespace App\Models;

use CodeIgniter\Model;

class MeetingTranscriptionJobModel extends Model
{
    public const STATUS_QUEUED       = 'queued';
    public const STATUS_CHUNKING     = 'chunking';
    public const STATUS_TRANSCRIBING = 'transcribing';
    public const STATUS_SUMMARIZING  = 'summarizing';
    public const STATUS_COMPLETED    = 'completed';
    public const STATUS_FAILED       = 'failed';
    public const STATUS_CANCELLED    = 'cancelled';

    public const TYPE_UMUM   = 'umum';
    public const TYPE_BANMUS = 'banmus';

    public const VALID_STATUSES = [
        self::STATUS_QUEUED,
        self::STATUS_CHUNKING,
        self::STATUS_TRANSCRIBING,
        self::STATUS_SUMMARIZING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    protected $table         = 'meeting_transcription_jobs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'jadwal_type',
        'jadwal_id',
        'audio_filename',
        'audio_path',
        'audio_size',
        'audio_duration',
        'status',
        'cancel_requested',
        'total_chunks',
        'completed_chunks',
        'progress_percent',
        'current_step',
        'error_message',
        'created_by',
    ];

    /**
     * Cari job terbaru berdasarkan jadwal.
     */
    public function findLatestByJadwal(string $jadwalType, int $jadwalId): ?array
    {
        return $this->where('jadwal_type', $jadwalType)
            ->where('jadwal_id', $jadwalId)
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Hitung ringkasan status job untuk dashboard monitoring.
     */
    public function getStatusCounts(): array
    {
        $rows = $this->select('status, COUNT(*) as total')
            ->groupBy('status')
            ->findAll();

        $counts = [
            self::STATUS_QUEUED       => 0,
            self::STATUS_CHUNKING     => 0,
            self::STATUS_TRANSCRIBING => 0,
            self::STATUS_SUMMARIZING  => 0,
            self::STATUS_COMPLETED    => 0,
            self::STATUS_FAILED       => 0,
            self::STATUS_CANCELLED    => 0,
        ];

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        $counts['in_progress'] = $counts[self::STATUS_CHUNKING]
            + $counts[self::STATUS_TRANSCRIBING]
            + $counts[self::STATUS_SUMMARIZING];

        return $counts;
    }
}
