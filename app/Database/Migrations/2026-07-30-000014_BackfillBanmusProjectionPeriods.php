<?php

namespace App\Database\Migrations;

use App\Models\JadwalBanmusModel;
use CodeIgniter\Database\Migration;

class BackfillBanmusProjectionPeriods extends Migration
{
    public function up(): void
    {
        $this->db->resetDataCache();
        if (! $this->db->tableExists('jadwal_banmus')) {
            return;
        }
        foreach (['tanggal_mulai', 'tanggal_selesai', 'bulan_mulai', 'bulan_selesai'] as $field) {
            if (! $this->db->fieldExists($field, 'jadwal_banmus')) {
                return;
            }
        }

        $rows = $this->db->table('jadwal_banmus jb')
            ->select('jb.id, jb.periode_label, jb.tanggal_mulai, jb.tanggal_selesai,
                jb.bulan_mulai, jb.bulan_selesai, db.tahun')
            ->join('dokumen_banmus db', 'db.id = jb.dokumen_banmus_id', 'left')
            ->where('jb.status', 'proyeksi')
            ->where('jb.deleted_at', null)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $range = JadwalBanmusModel::parseProjectionPeriodRange(
                $row['periode_label'] ?? null,
                isset($row['tahun']) ? (int) $row['tahun'] : null,
            );
            $update = [];
            foreach ($range as $field => $value) {
                if (($row[$field] ?? null) === null && $value !== null) {
                    $update[$field] = $value;
                }
            }
            if ($update !== []) {
                $this->db->table('jadwal_banmus')
                    ->where('id', (int) $row['id'])
                    ->update($update);
            }
        }
    }

    public function down(): void
    {
        // Derived period boundaries are intentionally retained on rollback.
    }
}
