<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveWaScheduling extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('notifikasi')) {
            $this->forge->dropTable('notifikasi', true);
        }

        foreach (['blast_before', 'reminder_time'] as $column) {
            if ($this->db->fieldExists($column, 'jadwal')) {
                $this->forge->dropColumn('jadwal', $column);
            }
        }

        if ($this->db->tableExists('settings')) {
            $this->db->table('settings')
                ->whereIn('key_name', [
                    'wa_sender_name',
                    'wa_template_reminder',
                    'wa_template_default_aktif',
                    'wa_api_key',
                    'wa_notif_aktif',
                ])
                ->delete();
        }
    }

    public function down(): void
    {
        if (! $this->db->fieldExists('blast_before', 'jadwal')) {
            $this->forge->addColumn('jadwal', [
                'blast_before' => ['type' => 'INT', 'default' => 60],
            ]);
        }

        if (! $this->db->fieldExists('reminder_time', 'jadwal')) {
            $this->forge->addColumn('jadwal', [
                'reminder_time' => ['type' => 'DATETIME', 'null' => true],
            ]);
        }

        if (! $this->db->tableExists('notifikasi')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'jadwal_id'     => ['type' => 'INT', 'unsigned' => true],
                'anggota_id'    => ['type' => 'INT', 'unsigned' => true],
                'no_wa'         => ['type' => 'VARCHAR', 'constraint' => 20],
                'status'        => ['type' => 'ENUM', 'constraint' => ['pending', 'sent', 'failed'], 'default' => 'pending'],
                'executed_at'   => ['type' => 'DATETIME', 'null' => true],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'message'       => ['type' => 'TEXT', 'null' => true],
                'retry_count'   => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
                'error_message' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('notifikasi', true);
        }
    }
}
