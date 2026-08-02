<?php

namespace App\Libraries\Weather;

use CodeIgniter\Cache\CacheInterface;

final class BmkgWeatherService
{
    public const FRESH_TTL_SECONDS = 1800;
    public const STALE_TTL_SECONDS = 86400;

    private CacheInterface $cache;
    /** @var \Closure(string): string */
    private \Closure $fetcher;
    /** @var \Closure(): int */
    private \Closure $clock;
    private string $adm4;
    private string $legacyCacheFile;

    public function __construct(
        ?CacheInterface $cache = null,
        ?callable $fetcher = null,
        ?callable $clock = null,
        ?string $adm4 = null,
        ?string $legacyCacheFile = null,
    ) {
        $this->cache = $cache ?? service('cache');
        $this->fetcher = \Closure::fromCallable($fetcher ?? $this->fetchFromBmkg(...));
        $this->clock = \Closure::fromCallable($clock ?? static fn (): int => time());
        $configuredAdm4 = $adm4 ?? env('BMKG_ADM4');
        $this->adm4 = trim((string) ($configuredAdm4 ?: '72.71.01.1004'));
        $this->legacyCacheFile = $legacyCacheFile ?? WRITEPATH . 'cache/bmkg_cuaca.json';
    }

    /** @return array<string, mixed> */
    public function current(): array
    {
        $now = ($this->clock)();
        $cached = $this->cachedWeather($now);
        if ($cached !== null && $cached['age_seconds'] < self::FRESH_TTL_SECONDS) {
            return $this->decorateCached($cached, false);
        }

        try {
            $result = $this->weatherFromJson(($this->fetcher)($this->endpoint()), $now);
            $this->cache->save($this->cacheKey(), [
                'cached_at_epoch' => $now,
                'payload'         => $result,
            ], self::STALE_TTL_SECONDS);

            return $result;
        } catch (\Throwable $exception) {
            log_message('warning', 'Gagal memperbarui cache BMKG: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }

        if ($cached !== null && $cached['age_seconds'] <= self::STALE_TTL_SECONDS) {
            return $this->decorateCached($cached, true);
        }

        return [
            'status'     => 'error',
            'message'    => 'Data cuaca belum tersedia. Periksa koneksi internet.',
            'from_cache' => false,
            'stale'      => false,
        ];
    }

    private function endpoint(): string
    {
        return 'https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4=' . urlencode($this->adm4);
    }

    private function cacheKey(): string
    {
        return 'bmkg_weather_' . hash('sha256', $this->adm4);
    }

    private function fetchFromBmkg(string $url): string
    {
        $response = service('curlrequest')->get($url, [
            'connect_timeout' => 3,
            'timeout'         => 8,
            'http_errors'     => false,
            'headers'         => [
                'Accept'     => 'application/json',
                'User-Agent' => 'DPRD-Signage/1.0',
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('BMKG mengembalikan HTTP ' . $response->getStatusCode() . '.');
        }

        return $response->getBody();
    }

    /** @return array<string, mixed> */
    private function weatherFromJson(string $body, int $now): array
    {
        $data = json_decode($body, true);
        if (! is_array($data)) {
            throw new \RuntimeException('Respons BMKG bukan JSON valid.');
        }

        $forecast = [];
        foreach ($data['data'][0]['cuaca'] ?? [] as $dailySlots) {
            foreach ($dailySlots as $slot) {
                if (is_array($slot)) {
                    $forecast[] = $slot;
                }
            }
        }

        $current = $this->nearestWeatherSlot($forecast, $now);
        if ($current === null) {
            throw new \RuntimeException('Tidak ada slot prakiraan BMKG yang valid.');
        }

        $location = is_array($data['lokasi'] ?? null) ? $data['lokasi'] : [];

        return [
            'status' => 'success',
            'lokasi' => [
                'desa'      => $location['desa'] ?? '-',
                'kecamatan' => $location['kecamatan'] ?? '-',
                'kotkab'    => $location['kotkab'] ?? '-',
                'provinsi'  => $location['provinsi'] ?? '-',
            ],
            'cuaca' => [
                'suhu'          => ($current['t'] ?? '-') . "\u{00B0}C",
                'suhu_raw'      => $current['t'] ?? null,
                'kondisi'       => $current['weather_desc'] ?? '-',
                'kondisi_en'    => $current['weather_desc_en'] ?? '-',
                'kelembapan'    => ($current['hu'] ?? '-') . '%',
                'kec_angin'     => ($current['ws'] ?? '-') . ' km/j',
                'arah_angin'    => $current['wd'] ?? '-',
                'jarak_pandang' => $current['vs_text'] ?? '-',
                'icon_url'      => ! empty($current['image'])
                    ? str_replace(' ', '%20', (string) $current['image'])
                    : '',
                'waktu_lokal' => $current['local_datetime'] ?? '',
            ],
            'cached_at'   => date('Y-m-d H:i:s', $now),
            'cached_at_epoch' => $now,
            'age_seconds' => 0,
            'from_cache'  => false,
            'stale'       => false,
            'attribution' => 'Sumber: BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)',
        ];
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

    /**
     * @return array{payload: array<string, mixed>, age_seconds: int, cached_at_epoch: int}|null
     */
    private function cachedWeather(int $now): ?array
    {
        $cached = $this->cache->get($this->cacheKey());
        if (! is_array($cached)) {
            $cached = $this->legacyCache($now);
        }

        $payload = $cached['payload'] ?? null;
        $cachedAt = (int) ($cached['cached_at_epoch'] ?? 0);
        if (! is_array($payload) || ($payload['status'] ?? null) !== 'success' || $cachedAt <= 0) {
            $this->cache->delete($this->cacheKey());
            return null;
        }

        $age = max(0, $now - $cachedAt);
        if ($age > self::STALE_TTL_SECONDS) {
            $this->cache->delete($this->cacheKey());
            return null;
        }

        return ['payload' => $payload, 'age_seconds' => $age, 'cached_at_epoch' => $cachedAt];
    }

    /** @return array<string, mixed>|null */
    private function legacyCache(int $now): ?array
    {
        if (! is_file($this->legacyCacheFile)) {
            return null;
        }

        $payload = json_decode((string) file_get_contents($this->legacyCacheFile), true);
        $cachedAt = (int) filemtime($this->legacyCacheFile);
        $age = max(0, $now - $cachedAt);
        if (! is_array($payload) || ($payload['status'] ?? null) !== 'success'
            || $age > self::STALE_TTL_SECONDS
        ) {
            return null;
        }

        $cached = ['cached_at_epoch' => $cachedAt, 'payload' => $payload];
        $this->cache->save($this->cacheKey(), $cached, max(1, self::STALE_TTL_SECONDS - $age));

        return $cached;
    }

    /**
     * @param array{payload: array<string, mixed>, age_seconds: int, cached_at_epoch: int} $cached
     * @return array<string, mixed>
     */
    private function decorateCached(array $cached, bool $stale): array
    {
        return [
            ...$cached['payload'],
            'cached_at_epoch' => $cached['cached_at_epoch'],
            'age_seconds' => $cached['age_seconds'],
            'from_cache'  => true,
            'stale'       => $stale,
        ];
    }
}
