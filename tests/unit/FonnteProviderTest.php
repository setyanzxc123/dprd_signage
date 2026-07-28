<?php

use App\Libraries\WhatsApp\Contracts\HttpTransportInterface;
use App\Libraries\WhatsApp\Providers\FonnteProvider;
use App\Libraries\WhatsApp\ValueObjects\HttpResponse;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FonnteProviderTest extends CIUnitTestCase
{
    public function testSendsMessageAndNormalizesSuccessfulResponse(): void
    {
        $transport = new RecordingHttpTransport(new HttpResponse(
            200,
            json_encode([
                'detail'    => 'success! message in queue',
                'id'        => ['80367170'],
                'process'   => 'pending',
                'requestid' => 2937124,
                'status'    => true,
                'target'    => ['628123456789'],
            ], JSON_THROW_ON_ERROR),
        ));
        $provider = new FonnteProvider(
            $transport,
            'test-token',
            'https://example.test/send',
            'https://example.test/device',
            12,
        );

        $result = $provider->send('628123456789', 'Test message');

        $this->assertTrue($result->success);
        $this->assertSame('pending', $result->status);
        $this->assertSame('80367170', $result->messageId);
        $this->assertSame('2937124', $result->requestId);
        $this->assertSame('https://example.test/send', $transport->lastUrl);
        $this->assertSame('test-token', $transport->lastHeaders['Authorization']);
        $this->assertSame('0', $transport->lastFields['countryCode']);
        $this->assertTrue($transport->lastFields['connectOnly']);
        $this->assertSame(12, $transport->lastTimeout);
    }

    public function testMapsProviderFailureWithoutExposingToken(): void
    {
        $transport = new RecordingHttpTransport(new HttpResponse(
            200,
            '{"status":false,"reason":"token invalid"}',
        ));
        $provider = new FonnteProvider($transport, 'bad-token');

        $result = $provider->send('628123456789', 'Test message');

        $this->assertFalse($result->success);
        $this->assertSame('failed', $result->status);
        $this->assertSame('Autentikasi layanan WhatsApp gagal.', $result->error);
        $this->assertStringNotContainsString('bad-token', (string) $result->error);
    }

    public function testReportsConnectedDeviceProfile(): void
    {
        $transport = new RecordingHttpTransport(new HttpResponse(
            200,
            '{"device":"628123456789","device_status":"connect","status":true}',
        ));
        $provider = new FonnteProvider($transport, 'test-token');

        $result = $provider->checkConnection();

        $this->assertTrue($result->success);
        $this->assertTrue($result->connected);
        $this->assertSame('628123456789', $result->device);
        $this->assertSame('connect', $result->deviceStatus);
    }

    public function testParsesStatusWebhook(): void
    {
        $provider = new FonnteProvider(new RecordingHttpTransport(new HttpResponse(200, '{}')), 'test-token');

        $event = $provider->parseStatusWebhook([
            'device'  => '628123456789',
            'id'      => '80367170',
            'stateid' => 'state-1',
            'status'  => 'sent',
            'state'   => 'delivered',
        ]);

        $this->assertNotNull($event);
        $this->assertSame('80367170', $event->messageId);
        $this->assertSame('state-1', $event->stateId);
        $this->assertSame('sent', $event->status);
        $this->assertSame('delivered', $event->state);
    }

    public function testRejectsWebhookWithoutMessageOrStateId(): void
    {
        $provider = new FonnteProvider(new RecordingHttpTransport(new HttpResponse(200, '{}')), 'test-token');

        $this->assertNull($provider->parseStatusWebhook(['status' => 'sent']));
    }
}

final class RecordingHttpTransport implements HttpTransportInterface
{
    public ?string $lastUrl = null;

    /** @var array<string, string> */
    public array $lastHeaders = [];

    /** @var array<string, scalar> */
    public array $lastFields = [];

    public ?int $lastTimeout = null;

    public function __construct(private readonly HttpResponse $response)
    {
    }

    public function post(string $url, array $headers, array $fields, int $timeoutSeconds): HttpResponse
    {
        $this->lastUrl = $url;
        $this->lastHeaders = $headers;
        $this->lastFields = $fields;
        $this->lastTimeout = $timeoutSeconds;

        return $this->response;
    }

    public function postJson(string $url, array $headers, array $payload, int $timeoutSeconds): HttpResponse
    {
        $this->lastUrl = $url;
        $this->lastHeaders = $headers;
        $this->lastFields = $payload;
        $this->lastTimeout = $timeoutSeconds;

        return $this->response;
    }
}
