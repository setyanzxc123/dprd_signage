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
        $this->syncUnitMembers();
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
                'keterangan' => 'Lantai 2. Ruang utama untuk sidang paripurna dan rapat pleno DPRD',
                'kapasitas'  => 80,
                'tersedia'   => 1,
            ],
            [
                'name'       => 'Ruang Komisi I',
                'keterangan' => 'Lantai 3. Bidang Pemerintahan, Hukum & HAM',
                'kapasitas'  => 30,
                'tersedia'   => 1,
            ],
            [
                'name'       => 'Ruang Komisi II',
                'keterangan' => 'Lantai 3. Bidang Perekonomian & Keuangan',
                'kapasitas'  => 30,
                'tersedia'   => 1,
            ],
            [
                'name'       => 'Ruang Komisi III',
                'keterangan' => 'Lantai 3. Bidang Pembangunan & Infrastruktur',
                'kapasitas'  => 30,
                'tersedia'   => 1,
            ],
            [
                'name'       => 'Ruang Komisi IV',
                'keterangan' => 'Lantai 4. Bidang Kesejahteraan Rakyat & Pendidikan',
                'kapasitas'  => 30,
                'tersedia'   => 1,
            ],
            [
                'name'       => 'Ruang Pansus',
                'keterangan' => 'Lantai 4. Ruang panitia khusus dan rapat gabungan',
                'kapasitas'  => 40,
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
                'aktif'      => 1,
                'urutan'     => 96,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama'       => 'Tim Pembahas RAPBD',
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
            ['name' => 'Budi Santoso, S.H.',   'jabatan' => 'Ketua Komisi I',         'fraksi' => 'Amanat Nasional',        'komisi' => 'Komisi I',   'no_wa' => '6281100000001', 'aktif' => 1],
            ['name' => 'Dewi Kartika, S.E.',   'jabatan' => 'Wakil Ketua Komisi I',   'fraksi' => 'Bulan Bintang',          'komisi' => 'Komisi I',   'no_wa' => '6281100000002', 'aktif' => 1],
            ['name' => 'Agus Salim',           'jabatan' => 'Anggota',                'fraksi' => 'Demokrat',               'komisi' => 'Komisi I',   'no_wa' => '6281100000003', 'aktif' => 1],
            ['name' => 'Sari Wulandari, S.Pd.', 'jabatan' => 'Anggota',               'fraksi' => 'Gerindra',               'komisi' => 'Komisi I',   'no_wa' => '6281100000004', 'aktif' => 1],
            ['name' => 'Hendra Gunawan, M.M.', 'jabatan' => 'Ketua Komisi II',        'fraksi' => 'Golongan Karya',         'komisi' => 'Komisi II',  'no_wa' => '6281100000005', 'aktif' => 1],
            ['name' => 'Ratna Sari, M.Si.',    'jabatan' => 'Wakil Ketua Komisi II',  'fraksi' => 'Hanura',                 'komisi' => 'Komisi II',  'no_wa' => '6281100000006', 'aktif' => 1],
            ['name' => 'Irwan Setiawan',       'jabatan' => 'Anggota',                'fraksi' => 'Keadilan Sejahtra',      'komisi' => 'Komisi II',  'no_wa' => '6281100000007', 'aktif' => 1],
            ['name' => 'Fitriani, S.T.',       'jabatan' => 'Anggota',                'fraksi' => 'PDIP',                   'komisi' => 'Komisi II',  'no_wa' => '6281100000008', 'aktif' => 1],
            ['name' => 'Mahmud Hidayat, M.T.', 'jabatan' => 'Ketua Komisi III',       'fraksi' => 'Persatuan Indonesia',    'komisi' => 'Komisi III', 'no_wa' => '6281100000009', 'aktif' => 1],
            ['name' => 'Nurul Azizah, S.H.',   'jabatan' => 'Wakil Ketua Komisi III', 'fraksi' => 'Persatuan Pembangunan',  'komisi' => 'Komisi III', 'no_wa' => '6281100000010', 'aktif' => 1],
            ['name' => 'Rusdi Pratama',        'jabatan' => 'Anggota',                'fraksi' => 'Amanat Nasional',        'komisi' => 'Komisi III', 'no_wa' => '6281100000011', 'aktif' => 1],
            ['name' => 'Siti Halimah, M.Pd.',  'jabatan' => 'Ketua Komisi IV',        'fraksi' => 'Bulan Bintang',          'komisi' => 'Komisi IV',  'no_wa' => '6281100000012', 'aktif' => 1],
            ['name' => 'Andi Syahrir',         'jabatan' => 'Wakil Ketua Komisi IV',  'fraksi' => 'Demokrat',               'komisi' => 'Komisi IV',  'no_wa' => '6281100000013', 'aktif' => 1],
            ['name' => 'Yuliana Putri',        'jabatan' => 'Anggota',                'fraksi' => 'Gerindra',               'komisi' => 'Komisi IV',  'no_wa' => '6281100000014', 'aktif' => 1],
            ['name' => 'Abdul Rahman, S.H.',   'jabatan' => 'Ketua DPRD',             'fraksi' => 'Golongan Karya',         'komisi' => '',           'no_wa' => '6281100000015', 'aktif' => 1],
            ['name' => 'Mariani Lestari, S.E.', 'jabatan' => 'Wakil Ketua DPRD I',    'fraksi' => 'Hanura',                 'komisi' => '',           'no_wa' => '6281100000016', 'aktif' => 1],
            ['name' => 'Faisal Taufik, S.H.',  'jabatan' => 'Wakil Ketua DPRD II',    'fraksi' => 'Keadilan Sejahtra',      'komisi' => '',           'no_wa' => '6281100000017', 'aktif' => 1],
        ];
    }

    private function syncUnitMembers(): void
    {
        if (! $this->db->tableExists('anggota_unit_rapat')) {
            return;
        }

        $membersByPhone = array_column(
            $this->db->table('anggota')
                ->select('id, no_wa')
                ->get()
                ->getResultArray(),
            'id',
            'no_wa'
        );

        $unitsByName = array_column(
            $this->db->table('unit_rapat')
                ->select('id, nama')
                ->get()
                ->getResultArray(),
            'id',
            'nama'
        );

        $unitMembers = [
            'Seluruh Anggota' => array_keys($membersByPhone),
            'Pansus Ranperda Pajak Daerah' => [
                '6281100000002',
                '6281100000007',
                '6281100000014',
                '6281100000015',
            ],
            'Tim Pembahas RAPBD' => [
                '6281100000005',
                '6281100000009',
                '6281100000012',
                '6281100000016',
            ],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($unitMembers as $unitName => $phones) {
            if (! isset($unitsByName[$unitName])) {
                continue;
            }

            $unitId = (int) $unitsByName[$unitName];
            foreach ($phones as $phone) {
                if (! isset($membersByPhone[$phone])) {
                    continue;
                }

                $anggotaId = (int) $membersByPhone[$phone];
                $exists = $this->db->table('anggota_unit_rapat')
                    ->where('anggota_id', $anggotaId)
                    ->where('unit_rapat_id', $unitId)
                    ->countAllResults() > 0;

                if (! $exists) {
                    $this->db->table('anggota_unit_rapat')->insert([
                        'anggota_id'    => $anggotaId,
                        'unit_rapat_id' => $unitId,
                        'created_at'    => $now,
                    ]);
                }
            }
        }

        // Isi relasi awal unit komisi untuk kebutuhan testing.
        $members = $this->db->table('anggota')
            ->select('id, komisi')
            ->where('komisi !=', '')
            ->where('komisi IS NOT NULL')
            ->get()
            ->getResultArray();

        $units = $this->db->table('unit_rapat')
            ->select('id, nama')
            ->get()
            ->getResultArray();

        $unitMap = array_column($units, 'id', 'nama');
        foreach ($members as $member) {
            $komisiName = trim($member['komisi']);
            if (isset($unitMap[$komisiName])) {
                $unitId = (int) $unitMap[$komisiName];
                $exists = $this->db->table('anggota_unit_rapat')
                    ->where('anggota_id', $member['id'])
                    ->where('unit_rapat_id', $unitId)
                    ->countAllResults() > 0;

                if (! $exists) {
                    $this->db->table('anggota_unit_rapat')->insert([
                        'anggota_id'    => $member['id'],
                        'unit_rapat_id' => $unitId,
                        'created_at'    => $now,
                    ]);
                }
            }
        }
    }
}
