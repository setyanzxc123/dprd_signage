<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAgendaResourceAccess extends Migration
{
    public function up(): void
    {
        foreach (['jadwal', 'jadwal_banmus'] as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            $fields = [];
            if (! $this->db->fieldExists('materi_akses', $table)) {
                $fields['materi_akses'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'peserta',
                    'after'      => 'materi_url',
                ];
            }
            if (! $this->db->fieldExists('stream_akses', $table)) {
                $fields['stream_akses'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'anggota',
                    'after'      => 'stream_url',
                ];
            }

            if ($fields !== []) {
                $this->forge->addColumn($table, $fields);
            }
        }
    }

    public function down(): void
    {
        foreach (['jadwal', 'jadwal_banmus'] as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            foreach (['stream_akses', 'materi_akses'] as $column) {
                if ($this->db->fieldExists($column, $table)) {
                    $this->forge->dropColumn($table, $column);
                }
            }
        }
    }
}
