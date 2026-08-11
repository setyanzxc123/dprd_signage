<?php

use App\Libraries\Otp\OtpStatus;
use CodeIgniter\Test\CIUnitTestCase;

final class OtpStatusTest extends CIUnitTestCase
{
    public function testActiveAndTerminalStatusesDoNotOverlap(): void
    {
        $this->assertSame([], array_values(array_intersect(OtpStatus::ACTIVE, OtpStatus::TERMINAL)));
    }

    public function testExpectedLifecycleTransitionsAreAllowed(): void
    {
        $this->assertTrue(OtpStatus::canTransition(OtpStatus::CREATED, OtpStatus::PENDING));
        $this->assertTrue(OtpStatus::canTransition(OtpStatus::PENDING, OtpStatus::DELIVERED));
        $this->assertTrue(OtpStatus::canTransition(OtpStatus::DELIVERED, OtpStatus::VERIFIED));
        $this->assertTrue(OtpStatus::canTransition(OtpStatus::MANUAL, OtpStatus::VERIFIED));
    }

    public function testTerminalStatusesCannotReturnToActiveLifecycle(): void
    {
        foreach (OtpStatus::TERMINAL as $terminal) {
            foreach (OtpStatus::ACTIVE as $active) {
                $this->assertFalse(
                    OtpStatus::canTransition($terminal, $active),
                    "Transisi {$terminal} -> {$active} harus ditolak.",
                );
            }
        }
    }

    public function testSourcesForIncludesIdempotentStateButExcludesTerminalRegression(): void
    {
        $sources = OtpStatus::sourcesFor(OtpStatus::DELIVERED);

        $this->assertContains(OtpStatus::PENDING, $sources);
        $this->assertContains(OtpStatus::SENT, $sources);
        $this->assertContains(OtpStatus::DELIVERED, $sources);
        $this->assertNotContains(OtpStatus::VERIFIED, $sources);
        $this->assertNotContains(OtpStatus::FAILED, $sources);
    }
}
