<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGeneralAgendas extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('agenda_umum')) {
            return;
        }

        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'judul'             => ['type' => 'VARCHAR', 'constraint' => 200],
            'kategori'          => ['type' => 'VARCHAR', 'constraint' => 30],
            'tanggal'           => ['type' => 'DATE'],
            'waktu_mulai'       => ['type' => 'TIME'],
            'waktu_selesai'     => ['type' => 'TIME', 'null' => true, 'default' => null],
            'lokasi'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'sumber_informasi'  => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'default' => null],
            'perkiraan_peserta' => ['type' => 'INT', 'null' => true, 'default' => null],
            'keterangan'        => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'tentatif'],
            'is_publik'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('tanggal');
        $this->forge->addKey(['is_publik', 'tanggal']);
        $this->forge->addKey(['status', 'tanggal']);
        $this->forge->createTable('agenda_umum', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('agenda_umum', true);
    }
}
