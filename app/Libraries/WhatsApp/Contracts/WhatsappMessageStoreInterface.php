<?php

namespace App\Libraries\WhatsApp\Contracts;

use App\Libraries\WhatsApp\ValueObjects\SendResult;
use App\Libraries\WhatsApp\ValueObjects\StatusWebhookEvent;

interface WhatsappMessageStoreInterface
{
    public function recordSend(string $provider, string $target, string $messageType, SendResult $result): void;

    public function applyStatus(string $provider, StatusWebhookEvent $event): void;
}
