<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $tomorrow  = date('Y-m-d', strtotime('+1 day'));

        // ── 1. RUANGAN ─────────────────────────────────────────────────
        $this->db->table('ruangan')->insertBatch([
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
                'keterangan' => 'Ruang Panitia Khusus & Rapat Gabungan',
                'kapasitas'  => 40,
                'lantai'     => 'Lantai 4',
                'tersedia'   => 1,
            ],
        ]);

        // ── 2. ANGGOTA DPRD ────────────────────────────────────────────
        $this->db->table('anggota')->insertBatch([
            // Komisi I
            ['name' => 'Budi Santoso, S.H.',        'jabatan' => 'Ketua Komisi I',        'fraksi' => 'Fraksi A', 'komisi' => 'Komisi I',   'no_wa' => '6281100000001', 'aktif' => 1],
            ['name' => 'Dewi Kartika, S.E.',         'jabatan' => 'Wakil Ketua Komisi I',  'fraksi' => 'Fraksi B', 'komisi' => 'Komisi I',   'no_wa' => '6281100000002', 'aktif' => 1],
            ['name' => 'Agus Salim',                 'jabatan' => 'Anggota',               'fraksi' => 'Fraksi C', 'komisi' => 'Komisi I',   'no_wa' => '6281100000003', 'aktif' => 1],
            ['name' => 'Sari Wulandari, S.Pd.',      'jabatan' => 'Anggota',               'fraksi' => 'Fraksi D', 'komisi' => 'Komisi I',   'no_wa' => '6281100000004', 'aktif' => 1],
            // Komisi II
            ['name' => 'Hendra Gunawan, M.M.',       'jabatan' => 'Ketua Komisi II',       'fraksi' => 'Fraksi A', 'komisi' => 'Komisi II',  'no_wa' => '6281100000005', 'aktif' => 1],
            ['name' => 'Ratna Sari, M.Si.',           'jabatan' => 'Wakil Ketua Komisi II', 'fraksi' => 'Fraksi B', 'komisi' => 'Komisi II',  'no_wa' => '6281100000006', 'aktif' => 1],
            ['name' => 'Irwan Setiawan',              'jabatan' => 'Anggota',               'fraksi' => 'Fraksi C', 'komisi' => 'Komisi II',  'no_wa' => '6281100000007', 'aktif' => 1],
            ['name' => 'Fitriani, S.T.',              'jabatan' => 'Anggota',               'fraksi' => 'Fraksi D', 'komisi' => 'Komisi II',  'no_wa' => '6281100000008', 'aktif' => 1],
            // Komisi III
            ['name' => 'Mahmud Hidayat, M.T.',       'jabatan' => 'Ketua Komisi III',      'fraksi' => 'Fraksi A', 'komisi' => 'Komisi III', 'no_wa' => '6281100000009', 'aktif' => 1],
            ['name' => 'Nurul Azizah, S.H.',         'jabatan' => 'Wakil Ketua Komisi III','fraksi' => 'Fraksi B', 'komisi' => 'Komisi III', 'no_wa' => '6281100000010', 'aktif' => 1],
            ['name' => 'Rusdi Pratama',               'jabatan' => 'Anggota',               'fraksi' => 'Fraksi C', 'komisi' => 'Komisi III', 'no_wa' => '6281100000011', 'aktif' => 1],
            // Komisi IV
            ['name' => 'Siti Halimah, M.Pd.',        'jabatan' => 'Ketua Komisi IV',       'fraksi' => 'Fraksi A', 'komisi' => 'Komisi IV',  'no_wa' => '6281100000012', 'aktif' => 1],
            ['name' => 'Andi Syahrir',                'jabatan' => 'Wakil Ketua Komisi IV', 'fraksi' => 'Fraksi B', 'komisi' => 'Komisi IV',  'no_wa' => '6281100000013', 'aktif' => 1],
            ['name' => 'Yuliana Putri',               'jabatan' => 'Anggota',               'fraksi' => 'Fraksi C', 'komisi' => 'Komisi IV',  'no_wa' => '6281100000014', 'aktif' => 1],
            // Pimpinan
            ['name' => 'Abdul Rahman, S.H.',         'jabatan' => 'Ketua DPRD',            'fraksi' => 'Fraksi A', 'komisi' => 'Pansus',     'no_wa' => '6281100000015', 'aktif' => 1],
            ['name' => 'Mariani Lestari, S.E.',      'jabatan' => 'Wakil Ketua DPRD I',    'fraksi' => 'Fraksi B', 'komisi' => 'Pansus',     'no_wa' => '6281100000016', 'aktif' => 1],
            ['name' => 'Faisal Taufik, S.H.',        'jabatan' => 'Wakil Ketua DPRD II',   'fraksi' => 'Fraksi C', 'komisi' => 'Pansus',     'no_wa' => '6281100000017', 'aktif' => 1],
        ]);

        // Ambil ID ruangan
        $ruangan = $this->db->table('ruangan')->get()->getResultArray();
        $rId     = array_column($ruangan, 'id', 'name');

        // ── 3. JADWAL RAPAT ────────────────────────────────────────────
        $jadwalList = [
            // ── Hari ini ──────────────────────────────────────────────
            [
                'judul'         => 'Rapat Paripurna — Pembahasan Laporan Tahunan',
                'keterangan'    => 'Pembahasan laporan tahunan dan evaluasi kinerja tahun berjalan.',
                'tanggal'       => $today,
                'waktu_mulai'   => '09:00:00',
                'waktu_selesai' => '12:00:00',
                'ruangan_id'    => $rId['Ruang Rapat Paripurna'] ?? 1,
                'target_units'  => ['Seluruh Anggota'],
                'blast_before'  => 60,
                'reminder_time' => date('Y-m-d H:i:s', strtotime("$today 08:00:00")),
                'status'        => 'berlangsung',
                'materi_url'    => 'https://drive.google.com/file/d/contoh-lkpj-2025',
            ],
            [
                'judul'         => 'Rapat Komisi II — Evaluasi Anggaran Triwulan',
                'keterangan'    => 'Evaluasi realisasi anggaran dan pendapatan daerah triwulan berjalan.',
                'tanggal'       => $today,
                'waktu_mulai'   => '13:00:00',
                'waktu_selesai' => '15:00:00',
                'ruangan_id'    => $rId['Ruang Komisi II'] ?? 3,
                'target_units'  => ['Komisi II'],
                'blast_before'  => 60,
                'reminder_time' => date('Y-m-d H:i:s', strtotime("$today 12:00:00")),
                'status'        => 'persiapan',
                'materi_url'    => null,
            ],
            [
                'judul'         => 'Rapat Komisi III — Peninjauan Program Infrastruktur',
                'keterangan'    => 'Peninjauan progres program infrastruktur dan pembangunan daerah.',
                'tanggal'       => $today,
                'waktu_mulai'   => '15:30:00',
                'waktu_selesai' => '17:00:00',
                'ruangan_id'    => $rId['Ruang Komisi III'] ?? 4,
                'target_units'  => ['Komisi III'],
                'blast_before'  => 60,
                'reminder_time' => date('Y-m-d H:i:s', strtotime("$today 14:30:00")),
                'status'        => 'menunggu',
                'materi_url'    => null,
            ],
            // ── Kemarin ───────────────────────────────────────────────
            [
                'judul'         => 'Rapat Pansus — Finalisasi Rancangan Peraturan Daerah',
                'keterangan'    => 'Finalisasi dan pembahasan akhir rancangan peraturan daerah.',
                'tanggal'       => $yesterday,
                'waktu_mulai'   => '10:00:00',
                'waktu_selesai' => '13:00:00',
                'ruangan_id'    => $rId['Ruang Pansus'] ?? 6,
                'target_units'  => ['Pansus'],
                'blast_before'  => 120,
                'reminder_time' => date('Y-m-d H:i:s', strtotime("$yesterday 08:00:00")),
                'status'        => 'selesai',
                'materi_url'    => null,
            ],
            // ── Besok ─────────────────────────────────────────────────
            [
                'judul'         => 'Rapat Komisi I — Pembahasan Peraturan Daerah',
                'keterangan'    => 'Pembahasan rancangan peraturan daerah bidang pemerintahan dan hukum.',
                'tanggal'       => $tomorrow,
                'waktu_mulai'   => '09:00:00',
                'waktu_selesai' => '11:30:00',
                'ruangan_id'    => $rId['Ruang Komisi I'] ?? 2,
                'target_units'  => ['Komisi I'],
                'blast_before'  => 60,
                'reminder_time' => date('Y-m-d H:i:s', strtotime("$tomorrow 08:00:00")),
                'status'        => 'menunggu',
                'materi_url'    => null,
            ],
            [
                'judul'         => 'Rapat Komisi IV — Rapat Dengar Pendapat',
                'keterangan'    => 'Rapat dengar pendapat bersama mitra kerja bidang kesejahteraan rakyat.',
                'tanggal'       => $tomorrow,
                'waktu_mulai'   => '13:30:00',
                'waktu_selesai' => '15:30:00',
                'ruangan_id'    => $rId['Ruang Komisi IV'] ?? 5,
                'target_units'  => ['Komisi IV'],
                'blast_before'  => 60,
                'reminder_time' => date('Y-m-d H:i:s', strtotime("$tomorrow 12:30:00")),
                'status'        => 'menunggu',
                'materi_url'    => null,
            ],
        ];

        foreach ($jadwalList as $jadwal) {
            $targetUnits = $jadwal['target_units'];
            unset($jadwal['target_units']);

            $this->db->table('jadwal')->insert($jadwal);
            $jadwalId    = $this->db->insertID();
            $unitRows = $this->db->table('unit_rapat')
                ->whereIn('nama', $targetUnits)
                ->get()
                ->getResultArray();
            $unitIds = array_column($unitRows, 'id', 'nama');

            foreach ($targetUnits as $unitName) {
                if (!isset($unitIds[$unitName])) {
                    continue;
                }

                $this->db->table('jadwal_unit_rapat')->insert([
                    'jadwal_id'     => $jadwalId,
                    'unit_rapat_id' => $unitIds[$unitName],
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }

            // Buat entri notifikasi pending untuk anggota terkait
            if (in_array('Seluruh Anggota', $targetUnits, true)) {
                $targets = $this->db->table('anggota')->where('aktif', 1)->get()->getResultArray();
            } else {
                $targets = $this->db->table('anggota')
                    ->where('aktif', 1)
                    ->whereIn('komisi', $targetUnits)
                    ->get()->getResultArray();
            }

            foreach ($targets as $anggota) {
                $this->db->table('notifikasi')->insert([
                    'jadwal_id'  => $jadwalId,
                    'anggota_id' => $anggota['id'],
                    'no_wa'      => $anggota['no_wa'],
                    'status'     => $jadwal['status'] === 'selesai' ? 'sent' : 'pending',
                    'executed_at'=> $jadwal['status'] === 'selesai' ? date('Y-m-d H:i:s', strtotime($yesterday . ' 08:00:00')) : null,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
