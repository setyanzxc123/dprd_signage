<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class WaSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['key_name' => 'wa_api_key',     'value' => ''],
            ['key_name' => 'wa_notif_aktif', 'value' => '0'],
            ['key_name' => 'wa_sender_name', 'value' => 'Sekretariat DPRD'],
        ];

        foreach ($data as $row) {
            $exists = $this->db->table('settings')
                ->where('key_name', $row['key_name'])
                ->get()->getRowArray();

            if (!$exists) {
                $this->db->table('settings')->insert($row);
            }
        }
    }
}
