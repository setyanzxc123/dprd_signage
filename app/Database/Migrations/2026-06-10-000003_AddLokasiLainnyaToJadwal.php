<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLokasiLainnyaToJadwal extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('jadwal') || $this->db->fieldExists('lokasi_lainnya', 'jadwal')) {
            return;
        }

        $this->forge->addColumn('jadwal', [
            'lokasi_lainnya' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'ruangan_id',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->tableExists('jadwal') && $this->db->fieldExists('lokasi_lainnya', 'jadwal')) {
            $this->forge->dropColumn('jadwal', 'lokasi_lainnya');
        }
    }
}
