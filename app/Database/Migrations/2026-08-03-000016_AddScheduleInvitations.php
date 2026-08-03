<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddScheduleInvitations extends Migration
{
    public function up(): void
    {
        foreach (['jadwal_banmus', 'jadwal_umum'] as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            $fields = [];
            if (! $this->db->fieldExists('undangan_file', $table)) {
                $fields['undangan_file'] = [
                    'type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null,
                ];
            }
            if (! $this->db->fieldExists('undangan_nama_asli', $table)) {
                $fields['undangan_nama_asli'] = [
                    'type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null,
                ];
            }
            if ($fields !== []) {
                $this->forge->addColumn($table, $fields);
            }
        }
    }

    public function down(): void
    {
        foreach (['jadwal_banmus', 'jadwal_umum'] as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            foreach (['undangan_nama_asli', 'undangan_file'] as $column) {
                if ($this->db->fieldExists($column, $table)) {
                    $this->forge->dropColumn($table, $column);
                }
            }
        }
    }
}
