<?php

use App\Libraries\WhatsApp\Contracts\WhatsappMessageStoreInterface;
use App\Libraries\WhatsApp\Contracts\WhatsappProviderInterface;
use App\Libraries\WhatsApp\ValueObjects\ConnectionResult;
use App\Libraries\WhatsApp\ValueObjects\SendResult;
use App\Libraries\WhatsApp\ValueObjects\StatusWebhookEvent;
use App\Libraries\WhatsApp\WhatsappGateway;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class WhatsappGatewayTest extends CIUnitTestCase
{
    public function testRecordsProviderResultWithoutKnowingProviderFormat(): void
    {
        $provider = new FakeWhatsappProvider();
        $store = new RecordingWhatsappStore();
        $gateway = new WhatsappGateway($provider, $store);

        $result = $gateway->send('628123456789', 'Secret message', 'otp');

        $this->assertTrue($result->success);
        $this->assertSame('fonnte', $gateway->providerName());
        $this->assertSame('628123456789', $store->target);
        $this->assertSame('otp', $store->messageType);
        $this->assertSame('message-1', $store->sendResult?->messageId);
    }

    public function testAppliesParsedWebhookThroughStore(): void
    {
        $provider = new FakeWhatsappProvider();
        $store = new RecordingWhatsappStore();
        $gateway = new WhatsappGateway($provider, $store);
        $event = $gateway->parseStatusWebhook([
            'id'     => 'message-1',
            'status' => 'sent',
        ]);

        $this->assertNotNull($event);
        $gateway->applyStatus($event);

        $this->assertSame('fonnte', $store->statusProvider);
        $this->assertSame('message-1', $store->statusEvent?->messageId);
    }
}

final class FakeWhatsappProvider implements WhatsappProviderInterface
{
    public function name(): string
    {
        return 'fonnte';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function send(string $target, string $message): SendResult
    {
        return new SendResult(true, 'pending', 'message-1', 'request-1');
    }

    public function checkConnection(): ConnectionResult
    {
        return new ConnectionResult(true, true);
    }

    public function parseStatusWebhook(array $payload): ?StatusWebhookEvent
    {
        return isset($payload['id'])
            ? new StatusWebhookEvent((string) $payload['id'], null, $payload['status'] ?? null, null, null)
            : null;
    }
}

final class RecordingWhatsappStore implements WhatsappMessageStoreInterface
{
    public ?string $target = null;
    public ?string $messageType = null;
    public ?SendResult $sendResult = null;
    public ?string $statusProvider = null;
    public ?StatusWebhookEvent $statusEvent = null;

    public function recordSend(string $provider, string $target, string $messageType, SendResult $result): void
    {
        $this->target = $target;
        $this->messageType = $messageType;
        $this->sendResult = $result;
    }

    public function applyStatus(string $provider, StatusWebhookEvent $event): void
    {
        $this->statusProvider = $provider;
        $this->statusEvent = $event;
    }
}
