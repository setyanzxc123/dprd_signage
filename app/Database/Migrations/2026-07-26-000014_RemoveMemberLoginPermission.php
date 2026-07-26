<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveMemberLoginPermission extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('member_accounts')
            && in_array('login_enabled', $this->db->getFieldNames('member_accounts'), true)) {
            $this->forge->dropColumn('member_accounts', 'login_enabled');
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('member_accounts')
            && ! in_array('login_enabled', $this->db->getFieldNames('member_accounts'), true)) {
            $this->forge->addColumn('member_accounts', [
                'login_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'after'      => 'anggota_id',
                ],
            ]);
        }
    }
}
