<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnggotaUnitRapat extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'anggota_id'    => ['type' => 'INT', 'null' => false],
            'unit_rapat_id' => ['type' => 'INT', 'null' => false],
            'created_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey(['anggota_id', 'unit_rapat_id'], true);
        $this->forge->addForeignKey('anggota_id', 'anggota', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_rapat_id', 'unit_rapat', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('anggota_unit_rapat', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('anggota_unit_rapat', true);
    }
}
