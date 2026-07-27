<?php

namespace App\Libraries\Schedule\Persistence;

use App\Libraries\Schedule\Contracts\ScheduleReadRepositoryInterface;
use CodeIgniter\Database\BaseConnection;

final class DatabaseScheduleReadRepository implements ScheduleReadRepositoryInterface
{
    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private readonly BaseConnection $db;

    public function findSchedules(
        bool $publicOnly,
        ?string $date,
        ?string $month,
        ?int $unitId,
        ?array $allowedScheduleIds = null,
    ): array {
        if ($allowedScheduleIds === []) {
            return [];
        }

        $builder = $this->baseScheduleQuery();
        if ($publicOnly) {
            $builder->where('j.is_publik', 1);
        }
        if ($allowedScheduleIds !== null) {
            $builder->whereIn('j.id', $allowedScheduleIds);
        }
        if ($unitId !== null) {
            $unitScheduleIds = $this->findScheduleIdsForUnit($unitId);
            if ($unitScheduleIds === []) {
                return [];
            }
            $builder->whereIn('j.id', $unitScheduleIds);
        }

        if ($month !== null) {
            $start = $month . '-01';
            $builder
                ->where('j.tanggal >=', $start)
                ->where('j.tanggal <=', date('Y-m-t', strtotime($start)));
        } else {
            $builder->where('j.tanggal', $date);
        }

        return $builder
            ->orderBy('j.tanggal', 'ASC')
            ->orderBy('j.waktu_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function findUpcomingPublic(string $afterDate, int $limit): array
    {
        return $this->baseScheduleQuery()
            ->where('j.is_publik', 1)
            ->where('j.tanggal >', $afterDate)
            ->orderBy('j.tanggal', 'ASC')
            ->orderBy('j.waktu_mulai', 'ASC')
            ->limit(max(1, $limit))
            ->get()
            ->getResultArray();
    }

    public function findActiveUnits(): array
    {
        return $this->db
            ->table('unit_rapat')
            ->select('id, nama')
            ->where('aktif', 1)
            ->orderBy('urutan', 'ASC')
            ->orderBy('nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function findMemberUnitIds(int $memberId): array
    {
        if ($memberId < 1 || ! $this->db->tableExists('anggota_unit_rapat')) {
            return [];
        }

        $rows = $this->db
            ->table('anggota_unit_rapat aur')
            ->select('aur.unit_rapat_id')
            ->join('unit_rapat ur', 'ur.id = aur.unit_rapat_id')
            ->where('aur.anggota_id', $memberId)
            ->where('ur.aktif', 1)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['unit_rapat_id'],
            $rows,
        )));
    }

    public function findScheduleIdsForMember(int $memberId): array
    {
        if ($memberId < 1
            || ! $this->db->tableExists('anggota_unit_rapat')
            || ! $this->db->tableExists('jadwal_unit_rapat')) {
            return [];
        }

        $rows = $this->db
            ->table('jadwal_unit_rapat jur')
            ->distinct()
            ->select('jur.jadwal_id')
            ->join('anggota_unit_rapat aur', 'aur.unit_rapat_id = jur.unit_rapat_id')
            ->join('unit_rapat ur', 'ur.id = jur.unit_rapat_id')
            ->where('aur.anggota_id', $memberId)
            ->where('ur.aktif', 1)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['jadwal_id'],
            $rows,
        )));
    }

    public function findUnitsByScheduleIds(array $scheduleIds): array
    {
        $scheduleIds = array_values(array_unique(array_filter(array_map('intval', $scheduleIds))));
        if ($scheduleIds === [] || ! $this->db->tableExists('jadwal_unit_rapat')) {
            return [];
        }

        $rows = $this->db
            ->table('jadwal_unit_rapat jur')
            ->select('jur.jadwal_id, ur.id, ur.nama')
            ->join('unit_rapat ur', 'ur.id = jur.unit_rapat_id')
            ->whereIn('jur.jadwal_id', $scheduleIds)
            ->where('ur.aktif', 1)
            ->orderBy('ur.urutan', 'ASC')
            ->orderBy('ur.nama', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['jadwal_id']][] = [
                'id'   => (int) $row['id'],
                'nama' => (string) $row['nama'],
            ];
        }

        return $map;
    }

    private function baseScheduleQuery()
    {
        return $this->db
            ->table('jadwal j')
            ->select('
                j.id,
                j.judul,
                j.keterangan,
                j.tanggal,
                j.waktu_mulai,
                j.waktu_selesai,
                j.status,
                j.materi_url,
                j.stream_url,
                j.jenis,
                j.is_publik,
                j.lokasi_lainnya,
                r.name AS nama_ruangan
            ')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left');
    }

    /** @return list<int> */
    private function findScheduleIdsForUnit(int $unitId): array
    {
        if ($unitId < 1 || ! $this->db->tableExists('jadwal_unit_rapat')) {
            return [];
        }

        $rows = $this->db
            ->table('jadwal_unit_rapat')
            ->select('jadwal_id')
            ->where('unit_rapat_id', $unitId)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['jadwal_id'],
            $rows,
        )));
    }
}
