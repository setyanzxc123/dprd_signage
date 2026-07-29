<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Schedule\ScheduleReadService;

class MemberScheduleController extends BaseController
{
    public function jadwal()
    {
        $auth = session()->get('member_auth');
        $memberId = is_array($auth) ? (int) ($auth['anggota_id'] ?? 0) : 0;
        if ($memberId < 1) {
            return $this->response
                ->setStatusCode(401)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setJSON(['status' => 'error', 'message' => 'Session anggota tidak valid.']);
        }

        $result = (new ScheduleReadService())->memberAgenda($memberId, [
            'date'  => $this->request->getGet('date'),
            'month' => $this->request->getGet('month'),
            'unit'  => $this->request->getGet('unit'),
            'scope' => $this->request->getGet('scope'),
        ]);

        $result['data'] = array_map(static function (array $schedule): array {
            $id = (int) ($schedule['source_id'] ?? $schedule['id']);
            $path = ($schedule['source'] ?? 'jadwal') === 'banmus'
                ? 'anggota/jadwal-banmus'
                : 'anggota/jadwal';
            if ($schedule['has_materi']) {
                $schedule['materi_url'] = base_url("{$path}/{$id}/berkas");
            }
            if ($schedule['has_stream']) {
                $schedule['stream_url'] = base_url("{$path}/{$id}/live");
            }

            return $schedule;
        }, $result['data']);

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Pragma', 'no-cache')
            ->setJSON(['status' => 'success', ...$result]);
    }
}
