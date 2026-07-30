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
        'stream_url',
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
                 p.urutan, p.catatan, p.status, p.tanggal, p.jam_mulai, p.jam_selesai,
                 p.ruangan_id, p.lokasi_lainnya, p.publikasi, p.materi_url, p.stream_url'
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
