<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnsToNotifikasi extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE notifikasi
                ADD COLUMN message       TEXT         NULL
                           COMMENT 'Isi pesan WA yang dikirim (audit log)',
                ADD COLUMN retry_count   TINYINT      NOT NULL DEFAULT 0
                           COMMENT 'Jumlah percobaan pengiriman ulang',
                ADD COLUMN error_message VARCHAR(500) NULL
                           COMMENT 'Pesan error dari Fonnte API'
        ");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE notifikasi DROP COLUMN message");
        $this->db->query("ALTER TABLE notifikasi DROP COLUMN retry_count");
        $this->db->query("ALTER TABLE notifikasi DROP COLUMN error_message");
    }
}
