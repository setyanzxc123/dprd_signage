<?php

namespace App\Libraries\WhatsApp\ValueObjects;

final class StatusWebhookEvent
{
    public function __construct(
        public readonly ?string $messageId,
        public readonly ?string $stateId,
        public readonly ?string $status,
        public readonly ?string $state,
        public readonly ?string $device,
    ) {
    }
}
