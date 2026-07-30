<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BulkMeetingDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->insertOrUpdate('ruangan', 'name', $this->rooms());
        $this->insertOrUpdate('unit_rapat', 'nama', $this->meetingUnits());
        $this->insertOrUpdate('anggota', 'no_wa', $this->members());
        $this->syncUnitMembers();
        $this->seedSchedules();
    }

    private function insertOrUpdate(string $table, string $uniqueField, array $rows): void
    {
        foreach ($rows as $row) {
            $exists = $this->db->table($table)
                ->where($uniqueField, $row[$uniqueField])
                ->get()
                ->getRowArray();

            if ($exists) {
                $this->db->table($table)
                    ->where('id', $exists['id'])
                    ->update($row);
                continue;
            }

            $this->db->table($table)->insert($row);
        }
    }

    private function rooms(): array
    {
        return [
            ['name' => 'Ruang Rapat Paripurna', 'keterangan' => 'Ruang utama untuk sidang paripurna dan rapat pleno DPRD.', 'kapasitas' => 120, 'tersedia' => 1],
            ['name' => 'Ruang Rapat Utama', 'keterangan' => 'Ruang pimpinan untuk rapat badan dan rapat koordinasi.', 'kapasitas' => 70, 'tersedia' => 1],
            ['name' => 'Ruang Badan Musyawarah', 'keterangan' => 'Ruang rapat Badan Musyawarah.', 'kapasitas' => 45, 'tersedia' => 1],
            ['name' => 'Ruang Badan Anggaran', 'keterangan' => 'Ruang rapat Badan Anggaran.', 'kapasitas' => 55, 'tersedia' => 1],
            ['name' => 'Ruang Bapemperda', 'keterangan' => 'Ruang pembahasan program pembentukan peraturan daerah.', 'kapasitas' => 40, 'tersedia' => 1],
            ['name' => 'Ruang Badan Kehormatan', 'keterangan' => 'Ruang rapat Badan Kehormatan.', 'kapasitas' => 30, 'tersedia' => 1],
            ['name' => 'Ruang Komisi I', 'keterangan' => 'Bidang pemerintahan, hukum, dan keamanan.', 'kapasitas' => 32, 'tersedia' => 1],
            ['name' => 'Ruang Komisi II', 'keterangan' => 'Bidang ekonomi dan keuangan daerah.', 'kapasitas' => 32, 'tersedia' => 1],
            ['name' => 'Ruang Komisi III', 'keterangan' => 'Bidang pembangunan dan infrastruktur.', 'kapasitas' => 32, 'tersedia' => 1],
            ['name' => 'Ruang Komisi IV', 'keterangan' => 'Bidang kesejahteraan rakyat dan pendidikan.', 'kapasitas' => 32, 'tersedia' => 1],
            ['name' => 'Ruang Pansus', 'keterangan' => 'Ruang panitia khusus dan rapat gabungan.', 'kapasitas' => 44, 'tersedia' => 1],
            ['name' => 'Ruang Rapat Sekretariat', 'keterangan' => 'Ruang koordinasi internal sekretariat DPRD.', 'kapasitas' => 24, 'tersedia' => 1],
        ];
    }

    private function meetingUnits(): array
    {
        $now = date('Y-m-d H:i:s');

        return [
            ['nama' => 'Komisi I', 'aktif' => 1, 'urutan' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Komisi II', 'aktif' => 1, 'urutan' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Komisi III', 'aktif' => 1, 'urutan' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Komisi IV', 'aktif' => 1, 'urutan' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Badan Anggaran', 'aktif' => 1, 'urutan' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Badan Musyawarah', 'aktif' => 1, 'urutan' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Bapemperda', 'aktif' => 1, 'urutan' => 70, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Badan Kehormatan', 'aktif' => 1, 'urutan' => 80, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Gabungan Komisi', 'aktif' => 1, 'urutan' => 90, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Pansus Ranperda Pajak Daerah', 'aktif' => 1, 'urutan' => 95, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Pansus Tata Tertib DPRD', 'aktif' => 1, 'urutan' => 96, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Tim Pembahas RAPBD', 'aktif' => 1, 'urutan' => 97, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Tim Kunjungan Kerja', 'aktif' => 1, 'urutan' => 98, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Seluruh Anggota', 'aktif' => 1, 'urutan' => 100, 'created_at' => $now, 'updated_at' => $now],
        ];
    }

    private function members(): array
    {
        return [
            ['name' => 'Abdul Rahman, S.H.', 'jabatan' => 'Ketua DPRD', 'fraksi' => 'Golongan Karya', 'komisi' => '', 'no_wa' => '6282101000001', 'aktif' => 1],
            ['name' => 'Mariani Lestari, S.E.', 'jabatan' => 'Wakil Ketua DPRD I', 'fraksi' => 'NasDem', 'komisi' => '', 'no_wa' => '6282101000002', 'aktif' => 1],
            ['name' => 'Faisal Taufik, S.H.', 'jabatan' => 'Wakil Ketua DPRD II', 'fraksi' => 'Gerindra', 'komisi' => '', 'no_wa' => '6282101000003', 'aktif' => 1],
            ['name' => 'Nurhayati Karim, M.Si.', 'jabatan' => 'Wakil Ketua DPRD III', 'fraksi' => 'PDIP', 'komisi' => '', 'no_wa' => '6282101000004', 'aktif' => 1],
            ['name' => 'Budi Santoso, S.H.', 'jabatan' => 'Ketua Komisi I', 'fraksi' => 'Amanat Nasional', 'komisi' => 'Komisi I', 'no_wa' => '6282101000005', 'aktif' => 1],
            ['name' => 'Dewi Kartika, S.E.', 'jabatan' => 'Wakil Ketua Komisi I', 'fraksi' => 'Demokrat', 'komisi' => 'Komisi I', 'no_wa' => '6282101000006', 'aktif' => 1],
            ['name' => 'Agus Salim', 'jabatan' => 'Sekretaris Komisi I', 'fraksi' => 'PKS', 'komisi' => 'Komisi I', 'no_wa' => '6282101000007', 'aktif' => 1],
            ['name' => 'Sari Wulandari, S.Pd.', 'jabatan' => 'Anggota Komisi I', 'fraksi' => 'Hanura', 'komisi' => 'Komisi I', 'no_wa' => '6282101000008', 'aktif' => 1],
            ['name' => 'Rahmat Prasetyo', 'jabatan' => 'Anggota Komisi I', 'fraksi' => 'Golongan Karya', 'komisi' => 'Komisi I', 'no_wa' => '6282101000009', 'aktif' => 1],
            ['name' => 'Sri Mulyani, S.H.', 'jabatan' => 'Anggota Komisi I', 'fraksi' => 'PKB', 'komisi' => 'Komisi I', 'no_wa' => '6282101000010', 'aktif' => 1],
            ['name' => 'Hendra Gunawan, M.M.', 'jabatan' => 'Ketua Komisi II', 'fraksi' => 'Golongan Karya', 'komisi' => 'Komisi II', 'no_wa' => '6282101000011', 'aktif' => 1],
            ['name' => 'Ratna Sari, M.Si.', 'jabatan' => 'Wakil Ketua Komisi II', 'fraksi' => 'NasDem', 'komisi' => 'Komisi II', 'no_wa' => '6282101000012', 'aktif' => 1],
            ['name' => 'Irwan Setiawan', 'jabatan' => 'Sekretaris Komisi II', 'fraksi' => 'Gerindra', 'komisi' => 'Komisi II', 'no_wa' => '6282101000013', 'aktif' => 1],
            ['name' => 'Fitriani, S.T.', 'jabatan' => 'Anggota Komisi II', 'fraksi' => 'PDIP', 'komisi' => 'Komisi II', 'no_wa' => '6282101000014', 'aktif' => 1],
            ['name' => 'Iskandar Langi', 'jabatan' => 'Anggota Komisi II', 'fraksi' => 'Demokrat', 'komisi' => 'Komisi II', 'no_wa' => '6282101000015', 'aktif' => 1],
            ['name' => 'Riska Amelia, S.E.', 'jabatan' => 'Anggota Komisi II', 'fraksi' => 'PKS', 'komisi' => 'Komisi II', 'no_wa' => '6282101000016', 'aktif' => 1],
            ['name' => 'Mahmud Hidayat, M.T.', 'jabatan' => 'Ketua Komisi III', 'fraksi' => 'NasDem', 'komisi' => 'Komisi III', 'no_wa' => '6282101000017', 'aktif' => 1],
            ['name' => 'Nurul Azizah, S.H.', 'jabatan' => 'Wakil Ketua Komisi III', 'fraksi' => 'Golongan Karya', 'komisi' => 'Komisi III', 'no_wa' => '6282101000018', 'aktif' => 1],
            ['name' => 'Rusdi Pratama', 'jabatan' => 'Sekretaris Komisi III', 'fraksi' => 'Gerindra', 'komisi' => 'Komisi III', 'no_wa' => '6282101000019', 'aktif' => 1],
            ['name' => 'Nurlina Rahim, S.T.', 'jabatan' => 'Anggota Komisi III', 'fraksi' => 'PDIP', 'komisi' => 'Komisi III', 'no_wa' => '6282101000020', 'aktif' => 1],
            ['name' => 'Arman Wijaya', 'jabatan' => 'Anggota Komisi III', 'fraksi' => 'PKB', 'komisi' => 'Komisi III', 'no_wa' => '6282101000021', 'aktif' => 1],
            ['name' => 'Yusuf Malik, M.T.', 'jabatan' => 'Anggota Komisi III', 'fraksi' => 'Amanat Nasional', 'komisi' => 'Komisi III', 'no_wa' => '6282101000022', 'aktif' => 1],
            ['name' => 'Siti Halimah, M.Pd.', 'jabatan' => 'Ketua Komisi IV', 'fraksi' => 'PDIP', 'komisi' => 'Komisi IV', 'no_wa' => '6282101000023', 'aktif' => 1],
            ['name' => 'Andi Syahrir', 'jabatan' => 'Wakil Ketua Komisi IV', 'fraksi' => 'Demokrat', 'komisi' => 'Komisi IV', 'no_wa' => '6282101000024', 'aktif' => 1],
            ['name' => 'Yuliana Putri', 'jabatan' => 'Sekretaris Komisi IV', 'fraksi' => 'NasDem', 'komisi' => 'Komisi IV', 'no_wa' => '6282101000025', 'aktif' => 1],
            ['name' => 'Mansur Latif, S.Pd.', 'jabatan' => 'Anggota Komisi IV', 'fraksi' => 'Golongan Karya', 'komisi' => 'Komisi IV', 'no_wa' => '6282101000026', 'aktif' => 1],
            ['name' => 'Indah Permatasari', 'jabatan' => 'Anggota Komisi IV', 'fraksi' => 'Gerindra', 'komisi' => 'Komisi IV', 'no_wa' => '6282101000027', 'aktif' => 1],
            ['name' => 'Taufan Maulana, M.Kes.', 'jabatan' => 'Anggota Komisi IV', 'fraksi' => 'PKS', 'komisi' => 'Komisi IV', 'no_wa' => '6282101000028', 'aktif' => 1],
            ['name' => 'Hasan Basri, S.E.', 'jabatan' => 'Anggota Badan Anggaran', 'fraksi' => 'Hanura', 'komisi' => 'Komisi II', 'no_wa' => '6282101000029', 'aktif' => 1],
            ['name' => 'Eka Saputra', 'jabatan' => 'Anggota Bapemperda', 'fraksi' => 'PKB', 'komisi' => 'Komisi I', 'no_wa' => '6282101000030', 'aktif' => 1],
            ['name' => 'Mira Andini, S.H.', 'jabatan' => 'Anggota Badan Kehormatan', 'fraksi' => 'Amanat Nasional', 'komisi' => 'Komisi IV', 'no_wa' => '6282101000031', 'aktif' => 1],
            ['name' => 'Jamaluddin, S.T.', 'jabatan' => 'Anggota DPRD', 'fraksi' => 'Golongan Karya', 'komisi' => 'Komisi III', 'no_wa' => '6282101000032', 'aktif' => 1],
        ];
    }

    private function syncUnitMembers(): void
    {
        if (! $this->db->tableExists('anggota_unit_rapat')) {
            return;
        }

        $members = $this->db->table('anggota')
            ->select('id, no_wa, komisi')
            ->where('aktif', 1)
            ->get()
            ->getResultArray();

        $unitsByName = array_column(
            $this->db->table('unit_rapat')->select('id, nama')->get()->getResultArray(),
            'id',
            'nama'
        );

        $memberIdsByPhone = array_column($members, 'id', 'no_wa');
        $now = date('Y-m-d H:i:s');

        $unitMembers = [
            'Seluruh Anggota' => array_column($members, 'id'),
            'Badan Musyawarah' => ['6282101000001', '6282101000002', '6282101000003', '6282101000004', '6282101000005', '6282101000011', '6282101000017', '6282101000023'],
            'Badan Anggaran' => ['6282101000001', '6282101000011', '6282101000012', '6282101000017', '6282101000023', '6282101000029'],
            'Bapemperda' => ['6282101000003', '6282101000007', '6282101000013', '6282101000025', '6282101000030'],
            'Badan Kehormatan' => ['6282101000004', '6282101000009', '6282101000015', '6282101000024', '6282101000031'],
            'Gabungan Komisi' => ['6282101000005', '6282101000011', '6282101000017', '6282101000023', '6282101000029', '6282101000032'],
            'Pansus Ranperda Pajak Daerah' => ['6282101000006', '6282101000012', '6282101000018', '6282101000024', '6282101000029'],
            'Pansus Tata Tertib DPRD' => ['6282101000007', '6282101000013', '6282101000019', '6282101000025', '6282101000030'],
            'Tim Pembahas RAPBD' => ['6282101000011', '6282101000014', '6282101000017', '6282101000020', '6282101000023'],
            'Tim Kunjungan Kerja' => ['6282101000008', '6282101000015', '6282101000021', '6282101000026', '6282101000032'],
        ];

        foreach ($members as $member) {
            $komisi = trim((string) $member['komisi']);
            if ($komisi !== '') {
                $unitMembers[$komisi][] = (int) $member['id'];
            }
        }

        foreach ($unitMembers as $unitName => $membersOrPhones) {
            if (! isset($unitsByName[$unitName])) {
                continue;
            }

            $unitId = (int) $unitsByName[$unitName];
            foreach (array_unique($membersOrPhones) as $memberOrPhone) {
                $memberId = is_numeric($memberOrPhone) && (int) $memberOrPhone < 1000000000
                    ? (int) $memberOrPhone
                    : (int) ($memberIdsByPhone[(string) $memberOrPhone] ?? 0);

                if ($memberId <= 0) {
                    continue;
                }

                $this->insertPivotIfMissing('anggota_unit_rapat', [
                    'anggota_id'    => $memberId,
                    'unit_rapat_id' => $unitId,
                    'created_at'    => $now,
                ], ['anggota_id', 'unit_rapat_id']);
            }
        }
    }

    private function seedSchedules(): void
    {
        $roomsByName = array_column(
            $this->db->table('ruangan')->select('id, name')->get()->getResultArray(),
            'id',
            'name'
        );
        $unitsByName = array_column(
            $this->db->table('unit_rapat')->select('id, nama')->get()->getResultArray(),
            'id',
            'nama'
        );

        $templates = $this->scheduleTemplates();
        $startDate = new \DateTimeImmutable('first day of -2 months');
        $endDate = new \DateTimeImmutable('last day of +2 months');
        $days = $startDate->diff($endDate)->days + 1;
        $created = date('Y-m-d H:i:s');

        for ($dayIndex = 0; $dayIndex < $days; $dayIndex++) {
            $date = $startDate->modify('+' . $dayIndex . ' days')->format('Y-m-d');
            $dailyCount = $dayIndex % 5 === 0 ? 3 : ($dayIndex % 2 === 0 ? 2 : 1);

            for ($slot = 0; $slot < $dailyCount; $slot++) {
                $template = $templates[($dayIndex + $slot) % count($templates)];
                $schedule = $this->buildScheduleRow($template, $date, $slot, $roomsByName, $created);
                $scheduleId = $this->insertOrUpdateSchedule($schedule);

                foreach ($template['units'] as $unitName) {
                    if (! isset($unitsByName[$unitName])) {
                        continue;
                    }

                    $this->insertPivotIfMissing('jadwal_umum_unit_rapat', [
                        'jadwal_umum_id' => $scheduleId,
                        'unit_rapat_id' => (int) $unitsByName[$unitName],
                        'created_at'    => $created,
                    ], ['jadwal_umum_id', 'unit_rapat_id']);
                }
            }
        }
    }

    private function scheduleTemplates(): array
    {
        return [
            ['title' => 'Rapat Badan Musyawarah penetapan agenda persidangan', 'unit' => 'Badan Musyawarah', 'units' => ['Badan Musyawarah'], 'room' => 'Ruang Badan Musyawarah', 'jenis' => 'reguler', 'public' => 1],
            ['title' => 'Rapat kerja Komisi I terkait evaluasi pelayanan pemerintahan', 'unit' => 'Komisi I', 'units' => ['Komisi I'], 'room' => 'Ruang Komisi I', 'jenis' => 'reguler', 'public' => 1],
            ['title' => 'Rapat kerja Komisi II pembahasan realisasi pendapatan daerah', 'unit' => 'Komisi II', 'units' => ['Komisi II'], 'room' => 'Ruang Komisi II', 'jenis' => 'reguler', 'public' => 1],
            ['title' => 'Rapat kerja Komisi III progres infrastruktur wilayah', 'unit' => 'Komisi III', 'units' => ['Komisi III'], 'room' => 'Ruang Komisi III', 'jenis' => 'reguler', 'public' => 1],
            ['title' => 'Rapat kerja Komisi IV bidang pendidikan dan kesehatan', 'unit' => 'Komisi IV', 'units' => ['Komisi IV'], 'room' => 'Ruang Komisi IV', 'jenis' => 'reguler', 'public' => 1],
            ['title' => 'Rapat Badan Anggaran pembahasan rancangan KUA PPAS', 'unit' => 'Badan Anggaran', 'units' => ['Badan Anggaran'], 'room' => 'Ruang Badan Anggaran', 'jenis' => 'reguler', 'public' => 1],
            ['title' => 'Rapat Bapemperda harmonisasi rancangan peraturan daerah', 'unit' => 'Bapemperda', 'units' => ['Bapemperda'], 'room' => 'Ruang Bapemperda', 'jenis' => 'reguler', 'public' => 1],
            ['title' => 'Rapat Badan Kehormatan telaah agenda pengawasan internal', 'unit' => 'Badan Kehormatan', 'units' => ['Badan Kehormatan'], 'room' => 'Ruang Badan Kehormatan', 'jenis' => 'insidental', 'public' => 0],
            ['title' => 'Rapat gabungan komisi pembahasan aspirasi masyarakat', 'unit' => 'Gabungan Komisi', 'units' => ['Gabungan Komisi', 'Komisi I', 'Komisi III'], 'room' => 'Ruang Rapat Utama', 'jenis' => 'insidental', 'public' => 1],
            ['title' => 'Rapat Pansus Ranperda Pajak Daerah dengan mitra kerja', 'unit' => 'Pansus Ranperda Pajak Daerah', 'units' => ['Pansus Ranperda Pajak Daerah'], 'room' => 'Ruang Pansus', 'jenis' => 'reguler', 'public' => 1],
            ['title' => 'Rapat Pansus Tata Tertib DPRD pembahasan matriks usulan', 'unit' => 'Pansus Tata Tertib DPRD', 'units' => ['Pansus Tata Tertib DPRD'], 'room' => 'Ruang Pansus', 'jenis' => 'reguler', 'public' => 1],
            ['title' => 'Rapat Tim Pembahas RAPBD konsolidasi materi pembahasan', 'unit' => 'Tim Pembahas RAPBD', 'units' => ['Tim Pembahas RAPBD'], 'room' => 'Ruang Badan Anggaran', 'jenis' => 'reguler', 'public' => 0],
            ['title' => 'Rapat koordinasi persiapan kunjungan kerja DPRD', 'unit' => 'Tim Kunjungan Kerja', 'units' => ['Tim Kunjungan Kerja'], 'room' => 'Ruang Rapat Sekretariat', 'jenis' => 'insidental', 'public' => 0],
            ['title' => 'Rapat paripurna penyampaian laporan alat kelengkapan DPRD', 'unit' => 'Seluruh Anggota', 'units' => ['Seluruh Anggota'], 'room' => 'Ruang Rapat Paripurna', 'jenis' => 'reguler', 'public' => 1],
        ];
    }

    private function buildScheduleRow(array $template, string $date, int $slot, array $roomsByName, string $created): array
    {
        $timeSlots = [
            ['09:00:00', '10:30:00'],
            ['11:00:00', '12:30:00'],
            ['13:30:00', '15:00:00'],
        ];
        [$start, $end] = $timeSlots[$slot] ?? $timeSlots[0];

        $row = [
            'judul'         => $template['title'],
            'keterangan'    => 'Agenda ' . $template['unit'] . ' untuk pembahasan dan koordinasi tindak lanjut program kerja DPRD.',
            'tanggal'       => $date,
            'waktu_mulai'   => $start,
            'waktu_selesai' => $end,
            'ruangan_id'    => $roomsByName[$template['room']] ?? null,
            'is_publik'     => $template['public'],
            'pihak_eksternal' => null,
        ];

        if ($this->db->fieldExists('lokasi_lainnya', 'jadwal_umum')) {
            $row['lokasi_lainnya'] = null;
        }

        if ($this->db->fieldExists('created_at', 'jadwal_umum')) {
            $row['created_at'] = $created;
            $row['updated_at'] = $created;
        }

        return $row;
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'agenda';
    }

    private function insertOrUpdateSchedule(array $row): int
    {
        $existing = $this->db->table('jadwal_umum')
            ->select('id')
            ->where('judul', $row['judul'])
            ->where('tanggal', $row['tanggal'])
            ->where('waktu_mulai', $row['waktu_mulai'])
            ->get()
            ->getRowArray();

        if ($existing) {
            $this->db->table('jadwal_umum')
                ->where('id', $existing['id'])
                ->update($row);

            return (int) $existing['id'];
        }

        $this->db->table('jadwal_umum')->insert($row);

        return (int) $this->db->insertID();
    }

    private function insertPivotIfMissing(string $table, array $row, array $uniqueFields): void
    {
        $builder = $this->db->table($table);
        foreach ($uniqueFields as $field) {
            $builder->where($field, $row[$field]);
        }

        if ($builder->countAllResults() > 0) {
            return;
        }

        $this->db->table($table)->insert($row);
    }
}
