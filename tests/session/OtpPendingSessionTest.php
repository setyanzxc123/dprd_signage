<?php

use App\Libraries\Otp\OtpPendingSession;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Otp;

/**
 * @internal
 */
final class OtpPendingSessionTest extends CIUnitTestCase
{
    private int $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = strtotime('2026-07-27 08:00:00');
        service('session')->remove('member_otp_pending');
    }

    protected function tearDown(): void
    {
        service('session')->remove('member_otp_pending');
        parent::tearDown();
    }

    public function testSeparatesChallengeAndOtpExpiryWithoutStoringRawPhone(): void
    {
        $pendingSession = $this->pendingSession();
        $pending = $pendingSession->begin(
            10,
            20,
            hash('sha256', '628123456789'),
            '+62 812••••789',
            60,
            $this->now + 300,
        );

        $this->assertArrayNotHasKey('phone', $pending);
        $this->assertSame(60, $pendingSession->retryAfter($pending));
        $this->assertSame(300, $pendingSession->otpExpiresAfter($pending));
        $this->assertSame($this->now + 900, $pending['expires_at']);
    }

    public function testExpiredChallengeIsRemovedFromSession(): void
    {
        $pendingSession = $this->pendingSession();
        $pendingSession->begin(10, 20, 'hash', '+62 812••••789', 60, $this->now + 300);
        $this->now += 901;

        $this->assertNull($pendingSession->get());
        $this->assertNull(service('session')->get('member_otp_pending'));
    }

    public function testRefreshPreservesFullServerRetryWindow(): void
    {
        $pendingSession = $this->pendingSession();
        $pending = $pendingSession->begin(10, 20, 'hash', '+62 812••••789', 60, $this->now + 300);
        $pending = $pendingSession->refresh($pending, 3600, $this->now + 300);

        $this->assertSame(3600, $pendingSession->retryAfter($pending));
        $this->assertSame($this->now + 3660, $pending['expires_at']);
    }

    private function pendingSession(): OtpPendingSession
    {
        $config = new Otp();
        $config->challengeTtlSeconds = 900;

        return new OtpPendingSession(
            service('session'),
            $config,
            fn (): int => $this->now,
        );
    }
}
