<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBanmusAgendaType extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('jadwal_banmus')
            || $this->db->fieldExists('jenis_agenda', 'jadwal_banmus')) {
            return;
        }

        $this->forge->addColumn('jadwal_banmus', [
            'jenis_agenda' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'rapat',
                'after'      => 'agenda',
            ],
        ]);

        $this->db->table('jadwal_banmus')
            ->like('agenda', 'reses')
            ->update(['jenis_agenda' => 'non_rapat']);
    }

    public function down(): void
    {
        if ($this->db->tableExists('jadwal_banmus')
            && $this->db->fieldExists('jenis_agenda', 'jadwal_banmus')) {
            $this->forge->dropColumn('jadwal_banmus', 'jenis_agenda');
        }
    }
}
