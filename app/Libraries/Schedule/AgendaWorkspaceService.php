<?php

namespace App\Libraries\Schedule;

use App\Models\JadwalBanmusModel;
use App\Models\JadwalModel;
use CodeIgniter\Database\BaseConnection;

final class AgendaWorkspaceService
{
    private const SOURCE_LABELS = [
        'banmus'              => 'Agenda Banmus',
        'insidental_internal' => 'Agenda Insidental',
        'agenda_eksternal'    => 'Agenda Eksternal',
    ];

    private const TYPE_LABELS = [
        'banmus'           => 'Agenda Banmus',
        'insidental'       => 'Agenda Insidental',
        'audiensi'         => 'Audiensi / Penerimaan Aspirasi',
        'audiensi_publik'  => 'Audiensi / Penerimaan Aspirasi',
        'demonstrasi'      => 'Aksi Unjuk Rasa / Demonstrasi',
        'kunjungan'        => 'Kunjungan Tamu atau Instansi',
        'undangan'         => 'Undangan / Agenda Luar Gedung',
        'kegiatan_sosial'  => 'Kegiatan Sosial dan Publik',
        'lainnya'          => 'Lainnya',
    ];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private readonly BaseConnection $db;

    /**
     * @param array<string, string> $filters
     * @return array{
     *     agendas: list<array<string, mixed>>,
     *     options: array<string, array<string, string>>,
     *     counts: array<string, int>
     * }
     */
    public function loadMonth(string $month, array $filters = []): array
    {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $agendas = array_merge(
            $this->findInsidental($startDate, $endDate),
            $this->findBanmus($startDate, $endDate),
            $this->findExternal($startDate, $endDate),
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
            (string) $left['waktu_mulai'],
            (string) $left['key'],
        ] <=> [
            (string) $right['tanggal'],
            (string) $right['waktu_mulai'],
            (string) $right['key'],
        ]);

        $counts = [
            'total'               => count($agendas),
            'banmus'              => 0,
            'insidental_internal' => 0,
            'agenda_eksternal'    => 0,
            'conflicts'           => 0,
        ];
        foreach ($agendas as $agenda) {
            ++$counts[$agenda['source']];
            if ($agenda['has_conflict']) {
                ++$counts['conflicts'];
            }
        }

        return compact('agendas', 'options', 'counts');
    }

    /** @return list<array<string, mixed>> */
    private function findInsidental(string $startDate, string $endDate): array
    {
        if (! $this->db->tableExists('jadwal')) {
            return [];
        }

        $builder = $this->db->table('jadwal j')
            ->select('
                j.id,
                j.judul,
                j.keterangan,
                j.tanggal,
                j.waktu_mulai,
                j.waktu_selesai,
                j.ruangan_id,
                j.lokasi_lainnya,
                j.status,
                j.is_publik,
                r.name AS nama_ruangan
            ')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.tanggal >=', $startDate)
            ->where('j.tanggal <=', $endDate);
        if ($this->db->fieldExists('jenis', 'jadwal')) {
            $builder->where('j.jenis', 'insidental');
        }

        return array_map(fn (array $row): array => $this->normalizeAgenda([
            ...$row,
            'source'        => 'insidental_internal',
            'source_id'     => (int) $row['id'],
            'jenis'         => 'insidental',
            'lingkup'       => 'internal',
            'publikasi'     => (int) $row['is_publik'] === 1 ? 'publik' : 'internal',
            'lokasi'        => $row['nama_ruangan'] ?: $row['lokasi_lainnya'],
            'edit_url'      => base_url("admin/jadwal/{$row['id']}/edit"),
            'document_id'   => null,
        ]), $builder->get()->getResultArray());
    }

    /** @return list<array<string, mixed>> */
    private function findBanmus(string $startDate, string $endDate): array
    {
        if (! $this->db->tableExists('jadwal_banmus')
            || ! $this->db->tableExists('dokumen_banmus')) {
            return [];
        }

        $rows = $this->db->table('jadwal_banmus jb')
            ->select('
                jb.id,
                jb.dokumen_banmus_id,
                jb.agenda AS judul,
                jb.catatan AS keterangan,
                jb.tanggal,
                jb.jam_mulai AS waktu_mulai,
                jb.jam_selesai AS waktu_selesai,
                jb.ruangan_id,
                jb.lokasi_lainnya,
                jb.status,
                jb.publikasi,
                db.is_publik AS dokumen_publik,
                r.name AS nama_ruangan
            ')
            ->join('dokumen_banmus db', 'db.id = jb.dokumen_banmus_id')
            ->join('ruangan r', 'r.id = jb.ruangan_id', 'left')
            ->where('jb.jenis_agenda', JadwalBanmusModel::TYPE_MEETING)
            ->whereIn('jb.status', JadwalBanmusModel::SCHEDULED_STATUSES)
            ->where('jb.deleted_at', null)
            ->where('jb.tanggal >=', $startDate)
            ->where('jb.tanggal <=', $endDate)
            ->where('jb.jam_mulai IS NOT NULL', null, false)
            ->where('jb.jam_selesai IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        return array_map(fn (array $row): array => $this->normalizeAgenda([
            ...$row,
            'source'      => 'banmus',
            'source_id'   => (int) $row['id'],
            'jenis'       => 'banmus',
            'lingkup'     => 'internal',
            'publikasi'   => $row['publikasi'] === 'publik' && (int) $row['dokumen_publik'] === 1
                ? 'publik'
                : 'internal',
            'lokasi'      => $row['nama_ruangan'] ?: $row['lokasi_lainnya'],
            'edit_url'    => base_url("admin/jadwal-banmus/{$row['dokumen_banmus_id']}"),
            'document_id' => (int) $row['dokumen_banmus_id'],
        ]), $rows);
    }

    /** @return list<array<string, mixed>> */
    private function findExternal(string $startDate, string $endDate): array
    {
        if (! $this->db->tableExists('agenda_umum')) {
            return [];
        }

        $rows = $this->db->table('agenda_umum')
            ->select('
                id,
                judul,
                keterangan,
                kategori,
                pihak_eksternal,
                tanggal,
                waktu_mulai,
                waktu_selesai,
                lokasi,
                is_publik
            ')
            ->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate)
            ->get()
            ->getResultArray();

        return array_map(fn (array $row): array => $this->normalizeAgenda([
            ...$row,
            'source'      => 'agenda_eksternal',
            'source_id'   => (int) $row['id'],
            'jenis'       => (string) $row['kategori'],
            'lingkup'     => 'eksternal',
            'publikasi'   => (int) $row['is_publik'] === 1 ? 'publik' : 'internal',
            'status'      => $this->resolveExternalStatus($row),
            'ruangan_id'  => null,
            'edit_url'    => base_url("admin/agenda-umum/{$row['id']}/edit"),
            'document_id' => null,
        ]), $rows);
    }

    /** @param array<string, mixed> $row */
    private function normalizeAgenda(array $row): array
    {
        $source = (string) $row['source'];
        $type = (string) $row['jenis'];
        $location = trim((string) ($row['lokasi'] ?? ''));

        return [
            'key'             => $source . ':' . (int) $row['source_id'],
            'source_id'       => (int) $row['source_id'],
            'source'          => $source,
            'source_label'    => self::SOURCE_LABELS[$source],
            'jenis'           => $type,
            'jenis_label'     => self::TYPE_LABELS[$type] ?? ucfirst(str_replace('_', ' ', $type)),
            'lingkup'         => (string) $row['lingkup'],
            'judul'           => (string) $row['judul'],
            'keterangan'      => $row['keterangan'] ?? null,
            'tanggal'         => (string) $row['tanggal'],
            'waktu_mulai'     => substr((string) $row['waktu_mulai'], 0, 5),
            'waktu_selesai'   => empty($row['waktu_selesai'])
                ? null
                : substr((string) $row['waktu_selesai'], 0, 5),
            'ruangan_id'      => isset($row['ruangan_id']) ? (int) $row['ruangan_id'] : null,
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

    /**
     * @param list<array<string, mixed>> $agendas
     */
    private function attachUnits(array &$agendas): void
    {
        $index = [];
        foreach ($agendas as $position => $agenda) {
            $index[$agenda['key']] = $position;
        }

        $sources = [
            'insidental_internal' => ['jadwal_unit_rapat', 'jadwal_id'],
            'banmus'              => ['jadwal_banmus_unit_rapat', 'jadwal_banmus_id'],
        ];
        foreach ($sources as $source => [$table, $foreignKey]) {
            if (! $this->db->tableExists($table) || ! $this->db->tableExists('unit_rapat')) {
                continue;
            }
            $rows = $this->db->table($table . ' rel')
                ->select("rel.{$foreignKey} AS source_id, ur.id, ur.nama")
                ->join('unit_rapat ur', "ur.id = rel.unit_rapat_id")
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

    /**
     * @param list<array<string, mixed>> $agendas
     */
    private function markConflicts(array &$agendas): void
    {
        $count = count($agendas);
        for ($left = 0; $left < $count; ++$left) {
            for ($right = $left + 1; $right < $count; ++$right) {
                if ($agendas[$left]['source'] === $agendas[$right]['source']
                    || $agendas[$left]['tanggal'] !== $agendas[$right]['tanggal']
                    || $agendas[$left]['location_key'] === ''
                    || $agendas[$left]['location_key'] !== $agendas[$right]['location_key']
                    || $agendas[$left]['waktu_selesai'] === null
                    || $agendas[$right]['waktu_selesai'] === null
                    || ! $this->timesOverlap($agendas[$left], $agendas[$right])) {
                    continue;
                }

                $agendas[$left]['has_conflict'] = true;
                $agendas[$right]['has_conflict'] = true;
                $agendas[$left]['conflicts'][] = [
                    'key'   => $agendas[$right]['key'],
                    'label' => $agendas[$right]['source_label'] . ': ' . $agendas[$right]['judul'],
                ];
                $agendas[$right]['conflicts'][] = [
                    'key'   => $agendas[$left]['key'],
                    'label' => $agendas[$left]['source_label'] . ': ' . $agendas[$left]['judul'],
                ];
            }
        }
    }

    /** @param array<string, mixed> $agenda */
    private function matchesFilters(array $agenda, array $filters): bool
    {
        $exact = ['source', 'jenis', 'lingkup', 'status', 'publikasi'];
        foreach ($exact as $key) {
            if (($filters[$key] ?? '') !== '' && $agenda[$key] !== $filters[$key]) {
                return false;
            }
        }

        if (($filters['unit'] ?? '') !== ''
            && ! in_array((int) $filters['unit'], $agenda['unit_ids'], true)) {
            return false;
        }

        return ($filters['lokasi'] ?? '') === ''
            || $agenda['location_key'] === $filters['lokasi'];
    }

    /** @param list<array<string, mixed>> $agendas */
    private function buildOptions(array $agendas): array
    {
        $options = [
            'sources'      => [],
            'types'        => [],
            'scopes'       => [],
            'units'        => [],
            'locations'    => [],
            'statuses'     => [],
            'publications' => ['internal' => 'Internal', 'publik' => 'Publik'],
        ];

        foreach ($agendas as $agenda) {
            $options['sources'][$agenda['source']] = $agenda['source_label'];
            $options['types'][$agenda['jenis']] = $agenda['jenis_label'];
            $options['scopes'][$agenda['lingkup']] = ucfirst($agenda['lingkup']);
            if ($agenda['location_key'] !== '') {
                $options['locations'][$agenda['location_key']] = $agenda['lokasi'];
            }
            $options['statuses'][$agenda['status']] = ucfirst($agenda['status']);
            foreach ($agenda['unit_ids'] as $index => $unitId) {
                $options['units'][(string) $unitId] = $agenda['units'][$index];
            }
        }
        foreach (['sources', 'types', 'scopes', 'units', 'locations', 'statuses'] as $key) {
            asort($options[$key], SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $options;
    }

    /** @param array<string, mixed> $row */
    private function resolveExternalStatus(array $row): string
    {
        $date = (string) $row['tanggal'];
        $start = substr((string) $row['waktu_mulai'], 0, 8);
        $end = empty($row['waktu_selesai'])
            ? null
            : substr((string) $row['waktu_selesai'], 0, 8);
        if ($end !== null) {
            return JadwalModel::resolveLifecycleStatus($date, $start, $end);
        }

        $today = date('Y-m-d');
        if ($date < $today) {
            return 'selesai';
        }
        if ($date > $today) {
            return 'menunggu';
        }

        $startTimestamp = strtotime($date . ' ' . $start);
        if ($startTimestamp !== false && $startTimestamp <= time()) {
            return 'berlangsung';
        }
        if ($startTimestamp !== false && $startTimestamp - time() <= 1800) {
            return 'persiapan';
        }

        return 'menunggu';
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function timesOverlap(array $left, array $right): bool
    {
        return $left['waktu_mulai'] < $right['waktu_selesai']
            && $left['waktu_selesai'] > $right['waktu_mulai'];
    }

    private function normalizeLocation(string $location): string
    {
        $normalized = mb_strtolower(trim($location));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized ?? '';
    }
}
