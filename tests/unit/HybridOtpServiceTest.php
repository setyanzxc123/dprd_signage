<?php

use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use App\Libraries\Otp\OtpService;
use App\Libraries\Otp\OtpStatus;
use App\Libraries\Otp\Providers\BaileysProvider;
use App\Libraries\Otp\Providers\FazpassProvider;
use App\Libraries\WhatsApp\Contracts\HttpTransportInterface;
use App\Libraries\WhatsApp\ValueObjects\HttpResponse;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Otp;

final class HybridOtpServiceTest extends CIUnitTestCase
{
    private int $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = strtotime('2026-09-01 10:00:00');
    }

    public function testHybridSendsViaBaileysWhenBaileysIsAvailable(): void
    {
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(200, json_encode([
                'status'  => 'success',
                'message' => 'OTP terkirim',
                'data'    => ['messageId' => 'BAE5-MSG-001', 'phone' => '628123456789'],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport);

        $result = $service->request(7, '628123456789');

        $this->assertTrue($result->success);
        $this->assertSame(OtpStatus::PENDING, $result->status);
        $this->assertSame(60, $result->retryAfter);
        $this->assertSame($this->now + 300, $result->expiresAt);

        $this->assertSame(1, $baileysTransport->requestCount);
        $this->assertSame(0, $fazpassTransport->requestCount);

        $otp = $repository->otps[1];
        $this->assertSame('baileys', $otp['provider']);
        $this->assertSame(OtpStatus::PENDING, $otp['status']);
        $this->assertSame('BAE5-MSG-001', $otp['provider_transaction_id']);
        $this->assertNotNull($otp['code_hash']);
    }

    public function testHybridVerifiesLocallyWithBcryptHash(): void
    {
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(200, json_encode([
                'status'  => 'success',
                'message' => 'OTP terkirim',
                'data'    => ['messageId' => 'BAE5-MSG-002'],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport);

        $service->request(7, '628123456789');
        $sentCode = (string) $baileysTransport->lastPayload['otp'];
        $this->assertSame(6, strlen($sentCode));

        $verifyResult = $service->verify(7, $sentCode);

        $this->assertTrue($verifyResult->success);
        $this->assertSame('verified', $verifyResult->status);
        $this->assertSame(0, $fazpassTransport->requestCount);
        $this->assertSame(OtpStatus::VERIFIED, $repository->otps[1]['status']);
        $this->assertNotNull($repository->otps[1]['used_at']);
    }

    public function testHybridFailsOverToFazpassWhenBaileysFails(): void
    {
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(503, json_encode([
                'status'  => 'error',
                'code'    => 'WA_GATEWAY_OFFLINE',
                'message' => 'WhatsApp Gateway offline.',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $fazpassTransport = new HybridRecordingTransport([
            '/otp/request' => new HttpResponse(200, json_encode([
                'status' => true,
                'data'   => ['id' => 'faz-otp-101', 'transaction_id' => 'faz-tx-101'],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport);

        $result = $service->request(7, '628123456789');

        $this->assertTrue($result->success);
        $this->assertSame(OtpStatus::PENDING, $result->status);
        $this->assertSame(1, $baileysTransport->requestCount);
        $this->assertSame(1, $fazpassTransport->requestCount);

        $otp = $repository->otps[1];
        $this->assertSame('fazpass', $otp['provider']);
        $this->assertSame('faz-otp-101', $otp['provider_otp_id']);
        $this->assertSame('faz-tx-101', $otp['provider_transaction_id']);
        $this->assertNull($otp['code_hash']);
    }

    public function testHybridVerifiesViaFazpassWhenOtpWasSentViaFazpassFallback(): void
    {
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(503, '{"status":"error","message":"Gateway offline"}'),
        ]);
        $fazpassTransport = new HybridRecordingTransport([
            '/otp/request' => new HttpResponse(200, json_encode([
                'status' => true,
                'data'   => ['id' => 'faz-otp-102', 'transaction_id' => 'faz-tx-102'],
            ], JSON_THROW_ON_ERROR)),
            '/otp/verify'  => new HttpResponse(200, '{"status":true}'),
        ]);
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport);

        $service->request(7, '628123456789');
        $verifyResult = $service->verify(7, '654321');

        $this->assertTrue($verifyResult->success);
        $this->assertSame('verified', $verifyResult->status);
        $this->assertSame('faz-otp-102', $fazpassTransport->lastPayload['otp_id']);
        $this->assertSame('654321', $fazpassTransport->lastPayload['otp']);
        $this->assertSame(OtpStatus::VERIFIED, $repository->otps[1]['status']);
    }

    public function testHybridFailsWhenBothBaileysAndFazpassFail(): void
    {
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(503, '{"status":"error","message":"Baileys offline"}'),
        ]);
        $fazpassTransport = new HybridRecordingTransport([
            '/otp/request' => new HttpResponse(400, '{"status":false,"message":"Fazpass saldo habis"}'),
        ]);
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport);

        $result = $service->request(7, '628123456789');

        $this->assertFalse($result->success);
        $this->assertSame(OtpStatus::FAILED, $result->status);
        $this->assertStringContainsString('Fazpass', (string) $result->error);
        $this->assertSame(1, $baileysTransport->requestCount);
        $this->assertSame(1, $fazpassTransport->requestCount);
        $this->assertSame(OtpStatus::FAILED, $repository->otps[1]['status']);
    }

    public function testHybridDoesNotFallBackWhenFallbackDisabled(): void
    {
        $config = $this->config();
        $config->fazpassFallbackEnabled = false;
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(503, '{"status":"error","message":"Baileys offline"}'),
        ]);
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport, $config);

        $result = $service->request(7, '628123456789');

        $this->assertFalse($result->success);
        $this->assertSame(OtpStatus::FAILED, $result->status);
        $this->assertSame(1, $baileysTransport->requestCount);
        $this->assertSame(0, $fazpassTransport->requestCount);
        $this->assertSame('baileys', $repository->otps[1]['provider']);
    }

    public function testHybridDoesNotFallBackWhenFazpassUnconfigured(): void
    {
        $config = $this->config();
        $config->fazpassMerchantKey = '';
        $config->fazpassGatewayKey = '';
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(503, '{"status":"error","message":"Baileys offline"}'),
        ]);
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport, $config);

        $result = $service->request(7, '628123456789');

        $this->assertFalse($result->success);
        $this->assertSame(OtpStatus::FAILED, $result->status);
        $this->assertSame(1, $baileysTransport->requestCount);
        $this->assertSame(0, $fazpassTransport->requestCount);
    }

    public function testBaileysOnlyProviderModeDoesNotUseFazpass(): void
    {
        $config = $this->config();
        $config->provider = 'baileys';
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(503, '{"status":"error","message":"Baileys offline"}'),
        ]);
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport, $config);

        $result = $service->request(7, '628123456789');

        $this->assertFalse($result->success);
        $this->assertSame(0, $fazpassTransport->requestCount);
    }

    public function testFazpassOnlyProviderModeDoesNotUseBaileys(): void
    {
        $config = $this->config();
        $config->provider = 'fazpass';
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport();
        $fazpassTransport = new HybridRecordingTransport([
            '/otp/request' => new HttpResponse(200, json_encode([
                'status' => true,
                'data'   => ['id' => 'faz-direct-1', 'transaction_id' => 'faz-tx-direct-1'],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport, $config);

        $result = $service->request(7, '628123456789');

        $this->assertTrue($result->success);
        $this->assertSame(0, $baileysTransport->requestCount);
        $this->assertSame(1, $fazpassTransport->requestCount);
        $this->assertSame('fazpass', $repository->otps[1]['provider']);
    }

    public function testInternalProviderModeWorksLocally(): void
    {
        $config = $this->config();
        $config->provider = 'internal';
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport();
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport, $config);

        $result = $service->request(7, '628123456789');

        $this->assertTrue($result->success);
        $this->assertSame(0, $baileysTransport->requestCount);
        $this->assertSame(0, $fazpassTransport->requestCount);
        $this->assertSame('internal', $repository->otps[1]['provider']);
        $this->assertNotNull($repository->otps[1]['code_hash']);
    }

    public function testCooldownPreventsSpamWithinResendWindow(): void
    {
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(200, '{"status":"success","data":{"messageId":"M1"}}'),
        ]);
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport);

        $first = $service->request(7, '628123456789');
        $this->now += 30;
        $cooldown = $service->request(7, '628123456789');

        $this->assertTrue($first->success);
        $this->assertFalse($cooldown->success);
        $this->assertSame('cooldown', $cooldown->status);
        $this->assertSame(30, $cooldown->retryAfter);
        $this->assertSame(1, $baileysTransport->requestCount);
    }

    public function testDailyAccountRateLimitEnforced(): void
    {
        $config = $this->config();
        $config->maxRequestsPerAccountPerDay = 2;
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(200, '{"status":"success","data":{"messageId":"M"}}'),
        ]);
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport, $config);

        $this->assertTrue($service->request(7, '628123456789')->success);
        $this->now += 61;
        $this->assertTrue($service->request(7, '628123456789')->success);
        $this->now += 61;
        $rateLimited = $service->request(7, '628123456789');

        $this->assertFalse($rateLimited->success);
        $this->assertSame('rate_limited', $rateLimited->status);
        $this->assertSame(2, $baileysTransport->requestCount);
    }

    public function testGlobalRateLimitCircuitBreakerEnforced(): void
    {
        $config = $this->config();
        $config->maxRequestsGlobal = 2;
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(200, '{"status":"success","data":{"messageId":"M"}}'),
        ]);
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport, $config);

        $this->assertTrue($service->request(1, '628123456781')->success);
        $this->assertTrue($service->request(2, '628123456782')->success);
        $rateLimited = $service->request(3, '628123456783');

        $this->assertFalse($rateLimited->success);
        $this->assertSame('rate_limited', $rateLimited->status);
    }

    public function testInvalidCodeIncrementsAttemptsAndCancelsAfterMaxAttempts(): void
    {
        $config = $this->config();
        $config->maxVerificationAttempts = 2;
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(200, '{"status":"success","data":{"messageId":"M"}}'),
        ]);
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport, $config);

        $service->request(7, '628123456789');
        $code = (string) $baileysTransport->lastPayload['otp'];
        $wrongCode = $code === '000000' ? '000001' : '000000';

        $first = $service->verify(7, $wrongCode);
        $this->assertFalse($first->success);
        $this->assertSame('invalid', $first->status);
        $this->assertSame(1, $repository->otps[1]['attempts']);

        $second = $service->verify(7, $wrongCode);
        $this->assertFalse($second->success);
        $this->assertSame('too_many_attempts', $second->status);
        $this->assertSame(2, $repository->otps[1]['attempts']);
        $this->assertSame(OtpStatus::CANCELLED, $repository->otps[1]['status']);
    }

    public function testExpiredOtpCannotBeVerified(): void
    {
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport([
            '/send-otp' => new HttpResponse(200, '{"status":"success","data":{"messageId":"M"}}'),
        ]);
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport);

        $service->request(7, '628123456789');
        $code = (string) $baileysTransport->lastPayload['otp'];

        $this->now += 301;
        $verify = $service->verify(7, $code);

        $this->assertFalse($verify->success);
        $this->assertSame('invalid', $verify->status);
        $this->assertNull($repository->otps[1]['used_at']);
    }

    public function testEmergencyOtpWorksIndependentlyOfProviderMode(): void
    {
        $repository = new HybridOtpMemoryRepository();
        $baileysTransport = new HybridRecordingTransport();
        $fazpassTransport = new HybridRecordingTransport();
        $service = $this->createService($repository, $baileysTransport, $fazpassTransport);

        $emergency = $service->createEmergency(7, 42);
        $this->assertSame(6, strlen($emergency->code));

        $verify = $service->verify(7, $emergency->code);

        $this->assertTrue($verify->success);
        $this->assertSame('verified', $verify->status);
        $this->assertSame(0, $baileysTransport->requestCount);
        $this->assertSame(0, $fazpassTransport->requestCount);
        $this->assertSame(42, $repository->otps[1]['created_by_admin_id']);
        $this->assertSame('emergency', $repository->otps[1]['provider']);
    }

    private function createService(
        HybridOtpMemoryRepository $repository,
        HybridRecordingTransport $baileysTransport,
        HybridRecordingTransport $fazpassTransport,
        ?Otp $config = null,
    ): OtpService {
        $config ??= $this->config();
        $baileysProvider = new BaileysProvider($baileysTransport, $config);
        $fazpassProvider = new FazpassProvider($fazpassTransport, $config);

        return new OtpService(
            $repository,
            $baileysProvider,
            $fazpassProvider,
            $config,
            fn (): int => $this->now,
        );
    }

    private function config(): Otp
    {
        $config = new Otp();
        $config->provider = 'hybrid';
        $config->baileysApiUrl = 'http://127.0.0.1:3001';
        $config->baileysApiKey = 'test-baileys-key';
        $config->baileysTimeoutSeconds = 5;
        $config->fazpassFallbackEnabled = true;
        $config->fazpassApiUrl = 'https://api.example.test';
        $config->fazpassMerchantKey = 'test-merchant-key';
        $config->fazpassGatewayKey = 'test-gateway-key';
        $config->resendCooldownSeconds = 60;
        $config->maxRequestsPerAccountPerDay = 10;
        $config->maxRequestsGlobal = 100;
        $config->maxRequestsGlobalPerDay = 300;
        $config->maxVerificationAttempts = 5;

        return $config;
    }
}

final class HybridRecordingTransport implements HttpTransportInterface
{
    public int $requestCount = 0;
    public string $lastUrl = '';
    /** @var array<string, string> */
    public array $lastHeaders = [];
    /** @var array<string, mixed> */
    public array $lastPayload = [];

    /** @param array<string, HttpResponse> $responses */
    public function __construct(private readonly array $responses = [])
    {
    }

    public function post(string $url, array $headers, array $fields, int $timeoutSeconds): HttpResponse
    {
        return new HttpResponse(405, null, 'not used');
    }

    public function postJson(string $url, array $headers, array $payload, int $timeoutSeconds): HttpResponse
    {
        $this->requestCount++;
        $this->lastUrl = $url;
        $this->lastHeaders = $headers;
        $this->lastPayload = $payload;

        foreach ($this->responses as $path => $response) {
            if (str_ends_with($url, $path)) {
                return $response;
            }
        }

        return new HttpResponse(200, '{"status":"success","data":{"messageId":"DEFAULT-MSG"}}');
    }
}

final class HybridOtpMemoryRepository implements OtpRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $otps = [];
    public bool $rejectUpdates = false;

    public function transaction(callable $callback): mixed
    {
        return $callback();
    }

    public function lockAccount(int $anggotaId): void
    {
    }

    public function cleanup(string $before): int
    {
        return 0;
    }

    public function findActive(int $anggotaId, string $now): ?array
    {
        foreach (array_reverse($this->otps, true) as $otp) {
            if ((int) $otp['anggota_id'] === $anggotaId
                && $otp['used_at'] === null
                && in_array((string) $otp['status'], OtpStatus::ACTIVE, true)
                && (string) $otp['expires_at'] >= $now
            ) {
                return $otp;
            }
        }

        return null;
    }

    public function countAccountRequests(int $anggotaId, string $since): int
    {
        return count(array_filter($this->otps, static fn (array $otp): bool =>
            (int) $otp['anggota_id'] === $anggotaId
            && $otp['created_at'] >= $since));
    }

    public function countGlobalRequests(string $since): int
    {
        return count(array_filter($this->otps, static fn (array $otp): bool =>
            $otp['created_at'] >= $since));
    }

    public function cancelActive(int $anggotaId, string $now): void
    {
        foreach ($this->otps as &$otp) {
            if ((int) $otp['anggota_id'] === $anggotaId
                && $otp['used_at'] === null
                && in_array((string) $otp['status'], OtpStatus::ACTIVE, true)
            ) {
                $otp['status'] = OtpStatus::CANCELLED;
                $otp['updated_at'] = $now;
            }
        }
        unset($otp);
    }

    public function create(array $data): int
    {
        $id = count($this->otps) + 1;
        $this->otps[$id] = ['id' => $id, 'used_at' => null] + $data;

        return $id;
    }

    public function update(int $id, array $changes): bool
    {
        if ($this->rejectUpdates || ! isset($this->otps[$id])) {
            return false;
        }
        $this->otps[$id] = array_replace($this->otps[$id], $changes);

        return true;
    }

    public function transitionStatus(int $id, array $fromStatuses, string $toStatus, array $changes = []): bool
    {
        if ($fromStatuses === [] || ! OtpStatus::isKnown($toStatus)) {
            throw new \InvalidArgumentException('Transisi status OTP tidak valid.');
        }
        foreach ($fromStatuses as $fromStatus) {
            if (! OtpStatus::canTransition($fromStatus, $toStatus)) {
                throw new \InvalidArgumentException("Transisi status OTP {$fromStatus} -> {$toStatus} tidak valid.");
            }
        }
        if ($this->rejectUpdates
            || ! isset($this->otps[$id])
            || ! in_array((string) $this->otps[$id]['status'], $fromStatuses, true)
        ) {
            return false;
        }

        $this->otps[$id] = array_replace($this->otps[$id], $changes, ['status' => $toStatus]);

        return true;
    }

    public function consume(int $id, string $now): bool
    {
        if (! isset($this->otps[$id])
            || $this->otps[$id]['used_at'] !== null
            || ! in_array((string) $this->otps[$id]['status'], OtpStatus::VERIFIABLE, true)
            || (string) $this->otps[$id]['expires_at'] < $now
        ) {
            return false;
        }
        $this->otps[$id]['used_at'] = $now;
        $this->otps[$id]['status'] = OtpStatus::VERIFIED;
        $this->otps[$id]['updated_at'] = $now;

        return true;
    }
}
