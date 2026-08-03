<?php

namespace App\Models;

use CodeIgniter\Model;

class JadwalBanmusModel extends Model
{
    public const TYPE_MEETING = 'rapat';
    public const TYPE_NON_MEETING = 'non_rapat';
    public const AGENDA_TYPES = [self::TYPE_MEETING, self::TYPE_NON_MEETING];
    public const SCHEDULED_STATUSES = ['menunggu', 'persiapan', 'berlangsung', 'selesai'];

    protected $table         = 'jadwal_banmus';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'dokumen_banmus_id',
        'agenda',
        'jenis_agenda',
        'periode_label',
        'tanggal_mulai',
        'tanggal_selesai',
        'teks_tanggal_asli',
        'bulan_mulai',
        'bulan_selesai',
        'jumlah_pelaksanaan_rencana',
        'halaman_sumber',
        'urutan',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'ruangan_id',
        'lokasi_lainnya',
        'publikasi',
        'materi_url',
        'materi_akses',
        'stream_url',
        'stream_akses',
        'undangan_file',
        'undangan_nama_asli',
        'status',
        'catatan',
    ];

    /**
     * Perbarui status waktu untuk agenda dengan data pelaksanaan lengkap.
     * Proyeksi serta status pengecualian ditunda dan dibatalkan tidak ditimpa.
     */
    public function autoUpdateStatuses(?int $documentId = null, ?int $now = null): void
    {
        if (! $this->db->tableExists($this->table)) {
            return;
        }

        $builder = $this->db->table($this->table)
            ->select('id, tanggal, jam_mulai, jam_selesai, status')
            ->whereIn('status', self::SCHEDULED_STATUSES)
            ->where('tanggal IS NOT NULL', null, false)
            ->where('jam_mulai IS NOT NULL', null, false)
            ->where('jam_selesai IS NOT NULL', null, false)
            ->where('deleted_at', null);
        if ($documentId !== null) {
            $builder->where('dokumen_banmus_id', $documentId);
        }

        $now ??= time();
        foreach ($builder->get()->getResultArray() as $item) {
            $status = self::resolveLifecycleStatus(
                true,
                (string) $item['tanggal'],
                (string) $item['jam_mulai'],
                (string) $item['jam_selesai'],
                $now,
            );
            if ($status !== $item['status']) {
                $this->update((int) $item['id'], ['status' => $status]);
            }
        }
    }

    public static function resolveLifecycleStatus(
        bool $isScheduleComplete,
        ?string $date,
        ?string $startTime,
        ?string $endTime,
        ?int $now = null,
    ): string {
        if (! $isScheduleComplete) {
            return 'proyeksi';
        }

        $start = strtotime((string) $date . ' ' . (string) $startTime);
        $end = strtotime((string) $date . ' ' . (string) $endTime);
        if ($start === false || $end === false) {
            return 'proyeksi';
        }

        $now ??= time();

        return match (true) {
            $end <= $now          => 'selesai',
            $start <= $now        => 'berlangsung',
            $start - $now <= 1800 => 'persiapan',
            default              => 'menunggu',
        };
    }

    /**
     * @return array{
     *     tanggal_mulai: ?string,
     *     tanggal_selesai: ?string,
     *     bulan_mulai: ?string,
     *     bulan_selesai: ?string
     * }
     */
    public static function parseProjectionPeriodRange(?string $label, ?int $fallbackYear = null): array
    {
        $empty = [
            'tanggal_mulai'   => null,
            'tanggal_selesai' => null,
            'bulan_mulai'     => null,
            'bulan_selesai'   => null,
        ];
        $label = trim((string) $label);
        if ($label === '') {
            return $empty;
        }

        $normalized = str_replace(["\u{2012}", "\u{2013}", "\u{2014}", "\u{2212}"], '-', $label);
        $monthNumbers = [
            'januari'   => 1,
            'februari'  => 2,
            'maret'     => 3,
            'april'     => 4,
            'mei'       => 5,
            'juni'      => 6,
            'juli'      => 7,
            'agustus'   => 8,
            'september' => 9,
            'oktober'   => 10,
            'november'  => 11,
            'desember'  => 12,
        ];
        preg_match_all(
            '/\b(' . implode('|', array_keys($monthNumbers)) . ')\b/iu',
            $normalized,
            $monthMatches,
        );
        if (($monthMatches[1] ?? []) === []) {
            return $empty;
        }

        preg_match_all('/\b(20\d{2})\b/u', $normalized, $yearMatches);
        $years = array_map('intval', $yearMatches[1] ?? []);
        $firstMonthName = mb_strtolower((string) $monthMatches[1][0]);
        $lastMonthName = mb_strtolower((string) end($monthMatches[1]));
        $firstMonth = $monthNumbers[$firstMonthName];
        $lastMonth = $monthNumbers[$lastMonthName];
        $firstYear = $years[0] ?? $fallbackYear;
        $lastYear = $years !== [] ? (int) end($years) : $fallbackYear;

        if ($firstYear === null || $lastYear === null) {
            return $empty;
        }
        if (count($years) === 1 && $firstMonth > $lastMonth) {
            $firstYear = $lastYear - 1;
        }

        $result = [
            'tanggal_mulai'   => null,
            'tanggal_selesai' => null,
            'bulan_mulai'     => sprintf('%04d-%02d', $firstYear, $firstMonth),
            'bulan_selesai'   => sprintf('%04d-%02d', $lastYear, $lastMonth),
        ];

        preg_match_all(
            '/\b(\d{1,2})(?:\s*-\s*(\d{1,2}))?\s+('
                . implode('|', array_keys($monthNumbers))
                . ')\b/iu',
            $normalized,
            $dateMatches,
            PREG_SET_ORDER,
        );
        if ($dateMatches === []) {
            return $result;
        }

        $firstDateMatch = $dateMatches[0];
        $lastDateMatch = $dateMatches[count($dateMatches) - 1];
        $startDay = (int) $firstDateMatch[1];
        $endDay = (int) ($lastDateMatch[2] !== '' ? $lastDateMatch[2] : $lastDateMatch[1]);
        $startDateMonth = $monthNumbers[mb_strtolower($firstDateMatch[3])];
        $endDateMonth = $monthNumbers[mb_strtolower($lastDateMatch[3])];

        if (checkdate($startDateMonth, $startDay, $firstYear)) {
            $result['tanggal_mulai'] = sprintf('%04d-%02d-%02d', $firstYear, $startDateMonth, $startDay);
        }
        if (checkdate($endDateMonth, $endDay, $lastYear)) {
            $result['tanggal_selesai'] = sprintf('%04d-%02d-%02d', $lastYear, $endDateMonth, $endDay);
        }

        return $result;
    }

    /**
     * @param list<int> $documentIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function findGroupedByDocumentIds(array $documentIds, bool $includeInternal = true): array
    {
        if ($documentIds === [] || ! $this->db->tableExists($this->table)) {
            return [];
        }

        $builder = $this->db->table($this->table . ' p')
            ->select(
                'p.id, p.dokumen_banmus_id, p.agenda, p.jenis_agenda, p.periode_label,
                 p.tanggal_mulai, p.tanggal_selesai, p.bulan_mulai, p.bulan_selesai,
                 p.urutan, p.catatan, p.status, p.tanggal, p.jam_mulai, p.jam_selesai,
                 p.ruangan_id, p.lokasi_lainnya, p.publikasi, p.materi_url, p.stream_url,
                 p.undangan_file, p.undangan_nama_asli'
            )
            ->whereIn('p.dokumen_banmus_id', $documentIds)
            ->where('p.deleted_at', null);
        if (! $includeInternal) {
            $builder->where('p.publikasi', 'publik');
        }

        $rows = $builder
            ->orderBy('p.urutan', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->get()
            ->getResultArray();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['dokumen_banmus_id']][] = $row;
        }

        return $grouped;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    public function attachUnitIds(array $items): array
    {
        $itemIds = array_values(array_filter(array_map(
            static fn (array $item): int => (int) ($item['id'] ?? 0),
            $items,
        )));
        if ($itemIds === [] || ! $this->db->tableExists('jadwal_banmus_unit_rapat')) {
            foreach ($items as &$item) {
                $item['unit_ids'] = [];
            }
            unset($item);

            return $items;
        }

        $rows = $this->db->table('jadwal_banmus_unit_rapat')
            ->select('jadwal_banmus_id, unit_rapat_id')
            ->whereIn('jadwal_banmus_id', $itemIds)
            ->get()
            ->getResultArray();

        $unitMap = [];
        foreach ($rows as $row) {
            $unitMap[(int) $row['jadwal_banmus_id']][] = (int) $row['unit_rapat_id'];
        }

        foreach ($items as &$item) {
            $item['unit_ids'] = $unitMap[(int) $item['id']] ?? [];
        }
        unset($item);

        return $items;
    }
}
