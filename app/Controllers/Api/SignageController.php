<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Schedule\ScheduleReadService;

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
     * Prakiraan BMKG terdekat disimpan dalam cache selama 30 menit.
     */
    public function cuaca()
    {
        $cacheFile = WRITEPATH . 'cache/bmkg_cuaca.json';
        $cacheTtl = 1800;

        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                $cached['from_cache'] = true;

                return $this->weatherResponse($cached);
            }
        }

        $adm4 = (string) (env('BMKG_ADM4') ?: '72.71.01.1004');
        $url = 'https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4=' . urlencode($adm4);
        $body = @file_get_contents($url);
        if ($body === false) {
            return $this->weatherError('Gagal mengambil data BMKG.');
        }

        $data = json_decode($body, true);
        if (! is_array($data)) {
            return $this->weatherError('Respons BMKG bukan JSON valid.');
        }

        $forecast = [];
        foreach ($data['data'][0]['cuaca'] ?? [] as $dailySlots) {
            foreach ($dailySlots as $slot) {
                $forecast[] = $slot;
            }
        }

        $current = $this->nearestWeatherSlot($forecast, time());
        if ($current === null) {
            return $this->weatherError('Tidak ada slot prakiraan tersedia.');
        }

        $location = $data['lokasi'] ?? [];
        $result = [
            'status' => 'success',
            'lokasi' => [
                'desa'      => $location['desa'] ?? '-',
                'kecamatan' => $location['kecamatan'] ?? '-',
                'kotkab'    => $location['kotkab'] ?? '-',
                'provinsi'  => $location['provinsi'] ?? '-',
            ],
            'cuaca' => [
                'suhu'          => ($current['t'] ?? '-') . '°C',
                'suhu_raw'      => $current['t'] ?? null,
                'kondisi'       => $current['weather_desc'] ?? '-',
                'kondisi_en'    => $current['weather_desc_en'] ?? '-',
                'kelembapan'    => ($current['hu'] ?? '-') . '%',
                'kec_angin'     => ($current['ws'] ?? '-') . ' km/j',
                'arah_angin'    => $current['wd'] ?? '-',
                'jarak_pandang' => $current['vs_text'] ?? '-',
                'icon_url'      => ! empty($current['image'])
                    ? str_replace(' ', '%20', $current['image'])
                    : '',
                'waktu_lokal' => $current['local_datetime'] ?? '',
            ],
            'cached_at'   => date('Y-m-d H:i:s'),
            'from_cache'  => false,
            'attribution' => 'Sumber: BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)',
        ];

        @file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE));

        return $this->weatherResponse($result);
    }

    /**
     * @param list<array<string, mixed>> $forecast
     * @return array<string, mixed>|null
     */
    private function nearestWeatherSlot(array $forecast, int $now): ?array
    {
        $current = null;
        $minimumDifference = PHP_INT_MAX;
        foreach ($forecast as $slot) {
            $timestamp = strtotime((string) ($slot['local_datetime'] ?? ''));
            if ($timestamp === false) {
                continue;
            }

            $difference = abs($now - $timestamp);
            if ($difference < $minimumDifference) {
                $minimumDifference = $difference;
                $current = $slot;
            }
        }

        return $current;
    }

    private function weatherError(string $message)
    {
        return $this->weatherResponse([
            'status'  => 'error',
            'message' => $message,
        ]);
    }

    private function weatherResponse(array $payload)
    {
        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON($payload);
    }
}
