<?php

use App\Libraries\Security\AdminLoginThrottle;
use CodeIgniter\Test\CIUnitTestCase;
use Config\AdminLoginSecurity;

final class AdminLoginThrottleTest extends CIUnitTestCase
{
    public function testAppliesGlobalIpAndUsernameLimitsWithoutStoringRawIdentity(): void
    {
        $calls = [];
        $throttle = new AdminLoginThrottle(
            $this->config(),
            static function (string $key, int $capacity, int $seconds) use (&$calls): bool {
                $calls[] = compact('key', 'capacity', 'seconds');

                return true;
            },
            static function (): void {
            },
        );

        $this->assertTrue($throttle->allows('Admin.Utama', '203.0.113.10'));
        $this->assertSame('admin_login_global', $calls[0]['key']);
        $this->assertSame(100, $calls[0]['capacity']);
        $this->assertSame(600, $calls[0]['seconds']);
        $this->assertStringStartsWith('admin_login_ip_', $calls[1]['key']);
        $this->assertSame(10, $calls[1]['capacity']);
        $this->assertSame(60, $calls[1]['seconds']);
        $this->assertStringStartsWith('admin_login_username_', $calls[2]['key']);
        $this->assertSame(5, $calls[2]['capacity']);
        $this->assertSame(900, $calls[2]['seconds']);
        $this->assertStringNotContainsString('Admin.Utama', implode('|', array_column($calls, 'key')));
        $this->assertStringNotContainsString('203.0.113.10', implode('|', array_column($calls, 'key')));
    }

    public function testStopsAtFirstBlockedLayer(): void
    {
        $calls = 0;
        $throttle = new AdminLoginThrottle(
            $this->config(),
            static function () use (&$calls): bool {
                $calls++;

                return false;
            },
            static function (): void {
            },
        );

        $this->assertFalse($throttle->allows('operator', '203.0.113.10'));
        $this->assertSame(1, $calls);
    }

    public function testUsernameLimitIsCaseInsensitiveAndCanBeClearedAfterSuccess(): void
    {
        $checkedKeys = [];
        $removedKeys = [];
        $throttle = new AdminLoginThrottle(
            $this->config(),
            static function (string $key) use (&$checkedKeys): bool {
                $checkedKeys[] = $key;

                return true;
            },
            static function (string $key) use (&$removedKeys): void {
                $removedKeys[] = $key;
            },
        );

        $throttle->allows('Operator', '203.0.113.10');
        $firstUsernameKey = $checkedKeys[2];
        $checkedKeys = [];
        $throttle->allows(' operator ', '203.0.113.11');
        $throttle->clearUsername('OPERATOR');

        $this->assertSame($firstUsernameKey, $checkedKeys[2]);
        $this->assertSame([$firstUsernameKey], $removedKeys);
    }

    public function testSixthUsernameAttemptIsBlockedAndSuccessfulLoginCanClearItsBucket(): void
    {
        $buckets = [];
        $checker = static function (string $key, int $capacity) use (&$buckets): bool {
            $buckets[$key] = ($buckets[$key] ?? 0) + 1;

            return $buckets[$key] <= $capacity;
        };
        $remover = static function (string $key) use (&$buckets): void {
            unset($buckets[$key]);
        };
        $throttle = new AdminLoginThrottle($this->config(), $checker, $remover);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->assertTrue($throttle->allows('operator', '203.0.113.10'));
        }
        $this->assertFalse($throttle->allows('operator', '203.0.113.10'));

        $throttle->clearUsername('operator');

        $this->assertTrue($throttle->allows('operator', '203.0.113.11'));
    }

    private function config(): AdminLoginSecurity
    {
        $config = new AdminLoginSecurity();
        $config->maxAttemptsPerIp = 10;
        $config->ipWindowSeconds = 60;
        $config->maxAttemptsPerUsername = 5;
        $config->usernameWindowSeconds = 900;
        $config->maxAttemptsGlobal = 100;
        $config->globalWindowSeconds = 600;

        return $config;
    }
}
