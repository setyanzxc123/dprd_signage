<?php

namespace App\Libraries\WhatsApp;

use App\Libraries\WhatsApp\Contracts\WhatsappMessageStoreInterface;
use App\Libraries\WhatsApp\Contracts\WhatsappProviderInterface;
use App\Libraries\WhatsApp\Persistence\DatabaseWhatsappMessageStore;
use App\Libraries\WhatsApp\Providers\FonnteProvider;
use App\Libraries\WhatsApp\ValueObjects\ConnectionResult;
use App\Libraries\WhatsApp\ValueObjects\SendResult;
use App\Libraries\WhatsApp\ValueObjects\StatusWebhookEvent;

final class WhatsappGateway
{
    public function __construct(
        ?WhatsappProviderInterface $provider = null,
        ?WhatsappMessageStoreInterface $store = null,
    ) {
        $this->provider = $provider ?? new FonnteProvider();
        $this->store = $store ?? new DatabaseWhatsappMessageStore();
    }

    private readonly WhatsappProviderInterface $provider;
    private readonly WhatsappMessageStoreInterface $store;

    public function providerName(): string
    {
        return $this->provider->name();
    }

    public function isConfigured(): bool
    {
        return $this->provider->isConfigured();
    }

    public function send(string $target, string $message, string $messageType = 'transactional'): SendResult
    {
        $result = $this->provider->send($target, $message);
        try {
            $this->store->recordSend($this->provider->name(), $target, $messageType, $result);
        } catch (\Throwable $exception) {
            log_message('error', 'Metadata pengiriman WhatsApp gagal disimpan: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }

        return $result;
    }

    public function checkConnection(): ConnectionResult
    {
        return $this->provider->checkConnection();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function parseStatusWebhook(array $payload): ?StatusWebhookEvent
    {
        return $this->provider->parseStatusWebhook($payload);
    }

    public function applyStatus(StatusWebhookEvent $event): void
    {
        $this->store->applyStatus($this->provider->name(), $event);
    }
}
