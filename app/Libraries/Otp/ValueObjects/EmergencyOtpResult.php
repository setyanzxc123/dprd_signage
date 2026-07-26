<?php

namespace App\Libraries\Otp\ValueObjects;

final class EmergencyOtpResult
{
    public function __construct(
        public readonly int $otpId,
        public readonly string $code,
        public readonly string $expiresAt,
    ) {
    }
}
