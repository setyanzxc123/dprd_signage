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
        'umum'          => 'umum',
        'jadwal-umum'   => 'umum',
        'banmus'        => 'banmus',
        'jadwal-banmus' => 'banmus',
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

        $routeSource = $normalizedSource === 'banmus' ? 'jadwal-banmus' : 'jadwal-umum';
        $waktuMulai = ! empty($scheduleInfo['waktu_mulai']) && $scheduleInfo['waktu_mulai'] !== '-'
            ? substr((string) $scheduleInfo['waktu_mulai'], 0, 5) . ' WITA'
            : '09:00 WITA';

        return $this->apiSuccess([
            'jadwal_type'         => $normalizedSource,
            'jadwal_id'           => $id,
            'risalah_tersedia'    => true,
            'status_verifikasi'   => $minutes['status_verifikasi'],
            'judul_rapat'         => $scheduleInfo['judul'],
            'tanggal_rapat'       => $scheduleInfo['tanggal'],
            'waktu_mulai'         => $waktuMulai,
            'ruangan'             => $scheduleInfo['ruangan'] ?? 'Ruang Rapat Paripurna DPRD Provinsi Sulawesi Tengah',
            'ringkasan_eksekutif' => $minutes['ringkasan_eksekutif'],
            'tiga_pilar'          => $pillars,
            'verified_at'         => $minutes['verified_at'] ?? null,
            'pdf_url'             => base_url("anggota/{$routeSource}/{$id}/risalah-pdf"),
            'api_pdf_url'         => base_url("api/v1/jadwal/{$normalizedSource}/{$id}/risalah-pdf"),
        ]);
    }

    /**
     * GET api/v1/jadwal/{sumber}/{id}/risalah-pdf
     */
    public function exportPdf(string $source, int $id): ResponseInterface
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

        if (! $job || $job['status'] !== MeetingTranscriptionJobModel::STATUS_COMPLETED) {
            return $this->apiError('Risalah rapat belum tersedia.', 404);
        }

        $minutes = (new MeetingMinutesModel())->findByJobId((int) $job['id']);
        if (! $minutes || empty($minutes['ringkasan_eksekutif'])) {
            return $this->apiError('Naskah risalah rapat belum tersedia.', 404);
        }

        $isMember = service('requestIdentity')->currentAnggota() !== null;
        if ($isMember && ($minutes['status_verifikasi'] ?? 'draft') !== 'final') {
            return $this->apiForbidden('Risalah rapat masih dalam tahap peninjauan oleh notulis.');
        }

        $schedule = (new \App\Libraries\Notulen\NotulenService())->resolveScheduleInfo($normalizedSource, $id);

        $judulRapat   = $schedule['judul'] !== '' ? $schedule['judul'] : (string) $job['audio_filename'];
        $tanggalRapat = $schedule['tanggal'] !== '' ? $schedule['tanggal'] : substr((string) $job['created_at'], 0, 10);
        $waktuMulai   = ! empty($schedule['waktu_mulai']) && $schedule['waktu_mulai'] !== '-'
            ? substr((string) $schedule['waktu_mulai'], 0, 5) . ' WITA'
            : '09:00 WITA';

        $html = view('admin/notulen/pdf', [
            'pageTitle'    => 'Risalah Rapat - ' . $judulRapat,
            'minutes'      => $minutes,
            'judulRapat'   => $judulRapat,
            'tanggalRapat' => $tanggalRapat,
            'waktuMulai'   => $waktuMulai,
        ]);

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => false,
            'defaultFont'     => 'Times-Roman',
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName = 'Risalah_' . preg_replace('/[^A-Za-z0-9]+/', '_', $judulRapat) . '_' . date('Ymd', strtotime((string) $tanggalRapat)) . '.pdf';

        return $this->response
            ->setStatusCode(200)
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setBody($dompdf->output());
    }
}
