<?php

use App\Libraries\Otp\Providers\BaileysProvider;
use App\Libraries\WhatsApp\Contracts\HttpTransportInterface;
use App\Libraries\WhatsApp\ValueObjects\HttpResponse;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Otp;

final class BaileysProviderTest extends CIUnitTestCase
{
    public function testSendsOtpToGatewayWithApiKeyHeader(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(200, json_encode([
            'status' => 'success',
            'message' => 'Kode OTP berhasil dikirim via WhatsApp.',
            'data' => ['success' => true, 'messageId' => 'BAE5-MSG-1', 'phone' => '628123456789'],
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->sendOtp('628123456789', '748192');

        $this->assertTrue($result->success);
        $this->assertSame('BAE5-MSG-1', $result->messageId);
        $this->assertNull($result->error);
        $this->assertSame('http://127.0.0.1:3001/send-otp', $transport->url);
        $this->assertSame('baileys-key', $transport->headers['x-api-key']);
        $this->assertSame('628123456789', $transport->payload['phone']);
        $this->assertSame('748192', $transport->payload['otp']);
    }

    public function testRejectsInvalidApiKeyAsFailure(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(401, json_encode([
            'status' => 'error',
            'message' => 'Akses ditolak. API Key tidak valid atau belum disertakan pada header x-api-key / Authorization Bearer.',
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->sendOtp('628123456789', '748192');

        $this->assertFalse($result->success);
        $this->assertNull($result->messageId);
        $this->assertStringContainsString('Akses ditolak', (string) $result->error);
    }

    public function testFastFailsWhenGatewayOffline(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(503, json_encode([
            'status' => 'error',
            'message' => 'WhatsApp Gateway belum terhubung. Silakan scan QR Code terlebih dahulu.',
            'code' => 'WA_GATEWAY_OFFLINE',
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->sendOtp('628123456789', '748192');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('belum terhubung', (string) $result->error);
    }

    public function testTransportTimeoutIsReportedAsFailure(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(0, null, 'Operation timed out after 5000 milliseconds'));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->sendOtp('628123456789', '748192');

        $this->assertFalse($result->success);
        $this->assertSame(5, $transport->timeoutSeconds);
        $this->assertStringContainsString('timed out', (string) $result->error);
    }

    public function testUnconfiguredGatewayFailsWithoutNetworkCall(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(200, '{"status":"success","data":{"messageId":"X"}}'));
        $config = new Otp();
        $config->baileysApiKey = '';
        $provider = new BaileysProvider($transport, $config);

        $result = $provider->sendOtp('628123456789', '748192');

        $this->assertFalse($result->success);
        $this->assertSame('', $transport->url);
        $this->assertStringContainsString('belum dikonfigurasi', (string) $result->error);
    }

    public function testSendsOtpWithServerAckAndElapsedMs(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(200, json_encode([
            'status'  => 'success',
            'message' => 'Kode OTP berhasil dikirim via WhatsApp.',
            'data'    => [
                'messageId'      => 'BAE5-ACK-1',
                'phone'          => '628123456789',
                'server_ack'     => true,
                'ack_elapsed_ms' => 235,
            ],
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->sendOtp('628123456789', '748192');

        $this->assertTrue($result->success);
        $this->assertSame('BAE5-ACK-1', $result->messageId);
        $this->assertTrue($result->serverAck);
        $this->assertSame(235, $result->ackElapsedMs);
        $this->assertSame(200, $result->statusCode);
        $this->assertTrue($transport->payload['wait_for_ack']);
        $this->assertSame(3000, $transport->payload['ack_timeout_ms']);
        $this->assertSame('DPRD Sulawesi Tengah', $transport->payload['app_name']);
    }

    public function testHandles502ServerRejected(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(502, json_encode([
            'status'  => 'error',
            'code'    => 'WA_SERVER_REJECTED',
            'message' => 'Server WhatsApp menolak pengiriman pesan (restriction 463).',
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->sendOtp('628123456789', '748192');

        $this->assertFalse($result->success);
        $this->assertSame(502, $result->statusCode);
        $this->assertSame('WA_SERVER_REJECTED', $result->errorCode);
        $this->assertStringContainsString('menolak', (string) $result->error);
    }

    public function testHandles504ServerAckTimeout(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(504, json_encode([
            'status'  => 'error',
            'code'    => 'WA_SERVER_ACK_TIMEOUT',
            'message' => 'Batas waktu menunggu Server ACK terlampaui.',
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->sendOtp('628123456789', '748192');

        $this->assertFalse($result->success);
        $this->assertSame(504, $result->statusCode);
        $this->assertSame('WA_SERVER_ACK_TIMEOUT', $result->errorCode);
    }

    public function testHandles422NumberNotRegistered(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(422, json_encode([
            'status'  => 'error',
            'code'    => 'WA_NUMBER_NOT_REGISTERED',
            'message' => 'Nomor tujuan tidak terdaftar di WhatsApp.',
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->sendOtp('628123456789', '748192');

        $this->assertFalse($result->success);
        $this->assertSame(422, $result->statusCode);
        $this->assertSame('WA_NUMBER_NOT_REGISTERED', $result->errorCode);
    }

    public function testHandles429RateLimited(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(429, json_encode([
            'status'  => 'error',
            'code'    => 'RATE_LIMITED',
            'message' => 'Batas request per menit terlampaui.',
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->sendOtp('628123456789', '748192');

        $this->assertFalse($result->success);
        $this->assertSame(429, $result->statusCode);
        $this->assertSame('RATE_LIMITED', $result->errorCode);
    }

    public function testSendMessageSendsPayloadToGateway(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(200, json_encode([
            'status'  => 'success',
            'message' => 'Pesan berhasil dikirim via WhatsApp.',
            'data'    => [
                'messageId'      => 'MSG-SEND-1',
                'server_ack'     => true,
                'ack_elapsed_ms' => 180,
            ],
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->sendMessage('628123456789', 'Pemberitahuan rapat.');

        $this->assertTrue($result->success);
        $this->assertSame('MSG-SEND-1', $result->messageId);
        $this->assertTrue($result->serverAck);
        $this->assertSame('http://127.0.0.1:3001/send-message', $transport->url);
        $this->assertSame('628123456789', $transport->payload['to']);
        $this->assertSame('Pemberitahuan rapat.', $transport->payload['message']);
        $this->assertTrue($transport->payload['wait_for_ack']);
    }

    public function testGetStatusReturnsConnectedDetailsWhenOnline(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(200, json_encode([
            'status' => 'success',
            'data'   => [
                'status'    => 'connected',
                'connected' => true,
                'user'      => [
                    'phone' => '628123456789',
                    'name'  => 'Humas DPRD',
                ],
            ],
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $status = $provider->getStatus();

        $this->assertTrue($status['configured']);
        $this->assertTrue($status['connected']);
        $this->assertSame('connected', $status['status']);
        $this->assertSame('628123456789', $status['phone']);
        $this->assertSame('Humas DPRD', $status['name']);
        $this->assertSame('http://127.0.0.1:3001/qr/raw', $status['qr_url']);
        $this->assertNull($status['error']);
        $this->assertSame('http://127.0.0.1:3001/status', $transport->url);
    }

    public function testGetStatusReportsOfflineWhenGatewayUnreachable(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(0, null, 'Connection refused'));
        $provider = new BaileysProvider($transport, $this->config());

        $status = $provider->getStatus();

        $this->assertTrue($status['configured']);
        $this->assertFalse($status['connected']);
        $this->assertSame('offline', $status['status']);
        $this->assertNull($status['phone']);
        $this->assertSame('http://127.0.0.1:3001/qr/raw', $status['qr_url']);
        $this->assertStringContainsString('Connection refused', (string) $status['error']);
    }

    public function testGetStatusReportsUnconfiguredWhenConfigMissing(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(200, '{}'));
        $config = new Otp();
        $config->baileysApiKey = '';
        $provider = new BaileysProvider($transport, $config);

        $status = $provider->getStatus();

        $this->assertFalse($status['configured']);
        $this->assertFalse($status['connected']);
        $this->assertSame('unconfigured', $status['status']);
        $this->assertSame('http://127.0.0.1:3001/qr/raw', $status['qr_url']);
        $this->assertStringContainsString('belum dikonfigurasi', (string) $status['error']);
    }

    public function testGetRawQrReturnsDataUrlWhenAvailable(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(200, json_encode([
            'status'       => 'success',
            'connected'    => false,
            'qr_available' => true,
            'qr_data_url'  => 'data:image/png;base64,iVBORw0KGgo...',
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->getRawQr();

        $this->assertTrue($result['success']);
        $this->assertFalse($result['connected']);
        $this->assertTrue($result['qr_available']);
        $this->assertSame('data:image/png;base64,iVBORw0KGgo...', $result['qr_data_url']);
        $this->assertSame('http://127.0.0.1:3001/qr/raw', $transport->url);
    }

    public function testRequestPairCodeReturnsEightDigitCode(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(200, json_encode([
            'status' => 'success',
            'data'   => [
                'phone'        => '628123456789',
                'pairing_code' => '1234-ABCD',
            ],
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->requestPairCode('08123456789');

        $this->assertTrue($result['success']);
        $this->assertSame('1234-ABCD', $result['pairing_code']);
        $this->assertSame('628123456789', $result['phone']);
        $this->assertSame('http://127.0.0.1:3001/pair-code', $transport->url);
        $this->assertSame(['phone' => '08123456789'], $transport->payload);
    }

    public function testLogoutDeviceClearsSessionOnSuccess(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(200, json_encode([
            'status'  => 'success',
            'message' => 'WhatsApp berhasil logout. Sesi lama telah dibersihkan. Lakukan pairing ulang via POST /pair-code atau GET /qr/raw.',
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->logoutDevice();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('logout', (string) $result['message']);
        $this->assertNull($result['error']);
        $this->assertSame('http://127.0.0.1:3001/logout', $transport->url);
        $this->assertSame([], $transport->payload);
        $this->assertSame('baileys-key', $transport->headers['x-api-key']);
    }

    public function testLogoutDeviceReportsGatewayError(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(503, json_encode([
            'status' => 'error',
            'message' => 'WhatsApp Gateway belum terhubung. Silakan scan QR Code terlebih dahulu.',
            'code'   => 'WA_GATEWAY_OFFLINE',
        ], JSON_THROW_ON_ERROR)));
        $provider = new BaileysProvider($transport, $this->config());

        $result = $provider->logoutDevice();

        $this->assertFalse($result['success']);
        $this->assertNull($result['message']);
        $this->assertStringContainsString('belum terhubung', (string) $result['error']);
    }

    public function testLogoutDeviceFailsWithoutNetworkCallWhenUnconfigured(): void
    {
        $transport = new BaileysRecordingTransport(new HttpResponse(200, '{"status":"success"}'));
        $config = new Otp();
        $config->baileysApiKey = '';
        $provider = new BaileysProvider($transport, $config);

        $result = $provider->logoutDevice();

        $this->assertFalse($result['success']);
        $this->assertSame('', $transport->url);
        $this->assertStringContainsString('belum dikonfigurasi', (string) $result['error']);
    }

    protected function tearDown(): void
    {
        try {
            cache()->delete(BaileysProvider::OFFLINE_CACHE_KEY);
        } catch (\Throwable) {
        }
        parent::tearDown();
    }

    public function testGetStatusCachesOfflineState(): void
    {
        try {
            cache()->delete(BaileysProvider::OFFLINE_CACHE_KEY);
        } catch (\Throwable) {
        }

        $transport = new BaileysRecordingTransport(new HttpResponse(0, null, 'Connection refused'));
        $provider = new BaileysProvider($transport, $this->config());

        $first = $provider->getStatus();
        $this->assertSame('offline', $first['status']);
        $this->assertSame(1, $transport->callCount);

        $second = $provider->getStatus();
        $this->assertSame('offline', $second['status']);
        $this->assertSame(1, $transport->callCount);

        $qr = $provider->getRawQr();
        $this->assertFalse($qr['success']);
        $this->assertSame(1, $transport->callCount);

        $forced = $provider->getStatus(forceRefresh: true);
        $this->assertSame('offline', $forced['status']);
        $this->assertSame(2, $transport->callCount);
    }

    private function config(): Otp
    {
        $config = new Otp();
        $config->baileysApiUrl = 'http://127.0.0.1:3001';
        $config->baileysApiKey = 'baileys-key';
        $config->baileysTimeoutSeconds = 5;

        return $config;
    }
}

final class BaileysRecordingTransport implements HttpTransportInterface
{
    public string $url = '';
    /** @var array<string, string> */
    public array $headers = [];
    /** @var array<string, mixed> */
    public array $payload = [];
    public int $timeoutSeconds = 0;
    public int $callCount = 0;

    public function __construct(private readonly HttpResponse $response)
    {
    }

    public function post(string $url, array $headers, array $fields, int $timeoutSeconds): HttpResponse
    {
        $this->callCount++;
        return $this->response;
    }

    public function postJson(string $url, array $headers, array $payload, int $timeoutSeconds): HttpResponse
    {
        $this->callCount++;
        $this->url = $url;
        $this->headers = $headers;
        $this->payload = $payload;
        $this->timeoutSeconds = $timeoutSeconds;

        return $this->response;
    }

    public function get(string $url, array $headers, int $timeoutSeconds): HttpResponse
    {
        $this->callCount++;
        $this->url = $url;
        $this->headers = $headers;
        $this->timeoutSeconds = $timeoutSeconds;

        return $this->response;
    }
}
