<?php

namespace App\Libraries\Otp\ValueObjects;

final class OtpVerificationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $error = null,
    ) {
    }
}
