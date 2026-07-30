<?php

use App\Libraries\Schedule\ScheduleResourceAccess;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ScheduleResourceAccessTest extends CIUnitTestCase
{
    public function testAccessMatrixSeparatesPublicMemberAndParticipant(): void
    {
        $this->assertTrue(ScheduleResourceAccess::canView('publik', false, false));
        $this->assertFalse(ScheduleResourceAccess::canView('anggota', false, false));
        $this->assertTrue(ScheduleResourceAccess::canView('anggota', true, false));
        $this->assertFalse(ScheduleResourceAccess::canView('peserta', true, false));
        $this->assertTrue(ScheduleResourceAccess::canView('peserta', true, true));
    }

    public function testInvalidValuesUseSafeResourceDefaults(): void
    {
        $this->assertSame(
            ScheduleResourceAccess::PARTICIPANT,
            ScheduleResourceAccess::normalize('admin', ScheduleResourceAccess::PARTICIPANT),
        );
        $this->assertSame(
            ScheduleResourceAccess::MEMBER,
            ScheduleResourceAccess::normalize('', ScheduleResourceAccess::MEMBER),
        );
    }
}
