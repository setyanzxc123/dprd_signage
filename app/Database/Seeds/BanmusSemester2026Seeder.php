<?php

namespace App\Database\Seeds;

use App\Models\JadwalUmumModel;
use CodeIgniter\Database\Seeder;
use RuntimeException;
use Throwable;

/**
 * Dataset bersih tahun 2026: jadwal banmus semester 1 dan 2 dari lampiran
 * SK Pimpinan DPRD, serta jadwal umum dummy sepanjang tahun.
 *
 * Tanggal dan jam operasional merupakan karangan di dalam periode SK agar
 * setiap item memiliki status terjadwal, bukan proyeksi. Teks tanggal asli
 * dari lampiran tetap disimpan pada kolom teks_tanggal_asli. Berkas PDF SK
 * sengaja dibiarkan kosong untuk diunggah manual.
 *
 * Anggota dan kelompok peserta mengikuti pola CurrentSystemDataSeeder.
 */
class BanmusSemester2026Seeder extends Seeder
{
    private const SEED_MARKER = '[BanmusSemester2026Seeder]';
    private const DUMMY_PREFIX = '(Dummy) ';
    private const SAMPLE_MEMBER_PHONE = '85156049890';
    private const SK_ONE_NUMBER = '160/1/2026';
    private const SK_NINE_NUMBER = '160/9/2026';
    private const DUMMY_NOTE = 'Tanggal dan jam operasional merupakan karangan data dummy di dalam periode SK.';

    public function run(): void
    {
        $this->assertCurrentSchema();

        $this->db->transBegin();

        try {
            $this->seedRooms();
            $this->seedMeetingUnits();
            $this->seedMembers();
            $this->cleanLegacyDummyData();
            $this->seedBanmusDocuments();
            $this->seedGeneralSchedules();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Database menolak sebagian data seeder.');
            }

            $this->db->transCommit();
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    private function assertCurrentSchema(): void
    {
        foreach ([
            'ruangan',
            'unit_rapat',
            'anggota',
            'anggota_unit_rapat',
            'dokumen_banmus',
            'jadwal_banmus',
            'jadwal_banmus_unit_rapat',
            'jadwal_umum',
            'jadwal_umum_unit_rapat',
        ] as $table) {
            if (! $this->db->tableExists($table)) {
                throw new RuntimeException(
                    "Tabel {$table} belum tersedia. Jalankan `php spark migrate` sebelum menjalankan seeder."
                );
            }
        }
    }

    private function seedRooms(): void
    {
        $rooms = [
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

        foreach ($rooms as $room) {
            $this->upsertBy('ruangan', 'name', $room);
        }
    }

    private function seedMeetingUnits(): void
    {
        $now = date('Y-m-d H:i:s');
        $units = [
            10 => ['Komisi I', 'komisi_anggota'],
            20 => ['Komisi II', 'komisi_anggota'],
            30 => ['Komisi III', 'komisi_anggota'],
            40 => ['Komisi IV', 'komisi_anggota'],
            50 => ['Badan Anggaran', 'manual'],
            60 => ['Badan Musyawarah', 'manual'],
            70 => ['Bapemperda', 'manual'],
            80 => ['Badan Kehormatan', 'manual'],
            90 => ['Gabungan Komisi', 'manual'],
            91 => ['Pimpinan DPRD', 'manual'],
            92 => ['Ketua Fraksi', 'manual'],
            95 => ['Pansus Ranperda Pajak Daerah', 'manual'],
            96 => ['Pansus Tata Tertib DPRD', 'manual'],
            97 => ['Tim Pembahas RAPBD', 'manual'],
            98 => ['Tim Kunjungan Kerja', 'manual'],
            100 => ['Seluruh Anggota', 'semua_anggota'],
        ];

        foreach ($units as $order => [$name, $membershipType]) {
            $this->upsertBy('unit_rapat', 'nama', [
                'nama'            => $name,
                'membership_type' => $membershipType,
                'aktif'           => 1,
                'urutan'          => $order,
                'created_at'      => $now,
                'updated_at'      => $now,
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
            $this->db->table('anggota_unit_rapat')
                ->where('anggota_id', (int) $member['id'])
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

        foreach ($this->sampleMemberUnitNames() as $unitName) {
            $assignments[$unitName][] = self::SAMPLE_MEMBER_PHONE;
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
                'user_id' => null,
                'foto'    => null,
            ];
        }

        $rows[] = [
            'name'    => self::DUMMY_PREFIX . 'Anggota Uji Agenda',
            'jabatan' => 'Anggota DPRD (Akun Uji)',
            'fraksi'  => 'Golongan Karya',
            'komisi'  => 'Komisi I',
            'no_wa'   => self::SAMPLE_MEMBER_PHONE,
            'aktif'   => 1,
            'user_id' => null,
            'foto'    => null,
        ];

        return $rows;
    }

    /**
     * Kelompok lintas agenda untuk akun uji agar perbedaan Semua Jadwal dan
     * Jadwal Saya terlihat saat demonstrasi.
     */
    private function sampleMemberUnitNames(): array
    {
        return [
            'Seluruh Anggota',
            'Komisi I',
            'Badan Musyawarah',
            'Badan Anggaran',
            'Bapemperda',
            'Gabungan Komisi',
            'Pimpinan DPRD',
            'Ketua Fraksi',
            'Pansus Ranperda Pajak Daerah',
            'Pansus Tata Tertib DPRD',
            'Tim Pembahas RAPBD',
            'Tim Kunjungan Kerja',
        ];
    }

    /**
     * Menghapus data dummy dari seeder lama: SK banmus bertanda DUMMY beserta
     * seluruh jadwal umum dummy dari seeder sebelumnya.
     */
    private function cleanLegacyDummyData(): void
    {
        $this->db->table('dokumen_banmus')
            ->like('nomor_sk', 'DUMMY', 'after')
            ->delete();

        $markers = [
            self::SEED_MARKER,
            '[CurrentSystemDataSeeder]',
            'Data contoh Jadwal Umum dari BamusMasaPersidanganKetiga2026Seeder',
            'Data contoh rapat dekat waktu sekarang dari BamusMasaPersidanganKetiga2026Seeder',
        ];

        foreach ($markers as $marker) {
            $ids = array_map(
                'intval',
                array_column(
                    $this->db->table('jadwal_umum')
                        ->select('id')
                        ->like('keterangan', $marker, 'after')
                        ->get()
                        ->getResultArray(),
                    'id'
                )
            );

            if ($ids === []) {
                continue;
            }

            $this->db->table('jadwal_umum_unit_rapat')->whereIn('jadwal_umum_id', $ids)->delete();
            $this->db->table('jadwal_umum')->whereIn('id', $ids)->delete();
        }
    }

    private function seedBanmusDocuments(): void
    {
        $documents = [
            [
                'document' => [
                    'judul' => 'Penetapan Jadwal Kegiatan Masa Persidangan Kedua Tahun Kedua DPRD Provinsi Sulawesi Tengah Masa Jabatan 2024-2029',
                    'nomor_sk' => self::SK_ONE_NUMBER,
                    'tanggal_sk' => '2026-01-21',
                    'tahun' => 2026,
                    'semester' => 1,
                    'masa_persidangan' => 'Masa Persidangan Kedua Tahun Kedua',
                    'periode_mulai' => '2026-01-01',
                    'periode_selesai' => '2026-05-25',
                    'status' => 'disahkan',
                    'is_publik' => 1,
                    'dokumen_file' => null,
                    'dokumen_nama_asli' => null,
                    'dokumen_url' => null,
                    'catatan' => 'Sumber resmi: Keputusan Pimpinan DPRD Provinsi Sulawesi Tengah Nomor 160/1/2026 tanggal 21 Januari 2026. ' . self::DUMMY_NOTE,
                ],
                'items' => $this->semesterOneItems(),
            ],
            [
                'document' => [
                    'judul' => 'Penetapan Jadwal Kegiatan Masa Persidangan Ketiga Tahun Kedua DPRD Provinsi Sulawesi Tengah Masa Jabatan 2024-2029',
                    'nomor_sk' => self::SK_NINE_NUMBER,
                    'tanggal_sk' => '2026-05-22',
                    'tahun' => 2026,
                    'semester' => 2,
                    'masa_persidangan' => 'Masa Persidangan Ketiga Tahun Kedua',
                    'periode_mulai' => '2026-05-27',
                    'periode_selesai' => '2026-09-22',
                    'status' => 'disahkan',
                    'is_publik' => 1,
                    'dokumen_file' => null,
                    'dokumen_nama_asli' => null,
                    'dokumen_url' => null,
                    'catatan' => 'Sumber resmi: Keputusan Pimpinan DPRD Provinsi Sulawesi Tengah Nomor 160/9/2026 tanggal 22 Mei 2026. ' . self::DUMMY_NOTE,
                ],
                'items' => $this->semesterTwoItems(),
            ],
        ];

        foreach ($documents as $document) {
            $this->upsertBanmusDocument($document['document'], $document['items']);
        }
    }

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
            $unitNames = $item['units'];
            $roomName = $item['room'];
            unset($item['units'], $item['room']);

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
            $this->db->table('jadwal_banmus_unit_rapat')->whereIn('jadwal_banmus_id', $itemIds)->delete();
        }

        $this->db->table('jadwal_banmus')
            ->where('dokumen_banmus_id', $documentId)
            ->delete();
    }

    /**
     * Item lampiran SK dengan tanggal operasional konkret. Untuk kegiatan
     * berdurasi (reses, koordinasi, kunjungan) tanggal_mulai-tanggal_selesai
     * dipakai sebagai rentang; rapat memakai satu tanggal dengan jam.
     */
    private function item(
        string $teksAsli,
        string $agenda,
        string $type,
        string $tanggalMulai,
        ?string $tanggalSelesai,
        ?string $jamMulai,
        ?string $jamSelesai,
        ?string $room,
        array $units,
        int $halaman,
    ): array {
        $tanggalSelesai ??= $tanggalMulai;

        return [
            'agenda'            => $agenda,
            'jenis_agenda'      => $type,
            'periode_label'     => $teksAsli,
            'tanggal_mulai'     => $tanggalMulai,
            'tanggal_selesai'   => $tanggalSelesai,
            'teks_tanggal_asli' => $teksAsli,
            'bulan_mulai'       => substr($tanggalMulai, 0, 7),
            'bulan_selesai'     => substr($tanggalSelesai, 0, 7),
            'jumlah_pelaksanaan_rencana' => 1,
            'halaman_sumber'    => $halaman,
            'tanggal'           => $tanggalMulai,
            'jam_mulai'         => $jamMulai,
            'jam_selesai'       => $jamSelesai,
            'room'              => $room,
            'lokasi_lainnya'    => null,
            'units'             => $units,
            'publikasi'         => 'publik',
            'materi_url'        => null,
            'materi_akses'      => 'publik',
            'stream_url'        => null,
            'stream_akses'      => 'publik',
            'status'            => 'menunggu',
            'catatan'           => self::DUMMY_NOTE,
        ];
    }

    /**
     * Lampiran SK 160/1/2026 (semester 1). Teks mengikuti kolom tanggal
     * pelaksanaan dan uraian kegiatan pada lampiran.
     */
    private function semesterOneItems(): array
    {
        return [
            $this->item('Januari-April 2026', 'Rapat Pembahasan dan Penyampaian Rekomendasi Panitia Khusus di Luar Raperda', 'rapat', '2026-02-11', null, '09:00:00', '11:00:00', 'Ruang Pansus', ['Pansus Tata Tertib DPRD', 'Tim Kunjungan Kerja'], 3),
            $this->item('Selasa, 3 Februari 2026', 'Rapat Kerja Pimpinan/Ketua AKD/Ketua Fraksi', 'rapat', '2026-02-03', null, '09:00:00', '11:00:00', 'Ruang Rapat Utama', ['Pimpinan DPRD', 'Ketua Fraksi'], 3),
            $this->item('Rabu, 4-8 Februari 2026', 'Koordinasi dan Komunikasi Dalam Daerah', 'non_rapat', '2026-02-04', '2026-02-08', null, null, null, [], 3),
            $this->item('Senin, 9-16 Februari 2026', 'Reses', 'non_rapat', '2026-02-09', '2026-02-16', null, null, null, [], 3),
            $this->item('Selasa, 17 Februari 2026', 'Tahun Baru Imlek', 'non_rapat', '2026-02-17', null, null, null, null, [], 3),
            $this->item('Februari-Mei 2026', 'Pembahasan/Penetapan Rancangan Peraturan Daerah Provinsi Sulawesi Tengah Tahun 2026', 'rapat', '2026-03-05', null, '09:00:00', '11:00:00', 'Ruang Bapemperda', ['Bapemperda'], 3),
            $this->item('Februari-Mei 2026', 'Pelaksanaan Rakor Forum DPRD Penghasil Nikel', 'rapat', '2026-04-16', null, '09:00:00', '11:00:00', 'Ruang Rapat Utama', ['Gabungan Komisi'], 3),
            $this->item('Kamis, 19 Maret 2026', 'Hari Nyepi', 'non_rapat', '2026-03-19', null, null, null, null, [], 3),
            $this->item('Maret 2026', 'Rapat Paripurna Penyampaian Laporan Hasil Reses Masa Persidangan Ke-II Tahun Kedua', 'rapat', '2026-03-26', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 3),
            $this->item('Maret 2026', 'Rapat Pembahasan dan Paripurna Penyampaian Pokok-Pokok Pikiran DPRD Tahun 2027 serta Penyusunan dan Penginputan', 'rapat', '2026-03-31', null, '09:00:00', '11:00:00', 'Ruang Rapat Utama', ['Pimpinan DPRD', 'Ketua Fraksi', 'Gabungan Komisi'], 3),
            $this->item('Maret 2026', 'Rapat Forum OPD Provinsi Sulawesi Tengah', 'rapat', '2026-03-25', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 3),
            $this->item('Sabtu, 21 Maret 2026', 'Hari Raya Idul Fitri 1447 H', 'non_rapat', '2026-03-21', null, null, null, null, [], 3),
            $this->item('Minggu, 18-24 Maret 2026', 'Cuti Bersama Hari Raya Nyepi dan Idul Fitri 1447 H', 'non_rapat', '2026-03-18', '2026-03-24', null, null, null, [], 4),
            $this->item('Senin, 30 Maret 2026', 'Rapat Paripurna Penyampaian Laporan Keterangan Pertanggungjawaban Kepala Daerah Tahun 2025', 'rapat', '2026-03-30', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 4),
            $this->item('Jumat, 3 April 2026', 'Wafat Yesus Kristus', 'non_rapat', '2026-04-03', null, null, null, null, [], 4),
            $this->item('Senin, 6-11 April 2026', 'Pelaksanaan KUNDAPIL/Pengawasan Penggunaan Anggaran', 'non_rapat', '2026-04-06', '2026-04-11', null, null, null, [], 4),
            $this->item('April 2026', 'Musrenbang RKPD Provinsi Sulawesi Tengah', 'non_rapat', '2026-04-15', null, null, null, null, [], 4),
            $this->item('Jumat, 10 April 2026', 'Rapat Paripurna Hari Ulang Tahun Provinsi Sulawesi Tengah', 'rapat', '2026-04-10', null, '09:00:00', '10:30:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 4),
            $this->item('Senin, 13 April 2026', 'HUT Provinsi Sulawesi Tengah', 'non_rapat', '2026-04-13', null, null, null, null, [], 4),
            $this->item('April 2026', 'Koordinasi dan Komunikasi Antar Daerah', 'non_rapat', '2026-04-20', '2026-04-24', null, null, null, [], 4),
            $this->item('April 2026', 'Rapat Dengar Pendapat Masing-Masing Komisi Beserta Mitra Kerja', 'rapat', '2026-04-23', null, '09:00:00', '11:00:00', 'Ruang Rapat Utama', ['Komisi I', 'Komisi II', 'Komisi III', 'Komisi IV'], 4),
            $this->item('Rabu, 29 April 2026', 'Rapat Paripurna Penyampaian Rekomendasi Pansus LKPJ Kepala Daerah Tahun 2025', 'rapat', '2026-04-29', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 4),
            $this->item('April 2026', 'Rapat Kerja Pimpinan/Ketua Fraksi/Alat Kelengkapan Dewan', 'rapat', '2026-04-21', null, '10:00:00', '12:00:00', 'Ruang Rapat Utama', ['Pimpinan DPRD', 'Ketua Fraksi'], 4),
            $this->item('Jumat, 1 Mei 2026', 'Hari Buruh Internasional', 'non_rapat', '2026-05-01', null, null, null, null, [], 4),
            $this->item('Sabtu, 2 Mei 2026', 'Hari Pendidikan Nasional', 'non_rapat', '2026-05-02', null, null, null, null, [], 4),
            $this->item('Senin, 4 Mei 2026', 'Rapat Paripurna Penyampaian Laporan Hasil Koordinasi dan Komunikasi Dalam Daerah dan Antar Daerah Masa Persidangan Ke-II Tahun Kedua', 'rapat', '2026-05-04', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 4),
            $this->item('Kamis, 14 Mei 2026', 'Kenaikan Yesus Kristus', 'non_rapat', '2026-05-14', null, null, null, null, [], 4),
            $this->item('Mei 2026', 'Rapat Kerja Pimpinan/Ketua Fraksi/Alat Kelengkapan Dewan', 'rapat', '2026-05-19', null, '10:00:00', '12:00:00', 'Ruang Rapat Utama', ['Pimpinan DPRD', 'Ketua Fraksi'], 4),
            $this->item('Jumat, 22 Mei 2026', 'Rapat Badan Musyawarah dengan Acara Pembahasan/Penetapan Jadwal Kegiatan Masa Persidangan Ke-III Tahun Kedua', 'rapat', '2026-05-22', null, '09:00:00', '11:00:00', 'Ruang Badan Musyawarah', ['Badan Musyawarah'], 4),
            $this->item('Senin, 25 Mei 2026', 'Rapat Paripurna Penutupan Masa Persidangan Ke-II Tahun Kedua dan Pembukaan Masa Persidangan Ke-III Tahun Kedua', 'rapat', '2026-05-25', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 5),
        ];
    }

    /**
     * Lampiran SK 160/9/2026 (semester 2).
     */
    private function semesterTwoItems(): array
    {
        return [
            $this->item('Rabu, 27 Mei 2026', 'Hari Raya Idul Adha 1447 H', 'non_rapat', '2026-05-27', null, null, null, null, [], 3),
            $this->item('Kamis, 28 Mei 2026', 'Cuti Bersama', 'non_rapat', '2026-05-28', null, null, null, null, [], 3),
            $this->item('Senin, 1 Juni 2026', 'Hari Lahir Pancasila', 'non_rapat', '2026-06-01', null, null, null, null, [], 3),
            $this->item('Selasa, 2 Juni 2026', 'Rapat Paripurna Penutupan Masa Persidangan Ke-II Tahun Kedua, sekaligus Pembukaan Masa Persidangan Ke-III Tahun Kedua, dan Penyampaian Laporan Hasil Pemeriksaan (LHP) BPK', 'rapat', '2026-06-02', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 3),
            $this->item('Rabu, 3 Juni 2026', 'Rapat Paripurna dengan Acara Pengumuman Perubahan Komposisi AKD dari Fraksi PDI Perjuangan, serta Pembubaran dan Pembentukan Pansus', 'rapat', '2026-06-03', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 3),
            $this->item('Selasa, 16 Juni 2026', 'Tahun Baru Islam 1448 H', 'non_rapat', '2026-06-16', null, null, null, null, [], 3),
            $this->item('Juni 2026', 'Rapat Paripurna Penyampaian Laporan Hasil Koordinasi dan Komunikasi Dalam Daerah dan Antar Daerah Masa Persidangan Ke-II Tahun Kedua', 'rapat', '2026-06-11', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 3),
            $this->item('Juni 2026', 'Rapat Komisi dengan Mitra Kerja dalam Rangka Koordinasi dan Komunikasi Dalam Daerah dan Antar Daerah', 'rapat', '2026-06-17', null, '09:00:00', '11:00:00', 'Ruang Rapat Utama', ['Komisi I', 'Komisi II', 'Komisi III', 'Komisi IV'], 3),
            $this->item('Juni 2026', 'Rapat Kerja Pimpinan/Ketua Fraksi/Alat Kelengkapan Dewan', 'rapat', '2026-06-23', null, '10:00:00', '12:00:00', 'Ruang Rapat Utama', ['Pimpinan DPRD', 'Ketua Fraksi'], 3),
            $this->item('Juni 2026', 'Pengawasan Penggunaan Anggaran/KUNDAPIL', 'non_rapat', '2026-06-24', '2026-06-30', null, null, null, [], 3),
            $this->item('Juni-Juli 2026', 'Rapat Paripurna/Rapat Badan Anggaran dengan Acara Pembahasan dan Penetapan Raperda tentang Pertanggungjawaban Pelaksanaan APBD Tahun Anggaran 2025', 'rapat', '2026-07-02', null, '09:00:00', '11:00:00', 'Ruang Badan Anggaran', ['Seluruh Anggota', 'Badan Anggaran'], 3),
            $this->item('Juni-Juli 2026', 'Rapat Penyusunan, Pembahasan, dan Penetapan RENJA DPRD Sulawesi Tengah Tahun 2027', 'rapat', '2026-07-08', null, '09:00:00', '11:00:00', 'Ruang Badan Musyawarah', ['Badan Musyawarah', 'Tim Pembahas RAPBD'], 4),
            $this->item('Juni-September 2026', 'Pembahasan/Penetapan Rancangan Peraturan Daerah Provinsi Sulawesi Tengah Tahun 2026', 'rapat', '2026-07-16', null, '09:00:00', '11:00:00', 'Ruang Bapemperda', ['Bapemperda'], 4),
            $this->item('Juni-September 2026', 'Rapat-Rapat Pembahasan Raperda, AKD, Fraksi, dan Agenda Kedewanan Lainnya', 'rapat', '2026-07-30', null, '09:00:00', '11:00:00', 'Ruang Rapat Utama', ['Bapemperda', 'Ketua Fraksi', 'Gabungan Komisi'], 4),
            $this->item('Juni-September 2026', 'Rapat Paripurna/Pembahasan Rekomendasi Panitia Khusus DPRD Provinsi Sulawesi Tengah: Penyintas Bencana Gempa Bumi 28 September 2018, Penyelesaian Konflik Agraria Perkebunan Kelapa Sawit di Kabupaten Tolitoli, dan Reinventarisasi Aset', 'rapat', '2026-08-13', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota', 'Pansus Tata Tertib DPRD'], 4),
            $this->item('Rabu, 1-5 Juli 2026', 'Koordinasi dan Komunikasi Dalam Daerah', 'non_rapat', '2026-07-01', '2026-07-05', null, null, null, [], 4),
            $this->item('Rabu, 8-15 Juli 2026', 'Koordinasi dan Komunikasi Antar Daerah', 'non_rapat', '2026-07-08', '2026-07-15', null, null, null, [], 4),
            $this->item('Juli 2026', 'Rapat Paripurna Penyampaian Laporan Semester Pertama Tahun 2026', 'rapat', '2026-07-27', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 4),
            $this->item('Juli-Agustus 2026', 'Rapat Paripurna/Rapat Badan Anggaran dengan Acara Pembahasan dan Penetapan KUA dan PPAS Perubahan Tahun Anggaran 2026', 'rapat', '2026-07-23', null, '09:00:00', '11:00:00', 'Ruang Badan Anggaran', ['Seluruh Anggota', 'Badan Anggaran'], 4),
            $this->item('Juli-Agustus 2026', 'Rapat Paripurna/Rapat Badan Anggaran dalam Rangka Pembahasan dan Penetapan KUA dan PPAS Tahun 2027', 'rapat', '2026-08-06', null, '09:00:00', '11:00:00', 'Ruang Badan Anggaran', ['Seluruh Anggota', 'Badan Anggaran'], 4),
            $this->item('Juli-Agustus 2026', 'Reses', 'non_rapat', '2026-08-17', '2026-08-28', null, null, null, [], 4),
            $this->item('Senin, 17 Agustus 2026', 'HUT Proklamasi Kemerdekaan Republik Indonesia ke-81', 'non_rapat', '2026-08-17', null, null, null, null, [], 4),
            $this->item('Selasa, 25 Agustus 2026', 'Maulid Nabi Muhammad S.A.W.', 'non_rapat', '2026-08-25', null, null, null, null, [], 4),
            $this->item('Agustus-September 2026', 'Rapat Paripurna Pembahasan/Penetapan RAPBD Perubahan Tahun Anggaran 2026: Penyampaian Ranperda Perubahan APBD oleh Kepala Daerah kepada DPRD dan Persetujuan Bersama DPRD dan Kepala Daerah', 'rapat', '2026-09-03', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota', 'Badan Anggaran'], 5),
            $this->item('Senin, 21 September 2026', 'Rapat Badan Musyawarah dengan Acara Pembahasan/Penetapan Jadwal Kegiatan Masa Persidangan Ke-I Tahun Ketiga DPRD Provinsi Sulawesi Tengah', 'rapat', '2026-09-21', null, '09:00:00', '11:00:00', 'Ruang Badan Musyawarah', ['Badan Musyawarah'], 5),
            $this->item('Selasa, 22 September 2026', 'Rapat Paripurna dengan Acara Penutupan Masa Persidangan Ke-III Tahun Kedua, sekaligus Pembukaan Masa Persidangan Ke-I Tahun Ketiga Periode Tahun 2024-2029', 'rapat', '2026-09-22', null, '09:00:00', '11:00:00', 'Ruang Rapat Paripurna', ['Seluruh Anggota'], 5),
        ];
    }

    /**
     * Jadwal umum dummy sepanjang 2026: rapat internal dan kegiatan non-rapat
     * publik tiap bulan, termasuk bulan-bulan setelah masa persidangan ketiga
     * agar panel tetap memiliki agenda mendatang.
     */
    private function seedGeneralSchedules(): void
    {
        $roomIdsByName = $this->idsBy('ruangan', 'name');
        $unitIdsByName = $this->idsBy('unit_rapat', 'nama');
        $now = date('Y-m-d H:i:s');

        foreach ($this->generalScheduleItems() as $item) {
            $roomName = $item['room'];
            unset($item['room']);

            $row = $item + [
                'ruangan_id' => is_string($roomName) && isset($roomIdsByName[$roomName])
                    ? (int) $roomIdsByName[$roomName]
                    : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->db->table('jadwal_umum')->insert($this->onlyExistingFields('jadwal_umum', $row));
            $scheduleId = (int) $this->db->insertID();

            foreach ($row['units'] as $unitName) {
                $unitId = (int) ($unitIdsByName[$unitName] ?? 0);
                if ($unitId < 1) {
                    throw new RuntimeException("Unit rapat {$unitName} tidak ditemukan.");
                }

                $this->insertPivotIfMissing('jadwal_umum_unit_rapat', [
                    'jadwal_umum_id' => $scheduleId,
                    'unit_rapat_id' => $unitId,
                    'created_at' => $now,
                ], ['jadwal_umum_id', 'unit_rapat_id']);
            }
        }
    }

    private function generalScheduleItem(
        string $date,
        string $title,
        string $type,
        ?string $jamMulai,
        ?string $jamSelesai,
        ?string $room,
        ?string $otherLocation,
        array $units,
        int $isPublik,
        ?string $pihakEksternal = null,
    ): array {
        return [
            'judul'           => self::DUMMY_PREFIX . $title,
            'jenis_agenda'    => $type,
            'keterangan'      => self::SEED_MARKER . ' Data contoh Jadwal Umum tahun 2026.',
            'tanggal'         => $date,
            'tanggal_mulai'   => $date,
            'tanggal_selesai' => $date,
            'waktu_mulai'     => $type === 'rapat' ? $jamMulai : null,
            'waktu_selesai'   => $type === 'rapat' ? $jamSelesai : null,
            'room'            => $type === 'rapat' ? $room : null,
            'lokasi_lainnya'  => $otherLocation,
            'pihak_eksternal' => $pihakEksternal,
            'units'           => $units,
            'is_publik'       => $isPublik,
            'materi_url'      => null,
            'materi_akses'    => 'publik',
            'stream_url'      => null,
            'stream_akses'    => 'publik',
            'status'          => $type === 'rapat'
                ? JadwalUmumModel::STATUS_MENUNGGU
                : JadwalUmumModel::STATUS_NON_RAPAT,
            'undangan_file'      => null,
            'undangan_nama_asli' => null,
        ];
    }

    private function generalScheduleItems(): array
    {
        return [
            $this->generalScheduleItem('2026-01-14', 'Rapat Koordinasi Awal Tahun Pimpinan dan Fraksi', 'rapat', '09:00:00', '11:00:00', 'Ruang Rapat Utama', null, ['Pimpinan DPRD', 'Ketua Fraksi'], 0),
            $this->generalScheduleItem('2026-01-28', 'Kunjungan Kerja Komisi III ke Instansi Vertikal', 'non_rapat', null, null, null, 'Kabupaten Buol', [], 1, 'Instansi Vertikal di Wilayah Sulawesi Tengah'),
            $this->generalScheduleItem('2026-02-11', 'Rapat Dengar Pendapat Komisi II dengan OPD Terkait', 'rapat', '09:00:00', '11:00:00', 'Ruang Komisi II', null, ['Komisi II'], 0),
            $this->generalScheduleItem('2026-02-25', 'Audiensi Dewan Kesehatan Daerah', 'rapat', '10:00:00', '12:00:00', 'Ruang Rapat Utama', null, ['Pimpinan DPRD'], 1, 'Dewan Kesehatan Daerah Provinsi Sulawesi Tengah'),
            $this->generalScheduleItem('2026-03-10', 'Rapat Kerja Pansus Penyusunan Ranperda', 'rapat', '09:00:00', '11:00:00', 'Ruang Pansus', null, ['Pansus Ranperda Pajak Daerah'], 0),
            $this->generalScheduleItem('2026-03-26', 'Bakti Sosial dan Donor Darah DPRD', 'non_rapat', null, null, null, 'Selasar Gedung DPRD', [], 1),
            $this->generalScheduleItem('2026-04-08', 'Rapat Koordinasi Badan Anggaran Pra-Musrenbang', 'rapat', '10:00:00', '12:00:00', 'Ruang Badan Anggaran', null, ['Badan Anggaran'], 0),
            $this->generalScheduleItem('2026-04-22', 'Kunjungan Edukasi Pelajar ke Gedung DPRD', 'non_rapat', null, null, null, 'Lobi Utama Gedung DPRD', [], 1),
            $this->generalScheduleItem('2026-05-13', 'Rapat Gabungan Komisi Evaluasi Program Prioritas', 'rapat', '09:00:00', '11:00:00', 'Ruang Rapat Utama', null, ['Gabungan Komisi'], 0),
            $this->generalScheduleItem('2026-05-20', 'Pameran Karya Percepatan Pembangunan Daerah', 'non_rapat', null, null, null, 'Selasar Gedung DPRD', [], 1),
            $this->generalScheduleItem('2026-06-10', 'Rapat Koordinasi Pimpinan dengan Sekretariat Daerah', 'rapat', '10:00:00', '12:00:00', 'Ruang Rapat Utama', null, ['Pimpinan DPRD'], 0, 'Sekretariat Daerah Provinsi Sulawesi Tengah'),
            $this->generalScheduleItem('2026-06-24', 'Seminar Publik Partisipasi Masyarakat dalam Pembangunan', 'rapat', '09:00:00', '12:00:00', 'Ruang Rapat Paripurna', null, ['Seluruh Anggota'], 1, 'Masyarakat dan Organisasi Masyarakat Sipil'),
            $this->generalScheduleItem('2026-07-15', 'Rapat Kerja Komisi IV dengan Dinas Terkait Pangan', 'rapat', '09:00:00', '11:00:00', 'Ruang Komisi IV', null, ['Komisi IV'], 0),
            $this->generalScheduleItem('2026-07-29', 'Aksi Penyampaian Aspirasi Masyarakat', 'non_rapat', null, null, null, 'Halaman Gedung DPRD', [], 1),
            $this->generalScheduleItem('2026-08-12', 'Rapat Persiapan Pelaksanaan Reses Fraksi', 'rapat', '10:00:00', '12:00:00', 'Ruang Rapat Utama', null, ['Pimpinan DPRD', 'Ketua Fraksi'], 0),
            $this->generalScheduleItem('2026-09-09', 'Pameran Ekonomi Kreatif Daerah', 'non_rapat', null, null, null, 'Selasar Gedung DPRD', [], 1),
            $this->generalScheduleItem('2026-09-23', 'Rapat Evaluasi Penutupan Masa Persidangan', 'rapat', '13:00:00', '15:00:00', 'Ruang Rapat Utama', null, ['Pimpinan DPRD', 'Ketua Fraksi'], 0),
            $this->generalScheduleItem('2026-10-14', 'Rapat Kerja Pimpinan dan Alat Kelengkapan Dewan', 'rapat', '09:00:00', '11:00:00', 'Ruang Rapat Utama', null, ['Pimpinan DPRD', 'Ketua Fraksi'], 0),
            $this->generalScheduleItem('2026-10-28', 'Kunjungan Sivitas Akademika Universitas Tadulako', 'non_rapat', null, null, null, 'Lobi dan Auditorium Gedung DPRD', [], 1, 'Universitas Tadulako'),
            $this->generalScheduleItem('2026-11-11', 'Rapat Badan Kehormatan Pembahasan Kode Etik', 'rapat', '09:00:00', '11:00:00', 'Ruang Badan Kehormatan', null, ['Badan Kehormatan'], 0),
            $this->generalScheduleItem('2026-11-25', 'Audiensi Asosiasi Petani Karet Sulawesi Tengah', 'rapat', '10:00:00', '11:30:00', 'Ruang Rapat Utama', null, ['Pimpinan DPRD'], 1, 'Asosiasi Petani Karet Sulawesi Tengah'),
            $this->generalScheduleItem('2026-12-09', 'Rapat Koordinasi Penyusunan Pokok-Pokok Pikiran DPRD', 'rapat', '09:00:00', '11:00:00', 'Ruang Rapat Utama', null, ['Pimpinan DPRD', 'Gabungan Komisi'], 0),
            $this->generalScheduleItem('2026-12-16', 'Perayaan Natal Bersama Pimpinan dan Anggota DPRD', 'non_rapat', null, null, null, 'Aula Gedung DPRD', [], 1),
        ];
    }

    private function upsertBy(string $table, string $uniqueField, array $row): void
    {
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
            return;
        }

        $this->db->table($table)->insert($row);
    }

    private function idsBy(string $table, string $nameField): array
    {
        return array_map(
            'intval',
            array_column(
                $this->db->table($table)->select("id, {$nameField}")->get()->getResultArray(),
                'id',
                $nameField
            )
        );
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

    private function onlyExistingFields(string $table, array $row): array
    {
        return array_filter(
            $row,
            fn (string $field): bool => $this->db->fieldExists($field, $table),
            ARRAY_FILTER_USE_KEY
        );
    }
}
