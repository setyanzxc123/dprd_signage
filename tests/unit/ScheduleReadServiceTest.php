<?php

use App\Libraries\Schedule\Contracts\ScheduleReadRepositoryInterface;
use App\Libraries\Schedule\ScheduleReadService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ScheduleReadServiceTest extends CIUnitTestCase
{
    private int $now;
    private FakeScheduleReadRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = strtotime('2026-07-27 10:00:00');
        $this->repository = new FakeScheduleReadRepository();
        $this->repository->units = [
            ['id' => 3, 'nama' => 'Komisi I'],
            ['id' => 4, 'nama' => 'Bamus'],
        ];
    }

    public function testPublicAgendaRequestsOnlyPublicSchedulesAndNormalizesOutput(): void
    {
        $this->repository->schedules = [$this->schedule([
            'is_publik'       => 1,
            'lokasi_lainnya' => 'Ruang Sidang Utama',
        ])];
        $this->repository->scheduleUnits = [
            10 => [['id' => 3, 'nama' => 'Komisi I']],
        ];

        $result = $this->service()->publicAgenda([
            'date'  => '2026-07-27',
            'unit'  => '3',
            'scope' => 'saya',
        ]);

        $this->assertTrue($this->repository->lastPublicOnly);
        $this->assertSame('2026-07-27', $this->repository->lastDate);
        $this->assertSame(3, $this->repository->lastUnitId);
        $this->assertNull($this->repository->lastAllowedScheduleIds);
        $this->assertSame('publik', $result['scope']);
        $this->assertSame('Ruang Sidang Utama', $result['data'][0]['ruangan']);
        $this->assertSame('Komisi I', $result['data'][0]['komisi']);
        $this->assertTrue($result['data'][0]['is_public']);
        $this->assertFalse($result['data'][0]['is_participant']);
    }

    public function testMemberAgendaScopeSayaRestrictsSchedulesByMembership(): void
    {
        $this->repository->memberScheduleIds = [10];
        $this->repository->memberUnitIds = [3];
        $this->repository->schedules = [$this->schedule(['is_publik' => 0])];
        $this->repository->scheduleUnits = [
            10 => [['id' => 3, 'nama' => 'Komisi I']],
        ];

        $result = $this->service()->memberAgenda(9, [
            'month' => '2026-07',
            'scope' => 'saya',
        ]);

        $this->assertFalse($this->repository->lastPublicOnly);
        $this->assertSame('2026-07', $this->repository->lastMonth);
        $this->assertSame([10], $this->repository->lastAllowedScheduleIds);
        $this->assertSame('saya', $result['scope']);
        $this->assertFalse($result['data'][0]['is_public']);
        $this->assertTrue($result['data'][0]['is_participant']);
    }

    public function testMemberAgendaRejectsUnknownScopeAsSemua(): void
    {
        $this->repository->schedules = [];

        $result = $this->service()->memberAgenda(9, [
            'date'  => '2026-07-27',
            'scope' => 'admin',
        ]);

        $this->assertSame('semua', $result['scope']);
        $this->assertNull($this->repository->lastAllowedScheduleIds);
    }

    public function testSignageContractRemainsBackwardCompatible(): void
    {
        $this->repository->schedules = [$this->schedule()];
        $this->repository->upcoming = [$this->schedule([
            'id'      => 11,
            'tanggal' => '2026-07-28',
        ])];

        $result = $this->service()->signage('2026-07-27');

        $this->assertSame(['date', 'jadwal', 'upcoming'], array_keys($result));
        $this->assertSame(10, $result['jadwal'][0]['id']);
        $this->assertSame(11, $result['upcoming'][0]['id']);
        $this->assertSame('09:00', $result['jadwal'][0]['waktu_mulai']);
        $this->assertSame('berlangsung', $result['jadwal'][0]['status']);
    }

    public function testFinishedScheduleMovedToFutureIsReportedAsWaiting(): void
    {
        $this->repository->schedules = [$this->schedule([
            'tanggal' => '2026-07-28',
            'status'  => 'selesai',
        ])];

        $result = $this->service()->publicAgenda([
            'date' => '2026-07-28',
        ]);

        $this->assertSame('menunggu', $result['data'][0]['status']);
    }

    private function service(): ScheduleReadService
    {
        return new ScheduleReadService(
            $this->repository,
            fn (): int => $this->now,
        );
    }

    /** @return array<string, mixed> */
    private function schedule(array $overrides = []): array
    {
        return $overrides + [
            'id'               => 10,
            'judul'           => 'Rapat Komisi',
            'keterangan'      => 'Pembahasan agenda',
            'tanggal'         => '2026-07-27',
            'waktu_mulai'     => '09:00:00',
            'waktu_selesai'   => '11:00:00',
            'status'          => 'menunggu',
            'materi_url'      => 'https://example.com/materi',
            'stream_url'      => '',
            'jenis'           => 'Rapat Kerja',
            'is_publik'       => 1,
            'lokasi_lainnya' => '',
            'nama_ruangan'    => 'Ruang Rapat I',
        ];
    }
}

final class FakeScheduleReadRepository implements ScheduleReadRepositoryInterface
{
    /** @var list<array<string, mixed>> */
    public array $schedules = [];
    /** @var list<array<string, mixed>> */
    public array $upcoming = [];
    /** @var list<array{id: int, nama: string}> */
    public array $units = [];
    /** @var list<int> */
    public array $memberUnitIds = [];
    /** @var list<int> */
    public array $memberScheduleIds = [];
    /** @var array<int, list<array{id: int, nama: string}>> */
    public array $scheduleUnits = [];
    public bool $lastPublicOnly = false;
    public ?string $lastDate = null;
    public ?string $lastMonth = null;
    public ?int $lastUnitId = null;
    /** @var list<int>|null */
    public ?array $lastAllowedScheduleIds = null;

    public function findSchedules(
        bool $publicOnly,
        ?string $date,
        ?string $month,
        ?int $unitId,
        ?array $allowedScheduleIds = null,
    ): array {
        $this->lastPublicOnly = $publicOnly;
        $this->lastDate = $date;
        $this->lastMonth = $month;
        $this->lastUnitId = $unitId;
        $this->lastAllowedScheduleIds = $allowedScheduleIds;

        return $this->schedules;
    }

    public function findUpcomingPublic(string $afterDate, int $limit): array
    {
        return array_slice($this->upcoming, 0, $limit);
    }

    public function findActiveUnits(): array
    {
        return $this->units;
    }

    public function findMemberUnitIds(int $memberId): array
    {
        return $this->memberUnitIds;
    }

    public function findScheduleIdsForMember(int $memberId): array
    {
        return $this->memberScheduleIds;
    }

    public function findUnitsByScheduleIds(array $scheduleIds): array
    {
        return array_intersect_key($this->scheduleUnits, array_flip($scheduleIds));
    }
}
