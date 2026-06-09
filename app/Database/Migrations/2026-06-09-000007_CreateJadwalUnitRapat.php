<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJadwalUnitRapat extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'jadwal_id'     => ['type' => 'INT', 'null' => false],
            'unit_rapat_id' => ['type' => 'INT', 'null' => false],
            'created_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey(['jadwal_id', 'unit_rapat_id'], true);
        $this->forge->addForeignKey('jadwal_id', 'jadwal', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_rapat_id', 'unit_rapat', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('jadwal_unit_rapat', true);

        $this->migrateKomisiTargetJson();

        if ($this->db->fieldExists('komisi_target', 'jadwal')) {
            $this->forge->dropColumn('jadwal', 'komisi_target');
        }
    }

    public function down(): void
    {
        if (! $this->db->fieldExists('komisi_target', 'jadwal')) {
            $this->forge->addColumn('jadwal', [
                'komisi_target' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'ruangan_id',
                ],
            ]);
        }

        $this->forge->dropTable('jadwal_unit_rapat', true);
    }

    private function migrateKomisiTargetJson(): void
    {
        if (! $this->db->fieldExists('komisi_target', 'jadwal')) {
            return;
        }

        $units = $this->db->table('unit_rapat')
            ->select('id, nama')
            ->get()
            ->getResultArray();

        $unitIds = array_column($units, 'id', 'nama');
        if (isset($unitIds['Seluruh Anggota'])) {
            $unitIds['All Komisi'] = $unitIds['Seluruh Anggota'];
        }

        $jadwals = $this->db->table('jadwal')
            ->select('id, komisi_target')
            ->get()
            ->getResultArray();

        $now = date('Y-m-d H:i:s');
        foreach ($jadwals as $jadwal) {
            $targets = json_decode($jadwal['komisi_target'] ?? '[]', true);
            if (! is_array($targets)) {
                continue;
            }

            foreach (array_unique($targets) as $target) {
                if (! isset($unitIds[$target])) {
                    continue;
                }

                $this->db->table('jadwal_unit_rapat')
                    ->ignore(true)
                    ->insert([
                        'jadwal_id'     => $jadwal['id'],
                        'unit_rapat_id' => $unitIds[$target],
                        'created_at'    => $now,
                    ]);
            }
        }
    }
}
