<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Schedule\GeneralAgendaReadService;
use App\Libraries\Schedule\ScheduleReadService;

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
        $result = (new GeneralAgendaReadService())->read([
            'date'  => $this->request->getGet('date'),
            'month' => $this->request->getGet('month'),
        ], false);

        return $this->response
            ->setHeader('Cache-Control', 'public, max-age=60, stale-while-revalidate=120')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setJSON(['status' => 'success', ...$result]);
    }
}
