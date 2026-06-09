<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->insertMissing('ruangan', 'name', $this->rooms());
        $this->insertMissing('unit_rapat', 'nama', $this->meetingUnits());
        $this->insertMissing('anggota', 'no_wa', $this->members());
    }

    private function insertMissing(string $table, string $uniqueField, array $rows): void
    {
        foreach ($rows as $row) {
            $exists = $this->db->table($table)
                ->where($uniqueField, $row[$uniqueField])
                ->countAllResults() > 0;

            if (! $exists) {
                $this->db->table($table)->insert($row);
            }
        }
    }

    private function rooms(): array
    {
        return [
            [
                'name'       => 'Ruang Rapat Paripurna',
                'keterangan' => 'Ruang utama untuk sidang paripurna dan rapat pleno DPRD',
                'kapasitas'  => 80,
                'lantai'     => 'Lantai 2',
                'tersedia'   => 1,
            ],
            [
                'name'       => 'Ruang Komisi I',
                'keterangan' => 'Bidang Pemerintahan, Hukum & HAM',
                'kapasitas'  => 30,
                'lantai'     => 'Lantai 3',
                'tersedia'   => 1,
            ],
            [
                'name'       => 'Ruang Komisi II',
                'keterangan' => 'Bidang Perekonomian & Keuangan',
                'kapasitas'  => 30,
                'lantai'     => 'Lantai 3',
                'tersedia'   => 1,
            ],
            [
                'name'       => 'Ruang Komisi III',
                'keterangan' => 'Bidang Pembangunan & Infrastruktur',
                'kapasitas'  => 30,
                'lantai'     => 'Lantai 3',
                'tersedia'   => 1,
            ],
            [
                'name'       => 'Ruang Komisi IV',
                'keterangan' => 'Bidang Kesejahteraan Rakyat & Pendidikan',
                'kapasitas'  => 30,
                'lantai'     => 'Lantai 4',
                'tersedia'   => 1,
            ],
            [
                'name'       => 'Ruang Pansus',
                'keterangan' => 'Ruang panitia khusus dan rapat gabungan',
                'kapasitas'  => 40,
                'lantai'     => 'Lantai 4',
                'tersedia'   => 1,
            ],
        ];
    }

    private function meetingUnits(): array
    {
        $now = date('Y-m-d H:i:s');

        return [
            [
                'nama'       => 'Pansus Ranperda Pajak Daerah',
                'jenis'      => 'pansus',
                'aktif'      => 1,
                'urutan'     => 96,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama'       => 'Tim Pembahas RAPBD',
                'jenis'      => 'lainnya',
                'aktif'      => 1,
                'urutan'     => 97,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
    }

    private function members(): array
    {
        return [
            ['name' => 'Budi Santoso, S.H.',   'jabatan' => 'Ketua Komisi I',         'fraksi' => 'Fraksi A', 'komisi' => 'Komisi I',   'no_wa' => '6281100000001', 'aktif' => 1],
            ['name' => 'Dewi Kartika, S.E.',   'jabatan' => 'Wakil Ketua Komisi I',   'fraksi' => 'Fraksi B', 'komisi' => 'Komisi I',   'no_wa' => '6281100000002', 'aktif' => 1],
            ['name' => 'Agus Salim',           'jabatan' => 'Anggota',                'fraksi' => 'Fraksi C', 'komisi' => 'Komisi I',   'no_wa' => '6281100000003', 'aktif' => 1],
            ['name' => 'Sari Wulandari, S.Pd.', 'jabatan' => 'Anggota',               'fraksi' => 'Fraksi D', 'komisi' => 'Komisi I',   'no_wa' => '6281100000004', 'aktif' => 1],
            ['name' => 'Hendra Gunawan, M.M.', 'jabatan' => 'Ketua Komisi II',        'fraksi' => 'Fraksi A', 'komisi' => 'Komisi II',  'no_wa' => '6281100000005', 'aktif' => 1],
            ['name' => 'Ratna Sari, M.Si.',    'jabatan' => 'Wakil Ketua Komisi II',  'fraksi' => 'Fraksi B', 'komisi' => 'Komisi II',  'no_wa' => '6281100000006', 'aktif' => 1],
            ['name' => 'Irwan Setiawan',       'jabatan' => 'Anggota',                'fraksi' => 'Fraksi C', 'komisi' => 'Komisi II',  'no_wa' => '6281100000007', 'aktif' => 1],
            ['name' => 'Fitriani, S.T.',       'jabatan' => 'Anggota',                'fraksi' => 'Fraksi D', 'komisi' => 'Komisi II',  'no_wa' => '6281100000008', 'aktif' => 1],
            ['name' => 'Mahmud Hidayat, M.T.', 'jabatan' => 'Ketua Komisi III',       'fraksi' => 'Fraksi A', 'komisi' => 'Komisi III', 'no_wa' => '6281100000009', 'aktif' => 1],
            ['name' => 'Nurul Azizah, S.H.',   'jabatan' => 'Wakil Ketua Komisi III', 'fraksi' => 'Fraksi B', 'komisi' => 'Komisi III', 'no_wa' => '6281100000010', 'aktif' => 1],
            ['name' => 'Rusdi Pratama',        'jabatan' => 'Anggota',                'fraksi' => 'Fraksi C', 'komisi' => 'Komisi III', 'no_wa' => '6281100000011', 'aktif' => 1],
            ['name' => 'Siti Halimah, M.Pd.',  'jabatan' => 'Ketua Komisi IV',        'fraksi' => 'Fraksi A', 'komisi' => 'Komisi IV',  'no_wa' => '6281100000012', 'aktif' => 1],
            ['name' => 'Andi Syahrir',         'jabatan' => 'Wakil Ketua Komisi IV',  'fraksi' => 'Fraksi B', 'komisi' => 'Komisi IV',  'no_wa' => '6281100000013', 'aktif' => 1],
            ['name' => 'Yuliana Putri',        'jabatan' => 'Anggota',                'fraksi' => 'Fraksi C', 'komisi' => 'Komisi IV',  'no_wa' => '6281100000014', 'aktif' => 1],
            ['name' => 'Abdul Rahman, S.H.',   'jabatan' => 'Ketua DPRD',             'fraksi' => 'Fraksi A', 'komisi' => '',           'no_wa' => '6281100000015', 'aktif' => 1],
            ['name' => 'Mariani Lestari, S.E.', 'jabatan' => 'Wakil Ketua DPRD I',    'fraksi' => 'Fraksi B', 'komisi' => '',           'no_wa' => '6281100000016', 'aktif' => 1],
            ['name' => 'Faisal Taufik, S.H.',  'jabatan' => 'Wakil Ketua DPRD II',    'fraksi' => 'Fraksi C', 'komisi' => '',           'no_wa' => '6281100000017', 'aktif' => 1],
        ];
    }
}
