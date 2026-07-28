<?php

namespace App\Libraries\Otp\ValueObjects;

final class FazpassRequestResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $otpId = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $provider = null,
        public readonly ?string $error = null,
    ) {
    }
}
