<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ClassifyExistingNonMeetingBanmusItems extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('jadwal_banmus')) {
            return;
        }
        $this->db->resetDataCache();
        if (! $this->db->fieldExists('jenis_agenda', 'jadwal_banmus')) {
            return;
        }

        $patterns = [
            ['reses', 'both'],
            ['hari raya', 'after'],
            ['cuti bersama', 'after'],
            ['hari lahir', 'after'],
            ['tahun baru', 'after'],
            ['hut proklamasi', 'after'],
            ['maulid', 'after'],
            ['pengawasan penggunaan anggaran', 'after'],
            ['koordinasi dan komunikasi dalam daerah', 'after'],
            ['koordinasi dan komunikasi antar daerah', 'after'],
        ];

        $builder = $this->db->table('jadwal_banmus')
            ->where('jenis_agenda', 'rapat')
            ->groupStart();
        foreach ($patterns as $index => [$pattern, $side]) {
            if ($index === 0) {
                $builder->like('agenda', $pattern, $side, null, true);
            } else {
                $builder->orLike('agenda', $pattern, $side, null, true);
            }
        }
        $builder
            ->groupEnd()
            ->update(['jenis_agenda' => 'non_rapat']);
    }

    public function down(): void
    {
        // Klasifikasi lama tidak dapat dipulihkan tanpa menimpa koreksi manual admin.
    }
}
