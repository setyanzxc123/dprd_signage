<?php

namespace App\Libraries\Schedule\Persistence;

use App\Libraries\Schedule\Contracts\ScheduleReadRepositoryInterface;
use App\Models\JadwalBanmusModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class DatabaseScheduleReadRepository implements ScheduleReadRepositoryInterface
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function findSchedules(
        bool $publicOnly,
        ?string $date,
        ?string $month,
        ?int $unitId,
        ?array $allowedScheduleIds = null
    ): array {
        $general = $this->db->tableExists('jadwal_umum')
            ? $this->generalScheduleQuery($publicOnly, $date, $month, $unitId, $allowedScheduleIds)->get()->getResultArray()
            : [];
        $banmus = $this->db->tableExists('jadwal_banmus') && $this->db->tableExists('dokumen_banmus')
            ? $this->banmusScheduleQuery($publicOnly, $date, $month, $unitId, $allowedScheduleIds)->get()->getResultArray()
            : [];

        return $this->sortSchedules(array_merge($general, $banmus));
    }

    public function findUpcomingPublic(string $afterDate, int $limit): array
    {
        $rows = $this->findSchedules(true, null, null, null);
        $rows = array_values(array_filter($rows, static fn (array $row): bool => ($row['tanggal'] ?? '') > $afterDate));

        return array_slice($rows, 0, $limit);
    }

    public function findActiveUnits(): array
    {
        return $this->db->table('unit_rapat')
            ->select('id, nama')
            ->where('aktif', 1)
            ->orderBy('urutan', 'ASC')
            ->orderBy('nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function findMemberUnitIds(int $memberId): array
    {
        if (! $this->db->tableExists('anggota_unit_rapat')) {
            return [];
        }

        return array_map(
            'intval',
            array_column(
                $this->db->table('anggota_unit_rapat')
                    ->select('unit_rapat_id')
                    ->where('anggota_id', $memberId)
                    ->get()
                    ->getResultArray(),
                'unit_rapat_id'
            )
        );
    }

    public function findScheduleIdsForMember(int $memberId): array
    {
        $unitIds = $this->findMemberUnitIds($memberId);
        if ($unitIds === []) {
            return [];
        }

        $generalIds = $this->db->tableExists('jadwal_umum_unit_rapat')
            ? array_map(
                'intval',
                array_column(
                    $this->db->table('jadwal_umum_unit_rapat')
                    ->distinct()
                    ->select('jadwal_umum_id')
                    ->whereIn('unit_rapat_id', $unitIds)
                    ->get()
                    ->getResultArray(),
                    'jadwal_umum_id'
                )
            )
            : [];

        $banmusIds = $this->db->tableExists('jadwal_banmus_unit_rapat')
            ? array_map(
                static fn ($id): int => -abs((int) $id),
                array_column(
                    $this->db->table('jadwal_banmus_unit_rapat')
                    ->distinct()
                    ->select('jadwal_banmus_id')
                    ->whereIn('unit_rapat_id', $unitIds)
                    ->get()
                    ->getResultArray(),
                    'jadwal_banmus_id'
                )
            )
            : [];

        return array_values(array_unique(array_merge($generalIds, $banmusIds)));
    }

    public function findUnitsByScheduleIds(array $scheduleIds): array
    {
        if ($scheduleIds === []) {
            return [];
        }

        $generalIds = array_values(array_filter(array_map('intval', $scheduleIds), static fn (int $id): bool => $id > 0));
        $banmusIds = array_values(array_map(
            static fn (int $id): int => abs($id),
            array_filter(array_map('intval', $scheduleIds), static fn (int $id): bool => $id < 0)
        ));
        $result = [];

        if ($generalIds !== [] && $this->db->tableExists('jadwal_umum_unit_rapat')) {
            $rows = $this->db->table('jadwal_umum_unit_rapat juur')
                ->select('juur.jadwal_umum_id AS schedule_id, ur.id, ur.nama')
                ->join('unit_rapat ur', 'ur.id = juur.unit_rapat_id')
                ->whereIn('juur.jadwal_umum_id', $generalIds)
                ->orderBy('ur.urutan', 'ASC')
                ->get()
                ->getResultArray();
            $this->groupUnits($result, $rows, false);
        }

        if ($banmusIds !== [] && $this->db->tableExists('jadwal_banmus_unit_rapat')) {
            $rows = $this->db->table('jadwal_banmus_unit_rapat jbur')
                ->select('jbur.jadwal_banmus_id AS schedule_id, ur.id, ur.nama')
                ->join('unit_rapat ur', 'ur.id = jbur.unit_rapat_id')
                ->whereIn('jbur.jadwal_banmus_id', $banmusIds)
                ->orderBy('ur.urutan', 'ASC')
                ->get()
                ->getResultArray();
            $this->groupUnits($result, $rows, true);
        }

        return $result;
    }

    private function generalScheduleQuery(
        bool $publicOnly,
        ?string $date,
        ?string $month,
        ?int $unitId,
        ?array $allowedScheduleIds
    ): BaseBuilder {
        $builder = $this->db->table('jadwal_umum ju')
            ->select(
                "ju.id, ju.id AS source_id, 'jadwal_umum' AS source, NULL AS lingkup, "
                . 'NULL AS dokumen_banmus_id, ju.judul, ju.keterangan, ju.tanggal, '
                . "ju.waktu_mulai, ju.waktu_selesai, 'menunggu' AS status, "
                . 'NULL AS materi_url, NULL AS materi_akses, NULL AS stream_url, NULL AS stream_akses, '
                . "'jadwal_umum' AS jenis, ju.is_publik, ju.lokasi_lainnya, "
                . 'r.name AS nama_ruangan, ju.pihak_eksternal',
                false,
            )
            ->join('ruangan r', 'r.id = ju.ruangan_id', 'left');

        if ($publicOnly) {
            $builder->where('ju.is_publik', 1);
        }
        $this->applyDateFilters($builder, 'ju.tanggal', $date, $month);

        if ($unitId !== null) {
            $builder->join('jadwal_umum_unit_rapat juur_filter', 'juur_filter.jadwal_umum_id = ju.id')
                ->where('juur_filter.unit_rapat_id', $unitId);
        }
        if ($allowedScheduleIds !== null) {
            $ids = array_values(array_filter(array_map('intval', $allowedScheduleIds), static fn (int $id): bool => $id > 0));
            $ids === [] ? $builder->where('1 = 0', null, false) : $builder->whereIn('ju.id', $ids);
        }

        return $builder->groupBy('ju.id');
    }

    private function banmusScheduleQuery(
        bool $publicOnly,
        ?string $date,
        ?string $month,
        ?int $unitId,
        ?array $allowedScheduleIds
    ): BaseBuilder {
        $builder = $this->db->table('jadwal_banmus jb')
            ->select(
                "-jb.id AS id, jb.id AS source_id, 'banmus' AS source, NULL AS lingkup, "
                . 'jb.dokumen_banmus_id, jb.agenda AS judul, jb.catatan AS keterangan, '
                . 'jb.tanggal, jb.jam_mulai AS waktu_mulai, jb.jam_selesai AS waktu_selesai, jb.status, '
                . 'jb.materi_url, jb.materi_akses, jb.stream_url, jb.stream_akses, '
                . "'rapat' AS jenis, CASE WHEN jb.publikasi = 'publik' AND db.is_publik = 1 THEN 1 ELSE 0 END AS is_publik, "
                . 'jb.lokasi_lainnya, r.name AS nama_ruangan, NULL AS pihak_eksternal',
                false,
            )
            ->join('dokumen_banmus db', 'db.id = jb.dokumen_banmus_id')
            ->join('ruangan r', 'r.id = jb.ruangan_id', 'left')
            ->where('jb.jenis_agenda', JadwalBanmusModel::TYPE_MEETING)
            ->whereIn('jb.status', JadwalBanmusModel::SCHEDULED_STATUSES)
            ->where('jb.deleted_at', null);

        if ($publicOnly) {
            $builder->where('jb.publikasi', 'publik')->where('db.is_publik', 1);
        }
        $this->applyDateFilters($builder, 'jb.tanggal', $date, $month);

        if ($unitId !== null) {
            $builder->join('jadwal_banmus_unit_rapat jbur_filter', 'jbur_filter.jadwal_banmus_id = jb.id')
                ->where('jbur_filter.unit_rapat_id', $unitId);
        }
        if ($allowedScheduleIds !== null) {
            $ids = array_values(array_map(
                static fn (int $id): int => abs($id),
                array_filter(array_map('intval', $allowedScheduleIds), static fn (int $id): bool => $id < 0)
            ));
            $ids === [] ? $builder->where('1 = 0', null, false) : $builder->whereIn('jb.id', $ids);
        }

        return $builder->groupBy('jb.id');
    }

    private function applyDateFilters(BaseBuilder $builder, string $field, ?string $date, ?string $month): void
    {
        if ($date !== null && $date !== '') {
            $builder->where($field, $date);
        }
        if ($month !== null && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            $builder->where($field . ' >=', $month . '-01')
                ->where($field . ' <', date('Y-m-d', strtotime($month . '-01 +1 month')));
        }
    }

    private function groupUnits(array &$result, array $rows, bool $negative): void
    {
        foreach ($rows as $row) {
            $scheduleId = (int) $row['schedule_id'];
            $key = $negative ? -abs($scheduleId) : $scheduleId;
            unset($row['schedule_id']);
            $result[$key][] = $row;
        }
    }

    private function sortSchedules(array $rows): array
    {
        usort($rows, static function (array $left, array $right): int {
            $leftKey = ($left['tanggal'] ?? '') . ' ' . ($left['waktu_mulai'] ?? '00:00:00');
            $rightKey = ($right['tanggal'] ?? '') . ' ' . ($right['waktu_mulai'] ?? '00:00:00');

            return $leftKey <=> $rightKey ?: ((int) $left['id'] <=> (int) $right['id']);
        });

        return $rows;
    }
}
