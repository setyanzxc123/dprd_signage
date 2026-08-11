<?php

namespace App\Libraries\Otp\Contracts;

interface OtpRepositoryInterface
{
    public function transaction(callable $callback): mixed;

    public function lockAccount(int $anggotaId): void;

    public function cleanup(string $before): int;

    /** @return array<string, mixed>|null */
    public function findActive(int $anggotaId, string $now): ?array;

    public function countAccountRequests(int $anggotaId, string $since): int;

    public function countGlobalRequests(string $since): int;

    public function cancelActive(int $anggotaId, string $now): void;

    /** @param array<string, mixed> $data */
    public function create(array $data): int;

    /** @param array<string, mixed> $changes */
    public function update(int $id, array $changes): bool;

    /**
     * @param list<string> $fromStatuses
     * @param array<string, mixed> $changes
     */
    public function transitionStatus(int $id, array $fromStatuses, string $toStatus, array $changes = []): bool;

    public function consume(int $id, string $now): bool;
}
