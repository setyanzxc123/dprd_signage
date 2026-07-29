<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Schedule\ScheduleReadService;
use App\Models\AgendaUmumModel;

class PublicController extends BaseController
{
    /**
     * GET api/v1/publik/jadwal
     * GET api/v1/publik/jadwal?date=YYYY-MM-DD
     * GET api/v1/publik/jadwal?month=YYYY-MM
     */
    public function jadwal()
    {
        $result = (new ScheduleReadService())->publicAgenda([
            'date'  => $this->request->getGet('date'),
            'month' => $this->request->getGet('month'),
            'unit'  => $this->request->getGet('unit'),
        ]);
        $result['data'] = array_map(static function (array $schedule): array {
            $id = (int) ($schedule['source_id'] ?? $schedule['id']);
            $path = ($schedule['source'] ?? 'jadwal') === 'banmus'
                ? 'go/jadwal-banmus'
                : 'go/jadwal';
            if ($schedule['has_materi']) {
                $schedule['materi_url'] = base_url("{$path}/{$id}/berkas");
            }
            if ($schedule['has_stream']) {
                $schedule['stream_url'] = base_url("{$path}/{$id}/live");
            }

            return $schedule;
        }, $result['data']);

        return $this->response
            ->setHeader('Cache-Control', 'public, max-age=60, stale-while-revalidate=120')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setJSON(['status' => 'success', ...$result]);
    }

    /**
     * GET api/v1/publik/agenda-umum
     * GET api/v1/publik/agenda-umum?date=YYYY-MM-DD
     * GET api/v1/publik/agenda-umum?month=YYYY-MM
     */
    public function agendaUmum()
    {
        $model = new AgendaUmumModel();
        $model->where('is_publik', 1);

        $date = trim((string) $this->request->getGet('date'));
        $month = trim((string) $this->request->getGet('month'));
        if ($this->validDate($date)) {
            $model->where('tanggal', $date);
        } else {
            $selectedMonth = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1
                ? $month
                : date('Y-m');
            $start = $selectedMonth . '-01';
            $model
                ->where('tanggal >=', $start)
                ->where('tanggal <=', date('Y-m-t', strtotime($start)));
        }

        $rows = $model
            ->orderBy('tanggal', 'ASC')
            ->orderBy('waktu_mulai', 'ASC')
            ->findAll(100);

        $data = array_map(static fn (array $row): array => [
            'id'                => (int) $row['id'],
            'judul'             => (string) $row['judul'],
            'kategori'          => (string) $row['kategori'],
            'tanggal'           => (string) $row['tanggal'],
            'waktu_mulai'       => substr((string) $row['waktu_mulai'], 0, 5),
            'waktu_selesai'     => $row['waktu_selesai'] !== null
                ? substr((string) $row['waktu_selesai'], 0, 5)
                : null,
            'lokasi'            => (string) $row['lokasi'],
            'sumber_informasi'  => $row['sumber_informasi'],
            'perkiraan_peserta' => $row['perkiraan_peserta'] !== null
                ? (int) $row['perkiraan_peserta']
                : null,
            'keterangan'        => $row['keterangan'],
            'status'            => (string) $row['status'],
        ], $rows);

        return $this->response
            ->setHeader('Cache-Control', 'public, max-age=60, stale-while-revalidate=120')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setJSON(['status' => 'success', 'data' => $data]);
    }

    private function validDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }
}
