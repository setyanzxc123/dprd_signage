<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BamusMasaPersidanganKetiga2026Seeder extends Seeder
{
    private const SOURCE_NOTE = 'Sumber: Keputusan Pimpinan DPRD Provinsi Sulawesi Tengah Nomor 160/9/2026 tanggal 22 Mei 2026.';
    private const SAMPLE_MEMBER_PHONE = '085156049890';

    public function run(): void
    {
        $this->insertOrUpdate('ruangan', 'name', $this->rooms());
        $this->insertOrUpdate('unit_rapat', 'nama', $this->meetingUnits());
        $this->insertOrUpdate('anggota', 'no_wa', $this->members());
        $this->syncSampleMemberUnits();
        $this->seedSchedules();
    }

    private function insertOrUpdate(string $table, string $uniqueField, array $rows): void
    {
        foreach ($rows as $row) {
            $existing = $this->db->table($table)
                ->select('id')
                ->where($uniqueField, $row[$uniqueField])
                ->get()
                ->getRowArray();
            $row = $this->onlyExistingFields($table, $row);

            if ($existing) {
                $this->db->table($table)
                    ->where('id', $existing['id'])
                    ->update($row);
                continue;
            }

            $this->db->table($table)->insert($row);
        }
    }

    private function onlyExistingFields(string $table, array $row): array
    {
        return array_filter(
            $row,
            fn (string $field): bool => $this->db->fieldExists($field, $table),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function rooms(): array
    {
        return [
            ['name' => 'Ruang Rapat Paripurna', 'keterangan' => 'Ruang utama untuk rapat paripurna DPRD.', 'kapasitas' => 120, 'lantai' => null, 'tersedia' => 1],
            ['name' => 'Ruang Badan Musyawarah', 'keterangan' => 'Ruang rapat Badan Musyawarah.', 'kapasitas' => 45, 'lantai' => null, 'tersedia' => 1],
            ['name' => 'Ruang Badan Anggaran', 'keterangan' => 'Ruang rapat Badan Anggaran.', 'kapasitas' => 55, 'lantai' => null, 'tersedia' => 1],
            ['name' => 'Ruang Rapat Utama', 'keterangan' => 'Ruang rapat gabungan DPRD.', 'kapasitas' => 70, 'lantai' => null, 'tersedia' => 1],
            ['name' => 'Ruang Komisi', 'keterangan' => 'Ruang rapat komisi dan alat kelengkapan dewan.', 'kapasitas' => 40, 'lantai' => null, 'tersedia' => 1],
            ['name' => 'Ruang Pansus', 'keterangan' => 'Ruang rapat panitia khusus.', 'kapasitas' => 44, 'lantai' => null, 'tersedia' => 1],
        ];
    }

    private function meetingUnits(): array
    {
        $now = date('Y-m-d H:i:s');
        $names = [
            10 => 'Seluruh Anggota',
            20 => 'Badan Musyawarah',
            30 => 'Badan Anggaran',
            40 => 'Bapemperda',
            50 => 'Badan Kehormatan',
            60 => 'Pansus',
            70 => 'Pimpinan DPRD',
            80 => 'Ketua Fraksi',
            90 => 'Fraksi',
            100 => 'Alat Kelengkapan Dewan',
            110 => 'Komisi I',
            120 => 'Komisi II',
            130 => 'Komisi III',
            140 => 'Komisi IV',
        ];

        $rows = [];
        foreach ($names as $order => $name) {
            $rows[] = [
                'nama'       => $name,
                'aktif'      => 1,
                'urutan'     => $order,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    private function members(): array
    {
        return [[
            'name'    => 'Anggota Contoh Bamus',
            'jabatan' => 'Anggota DPRD',
            'fraksi'  => 'Golongan Karya',
            'komisi'  => 'Komisi I',
            'no_wa'   => self::SAMPLE_MEMBER_PHONE,
            'aktif'   => 1,
            'foto'    => null,
        ]];
    }

    private function syncSampleMemberUnits(): void
    {
        if (! $this->db->tableExists('anggota_unit_rapat')) {
            return;
        }

        $member = $this->db->table('anggota')
            ->select('id')
            ->where('no_wa', self::SAMPLE_MEMBER_PHONE)
            ->get()
            ->getRowArray();

        if (! $member) {
            return;
        }

        $unitIds = array_column(
            $this->db->table('unit_rapat')
                ->select('id')
                ->whereIn('nama', $this->allUnitNames())
                ->get()
                ->getResultArray(),
            'id'
        );

        $now = date('Y-m-d H:i:s');
        foreach ($unitIds as $unitId) {
            $this->insertPivotIfMissing('anggota_unit_rapat', [
                'anggota_id'    => (int) $member['id'],
                'unit_rapat_id' => (int) $unitId,
                'created_at'    => $now,
            ], ['anggota_id', 'unit_rapat_id']);
        }
    }

    private function seedSchedules(): void
    {
        if (! $this->db->tableExists('jadwal_unit_rapat')) {
            return;
        }

        $this->deleteExistingBamusSchedules();

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

        foreach ($this->schedules() as $schedule) {
            $row = $this->buildScheduleRow($schedule, $roomsByName);
            $scheduleId = $this->insertOrUpdateSchedule($row);
            $this->syncScheduleUnits($scheduleId, $schedule['units'], $unitsByName);
        }
    }

    private function deleteExistingBamusSchedules(): void
    {
        $rows = $this->db->table('jadwal')
            ->select('id')
            ->like('keterangan', self::SOURCE_NOTE, 'after')
            ->get()
            ->getResultArray();

        $ids = array_map('intval', array_column($rows, 'id'));
        if (empty($ids)) {
            return;
        }

        $this->db->table('jadwal_unit_rapat')->whereIn('jadwal_id', $ids)->delete();
        $this->db->table('jadwal')->whereIn('id', $ids)->delete();
    }

    private function schedules(): array
    {
        return array_merge($this->nearNowSchedules(), $this->bamusSchedules());
    }

    private function nearNowSchedules(): array
    {
        $now = new \DateTimeImmutable('now');
        $recent = $this->meetingSlot($now->modify('-90 minutes'), 45);
        $current = $this->meetingSlot($now->modify('-15 minutes'), 60);
        $soon = $this->meetingSlot($now->modify('+20 minutes'), 75);
        $next = $this->meetingSlot($now->modify('+50 minutes'), 60);
        $later = $this->meetingSlot($now->modify('+2 hours'), 90);
        $lateToday = $this->meetingSlot($now->modify('+4 hours'), 75);

        return [
            ['date' => $recent['date'], 'start' => $recent['start'], 'end' => $recent['end'], 'title' => 'Rapat Komisi Evaluasi Singkat Agenda Hari Ini', 'period' => 'Contoh rapat dekat waktu sekarang', 'units' => ['Komisi I', 'Komisi II', 'Komisi III', 'Komisi IV'], 'room' => 'Ruang Komisi', 'location' => null, 'jenis' => 'reguler', 'public' => 0],
            ['date' => $current['date'], 'start' => $current['start'], 'end' => $current['end'], 'title' => 'Rapat Badan Musyawarah Monitoring Agenda Hari Ini', 'period' => 'Contoh rapat dekat waktu sekarang', 'units' => ['Badan Musyawarah'], 'room' => 'Ruang Badan Musyawarah', 'location' => null, 'jenis' => 'bamus', 'public' => 0],
            ['date' => $soon['date'], 'start' => $soon['start'], 'end' => $soon['end'], 'title' => 'Rapat Paripurna Persiapan Agenda Terdekat', 'period' => 'Contoh rapat dekat waktu sekarang', 'units' => ['Seluruh Anggota'], 'room' => 'Ruang Rapat Paripurna', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => $next['date'], 'start' => $next['start'], 'end' => $next['end'], 'title' => 'Rapat Pimpinan dan Ketua Fraksi Sinkronisasi Jadwal', 'period' => 'Contoh rapat dekat waktu sekarang', 'units' => ['Pimpinan DPRD', 'Ketua Fraksi'], 'room' => 'Ruang Rapat Utama', 'location' => null, 'jenis' => 'reguler', 'public' => 0],
            ['date' => $later['date'], 'start' => $later['start'], 'end' => $later['end'], 'title' => 'Rapat Badan Anggaran Pembahasan Lanjutan Hari Ini', 'period' => 'Contoh rapat dekat waktu sekarang', 'units' => ['Badan Anggaran'], 'room' => 'Ruang Badan Anggaran', 'location' => null, 'jenis' => 'reguler', 'public' => 0],
            ['date' => $lateToday['date'], 'start' => $lateToday['start'], 'end' => $lateToday['end'], 'title' => 'Rapat Pansus Finalisasi Catatan Agenda Hari Ini', 'period' => 'Contoh rapat dekat waktu sekarang', 'units' => ['Pansus'], 'room' => 'Ruang Pansus', 'location' => null, 'jenis' => 'reguler', 'public' => 0],
        ];
    }

    private function meetingSlot(\DateTimeImmutable $start, int $durationMinutes): array
    {
        $end = $start->modify('+' . $durationMinutes . ' minutes');

        return [
            'date'  => $start->format('Y-m-d'),
            'start' => $start->format('H:i:00'),
            'end'   => $end->format('H:i:00'),
        ];
    }

    private function bamusSchedules(): array
    {
        return [
            ['date' => '2026-06-04', 'start' => '09:00:00', 'end' => '11:00:00', 'title' => 'Rapat Paripurna Penutupan Masa Persidangan Ke-II dan Pembukaan Masa Persidangan Ke-III Tahun Kedua', 'period' => 'Selasa, 2 Juni 2026', 'units' => ['Seluruh Anggota'], 'room' => 'Ruang Rapat Paripurna', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => '2026-06-05', 'start' => '13:00:00', 'end' => '15:00:00', 'title' => 'Rapat Paripurna Penyampaian Laporan Hasil Pemeriksaan BPK', 'period' => 'Selasa, 2 Juni 2026', 'units' => ['Seluruh Anggota'], 'room' => 'Ruang Rapat Paripurna', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => '2026-06-09', 'start' => '09:00:00', 'end' => '11:00:00', 'title' => 'Rapat Paripurna Pengumuman Perubahan Komposisi AKD dan Pembubaran serta Pembentukan Pansus', 'period' => 'Rabu, 3 Juni 2026', 'units' => ['Seluruh Anggota', 'Alat Kelengkapan Dewan', 'Pansus'], 'room' => 'Ruang Rapat Paripurna', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => '2026-06-08', 'start' => '09:00:00', 'end' => '11:00:00', 'title' => 'Rapat Paripurna Penyampaian Laporan Hasil Koordinasi dan Komunikasi Masa Persidangan Ke-II Tahun Kedua', 'period' => 'Juni 2026', 'units' => ['Seluruh Anggota'], 'room' => 'Ruang Rapat Paripurna', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => '2026-06-10', 'start' => '10:00:00', 'end' => '12:00:00', 'title' => 'Rapat Komisi dengan Mitra Kerja dalam rangka Koordinasi dan Komunikasi Dalam Daerah dan Antar Daerah', 'period' => 'Juni 2026', 'units' => ['Komisi I', 'Komisi II', 'Komisi III', 'Komisi IV'], 'room' => 'Ruang Komisi', 'location' => null, 'jenis' => 'reguler', 'public' => 0],
            ['date' => '2026-06-18', 'start' => '13:30:00', 'end' => '15:30:00', 'title' => 'Rapat Kerja Pimpinan, Ketua Fraksi, dan Alat Kelengkapan Dewan', 'period' => 'Juni 2026', 'units' => ['Pimpinan DPRD', 'Ketua Fraksi', 'Alat Kelengkapan Dewan'], 'room' => 'Ruang Rapat Utama', 'location' => null, 'jenis' => 'reguler', 'public' => 0],
            ['date' => '2026-06-24', 'start' => '09:00:00', 'end' => '11:00:00', 'title' => 'Rapat Paripurna dan Rapat Badan Anggaran Pembahasan Pertanggungjawaban Pelaksanaan APBD Tahun Anggaran 2025', 'period' => 'Juni - Juli 2026', 'units' => ['Seluruh Anggota', 'Badan Anggaran'], 'room' => 'Ruang Badan Anggaran', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => '2026-07-03', 'start' => '10:00:00', 'end' => '12:00:00', 'title' => 'Rapat Penyusunan, Pembahasan, dan Penetapan RENJA DPRD Sulawesi Tengah Tahun 2027', 'period' => 'Juni - Juli 2026', 'units' => ['Badan Musyawarah', 'Alat Kelengkapan Dewan'], 'room' => 'Ruang Badan Musyawarah', 'location' => null, 'jenis' => 'reguler', 'public' => 0],
            ['date' => '2026-07-14', 'start' => '09:00:00', 'end' => '11:00:00', 'title' => 'Rapat Pembahasan dan Penetapan Rancangan Peraturan Daerah Provinsi Sulawesi Tengah Tahun 2026', 'period' => 'Juni - September 2026', 'units' => ['Bapemperda', 'Pansus'], 'room' => 'Ruang Pansus', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => '2026-07-28', 'start' => '13:30:00', 'end' => '15:30:00', 'title' => 'Rapat Pembahasan Raperda, AKD, Fraksi, dan Agenda Kedewanan Lainnya', 'period' => 'Juni - September 2026', 'units' => ['Bapemperda', 'Alat Kelengkapan Dewan', 'Fraksi'], 'room' => 'Ruang Rapat Utama', 'location' => null, 'jenis' => 'reguler', 'public' => 0],
            ['date' => '2026-08-11', 'start' => '09:00:00', 'end' => '11:00:00', 'title' => 'Rapat Paripurna Pembahasan Rekomendasi Panitia Khusus DPRD', 'period' => 'Juni - September 2026', 'units' => ['Seluruh Anggota', 'Pansus'], 'room' => 'Ruang Rapat Paripurna', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => '2026-07-21', 'start' => '09:00:00', 'end' => '11:00:00', 'title' => 'Rapat Paripurna Penyampaian Laporan Semester Pertama Tahun 2026', 'period' => 'Juli 2026', 'units' => ['Seluruh Anggota'], 'room' => 'Ruang Rapat Paripurna', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => '2026-08-04', 'start' => '09:00:00', 'end' => '11:00:00', 'title' => 'Rapat Paripurna dan Rapat Badan Anggaran Pembahasan KUA dan PPAS Perubahan Tahun Anggaran 2026', 'period' => 'Juli - Agustus 2026', 'units' => ['Seluruh Anggota', 'Badan Anggaran'], 'room' => 'Ruang Badan Anggaran', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => '2026-08-13', 'start' => '13:30:00', 'end' => '15:30:00', 'title' => 'Rapat Paripurna dan Rapat Badan Anggaran Pembahasan KUA dan PPAS Tahun 2027', 'period' => 'Juli - Agustus 2026', 'units' => ['Seluruh Anggota', 'Badan Anggaran'], 'room' => 'Ruang Badan Anggaran', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => '2026-09-03', 'start' => '09:00:00', 'end' => '11:00:00', 'title' => 'Rapat Paripurna Pembahasan dan Penetapan RAPBD Perubahan Tahun Anggaran 2026', 'period' => 'Agustus - September 2026', 'units' => ['Seluruh Anggota', 'Badan Anggaran'], 'room' => 'Ruang Rapat Paripurna', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
            ['date' => '2026-09-16', 'start' => '09:00:00', 'end' => '11:00:00', 'title' => 'Rapat Badan Musyawarah Pembahasan dan Penetapan Jadwal Masa Persidangan Ke-I Tahun Ketiga', 'period' => 'Senin, 21 September 2026', 'units' => ['Badan Musyawarah'], 'room' => 'Ruang Badan Musyawarah', 'location' => null, 'jenis' => 'bamus', 'public' => 0],
            ['date' => '2026-09-24', 'start' => '09:00:00', 'end' => '11:00:00', 'title' => 'Rapat Paripurna Penutupan Masa Persidangan Ke-III Tahun Kedua dan Pembukaan Masa Persidangan Ke-I Tahun Ketiga', 'period' => 'Selasa, 22 September 2026', 'units' => ['Seluruh Anggota'], 'room' => 'Ruang Rapat Paripurna', 'location' => null, 'jenis' => 'reguler', 'public' => 1],
        ];
    }

    private function buildScheduleRow(array $schedule, array $roomsByName): array
    {
        $dateTime = $schedule['date'] . ' ' . $schedule['start'];
        $row = [
            'judul'         => $schedule['title'],
            'keterangan'    => self::SOURCE_NOTE . ' Periode pelaksanaan pada dokumen: ' . $schedule['period'],
            'tanggal'       => $schedule['date'],
            'waktu_mulai'   => $schedule['start'],
            'waktu_selesai' => $schedule['end'],
            'ruangan_id'    => $schedule['room'] !== null ? ($roomsByName[$schedule['room']] ?? null) : null,
            'blast_before'  => 60,
            'reminder_time' => date('Y-m-d H:i:s', strtotime($dateTime . ' -60 minutes')),
            'status'        => $this->statusFor($schedule['date'], $schedule['start'], $schedule['end']),
            'materi_url'    => null,
        ];

        $this->putIfFieldExists($row, 'jadwal', 'lokasi_lainnya', $schedule['location']);
        $this->putIfFieldExists($row, 'jadwal', 'stream_url', null);
        $this->putIfFieldExists($row, 'jadwal', 'is_publik', (int) $schedule['public']);
        $this->putIfFieldExists($row, 'jadwal', 'jenis', $schedule['jenis']);

        return $row;
    }

    private function statusFor(string $date, string $start, string $end): string
    {
        $today = date('Y-m-d');
        $now = date('H:i:s');

        if ($date < $today || ($date === $today && $end <= $now)) {
            return 'selesai';
        }

        if ($date === $today && $start <= $now && $end > $now) {
            return 'berlangsung';
        }

        if ($date === $today && date('H:i:s', strtotime($start . ' -30 minutes')) <= $now && $start > $now) {
            return 'persiapan';
        }

        return 'menunggu';
    }

    private function putIfFieldExists(array &$row, string $table, string $field, mixed $value): void
    {
        if ($this->db->fieldExists($field, $table)) {
            $row[$field] = $value;
        }
    }

    private function insertOrUpdateSchedule(array $row): int
    {
        $existing = $this->db->table('jadwal')
            ->select('id')
            ->where('judul', $row['judul'])
            ->where('tanggal', $row['tanggal'])
            ->where('waktu_mulai', $row['waktu_mulai'])
            ->get()
            ->getRowArray();

        if ($existing) {
            $this->db->table('jadwal')
                ->where('id', $existing['id'])
                ->update($row);

            return (int) $existing['id'];
        }

        $this->db->table('jadwal')->insert($row);

        return (int) $this->db->insertID();
    }

    private function syncScheduleUnits(int $scheduleId, array $unitNames, array $unitsByName): void
    {
        $this->db->table('jadwal_unit_rapat')
            ->where('jadwal_id', $scheduleId)
            ->delete();

        $now = date('Y-m-d H:i:s');
        foreach (array_unique($unitNames) as $unitName) {
            if (! isset($unitsByName[$unitName])) {
                continue;
            }

            $this->insertPivotIfMissing('jadwal_unit_rapat', [
                'jadwal_id'     => $scheduleId,
                'unit_rapat_id' => (int) $unitsByName[$unitName],
                'created_at'    => $now,
            ], ['jadwal_id', 'unit_rapat_id']);
        }
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

    private function allUnitNames(): array
    {
        return array_map(
            static fn (array $row): string => $row['nama'],
            $this->meetingUnits()
        );
    }
}