<?php

use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use App\Libraries\Otp\FazpassOtpService;
use App\Libraries\Otp\OtpStatus;
use App\Libraries\Otp\OtpService;
use App\Libraries\Otp\Persistence\DatabaseOtpRepository;
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

    public function testDatabaseRepositoryCanBeLoadedThroughItsContract(): void
    {
        $this->assertInstanceOf(OtpRepositoryInterface::class, new DatabaseOtpRepository());
    }

    public function testApplicationServiceAcceptsRepositoryContract(): void
    {
        $config = $this->config();
        $provider = new FazpassProvider(new OtpRecordingTransport(), $config);

        $this->assertInstanceOf(OtpService::class, new OtpService(
            new OtpMemoryRepository(),
            $provider,
            $config,
            fn (): int => $this->now,
        ));
    }

    public function testAllowsFortyMembersBehindOneSharedIp(): void
    {
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport);

        for ($accountId = 1; $accountId <= 40; $accountId++) {
            $result = $service->request($accountId, '62812345' . str_pad((string) $accountId, 4, '0', STR_PAD_LEFT));
            $this->assertTrue($result->success, 'Anggota ke-' . $accountId . ' seharusnya tidak diblokir oleh IP bersama.');
        }

        $this->assertSame(40, $transport->requestCount);
    }

    public function testResendIsAllowedAfterCooldownAndCancelsPreviousOtp(): void
    {
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport);

        $first = $service->request(7, '628123456789');
        $this->now += 30;
        $cooldown = $service->request(7, '628123456789');
        $this->now += 31;
        $resent = $service->request(7, '628123456789');

        $this->assertTrue($first->success);
        $this->assertFalse($cooldown->success);
        $this->assertSame('cooldown', $cooldown->status);
        $this->assertSame(30, $cooldown->retryAfter);
        $this->assertTrue($resent->success);
        $this->assertSame(2, $transport->requestCount);
        $this->assertSame('cancelled', $repository->otps[1]['status']);
    }

    public function testDailyAccountLimitStopsRepeatedProviderCost(): void
    {
        $config = $this->config();
        $config->maxRequestsPerPhone = 100;
        $config->maxRequestsPerAccountPerDay = 2;
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport, $config);

        $this->assertTrue($service->request(7, '628123456789')->success);
        $this->now += 61;
        $this->assertTrue($service->request(7, '628123456789')->success);
        $this->now += 61;
        $limited = $service->request(7, '628123456789');

        $this->assertFalse($limited->success);
        $this->assertSame('rate_limited', $limited->status);
        $this->assertSame(2, $transport->requestCount);
    }

    public function testFazpassIdentifiersAreStoredSeparatelyFromEmergencyHash(): void
    {
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport);

        $this->assertTrue($service->request(7, '628123456789')->success);

        $otp = $repository->otps[1];
        $this->assertNull($otp['code_hash']);
        $this->assertSame('otp-1', $otp['provider_otp_id']);
        $this->assertSame('tx-1', $otp['provider_transaction_id']);
    }

    public function testFazpassVerificationUsesProviderOtpId(): void
    {
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport);

        $service->request(7, '628123456789');
        $verified = $service->verify(7, '123456');

        $this->assertTrue($verified->success);
        $this->assertSame('otp-1', $transport->lastPayload['otp_id']);
        $this->assertSame('verified', $repository->otps[1]['status']);
    }

    public function testProviderSuccessIsRejectedWhenIdentifiersCannotBePersisted(): void
    {
        $repository = new OtpMemoryRepository();
        $repository->rejectUpdates = true;
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport);

        $result = $service->request(7, '628123456789');

        $this->assertFalse($result->success);
        $this->assertSame('failed', $result->status);
        $this->assertSame('Referensi OTP tidak dapat disimpan.', $result->error);
        $this->assertSame(1, $transport->requestCount);
        $this->assertSame('created', $repository->otps[1]['status']);
    }

    public function testEmergencyOtpIsVerifiedLocallyWithAdminContext(): void
    {
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $config = $this->config();
        $service = new OtpService(
            $repository,
            new FazpassProvider($transport, $config),
            $config,
            fn (): int => $this->now,
        );

        $emergency = $service->createEmergency(7, 42);
        $verified = $service->verify(7, $emergency->code);

        $this->assertTrue($verified->success);
        $this->assertSame(0, $transport->requestCount);
        $this->assertTrue(password_verify($emergency->code, $repository->otps[1]['code_hash']));
        $this->assertSame(42, $repository->otps[1]['created_by_admin_id']);
        $this->assertSame('verified', $repository->otps[1]['status']);
    }

    public function testEmergencyOtpIsCancelledAfterMaximumFailedAttempts(): void
    {
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $config = $this->config();
        $config->maxVerificationAttempts = 2;
        $service = new OtpService(
            $repository,
            new FazpassProvider($transport, $config),
            $config,
            fn (): int => $this->now,
        );

        $emergency = $service->createEmergency(7, 42);
        $wrongCode = $emergency->code === '000000' ? '000001' : '000000';
        $first = $service->verify(7, $wrongCode);
        $second = $service->verify(7, $wrongCode);

        $this->assertFalse($first->success);
        $this->assertSame('invalid', $first->status);
        $this->assertFalse($second->success);
        $this->assertSame('too_many_attempts', $second->status);
        $this->assertSame(2, $repository->otps[1]['attempts']);
        $this->assertSame('cancelled', $repository->otps[1]['status']);
        $this->assertSame(0, $transport->requestCount);
    }

    public function testExpiredEmergencyOtpCannotBeVerified(): void
    {
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $config = $this->config();
        $service = new OtpService(
            $repository,
            new FazpassProvider($transport, $config),
            $config,
            fn (): int => $this->now,
        );

        $emergency = $service->createEmergency(7, 42);
        $this->now += $config->ttlSeconds + 1;

        $this->assertFalse($service->verify(7, $emergency->code)->success);
        $this->assertNull($repository->otps[1]['used_at']);
        $this->assertSame(0, $transport->requestCount);
    }

    public function testGlobalCircuitBreakerStopsRequestsAfterConfiguredBudget(): void
    {
        $config = $this->config();
        $config->maxRequestsGlobal = 3;
        $repository = new OtpMemoryRepository();
        $transport = new OtpRecordingTransport();
        $service = $this->service($repository, $transport, $config);

        foreach ([1, 2, 3] as $accountId) {
            $this->assertTrue($service->request($accountId, '62812345678' . $accountId)->success);
        }
        $limited = $service->request(4, '628123456784');

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
            $this->assertTrue($service->request($accountId, '62812345678' . $accountId)->success);
        }
        $this->now += 3601;
        $limited = $service->request(4, '628123456784');

        $this->assertFalse($limited->success);
        $this->assertSame('rate_limited', $limited->status);
        $this->assertSame(3, $transport->requestCount);
    }

    public function testTerminalStatusesAreNeverReturnedAsActive(): void
    {
        foreach (OtpStatus::TERMINAL as $index => $status) {
            $repository = new OtpMemoryRepository();
            $repository->otps[$index + 1] = [
                'id'         => $index + 1,
                'anggota_id' => 7,
                'provider'   => 'fazpass',
                'status'     => $status,
                'used_at'    => null,
                'expires_at' => date('Y-m-d H:i:s', $this->now + 300),
                'created_at' => date('Y-m-d H:i:s', $this->now),
            ];

            $this->assertNull(
                $repository->findActive(7, date('Y-m-d H:i:s', $this->now)),
                "Status {$status} tidak boleh dianggap aktif.",
            );
        }
    }

    public function testConsumeOnlyAcceptsVerifiableStatus(): void
    {
        $repository = new OtpMemoryRepository();
        $expiresAt = date('Y-m-d H:i:s', $this->now + 300);
        $now = date('Y-m-d H:i:s', $this->now);
        foreach ([OtpStatus::CREATED, ...OtpStatus::TERMINAL] as $index => $status) {
            $repository->otps[$index + 1] = [
                'id'         => $index + 1,
                'status'     => $status,
                'used_at'    => null,
                'expires_at' => $expiresAt,
            ];
            $this->assertFalse($repository->consume($index + 1, $now));
        }

        $verifiableId = count($repository->otps) + 1;
        $repository->otps[$verifiableId] = [
            'id'         => $verifiableId,
            'status'     => OtpStatus::PENDING,
            'used_at'    => null,
            'expires_at' => $expiresAt,
        ];
        $this->assertTrue($repository->consume($verifiableId, $now));
        $this->assertSame(OtpStatus::VERIFIED, $repository->otps[$verifiableId]['status']);
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
    /** @var array<string, mixed> */
    public array $lastPayload = [];

    public function post(string $url, array $headers, array $fields, int $timeoutSeconds): HttpResponse
    {
        return new HttpResponse(405, null, 'not used');
    }

    public function postJson(string $url, array $headers, array $payload, int $timeoutSeconds): HttpResponse
    {
        $this->requestCount++;
        $this->lastPayload = $payload;

        if (str_ends_with($url, '/otp/verify')) {
            return new HttpResponse(200, '{"status":true}');
        }

        return new HttpResponse(200, json_encode([
            'status' => true,
            'data'   => [
                'id'             => 'otp-' . $this->requestCount,
                'transaction_id' => 'tx-' . $this->requestCount,
            ],
        ], JSON_THROW_ON_ERROR));
    }
}

final class OtpMemoryRepository implements OtpRepositoryInterface
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
            && ($otp['provider'] ?? null) === 'fazpass'
            && $otp['created_at'] >= $since));
    }

    public function countGlobalRequests(string $since): int
    {
        return count(array_filter($this->otps, static fn (array $otp): bool =>
            ($otp['provider'] ?? null) === 'fazpass'
            && $otp['created_at'] >= $since));
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
        if ($this->rejectUpdates) {
            return false;
        }
        $this->otps[$id] = array_replace($this->otps[$id], $changes);

        return true;
    }

    public function transitionStatus(int $id, array $fromStatuses, string $toStatus, array $changes = []): bool
    {
        if ($fromStatuses === [] || ! OtpStatus::isKnown($toStatus)) {
            throw new InvalidArgumentException('Transisi status OTP tidak valid.');
        }
        foreach ($fromStatuses as $fromStatus) {
            if (! OtpStatus::canTransition($fromStatus, $toStatus)) {
                throw new InvalidArgumentException("Transisi status OTP {$fromStatus} -> {$toStatus} tidak valid.");
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
            || (string) $this->otps[$id]['expires_at'] < $now) {
            return false;
        }
        $this->otps[$id]['used_at'] = $now;
        $this->otps[$id]['status'] = OtpStatus::VERIFIED;
        $this->otps[$id]['updated_at'] = $now;

        return true;
    }
}
