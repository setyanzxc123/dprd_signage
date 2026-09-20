<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePushSubscriptions extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('push_subscriptions')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'endpoint' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'p256dh' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'auth' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey('endpoint');
        $this->forge->createTable('push_subscriptions', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('push_subscriptions')) {
            $this->forge->dropTable('push_subscriptions', true);
        }
    }
}
