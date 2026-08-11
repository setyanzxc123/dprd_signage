<?php

use App\Database\Seeds\CurrentSystemDataSeeder;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CurrentSystemDataSeederTest extends CIUnitTestCase
{
    private CurrentSystemDataSeeder $subject;
    private \ReflectionClass $reflection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflection = new \ReflectionClass(CurrentSystemDataSeeder::class);
        $this->subject = $this->reflection->newInstanceWithoutConstructor();
    }

    public function testOfficialDocumentsKeepAllSourceItemsAsPublicProjections(): void
    {
        $firstDocumentItems = $this->invokeArrayMethod('realSkOneItems');
        $secondDocumentItems = $this->invokeArrayMethod('realSkNineItems');
        $items = array_merge($firstDocumentItems, $secondDocumentItems);

        $this->assertCount(29, $firstDocumentItems);
        $this->assertCount(26, $secondDocumentItems);
        $this->assertSame(['non_rapat', 'rapat'], $this->uniqueValues($items, 'jenis_agenda'));

        foreach ($items as $item) {
            $this->assertSame('proyeksi', $item['status']);
            $this->assertSame('publik', $item['publikasi']);
            $this->assertNull($item['tanggal']);
            $this->assertNull($item['jam_mulai']);
            $this->assertNull($item['jam_selesai']);
            $this->assertFalse(str_starts_with($item['agenda'], '(Dummy) '));
        }
    }

    public function testDummyBanmusDataCoversComplexVisibilityAndSchedulingConditions(): void
    {
        $items = array_merge(
            $this->invokeArrayMethod('dummySk2027FirstSemesterItems'),
            $this->invokeArrayMethod('dummySk2027SecondSemesterItems'),
        );

        $this->assertCount(38, $items);
        $this->assertSame(['non_rapat', 'rapat'], $this->uniqueValues($items, 'jenis_agenda'));
        $this->assertSame(['internal', 'publik'], $this->uniqueValues($items, 'publikasi'));
        $this->assertContains('proyeksi', array_column($items, 'status'));
        $this->assertContains('menunggu', array_column($items, 'status'));
        $this->assertSame(
            ['anggota', 'peserta', 'publik'],
            $this->uniqueValues($items, 'materi_akses'),
        );

        $hasRoom = false;
        $hasOtherLocation = false;
        $hasMultipleUnits = false;
        $hasMaterial = false;
        $hasStream = false;

        foreach ($items as $item) {
            $this->assertStringStartsWith('(Dummy) ', $item['agenda']);
            $hasRoom = $hasRoom || $item['room'] !== null;
            $hasOtherLocation = $hasOtherLocation || $item['lokasi_lainnya'] !== null;
            $hasMultipleUnits = $hasMultipleUnits || count($item['units']) > 1;
            $hasMaterial = $hasMaterial || $item['materi_url'] !== null;
            $hasStream = $hasStream || $item['stream_url'] !== null;
        }

        $this->assertTrue($hasRoom);
        $this->assertTrue($hasOtherLocation);
        $this->assertTrue($hasMultipleUnits);
        $this->assertTrue($hasMaterial);
        $this->assertTrue($hasStream);
    }

    public function testSeederDoesNotDependOnLocalPdfSources(): void
    {
        $source = file_get_contents(
            ROOTPATH . 'app/Database/Seeds/CurrentSystemDataSeeder.php'
        );

        $this->assertIsString($source);
        $this->assertStringNotContainsString('storeSourcePdf', $source);
        $this->assertStringNotContainsString('clearStoredBanmusPdfs', $source);
        $this->assertStringNotContainsString("ROOTPATH . 'tes'", $source);
        $this->assertGreaterThanOrEqual(4, substr_count($source, "'dokumen_file' => null"));
        $this->assertGreaterThanOrEqual(4, substr_count($source, "'dokumen_nama_asli' => null"));
    }

    public function testGeneralScheduleUsesTargetShape(): void
    {
        $method = $this->reflection->getMethod('generalSchedule');
        $schedule = $method->invoke(
            $this->subject,
            'Rapat Insidental Pengujian',
            new DateTimeImmutable('2027-08-20 09:00:00'),
            new DateTimeImmutable('2027-08-20 11:00:00'),
            'Ruang Rapat Utama',
            null,
            ['Pimpinan DPRD'],
            'publik',
            'https://example.com/dummy/materi/rapat-insidental',
            'peserta',
            'https://example.com/dummy/live/rapat-insidental',
            'publik',
        );

        $this->assertArrayNotHasKey('jenis', $schedule);
        $this->assertArrayNotHasKey('sumber_agenda', $schedule);
        $this->assertNull($schedule['pihak_eksternal']);
        $this->assertSame(1, $schedule['is_publik']);
        $this->assertSame('https://example.com/dummy/materi/rapat-insidental', $schedule['materi_url']);
        $this->assertSame('peserta', $schedule['materi_akses']);
        $this->assertSame('https://example.com/dummy/live/rapat-insidental', $schedule['stream_url']);
        $this->assertSame('publik', $schedule['stream_akses']);
        $this->assertStringStartsWith('(Dummy) ', $schedule['judul']);
    }

    public function testDummyResourceLinksUseExampleDotComOnly(): void
    {
        $source = file_get_contents(
            ROOTPATH . 'app/Database/Seeds/CurrentSystemDataSeeder.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString('https://example.com/dummy/materi/', $source);
        $this->assertStringContainsString('https://example.com/dummy/live/', $source);
        $this->assertStringNotContainsString('https://example.test/', $source);
    }

    public function testSeederWritesOnlyTheTargetGeneralScheduleTables(): void
    {
        $source = file_get_contents(
            ROOTPATH . 'app/Database/Seeds/CurrentSystemDataSeeder.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString("table('jadwal_umum')", $source);
        $this->assertStringContainsString("insertPivotIfMissing('jadwal_umum_unit_rapat'", $source);
        $this->assertStringNotContainsString("table('jadwal')", $source);
        $this->assertStringNotContainsString("table('agenda_umum')", $source);
        $this->assertStringNotContainsString("insertPivotIfMissing('jadwal_unit_rapat'", $source);
        $this->assertStringContainsString('Kegiatan Orientasi Anggota Sepanjang Hari', $source);
        $this->assertStringContainsString("['Pimpinan DPRD', 'Komisi I']", $source);
    }

    public function testSeederDoesNotWriteAdminUsers(): void
    {
        $resettableTables = $this->invokeArrayMethod('resettableTables');
        $requiredTables = $this->invokeArrayMethod('requiredTables');
        $requiredFields = $this->invokeArrayMethod('requiredFields');
        $source = file_get_contents(
            ROOTPATH . 'app/Database/Seeds/CurrentSystemDataSeeder.php'
        );

        $this->assertNotContains('users', $resettableTables);
        $this->assertNotContains('migrations', $resettableTables);
        $this->assertContains('member_otps', $resettableTables);
        $this->assertContains('jadwal_umum', $resettableTables);
        $this->assertContains('jadwal_umum_unit_rapat', $resettableTables);
        $this->assertNotContains('member_accounts', $resettableTables);
        $this->assertNotContains('notifikasi', $resettableTables);
        $this->assertNotContains('agenda_audit_log', $resettableTables);
        $this->assertNotContains('agenda_migration_state', $resettableTables);
        $this->assertNotContains('jadwal', $resettableTables);
        $this->assertNotContains('jadwal_unit_rapat', $resettableTables);
        $this->assertNotContains('agenda_umum', $resettableTables);
        $this->assertContains('users', $requiredTables);
        $this->assertContains('member_otps', $requiredTables);
        $this->assertContains('last_login_at', $requiredFields['anggota']);
        $this->assertContains('provider_transaction_id', $requiredFields['member_otps']);
        $this->assertContains('created_by_admin_id', $requiredFields['member_otps']);
        $this->assertIsString($source);
        $this->assertStringNotContainsString("table('users')", $source);
        $this->assertStringNotContainsString('insertBatch($users', $source);
    }

    public function testSampleLoginMemberCoversManyRelevantAgendaUnits(): void
    {
        $members = $this->invokeArrayMethod('members');
        $sampleMembers = array_values(array_filter(
            $members,
            static fn (array $member): bool => $member['no_wa'] === '85156049890',
        ));
        $unitNames = $this->invokeArrayMethod('sampleMemberUnitNames');

        $this->assertCount(1, $sampleMembers);
        $this->assertSame('(Dummy) Anggota Uji Agenda', $sampleMembers[0]['name']);
        $this->assertSame('Komisi I', $sampleMembers[0]['komisi']);
        $this->assertSame(1, $sampleMembers[0]['aktif']);
        $this->assertGreaterThanOrEqual(10, count($unitNames));
        $this->assertContains('Seluruh Anggota', $unitNames);
        $this->assertContains('Komisi I', $unitNames);
        $this->assertContains('Badan Musyawarah', $unitNames);
        $this->assertContains('Badan Anggaran', $unitNames);
        $this->assertContains('Gabungan Komisi', $unitNames);
        $this->assertContains('Pimpinan DPRD', $unitNames);
        $this->assertContains('Pansus Ranperda Pajak Daerah', $unitNames);
        $this->assertContains('Tim Pembahas RAPBD', $unitNames);
        $this->assertSame($unitNames, array_values(array_unique($unitNames)));
    }

    public function testCurrentMonthDummyBanmusDocumentIsImmediatelyUsefulForMemberTesting(): void
    {
        $now = new DateTimeImmutable();
        $method = $this->reflection->getMethod('dummyCurrentMonthBanmusDocument');
        $dataset = $method->invoke($this->subject, $now);
        $document = $dataset['document'];
        $items = $dataset['items'];

        $this->assertSame('(Dummy) SK Jadwal Rapat Banmus Uji Bulan Berjalan', $document['judul']);
        $this->assertSame('DUMMY/UJI-AGENDA/' . $now->format('Y-m'), $document['nomor_sk']);
        $this->assertSame((int) $now->format('Y'), $document['tahun']);
        $this->assertSame(1, $document['is_publik']);
        $this->assertCount(6, $items);

        foreach ($items as $item) {
            $this->assertSame('rapat', $item['jenis_agenda']);
            $this->assertNotSame('proyeksi', $item['status']);
            $this->assertNotEmpty($item['tanggal']);
            $this->assertNotEmpty($item['units']);
            $this->assertStringStartsWith('(Dummy) ', $item['agenda']);
        }

        $this->assertGreaterThanOrEqual(5, count(array_filter(array_column($items, 'materi_url'))));
        $this->assertGreaterThanOrEqual(5, count(array_filter(array_column($items, 'stream_url'))));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function invokeArrayMethod(string $methodName): array
    {
        $method = $this->reflection->getMethod($methodName);

        return $method->invoke($this->subject);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<string>
     */
    private function uniqueValues(array $rows, string $field): array
    {
        $values = array_values(array_unique(array_column($rows, $field)));
        sort($values);

        return $values;
    }
}
