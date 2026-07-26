<?php

namespace App\Libraries\Otp\ValueObjects;

final class OtpRequestResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?int $retryAfter = null,
        public readonly ?string $error = null,
    ) {
    }
}
