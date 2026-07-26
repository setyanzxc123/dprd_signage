<?php

namespace App\Libraries\Otp\ValueObjects;

final class OtpDeliveryResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $provider = null,
        public readonly ?string $messageId = null,
        public readonly ?string $requestId = null,
        public readonly ?string $error = null,
    ) {
    }

    public function accepted(): bool
    {
        return in_array($this->status, ['pending', 'sent'], true);
    }

    public function ambiguous(): bool
    {
        return $this->status === 'ambiguous';
    }
}
