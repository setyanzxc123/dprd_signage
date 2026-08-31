<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Schedule\ScheduleReadService;

class MemberScheduleController extends BaseController
{
    public function jadwal()
    {
        $anggota = service('requestIdentity')->currentAnggota();
        $memberId = (int) ($anggota['anggota_id'] ?? 0);

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
            $source = (string) ($schedule['source'] ?? '');
            $routeSource = $source === 'banmus' ? 'jadwal-banmus' : ($source === 'jadwal_umum' ? 'jadwal-umum' : null);
            if ($routeSource !== null && $schedule['has_materi']) {
                $schedule['materi_url'] = base_url("anggota/{$routeSource}/{$id}/berkas");
            }
            if ($routeSource !== null && $schedule['has_stream']) {
                $schedule['stream_url'] = base_url("anggota/{$routeSource}/{$id}/live");
            }
            if ($routeSource !== null && ($schedule['has_undangan'] ?? false)) {
                $schedule['undangan_url'] = base_url("anggota/{$routeSource}/{$id}/undangan");
            }

            return $schedule;
        }, $result['data']);

        // Ketersediaan risalah final per jadwal (tautan risalah di /agenda mobile).
        $umumIds = [];
        $banmusIds = [];
        foreach ($result['data'] as $schedule) {
            $id = (int) ($schedule['source_id'] ?? $schedule['id']);
            $source = (string) ($schedule['source'] ?? '');
            if ($id < 1) {
                continue;
            }
            if ($source === 'banmus') {
                $banmusIds[$id] = true;
            } elseif ($source === 'jadwal_umum') {
                $umumIds[$id] = true;
            }
        }
        $finalMap = (new \App\Libraries\Notulen\NotulenService())->resolveFinalMinutesMap(
            array_keys($umumIds),
            array_keys($banmusIds)
        );

        $result['data'] = array_map(static function (array $schedule) use ($finalMap): array {
            $id = (int) ($schedule['source_id'] ?? $schedule['id']);
            $source = (string) ($schedule['source'] ?? '');
            $apiSource = $source === 'banmus' ? 'banmus' : 'umum';
            $hasFinal = $finalMap[$apiSource][$id] ?? false;
            $schedule['risalah_tersedia'] = $hasFinal;
            if ($hasFinal) {
                $schedule['risalah_url'] = base_url("api/v1/jadwal/{$apiSource}/{$id}/risalah");
            }

            return $schedule;
        }, $result['data']);

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Pragma', 'no-cache')
            ->setJSON(['status' => 'success', ...$result]);
    }
}
