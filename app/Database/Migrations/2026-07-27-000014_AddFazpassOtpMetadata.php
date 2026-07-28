<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddFazpassOtpMetadata extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('member_otps')) {
            return;
        }

        $fields = $this->db->getFieldNames('member_otps');
        $add = [];
        if (! in_array('provider_transaction_id', $fields, true)) {
            $add['provider_transaction_id'] = ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true];
        }
        if (! in_array('provider_otp_id', $fields, true)) {
            $add['provider_otp_id'] = ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true];
        }
        if ($add !== []) {
            $this->forge->addColumn('member_otps', $add);
        }

        // Fazpass owns the OTP code; emergency OTP still uses code_hash.
        // MySQL needs an explicit alter for existing installations. Fresh SQLite
        // test databases already allow the field to remain unused for Fazpass.
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('ALTER TABLE ' . $this->db->prefixTable('member_otps') . ' MODIFY code_hash VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('member_otps')) {
            return;
        }

        $fields = $this->db->getFieldNames('member_otps');
        foreach (['provider_transaction_id', 'provider_otp_id'] as $field) {
            if (in_array($field, $fields, true)) {
                $this->forge->dropColumn('member_otps', $field);
            }
        }
    }
}
