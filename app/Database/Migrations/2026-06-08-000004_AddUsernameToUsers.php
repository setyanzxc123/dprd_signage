<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUsernameToUsers extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('username', 'users')) {
            $this->forge->addColumn('users', [
                'username' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'name',
                ],
            ]);
        }

        $users = $this->db->table('users')
            ->select('id, email, username')
            ->get()
            ->getResultArray();

        foreach ($users as $user) {
            if (! empty($user['username'])) {
                continue;
            }

            $base = $this->makeUsername($user);
            $username = $base;
            $suffix = 2;

            while ($this->usernameExists($username, (int) $user['id'])) {
                $username = $base . $suffix;
                $suffix++;
            }

            $this->db->table('users')
                ->where('id', $user['id'])
                ->update(['username' => $username]);
        }

        $this->forge->modifyColumn('users', [
            'username' => [
                'name'       => 'username',
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'email' => [
                'name'       => 'email',
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
        ]);

        if (! $this->hasUsernameUniqueIndex()) {
            $this->forge->addUniqueKey('username', 'users_username_unique');
            $this->forge->processIndexes('users');
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('username', 'users')) {
            $this->forge->dropColumn('users', 'username');
        }
    }

    private function makeUsername(array $user): string
    {
        $email = (string) ($user['email'] ?? '');
        $base = $email !== '' ? explode('@', $email)[0] : 'user' . $user['id'];
        $base = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $base) ?? '');

        return $base !== '' ? substr($base, 0, 40) : 'user' . $user['id'];
    }

    private function usernameExists(string $username, int $exceptId): bool
    {
        return $this->db->table('users')
            ->where('username', $username)
            ->where('id !=', $exceptId)
            ->countAllResults() > 0;
    }

    private function hasUsernameUniqueIndex(): bool
    {
        foreach ($this->db->getIndexData('users') as $index) {
            if (($index->type ?? '') === 'UNIQUE' && ($index->fields ?? []) === ['username']) {
                return true;
            }
        }

        return false;
    }
}
