<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveWaApiSettings extends Migration
{
    private array $waSettings = [
        'wa_api_key',
        'wa_notif_aktif',
        'wa_sender_name',
    ];

    public function up(): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }

        $this->db->table('settings')
            ->whereIn('key_name', $this->waSettings)
            ->delete();
    }

    public function down(): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }

        $defaults = [
            'wa_api_key'     => '',
            'wa_notif_aktif' => '0',
            'wa_sender_name' => 'Sekretariat DPRD',
        ];

        foreach ($defaults as $key => $value) {
            $exists = $this->db->table('settings')
                ->where('key_name', $key)
                ->countAllResults() > 0;

            if (! $exists) {
                $this->db->table('settings')->insert([
                    'key_name' => $key,
                    'value'    => $value,
                ]);
            }
        }
    }
}
