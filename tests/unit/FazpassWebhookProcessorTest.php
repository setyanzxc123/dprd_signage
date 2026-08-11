<?php

use App\Libraries\Otp\Contracts\OtpWebhookRepositoryInterface;
use App\Libraries\Otp\FazpassWebhookProcessor;
use App\Libraries\Otp\OtpStatus;
use CodeIgniter\Test\CIUnitTestCase;

final class FazpassWebhookProcessorTest extends CIUnitTestCase
{
    private int $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = strtotime('2026-08-11 10:00:00');
    }

    public function testCallbackCanResolveOtpId(): void
    {
        $repository = $this->repository();
        $result = $this->processor($repository)->process('otp-a', null, 'delivered');

        $this->assertSame(FazpassWebhookProcessor::PROCESSED, $result);
        $this->assertSame(OtpStatus::DELIVERED, $repository->rows[1]['status']);
    }

    public function testCallbackCanResolveTransactionId(): void
    {
        $repository = $this->repository();
        $result = $this->processor($repository)->process(null, 'tx-a', 'sent');

        $this->assertSame(FazpassWebhookProcessor::PROCESSED, $result);
        $this->assertSame(OtpStatus::SENT, $repository->rows[1]['status']);
    }

    public function testBothIdentifiersMustBelongToTheSameOtp(): void
    {
        $repository = $this->repository();
        $repository->rows[2] = [
            'id' => 2,
            'provider' => 'fazpass',
            'provider_otp_id' => 'otp-b',
            'provider_transaction_id' => 'tx-b',
            'status' => OtpStatus::PENDING,
        ];

        $result = $this->processor($repository)->process('otp-a', 'tx-b', 'sent');

        $this->assertSame(FazpassWebhookProcessor::NOT_FOUND, $result);
        $this->assertSame(0, $repository->transitionCount);
    }

    public function testUnknownIdentifierReturnsNotFound(): void
    {
        $repository = $this->repository();

        $result = $this->processor($repository)->process('otp-missing', null, 'sent');

        $this->assertSame(FazpassWebhookProcessor::NOT_FOUND, $result);
        $this->assertSame(0, $repository->transitionCount);
    }

    public function testDuplicateCallbackHasNoAdditionalSideEffect(): void
    {
        $repository = $this->repository(status: OtpStatus::DELIVERED);

        $result = $this->processor($repository)->process('otp-a', 'tx-a', 'delivered');

        $this->assertSame(FazpassWebhookProcessor::DUPLICATE, $result);
        $this->assertSame(0, $repository->transitionCount);
    }

    public function testOutOfOrderCallbackCannotRegressDeliveryState(): void
    {
        $repository = $this->repository(status: OtpStatus::DELIVERED);

        $result = $this->processor($repository)->process('otp-a', null, 'sent');

        $this->assertSame(FazpassWebhookProcessor::IGNORED, $result);
        $this->assertSame(OtpStatus::DELIVERED, $repository->rows[1]['status']);
        $this->assertSame(0, $repository->transitionCount);
    }

    public function testTerminalStateCannotBeChangedByLateCallback(): void
    {
        $repository = $this->repository(status: OtpStatus::VERIFIED);

        $result = $this->processor($repository)->process(null, 'tx-a', 'delivered');

        $this->assertSame(FazpassWebhookProcessor::IGNORED, $result);
        $this->assertSame(OtpStatus::VERIFIED, $repository->rows[1]['status']);
    }

    public function testVerifiedCallbackUsesVerifiedTerminalState(): void
    {
        $repository = $this->repository(status: OtpStatus::DELIVERED);

        $result = $this->processor($repository)->process('otp-a', null, 'verified');

        $this->assertSame(FazpassWebhookProcessor::PROCESSED, $result);
        $this->assertSame(OtpStatus::VERIFIED, $repository->rows[1]['status']);
    }

    public function testChargedFailureStatusesCollapseToInternalFailedState(): void
    {
        foreach (['REJECTED', 'UNDELIVERED'] as $providerStatus) {
            $repository = $this->repository();

            $result = $this->processor($repository)->process('otp-a', null, $providerStatus);

            $this->assertSame(FazpassWebhookProcessor::PROCESSED, $result);
            $this->assertSame(OtpStatus::FAILED, $repository->rows[1]['status']);
        }
    }

    public function testUnknownStatusIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->processor($this->repository())->process('otp-a', null, 'mystery');
    }

    public function testMissingIdentifiersAreRejectedByDomainProcessor(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->processor($this->repository())->process(null, '  ', 'sent');
    }

    private function processor(FazpassWebhookMemoryRepository $repository): FazpassWebhookProcessor
    {
        return new FazpassWebhookProcessor($repository, fn (): int => $this->now);
    }

    private function repository(string $status = OtpStatus::PENDING): FazpassWebhookMemoryRepository
    {
        return new FazpassWebhookMemoryRepository([[
            'id' => 1,
            'provider' => 'fazpass',
            'provider_otp_id' => 'otp-a',
            'provider_transaction_id' => 'tx-a',
            'status' => $status,
        ]]);
    }
}

final class FazpassWebhookMemoryRepository implements OtpWebhookRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];
    public int $transitionCount = 0;

    /** @param list<array<string, mixed>> $rows */
    public function __construct(array $rows)
    {
        foreach ($rows as $row) {
            $this->rows[(int) $row['id']] = $row;
        }
    }

    public function findByProviderIdentifiers(
        string $provider,
        ?string $providerOtpId,
        ?string $providerTransactionId,
    ): ?array {
        foreach ($this->rows as $row) {
            if ($row['provider'] !== $provider
                || ($providerOtpId !== null && $row['provider_otp_id'] !== $providerOtpId)
                || ($providerTransactionId !== null && $row['provider_transaction_id'] !== $providerTransactionId)
            ) {
                continue;
            }

            return ['id' => $row['id'], 'status' => $row['status']];
        }

        return null;
    }

    public function transitionStatus(int $id, array $fromStatuses, string $toStatus, array $changes = []): bool
    {
        if (! isset($this->rows[$id])
            || ! in_array($this->rows[$id]['status'], $fromStatuses, true)
        ) {
            return false;
        }

        $this->rows[$id] = array_replace($this->rows[$id], $changes, ['status' => $toStatus]);
        $this->transitionCount++;

        return true;
    }
}
