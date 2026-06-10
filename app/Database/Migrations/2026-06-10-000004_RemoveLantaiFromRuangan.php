<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveLantaiFromRuangan extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('ruangan') && $this->db->fieldExists('lantai', 'ruangan')) {
            $this->forge->dropColumn('ruangan', 'lantai');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('ruangan') || $this->db->fieldExists('lantai', 'ruangan')) {
            return;
        }

        $this->forge->addColumn('ruangan', [
            'lantai' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'kapasitas',
            ],
        ]);
    }
}
