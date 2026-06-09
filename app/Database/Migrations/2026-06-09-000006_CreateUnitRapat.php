<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUnitRapat extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'jenis'      => [
                'type'       => 'ENUM',
                'constraint' => ['komisi', 'badan', 'pansus', 'gabungan', 'lainnya'],
                'default'    => 'lainnya',
            ],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'urutan'     => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nama');
        $this->forge->createTable('unit_rapat', true);

        $now = date('Y-m-d H:i:s');
        $this->db->table('unit_rapat')->insertBatch([
            ['nama' => 'Komisi I',        'jenis' => 'komisi',   'aktif' => 1, 'urutan' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Komisi II',       'jenis' => 'komisi',   'aktif' => 1, 'urutan' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Komisi III',      'jenis' => 'komisi',   'aktif' => 1, 'urutan' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Komisi IV',       'jenis' => 'komisi',   'aktif' => 1, 'urutan' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Badan Anggaran',  'jenis' => 'badan',    'aktif' => 1, 'urutan' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Badan Musyawarah','jenis' => 'badan',    'aktif' => 1, 'urutan' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Bapemperda',      'jenis' => 'badan',    'aktif' => 1, 'urutan' => 70, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Badan Kehormatan','jenis' => 'badan',    'aktif' => 1, 'urutan' => 80, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Gabungan Komisi', 'jenis' => 'gabungan', 'aktif' => 1, 'urutan' => 90, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Pansus',          'jenis' => 'pansus',   'aktif' => 1, 'urutan' => 95, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Seluruh Anggota', 'jenis' => 'lainnya',  'aktif' => 1, 'urutan' => 100, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('unit_rapat', true);
    }
}
