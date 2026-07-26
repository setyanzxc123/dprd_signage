<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMemberOtps extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('member_otps')) {
            $this->forge->addField([
                'id'                    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'member_account_id'     => ['type' => 'INT', 'null' => false],
                'code_hash'             => ['type' => 'VARCHAR', 'constraint' => 255],
                'phone_hash'            => ['type' => 'CHAR', 'constraint' => 64],
                'ip_hash'               => ['type' => 'CHAR', 'constraint' => 64],
                'delivery_status'       => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'created'],
                'provider'              => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'provider_message_id'   => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'provider_request_id'   => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'verification_attempts' => ['type' => 'SMALLINT', 'default' => 0],
                'max_attempts'          => ['type' => 'SMALLINT', 'default' => 5],
                'expires_at'            => ['type' => 'DATETIME'],
                'resend_available_at'   => ['type' => 'DATETIME'],
                'used_at'               => ['type' => 'DATETIME', 'null' => true],
                'cancelled_at'          => ['type' => 'DATETIME', 'null' => true],
                'created_at'            => ['type' => 'DATETIME'],
                'updated_at'            => ['type' => 'DATETIME'],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addForeignKey('member_account_id', 'member_accounts', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addKey(['member_account_id', 'expires_at'], false, false, 'idx_member_otp_active');
            $this->forge->addKey(['phone_hash', 'created_at'], false, false, 'idx_member_otp_phone');
            $this->forge->addKey(['ip_hash', 'created_at'], false, false, 'idx_member_otp_ip');
            $this->forge->createTable('member_otps', true);
        }

        if (! $this->db->tableExists('member_otp_audits')) {
            $this->forge->addField([
                'id'                    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'member_otp_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
                'member_account_id'     => ['type' => 'INT', 'null' => true],
                'event'                 => ['type' => 'VARCHAR', 'constraint' => 48],
                'phone_hash'            => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
                'ip_hash'               => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
                'provider'              => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'provider_status'       => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'reason'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at'            => ['type' => 'DATETIME'],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addForeignKey('member_otp_id', 'member_otps', 'id', 'CASCADE', 'SET NULL');
            $this->forge->addForeignKey('member_account_id', 'member_accounts', 'id', 'CASCADE', 'SET NULL');
            $this->forge->addKey(['event', 'created_at'], false, false, 'idx_member_otp_audit_event');
            $this->forge->addKey(['phone_hash', 'created_at'], false, false, 'idx_member_otp_audit_phone');
            $this->forge->addKey(['ip_hash', 'created_at'], false, false, 'idx_member_otp_audit_ip');
            $this->forge->createTable('member_otp_audits', true);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('member_otp_audits', true);
        $this->forge->dropTable('member_otps', true);
    }
}
