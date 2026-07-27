<?php

namespace App\Libraries\Schedule\Contracts;

interface ScheduleReadRepositoryInterface
{
    /**
     * @param list<int>|null $allowedScheduleIds
     * @return list<array<string, mixed>>
     */
    public function findSchedules(
        bool $publicOnly,
        ?string $date,
        ?string $month,
        ?int $unitId,
        ?array $allowedScheduleIds = null,
    ): array;

    /** @return list<array<string, mixed>> */
    public function findUpcomingPublic(string $afterDate, int $limit): array;

    /** @return list<array{id: int|string, nama: string}> */
    public function findActiveUnits(): array;

    /** @return list<int> */
    public function findMemberUnitIds(int $memberId): array;

    /** @return list<int> */
    public function findScheduleIdsForMember(int $memberId): array;

    /**
     * @param list<int> $scheduleIds
     * @return array<int, list<array{id: int, nama: string}>>
     */
    public function findUnitsByScheduleIds(array $scheduleIds): array;
}
