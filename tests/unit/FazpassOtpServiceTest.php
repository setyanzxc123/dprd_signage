<?php

use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use App\Libraries\Otp\FazpassOtpService;
use App\Libraries\Otp\Providers\FazpassProvider;
use App\Libraries\WhatsApp\Contracts\HttpTransportInterface;
use App\Libraries\WhatsApp\ValueObjects\HttpResponse;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Otp;

final class FazpassOtpServiceTest extends CIUnitTestCase
{
    private int $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = strtotime('2026-08-02 10:00:00');
    }

    public function testLegacyIpLimitCannotReduceSharedNetworkCapacityBelowOneHundred(): void
    {
        $this->assertGreaterThanOrEqual(100, (new Otp())->maxRequestsPerIp);
    }

    public function testAllowsFortyMembersBehindOneSharedIp(): void
    {
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport);

        for ($accountId = 1; $accountId <= 40; $accountId++) {
            $result = $service->request($accountId, '62812345' . str_pad((string) $accountId, 4, '0', STR_PAD_LEFT), '203.0.113.10');
            $this->assertTrue($result->success, 'Anggota ke-' . $accountId . ' seharusnya tidak diblokir oleh IP bersama.');
        }

        $this->assertSame(40, $transport->requestCount);
    }

    public function testResendIsAllowedAfterCooldownAndCancelsPreviousOtp(): void
    {
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport);

        $first = $service->request(7, '628123456789', '203.0.113.10');
        $this->now += 30;
        $cooldown = $service->request(7, '628123456789', '203.0.113.10');
        $this->now += 31;
        $resent = $service->request(7, '628123456789', '203.0.113.10');

        $this->assertTrue($first->success);
        $this->assertFalse($cooldown->success);
        $this->assertSame('cooldown', $cooldown->status);
        $this->assertSame(30, $cooldown->retryAfter);
        $this->assertTrue($resent->success);
        $this->assertSame(2, $transport->requestCount);
        $this->assertNotNull($repository->otps[1]['cancelled_at']);
    }

    public function testDailyAccountLimitStopsRepeatedProviderCost(): void
    {
        $config = $this->config();
        $config->maxRequestsPerPhone = 100;
        $config->maxRequestsPerAccountPerDay = 2;
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport, $config);

        $this->assertTrue($service->request(7, '628123456789', '203.0.113.10')->success);
        $this->now += 61;
        $this->assertTrue($service->request(7, '628123456789', '203.0.113.10')->success);
        $this->now += 61;
        $limited = $service->request(7, '628123456789', '203.0.113.10');

        $this->assertFalse($limited->success);
        $this->assertSame('rate_limited', $limited->status);
        $this->assertSame(2, $transport->requestCount);
    }

    public function testGlobalCircuitBreakerStopsRequestsAfterConfiguredBudget(): void
    {
        $config = $this->config();
        $config->maxRequestsGlobal = 3;
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport, $config);

        foreach ([1, 2, 3] as $accountId) {
            $this->assertTrue($service->request($accountId, '62812345678' . $accountId, '203.0.113.10')->success);
        }
        $limited = $service->request(4, '628123456784', '203.0.113.10');

        $this->assertFalse($limited->success);
        $this->assertSame('rate_limited', $limited->status);
        $this->assertSame(3, $transport->requestCount);
    }

    public function testDailyGlobalBudgetLimitsProviderCostAcrossHourlyWindows(): void
    {
        $config = $this->config();
        $config->maxRequestsGlobal = 100;
        $config->maxRequestsGlobalPerDay = 3;
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport, $config);

        foreach ([1, 2, 3] as $accountId) {
            $this->assertTrue($service->request($accountId, '62812345678' . $accountId, '203.0.113.10')->success);
        }
        $this->now += 3601;
        $limited = $service->request(4, '628123456784', '203.0.113.11');

        $this->assertFalse($limited->success);
        $this->assertSame('rate_limited', $limited->status);
        $this->assertSame(3, $transport->requestCount);
    }

    private function service(
        OtpMemoryRepository $repository,
        OtpRecordingTransport $transport,
        ?Otp $config = null,
    ): FazpassOtpService {
        $config ??= $this->config();
        $provider = new FazpassProvider($transport, $config);

        return new FazpassOtpService(
            $repository,
            $provider,
            $config,
            fn (): int => $this->now,
        );
    }

    private function config(): Otp
    {
        $config = new Otp();
        $config->fazpassApiUrl = 'https://api.example.test';
        $config->fazpassMerchantKey = 'merchant';
        $config->fazpassGatewayKey = 'gateway';
        $config->resendCooldownSeconds = 60;
        $config->maxRequestsPerPhone = 5;
        $config->maxRequestsPerIp = 100;
        $config->maxRequestsPerAccountPerDay = 10;
        $config->maxRequestsGlobal = 100;
        $config->maxRequestsGlobalPerDay = 300;

        return $config;
    }
}

final class OtpRecordingTransport implements HttpTransportInterface
{
    public int $requestCount = 0;

    public function post(string $url, array $headers, array $fields, int $timeoutSeconds): HttpResponse
    {
        return new HttpResponse(405, null, 'not used');
    }

    public function postJson(string $url, array $headers, array $payload, int $timeoutSeconds): HttpResponse
    {
        $this->requestCount++;

        return new HttpResponse(200, json_encode([
            'status' => true,
            'data'   => ['id' => 'otp-' . $this->requestCount],
        ], JSON_THROW_ON_ERROR));
    }
}

final class OtpMemoryRepository implements OtpRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $otps = [];
    /** @var list<array<string, mixed>> */
    private array $audits = [];

    public function transaction(callable $callback): mixed
    {
        return $callback();
    }

    public function lockAccount(int $accountId): void
    {
    }

    public function cleanup(string $before): int
    {
        return 0;
    }

    public function findActive(int $accountId, string $now): ?array
    {
        foreach (array_reverse($this->otps, true) as $otp) {
            if ((int) $otp['member_account_id'] === $accountId
                && $otp['used_at'] === null
                && $otp['cancelled_at'] === null
                && (string) $otp['expires_at'] >= $now
            ) {
                return $otp;
            }
        }

        return null;
    }

    public function countRequests(string $field, string $value, string $since): int
    {
        return count(array_filter($this->audits, static fn (array $audit): bool =>
            $audit['event'] === 'requested'
            && ($audit[$field] ?? null) === $value
            && $audit['created_at'] >= $since));
    }

    public function countAccountRequests(int $accountId, string $since): int
    {
        return count(array_filter($this->audits, static fn (array $audit): bool =>
            $audit['event'] === 'requested'
            && $audit['member_account_id'] === $accountId
            && $audit['created_at'] >= $since));
    }

    public function countGlobalRequests(string $since): int
    {
        return count(array_filter($this->audits, static fn (array $audit): bool =>
            $audit['event'] === 'requested' && $audit['created_at'] >= $since));
    }

    public function cancelActive(int $accountId, string $now): void
    {
        foreach ($this->otps as &$otp) {
            if ((int) $otp['member_account_id'] === $accountId && $otp['used_at'] === null && $otp['cancelled_at'] === null) {
                $otp['cancelled_at'] = $now;
                $otp['updated_at'] = $now;
            }
        }
        unset($otp);
    }

    public function create(array $data): int
    {
        $id = count($this->otps) + 1;
        $this->otps[$id] = ['id' => $id, 'used_at' => null, 'cancelled_at' => null] + $data;

        return $id;
    }

    public function update(int $id, array $changes): void
    {
        $this->otps[$id] = array_replace($this->otps[$id], $changes);
    }

    public function consume(int $id, string $now): bool
    {
        if (! isset($this->otps[$id]) || $this->otps[$id]['used_at'] !== null || $this->otps[$id]['cancelled_at'] !== null) {
            return false;
        }
        $this->otps[$id]['used_at'] = $now;

        return true;
    }

    public function audit(?int $otpId, ?int $accountId, string $event, array $context, string $createdAt): void
    {
        $this->audits[] = [
            'member_otp_id'     => $otpId,
            'member_account_id' => $accountId,
            'event'             => $event,
            'phone_hash'        => $context['phone_hash'] ?? null,
            'ip_hash'           => $context['ip_hash'] ?? null,
            'created_at'        => $createdAt,
        ];
    }
}
