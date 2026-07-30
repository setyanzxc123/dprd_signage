<?php

namespace App\Database\Seeds;

use App\Models\JadwalBanmusModel;
use CodeIgniter\Database\Seeder;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

/**
 * Dataset lengkap untuk arsitektur agenda saat ini.
 *
 * Seeder ini mengosongkan seluruh data aplikasi, tetapi sengaja tidak menyentuh
 * tabel `users` dan `migrations`. Akun admin serta riwayat migrasi yang sudah
 * ada tetap dipertahankan. Data contoh selalu memakai awalan `(Dummy)`.
 */
class CurrentSystemDataSeeder extends Seeder
{
    private const SEED_MARKER = '[CurrentSystemDataSeeder]';
    private const DUMMY_PREFIX = '(Dummy) ';

    private const REAL_SK_ONE_NUMBER = '160/1/2026';
    private const REAL_SK_NINE_NUMBER = '160/9/2026';
    private const DUMMY_SK_2027_ONE_NUMBER = 'DUMMY/160/1/2027';
    private const DUMMY_SK_2027_TWO_NUMBER = 'DUMMY/160/9/2027';

    private const REAL_SK_ONE_PDF = '1. PENETAPAN JADWAL BANMUS MASA PERSIDANGAN KE-II TAHUN KEDUA NO. 1 TGL 21 JAN 2026 .pdf';
    private const REAL_SK_NINE_PDF = '9. PENETAPAN JADWAL BANMUS MASA PERSIDANGAN KE-III TAHUN KEDUA NO. 9  TGL 22 MEI 2026 (SALINAN) (1).pdf';

    public function run(): void
    {
        $this->assertCurrentSchema();
        $this->clearStoredBanmusPdfs();

        $storedPdfs = [
            self::REAL_SK_ONE_NUMBER  => $this->storeSourcePdf(self::REAL_SK_ONE_PDF),
            self::REAL_SK_NINE_NUMBER => $this->storeSourcePdf(self::REAL_SK_NINE_PDF),
        ];

        $this->db->transBegin();

        try {
            $this->resetApplicationData();
            $this->seedSettings();
            $this->seedRooms();
            $this->seedMeetingUnits();
            $this->seedMembers();
            $this->seedBanmusDocuments($storedPdfs);
            $this->seedGeneralSchedules();
            $this->seedExternalGeneralSchedules();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Database menolak sebagian data seeder.');
            }

            $this->db->transCommit();
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    /**
     * Kosongkan data dalam urutan anak-ke-induk agar foreign key tetap valid.
     * Tabel `users` dan `migrations` tidak pernah masuk daftar ini.
     */
    private function resetApplicationData(): void
    {
        foreach ($this->resettableTables() as $table) {
            if ($this->db->tableExists($table)) {
                $this->db->table($table)->emptyTable();
            }
        }
    }

    /**
     * @return list<string>
     */
    private function resettableTables(): array
    {
        return [
            'member_otp_audits',
            'member_otps',
            'otp_webhook_events',
            'notifikasi',
            'agenda_audit_log',
            'jadwal_banmus_dokumen',
            'jadwal_banmus_unit_rapat',
            'jadwal_umum_unit_rapat',
            'anggota_unit_rapat',
            'member_accounts',
            'jadwal_banmus',
            'jadwal_umum',
            'dokumen_banmus',
            'anggota',
            'unit_rapat',
            'ruangan',
            'settings',
            'agenda_migration_state',
        ];
    }

    private function clearStoredBanmusPdfs(): void
    {
        $uploadDirectory = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'sk-banmus';
        if (! is_dir($uploadDirectory)) {
            return;
        }

        $paths = glob($uploadDirectory . DIRECTORY_SEPARATOR . '*.pdf') ?: [];
        foreach ($paths as $path) {
            $fileName = basename($path);
            if (preg_match('/^[a-f0-9]{40}\.pdf$/', $fileName) !== 1) {
                continue;
            }
            if (is_file($path) && ! unlink($path)) {
                throw new RuntimeException("PDF SK lama gagal dihapus: {$fileName}");
            }
        }
    }

    private function assertCurrentSchema(): void
    {
        $requiredTables = [
            'settings',
            'ruangan',
            'unit_rapat',
            'anggota',
            'anggota_unit_rapat',
            'member_accounts',
            'dokumen_banmus',
            'jadwal_banmus',
            'jadwal_banmus_unit_rapat',
            'jadwal_umum',
            'jadwal_umum_unit_rapat',
        ];

        foreach ($requiredTables as $table) {
            if (! $this->db->tableExists($table)) {
                throw new RuntimeException(
                    "Tabel {$table} belum tersedia. Jalankan `php spark migrate` terlebih dahulu."
                );
            }
        }

        $requiredFields = [
            'dokumen_banmus' => ['nomor_sk', 'tahun', 'semester', 'dokumen_file', 'is_publik'],
            'jadwal_banmus'  => ['dokumen_banmus_id', 'agenda', 'jenis_agenda', 'publikasi', 'status'],
            'jadwal_umum'    => [
                'judul', 'tanggal', 'waktu_mulai', 'waktu_selesai', 'ruangan_id',
                'lokasi_lainnya', 'pihak_eksternal', 'is_publik',
            ],
        ];

        foreach ($requiredFields as $table => $fields) {
            foreach ($fields as $field) {
                if (! $this->db->fieldExists($field, $table)) {
                    throw new RuntimeException("Kolom {$table}.{$field} belum tersedia.");
                }
            }
        }
    }

    private function seedSettings(): void
    {
        $rows = [
            ['key_name' => 'tema_signage', 'value' => 'dark'],
            ['key_name' => 'running_text', 'value' => 'Selamat datang di DPRD Provinsi Sulawesi Tengah'],
            ['key_name' => 'running_text_aktif', 'value' => '1'],
            ['key_name' => 'media_mode', 'value' => 'video'],
            ['key_name' => 'media_file', 'value' => ''],
        ];

        foreach ($rows as $row) {
            $this->upsertBy('settings', 'key_name', $row);
        }
    }

    private function seedRooms(): void
    {
        foreach ($this->rooms() as $room) {
            $this->upsertBy('ruangan', 'name', $room);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rooms(): array
    {
        return [
            ['name' => 'Ruang Rapat Paripurna', 'keterangan' => 'Ruang utama untuk rapat paripurna dan rapat pleno DPRD.', 'kapasitas' => 120, 'tersedia' => 1],
            ['name' => 'Ruang Rapat Utama', 'keterangan' => 'Ruang pimpinan untuk rapat badan dan rapat koordinasi.', 'kapasitas' => 70, 'tersedia' => 1],
            ['name' => 'Ruang Badan Musyawarah', 'keterangan' => 'Ruang rapat Badan Musyawarah.', 'kapasitas' => 45, 'tersedia' => 1],
            ['name' => 'Ruang Badan Anggaran', 'keterangan' => 'Ruang rapat Badan Anggaran.', 'kapasitas' => 55, 'tersedia' => 1],
            ['name' => 'Ruang Bapemperda', 'keterangan' => 'Ruang pembahasan program pembentukan peraturan daerah.', 'kapasitas' => 40, 'tersedia' => 1],
            ['name' => 'Ruang Badan Kehormatan', 'keterangan' => 'Ruang rapat Badan Kehormatan.', 'kapasitas' => 30, 'tersedia' => 1],
            ['name' => 'Ruang Komisi I', 'keterangan' => 'Ruang rapat bidang pemerintahan, hukum, dan keamanan.', 'kapasitas' => 32, 'tersedia' => 1],
            ['name' => 'Ruang Komisi II', 'keterangan' => 'Ruang rapat bidang ekonomi dan keuangan daerah.', 'kapasitas' => 32, 'tersedia' => 1],
            ['name' => 'Ruang Komisi III', 'keterangan' => 'Ruang rapat bidang pembangunan dan infrastruktur.', 'kapasitas' => 32, 'tersedia' => 1],
            ['name' => 'Ruang Komisi IV', 'keterangan' => 'Ruang rapat bidang kesejahteraan rakyat dan pendidikan.', 'kapasitas' => 32, 'tersedia' => 1],
            ['name' => 'Ruang Pansus', 'keterangan' => 'Ruang panitia khusus dan rapat gabungan.', 'kapasitas' => 44, 'tersedia' => 1],
            ['name' => 'Ruang Rapat Sekretariat', 'keterangan' => 'Ruang koordinasi internal Sekretariat DPRD.', 'kapasitas' => 24, 'tersedia' => 1],
        ];
    }

    private function seedMeetingUnits(): void
    {
        $now = date('Y-m-d H:i:s');
        $units = [
            10 => 'Komisi I',
            20 => 'Komisi II',
            30 => 'Komisi III',
            40 => 'Komisi IV',
            50 => 'Badan Anggaran',
            60 => 'Badan Musyawarah',
            70 => 'Bapemperda',
            80 => 'Badan Kehormatan',
            90 => 'Gabungan Komisi',
            91 => 'Pimpinan DPRD',
            92 => 'Ketua Fraksi',
            95 => 'Pansus Ranperda Pajak Daerah',
            96 => 'Pansus Tata Tertib DPRD',
            97 => 'Tim Pembahas RAPBD',
            98 => 'Tim Kunjungan Kerja',
            100 => 'Seluruh Anggota',
        ];

        foreach ($units as $order => $name) {
            $this->upsertBy('unit_rapat', 'nama', [
                'nama'       => $name,
                'aktif'      => 1,
                'urutan'     => $order,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedMembers(): void
    {
        $members = $this->members();
        foreach ($members as $member) {
            $this->upsertBy('anggota', 'no_wa', $member);
        }

        $phones = array_column($members, 'no_wa');
        $memberRows = $this->db->table('anggota')
            ->select('id, no_wa, komisi')
            ->whereIn('no_wa', $phones)
            ->get()
            ->getResultArray();
        $memberIdsByPhone = array_map('intval', array_column($memberRows, 'id', 'no_wa'));
        $unitIdsByName = $this->idsBy('unit_rapat', 'nama');
        $now = date('Y-m-d H:i:s');

        foreach ($memberRows as $member) {
            $memberId = (int) $member['id'];
            $this->upsertBy('member_accounts', 'anggota_id', [
                'anggota_id'    => $memberId,
                'last_login_at' => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            $this->db->table('anggota_unit_rapat')
                ->where('anggota_id', $memberId)
                ->delete();
        }

        $assignments = [
            'Seluruh Anggota' => $phones,
            'Pimpinan DPRD' => array_slice($phones, 0, 4),
            'Ketua Fraksi' => [$phones[0], $phones[4], $phones[10], $phones[16], $phones[22]],
            'Badan Musyawarah' => [$phones[0], $phones[1], $phones[2], $phones[3], $phones[4], $phones[10], $phones[16], $phones[22]],
            'Badan Anggaran' => [$phones[0], $phones[10], $phones[11], $phones[16], $phones[17], $phones[22], $phones[23]],
            'Bapemperda' => [$phones[2], $phones[6], $phones[12], $phones[18], $phones[24]],
            'Badan Kehormatan' => [$phones[3], $phones[8], $phones[14], $phones[20], $phones[25]],
            'Gabungan Komisi' => [$phones[4], $phones[10], $phones[16], $phones[22]],
            'Pansus Ranperda Pajak Daerah' => [$phones[5], $phones[11], $phones[17], $phones[23], $phones[26]],
            'Pansus Tata Tertib DPRD' => [$phones[6], $phones[12], $phones[18], $phones[24], $phones[27]],
            'Tim Pembahas RAPBD' => [$phones[10], $phones[13], $phones[16], $phones[19], $phones[22]],
            'Tim Kunjungan Kerja' => [$phones[7], $phones[14], $phones[20], $phones[25], $phones[27]],
        ];

        foreach ($memberRows as $member) {
            $commission = trim((string) $member['komisi']);
            if ($commission !== '') {
                $assignments[$commission][] = (string) $member['no_wa'];
            }
        }

        foreach ($assignments as $unitName => $assignedPhones) {
            $unitId = (int) ($unitIdsByName[$unitName] ?? 0);
            if ($unitId < 1) {
                continue;
            }

            foreach (array_unique($assignedPhones) as $phone) {
                $memberId = (int) ($memberIdsByPhone[$phone] ?? 0);
                if ($memberId < 1) {
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

    /**
     * @return list<array<string, mixed>>
     */
    private function members(): array
    {
        $profiles = [
            ['Ketua DPRD', '', 'Golongan Karya'],
            ['Wakil Ketua DPRD I', '', 'NasDem'],
            ['Wakil Ketua DPRD II', '', 'Gerindra'],
            ['Wakil Ketua DPRD III', '', 'PDI Perjuangan'],
            ['Ketua Komisi I', 'Komisi I', 'Amanat Nasional'],
            ['Wakil Ketua Komisi I', 'Komisi I', 'Demokrat'],
            ['Sekretaris Komisi I', 'Komisi I', 'PKS'],
            ['Anggota Komisi I', 'Komisi I', 'Hanura'],
            ['Anggota Komisi I', 'Komisi I', 'Golongan Karya'],
            ['Anggota Komisi I', 'Komisi I', 'PKB'],
            ['Ketua Komisi II', 'Komisi II', 'Golongan Karya'],
            ['Wakil Ketua Komisi II', 'Komisi II', 'NasDem'],
            ['Sekretaris Komisi II', 'Komisi II', 'Gerindra'],
            ['Anggota Komisi II', 'Komisi II', 'PDI Perjuangan'],
            ['Anggota Komisi II', 'Komisi II', 'Demokrat'],
            ['Anggota Komisi II', 'Komisi II', 'PKS'],
            ['Ketua Komisi III', 'Komisi III', 'NasDem'],
            ['Wakil Ketua Komisi III', 'Komisi III', 'Golongan Karya'],
            ['Sekretaris Komisi III', 'Komisi III', 'Gerindra'],
            ['Anggota Komisi III', 'Komisi III', 'PDI Perjuangan'],
            ['Anggota Komisi III', 'Komisi III', 'PKB'],
            ['Anggota Komisi III', 'Komisi III', 'Amanat Nasional'],
            ['Ketua Komisi IV', 'Komisi IV', 'PDI Perjuangan'],
            ['Wakil Ketua Komisi IV', 'Komisi IV', 'Demokrat'],
            ['Sekretaris Komisi IV', 'Komisi IV', 'NasDem'],
            ['Anggota Komisi IV', 'Komisi IV', 'Golongan Karya'],
            ['Anggota Komisi IV', 'Komisi IV', 'Gerindra'],
            ['Anggota Komisi IV', 'Komisi IV', 'PKS'],
        ];

        $rows = [];
        foreach ($profiles as $index => [$position, $commission, $fraction]) {
            $number = $index + 1;
            $rows[] = [
                'name'    => self::DUMMY_PREFIX . 'Anggota DPRD ' . str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'jabatan' => $position,
                'fraksi'  => $fraction,
                'komisi'  => $commission,
                'no_wa'   => '08000001' . str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'aktif'   => 1,
                'foto'    => null,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, array{stored: string, original: string}> $storedPdfs
     */
    private function seedBanmusDocuments(array $storedPdfs): void
    {
        $documents = [
            [
                'document' => [
                    'judul' => 'Penetapan Jadwal Kegiatan Masa Persidangan Kedua Tahun Kedua DPRD Provinsi Sulawesi Tengah Masa Jabatan 2024-2029',
                    'nomor_sk' => self::REAL_SK_ONE_NUMBER,
                    'tanggal_sk' => '2026-01-21',
                    'tahun' => 2026,
                    'semester' => 1,
                    'masa_persidangan' => 'Masa Persidangan Kedua Tahun Kedua',
                    'periode_mulai' => '2026-01-01',
                    'periode_selesai' => '2026-05-25',
                    'status' => 'disahkan',
                    'is_publik' => 1,
                    'dokumen_file' => $storedPdfs[self::REAL_SK_ONE_NUMBER]['stored'],
                    'dokumen_nama_asli' => $storedPdfs[self::REAL_SK_ONE_NUMBER]['original'],
                    'dokumen_url' => null,
                    'catatan' => 'Sumber resmi: Keputusan Pimpinan DPRD Provinsi Sulawesi Tengah Nomor 160/1/2026 tanggal 21 Januari 2026.',
                ],
                'items' => $this->realSkOneItems(),
            ],
            [
                'document' => [
                    'judul' => 'Penetapan Jadwal Kegiatan Masa Persidangan Ketiga Tahun Kedua DPRD Provinsi Sulawesi Tengah Masa Jabatan 2024-2029',
                    'nomor_sk' => self::REAL_SK_NINE_NUMBER,
                    'tanggal_sk' => '2026-05-22',
                    'tahun' => 2026,
                    'semester' => 2,
                    'masa_persidangan' => 'Masa Persidangan Ketiga Tahun Kedua',
                    'periode_mulai' => '2026-05-27',
                    'periode_selesai' => '2026-09-22',
                    'status' => 'disahkan',
                    'is_publik' => 1,
                    'dokumen_file' => $storedPdfs[self::REAL_SK_NINE_NUMBER]['stored'],
                    'dokumen_nama_asli' => $storedPdfs[self::REAL_SK_NINE_NUMBER]['original'],
                    'dokumen_url' => null,
                    'catatan' => 'Sumber resmi: Keputusan Pimpinan DPRD Provinsi Sulawesi Tengah Nomor 160/9/2026 tanggal 22 Mei 2026.',
                ],
                'items' => $this->realSkNineItems(),
            ],
            [
                'document' => [
                    'judul' => self::DUMMY_PREFIX . 'Jadwal Kegiatan Masa Persidangan Kedua Tahun Ketiga DPRD Provinsi Sulawesi Tengah',
                    'nomor_sk' => self::DUMMY_SK_2027_ONE_NUMBER,
                    'tanggal_sk' => '2027-01-20',
                    'tahun' => 2027,
                    'semester' => 1,
                    'masa_persidangan' => 'Masa Persidangan Kedua Tahun Ketiga',
                    'periode_mulai' => '2027-01-20',
                    'periode_selesai' => '2027-05-28',
                    'status' => 'disahkan',
                    'is_publik' => 1,
                    'dokumen_file' => null,
                    'dokumen_nama_asli' => null,
                    'dokumen_url' => null,
                    'catatan' => self::DUMMY_PREFIX . 'SK simulasi untuk menguji kombinasi proyeksi, jadwal pasti, jenis agenda, publikasi, lokasi, peserta, dan sumber daya.',
                ],
                'items' => $this->dummySk2027FirstSemesterItems(),
            ],
            [
                'document' => [
                    'judul' => self::DUMMY_PREFIX . 'Jadwal Kegiatan Masa Persidangan Ketiga Tahun Ketiga DPRD Provinsi Sulawesi Tengah',
                    'nomor_sk' => self::DUMMY_SK_2027_TWO_NUMBER,
                    'tanggal_sk' => '2027-05-21',
                    'tahun' => 2027,
                    'semester' => 2,
                    'masa_persidangan' => 'Masa Persidangan Ketiga Tahun Ketiga',
                    'periode_mulai' => '2027-05-24',
                    'periode_selesai' => '2027-09-24',
                    'status' => 'disahkan',
                    'is_publik' => 1,
                    'dokumen_file' => null,
                    'dokumen_nama_asli' => null,
                    'dokumen_url' => null,
                    'catatan' => self::DUMMY_PREFIX . 'SK simulasi dengan kepadatan agenda tinggi pada Agustus 2027.',
                ],
                'items' => $this->dummySk2027SecondSemesterItems(),
            ],
        ];

        foreach ($documents as $document) {
            $this->upsertBanmusDocument($document['document'], $document['items']);
        }
    }

    /**
     * @param array<string, mixed>       $document
     * @param list<array<string, mixed>> $items
     */
    private function upsertBanmusDocument(array $document, array $items): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->db->table('dokumen_banmus')
            ->select('id')
            ->where('nomor_sk', $document['nomor_sk'])
            ->get()
            ->getRowArray();

        $document += [
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $document['updated_at'] = $now;
        $document = $this->onlyExistingFields('dokumen_banmus', $document);

        if ($existing) {
            $documentId = (int) $existing['id'];
            unset($document['created_at']);
            $this->db->table('dokumen_banmus')
                ->where('id', $documentId)
                ->update($document);
            $this->deleteBanmusItems($documentId);
        } else {
            $this->db->table('dokumen_banmus')->insert($document);
            $documentId = (int) $this->db->insertID();
        }

        $roomIdsByName = $this->idsBy('ruangan', 'name');
        $unitIdsByName = $this->idsBy('unit_rapat', 'nama');

        foreach ($items as $index => $item) {
            $unitNames = $item['units'] ?? [];
            unset($item['units'], $item['room']);

            $roomName = $items[$index]['room'] ?? null;
            $item['dokumen_banmus_id'] = $documentId;
            $item['urutan'] = $index + 1;
            $item['ruangan_id'] = is_string($roomName) && isset($roomIdsByName[$roomName])
                ? (int) $roomIdsByName[$roomName]
                : null;
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            $item['deleted_at'] = null;

            $this->db->table('jadwal_banmus')
                ->insert($this->onlyExistingFields('jadwal_banmus', $item));
            $itemId = (int) $this->db->insertID();

            foreach ($unitNames as $unitName) {
                $unitId = (int) ($unitIdsByName[$unitName] ?? 0);
                if ($unitId < 1) {
                    throw new RuntimeException("Unit rapat {$unitName} tidak ditemukan.");
                }

                $this->insertPivotIfMissing('jadwal_banmus_unit_rapat', [
                    'jadwal_banmus_id' => $itemId,
                    'unit_rapat_id' => $unitId,
                    'created_at' => $now,
                ], ['jadwal_banmus_id', 'unit_rapat_id']);
            }
        }
    }

    private function deleteBanmusItems(int $documentId): void
    {
        $itemIds = array_map(
            'intval',
            array_column(
                $this->db->table('jadwal_banmus')
                    ->select('id')
                    ->where('dokumen_banmus_id', $documentId)
                    ->get()
                    ->getResultArray(),
                'id'
            )
        );

        if ($itemIds !== []) {
            $this->db->table('jadwal_banmus_unit_rapat')
                ->whereIn('jadwal_banmus_id', $itemIds)
                ->delete();
            if ($this->db->tableExists('jadwal_banmus_dokumen')) {
                $this->db->table('jadwal_banmus_dokumen')
                    ->whereIn('jadwal_banmus_id', $itemIds)
                    ->delete();
            }
        }

        $this->db->table('jadwal_banmus')
            ->where('dokumen_banmus_id', $documentId)
            ->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function realSkOneItems(): array
    {
        return [
            $this->sourceItem('Januari–April 2026', 'Rapat Pembahasan dan Penyampaian Rekomendasi Panitia Khusus di luar Raperda', 'rapat', '2026-01-01', '2026-04-30', 3),
            $this->sourceItem('Selasa, 3 Februari 2026', 'Rapat Kerja Pimpinan/Ketua AKD/Ketua Fraksi', 'rapat', '2026-02-03', '2026-02-03', 3),
            $this->sourceItem('Rabu, 4–8 Februari 2026', 'Koordinasi dan Komunikasi Dalam Daerah', 'non_rapat', '2026-02-04', '2026-02-08', 3),
            $this->sourceItem('Senin, 9–16 Februari 2026', 'Reses', 'non_rapat', '2026-02-09', '2026-02-16', 3),
            $this->sourceItem('Selasa, 17 Februari 2026', 'Tahun Baru Imlek', 'non_rapat', '2026-02-17', '2026-02-17', 3),
            $this->sourceItem('Februari–Mei 2026', 'Pembahasan/Penetapan Rancangan Peraturan Daerah Provinsi Sulawesi Tengah Tahun 2026', 'rapat', '2026-02-01', '2026-05-31', 3),
            $this->sourceItem('Kamis, 19 Maret 2026', 'Pelaksanaan Rakor Forum DPRD Penghasil Nikel', 'rapat', '2026-03-19', '2026-03-19', 3),
            $this->sourceItem('Maret 2026', 'Hari Nyepi', 'non_rapat', '2026-03-01', '2026-03-31', 3),
            $this->sourceItem('Maret 2026', 'Rapat Paripurna Penyampaian Laporan Hasil Reses Masa Persidangan II Tahun Kedua serta Penyusunan dan Penginputan Pokok-Pokok Pikiran DPRD Tahun 2027', 'rapat', '2026-03-01', '2026-03-31', 3),
            $this->sourceItem('Maret 2026', 'Rapat Forum OPD Provinsi Sulawesi Tengah', 'rapat', '2026-03-01', '2026-03-31', 3),
            $this->sourceItem('Sabtu, 21 Maret 2026', 'Hari Raya Idul Fitri 1447 H', 'non_rapat', '2026-03-21', '2026-03-21', 3),
            $this->sourceItem('18–24 Maret 2026', 'Cuti Bersama Hari Raya Nyepi dan Idul Fitri 1447 H', 'non_rapat', '2026-03-18', '2026-03-24', 4),
            $this->sourceItem('Senin, 30 Maret 2026', 'Rapat Paripurna Penyampaian Laporan Keterangan Pertanggungjawaban Kepala Daerah Tahun 2025', 'rapat', '2026-03-30', '2026-03-30', 4),
            $this->sourceItem('Jumat, 3 April 2026', 'Wafat Yesus Kristus', 'non_rapat', '2026-04-03', '2026-04-03', 4),
            $this->sourceItem('Senin, 6–11 April 2026', 'Pelaksanaan KUNDAPIL/Pengawasan Penggunaan Anggaran', 'non_rapat', '2026-04-06', '2026-04-11', 4),
            $this->sourceItem('April 2026', 'Musrenbang RKPD Provinsi Sulawesi Tengah', 'non_rapat', '2026-04-01', '2026-04-30', 4),
            $this->sourceItem('Jumat, 10 April 2026', 'Rapat Paripurna Hari Ulang Tahun Provinsi Sulawesi Tengah', 'rapat', '2026-04-10', '2026-04-10', 4),
            $this->sourceItem('Senin, 13 April 2026', 'HUT Provinsi Sulawesi Tengah', 'non_rapat', '2026-04-13', '2026-04-13', 4),
            $this->sourceItem('April 2026', 'Koordinasi dan Komunikasi Antar Daerah', 'non_rapat', '2026-04-01', '2026-04-30', 4),
            $this->sourceItem('April 2026', 'Rapat Dengar Pendapat Masing-Masing Komisi Beserta Mitra Kerja', 'rapat', '2026-04-01', '2026-04-30', 4),
            $this->sourceItem('Rabu, 29 April 2026', 'Rapat Paripurna Penyampaian Rekomendasi Pansus LKPJ Kepala Daerah Tahun 2025', 'rapat', '2026-04-29', '2026-04-29', 4),
            $this->sourceItem('April 2026', 'Rapat Kerja Pimpinan/Ketua Fraksi/Alat Kelengkapan Dewan', 'rapat', '2026-04-01', '2026-04-30', 4),
            $this->sourceItem('Jumat, 1 Mei 2026', 'Hari Buruh Internasional', 'non_rapat', '2026-05-01', '2026-05-01', 4),
            $this->sourceItem('Sabtu, 2 Mei 2026', 'Hari Pendidikan Nasional', 'non_rapat', '2026-05-02', '2026-05-02', 4),
            $this->sourceItem('Senin, 4 Mei 2026', 'Rapat Paripurna Penyampaian Laporan Hasil Koordinasi dan Komunikasi Dalam Daerah dan Antar Daerah Masa Persidangan II Tahun Kedua', 'rapat', '2026-05-04', '2026-05-04', 4),
            $this->sourceItem('Kamis, 14 Mei 2026', 'Kenaikan Yesus Kristus', 'non_rapat', '2026-05-14', '2026-05-14', 4),
            $this->sourceItem('Mei 2026', 'Rapat Kerja Pimpinan/Ketua Fraksi/Alat Kelengkapan Dewan', 'rapat', '2026-05-01', '2026-05-31', 4),
            $this->sourceItem('Jumat, 22 Mei 2026', 'Rapat Badan Musyawarah dengan Acara Pembahasan/Penetapan Jadwal Kegiatan Masa Persidangan III Tahun Kedua', 'rapat', '2026-05-22', '2026-05-22', 4),
            $this->sourceItem('Senin, 25 Mei 2026', 'Rapat Paripurna Penutupan Masa Persidangan II Tahun Kedua dan Pembukaan Masa Persidangan III Tahun Kedua', 'rapat', '2026-05-25', '2026-05-25', 5),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function realSkNineItems(): array
    {
        return [
            $this->sourceItem('Rabu, 27 Mei 2026', 'Hari Raya Idul Adha 1447 H', 'non_rapat', '2026-05-27', '2026-05-27', 3),
            $this->sourceItem('Kamis, 28 Mei 2026', 'Cuti Bersama', 'non_rapat', '2026-05-28', '2026-05-28', 3),
            $this->sourceItem('Senin, 1 Juni 2026', 'Hari Lahir Pancasila', 'non_rapat', '2026-06-01', '2026-06-01', 3),
            $this->sourceItem('Selasa, 2 Juni 2026', 'Rapat Paripurna Penutupan Masa Persidangan II Tahun Kedua sekaligus Pembukaan Masa Persidangan III Tahun Kedua dan Penyampaian Laporan Hasil Pemeriksaan BPK', 'rapat', '2026-06-02', '2026-06-02', 3),
            $this->sourceItem('Rabu, 3 Juni 2026', 'Rapat Paripurna Pengumuman Perubahan Komposisi AKD dari Fraksi PDI Perjuangan serta Pembubaran dan Pembentukan Pansus', 'rapat', '2026-06-03', '2026-06-03', 3),
            $this->sourceItem('Selasa, 16 Juni 2026', 'Tahun Baru Islam 1448 H', 'non_rapat', '2026-06-16', '2026-06-16', 3),
            $this->sourceItem('Juni 2026', 'Rapat Paripurna Penyampaian Laporan Hasil Koordinasi dan Komunikasi Dalam Daerah dan Antar Daerah Masa Persidangan II Tahun Kedua', 'rapat', '2026-06-01', '2026-06-30', 3),
            $this->sourceItem('Juni 2026', 'Rapat Komisi dengan Mitra Kerja dalam Rangka Koordinasi dan Komunikasi Dalam Daerah dan Antar Daerah', 'rapat', '2026-06-01', '2026-06-30', 3),
            $this->sourceItem('Juni 2026', 'Rapat Kerja Pimpinan/Ketua Fraksi/Alat Kelengkapan Dewan', 'rapat', '2026-06-01', '2026-06-30', 3),
            $this->sourceItem('Juni 2026', 'Pengawasan Penggunaan Anggaran/KUNDAPIL', 'non_rapat', '2026-06-01', '2026-06-30', 3),
            $this->sourceItem('Juni–Juli 2026', 'Rapat Paripurna/Rapat Badan Anggaran Pembahasan dan Penetapan Raperda tentang Pertanggungjawaban Pelaksanaan APBD Tahun Anggaran 2025', 'rapat', '2026-06-01', '2026-07-31', 3),
            $this->sourceItem('Juni–Juli 2026', 'Rapat Penyusunan, Pembahasan, dan Penetapan RENJA DPRD Sulawesi Tengah Tahun 2027', 'rapat', '2026-06-01', '2026-07-31', 4),
            $this->sourceItem('Juni–September 2026', 'Pembahasan/Penetapan Rancangan Peraturan Daerah Provinsi Sulawesi Tengah Tahun 2026', 'rapat', '2026-06-01', '2026-09-30', 4),
            $this->sourceItem('Juni–September 2026', 'Rapat-Rapat Pembahasan Raperda, AKD, Fraksi, dan Agenda Kedewanan Lainnya', 'rapat', '2026-06-01', '2026-09-30', 4),
            $this->sourceItem('Juni–September 2026', 'Rapat Paripurna/Pembahasan Rekomendasi Panitia Khusus DPRD Provinsi Sulawesi Tengah', 'rapat', '2026-06-01', '2026-09-30', 4),
            $this->sourceItem('Rabu, 1–5 Juli 2026', 'Koordinasi dan Komunikasi Dalam Daerah', 'non_rapat', '2026-07-01', '2026-07-05', 4),
            $this->sourceItem('Rabu, 8–15 Juli 2026', 'Koordinasi dan Komunikasi Antar Daerah', 'non_rapat', '2026-07-08', '2026-07-15', 4),
            $this->sourceItem('Juli 2026', 'Rapat Paripurna Penyampaian Laporan Semester Pertama Tahun 2026', 'rapat', '2026-07-01', '2026-07-31', 4),
            $this->sourceItem('Juli–Agustus 2026', 'Rapat Paripurna/Rapat Badan Anggaran Pembahasan dan Penetapan KUA dan PPAS Perubahan Tahun Anggaran 2026', 'rapat', '2026-07-01', '2026-08-31', 4),
            $this->sourceItem('Juli–Agustus 2026', 'Rapat Paripurna/Rapat Badan Anggaran Pembahasan dan Penetapan KUA dan PPAS Tahun 2027', 'rapat', '2026-07-01', '2026-08-31', 4),
            $this->sourceItem('Juli–Agustus 2026', 'Reses', 'non_rapat', '2026-07-01', '2026-08-31', 4),
            $this->sourceItem('Senin, 17 Agustus 2026', 'HUT Proklamasi Kemerdekaan Republik Indonesia Ke-81', 'non_rapat', '2026-08-17', '2026-08-17', 4),
            $this->sourceItem('Selasa, 25 Agustus 2026', 'Maulid Nabi Muhammad S.A.W.', 'non_rapat', '2026-08-25', '2026-08-25', 4),
            $this->sourceItem('Agustus–September 2026', 'Rapat Paripurna Pembahasan/Penetapan RAPBD Perubahan Tahun Anggaran 2026', 'rapat', '2026-08-01', '2026-09-30', 5),
            $this->sourceItem('Senin, 21 September 2026', 'Rapat Badan Musyawarah dengan Acara Pembahasan/Penetapan Jadwal Kegiatan Masa Persidangan I Tahun Ketiga DPRD Provinsi Sulawesi Tengah', 'rapat', '2026-09-21', '2026-09-21', 5),
            $this->sourceItem('Selasa, 22 September 2026', 'Rapat Paripurna Penutupan Masa Persidangan III Tahun Kedua sekaligus Pembukaan Masa Persidangan I Tahun Ketiga', 'rapat', '2026-09-22', '2026-09-22', 5),
        ];
    }

    /**
     * Item dari lampiran SK hanya menyimpan informasi yang benar-benar tersedia
     * pada dokumen. Tanggal operasional sengaja belum diisi agar statusnya tetap
     * Proyeksi sampai admin melengkapi jam, lokasi, dan kelompok peserta.
     *
     * @return array<string, mixed>
     */
    private function sourceItem(
        string $periodLabel,
        string $agenda,
        string $type,
        string $startDate,
        string $endDate,
        int $sourcePage,
    ): array {
        return [
            'agenda' => $agenda,
            'jenis_agenda' => $type,
            'periode_label' => $periodLabel,
            'tanggal_mulai' => $startDate,
            'tanggal_selesai' => $endDate,
            'teks_tanggal_asli' => $periodLabel,
            'bulan_mulai' => substr($startDate, 0, 7),
            'bulan_selesai' => substr($endDate, 0, 7),
            'jumlah_pelaksanaan_rencana' => 1,
            'halaman_sumber' => $sourcePage,
            'tanggal' => null,
            'jam_mulai' => null,
            'jam_selesai' => null,
            'room' => null,
            'lokasi_lainnya' => null,
            'units' => [],
            'publikasi' => 'publik',
            'materi_url' => null,
            'materi_akses' => 'publik',
            'stream_url' => null,
            'stream_akses' => 'publik',
            'status' => 'proyeksi',
            'catatan' => 'Butir agenda sesuai lampiran SK; data pelaksanaan operasional belum dilengkapi.',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dummySk2027FirstSemesterItems(): array
    {
        return [
            $this->dummyBanmusItem(
                'Januari–April 2027',
                'Rapat Pembahasan dan Penyampaian Rekomendasi Panitia Khusus di Luar Raperda',
                'rapat',
            ),
            $this->dummyBanmusItem(
                'Selasa, 2 Februari 2027',
                'Rapat Kerja Pimpinan, Ketua Fraksi, dan Ketua Alat Kelengkapan Dewan',
                'rapat',
                '2027-02-02',
                '09:00:00',
                '11:00:00',
                'Ruang Rapat Utama',
                null,
                ['Pimpinan DPRD', 'Ketua Fraksi'],
                'internal',
                'https://example.test/dummy/materi/rapat-pimpinan-februari',
                'anggota',
            ),
            $this->dummyBanmusItem(
                'Rabu, 3 Februari 2027',
                'Rapat Dengar Pendapat Komisi I dengan Mitra Kerja Bidang Pemerintahan',
                'rapat',
                '2027-02-03',
                '09:00:00',
                '11:30:00',
                'Ruang Komisi I',
                null,
                ['Komisi I'],
                'publik',
                'https://example.test/dummy/materi/rdp-komisi-i',
                'publik',
            ),
            $this->dummyBanmusItem(
                'Rabu, 3 Februari 2027',
                'Rapat Dengar Pendapat Komisi II dengan Mitra Kerja Bidang Perekonomian',
                'rapat',
                '2027-02-03',
                '13:30:00',
                '16:00:00',
                'Ruang Komisi II',
                null,
                ['Komisi II'],
                'internal',
            ),
            $this->dummyBanmusItem(
                'Kamis, 4 Februari 2027',
                'Rapat Gabungan Komisi Membahas Tindak Lanjut Aspirasi Masyarakat',
                'rapat',
                '2027-02-04',
                '09:00:00',
                '12:00:00',
                'Ruang Rapat Utama',
                null,
                ['Gabungan Komisi'],
                'publik',
                'https://example.test/dummy/materi/aspirasi-masyarakat',
                'publik',
                'https://example.test/dummy/live/aspirasi-masyarakat',
                'publik',
            ),
            $this->dummyBanmusItem(
                '5–10 Februari 2027',
                'Reses Masa Persidangan Kedua Tahun Ketiga',
                'non_rapat',
            ),
            $this->dummyBanmusItem(
                '11–13 Februari 2027',
                'Koordinasi dan Komunikasi Dalam Daerah',
                'non_rapat',
                '2027-02-11',
                '08:00:00',
                '17:00:00',
                null,
                'Kabupaten Sigi',
                ['Tim Kunjungan Kerja'],
                'internal',
            ),
            $this->dummyBanmusItem(
                'Senin, 15 Februari 2027',
                'Rapat Paripurna Penyampaian Laporan Hasil Reses',
                'rapat',
                '2027-02-15',
                '10:00:00',
                '12:00:00',
                'Ruang Rapat Paripurna',
                null,
                ['Seluruh Anggota'],
                'publik',
                'https://example.test/dummy/materi/laporan-reses',
                'publik',
                'https://example.test/dummy/live/paripurna-reses',
                'publik',
            ),
            $this->dummyBanmusItem(
                'Selasa, 16 Februari 2027',
                'Rapat Badan Anggaran Membahas Evaluasi Realisasi APBD',
                'rapat',
                '2027-02-16',
                '09:00:00',
                '12:00:00',
                'Ruang Badan Anggaran',
                null,
                ['Badan Anggaran'],
                'internal',
                'https://example.test/dummy/materi/evaluasi-apbd',
                'peserta',
            ),
            $this->dummyBanmusItem(
                'Rabu, 17 Februari 2027',
                'Rapat Pansus Ranperda Pajak Daerah',
                'rapat',
                '2027-02-17',
                '13:00:00',
                '16:00:00',
                'Ruang Pansus',
                null,
                ['Pansus Ranperda Pajak Daerah'],
                'internal',
                'https://example.test/dummy/materi/ranperda-pajak',
                'peserta',
            ),
            $this->dummyBanmusItem(
                'Kamis, 18 Februari 2027',
                'Rapat Komisi III dengan Balai Pelaksana Jalan Nasional',
                'rapat',
                '2027-02-18',
                '09:30:00',
                '12:00:00',
                null,
                'Kantor Balai Pelaksana Jalan Nasional Sulawesi Tengah',
                ['Komisi III'],
                'internal',
            ),
            $this->dummyBanmusItem(
                'Jumat, 19 Februari 2027',
                'Rapat Bapemperda Harmonisasi Program Pembentukan Peraturan Daerah',
                'rapat',
                '2027-02-19',
                '09:00:00',
                '11:30:00',
                'Ruang Bapemperda',
                null,
                ['Bapemperda'],
                'publik',
                'https://example.test/dummy/materi/harmonisasi-propemperda',
                'anggota',
            ),
            $this->dummyBanmusItem(
                '20–22 Februari 2027',
                'Pelaksanaan KUNDAPIL dan Pengawasan Penggunaan Anggaran',
                'non_rapat',
            ),
            $this->dummyBanmusItem(
                'Rabu, 24 Februari 2027',
                'Rapat Badan Musyawarah Evaluasi Pelaksanaan Agenda Bulanan',
                'rapat',
                '2027-02-24',
                '13:30:00',
                '15:30:00',
                'Ruang Badan Musyawarah',
                null,
                ['Badan Musyawarah'],
                'publik',
                'https://example.test/dummy/materi/evaluasi-banmus',
                'publik',
                'https://example.test/dummy/live/evaluasi-banmus',
                'anggota',
            ),
            $this->dummyBanmusItem(
                'Kamis, 25 Februari 2027',
                'Cuti Bersama',
                'non_rapat',
            ),
            $this->dummyBanmusItem(
                'Maret 2027',
                'Rapat Forum Perangkat Daerah Provinsi Sulawesi Tengah',
                'rapat',
            ),
            $this->dummyBanmusItem(
                'April 2027',
                'Koordinasi dan Komunikasi Antar Daerah',
                'non_rapat',
            ),
            $this->dummyBanmusItem(
                'Jumat, 28 Mei 2027',
                'Rapat Paripurna Penutupan Masa Persidangan Kedua dan Pembukaan Masa Persidangan Ketiga Tahun Ketiga',
                'rapat',
                '2027-05-28',
                '09:30:00',
                '12:00:00',
                'Ruang Rapat Paripurna',
                null,
                ['Seluruh Anggota'],
                'publik',
                'https://example.test/dummy/materi/paripurna-penutupan',
                'publik',
                'https://example.test/dummy/live/paripurna-penutupan',
                'publik',
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dummySk2027SecondSemesterItems(): array
    {
        return [
            $this->dummyBanmusItem(
                'Juni–September 2027',
                'Pembahasan dan Penetapan Rancangan Peraturan Daerah Provinsi Sulawesi Tengah',
                'rapat',
            ),
            $this->dummyBanmusItem(
                'Juni–Juli 2027',
                'Rapat Penyusunan, Pembahasan, dan Penetapan Rencana Kerja DPRD Tahun 2028',
                'rapat',
            ),
            $this->dummyBanmusItem(
                '1–7 Juli 2027',
                'Koordinasi dan Komunikasi Dalam Daerah',
                'non_rapat',
            ),
            $this->dummyBanmusItem(
                '12–19 Juli 2027',
                'Reses Masa Persidangan Ketiga Tahun Ketiga',
                'non_rapat',
            ),
            $this->dummyBanmusItem(
                'Senin, 2 Agustus 2027',
                'Rapat Badan Anggaran Pembahasan Rancangan KUA dan PPAS Tahun 2028',
                'rapat',
                '2027-08-02',
                '09:00:00',
                '12:00:00',
                'Ruang Badan Anggaran',
                null,
                ['Badan Anggaran', 'Tim Pembahas RAPBD'],
                'internal',
                'https://example.test/dummy/materi/kua-ppas-2028',
                'peserta',
            ),
            $this->dummyBanmusItem(
                'Selasa, 3 Agustus 2027',
                'Rapat Komisi I Membahas Layanan Administrasi Kependudukan',
                'rapat',
                '2027-08-03',
                '09:00:00',
                '11:30:00',
                'Ruang Komisi I',
                null,
                ['Komisi I'],
                'publik',
            ),
            $this->dummyBanmusItem(
                'Selasa, 3 Agustus 2027',
                'Rapat Komisi II Membahas Stabilitas Harga Pangan',
                'rapat',
                '2027-08-03',
                '09:00:00',
                '11:30:00',
                'Ruang Komisi II',
                null,
                ['Komisi II'],
                'publik',
            ),
            $this->dummyBanmusItem(
                'Selasa, 3 Agustus 2027',
                'Rapat Komisi III Membahas Percepatan Infrastruktur Jalan',
                'rapat',
                '2027-08-03',
                '13:30:00',
                '16:00:00',
                'Ruang Komisi III',
                null,
                ['Komisi III'],
                'internal',
            ),
            $this->dummyBanmusItem(
                'Selasa, 3 Agustus 2027',
                'Rapat Komisi IV Membahas Pelayanan Kesehatan Rujukan',
                'rapat',
                '2027-08-03',
                '13:30:00',
                '16:00:00',
                'Ruang Komisi IV',
                null,
                ['Komisi IV'],
                'publik',
                'https://example.test/dummy/materi/kesehatan-rujukan',
                'publik',
            ),
            $this->dummyBanmusItem(
                'Rabu, 4 Agustus 2027',
                'Rapat Paripurna Penyampaian Rancangan KUA dan PPAS Tahun 2028',
                'rapat',
                '2027-08-04',
                '10:00:00',
                '12:00:00',
                'Ruang Rapat Paripurna',
                null,
                ['Seluruh Anggota'],
                'publik',
                'https://example.test/dummy/materi/paripurna-kua-ppas',
                'publik',
                'https://example.test/dummy/live/paripurna-kua-ppas',
                'publik',
            ),
            $this->dummyBanmusItem(
                'Kamis, 5 Agustus 2027',
                'Rapat Gabungan Komisi dan Badan Anggaran',
                'rapat',
                '2027-08-05',
                '09:00:00',
                '12:00:00',
                'Ruang Rapat Utama',
                null,
                ['Gabungan Komisi', 'Badan Anggaran'],
                'internal',
                'https://example.test/dummy/materi/gabungan-komisi-banggar',
                'peserta',
            ),
            $this->dummyBanmusItem(
                'Jumat, 6 Agustus 2027',
                'Rapat Badan Kehormatan',
                'rapat',
                '2027-08-06',
                '09:00:00',
                '11:00:00',
                'Ruang Badan Kehormatan',
                null,
                ['Badan Kehormatan'],
                'internal',
            ),
            $this->dummyBanmusItem(
                'Senin, 9 Agustus 2027',
                'Rapat Pansus Tata Tertib DPRD',
                'rapat',
                '2027-08-09',
                '13:00:00',
                '16:00:00',
                'Ruang Pansus',
                null,
                ['Pansus Tata Tertib DPRD'],
                'internal',
                'https://example.test/dummy/materi/tata-tertib',
                'anggota',
            ),
            $this->dummyBanmusItem(
                'Selasa, 10 Agustus 2027',
                'Rapat Badan Musyawarah Penyesuaian Agenda Masa Persidangan',
                'rapat',
                '2027-08-10',
                '09:30:00',
                '11:30:00',
                'Ruang Badan Musyawarah',
                null,
                ['Badan Musyawarah'],
                'publik',
                null,
                'publik',
                'https://example.test/dummy/live/penyesuaian-banmus',
                'anggota',
            ),
            $this->dummyBanmusItem(
                'Rabu, 11 Agustus 2027',
                'Rapat Konsultasi Komisi IV dengan Kementerian Kesehatan',
                'rapat',
                '2027-08-11',
                '09:00:00',
                '12:00:00',
                null,
                'Kantor Kementerian Kesehatan, Jakarta',
                ['Komisi IV'],
                'internal',
            ),
            $this->dummyBanmusItem(
                'Kamis, 12 Agustus 2027',
                'Rapat Paripurna Persetujuan Bersama KUA dan PPAS Tahun 2028',
                'rapat',
                '2027-08-12',
                '10:00:00',
                '12:30:00',
                'Ruang Rapat Paripurna',
                null,
                ['Seluruh Anggota'],
                'publik',
                'https://example.test/dummy/materi/persetujuan-kua-ppas',
                'publik',
                'https://example.test/dummy/live/persetujuan-kua-ppas',
                'publik',
            ),
            $this->dummyBanmusItem(
                '13–16 Agustus 2027',
                'KUNDAPIL dan Pengawasan Penggunaan Anggaran',
                'non_rapat',
            ),
            $this->dummyBanmusItem(
                'Selasa, 17 Agustus 2027',
                'Peringatan Hari Ulang Tahun Proklamasi Kemerdekaan Republik Indonesia',
                'non_rapat',
                '2027-08-17',
                '07:30:00',
                '10:00:00',
                null,
                'Halaman Kantor Gubernur Sulawesi Tengah',
                ['Seluruh Anggota'],
                'publik',
            ),
            $this->dummyBanmusItem(
                '18–22 Agustus 2027',
                'Koordinasi dan Komunikasi Antar Daerah',
                'non_rapat',
            ),
            $this->dummyBanmusItem(
                'Jumat, 24 September 2027',
                'Rapat Paripurna Penutupan Masa Persidangan Ketiga Tahun Ketiga',
                'rapat',
                '2027-09-24',
                '09:30:00',
                '12:00:00',
                'Ruang Rapat Paripurna',
                null,
                ['Seluruh Anggota'],
                'publik',
                'https://example.test/dummy/materi/penutupan-masa-persidangan',
                'publik',
                'https://example.test/dummy/live/penutupan-masa-persidangan',
                'publik',
            ),
        ];
    }

    /**
     * @param list<string> $units
     * @return array<string, mixed>
     */
    private function dummyBanmusItem(
        string $periodLabel,
        string $agenda,
        string $type,
        ?string $date = null,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $room = null,
        ?string $otherLocation = null,
        array $units = [],
        string $publication = 'publik',
        ?string $materialUrl = null,
        string $materialAccess = 'publik',
        ?string $streamUrl = null,
        string $streamAccess = 'publik',
    ): array {
        $isComplete = $date !== null
            && $startTime !== null
            && $endTime !== null
            && ($room !== null || $otherLocation !== null)
            && $units !== [];

        return [
            'agenda' => self::DUMMY_PREFIX . $agenda,
            'jenis_agenda' => $type,
            'periode_label' => $periodLabel,
            'tanggal_mulai' => $date,
            'tanggal_selesai' => $date,
            'teks_tanggal_asli' => $periodLabel,
            'bulan_mulai' => $date !== null ? substr($date, 0, 7) : null,
            'bulan_selesai' => $date !== null ? substr($date, 0, 7) : null,
            'jumlah_pelaksanaan_rencana' => 1,
            'halaman_sumber' => null,
            'tanggal' => $date,
            'jam_mulai' => $startTime,
            'jam_selesai' => $endTime,
            'room' => $room,
            'lokasi_lainnya' => $otherLocation,
            'units' => $units,
            'publikasi' => $publication,
            'materi_url' => $materialUrl,
            'materi_akses' => $materialAccess,
            'stream_url' => $streamUrl,
            'stream_akses' => $streamAccess,
            'status' => JadwalBanmusModel::resolveLifecycleStatus(
                $isComplete,
                $date,
                $startTime,
                $endTime,
            ),
            'catatan' => self::DUMMY_PREFIX . 'Data simulasi item agenda Banmus.',
        ];
    }

    private function seedGeneralSchedules(): void
    {
        $roomIdsByName = $this->idsBy('ruangan', 'name');
        $unitIdsByName = $this->idsBy('unit_rapat', 'nama');
        $now = new DateTimeImmutable();
        $schedules = [
            $this->generalSchedule(
                'Rapat Koordinasi Sekretariat yang Telah Selesai',
                $now->modify('-3 hours'),
                $now->modify('-2 hours'),
                'Ruang Rapat Sekretariat',
                null,
                ['Pimpinan DPRD'],
                'internal',
            ),
            $this->generalSchedule(
                'Rapat Internal yang Sedang Berlangsung',
                $now->modify('-15 minutes'),
                $now->modify('+45 minutes'),
                'Ruang Komisi I',
                null,
                ['Komisi I'],
                'internal',
            ),
            $this->generalSchedule(
                'Rapat Persiapan Pimpinan DPRD',
                $now->modify('+15 minutes'),
                $now->modify('+75 minutes'),
                'Ruang Rapat Utama',
                null,
                ['Pimpinan DPRD'],
                'publik',
                'https://example.test/dummy/materi/persiapan-pimpinan',
                'anggota',
            ),
            $this->generalSchedule(
                'Rapat Insidental Menunggu Pelaksanaan',
                $now->modify('+2 hours'),
                $now->modify('+4 hours'),
                'Ruang Komisi II',
                null,
                ['Komisi II'],
                'publik',
                null,
                'publik',
                'https://example.test/dummy/live/rapat-insidental',
                'publik',
            ),
            $this->generalSchedule(
                'Rapat Koordinasi Persiapan Pelayanan Aspirasi',
                new DateTimeImmutable('2027-08-02 13:30:00'),
                new DateTimeImmutable('2027-08-02 15:30:00'),
                'Ruang Rapat Sekretariat',
                null,
                ['Pimpinan DPRD'],
                'internal',
            ),
            $this->generalSchedule(
                'Rapat Tindak Lanjut Hasil Audiensi Masyarakat',
                new DateTimeImmutable('2027-08-04 13:30:00'),
                new DateTimeImmutable('2027-08-04 15:30:00'),
                'Ruang Rapat Utama',
                null,
                ['Gabungan Komisi', 'Komisi I', 'Komisi II'],
                'publik',
            ),
            $this->generalSchedule(
                'Rapat Teknis Penyusunan Bahan Paripurna',
                new DateTimeImmutable('2027-08-05 13:30:00'),
                new DateTimeImmutable('2027-08-05 16:00:00'),
                'Ruang Rapat Sekretariat',
                null,
                ['Pimpinan DPRD'],
                'internal',
                'https://example.test/dummy/materi/bahan-paripurna',
                'peserta',
            ),
            $this->generalSchedule(
                'Rapat Konsultasi Komisi II dengan Tenaga Ahli',
                new DateTimeImmutable('2027-08-06 13:30:00'),
                new DateTimeImmutable('2027-08-06 15:30:00'),
                'Ruang Komisi II',
                null,
                ['Komisi II'],
                'internal',
            ),
            $this->generalSchedule(
                'Rapat Virtual Evaluasi Sistem Informasi DPRD',
                new DateTimeImmutable('2027-08-09 09:00:00'),
                new DateTimeImmutable('2027-08-09 11:00:00'),
                null,
                'Ruang Rapat Virtual Sekretariat DPRD',
                ['Pimpinan DPRD'],
                'publik',
                null,
                'publik',
                'https://example.test/dummy/live/evaluasi-sistem',
                'anggota',
            ),
            $this->generalSchedule(
                'Rapat Internal Finalisasi Notulen Badan Musyawarah',
                new DateTimeImmutable('2027-08-10 13:30:00'),
                new DateTimeImmutable('2027-08-10 15:00:00'),
                'Ruang Rapat Sekretariat',
                null,
                ['Badan Musyawarah'],
                'internal',
            ),
        ];

        foreach ($schedules as $schedule) {
            $unitNames = $schedule['units'];
            $roomName = $schedule['room'];
            unset($schedule['units'], $schedule['room']);

            $schedule['ruangan_id'] = is_string($roomName) && isset($roomIdsByName[$roomName])
                ? (int) $roomIdsByName[$roomName]
                : null;
            $this->db->table('jadwal_umum')
                ->insert($this->onlyExistingFields('jadwal_umum', $schedule));
            $scheduleId = (int) $this->db->insertID();

            foreach ($unitNames as $unitName) {
                $unitId = (int) ($unitIdsByName[$unitName] ?? 0);
                if ($unitId < 1) {
                    throw new RuntimeException("Unit rapat {$unitName} tidak ditemukan.");
                }

                $this->insertPivotIfMissing('jadwal_umum_unit_rapat', [
                    'jadwal_umum_id' => $scheduleId,
                    'unit_rapat_id' => $unitId,
                    'created_at' => date('Y-m-d H:i:s'),
                ], ['jadwal_umum_id', 'unit_rapat_id']);
            }
        }
    }

    /**
     * @param list<string> $units
     * @return array<string, mixed>
     */
    private function generalSchedule(
        string $title,
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        ?string $room,
        ?string $otherLocation,
        array $units,
        string $publication,
        ?string $materialUrl = null,
        string $materialAccess = 'publik',
        ?string $streamUrl = null,
        string $streamAccess = 'publik',
    ): array {
        return [
            'judul' => self::DUMMY_PREFIX . $title,
            'keterangan' => self::SEED_MARKER . ' ' . self::DUMMY_PREFIX . 'Jadwal Umum untuk demonstrasi lifecycle dan visibilitas.',
            'tanggal' => $start->format('Y-m-d'),
            'waktu_mulai' => $start->format('H:i:s'),
            'waktu_selesai' => $end->format('H:i:s'),
            'room' => $room,
            'lokasi_lainnya' => $otherLocation,
            'pihak_eksternal' => null,
            'units' => $units,
            'is_publik' => $publication === 'publik' ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function seedExternalGeneralSchedules(): void
    {
        $today = new DateTimeImmutable();
        $roomIdsByName = $this->idsBy('ruangan', 'name');
        $unitIdsByName = $this->idsBy('unit_rapat', 'nama');
        $agendas = [
            $this->generalAgenda(
                'Audiensi Forum Pelajar Sulawesi Tengah',
                'audiensi',
                'Forum Pelajar Sulawesi Tengah',
                $today->modify('+1 day')->setTime(9, 0),
                $today->modify('+1 day')->setTime(11, 0),
                'Halaman dan Ruang Aspirasi DPRD',
                'Surat permohonan audiensi',
                true,
                ['Pimpinan DPRD', 'Komisi I'],
            ),
            $this->generalAgenda(
                'Kunjungan Kerja DPRD Kabupaten Banggai',
                'kunjungan',
                'DPRD Kabupaten Banggai',
                new DateTimeImmutable('2027-08-02 10:00:00'),
                new DateTimeImmutable('2027-08-02 12:00:00'),
                'Ruang Rapat Utama',
                'Surat Sekretariat DPRD Kabupaten Banggai',
                true,
            ),
            $this->generalAgenda(
                'Audiensi Aliansi Masyarakat Peduli Pangan',
                'audiensi',
                'Aliansi Masyarakat Peduli Pangan',
                new DateTimeImmutable('2027-08-03 10:00:00'),
                new DateTimeImmutable('2027-08-03 12:00:00'),
                'Ruang Aspirasi DPRD',
                'Surat permohonan audiensi',
                true,
            ),
            $this->generalAgenda(
                'Undangan Forum Konsultasi Publik RKPD',
                'undangan',
                'Bappeda Provinsi Sulawesi Tengah',
                new DateTimeImmutable('2027-08-04 09:00:00'),
                new DateTimeImmutable('2027-08-04 12:00:00'),
                'Hotel Santika Palu',
                'Undangan Bappeda Provinsi Sulawesi Tengah',
                false,
            ),
            $this->generalAgenda(
                'Kunjungan Edukasi Mahasiswa Fakultas Hukum',
                'kunjungan',
                'Fakultas Hukum Universitas Tadulako',
                new DateTimeImmutable('2027-08-05 09:30:00'),
                new DateTimeImmutable('2027-08-05 11:30:00'),
                'Ruang Rapat Paripurna',
                'Surat Fakultas Hukum Universitas Tadulako',
                true,
            ),
            $this->generalAgenda(
                'Aksi Penyampaian Aspirasi Kebijakan Pertanian',
                'demonstrasi',
                'Koalisi Petani Sulawesi Tengah',
                new DateTimeImmutable('2027-08-06 10:00:00'),
                null,
                'Gerbang Utama Kantor DPRD',
                'Pemberitahuan kepolisian',
                false,
            ),
            $this->generalAgenda(
                'Bakti Sosial Donor Darah',
                'kegiatan_sosial',
                'Palang Merah Indonesia Provinsi Sulawesi Tengah',
                new DateTimeImmutable('2027-08-09 08:30:00'),
                new DateTimeImmutable('2027-08-09 12:00:00'),
                'Lobi Utama Kantor DPRD',
                'Surat PMI Provinsi Sulawesi Tengah',
                true,
            ),
            $this->generalAgenda(
                'Kunjungan Delegasi Forum Anak Daerah',
                'kunjungan',
                'Forum Anak Daerah Sulawesi Tengah',
                new DateTimeImmutable('2027-08-10 09:00:00'),
                new DateTimeImmutable('2027-08-10 11:00:00'),
                'Ruang Rapat Utama',
                'Surat Dinas Pemberdayaan Perempuan dan Perlindungan Anak',
                true,
            ),
            $this->generalAgenda(
                'Undangan Pembukaan Festival Budaya Sulawesi Tengah',
                'undangan',
                'Dinas Kebudayaan Provinsi Sulawesi Tengah',
                new DateTimeImmutable('2027-08-12 19:30:00'),
                new DateTimeImmutable('2027-08-12 22:00:00'),
                'Taman Budaya Sulawesi Tengah',
                'Undangan Dinas Kebudayaan',
                true,
            ),
            [
                'judul' => self::DUMMY_PREFIX . 'Kegiatan Orientasi Anggota Sepanjang Hari',
                'pihak_eksternal' => null,
                'tanggal' => '2027-08-11',
                'waktu_mulai' => null,
                'waktu_selesai' => null,
                'lokasi' => 'Ruang Rapat Utama',
                'keterangan' => self::SEED_MARKER . ' ' . self::DUMMY_PREFIX . 'Contoh Jadwal Umum sepanjang hari.',
                'is_publik' => 0,
                'units' => [],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($agendas as $agenda) {
            $unitNames = $agenda['units'] ?? [];
            $location = trim((string) $agenda['lokasi']);
            unset($agenda['units'], $agenda['lokasi']);
            $agenda['ruangan_id'] = $roomIdsByName[$location] ?? null;
            $agenda['lokasi_lainnya'] = $agenda['ruangan_id'] === null ? $location : null;

            $this->db->table('jadwal_umum')
                ->insert($this->onlyExistingFields('jadwal_umum', $agenda));
            $scheduleId = (int) $this->db->insertID();

            foreach ($unitNames as $unitName) {
                $unitId = (int) ($unitIdsByName[$unitName] ?? 0);
                if ($unitId < 1) {
                    throw new RuntimeException("Unit rapat {$unitName} tidak ditemukan.");
                }
                $this->insertPivotIfMissing('jadwal_umum_unit_rapat', [
                    'jadwal_umum_id' => $scheduleId,
                    'unit_rapat_id' => $unitId,
                    'created_at' => date('Y-m-d H:i:s'),
                ], ['jadwal_umum_id', 'unit_rapat_id']);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function generalAgenda(
        string $title,
        string $category,
        string $externalParty,
        DateTimeImmutable $start,
        ?DateTimeImmutable $end,
        string $location,
        string $source,
        bool $isPublic,
        array $units = [],
    ): array {
        $now = date('Y-m-d H:i:s');

        return [
            'judul' => self::DUMMY_PREFIX . $title,
            'pihak_eksternal' => $externalParty,
            'tanggal' => $start->format('Y-m-d'),
            'waktu_mulai' => $start->format('H:i:s'),
            'waktu_selesai' => $end?->format('H:i:s'),
            'lokasi' => $location,
            'keterangan' => self::SEED_MARKER . ' ' . self::DUMMY_PREFIX
                . 'Jadwal Umum dengan pihak eksternal. Sumber informasi: ' . $source,
            'is_publik' => $isPublic ? 1 : 0,
            'units' => $units,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @return array{stored: string, original: string}
     */
    private function storeSourcePdf(string $sourceFileName): array
    {
        $sourcePath = ROOTPATH . 'tes' . DIRECTORY_SEPARATOR . $sourceFileName;
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException("PDF sumber SK tidak ditemukan: {$sourcePath}");
        }

        $checksum = sha1_file($sourcePath);
        if (! is_string($checksum) || $checksum === '') {
            throw new RuntimeException("Checksum PDF sumber gagal dihitung: {$sourceFileName}");
        }

        $storedFileName = $checksum . '.pdf';
        $uploadDirectory = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'sk-banmus';
        if (! is_dir($uploadDirectory)
            && ! mkdir($uploadDirectory, 0750, true)
            && ! is_dir($uploadDirectory)) {
            throw new RuntimeException("Direktori unggahan SK tidak dapat dibuat: {$uploadDirectory}");
        }

        $targetPath = $uploadDirectory . DIRECTORY_SEPARATOR . $storedFileName;
        if (! is_file($targetPath) || filesize($targetPath) !== filesize($sourcePath)) {
            if (! copy($sourcePath, $targetPath)) {
                throw new RuntimeException("PDF sumber SK gagal disalin: {$sourceFileName}");
            }
        }

        return [
            'stored' => $storedFileName,
            'original' => $sourceFileName,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function upsertBy(string $table, string $uniqueField, array $row): void
    {
        $row = $this->onlyExistingFields($table, $row);
        $existing = $this->db->table($table)
            ->where($uniqueField, $row[$uniqueField])
            ->countAllResults() > 0;

        if ($existing) {
            $this->db->table($table)
                ->where($uniqueField, $row[$uniqueField])
                ->update($row);

            return;
        }

        $this->db->table($table)->insert($row);
    }

    /**
     * @return array<string, int>
     */
    private function idsBy(string $table, string $nameField): array
    {
        return array_map(
            'intval',
            array_column(
                $this->db->table($table)
                    ->select("id, {$nameField}")
                    ->get()
                    ->getResultArray(),
                'id',
                $nameField
            )
        );
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string>         $uniqueFields
     */
    private function insertPivotIfMissing(string $table, array $row, array $uniqueFields): void
    {
        $builder = $this->db->table($table);
        foreach ($uniqueFields as $field) {
            $builder->where($field, $row[$field]);
        }

        if ($builder->countAllResults() === 0) {
            $this->db->table($table)
                ->insert($this->onlyExistingFields($table, $row));
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function onlyExistingFields(string $table, array $row): array
    {
        $fields = array_flip($this->db->getFieldNames($table));

        return array_intersect_key($row, $fields);
    }
}
