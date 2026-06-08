<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Settings awal signage ──────────────────────────────────────
        $this->db->table('settings')->insertBatch([
            ['key_name' => 'tema_signage',       'value' => 'dark'],
            ['key_name' => 'running_text',       'value' => 'Selamat datang di DPRD Provinsi Sulawesi Tengah'],
            ['key_name' => 'running_text_aktif', 'value' => '1'],
            ['key_name' => 'media_mode',         'value' => 'video'],
            ['key_name' => 'media_file',         'value' => ''],
            ['key_name' => 'logo_path',          'value' => './assets/images/logo_dprd.jpg'],
            ['key_name' => 'bmkg_adm4',          'value' => '72.71.04.1001'],
        ]);

        // ── User admin pertama (password: admin) ────────────────────
        $this->db->table('users')->insert([
            'name'       => 'Administrator',
            'username'   => 'admin',
            'email'      => 'admin@gmail.com',
            'password'   => password_hash('admin', PASSWORD_BCRYPT),
            'role'       => 'superadmin',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
