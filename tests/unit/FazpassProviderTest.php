<?php

use App\Libraries\Otp\Providers\FazpassProvider;
use App\Libraries\WhatsApp\Contracts\HttpTransportInterface;
use App\Libraries\WhatsApp\ValueObjects\HttpResponse;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Otp;

final class FazpassProviderTest extends CIUnitTestCase
{
    public function testRequestsOtpUsingConfiguredGateway(): void
    {
        $transport = new FazpassRecordingTransport(new HttpResponse(200, json_encode([
            'status' => true,
            'data' => ['id' => 'otp-1', 'transaction_id' => 'tx-1', 'provider' => 'Fazpass Generic 7'],
        ], JSON_THROW_ON_ERROR)));
        $config = $this->config();
        $provider = new FazpassProvider($transport, $config);

        $result = $provider->request('628123456789');

        $this->assertTrue($result->success);
        $this->assertSame('otp-1', $result->otpId);
        $this->assertSame('tx-1', $result->transactionId);
        $this->assertSame('https://api.example.test/api/v1/otp/request', $transport->url);
        $this->assertSame('Bearer merchant', $transport->headers['Authorization']);
        $this->assertSame('gateway', $transport->payload['gateway_key']);
    }

    public function testVerifiesOtpThroughFazpass(): void
    {
        $transport = new FazpassRecordingTransport(new HttpResponse(200, '{"status":true}'));
        $provider = new FazpassProvider($transport, $this->config());

        $result = $provider->verify('otp-1', '123456');

        $this->assertTrue($result->success);
        $this->assertSame('https://api.example.test/api/v1/otp/verify', $transport->url);
        $this->assertSame('otp-1', $transport->payload['otp_id']);
        $this->assertSame('123456', $transport->payload['otp']);
    }

    public function testDoesNotExposeCredentialsOnProviderFailure(): void
    {
        $transport = new FazpassRecordingTransport(new HttpResponse(401, '{"status":false,"message":"invalid key"}'));
        $provider = new FazpassProvider($transport, $this->config());

        $result = $provider->request('628123456789');

        $this->assertFalse($result->success);
        $this->assertStringNotContainsString('merchant', (string) $result->error);
    }

    private function config(): Otp
    {
        $config = new Otp();
        $config->fazpassApiUrl = 'https://api.example.test';
        $config->fazpassMerchantKey = 'merchant';
        $config->fazpassGatewayKey = 'gateway';

        return $config;
    }
}

final class FazpassRecordingTransport implements HttpTransportInterface
{
    public string $url = '';
    /** @var array<string, string> */
    public array $headers = [];
    /** @var array<string, mixed> */
    public array $payload = [];

    public function __construct(private readonly HttpResponse $response)
    {
    }

    public function post(string $url, array $headers, array $fields, int $timeoutSeconds): HttpResponse
    {
        return $this->response;
    }

    public function postJson(string $url, array $headers, array $payload, int $timeoutSeconds): HttpResponse
    {
        $this->url = $url;
        $this->headers = $headers;
        $this->payload = $payload;

        return $this->response;
    }
}
