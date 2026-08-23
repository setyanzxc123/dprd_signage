<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->deleteObsoleteSettings([
            'logo_path',
            'wa_notif_aktif',
            'wa_sender_name',
            'wa_template_reminder',
            'wa_template_default_aktif',
        ]);

        $this->insertMissing('settings', 'key_name', [
            ['key_name' => 'tema_signage',       'value' => 'dark'],
            ['key_name' => 'running_text',       'value' => 'Selamat datang di DPRD Provinsi Sulawesi Tengah'],
            ['key_name' => 'running_text_aktif', 'value' => '1'],
            ['key_name' => 'media_mode',         'value' => 'video'],
            ['key_name' => 'media_file',         'value' => ''],
        ]);

        $this->seedAdminUser();
    }

    /**
     * Akun admin hidup sebagai user CodeIgniter Shield: identitas
     * email/password di tabel auth_identities dan peran via grup.
     */
    private function seedAdminUser(): void
    {
        $users = new UserModel();

        if ($this->findByUsername($users, 'admin') !== null) {
            return;
        }

        $user = new User([
            'username' => 'admin',
            'name'     => 'Administrator',
            'email'    => 'admin@gmail.com',
            'password' => 'admin123$',
            'active'   => 1,
        ]);

        $users->save($user);

        $user = $this->findByUsername($users, 'admin');
        $user->addGroup('superadmin');
    }

    private function findByUsername(UserModel $users, string $username): ?User
    {
        return $users->where('username', $username)->first();
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

    private function deleteObsoleteSettings(array $keys): void
    {
        if (empty($keys)) {
            return;
        }

        $this->db->table('settings')
            ->whereIn('key_name', $keys)
            ->delete();
    }
}
