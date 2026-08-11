<?php

namespace App\Libraries\Otp;

use App\Libraries\Otp\Contracts\OtpWebhookRepositoryInterface;

final class FazpassWebhookProcessor
{
    public const PROCESSED = 'processed';
    public const DUPLICATE = 'duplicate';
    public const IGNORED = 'ignored';
    public const NOT_FOUND = 'not_found';

    /** @var \Closure(): int */
    private \Closure $clock;

    public function __construct(
        private readonly OtpWebhookRepositoryInterface $repository,
        ?callable $clock = null,
    ) {
        $this->clock = \Closure::fromCallable($clock ?? static fn (): int => time());
    }

    public function process(?string $otpId, ?string $transactionId, string $providerStatus): string
    {
        $otpId = $this->identifier($otpId);
        $transactionId = $this->identifier($transactionId);
        if ($otpId === null && $transactionId === null) {
            throw new \InvalidArgumentException('Minimal satu identifier Fazpass wajib tersedia.');
        }

        $targetStatus = self::normalizeStatus($providerStatus);
        if ($targetStatus === null) {
            throw new \InvalidArgumentException('Status callback Fazpass tidak dikenal.');
        }

        $otp = $this->repository->findByProviderIdentifiers('fazpass', $otpId, $transactionId);
        if ($otp === null) {
            return self::NOT_FOUND;
        }

        $currentStatus = (string) $otp['status'];
        if ($currentStatus === $targetStatus) {
            return self::DUPLICATE;
        }
        if (! OtpStatus::canTransition($currentStatus, $targetStatus)) {
            return self::IGNORED;
        }

        $updated = $this->repository->transitionStatus(
            (int) $otp['id'],
            [$currentStatus],
            $targetStatus,
            ['updated_at' => date('Y-m-d H:i:s', ($this->clock)())],
        );
        if ($updated) {
            return self::PROCESSED;
        }

        // Row mungkin berubah setelah dibaca. Klasifikasikan ulang tanpa
        // menimpa state terbaru agar callback tetap idempotent.
        $latest = $this->repository->findByProviderIdentifiers('fazpass', $otpId, $transactionId);
        if (($latest['status'] ?? null) === $targetStatus) {
            return self::DUPLICATE;
        }

        return self::IGNORED;
    }

    public static function normalizeStatus(string $status): ?string
    {
        return match (strtolower(trim($status))) {
            'processing', 'pending'                 => OtpStatus::PENDING,
            'sent'                                  => OtpStatus::SENT,
            'delivered'                             => OtpStatus::DELIVERED,
            'verified'                              => OtpStatus::VERIFIED,
            'expired'                               => OtpStatus::EXPIRED,
            'error', 'failed', 'rejected',
            'undelivered'                           => OtpStatus::FAILED,
            default                                 => null,
        };
    }

    private function identifier(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
