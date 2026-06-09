<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveTechnicalApiSettings extends Migration
{
    private array $settings = [
        'bmkg_adm4',
    ];

    public function up(): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }

        $this->db->table('settings')
            ->whereIn('key_name', $this->settings)
            ->delete();
    }

    public function down(): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }

        $exists = $this->db->table('settings')
            ->where('key_name', 'bmkg_adm4')
            ->countAllResults() > 0;

        if (! $exists) {
            $this->db->table('settings')->insert([
                'key_name' => 'bmkg_adm4',
                'value'    => '72.71.04.1001',
            ]);
        }
    }
}
