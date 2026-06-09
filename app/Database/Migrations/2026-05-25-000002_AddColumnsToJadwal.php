<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnsToJadwal extends Migration
{
    public function up(): void
    {
        // Tambah kolom jenis, is_publik, stream_url ke tabel jadwal
        $this->db->query("
            ALTER TABLE jadwal
                ADD COLUMN jenis      ENUM('reguler','insidental') NOT NULL DEFAULT 'insidental'
                           COMMENT 'Klasifikasi rapat: reguler=terencana, insidental=mendadak',
                ADD COLUMN is_publik  TINYINT(1) NOT NULL DEFAULT 0
                           COMMENT '1 = tampil di signage TV & portal publik',
                ADD COLUMN stream_url VARCHAR(500) DEFAULT NULL
                           COMMENT 'URL live streaming atau arsip video rapat'
        ");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE jadwal DROP COLUMN jenis");
        $this->db->query("ALTER TABLE jadwal DROP COLUMN is_publik");
        $this->db->query("ALTER TABLE jadwal DROP COLUMN stream_url");
    }
}
