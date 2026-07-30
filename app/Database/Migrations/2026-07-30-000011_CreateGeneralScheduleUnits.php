<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGeneralScheduleUnits extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('jadwal_umum_unit_rapat')) {
            return;
        }

        $this->forge->addField([
            'jadwal_umum_id' => ['type' => 'INT', 'null' => false],
            'unit_rapat_id'  => ['type' => 'INT', 'null' => false],
            'created_at'     => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey(['jadwal_umum_id', 'unit_rapat_id'], true);
        $this->forge->addForeignKey('jadwal_umum_id', 'jadwal_umum', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_rapat_id', 'unit_rapat', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('jadwal_umum_unit_rapat', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('jadwal_umum_unit_rapat', true);
    }
}
