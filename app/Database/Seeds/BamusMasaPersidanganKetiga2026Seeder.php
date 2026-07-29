<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class BamusMasaPersidanganKetiga2026Seeder extends Seeder
{
    private const SK_NUMBER = '160/9/2026';
    private const SK_YEAR = 2026;
    private const SK_SEMESTER = 2;
    private const NEXT_SK_NUMBER = 'DUMMY/1/2027';
    private const LEGACY_SOURCE_NOTE = 'Sumber: Keputusan Pimpinan DPRD Provinsi Sulawesi Tengah Nomor 160/9/2026 tanggal 22 Mei 2026.';
    private const DUMMY_SCHEDULE_NOTE = 'Data contoh rapat dekat waktu sekarang dari BamusMasaPersidanganKetiga2026Seeder.';
    private const GENERAL_DUMMY_NOTE = 'Data contoh Jadwal Umum dari BamusMasaPersidanganKetiga2026Seeder.';
    private const SOURCE_PDF = '9. PENETAPAN JADWAL BANMUS MASA PERSIDANGAN KE-III TAHUN KEDUA NO. 9  TGL 22 MEI 2026 (SALINAN) (1).pdf';
    private const SAMPLE_MEMBER_PHONE = '085156049890';

    public function run(): void
    {
        $this->assertCurrentSchema();
        $this->insertOrUpdate('ruangan', 'name', $this->rooms());
        $this->insertOrUpdate('unit_rapat', 'nama', $this->meetingUnits());
        $this->insertOrUpdate('anggota', 'no_wa', $this->members());
        $this->syncSampleMemberUnits();
        $this->seedDefinitiveScheduleExamples();
        $this->seedGeneralAgendaExamples();
        $this->seedBanmusDocument();
        $this->seedNextSemesterProjection();
    }

    private function assertCurrentSchema(): void
    {
        foreach (['dokumen_banmus', 'jadwal_banmus', 'agenda_umum'] as $table) {
            if (! $this->db->tableExists($table)) {
                throw new RuntimeException(
                    "Tabel {$table} belum tersedia. Jalankan `php spark migrate` sebelum menjalankan seeder."
                );
            }
        }

        $requiredDocumentFields = [
            'nomor_sk',
            'tahun',
            'semester',
            'dokumen_file',
            'dokumen_nama_asli',
        ];
        $requiredScheduleFields = [
            'dokumen_banmus_id',
            'agenda',
            'periode_label',
            'urutan',
            'catatan',
        ];

        foreach ($requiredDocumentFields as $field) {
            if (! $this->db->fieldExists($field, 'dokumen_banmus')) {
                throw new RuntimeException("Kolom dokumen_banmus.{$field} belum tersedia.");
            }
        }
        foreach ($requiredScheduleFields as $field) {
            if (! $this->db->fieldExists($field, 'jadwal_banmus')) {
                throw new RuntimeException("Kolom jadwal_banmus.{$field} belum tersedia.");
            }
        }

        foreach (['judul', 'kategori', 'tanggal', 'waktu_mulai', 'lokasi', 'keterangan', 'status', 'is_publik'] as $field) {
            if (! $this->db->fieldExists($field, 'agenda_umum')) {
                throw new RuntimeException("Kolom agenda_umum.{$field} belum tersedia.");
            }
        }
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

        // Akun contoh hanya ditempatkan pada satu unit agar perbedaan
        // "Semua Jadwal" dan "Jadwal Saya" terlihat saat demo.
        $unitIds = array_column(
            $this->db->table('unit_rapat')
                ->select('id')
                ->where('nama', 'Komisi I')
                ->get()
                ->getResultArray(),
            'id'
        );

        $now = date('Y-m-d H:i:s');
        $this->db->table('anggota_unit_rapat')
            ->where('anggota_id', (int) $member['id'])
            ->delete();
        foreach ($unitIds as $unitId) {
            $this->insertPivotIfMissing('anggota_unit_rapat', [
                'anggota_id'    => (int) $member['id'],
                'unit_rapat_id' => (int) $unitId,
                'created_at'    => $now,
            ], ['anggota_id', 'unit_rapat_id']);
        }
    }

    private function seedDefinitiveScheduleExamples(): void
    {
        if (! $this->db->tableExists('jadwal_unit_rapat')) {
            return;
        }

        $this->deleteExistingSeederSchedules();

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

        $schedules = array_merge(
            $this->nearNowSchedules(),
            $this->rapatBamusScheduleExamples(),
        );

        foreach ($schedules as $schedule) {
            $row = $this->buildScheduleRow($schedule, $roomsByName);
            $scheduleId = $this->insertOrUpdateSchedule($row);
            $this->syncScheduleUnits($scheduleId, $schedule['units'], $unitsByName);
        }
    }

    private function deleteExistingSeederSchedules(): void
    {
        $ids = [];
        foreach ([self::LEGACY_SOURCE_NOTE, self::DUMMY_SCHEDULE_NOTE] as $note) {
            $rows = $this->db->table('jadwal')
                ->select('id')
                ->like('keterangan', $note, 'after')
                ->get()
                ->getResultArray();
            $ids = array_merge($ids, array_map('intval', array_column($rows, 'id')));
        }
        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            return;
        }

        $this->db->table('jadwal_unit_rapat')->whereIn('jadwal_id', $ids)->delete();
        $this->db->table('jadwal')->whereIn('id', $ids)->delete();
    }

    private function seedBanmusDocument(): void
    {
        [$storedFileName, $originalFileName] = $this->storeSourcePdf();
        $this->upsertBanmusDocument([
            'judul'                => 'Jadwal Banmus Semester 2 Tahun 2026',
            'nomor_sk'             => self::SK_NUMBER,
            'tahun'                => self::SK_YEAR,
            'semester'             => self::SK_SEMESTER,
            'dokumen_file'         => $storedFileName,
            'dokumen_nama_asli'    => $originalFileName,
        ], $this->banmusRows());
    }

    private function seedNextSemesterProjection(): void
    {
        [$storedFileName, $originalFileName] = $this->storePlaceholderPdf(2027, 1);
        $this->upsertBanmusDocument([
            'judul'                => 'Jadwal Banmus Semester 1 Tahun 2027 (Dummy)',
            'nomor_sk'             => self::NEXT_SK_NUMBER,
            'tahun'                => 2027,
            'semester'             => 1,
            'dokumen_file'         => $storedFileName,
            'dokumen_nama_asli'    => $originalFileName,
        ], $this->nextSemesterRows());
    }

    /**
     * @param array<string, mixed> $document
     * @param list<array{tanggal_pelaksanaan: string, uraian_kegiatan: string, keterangan: ?string}> $items
     */
    private function upsertBanmusDocument(array $document, array $items): void
    {
        $now = date('Y-m-d H:i:s');
        $document += [
            'tanggal_sk'       => null,
            'masa_persidangan' => null,
            'periode_mulai'   => null,
            'periode_selesai' => null,
            'status'           => 'disahkan',
            'is_publik'        => 1,
            'dokumen_url'      => null,
            'catatan'          => null,
            'updated_at'       => $now,
        ];

        $this->db->transBegin();
        try {
            $existing = $this->db->table('dokumen_banmus')
                ->select('id')
                ->where('nomor_sk', $document['nomor_sk'])
                ->get()
                ->getRowArray();

            if ($existing) {
                $documentId = (int) $existing['id'];
                $this->db->table('dokumen_banmus')
                    ->where('id', $documentId)
                    ->update($document);
                $this->db->table('jadwal_banmus')
                    ->where('dokumen_banmus_id', $documentId)
                    ->delete();
            } else {
                $document['created_at'] = $now;
                $this->db->table('dokumen_banmus')->insert($document);
                $documentId = (int) $this->db->insertID();
            }

            $rows = [];
            foreach ($items as $index => $item) {
                $rows[] = [
                    'dokumen_banmus_id' => $documentId,
                    'agenda'            => $item['uraian_kegiatan'],
                    'periode_label'     => $item['tanggal_pelaksanaan'],
                    'tanggal_mulai'     => null,
                    'tanggal_selesai'   => null,
                    'urutan'            => $index + 1,
                    'status'            => 'proyeksi',
                    'catatan'           => $item['keterangan'],
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }
            $this->db->table('jadwal_banmus')->insertBatch($rows);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi data contoh Jadwal Banmus gagal.');
            }

            $this->db->transCommit();
        } catch (\Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    private function seedGeneralAgendaExamples(): void
    {
        $this->db->table('agenda_umum')
            ->like('keterangan', self::GENERAL_DUMMY_NOTE, 'after')
            ->delete();

        $now = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($this->generalAgendaRows() as $item) {
            $rows[] = [
                ...$item,
                'keterangan' => self::GENERAL_DUMMY_NOTE . ' ' . $item['keterangan'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->table('agenda_umum')->insertBatch($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function generalAgendaRows(): array
    {
        return [
            ['judul' => 'Aksi Penyampaian Aspirasi Layanan Publik', 'kategori' => 'demonstrasi', 'tanggal' => '2026-07-27', 'waktu_mulai' => '09:00:00', 'waktu_selesai' => '11:00:00', 'lokasi' => 'Halaman Gedung DPRD', 'sumber_informasi' => 'Contoh agenda publik', 'perkiraan_peserta' => 120, 'keterangan' => 'Simulasi kegiatan insidental yang ditampilkan pada panel Jadwal Umum.', 'status' => 'terkonfirmasi', 'is_publik' => 1],
            ['judul' => 'Audiensi Publik Kelompok Masyarakat', 'kategori' => 'audiensi_publik', 'tanggal' => '2026-07-28', 'waktu_mulai' => '10:00:00', 'waktu_selesai' => '12:00:00', 'lokasi' => 'Ruang Rapat Utama', 'sumber_informasi' => 'Contoh agenda publik', 'perkiraan_peserta' => 35, 'keterangan' => 'Simulasi audiensi publik dengan pimpinan DPRD.', 'status' => 'tentatif', 'is_publik' => 1],
            ['judul' => 'Kunjungan Edukasi Siswa ke Gedung DPRD', 'kategori' => 'kunjungan', 'tanggal' => '2026-07-29', 'waktu_mulai' => '08:30:00', 'waktu_selesai' => '11:30:00', 'lokasi' => 'Gedung DPRD Provinsi Sulawesi Tengah', 'sumber_informasi' => 'Contoh agenda publik', 'perkiraan_peserta' => 80, 'keterangan' => 'Simulasi kunjungan edukasi masyarakat.', 'status' => 'terkonfirmasi', 'is_publik' => 1],
            ['judul' => 'Bakti Sosial dan Donor Darah DPRD', 'kategori' => 'kegiatan_sosial', 'tanggal' => '2026-07-30', 'waktu_mulai' => '08:00:00', 'waktu_selesai' => '13:00:00', 'lokasi' => 'Selasar Gedung DPRD', 'sumber_informasi' => 'Contoh agenda publik', 'perkiraan_peserta' => 150, 'keterangan' => 'Simulasi kegiatan sosial nonrapat.', 'status' => 'tentatif', 'is_publik' => 1],
            ['judul' => 'Pameran Informasi Program DPRD', 'kategori' => 'lainnya', 'tanggal' => '2026-07-31', 'waktu_mulai' => '09:00:00', 'waktu_selesai' => '15:00:00', 'lokasi' => 'Ruang Publik Gedung DPRD', 'sumber_informasi' => 'Contoh agenda publik', 'perkiraan_peserta' => 200, 'keterangan' => 'Simulasi kegiatan informasi publik untuk panel Jadwal Umum.', 'status' => 'tentatif', 'is_publik' => 1],
            ['judul' => 'Audiensi Komunitas dan Organisasi Kepemudaan', 'kategori' => 'audiensi_publik', 'tanggal' => '2026-08-03', 'waktu_mulai' => '13:00:00', 'waktu_selesai' => '15:00:00', 'lokasi' => 'Ruang Badan Musyawarah', 'sumber_informasi' => 'Contoh agenda publik', 'perkiraan_peserta' => 45, 'keterangan' => 'Simulasi agenda publik bulan berikutnya.', 'status' => 'tentatif', 'is_publik' => 1],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function storeSourcePdf(): array
    {
        $sourcePath = ROOTPATH . 'tes' . DIRECTORY_SEPARATOR . self::SOURCE_PDF;
        if (! is_file($sourcePath)) {
            throw new RuntimeException("PDF sumber data contoh tidak ditemukan: {$sourcePath}");
        }

        $hash = sha1_file($sourcePath);
        if (! is_string($hash) || $hash === '') {
            throw new RuntimeException('Hash PDF sumber tidak dapat dibuat.');
        }

        $uploadDirectory = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'sk-banmus';
        if (! is_dir($uploadDirectory)
            && ! mkdir($uploadDirectory, 0750, true)
            && ! is_dir($uploadDirectory)
        ) {
            throw new RuntimeException('Direktori penyimpanan SK Banmus tidak dapat dibuat.');
        }

        $storedFileName = $hash . '.pdf';
        $targetPath = $uploadDirectory . DIRECTORY_SEPARATOR . $storedFileName;
        if (! is_file($targetPath) && ! copy($sourcePath, $targetPath)) {
            throw new RuntimeException('PDF sumber tidak dapat disalin ke penyimpanan privat.');
        }

        return [$storedFileName, self::SOURCE_PDF];
    }

    /**
     * Membuat PDF kecil yang valid sebagai penanda dokumen proyeksi semester
     * depan. Dokumen ini sengaja diberi label dummy agar tidak disalahartikan
     * sebagai SK resmi.
     *
     * @return array{0: string, 1: string}
     */
    private function storePlaceholderPdf(int $year, int $semester): array
    {
        $uploadDirectory = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'sk-banmus';
        if (! is_dir($uploadDirectory)
            && ! mkdir($uploadDirectory, 0750, true)
            && ! is_dir($uploadDirectory)
        ) {
            throw new RuntimeException('Direktori penyimpanan SK Banmus tidak dapat dibuat.');
        }

        $stream = "BT /F1 16 Tf 72 720 Td (DOKUMEN CONTOH) Tj 0 -30 Td (SK Banmus Semester {$semester} Tahun {$year}) Tj 0 -30 Td (Data dummy untuk pratinjau aplikasi.) Tj ET";
        $objects = [
            "<< /Type /Catalog /Pages 2 0 R >>",
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
            "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($number + 1) . " 0 obj\n{$object}\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($index = 1; $index <= count($objects); $index++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        $hash = sha1($pdf);
        $storedFileName = $hash . '.pdf';
        $targetPath = $uploadDirectory . DIRECTORY_SEPARATOR . $storedFileName;
        if (! is_file($targetPath) && file_put_contents($targetPath, $pdf) === false) {
            throw new RuntimeException('PDF placeholder tidak dapat disimpan.');
        }

        return [$storedFileName, "Contoh SK Banmus Semester {$semester} Tahun {$year}.pdf"];
    }

    /**
     * @return list<array{tanggal_pelaksanaan: string, uraian_kegiatan: string, keterangan: string}>
     */
    private function nextSemesterRows(): array
    {
        $note = 'Data dummy proyeksi semester depan; belum merupakan SK resmi.';

        return [
            ['tanggal_pelaksanaan' => 'Januari 2027', 'uraian_kegiatan' => 'Rapat Paripurna Pembukaan Masa Persidangan Ke-II Tahun Ketiga.', 'keterangan' => $note],
            ['tanggal_pelaksanaan' => 'Januari 2027', 'uraian_kegiatan' => 'Rapat Komisi dengan Mitra Kerja dan penyusunan agenda prioritas.', 'keterangan' => $note],
            ['tanggal_pelaksanaan' => 'Februari 2027', 'uraian_kegiatan' => 'Rapat Pembahasan Rancangan Peraturan Daerah dan agenda alat kelengkapan dewan.', 'keterangan' => $note],
            ['tanggal_pelaksanaan' => 'Maret 2027', 'uraian_kegiatan' => 'Rapat Badan Anggaran Pembahasan KUA dan PPAS Tahun Anggaran 2028.', 'keterangan' => $note],
            ['tanggal_pelaksanaan' => 'April 2027', 'uraian_kegiatan' => 'Rapat Koordinasi dan Komunikasi Dalam Daerah serta Antar Daerah.', 'keterangan' => $note],
            ['tanggal_pelaksanaan' => 'Mei 2027', 'uraian_kegiatan' => 'Rapat Paripurna Penyampaian Laporan Pelaksanaan Program Semester.', 'keterangan' => $note],
            ['tanggal_pelaksanaan' => 'Juni 2027', 'uraian_kegiatan' => 'Rapat Badan Musyawarah Penetapan Jadwal Masa Persidangan Berikutnya.', 'keterangan' => $note],
        ];
    }

    /**
     * Teks mengikuti tiga kolom pada lampiran SK. Kolom KET pada dokumen sumber kosong.
     *
     * @return list<array{tanggal_pelaksanaan: string, uraian_kegiatan: string, keterangan: null}>
     */
    private function banmusRows(): array
    {
        return [
            [
                'tanggal_pelaksanaan' => 'Rabu, 27 Mei 2026',
                'uraian_kegiatan'     => 'Hari Raya Iduladha 1447 H',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Kamis, 28 Mei 2026',
                'uraian_kegiatan'     => 'Cuti bersama',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Senin, 1 Juni 2026',
                'uraian_kegiatan'     => 'Hari Lahir Pancasila',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Selasa, 2 Juni 2026',
                'uraian_kegiatan'     => "a. Rapat Paripurna Penutupan Masa Persidangan Ke-II Tahun Kedua, sekaligus Pembukaan Masa Persidangan Ke-III Tahun Kedua Periode Tahun 2024–2029.\n"
                    . 'b. Rapat Paripurna Penyampaian Laporan Hasil Pemeriksaan (LHP) BPK.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Rabu, 3 Juni 2026',
                'uraian_kegiatan'     => "Rapat Paripurna dengan acara:\n"
                    . "a. Pengumuman perubahan komposisi AKD dari Fraksi PDI Perjuangan.\n"
                    . 'b. Pembubaran dan pembentukan Pansus.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Selasa, 16 Juni 2026',
                'uraian_kegiatan'     => 'Tahun Baru Islam 1448 H',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juni 2026',
                'uraian_kegiatan'     => 'Rapat Paripurna Penyampaian Laporan Hasil Koordinasi dan Komunikasi Dalam Daerah dan Antar Daerah Masa Persidangan Ke-II Tahun Kedua.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juni 2026',
                'uraian_kegiatan'     => 'Rapat Komisi dengan Mitra Kerja dalam rangka Koordinasi dan Komunikasi Dalam Daerah dan Antar Daerah.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juni 2026',
                'uraian_kegiatan'     => 'Rapat Kerja Pimpinan/Ketua Fraksi/Alat Kelengkapan Dewan.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juni 2026',
                'uraian_kegiatan'     => 'Pengawasan Penggunaan Anggaran/KUNDAPIL.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juni–Juli 2026',
                'uraian_kegiatan'     => 'Rapat Paripurna/Rapat Badan Anggaran dengan acara Pembahasan dan Penetapan Raperda tentang Pertanggungjawaban Pelaksanaan APBD Tahun Anggaran 2025.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juni–Juli 2026',
                'uraian_kegiatan'     => 'Rapat Penyusunan, Pembahasan, dan Penetapan RENJA DPRD Sulawesi Tengah Tahun 2027.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juni–September 2026',
                'uraian_kegiatan'     => 'Pembahasan dan Penetapan Rancangan Peraturan Daerah Provinsi Sulawesi Tengah Tahun 2026.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juni–September 2026',
                'uraian_kegiatan'     => 'Rapat-rapat pembahasan Raperda, AKD, Fraksi, dan agenda kedewanan lainnya.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juni–September 2026',
                'uraian_kegiatan'     => "Rapat Paripurna/Pembahasan Rekomendasi Panitia Khusus DPRD Provinsi Sulawesi Tengah, yaitu:\n"
                    . "a. Penyintas Bencana Gempa Bumi tanggal 28 September 2018.\n"
                    . "b. Penyelesaian konflik agraria perkebunan kelapa sawit di Kabupaten Tolitoli.\n"
                    . 'c. Reinventarisasi aset.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Rabu, 1–5 Juli 2026',
                'uraian_kegiatan'     => 'Koordinasi dan Komunikasi Dalam Daerah.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Rabu, 8–15 Juli 2026',
                'uraian_kegiatan'     => 'Koordinasi dan Komunikasi Antar Daerah.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juli 2026',
                'uraian_kegiatan'     => 'Rapat Paripurna Penyampaian Laporan Semester Pertama Tahun 2026.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juli–Agustus 2026',
                'uraian_kegiatan'     => "Rapat Paripurna/Rapat Badan Anggaran dengan acara pembahasan dan penetapan KUA dan PPAS Perubahan Tahun Anggaran 2026:\n"
                    . "a. Penyampaian Rancangan KUA dan PPAS Perubahan.\n"
                    . 'b. Kesepakatan antara Kepala Daerah dan DPRD atas Rancangan KUPA dan Rancangan PPAS Perubahan.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juli–Agustus 2026',
                'uraian_kegiatan'     => "Rapat Paripurna/Rapat Badan Anggaran dalam rangka Pembahasan dan Penetapan KUA dan PPAS Tahun 2027:\n"
                    . "a. Penyampaian Rancangan KUA dan Rancangan PPAS oleh Kepala Daerah kepada DPRD.\n"
                    . 'b. Kesepakatan antara Kepala Daerah dan DPRD atas Rancangan KUA dan Rancangan PPAS.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Juli–Agustus 2026',
                'uraian_kegiatan'     => 'Reses',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Senin, 17 Agustus 2026',
                'uraian_kegiatan'     => 'HUT Proklamasi Kemerdekaan Republik Indonesia ke-81',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Selasa, 25 Agustus 2026',
                'uraian_kegiatan'     => 'Maulid Nabi Muhammad SAW',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Agustus–September 2026',
                'uraian_kegiatan'     => "Rapat Paripurna Pembahasan dan Penetapan RAPBD Perubahan Tahun Anggaran 2026:\n"
                    . "a. Penyampaian Rancangan Peraturan Daerah tentang Perubahan APBD Tahun 2026 oleh Kepala Daerah kepada DPRD.\n"
                    . 'b. Persetujuan bersama DPRD dan Kepala Daerah.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Senin, 21 September 2026',
                'uraian_kegiatan'     => 'Rapat Badan Musyawarah dengan acara Pembahasan dan Penetapan Jadwal Kegiatan Masa Persidangan Ke-I Tahun Ketiga DPRD Provinsi Sulawesi Tengah.',
                'keterangan'          => null,
            ],
            [
                'tanggal_pelaksanaan' => 'Selasa, 22 September 2026',
                'uraian_kegiatan'     => 'Rapat Paripurna dengan acara Penutupan Masa Persidangan Ke-III Tahun Kedua Periode Tahun 2024–2029, sekaligus Pembukaan Masa Persidangan Ke-I Tahun Ketiga Periode Tahun 2024–2029.',
                'keterangan'          => null,
            ],
        ];
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

    /**
     * Rapat contoh mengikuti bulan agenda rapat pada SK, tetapi tanggal dan
     * jamnya sengaja dibuat sebagai data dummy definitif.
     *
     * @return list<array<string, mixed>>
     */
    private function rapatBamusScheduleExamples(): array
    {
        return [
            $this->scheduleExample('2026-06-02', '09:00:00', '11:00:00', 'Rapat Paripurna Penutupan Masa Persidangan Ke-II dan Pembukaan Masa Persidangan Ke-III Tahun Kedua', 'Selasa, 2 Juni 2026', ['Seluruh Anggota'], 'Ruang Rapat Paripurna'),
            $this->scheduleExample('2026-06-03', '09:00:00', '11:00:00', 'Rapat Paripurna Pengumuman Perubahan Komposisi AKD dan Pembubaran serta Pembentukan Pansus', 'Rabu, 3 Juni 2026', ['Seluruh Anggota', 'Alat Kelengkapan Dewan', 'Pansus'], 'Ruang Rapat Paripurna'),
            $this->scheduleExample('2026-06-09', '10:00:00', '12:00:00', 'Rapat Paripurna Penyampaian Laporan Hasil Koordinasi dan Komunikasi Dalam Daerah dan Antar Daerah', 'Juni 2026', ['Seluruh Anggota'], 'Ruang Rapat Paripurna'),
            $this->scheduleExample('2026-06-16', '09:00:00', '11:00:00', 'Rapat Komisi dengan Mitra Kerja dalam rangka Koordinasi dan Komunikasi Dalam Daerah dan Antar Daerah', 'Juni 2026', ['Komisi I', 'Komisi II', 'Komisi III', 'Komisi IV'], 'Ruang Komisi'),
            $this->scheduleExample('2026-06-23', '13:30:00', '15:30:00', 'Rapat Kerja Pimpinan, Ketua Fraksi, dan Alat Kelengkapan Dewan', 'Juni 2026', ['Pimpinan DPRD', 'Ketua Fraksi', 'Alat Kelengkapan Dewan'], 'Ruang Rapat Utama'),
            $this->scheduleExample('2026-06-29', '09:00:00', '11:00:00', 'Rapat Paripurna dan Rapat Badan Anggaran Pembahasan Pertanggungjawaban Pelaksanaan APBD Tahun Anggaran 2025', 'Juni–Juli 2026', ['Seluruh Anggota', 'Badan Anggaran'], 'Ruang Badan Anggaran'),

            $this->scheduleExample('2026-07-02', '09:00:00', '11:00:00', 'Rapat Penyusunan, Pembahasan, dan Penetapan RENJA DPRD Sulawesi Tengah Tahun 2027', 'Juni–Juli 2026', ['Badan Musyawarah', 'Alat Kelengkapan Dewan'], 'Ruang Badan Musyawarah'),
            $this->scheduleExample('2026-07-07', '10:00:00', '12:00:00', 'Rapat Pembahasan dan Penetapan Rancangan Peraturan Daerah Provinsi Sulawesi Tengah Tahun 2026', 'Juni–September 2026', ['Bapemperda', 'Pansus'], 'Ruang Pansus'),
            $this->scheduleExample('2026-07-14', '09:00:00', '11:00:00', 'Rapat Pembahasan Raperda, AKD, Fraksi, dan Agenda Kedewanan Lainnya', 'Juni–September 2026', ['Bapemperda', 'Alat Kelengkapan Dewan', 'Fraksi'], 'Ruang Rapat Utama'),
            $this->scheduleExample('2026-07-21', '09:00:00', '11:00:00', 'Rapat Paripurna Pembahasan Rekomendasi Panitia Khusus DPRD Provinsi Sulawesi Tengah', 'Juni–September 2026', ['Seluruh Anggota', 'Pansus'], 'Ruang Rapat Paripurna'),
            $this->scheduleExample('2026-07-29', '13:30:00', '15:30:00', 'Rapat Koordinasi dan Komunikasi Dalam Daerah', 'Rabu, 1–5 Juli 2026', ['Seluruh Anggota', 'Alat Kelengkapan Dewan'], 'Ruang Rapat Utama'),

            $this->scheduleExample('2026-08-05', '09:00:00', '11:00:00', 'Rapat Koordinasi dan Komunikasi Antar Daerah', 'Rabu, 8–15 Juli 2026', ['Seluruh Anggota', 'Alat Kelengkapan Dewan'], 'Ruang Rapat Utama'),
            $this->scheduleExample('2026-08-11', '09:00:00', '11:00:00', 'Rapat Paripurna Penyampaian Laporan Semester Pertama Tahun 2026', 'Juli 2026', ['Seluruh Anggota'], 'Ruang Rapat Paripurna'),
            $this->scheduleExample('2026-08-18', '09:00:00', '11:00:00', 'Rapat Paripurna dan Rapat Badan Anggaran Pembahasan KUA dan PPAS Perubahan Tahun Anggaran 2026', 'Juli–Agustus 2026', ['Seluruh Anggota', 'Badan Anggaran'], 'Ruang Badan Anggaran'),
            $this->scheduleExample('2026-08-25', '13:30:00', '15:30:00', 'Rapat Paripurna dan Rapat Badan Anggaran Pembahasan KUA dan PPAS Tahun 2027', 'Juli–Agustus 2026', ['Seluruh Anggota', 'Badan Anggaran'], 'Ruang Badan Anggaran'),
            $this->scheduleExample('2026-08-28', '09:00:00', '11:00:00', 'Rapat Paripurna Pembahasan dan Penetapan RAPBD Perubahan Tahun Anggaran 2026', 'Agustus–September 2026', ['Seluruh Anggota', 'Badan Anggaran'], 'Ruang Rapat Paripurna'),

            $this->scheduleExample('2026-09-03', '09:00:00', '11:00:00', 'Rapat Badan Anggaran Pembahasan RAPBD Perubahan Tahun Anggaran 2026', 'Agustus–September 2026', ['Badan Anggaran'], 'Ruang Badan Anggaran'),
            $this->scheduleExample('2026-09-10', '13:30:00', '15:30:00', 'Rapat Paripurna Pembahasan Rekomendasi Panitia Khusus DPRD Provinsi Sulawesi Tengah', 'Juni–September 2026', ['Seluruh Anggota', 'Pansus'], 'Ruang Rapat Paripurna'),
            $this->scheduleExample('2026-09-17', '09:00:00', '11:00:00', 'Rapat Pembahasan Raperda, AKD, Fraksi, dan Agenda Kedewanan Lainnya', 'Juni–September 2026', ['Bapemperda', 'Alat Kelengkapan Dewan', 'Fraksi'], 'Ruang Rapat Utama'),
            $this->scheduleExample('2026-09-21', '09:00:00', '11:00:00', 'Rapat Badan Musyawarah Pembahasan dan Penetapan Jadwal Kegiatan Masa Persidangan Ke-I Tahun Ketiga', 'Senin, 21 September 2026', ['Badan Musyawarah'], 'Ruang Badan Musyawarah', 'bamus'),
            $this->scheduleExample('2026-09-22', '09:00:00', '11:00:00', 'Rapat Paripurna Penutupan Masa Persidangan Ke-III Tahun Kedua dan Pembukaan Masa Persidangan Ke-I Tahun Ketiga', 'Selasa, 22 September 2026', ['Seluruh Anggota'], 'Ruang Rapat Paripurna'),
        ];
    }

    private function scheduleExample(
        string $date,
        string $start,
        string $end,
        string $title,
        string $period,
        array $units,
        string $room,
        string $jenis = 'reguler',
    ): array {
        return [
            'date'     => $date,
            'start'    => $start,
            'end'      => $end,
            'title'    => $title,
            'period'   => $period,
            'units'    => $units,
            'room'     => $room,
            'location' => null,
            'jenis'    => $jenis,
            'public'   => 1,
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

    private function buildScheduleRow(array $schedule, array $roomsByName): array
    {
        $row = [
            'judul'         => $schedule['title'],
            'keterangan'    => self::DUMMY_SCHEDULE_NOTE . ' ' . $schedule['period'],
            'tanggal'       => $schedule['date'],
            'waktu_mulai'   => $schedule['start'],
            'waktu_selesai' => $schedule['end'],
            'ruangan_id'    => $schedule['room'] !== null ? ($roomsByName[$schedule['room']] ?? null) : null,
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
