<?php

namespace App\Libraries\WhatsApp\Contracts;

use App\Libraries\WhatsApp\ValueObjects\ConnectionResult;
use App\Libraries\WhatsApp\ValueObjects\SendResult;
use App\Libraries\WhatsApp\ValueObjects\StatusWebhookEvent;

interface WhatsappProviderInterface
{
    public function name(): string;

    public function isConfigured(): bool;

    public function send(string $target, string $message): SendResult;

    public function checkConnection(): ConnectionResult;

    /**
     * @param array<string, mixed> $payload
     */
    public function parseStatusWebhook(array $payload): ?StatusWebhookEvent;
}
