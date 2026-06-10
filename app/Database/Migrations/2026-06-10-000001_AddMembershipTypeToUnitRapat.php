<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMembershipTypeToUnitRapat extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('unit_rapat')) {
            return;
        }

        if ($this->db->fieldExists('membership_type', 'unit_rapat')) {
            return;
        }

        // 1. Tambah kolom membership_type ke unit_rapat
        $field = [
            'type'       => 'ENUM',
            'constraint' => ['semua_anggota', 'komisi_anggota', 'manual'],
            'default'    => 'manual',
            'null'       => false,
        ];

        if ($this->db->fieldExists('jenis', 'unit_rapat')) {
            $field['after'] = 'jenis';
        }

        $this->forge->addColumn('unit_rapat', [
            'membership_type' => $field,
        ]);

        if (! $this->db->fieldExists('jenis', 'unit_rapat')) {
            return;
        }

        // 2. Set membership_type untuk data yang sudah ada
        $this->db->table('unit_rapat')
            ->where('jenis', 'komisi')
            ->update(['membership_type' => 'komisi_anggota']);

        $this->db->table('unit_rapat')
            ->where('nama', 'Seluruh Anggota')
            ->update(['membership_type' => 'semua_anggota']);

        // 3. Sinkronisasikan data komisi dari anggota ke anggota_unit_rapat
        if (! $this->db->tableExists('anggota_unit_rapat')) {
            return;
        }

        $members = $this->db->table('anggota')
            ->select('id, komisi')
            ->where('komisi !=', '')
            ->where('komisi IS NOT NULL')
            ->get()
            ->getResultArray();

        $units = $this->db->table('unit_rapat')
            ->select('id, nama')
            ->where('jenis', 'komisi')
            ->get()
            ->getResultArray();

        $unitMap = array_column($units, 'id', 'nama');
        $now = date('Y-m-d H:i:s');
        $insertRows = [];

        foreach ($members as $member) {
            $komisiName = trim($member['komisi']);
            if (! isset($unitMap[$komisiName])) {
                continue;
            }

            $unitId = (int) $unitMap[$komisiName];
            $exists = $this->db->table('anggota_unit_rapat')
                ->where('anggota_id', $member['id'])
                ->where('unit_rapat_id', $unitId)
                ->countAllResults() > 0;

            if (! $exists) {
                $insertRows[] = [
                    'anggota_id'    => $member['id'],
                    'unit_rapat_id' => $unitId,
                    'created_at'    => $now,
                ];
            }
        }

        if (! empty($insertRows)) {
            $this->db->table('anggota_unit_rapat')->insertBatch($insertRows);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('unit_rapat') && $this->db->fieldExists('membership_type', 'unit_rapat')) {
            $this->forge->dropColumn('unit_rapat', 'membership_type');
        }
    }
}
