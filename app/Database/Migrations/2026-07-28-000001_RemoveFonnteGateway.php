<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class RemoveFonnteGateway extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('otp_webhook_events')) {
            $this->forge->addField([
                'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'provider'            => ['type' => 'VARCHAR', 'constraint' => 32],
                'event_hash'          => ['type' => 'CHAR', 'constraint' => 64],
                'provider_message_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'status'              => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'raw_payload'         => ['type' => 'TEXT'],
                'received_at'         => ['type' => 'DATETIME'],
                'processed_at'        => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['provider', 'event_hash'], 'uq_otp_webhook_event');
            $this->forge->createTable('otp_webhook_events', true);
        }

        $this->forge->dropTable('whatsapp_webhook_events', true);
        $this->forge->dropTable('whatsapp_messages', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('otp_webhook_events', true);
    }
}
