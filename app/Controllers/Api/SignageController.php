<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Schedule\ScheduleReadService;
use App\Libraries\Weather\BmkgWeatherService;

class SignageController extends BaseController
{
    /**
     * GET api/signage/jadwal
     *
     * Format respons lama dipertahankan untuk UI TV.
     */
    public function jadwal()
    {
        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON((new ScheduleReadService())->signage());
    }

    /**
     * GET api/signage/cuaca
     *
     * Cache server: fresh 30 menit, lalu stale-if-error hingga 24 jam.
     */
    public function cuaca()
    {
        return $this->weatherResponse((new BmkgWeatherService())->current());
    }

    private function weatherResponse(array $payload)
    {
        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setJSON($payload);
    }
}
