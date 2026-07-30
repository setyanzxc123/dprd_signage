<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
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
            if (($schedule['source'] ?? '') === 'banmus' && $schedule['has_materi']) {
                $schedule['materi_url'] = base_url("go/jadwal-banmus/{$id}/berkas");
            }
            if (($schedule['source'] ?? '') === 'banmus' && $schedule['has_stream']) {
                $schedule['stream_url'] = base_url("go/jadwal-banmus/{$id}/live");
            }

            return $schedule;
        }, $result['data']);

        return $this->response
            ->setHeader('Cache-Control', 'public, max-age=60, stale-while-revalidate=120')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setJSON(['status' => 'success', ...$result]);
    }

}
