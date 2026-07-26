<?php

use App\Libraries\Otp\Contracts\OtpDeliveryInterface;
use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use App\Libraries\Otp\OtpService;
use App\Libraries\Otp\ValueObjects\OtpDeliveryResult;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Otp;

/**
 * @internal
 */
final class OtpServiceTest extends CIUnitTestCase
{
    private int $now;
    private InMemoryOtpRepository $repository;
    private FakeOtpDelivery $delivery;
    private Otp $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = strtotime('2026-07-26 12:00:00');
        $this->repository = new InMemoryOtpRepository();
        $this->delivery = new FakeOtpDelivery();
        $this->config = new Otp();
        $this->config->ttlSeconds = 300;
        $this->config->resendCooldownSeconds = 60;
        $this->config->requestWindowSeconds = 3600;
        $this->config->maxRequestsPerPhone = 2;
        $this->config->maxRequestsPerIp = 3;
        $this->config->maxVerificationAttempts = 3;
    }

    public function testCreatesHashedSixDigitOtpAndVerifiesItOnce(): void
    {
        $service = $this->service();

        $request = $service->request(10, '08123456789', '192.0.2.10');
        $row = $this->repository->otps[1];

        $this->assertTrue($request->success);
        $this->assertSame($this->now + 300, $request->expiresAt);
        $this->assertSame('042017', $this->delivery->lastCode);
        $this->assertNotSame('042017', $row['code_hash']);
        $this->assertTrue(password_verify('042017', $row['code_hash']));

        $verified = $service->verify(10, '042017', '192.0.2.10', '08123456789');
        $reused = $service->verify(10, '042017', '192.0.2.10', '08123456789');

        $this->assertTrue($verified->success);
        $this->assertSame('verified', $verified->status);
        $this->assertFalse($reused->success);
        $this->assertSame('invalid', $reused->status);
        $this->assertNotNull($this->repository->otps[1]['used_at']);
    }

    public function testRejectsWrongAndExpiredOtp(): void
    {
        $service = $this->service();
        $service->request(10, '08123456789', '192.0.2.10');

        $wrong = $service->verify(10, '999999', '192.0.2.10', '08123456789');
        $this->now += 301;
        $expired = $service->verify(10, '042017', '192.0.2.10', '08123456789');

        $this->assertSame('invalid', $wrong->status);
        $this->assertSame(1, $this->repository->otps[1]['verification_attempts']);
        $this->assertSame('invalid', $expired->status);
    }

    public function testLocksOtpAtMaximumVerificationAttempts(): void
    {
        $service = $this->service();
        $service->request(10, '08123456789', '192.0.2.10');

        $service->verify(10, '111111', '192.0.2.10', '08123456789');
        $service->verify(10, '222222', '192.0.2.10', '08123456789');
        $last = $service->verify(10, '333333', '192.0.2.10', '08123456789');

        $this->assertSame('too_many_attempts', $last->status);
        $this->assertNotNull($this->repository->otps[1]['cancelled_at']);
    }

    public function testEnforcesCooldownAndRequestRateLimit(): void
    {
        $this->delivery->result = new OtpDeliveryResult('failed', 'fonnte', error: 'explicit failure');
        $service = $this->service();

        $first = $service->request(10, '08123456789', '192.0.2.10');
        $cooldown = $service->request(10, '08123456789', '192.0.2.10');
        $this->now += 61;
        $second = $service->request(10, '08123456789', '192.0.2.10');
        $this->now += 61;
        $limited = $service->request(10, '08123456789', '192.0.2.10');

        $this->assertSame('failed', $first->status);
        $this->assertSame('cooldown', $cooldown->status);
        $this->assertSame('failed', $second->status);
        $this->assertSame('rate_limited', $limited->status);
        $this->assertSame(2, $this->delivery->sendCount);
    }

    public function testAmbiguousDeliveryNeverGeneratesOrSendsAnotherActiveCode(): void
    {
        $this->delivery->result = new OtpDeliveryResult('ambiguous', 'fonnte', error: 'timeout');
        $service = $this->service();

        $first = $service->request(10, '08123456789', '192.0.2.10');
        $this->now += 120;
        $second = $service->request(10, '08123456789', '192.0.2.10');

        $this->assertSame('ambiguous', $first->status);
        $this->assertSame('delivery_ambiguous', $second->status);
        $this->assertSame($this->now + 180, $second->expiresAt);
        $this->assertCount(1, $this->repository->otps);
        $this->assertSame(1, $this->delivery->sendCount);
    }

    public function testUnpersistedDeliveryStatusRemainsAmbiguousUntilExpiry(): void
    {
        $service = $this->service();
        $this->repository->failNextUpdate = true;

        try {
            $service->request(10, '08123456789', '192.0.2.10');
            $this->fail('Pembaruan status delivery seharusnya gagal.');
        } catch (RuntimeException) {
            // Record OTP tetap berstatus created dan tidak boleh langsung diganti.
        }

        $this->now += 120;
        $second = $service->request(10, '08123456789', '192.0.2.10');

        $this->assertSame('delivery_ambiguous', $second->status);
        $this->assertCount(1, $this->repository->otps);
        $this->assertSame(1, $this->delivery->sendCount);
    }

    public function testAcceptedOtpCanBeReplacedAfterResendCooldown(): void
    {
        $service = $this->service();
        $service->request(10, '08123456789', '192.0.2.10');
        $this->now += 61;

        $second = $service->request(10, '08123456789', '192.0.2.10');

        $this->assertTrue($second->success);
        $this->assertCount(2, $this->repository->otps);
        $this->assertNotNull($this->repository->otps[1]['cancelled_at']);
        $this->assertSame(2, $this->delivery->sendCount);
    }

    public function testCreatesSingleUseEmergencyOtpWithAdminAudit(): void
    {
        $service = $this->service();

        $result = $service->createEmergency(10, 7, 'Provider Fonnte sedang terputus');

        $this->assertSame('042017', $result->code);
        $this->assertSame('emergency', $this->repository->otps[1]['source']);
        $this->assertSame(7, $this->repository->otps[1]['created_by_admin_id']);
        $this->assertTrue(password_verify($result->code, $this->repository->otps[1]['code_hash']));
        $this->assertTrue($service->verify(10, $result->code, '192.0.2.10')->success);
        $this->assertFalse($service->verify(10, $result->code, '192.0.2.10')->success);
        $this->assertContains('emergency_created', array_column($this->repository->audits, 'event'));
    }

    public function testRejectsOtpWhenPhoneBindingChanges(): void
    {
        $service = $this->service();
        $service->request(10, '08123456789', '192.0.2.10');

        $result = $service->verify(10, '042017', '192.0.2.10', '08129999999');

        $this->assertFalse($result->success);
        $this->assertNull($this->repository->otps[1]['used_at']);
        $this->assertContains('phone_changed', array_column(array_column($this->repository->audits, 'context'), 'reason'));
    }

    public function testVerificationRequiresAtomicConsume(): void
    {
        $service = $this->service();
        $service->request(10, '08123456789', '192.0.2.10');
        $this->repository->consumeAllowed = false;

        $result = $service->verify(10, '042017', '192.0.2.10', '08123456789');

        $this->assertFalse($result->success);
        $this->assertNull($this->repository->otps[1]['used_at']);
        $this->assertGreaterThanOrEqual(2, $this->repository->lockCount);
    }

    private function service(): OtpService
    {
        return new OtpService(
            $this->repository,
            $this->delivery,
            $this->config,
            fn (): int => $this->now,
            static fn (int $min, int $max): int => 42017,
        );
    }
}

final class FakeOtpDelivery implements OtpDeliveryInterface
{
    public OtpDeliveryResult $result;
    public int $sendCount = 0;
    public ?string $lastCode = null;

    public function __construct()
    {
        $this->result = new OtpDeliveryResult('pending', 'fonnte', 'message-1', 'request-1');
    }

    public function send(string $phone, string $code, int $ttlSeconds): OtpDeliveryResult
    {
        $this->sendCount++;
        $this->lastCode = $code;

        return $this->result;
    }
}

final class InMemoryOtpRepository implements OtpRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $otps = [];

    /** @var list<array<string, mixed>> */
    public array $audits = [];
    public bool $consumeAllowed = true;
    public bool $failNextUpdate = false;
    public int $lockCount = 0;

    public function transaction(callable $callback): mixed
    {
        return $callback();
    }

    public function lockAccount(int $accountId): void
    {
        $this->lockCount++;
    }

    public function cleanup(string $before): int
    {
        $removed = 0;
        foreach ($this->otps as $id => $otp) {
            if ($otp['expires_at'] < $before && $otp['created_at'] < $before) {
                unset($this->otps[$id]);
                $removed++;
            }
        }

        return $removed;
    }

    public function findActive(int $accountId, string $now): ?array
    {
        $matches = array_filter($this->otps, static fn (array $otp): bool =>
            $otp['member_account_id'] === $accountId
            && ($otp['used_at'] ?? null) === null
            && ($otp['cancelled_at'] ?? null) === null
            && $otp['expires_at'] >= $now);

        return $matches === [] ? null : end($matches);
    }

    public function countRequests(string $field, string $value, string $since): int
    {
        return count(array_filter($this->audits, static fn (array $audit): bool =>
            $audit['event'] === 'requested'
            && ($audit['context'][$field] ?? null) === $value
            && $audit['created_at'] >= $since));
    }

    public function cancelActive(int $accountId, string $now): void
    {
        foreach ($this->otps as &$otp) {
            if ($otp['member_account_id'] === $accountId && ($otp['used_at'] ?? null) === null && ($otp['cancelled_at'] ?? null) === null) {
                $otp['cancelled_at'] = $now;
                $otp['updated_at'] = $now;
            }
        }
    }

    public function create(array $data): int
    {
        $id = count($this->otps) + 1;
        $this->otps[$id] = ['id' => $id, 'used_at' => null, 'cancelled_at' => null] + $data;

        return $id;
    }

    public function update(int $id, array $changes): void
    {
        if ($this->failNextUpdate) {
            $this->failNextUpdate = false;
            throw new RuntimeException('Simulasi kegagalan update delivery.');
        }

        $this->otps[$id] = $changes + $this->otps[$id];
    }

    public function consume(int $id, string $now): bool
    {
        if (! $this->consumeAllowed
            || ! isset($this->otps[$id])
            || $this->otps[$id]['used_at'] !== null
            || $this->otps[$id]['cancelled_at'] !== null
            || $this->otps[$id]['expires_at'] < $now) {
            return false;
        }

        $this->otps[$id]['used_at'] = $now;
        $this->otps[$id]['updated_at'] = $now;

        return true;
    }

    public function audit(?int $otpId, ?int $accountId, string $event, array $context, string $createdAt): void
    {
        $this->audits[] = compact('otpId', 'accountId', 'event', 'context', 'createdAt');
        $last = array_key_last($this->audits);
        $this->audits[$last]['created_at'] = $createdAt;
    }
}
