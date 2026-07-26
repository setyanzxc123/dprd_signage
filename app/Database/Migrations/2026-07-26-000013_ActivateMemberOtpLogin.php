<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ActivateMemberOtpLogin extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('member_otps')) {
            $fields = $this->db->getFieldNames('member_otps');
            $add = [];
            if (! in_array('source', $fields, true)) {
                $add['source'] = ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'whatsapp', 'after' => 'delivery_status'];
            }
            if (! in_array('created_by_admin_id', $fields, true)) {
                $add['created_by_admin_id'] = ['type' => 'INT', 'null' => true, 'after' => 'source'];
            }
            if (! in_array('emergency_reason', $fields, true)) {
                $add['emergency_reason'] = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'created_by_admin_id'];
            }
            if ($add !== []) {
                $this->forge->addColumn('member_otps', $add);
            }
        }

        if ($this->db->tableExists('member_accounts')
            && in_array('password_hash', $this->db->getFieldNames('member_accounts'), true)) {
            $this->forge->dropColumn('member_accounts', 'password_hash');
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('member_accounts')
            && ! in_array('password_hash', $this->db->getFieldNames('member_accounts'), true)) {
            $this->forge->addColumn('member_accounts', [
                'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'anggota_id'],
            ]);
        }

        if ($this->db->tableExists('member_otps')) {
            foreach (['emergency_reason', 'created_by_admin_id', 'source'] as $column) {
                if (in_array($column, $this->db->getFieldNames('member_otps'), true)) {
                    $this->forge->dropColumn('member_otps', $column);
                }
            }
        }
    }
}
