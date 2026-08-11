<?php

namespace App\Libraries\Otp\Contracts;

interface OtpWebhookRepositoryInterface
{
    /** @return array{id: int|string, status: string}|null */
    public function findByProviderIdentifiers(
        string $provider,
        ?string $providerOtpId,
        ?string $providerTransactionId,
    ): ?array;

    /**
     * @param list<string> $fromStatuses
     * @param array<string, mixed> $changes
     */
    public function transitionStatus(int $id, array $fromStatuses, string $toStatus, array $changes = []): bool;
}
