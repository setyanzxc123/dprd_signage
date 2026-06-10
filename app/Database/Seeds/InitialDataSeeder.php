<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->insertMissing('settings', 'key_name', [
            ['key_name' => 'tema_signage',       'value' => 'dark'],
            ['key_name' => 'running_text',       'value' => 'Selamat datang di DPRD Provinsi Sulawesi Tengah'],
            ['key_name' => 'running_text_aktif', 'value' => '1'],
            ['key_name' => 'media_mode',         'value' => 'video'],
            ['key_name' => 'media_file',         'value' => ''],
            ['key_name' => 'logo_path',          'value' => './assets/images/logo_dprd.jpg'],
        ]);

        $this->insertMissing('users', 'username', [[
            'name'       => 'Administrator',
            'username'   => 'admin',
            'email'      => 'admin@gmail.com',
            'password'   => password_hash('admin', PASSWORD_BCRYPT),
            'role'       => 'superadmin',
            'created_at' => date('Y-m-d H:i:s'),
        ]]);
    }

    private function insertMissing(string $table, string $uniqueField, array $rows): void
    {
        foreach ($rows as $row) {
            $exists = $this->db->table($table)
                ->where($uniqueField, $row[$uniqueField])
                ->countAllResults() > 0;

            if (! $exists) {
                $this->db->table($table)->insert($row);
            }
        }
    }
}
