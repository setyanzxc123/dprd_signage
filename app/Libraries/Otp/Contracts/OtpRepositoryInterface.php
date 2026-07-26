<?php

namespace App\Libraries\Otp\Contracts;

interface OtpRepositoryInterface
{
    public function cleanup(string $before): int;

    /** @return array<string, mixed>|null */
    public function findActive(int $accountId, string $now): ?array;

    public function countRequests(string $field, string $value, string $since): int;

    public function cancelActive(int $accountId, string $now): void;

    /** @param array<string, mixed> $data */
    public function create(array $data): int;

    /** @param array<string, mixed> $changes */
    public function update(int $id, array $changes): void;

    /** @param array<string, mixed> $context */
    public function audit(?int $otpId, ?int $accountId, string $event, array $context, string $createdAt): void;
}
