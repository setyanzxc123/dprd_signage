<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Schedule\ScheduleResourceLinkService;

/**
 * Resolve URL materi/live jadwal untuk aplikasi mobile anggota —
 * pengganti redirect ber-session (rute live/berkas di portal anggota)
 * yang tidak bisa dipakai bearer token. Aturan akses identik versi
 * web karena sama-sama menempuh ScheduleResourceLinkService.
 */
class ScheduleResourceController extends BaseController
{
    use ApiResponse;

    private const SOURCE_MAP = [
        'banmus'      => ScheduleResourceLinkService::SOURCE_BANMUS,
        'jadwal-umum' => ScheduleResourceLinkService::SOURCE_GENERAL,
    ];

    public function resolve(string $source, int $id, string $resource)
    {
        $mappedSource = self::SOURCE_MAP[$source] ?? null;

        if ($mappedSource === null) {
            return $this->apiError('Sumber jadwal tidak dikenali.', 404);
        }

        $memberId = service('requestIdentity')->currentAnggotaId();
        $url = (new ScheduleResourceLinkService())->memberUrl($mappedSource, $id, $resource, $memberId);

        if ($url === null) {
            return $this->apiError('Resource tidak tersedia atau tidak dapat Anda akses.', 403);
        }

        return $this->apiSuccess(['url' => $url]);
    }
}
