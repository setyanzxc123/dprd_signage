<?php

use App\Libraries\Weather\BmkgWeatherService;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Test\CIUnitTestCase;

final class BmkgWeatherServiceTest extends CIUnitTestCase
{
    public function testFreshCacheAvoidsAnotherUpstreamRequest(): void
    {
        $now = 1_700_000_000;
        $calls = 0;
        $service = $this->service(
            new InMemoryWeatherCache(),
            static function () use (&$calls, $now): string {
                $calls++;

                return self::fixture($now);
            },
            static fn (): int => $now,
        );

        $first = $service->current();
        $second = $service->current();

        $this->assertSame(1, $calls);
        $this->assertFalse($first['from_cache']);
        $this->assertFalse($first['stale']);
        $this->assertSame($now, $first['cached_at_epoch']);
        $this->assertTrue($second['from_cache']);
        $this->assertFalse($second['stale']);
        $this->assertSame(0, $second['age_seconds']);
    }

    public function testStaleCacheIsReturnedWhenRefreshFails(): void
    {
        $now = 1_700_000_000;
        $cache = new InMemoryWeatherCache();
        $this->service($cache, static fn (): string => self::fixture($now), static fn (): int => $now)->current();

        $result = $this->service(
            $cache,
            static fn (): string => throw new RuntimeException('offline'),
            static fn (): int => $now + BmkgWeatherService::FRESH_TTL_SECONDS,
        )->current();

        $this->assertSame('success', $result['status']);
        $this->assertTrue($result['from_cache']);
        $this->assertTrue($result['stale']);
        $this->assertSame(BmkgWeatherService::FRESH_TTL_SECONDS, $result['age_seconds']);
    }

    public function testCacheOlderThanStaleLimitIsNotServed(): void
    {
        $now = 1_700_000_000;
        $cache = new InMemoryWeatherCache();
        $this->service($cache, static fn (): string => self::fixture($now), static fn (): int => $now)->current();

        $result = $this->service(
            $cache,
            static fn (): string => throw new RuntimeException('offline'),
            static fn (): int => $now + BmkgWeatherService::STALE_TTL_SECONDS + 1,
        )->current();

        $this->assertSame('error', $result['status']);
        $this->assertFalse($result['from_cache']);
        $this->assertFalse($result['stale']);
    }

    public function testInvalidUpstreamPayloadFallsBackToStaleCache(): void
    {
        $now = 1_700_000_000;
        $cache = new InMemoryWeatherCache();
        $this->service($cache, static fn (): string => self::fixture($now), static fn (): int => $now)->current();

        $result = $this->service(
            $cache,
            static fn (): string => '{invalid-json',
            static fn (): int => $now + BmkgWeatherService::FRESH_TTL_SECONDS,
        )->current();

        $this->assertSame('success', $result['status']);
        $this->assertTrue($result['from_cache']);
        $this->assertTrue($result['stale']);
    }

    public function testCorruptCacheEntryIsIgnoredAndReplaced(): void
    {
        $now = 1_700_000_000;
        $cache = new InMemoryWeatherCache();
        $cache->save(
            'bmkg_weather_' . hash('sha256', '72.71.01.1004'),
            ['cached_at_epoch' => $now, 'payload' => 'rusak'],
            BmkgWeatherService::STALE_TTL_SECONDS,
        );

        $result = $this->service(
            $cache,
            static fn (): string => self::fixture($now),
            static fn (): int => $now,
        )->current();

        $this->assertSame('success', $result['status']);
        $this->assertFalse($result['from_cache']);
        $this->assertFalse($result['stale']);
    }

    private function service(CacheInterface $cache, callable $fetcher, callable $clock): BmkgWeatherService
    {
        return new BmkgWeatherService(
            $cache,
            $fetcher,
            $clock,
            '72.71.01.1004',
            WRITEPATH . 'cache/nonexistent-bmkg-legacy-test.json',
        );
    }

    private static function fixture(int $timestamp): string
    {
        return json_encode([
            'lokasi' => [
                'desa' => 'Sungai Raya',
                'kecamatan' => 'Sungai Raya',
                'kotkab' => 'Kubu Raya',
                'provinsi' => 'Kalimantan Barat',
            ],
            'data' => [[
                'cuaca' => [[[
                    'local_datetime' => date('Y-m-d H:i:s', $timestamp),
                    'weather_desc' => 'Cerah Berawan',
                    't' => 29,
                ]]],
            ]],
        ], JSON_THROW_ON_ERROR);
    }
}

final class InMemoryWeatherCache implements CacheInterface
{
    private array $values = [];

    public function initialize(): void
    {
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function save(string $key, mixed $value, int $ttl = 60): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        if (! array_key_exists($key, $this->values)) {
            $this->values[$key] = $callback();
        }

        return $this->values[$key];
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    public function deleteMatching(string $pattern): int
    {
        $count = count($this->values);
        $this->values = [];

        return $count;
    }

    public function increment(string $key, int $offset = 1): bool|int
    {
        return $this->values[$key] = ($this->values[$key] ?? 0) + $offset;
    }

    public function decrement(string $key, int $offset = 1): bool|int
    {
        return $this->values[$key] = ($this->values[$key] ?? 0) - $offset;
    }

    public function clean(): bool
    {
        $this->values = [];

        return true;
    }

    public function getCacheInfo(): array|false|object|null
    {
        return null;
    }

    public function getMetaData(string $key): ?array
    {
        return null;
    }

    public function isSupported(): bool
    {
        return true;
    }
}
