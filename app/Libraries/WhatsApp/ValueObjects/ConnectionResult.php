<?php

namespace App\Libraries\WhatsApp\ValueObjects;

final class ConnectionResult
{
    /**
     * @param array<string, mixed>|null $payload
     */
    public function __construct(
        public readonly bool $success,
        public readonly bool $connected,
        public readonly ?string $device = null,
        public readonly ?string $deviceStatus = null,
        public readonly ?string $error = null,
        public readonly ?string $rawResponse = null,
        public readonly ?array $payload = null,
    ) {
    }
}
