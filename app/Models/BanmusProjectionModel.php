<?php

namespace App\Models;

use CodeIgniter\Model;

class BanmusProjectionModel extends Model
{
    protected $table         = 'proyeksi_banmus';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'dokumen_banmus_id',
        'agenda',
        'periode_label',
        'tanggal_mulai',
        'tanggal_selesai',
        'unit_rapat_id',
        'urutan',
        'status',
        'catatan',
        'jadwal_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'ruangan_id',
        'lokasi_lainnya',
        'target_unit_ids',
        'publikasi',
        'kepastian_tanggal',
    ];

    /**
     * Otomatis menentukan status berdasarkan ada/tidaknya tanggal pasti
     */
    public function resolveStatus(array $data): string
    {
        $currentStatus = $data['status'] ?? 'proyeksi';
        $tanggal = trim((string) ($data['tanggal'] ?? ''));

        // Jika status khusus (selesai, ditunda, dibatalkan), pertahankan
        if (in_array($currentStatus, ['selesai', 'ditunda', 'dibatalkan'], true)) {
            return $currentStatus;
        }

        // Jika tanggal pasti ada, otomatis fixed; jika tidak, proyeksi
        return $tanggal !== '' ? 'fixed' : 'proyeksi';
    }

    /**
     * @param list<int> $documentIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function findGroupedByDocumentIds(array $documentIds): array
    {
        if ($documentIds === [] || ! $this->db->tableExists($this->table)) {
            return [];
        }

        $rows = $this->db->table($this->table . ' p')
            ->select(
                'p.id, p.dokumen_banmus_id, p.agenda, p.periode_label,
                 p.urutan, p.catatan, p.status, p.tanggal, p.jam_mulai, p.jam_selesai,
                 p.ruangan_id, p.lokasi_lainnya, p.target_unit_ids, p.publikasi, p.kepastian_tanggal'
            )
            ->whereIn('p.dokumen_banmus_id', $documentIds)
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
}
