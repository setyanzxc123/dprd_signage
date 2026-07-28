<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOperationalFieldsToBanmusItem extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('proyeksi_banmus')) {
            $fields = [];

            if (! $this->db->fieldExists('tanggal', 'proyeksi_banmus')) {
                $fields['tanggal'] = ['type' => 'DATE', 'null' => true, 'default' => null];
            }
            if (! $this->db->fieldExists('jam_mulai', 'proyeksi_banmus')) {
                $fields['jam_mulai'] = ['type' => 'TIME', 'null' => true, 'default' => null];
            }
            if (! $this->db->fieldExists('jam_selesai', 'proyeksi_banmus')) {
                $fields['jam_selesai'] = ['type' => 'TIME', 'null' => true, 'default' => null];
            }
            if (! $this->db->fieldExists('ruangan_id', 'proyeksi_banmus')) {
                $fields['ruangan_id'] = ['type' => 'INT', 'null' => true, 'default' => null];
            }
            if (! $this->db->fieldExists('lokasi_lainnya', 'proyeksi_banmus')) {
                $fields['lokasi_lainnya'] = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null];
            }
            if (! $this->db->fieldExists('target_unit_ids', 'proyeksi_banmus')) {
                $fields['target_unit_ids'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (! $this->db->fieldExists('publikasi', 'proyeksi_banmus')) {
                $fields['publikasi'] = ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'internal'];
            }
            if (! $this->db->fieldExists('kepastian_tanggal', 'proyeksi_banmus')) {
                $fields['kepastian_tanggal'] = ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'bulan'];
            }

            if ($fields !== []) {
                $this->forge->addColumn('proyeksi_banmus', $fields);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('proyeksi_banmus')) {
            $columns = [
                'tanggal',
                'jam_mulai',
                'jam_selesai',
                'ruangan_id',
                'lokasi_lainnya',
                'target_unit_ids',
                'publikasi',
                'kepastian_tanggal',
            ];

            foreach ($columns as $column) {
                if ($this->db->fieldExists($column, 'proyeksi_banmus')) {
                    $this->forge->dropColumn('proyeksi_banmus', $column);
                }
            }
        }
    }
}
