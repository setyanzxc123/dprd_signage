<?php

namespace App\Libraries\Schedule;

use App\Libraries\Schedule\Contracts\ScheduleReadRepositoryInterface;
use App\Libraries\Schedule\Persistence\DatabaseScheduleReadRepository;
use App\Models\JadwalUmumModel;

final class ScheduleReadService
{
    /** @var \Closure(): int */
    private \Closure $clock;

    public function __construct(
        ?ScheduleReadRepositoryInterface $repository = null,
        ?callable $clock = null,
    ) {
        $this->repository = $repository ?? new DatabaseScheduleReadRepository();
        $this->clock = \Closure::fromCallable($clock ?? static fn (): int => time());
    }

    private readonly ScheduleReadRepositoryInterface $repository;

    /** @return array<string, mixed> */
    public function publicAgenda(array $filters): array
    {
        $range = $this->normalizeRange($filters);
        $unitId = $this->normalizeUnitId($filters['unit'] ?? null);
        $rows = $this->repository->findSchedules(
            true,
            $range['date'],
            $range['month'],
            $unitId,
        );

        return [
            'date'  => $range['date'],
            'month' => $range['month'],
            'scope' => 'publik',
            'units' => $this->activeUnits(),
            'data'  => $this->formatRows($rows),
        ];
    }

    /** @return array<string, mixed> */
    public function memberAgenda(int $memberId, array $filters): array
    {
        $range = $this->normalizeRange($filters);
        $unitId = $this->normalizeUnitId($filters['unit'] ?? null);
        $scope = ($filters['scope'] ?? 'saya') === 'semua' ? 'semua' : 'saya';
        $allowedIds = $this->repository->findScheduleIdsForMember($memberId);
        $memberUnitIds = $this->repository->findMemberUnitIds($memberId);
        $rows = $this->repository->findSchedules(
            false,
            $range['date'],
            $range['month'],
            $unitId,
            $allowedIds,
        );
        if ($scope === 'semua') {
            $rowMap = [];
            foreach ($rows as $row) {
                $rowMap[(string) $row['id']] = $row;
            }
            $publicRows = $this->repository->findSchedules(
                true,
                $range['date'],
                $range['month'],
                $unitId,
            );
            foreach ($publicRows as $row) {
                $rowMap[(string) $row['id']] = $row;
            }
            $rows = array_values($rowMap);
        }

        return [
            'date'  => $range['date'],
            'month' => $range['month'],
            'scope' => $scope,
            'units' => $this->activeUnits(),
            'data'  => $this->formatRows($rows, $memberUnitIds, true),
        ];
    }

    /** @return array{date: string, jadwal: list<array<string, mixed>>, upcoming: list<array<string, mixed>>} */
    public function signage(?string $date = null): array
    {
        $date = $this->validDate($date) ? $date : date('Y-m-d', ($this->clock)());
        $todayRows = $this->repository->findSchedules(true, $date, null, null);
        $upcomingRows = $this->repository->findUpcomingPublic($date, 5);

        return [
            'date'     => $date,
            'jadwal'   => $this->formatRows($todayRows),
            'upcoming' => $this->formatRows($upcomingRows),
        ];
    }

    /** @return list<array{id: int, nama: string}> */
    public function activeUnits(): array
    {
        return array_map(
            static fn (array $unit): array => [
                'id'   => (int) $unit['id'],
                'nama' => (string) $unit['nama'],
            ],
            $this->repository->findActiveUnits(),
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<int> $memberUnitIds
     * @return list<array<string, mixed>>
     */
    private function formatRows(
        array $rows,
        array $memberUnitIds = [],
        bool $isMember = false,
    ): array
    {
        $unitMap = $this->repository->findUnitsByScheduleIds(array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows,
        ));

        return array_map(function (array $row) use ($unitMap, $memberUnitIds, $isMember): array {
            $id = (int) $row['id'];
            $units = $unitMap[$id] ?? [];
            $unitIds = array_column($units, 'id');
            $row['id'] = $id;
            $row['waktu_mulai'] = substr((string) $row['waktu_mulai'], 0, 5);
            $row['waktu_selesai'] = substr((string) $row['waktu_selesai'], 0, 5);
            $row['status'] = $this->currentStatus($row);
            $row['ruangan'] = $this->displayLocation($row);
            $row['units'] = $units;
            $row['unit_ids'] = $unitIds;
            $row['komisi'] = implode(', ', array_column($units, 'nama'));
            $row['is_public'] = (int) ($row['is_publik'] ?? 0) === 1;
            $row['is_participant'] = $memberUnitIds !== []
                && array_intersect($unitIds, $memberUnitIds) !== [];
            $this->formatResourceAccess($row, 'materi', $isMember);
            $this->formatResourceAccess($row, 'stream', $isMember);
            $row['has_undangan'] = $isMember && trim((string) ($row['undangan_file'] ?? '')) !== '';
            unset(
                $row['nama_ruangan'],
                $row['lokasi_lainnya'],
                $row['is_publik'],
                $row['materi_url'],
                $row['materi_akses'],
                $row['stream_url'],
                $row['stream_akses'],
                $row['undangan_file'],
                $row['undangan_nama_asli'],
            );

            return $row;
        }, $rows);
    }

    /**
     * URL asli tidak pernah dikirim oleh service. Controller hanya membentuk URL
     * proxy setelah akses efektif dinyatakan boleh.
     *
     * @param array<string, mixed> $row
     */
    private function formatResourceAccess(array &$row, string $resource, bool $isMember): void
    {
        $default = $resource === 'materi'
            ? ScheduleResourceAccess::PARTICIPANT
            : ScheduleResourceAccess::MEMBER;
        $access = ScheduleResourceAccess::normalize($row[$resource . '_akses'] ?? null, $default);
        $available = trim((string) ($row[$resource . '_url'] ?? '')) !== '';
        $allowed = $available && ScheduleResourceAccess::canView(
            $access,
            $isMember,
            (bool) $row['is_participant'],
        );

        $row['has_' . $resource] = $allowed;
        $row[$resource . '_access'] = ($isMember || $allowed) && $available ? $access : null;
        $row[$resource . '_access_label'] = ($isMember || $allowed) && $available
            ? ScheduleResourceAccess::label($access)
            : null;
        $row[$resource . '_restricted'] = $isMember && $available && ! $allowed;
    }

    /** @return array{date: ?string, month: ?string} */
    private function normalizeRange(array $filters): array
    {
        $month = trim((string) ($filters['month'] ?? ''));
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1) {
            return ['date' => null, 'month' => $month];
        }

        $date = trim((string) ($filters['date'] ?? ''));

        return [
            'date'  => $this->validDate($date) ? $date : date('Y-m-d', ($this->clock)()),
            'month' => null,
        ];
    }

    private function normalizeUnitId(mixed $unitId): ?int
    {
        $unitId = filter_var($unitId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $unitId === false ? null : (int) $unitId;
    }

    private function validDate(?string $date): bool
    {
        if ($date === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));

        return checkdate($month, $day, $year);
    }

    private function currentStatus(array $row): string
    {
        if (($row['source'] ?? '') === JadwalUmumModel::SOURCE) {
            return JadwalUmumModel::resolveLifecycleStatus(
                (string) ($row['tanggal'] ?? ''),
                $row['waktu_mulai'] ?? null,
                $row['waktu_selesai'] ?? null,
                ($this->clock)(),
            );
        }

        $storedStatus = (string) ($row['status'] ?? 'menunggu');
        if (in_array($storedStatus, ['proyeksi', 'ditunda', 'dibatalkan'], true)) {
            return $storedStatus;
        }

        $start = strtotime((string) $row['tanggal'] . ' ' . (string) $row['waktu_mulai']);
        $end = strtotime((string) $row['tanggal'] . ' ' . (string) $row['waktu_selesai']);
        $now = ($this->clock)();
        if ($start === false || $end === false) {
            return $storedStatus;
        }
        if ($end <= $now) {
            return 'selesai';
        }
        if ($start <= $now) {
            return 'berlangsung';
        }
        if ($start - $now <= 1800) {
            return 'persiapan';
        }

        return 'menunggu';
    }

    private function displayLocation(array $row): string
    {
        $other = trim((string) ($row['lokasi_lainnya'] ?? ''));

        return $other !== '' ? $other : (string) ($row['nama_ruangan'] ?? '-');
    }
}
