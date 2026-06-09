<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateJadwalJenisEnum extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('jenis', 'jadwal')) {
            return;
        }

        $this->db->query("
            ALTER TABLE jadwal
                MODIFY jenis ENUM('bamus','reguler','insidental') NOT NULL DEFAULT 'insidental'
                COMMENT 'Klasifikasi rapat: reguler=terencana, insidental=mendadak'
        ");

        $this->db->table('jadwal')
            ->where('jenis', 'bamus')
            ->update(['jenis' => 'reguler']);

        $this->db->query("
            ALTER TABLE jadwal
                MODIFY jenis ENUM('reguler','insidental') NOT NULL DEFAULT 'insidental'
                COMMENT 'Klasifikasi rapat: reguler=terencana, insidental=mendadak'
        ");
    }

    public function down(): void
    {
        if (! $this->db->fieldExists('jenis', 'jadwal')) {
            return;
        }

        $this->db->query("
            ALTER TABLE jadwal
                MODIFY jenis ENUM('bamus','reguler','insidental') NOT NULL DEFAULT 'insidental'
                COMMENT 'Klasifikasi rapat: bamus=terencana SK, insidental=mendadak'
        ");

        $this->db->table('jadwal')
            ->where('jenis', 'reguler')
            ->update(['jenis' => 'bamus']);

        $this->db->query("
            ALTER TABLE jadwal
                MODIFY jenis ENUM('bamus','insidental') NOT NULL DEFAULT 'insidental'
                COMMENT 'Klasifikasi rapat: bamus=terencana SK, insidental=mendadak'
        ");
    }
}
