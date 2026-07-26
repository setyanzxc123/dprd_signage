<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWhatsappGatewayTables extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('whatsapp_messages')) {
            $this->forge->addField([
                'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'provider'            => ['type' => 'VARCHAR', 'constraint' => 32],
                'provider_message_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'provider_request_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'target'              => ['type' => 'VARCHAR', 'constraint' => 32],
                'message_type'        => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'transactional'],
                'status'              => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pending'],
                'state_id'            => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'state'               => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'detail'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'last_error'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'raw_response'        => ['type' => 'TEXT', 'null' => true],
                'created_at'          => ['type' => 'DATETIME', 'null' => true],
                'updated_at'          => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['provider', 'provider_message_id'], 'uq_wa_provider_message');
            $this->forge->addKey(['provider', 'provider_request_id'], false, false, 'idx_wa_provider_request');
            $this->forge->addKey(['provider', 'state_id'], false, false, 'idx_wa_provider_state');
            $this->forge->addKey(['target', 'created_at'], false, false, 'idx_wa_target_created');
            $this->forge->createTable('whatsapp_messages', true);
        }

        if (! $this->db->tableExists('whatsapp_webhook_events')) {
            $this->forge->addField([
                'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'provider'            => ['type' => 'VARCHAR', 'constraint' => 32],
                'event_hash'          => ['type' => 'CHAR', 'constraint' => 64],
                'provider_message_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'state_id'            => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'status'              => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'raw_payload'         => ['type' => 'TEXT'],
                'received_at'         => ['type' => 'DATETIME'],
                'processed_at'        => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['provider', 'event_hash'], 'uq_wa_webhook_event');
            $this->forge->addKey(['provider', 'provider_message_id'], false, false, 'idx_wa_event_message');
            $this->forge->createTable('whatsapp_webhook_events', true);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('whatsapp_webhook_events', true);
        $this->forge->dropTable('whatsapp_messages', true);
    }
}
