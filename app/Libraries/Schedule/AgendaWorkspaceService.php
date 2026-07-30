<?php

namespace App\Libraries\Schedule;

use App\Models\JadwalBanmusModel;
use App\Models\JadwalUmumModel;
use CodeIgniter\Database\BaseConnection;

final class AgendaWorkspaceService
{
    private const SOURCE_LABELS = [
        'banmus'       => 'Agenda Banmus',
        'jadwal_umum'  => 'Jadwal Umum',
    ];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private readonly BaseConnection $db;

    public function loadMonth(string $month, array $filters = []): array
    {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $agendas = array_merge(
            $this->findGeneral($startDate, $endDate),
            $this->findBanmus($startDate, $endDate),
        );

        $this->attachUnits($agendas);
        $this->markConflicts($agendas);
        $options = $this->buildOptions($agendas);
        $agendas = array_values(array_filter(
            $agendas,
            fn (array $agenda): bool => $this->matchesFilters($agenda, $filters),
        ));

        usort($agendas, static fn (array $left, array $right): int => [
            (string) $left['tanggal'],
            (string) ($left['waktu_mulai'] ?? ''),
            (string) $left['key'],
        ] <=> [
            (string) $right['tanggal'],
            (string) ($right['waktu_mulai'] ?? ''),
            (string) $right['key'],
        ]);

        $counts = ['total' => count($agendas), 'banmus' => 0, 'jadwal_umum' => 0, 'conflicts' => 0];
        foreach ($agendas as $agenda) {
            ++$counts[$agenda['source']];
            if ($agenda['has_conflict']) {
                ++$counts['conflicts'];
            }
        }

        return compact('agendas', 'options', 'counts');
    }

    private function findGeneral(string $startDate, string $endDate): array
    {
        if (! $this->db->tableExists('jadwal_umum')) {
            return [];
        }

        $rows = $this->db->table('jadwal_umum ju')
            ->select('ju.*, r.name AS nama_ruangan')
            ->join('ruangan r', 'r.id = ju.ruangan_id', 'left')
            ->where('ju.tanggal >=', $startDate)
            ->where('ju.tanggal <=', $endDate)
            ->get()
            ->getResultArray();

        return array_map(fn (array $row): array => $this->normalizeAgenda([
            ...$row,
            'source'      => JadwalUmumModel::SOURCE,
            'source_id'   => (int) $row['id'],
            'publikasi'   => (int) $row['is_publik'] === 1 ? 'publik' : 'internal',
            'status'      => JadwalUmumModel::resolveLifecycleStatus(
                (string) $row['tanggal'],
                $row['waktu_mulai'] ?? null,
                $row['waktu_selesai'] ?? null,
            ),
            'lokasi'      => $row['lokasi_lainnya'] ?: $row['nama_ruangan'],
            'edit_url'    => base_url("admin/jadwal-umum/{$row['id']}/edit"),
            'document_id' => null,
        ]), $rows);
    }

    private function findBanmus(string $startDate, string $endDate): array
    {
        if (! $this->db->tableExists('jadwal_banmus') || ! $this->db->tableExists('dokumen_banmus')) {
            return [];
        }

        $rows = $this->db->table('jadwal_banmus jb')
            ->select('
                jb.id, jb.dokumen_banmus_id, jb.agenda AS judul, jb.catatan AS keterangan,
                jb.tanggal, jb.jam_mulai AS waktu_mulai, jb.jam_selesai AS waktu_selesai,
                jb.ruangan_id, jb.lokasi_lainnya, jb.status, jb.publikasi,
                db.is_publik AS dokumen_publik, r.name AS nama_ruangan
            ')
            ->join('dokumen_banmus db', 'db.id = jb.dokumen_banmus_id')
            ->join('ruangan r', 'r.id = jb.ruangan_id', 'left')
            ->where('jb.jenis_agenda', JadwalBanmusModel::TYPE_MEETING)
            ->whereIn('jb.status', JadwalBanmusModel::SCHEDULED_STATUSES)
            ->where('jb.deleted_at', null)
            ->where('jb.tanggal >=', $startDate)
            ->where('jb.tanggal <=', $endDate)
            ->get()
            ->getResultArray();

        return array_map(fn (array $row): array => $this->normalizeAgenda([
            ...$row,
            'source'          => 'banmus',
            'source_id'       => (int) $row['id'],
            'publikasi'       => $row['publikasi'] === 'publik' && (int) $row['dokumen_publik'] === 1
                ? 'publik'
                : 'internal',
            'lokasi'          => $row['lokasi_lainnya'] ?: $row['nama_ruangan'],
            'edit_url'        => base_url("admin/jadwal-banmus/{$row['dokumen_banmus_id']}"),
            'document_id'     => (int) $row['dokumen_banmus_id'],
            'pihak_eksternal' => null,
        ]), $rows);
    }

    private function normalizeAgenda(array $row): array
    {
        $source = (string) $row['source'];
        $location = trim((string) ($row['lokasi'] ?? ''));

        return [
            'key'             => $source . ':' . (int) $row['source_id'],
            'source_id'       => (int) $row['source_id'],
            'source'          => $source,
            'source_label'    => self::SOURCE_LABELS[$source],
            'judul'           => (string) $row['judul'],
            'keterangan'      => $row['keterangan'] ?? null,
            'tanggal'         => (string) $row['tanggal'],
            'waktu_mulai'     => empty($row['waktu_mulai']) ? null : substr((string) $row['waktu_mulai'], 0, 5),
            'waktu_selesai'   => empty($row['waktu_selesai']) ? null : substr((string) $row['waktu_selesai'], 0, 5),
            'ruangan_id'      => empty($row['ruangan_id']) ? null : (int) $row['ruangan_id'],
            'lokasi'          => $location !== '' ? $location : 'Lokasi belum ditentukan',
            'location_key'    => $location !== '' ? $this->normalizeLocation($location) : '',
            'status'          => (string) $row['status'],
            'publikasi'       => (string) $row['publikasi'],
            'is_publik'       => $row['publikasi'] === 'publik',
            'document_id'     => $row['document_id'],
            'edit_url'        => (string) $row['edit_url'],
            'pihak_eksternal' => $row['pihak_eksternal'] ?? null,
            'unit_ids'        => [],
            'units'           => [],
            'has_conflict'    => false,
            'conflicts'       => [],
        ];
    }

    private function attachUnits(array &$agendas): void
    {
        $index = [];
        foreach ($agendas as $position => $agenda) {
            $index[$agenda['key']] = $position;
        }

        $sources = [
            JadwalUmumModel::SOURCE => ['jadwal_umum_unit_rapat', 'jadwal_umum_id'],
            'banmus'                => ['jadwal_banmus_unit_rapat', 'jadwal_banmus_id'],
        ];
        foreach ($sources as $source => [$table, $foreignKey]) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            $rows = $this->db->table($table . ' rel')
                ->select("rel.{$foreignKey} AS source_id, ur.id, ur.nama")
                ->join('unit_rapat ur', 'ur.id = rel.unit_rapat_id')
                ->orderBy('ur.urutan', 'ASC')
                ->orderBy('ur.nama', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($rows as $row) {
                $key = $source . ':' . (int) $row['source_id'];
                if (! isset($index[$key])) {
                    continue;
                }
                $position = $index[$key];
                $agendas[$position]['unit_ids'][] = (int) $row['id'];
                $agendas[$position]['units'][] = (string) $row['nama'];
            }
        }
    }

    private function markConflicts(array &$agendas): void
    {
        $count = count($agendas);
        for ($left = 0; $left < $count; ++$left) {
            for ($right = $left + 1; $right < $count; ++$right) {
                if ($agendas[$left]['source'] === $agendas[$right]['source']
                    || $agendas[$left]['tanggal'] !== $agendas[$right]['tanggal']
                    || $agendas[$left]['location_key'] === ''
                    || $agendas[$left]['location_key'] !== $agendas[$right]['location_key']
                    || $agendas[$left]['waktu_mulai'] === null
                    || $agendas[$right]['waktu_mulai'] === null
                    || $agendas[$left]['waktu_selesai'] === null
                    || $agendas[$right]['waktu_selesai'] === null
                    || ! $this->timesOverlap($agendas[$left], $agendas[$right])) {
                    continue;
                }

                $agendas[$left]['has_conflict'] = true;
                $agendas[$right]['has_conflict'] = true;
                $agendas[$left]['conflicts'][] = [
                    'key' => $agendas[$right]['key'],
                    'label' => $agendas[$right]['source_label'] . ': ' . $agendas[$right]['judul'],
                ];
                $agendas[$right]['conflicts'][] = [
                    'key' => $agendas[$left]['key'],
                    'label' => $agendas[$left]['source_label'] . ': ' . $agendas[$left]['judul'],
                ];
            }
        }
    }

    private function matchesFilters(array $agenda, array $filters): bool
    {
        foreach (['source', 'status', 'publikasi'] as $key) {
            if (($filters[$key] ?? '') !== '' && $agenda[$key] !== $filters[$key]) {
                return false;
            }
        }
        if (($filters['unit'] ?? '') !== '' && ! in_array((int) $filters['unit'], $agenda['unit_ids'], true)) {
            return false;
        }

        return ($filters['lokasi'] ?? '') === '' || $agenda['location_key'] === $filters['lokasi'];
    }

    private function buildOptions(array $agendas): array
    {
        $options = [
            'sources' => [],
            'units' => [],
            'locations' => [],
            'statuses' => [],
            'publications' => ['internal' => 'Internal', 'publik' => 'Publik'],
        ];
        foreach ($agendas as $agenda) {
            $options['sources'][$agenda['source']] = $agenda['source_label'];
            if ($agenda['location_key'] !== '') {
                $options['locations'][$agenda['location_key']] = $agenda['lokasi'];
            }
            $options['statuses'][$agenda['status']] = ucfirst($agenda['status']);
            foreach ($agenda['unit_ids'] as $index => $unitId) {
                $options['units'][(string) $unitId] = $agenda['units'][$index];
            }
        }
        foreach (['sources', 'units', 'locations', 'statuses'] as $key) {
            asort($options[$key], SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $options;
    }

    private function timesOverlap(array $left, array $right): bool
    {
        return $left['waktu_mulai'] < $right['waktu_selesai']
            && $left['waktu_selesai'] > $right['waktu_mulai'];
    }

    private function normalizeLocation(string $location): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($location))) ?? '';
    }
}
