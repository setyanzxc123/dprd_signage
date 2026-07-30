<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGeneralSchedules extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('jadwal_umum')) {
            return;
        }

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'auto_increment' => true],
            'judul'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal'          => ['type' => 'DATE'],
            'waktu_mulai'      => ['type' => 'TIME', 'null' => true, 'default' => null],
            'waktu_selesai'    => ['type' => 'TIME', 'null' => true, 'default' => null],
            'ruangan_id'       => ['type' => 'INT', 'null' => true, 'default' => null],
            'lokasi_lainnya'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'pihak_eksternal'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'is_publik'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'keterangan'       => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('tanggal');
        $this->forge->addKey(['is_publik', 'tanggal']);
        $this->forge->addKey(['ruangan_id', 'tanggal']);
        $this->forge->addForeignKey('ruangan_id', 'ruangan', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('jadwal_umum', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('jadwal_umum', true);
    }
}
