<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBanmusProjections extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('dokumen_banmus')) {
            $this->forge->addField([
                'id'                  => ['type' => 'INT', 'auto_increment' => true],
                'judul'               => ['type' => 'VARCHAR', 'constraint' => 200],
                'nomor_sk'            => ['type' => 'VARCHAR', 'constraint' => 100],
                'tanggal_sk'          => ['type' => 'DATE'],
                'tahun'               => ['type' => 'SMALLINT'],
                'semester'            => ['type' => 'TINYINT', 'constraint' => 1],
                'masa_persidangan'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null],
                'periode_mulai'       => ['type' => 'DATE', 'null' => true, 'default' => null],
                'periode_selesai'     => ['type' => 'DATE', 'null' => true, 'default' => null],
                'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'rancangan'],
                'is_publik'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'dokumen_file'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
                'dokumen_nama_asli'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
                'dokumen_url'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'default' => null],
                'catatan'             => ['type' => 'TEXT', 'null' => true, 'default' => null],
                'created_at'          => ['type' => 'DATETIME', 'null' => true, 'default' => null],
                'updated_at'          => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey(['tahun', 'semester']);
            $this->forge->addKey(['is_publik', 'status']);
            $this->forge->createTable('dokumen_banmus', true);
        }

        if (! $this->db->tableExists('proyeksi_banmus')) {
            $this->forge->addField([
                'id'                => ['type' => 'INT', 'auto_increment' => true],
                'dokumen_banmus_id' => ['type' => 'INT'],
                'agenda'            => ['type' => 'VARCHAR', 'constraint' => 255],
                'periode_label'     => ['type' => 'VARCHAR', 'constraint' => 100],
                'tanggal_mulai'     => ['type' => 'DATE', 'null' => true, 'default' => null],
                'tanggal_selesai'   => ['type' => 'DATE', 'null' => true, 'default' => null],
                'unit_rapat_id'     => ['type' => 'INT', 'null' => true, 'default' => null],
                'urutan'            => ['type' => 'INT', 'default' => 0],
                'status'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'proyeksi'],
                'catatan'           => ['type' => 'TEXT', 'null' => true, 'default' => null],
                'jadwal_id'         => ['type' => 'INT', 'null' => true, 'default' => null],
                'created_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
                'updated_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey(['dokumen_banmus_id', 'urutan']);
            $this->forge->addKey('jadwal_id');
            $this->forge->addForeignKey('dokumen_banmus_id', 'dokumen_banmus', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('unit_rapat_id', 'unit_rapat', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('jadwal_id', 'jadwal', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('proyeksi_banmus', true);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('proyeksi_banmus', true);
        $this->forge->dropTable('dokumen_banmus', true);
    }
}
