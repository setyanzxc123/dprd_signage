<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandGeneralAgendaFields extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('agenda_umum')) {
            return;
        }

        if (! $this->db->fieldExists('pihak_eksternal', 'agenda_umum')) {
            $this->forge->addColumn('agenda_umum', [
                'pihak_eksternal' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'kategori',
                ],
            ]);
        }

        foreach (['penanggung_jawab_internal', 'lingkup', 'perkiraan_peserta', 'status'] as $field) {
            if ($this->db->fieldExists($field, 'agenda_umum')) {
                $this->forge->dropColumn('agenda_umum', $field);
            }
        }

        $this->db->table('agenda_umum')
            ->where('kategori', 'audiensi_publik')
            ->update(['kategori' => 'audiensi']);

        $this->forge->modifyColumn('agenda_umum', [
            'is_publik' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
        ]);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('agenda_umum')) {
            return;
        }

        $fields = [];
        if (! $this->db->fieldExists('perkiraan_peserta', 'agenda_umum')) {
            $fields['perkiraan_peserta'] = [
                'type'    => 'INT',
                'null'    => true,
                'default' => null,
                'after'   => 'sumber_informasi',
            ];
        }
        if (! $this->db->fieldExists('status', 'agenda_umum')) {
            $fields['status'] = [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'tentatif',
                'after'      => 'keterangan',
            ];
        }
        if ($fields !== []) {
            $this->forge->addColumn('agenda_umum', $fields);
        }

        $this->db->table('agenda_umum')
            ->where('kategori', 'audiensi')
            ->update(['kategori' => 'audiensi_publik']);

        if ($this->db->fieldExists('pihak_eksternal', 'agenda_umum')) {
            $this->forge->dropColumn('agenda_umum', 'pihak_eksternal');
        }

        $this->forge->modifyColumn('agenda_umum', [
            'is_publik' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
        ]);
    }
}
