<?php

namespace App\Libraries\Schedule\Persistence;

use App\Libraries\Schedule\Contracts\ScheduleReadRepositoryInterface;
use App\Models\JadwalBanmusModel;
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

        $rows = [];
        if ($this->db->tableExists('jadwal')) {
            $builder = $this->baseScheduleQuery();
            if ($publicOnly) {
                $builder->where('j.is_publik', 1);
            }
            $this->applyDateFilter($builder, 'j.tanggal', $date, $month);
            $rows = $builder->get()->getResultArray();
        }

        if ($this->db->tableExists('jadwal_banmus')) {
            $builder = $this->baseBanmusQuery()
                ->whereIn('jb.status', JadwalBanmusModel::SCHEDULED_STATUSES)
                ->where('jb.deleted_at', null)
                ->where('jb.tanggal IS NOT NULL', null, false)
                ->where('jb.jam_mulai IS NOT NULL', null, false)
                ->where('jb.jam_selesai IS NOT NULL', null, false);
            if ($publicOnly) {
                $builder
                    ->where('jb.publikasi', 'publik')
                    ->where('db.is_publik', 1);
            }
            $this->applyDateFilter($builder, 'jb.tanggal', $date, $month);
            $rows = array_merge($rows, $builder->get()->getResultArray());
        }

        if ($allowedScheduleIds !== null) {
            $allowedMap = array_fill_keys(array_map('intval', $allowedScheduleIds), true);
            $rows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => isset($allowedMap[(int) $row['id']]),
            ));
        }
        if ($unitId !== null) {
            $unitScheduleIds = $this->findScheduleIdsForUnit($unitId);
            if ($unitScheduleIds === []) {
                return [];
            }
            $unitMap = array_fill_keys($unitScheduleIds, true);
            $rows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => isset($unitMap[(int) $row['id']]),
            ));
        }

        usort($rows, static fn (array $left, array $right): int => [
            (string) $left['tanggal'],
            (string) $left['waktu_mulai'],
            (int) $left['id'],
        ] <=> [
            (string) $right['tanggal'],
            (string) $right['waktu_mulai'],
            (int) $right['id'],
        ]);

        return $rows;
    }

    public function findUpcomingPublic(string $afterDate, int $limit): array
    {
        $limit = max(1, $limit);
        $rows = [];

        if ($this->db->tableExists('jadwal')) {
            $rows = $this->baseScheduleQuery()
                ->where('j.is_publik', 1)
                ->where('j.tanggal >', $afterDate)
                ->orderBy('j.tanggal', 'ASC')
                ->orderBy('j.waktu_mulai', 'ASC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        }
        if ($this->db->tableExists('jadwal_banmus')) {
            $banmusRows = $this->baseBanmusQuery()
                ->where('jb.publikasi', 'publik')
                ->where('db.is_publik', 1)
                ->whereIn('jb.status', JadwalBanmusModel::SCHEDULED_STATUSES)
                ->where('jb.deleted_at', null)
                ->where('jb.tanggal >', $afterDate)
                ->where('jb.jam_mulai IS NOT NULL', null, false)
                ->where('jb.jam_selesai IS NOT NULL', null, false)
                ->orderBy('jb.tanggal', 'ASC')
                ->orderBy('jb.jam_mulai', 'ASC')
                ->limit($limit)
                ->get()
                ->getResultArray();
            $rows = array_merge($rows, $banmusRows);
        }

        usort($rows, static fn (array $left, array $right): int => [
            (string) $left['tanggal'],
            (string) $left['waktu_mulai'],
        ] <=> [
            (string) $right['tanggal'],
            (string) $right['waktu_mulai'],
        ]);

        return array_slice($rows, 0, $limit);
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

        $rows = $this->db->table('jadwal_unit_rapat jur')
            ->distinct()
            ->select('jur.jadwal_id')
            ->join('anggota_unit_rapat aur', 'aur.unit_rapat_id = jur.unit_rapat_id')
            ->join('unit_rapat ur', 'ur.id = jur.unit_rapat_id')
            ->where('aur.anggota_id', $memberId)
            ->where('ur.aktif', 1)
            ->get()->getResultArray();
        $ids = array_map(
            static fn (array $row): int => (int) $row['jadwal_id'],
            $rows,
        );

        if ($this->db->tableExists('jadwal_banmus_unit_rapat')) {
            $banmusRows = $this->db->table('jadwal_banmus_unit_rapat jbur')
                ->distinct()
                ->select('jbur.jadwal_banmus_id')
                ->join('anggota_unit_rapat aur', 'aur.unit_rapat_id = jbur.unit_rapat_id')
                ->join('unit_rapat ur', 'ur.id = jbur.unit_rapat_id')
                ->where('aur.anggota_id', $memberId)
                ->where('ur.aktif', 1)
                ->get()->getResultArray();
            $ids = array_merge($ids, array_map(
                static fn (array $row): int => -(int) $row['jadwal_banmus_id'],
                $banmusRows,
            ));
        }

        return array_values(array_unique($ids));
    }

    public function findUnitsByScheduleIds(array $scheduleIds): array
    {
        $scheduleIds = array_values(array_unique(array_filter(array_map('intval', $scheduleIds))));
        if ($scheduleIds === []) {
            return [];
        }

        $map = [];
        $regularIds = array_values(array_filter($scheduleIds, static fn (int $id): bool => $id > 0));
        if ($regularIds !== [] && $this->db->tableExists('jadwal_unit_rapat')) {
            $rows = $this->db->table('jadwal_unit_rapat jur')
                ->select('jur.jadwal_id, ur.id, ur.nama')
                ->join('unit_rapat ur', 'ur.id = jur.unit_rapat_id')
                ->whereIn('jur.jadwal_id', $regularIds)
                ->where('ur.aktif', 1)
                ->orderBy('ur.urutan', 'ASC')
                ->orderBy('ur.nama', 'ASC')
                ->get()->getResultArray();
            foreach ($rows as $row) {
                $map[(int) $row['jadwal_id']][] = [
                    'id' => (int) $row['id'],
                    'nama' => (string) $row['nama'],
                ];
            }
        }

        $banmusIds = array_map(
            static fn (int $id): int => abs($id),
            array_values(array_filter($scheduleIds, static fn (int $id): bool => $id < 0)),
        );
        if ($banmusIds !== [] && $this->db->tableExists('jadwal_banmus_unit_rapat')) {
            $rows = $this->db->table('jadwal_banmus_unit_rapat jbur')
                ->select('jbur.jadwal_banmus_id, ur.id, ur.nama')
                ->join('unit_rapat ur', 'ur.id = jbur.unit_rapat_id')
                ->whereIn('jbur.jadwal_banmus_id', $banmusIds)
                ->where('ur.aktif', 1)
                ->orderBy('ur.urutan', 'ASC')
                ->orderBy('ur.nama', 'ASC')
                ->get()->getResultArray();
            foreach ($rows as $row) {
                $map[-(int) $row['jadwal_banmus_id']][] = [
                    'id' => (int) $row['id'],
                    'nama' => (string) $row['nama'],
                ];
            }
        }

        return $map;
    }

    private function baseScheduleQuery()
    {
        return $this->db
            ->table('jadwal j')
            ->select('
                j.id,
                j.id AS source_id,
                "insidental_internal" AS source,
                "internal" AS lingkup,
                NULL AS dokumen_banmus_id,
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
            ', false)
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left');
    }

    private function baseBanmusQuery()
    {
        return $this->db
            ->table('jadwal_banmus jb')
            ->select('
                -jb.id AS id,
                jb.id AS source_id,
                "banmus" AS source,
                "internal" AS lingkup,
                jb.dokumen_banmus_id,
                jb.agenda AS judul,
                jb.catatan AS keterangan,
                jb.tanggal,
                jb.jam_mulai AS waktu_mulai,
                jb.jam_selesai AS waktu_selesai,
                jb.status,
                jb.materi_url,
                jb.stream_url,
                "reguler" AS jenis,
                CASE WHEN jb.publikasi = "publik" THEN 1 ELSE 0 END AS is_publik,
                jb.lokasi_lainnya,
                r.name AS nama_ruangan
            ', false)
            ->join('dokumen_banmus db', 'db.id = jb.dokumen_banmus_id')
            ->join('ruangan r', 'r.id = jb.ruangan_id', 'left');
    }

    /** @return list<int> */
    private function findScheduleIdsForUnit(int $unitId): array
    {
        if ($unitId < 1) {
            return [];
        }

        $ids = [];
        if ($this->db->tableExists('jadwal_unit_rapat')) {
            $rows = $this->db->table('jadwal_unit_rapat')
                ->select('jadwal_id')
                ->where('unit_rapat_id', $unitId)
                ->get()->getResultArray();
            $ids = array_map(static fn (array $row): int => (int) $row['jadwal_id'], $rows);
        }
        if ($this->db->tableExists('jadwal_banmus_unit_rapat')) {
            $rows = $this->db->table('jadwal_banmus_unit_rapat')
                ->select('jadwal_banmus_id')
                ->where('unit_rapat_id', $unitId)
                ->get()->getResultArray();
            $ids = array_merge($ids, array_map(
                static fn (array $row): int => -(int) $row['jadwal_banmus_id'],
                $rows,
            ));
        }

        return array_values(array_unique($ids));
    }

    private function applyDateFilter($builder, string $column, ?string $date, ?string $month): void
    {
        if ($month !== null) {
            $start = $month . '-01';
            $builder
                ->where($column . ' >=', $start)
                ->where($column . ' <=', date('Y-m-t', strtotime($start)));

            return;
        }

        $builder->where($column, $date);
    }
}
