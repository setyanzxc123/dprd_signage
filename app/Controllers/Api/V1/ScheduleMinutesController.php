<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Models\MeetingMinutesModel;
use App\Models\MeetingTranscriptionJobModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Endpoint satu pintu untuk mengakses risalah rapat resmi terstruktur
 * bagi anggota dewan dan admin melalui aplikasi mobile.
 */
class ScheduleMinutesController extends BaseController
{
    use ApiResponse;

    private const SOURCE_MAP = [
        'umum'        => 'umum',
        'jadwal-umum' => 'umum',
        'banmus'      => 'banmus',
    ];

    /**
     * GET api/v1/jadwal/{sumber}/{id}/risalah
     */
    public function show(string $source, int $id): ResponseInterface
    {
        $normalizedSource = self::SOURCE_MAP[$source] ?? null;

        if ($normalizedSource === null) {
            return $this->apiError('Sumber jadwal tidak dikenali.', 404);
        }

        $jobModel = new MeetingTranscriptionJobModel();
        $job = $jobModel->where('jadwal_type', $normalizedSource)
            ->where('jadwal_id', $id)
            ->orderBy('id', 'DESC')
            ->first();

        $minutes = null;
        if ($job) {
            $minutes = (new MeetingMinutesModel())->findByJobId((int) $job['id']);
        }

        $scheduleInfo = (new \App\Libraries\Notulen\NotulenService())->resolveScheduleInfo($normalizedSource, $id);

        if (! $minutes || empty($minutes['ringkasan_eksekutif'])) {
            return $this->apiSuccess([
                'jadwal_type'       => $normalizedSource,
                'jadwal_id'         => $id,
                'risalah_tersedia'  => false,
                'status_verifikasi' => $minutes['status_verifikasi'] ?? null,
                'job_status'        => $job['status'] ?? null,
                'message'           => 'Risalah rapat belum tersedia atau masih dalam proses transkripsi AI.',
                'risalah'           => null,
            ]);
        }

        $isMember = service('requestIdentity')->currentAnggota() !== null;

        // Jika user adalah anggota dan status belum 'final', sembunyikan draft belum terverifikasi
        if ($isMember && ($minutes['status_verifikasi'] ?? 'draft') !== 'final') {
            return $this->apiSuccess([
                'jadwal_type'       => $normalizedSource,
                'jadwal_id'         => $id,
                'risalah_tersedia'  => false,
                'status_verifikasi' => $minutes['status_verifikasi'] ?? 'draft',
                'message'           => 'Risalah rapat sedang dalam tahap peninjauan (draft) oleh notulis.',
                'risalah'           => null,
            ]);
        }

        $notulenService = new \App\Libraries\Notulen\NotulenService();
        $pillars = null;
        if (! empty($minutes['struktur_json'])) {
            $decoded = json_decode((string) $minutes['struktur_json'], true);
            if (is_array($decoded) && isset($decoded['ringkasan_utama'], $decoded['poin_pembahasan'], $decoded['kesimpulan_akhir'])) {
                $pillars = $decoded;
            }
        }
        if ($pillars === null) {
            $pillars = $notulenService->parsePillarsFromText($minutes['ringkasan_eksekutif'] ?? null);
        }

        return $this->apiSuccess([
            'jadwal_type'         => $normalizedSource,
            'jadwal_id'           => $id,
            'risalah_tersedia'    => true,
            'status_verifikasi'   => $minutes['status_verifikasi'],
            'judul_rapat'         => $scheduleInfo['judul'],
            'tanggal_rapat'       => $scheduleInfo['tanggal'],
            'ringkasan_eksekutif' => $minutes['ringkasan_eksekutif'],
            'tiga_pilar'          => $pillars,
            'verified_at'         => $minutes['verified_at'] ?? null,
        ]);
    }
}
