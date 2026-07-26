<?php

namespace App\Libraries\WhatsApp\ValueObjects;

final class SendResult
{
    /**
     * @param array<string, mixed>|null $payload
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $messageId = null,
        public readonly ?string $requestId = null,
        public readonly ?string $detail = null,
        public readonly ?string $error = null,
        public readonly ?string $rawResponse = null,
        public readonly ?array $payload = null,
    ) {
    }
}
