<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class AddGeneralScheduleResources extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('jadwal_umum')) {
            throw new RuntimeException('Tabel jadwal_umum belum tersedia.');
        }

        $fields = [];
        if (! $this->db->fieldExists('materi_url', 'jadwal_umum')) {
            $fields['materi_url'] = [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
            ];
        }
        if (! $this->db->fieldExists('materi_akses', 'jadwal_umum')) {
            $fields['materi_akses'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'peserta',
            ];
        }
        if (! $this->db->fieldExists('stream_url', 'jadwal_umum')) {
            $fields['stream_url'] = [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
            ];
        }
        if (! $this->db->fieldExists('stream_akses', 'jadwal_umum')) {
            $fields['stream_akses'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'anggota',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('jadwal_umum', $fields);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('jadwal_umum')) {
            return;
        }

        foreach (['stream_akses', 'stream_url', 'materi_akses', 'materi_url'] as $column) {
            if ($this->db->fieldExists($column, 'jadwal_umum')) {
                $this->forge->dropColumn('jadwal_umum', $column);
            }
        }
    }
}
