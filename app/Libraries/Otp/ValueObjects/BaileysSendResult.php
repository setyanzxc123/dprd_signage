<?php

namespace App\Libraries\Otp\ValueObjects;

final class BaileysSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null,
        public readonly ?string $errorCode = null,
        public readonly int $statusCode = 0,
        public readonly bool $serverAck = false,
        public readonly ?int $ackElapsedMs = null,
    ) {
    }
}
