<?php

use App\Models\JadwalUmumModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class JadwalUmumModelTest extends CIUnitTestCase
{
    public function testResolvesLifecycleForAllDaySchedules(): void
    {
        $now = strtotime('2099-08-12 10:00:00');

        $this->assertSame('selesai', JadwalUmumModel::resolveLifecycleStatus('2099-08-11', null, null, $now));
        $this->assertSame('berlangsung', JadwalUmumModel::resolveLifecycleStatus('2099-08-12', null, null, $now));
        $this->assertSame('menunggu', JadwalUmumModel::resolveLifecycleStatus('2099-08-13', null, null, $now));
    }

    public function testResolvesLifecycleForCompleteTimes(): void
    {
        $date = '2099-08-12';

        $this->assertSame(
            'menunggu',
            JadwalUmumModel::resolveLifecycleStatus($date, '11:00:00', '12:00:00', strtotime($date . ' 10:00:00')),
        );
        $this->assertSame(
            'persiapan',
            JadwalUmumModel::resolveLifecycleStatus($date, '10:20:00', '11:00:00', strtotime($date . ' 10:00:00')),
        );
        $this->assertSame(
            'berlangsung',
            JadwalUmumModel::resolveLifecycleStatus($date, '09:00:00', '11:00:00', strtotime($date . ' 10:00:00')),
        );
        $this->assertSame(
            'selesai',
            JadwalUmumModel::resolveLifecycleStatus($date, '08:00:00', '09:00:00', strtotime($date . ' 10:00:00')),
        );
    }

    public function testStartOnlyScheduleRunsUntilEndOfDate(): void
    {
        $date = '2099-08-12';

        $this->assertSame(
            'persiapan',
            JadwalUmumModel::resolveLifecycleStatus($date, '10:20:00', null, strtotime($date . ' 10:00:00')),
        );
        $this->assertSame(
            'berlangsung',
            JadwalUmumModel::resolveLifecycleStatus($date, '09:00:00', null, strtotime($date . ' 10:00:00')),
        );
        $this->assertSame(
            'selesai',
            JadwalUmumModel::resolveLifecycleStatus('2099-08-11', '09:00:00', null, strtotime($date . ' 10:00:00')),
        );
    }
}
