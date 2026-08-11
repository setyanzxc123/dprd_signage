<?php

use App\Libraries\Otp\MemberOtpThrottle;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Otp;

final class MemberOtpThrottleTest extends CIUnitTestCase
{
    public function testPhoneLimitStopsRepeatedRequests(): void
    {
        [$throttle] = $this->throttle(phoneLimit: 2, ipLimit: 100);

        $this->assertTrue($throttle->allows('phone-a', '203.0.113.10'));
        $this->assertTrue($throttle->allows('phone-a', '203.0.113.10'));
        $this->assertFalse($throttle->allows('phone-a', '203.0.113.10'));
    }

    public function testIpLimitStopsRequestsAcrossDifferentPhones(): void
    {
        [$throttle] = $this->throttle(phoneLimit: 10, ipLimit: 2);

        $this->assertTrue($throttle->allows('phone-a', '203.0.113.10'));
        $this->assertTrue($throttle->allows('phone-b', '203.0.113.10'));
        $this->assertFalse($throttle->allows('phone-c', '203.0.113.10'));
    }

    public function testFortyMembersCanRequestBehindOneOfficeIp(): void
    {
        [$throttle] = $this->throttle(phoneLimit: 5, ipLimit: 100);

        for ($member = 1; $member <= 40; $member++) {
            $this->assertTrue($throttle->allows('phone-' . $member, '203.0.113.10'));
        }
    }

    /** @return array{MemberOtpThrottle, array<string, int>} */
    private function throttle(int $phoneLimit, int $ipLimit): array
    {
        $config = new Otp();
        $config->maxRequestsPerPhone = $phoneLimit;
        $config->maxRequestsPerIp = $ipLimit;
        $counters = [];
        $checker = static function (string $key, int $capacity, int $seconds) use (&$counters): bool {
            $counters[$key] = ($counters[$key] ?? 0) + 1;

            return $counters[$key] <= $capacity;
        };

        return [new MemberOtpThrottle($config, $checker), $counters];
    }
}
