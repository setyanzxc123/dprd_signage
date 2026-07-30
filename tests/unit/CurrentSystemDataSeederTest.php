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

    public function testRequiredOfficialPdfSourcesExist(): void
    {
        foreach (['REAL_SK_ONE_PDF', 'REAL_SK_NINE_PDF'] as $constantName) {
            $fileName = $this->reflection->getReflectionConstant($constantName)?->getValue();

            $this->assertIsString($fileName);
            $this->assertFileExists(ROOTPATH . 'tes' . DIRECTORY_SEPARATOR . $fileName);
        }
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
        );

        $this->assertArrayNotHasKey('jenis', $schedule);
        $this->assertArrayNotHasKey('sumber_agenda', $schedule);
        $this->assertNull($schedule['pihak_eksternal']);
        $this->assertSame(1, $schedule['is_publik']);
        $this->assertStringStartsWith('(Dummy) ', $schedule['judul']);
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
        $source = file_get_contents(
            ROOTPATH . 'app/Database/Seeds/CurrentSystemDataSeeder.php'
        );

        $this->assertNotContains('users', $resettableTables);
        $this->assertNotContains('migrations', $resettableTables);
        $this->assertContains('member_accounts', $resettableTables);
        $this->assertContains('agenda_migration_state', $resettableTables);
        $this->assertContains('jadwal_umum', $resettableTables);
        $this->assertContains('jadwal_umum_unit_rapat', $resettableTables);
        $this->assertNotContains('jadwal', $resettableTables);
        $this->assertNotContains('jadwal_unit_rapat', $resettableTables);
        $this->assertNotContains('agenda_umum', $resettableTables);
        $this->assertIsString($source);
        $this->assertStringNotContainsString("table('users')", $source);
        $this->assertStringNotContainsString('insertBatch($users', $source);
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
