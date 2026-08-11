<?php

namespace App\Libraries\Otp;

use Config\Otp;

final class MemberOtpThrottle
{
    private readonly Otp $config;

    /** @var \Closure(string, int, int): bool */
    private \Closure $checker;

    public function __construct(?Otp $config = null, ?callable $checker = null)
    {
        $this->config = $config ?? new Otp();
        $this->checker = \Closure::fromCallable(
            $checker ?? static fn (string $key, int $capacity, int $seconds): bool =>
                service('throttler')->check($key, $capacity, $seconds),
        );
    }

    public function allows(?string $phoneFingerprint, string $ipAddress): bool
    {
        $ipAllowed = ($this->checker)(
            'member_otp_ip_' . hash('sha256', $ipAddress),
            $this->config->maxRequestsPerIp,
            $this->config->requestWindowSeconds,
        );
        $phoneAllowed = $phoneFingerprint === null
            || ($this->checker)(
                'member_otp_phone_' . $phoneFingerprint,
                $this->config->maxRequestsPerPhone,
                $this->config->requestWindowSeconds,
            );

        return $ipAllowed && $phoneAllowed;
    }
}
