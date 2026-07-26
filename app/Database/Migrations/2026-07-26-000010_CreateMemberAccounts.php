<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMemberAccounts extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('member_accounts')) {
            return;
        }

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true],
            'anggota_id'    => ['type' => 'INT', 'null' => false],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'login_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'created_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('anggota_id');
        $this->forge->addForeignKey('anggota_id', 'anggota', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('member_accounts', true);

        $members = $this->db->table('anggota')->select('id')->get()->getResultArray();
        if ($members === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $rows = array_map(static fn (array $member): array => [
            'anggota_id'    => (int) $member['id'],
            'password_hash' => null,
            'login_enabled' => 0,
            'created_at'    => $now,
            'updated_at'    => $now,
        ], $members);

        $this->db->table('member_accounts')->insertBatch($rows);
    }

    public function down(): void
    {
        $this->forge->dropTable('member_accounts', true);
    }
}
