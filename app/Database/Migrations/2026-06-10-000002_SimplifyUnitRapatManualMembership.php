<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SimplifyUnitRapatManualMembership extends Migration
{
    public function up(): void
    {
        $this->backfillManualMemberships();

        if ($this->db->fieldExists('jenis', 'unit_rapat')) {
            $this->forge->dropColumn('unit_rapat', 'jenis');
        }

        if ($this->db->fieldExists('membership_type', 'unit_rapat')) {
            $this->forge->dropColumn('unit_rapat', 'membership_type');
        }
    }

    public function down(): void
    {
        if (! $this->db->fieldExists('jenis', 'unit_rapat')) {
            $this->forge->addColumn('unit_rapat', [
                'jenis' => [
                    'type'       => 'ENUM',
                    'constraint' => ['komisi', 'badan', 'pansus', 'gabungan', 'lainnya'],
                    'default'    => 'lainnya',
                    'after'      => 'nama',
                ],
            ]);
        }

        if (! $this->db->fieldExists('membership_type', 'unit_rapat')) {
            $this->forge->addColumn('unit_rapat', [
                'membership_type' => [
                    'type'       => 'ENUM',
                    'constraint' => ['semua_anggota', 'komisi_anggota', 'manual'],
                    'default'    => 'manual',
                    'null'       => false,
                    'after'      => 'jenis',
                ],
            ]);
        }
    }

    private function backfillManualMemberships(): void
    {
        if (
            ! $this->db->fieldExists('membership_type', 'unit_rapat')
            || ! $this->db->tableExists('anggota_unit_rapat')
        ) {
            return;
        }

        $units = $this->db->table('unit_rapat')
            ->select('id, nama, membership_type')
            ->whereIn('membership_type', ['semua_anggota', 'komisi_anggota'])
            ->get()
            ->getResultArray();

        if (empty($units)) {
            return;
        }

        $activeMembers = $this->db->table('anggota')
            ->select('id, komisi')
            ->where('aktif', 1)
            ->get()
            ->getResultArray();

        $now = date('Y-m-d H:i:s');

        foreach ($units as $unit) {
            $unitId = (int) $unit['id'];
            $memberIds = [];

            foreach ($activeMembers as $member) {
                if ($unit['membership_type'] === 'semua_anggota') {
                    $memberIds[] = (int) $member['id'];
                    continue;
                }

                if (trim((string) $member['komisi']) === trim((string) $unit['nama'])) {
                    $memberIds[] = (int) $member['id'];
                }
            }

            foreach (array_unique($memberIds) as $memberId) {
                $exists = $this->db->table('anggota_unit_rapat')
                    ->where('anggota_id', $memberId)
                    ->where('unit_rapat_id', $unitId)
                    ->countAllResults() > 0;

                if (! $exists) {
                    $this->db->table('anggota_unit_rapat')->insert([
                        'anggota_id'    => $memberId,
                        'unit_rapat_id' => $unitId,
                        'created_at'    => $now,
                    ]);
                }
            }
        }
    }
}
