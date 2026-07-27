<?php

namespace App\Models;

use CodeIgniter\Model;

class BanmusDocumentModel extends Model
{
    protected $table         = 'dokumen_banmus';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'judul',
        'nomor_sk',
        'tanggal_sk',
        'tahun',
        'semester',
        'masa_persidangan',
        'periode_mulai',
        'periode_selesai',
        'status',
        'is_publik',
        'dokumen_file',
        'dokumen_nama_asli',
        'dokumen_url',
        'catatan',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function findForPortal(bool $includeInternal, int $year, ?int $semester = null): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        $builder = $this
            ->where('tahun', $year)
            ->orderBy('semester', 'DESC')
            ->orderBy('tanggal_sk', 'DESC')
            ->orderBy('id', 'DESC');

        if (! $includeInternal) {
            $builder->where('is_publik', 1);
        }
        if ($semester !== null) {
            $builder->where('semester', $semester);
        }

        $documents = $builder->findAll();
        if ($documents === []) {
            return [];
        }

        $itemsByDocument = (new BanmusProjectionModel())
            ->findGroupedByDocumentIds(
                array_map('intval', array_column($documents, 'id')),
            );

        foreach ($documents as &$document) {
            $document['items'] = $itemsByDocument[(int) $document['id']] ?? [];
        }
        unset($document);

        return $documents;
    }

    /**
     * @return list<int>
     */
    public function availableYears(bool $includeInternal): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        $builder = $this->builder()
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun', 'DESC');

        if (! $includeInternal) {
            $builder->where('is_publik', 1);
        }

        return array_map(
            'intval',
            array_column($builder->get()->getResultArray(), 'tahun'),
        );
    }
}
