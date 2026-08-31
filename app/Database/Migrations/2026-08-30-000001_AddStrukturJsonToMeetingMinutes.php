<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStrukturJsonToMeetingMinutes extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('meeting_minutes')) {
            if (! $this->db->fieldExists('struktur_json', 'meeting_minutes')) {
                $this->forge->addColumn('meeting_minutes', [
                    'struktur_json' => [
                        'type'    => 'LONGTEXT',
                        'null'    => true,
                        'default' => null,
                        'after'   => 'ringkasan_eksekutif',
                    ],
                ]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('meeting_minutes')) {
            if ($this->db->fieldExists('struktur_json', 'meeting_minutes')) {
                $this->forge->dropColumn('meeting_minutes', 'struktur_json');
            }
        }
    }
}
