<?php

namespace App\Controllers\Member;

use App\Controllers\BaseController;
use App\Libraries\Notulen\NotulenService;
use App\Models\MeetingMinutesModel;
use App\Models\MeetingTranscriptionJobModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Dompdf\Dompdf;

class ScheduleMinutesController extends BaseController
{
    public function banmusPdf(int $id): ResponseInterface
    {
        return $this->servePdf('banmus', $id);
    }

    public function generalPdf(int $id): ResponseInterface
    {
        return $this->servePdf('umum', $id);
    }

    private function servePdf(string $source, int $id): ResponseInterface
    {
        $auth = session()->get('member_auth');
        if (! is_array($auth) || (int) ($auth['anggota_id'] ?? 0) < 1 || $id < 1) {
            throw PageNotFoundException::forPageNotFound();
        }

        $job = (new MeetingTranscriptionJobModel())
            ->where('jadwal_type', $source)
            ->where('jadwal_id', $id)
            ->orderBy('id', 'DESC')
            ->first();

        if (! $job || $job['status'] !== MeetingTranscriptionJobModel::STATUS_COMPLETED) {
            throw PageNotFoundException::forPageNotFound();
        }

        $minutes = (new MeetingMinutesModel())->findByJobId((int) $job['id']);
        if (! $minutes || empty($minutes['ringkasan_eksekutif']) || ($minutes['status_verifikasi'] ?? 'draft') !== 'final') {
            throw PageNotFoundException::forPageNotFound();
        }

        $schedule = (new NotulenService())->resolveScheduleInfo($source, $id);

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

        $dompdf = new Dompdf(['isRemoteEnabled' => false]);
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
