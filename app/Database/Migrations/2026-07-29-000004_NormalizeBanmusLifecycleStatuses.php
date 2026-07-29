<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeBanmusLifecycleStatuses extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('jadwal_banmus')) {
            return;
        }

        $this->db->table('jadwal_banmus')
            ->where('status', 'fixed')
            ->update(['status' => 'menunggu']);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('jadwal_banmus')) {
            return;
        }

        $this->db->table('jadwal_banmus')
            ->whereIn('status', ['menunggu', 'persiapan', 'berlangsung'])
            ->update(['status' => 'fixed']);
    }
}
