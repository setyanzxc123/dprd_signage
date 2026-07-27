<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SimplifyBanmusProjectionEntry extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('dokumen_banmus', [
            'tanggal_sk' => [
                'name'    => 'tanggal_sk',
                'type'    => 'DATE',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->modifyColumn('proyeksi_banmus', [
            'agenda' => [
                'name' => 'agenda',
                'type' => 'TEXT',
            ],
        ]);
    }

    public function down(): void
    {
        $this->db->table('dokumen_banmus')
            ->where('tanggal_sk', null)
            ->update(['tanggal_sk' => '2000-01-01']);

        $rows = $this->db->table('proyeksi_banmus')
            ->select('id, agenda')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $agenda = (string) ($row['agenda'] ?? '');
            if (mb_strlen($agenda) <= 255) {
                continue;
            }

            $this->db->table('proyeksi_banmus')
                ->where('id', $row['id'])
                ->update(['agenda' => mb_substr($agenda, 0, 255)]);
        }

        $this->forge->modifyColumn('dokumen_banmus', [
            'tanggal_sk' => [
                'name' => 'tanggal_sk',
                'type' => 'DATE',
                'null' => false,
            ],
        ]);

        $this->forge->modifyColumn('proyeksi_banmus', [
            'agenda' => [
                'name'       => 'agenda',
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
        ]);
    }
}
