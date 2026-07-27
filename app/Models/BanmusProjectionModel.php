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
    ];

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
                 p.urutan, p.catatan'
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
