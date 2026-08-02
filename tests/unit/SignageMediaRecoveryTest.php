<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SignageMediaRecoveryTest extends CIUnitTestCase
{
    public function testSignageDetectsAndRecoversFromPlaybackStalls(): void
    {
        $source = file_get_contents(APPPATH . 'Views/signage/index.php');

        $this->assertIsString($source);
        $this->assertStringContainsString('@timeupdate="handleMediaProgress"', $source);
        $this->assertStringContainsString('@waiting="handleMediaWaiting"', $source);
        $this->assertStringContainsString('MEDIA_STALL_THRESHOLD_MS', $source);
        $this->assertStringContainsString('Date.now() - lastMediaProgressAt', $source);
        $this->assertStringContainsString('video.load();', $source);
        $this->assertStringContainsString('MEDIA_MAX_RECOVERY_ATTEMPTS', $source);
        $this->assertStringContainsString('navigator.onLine === false', $source);
        $this->assertStringContainsString("addEventListener('offline'", $source);
        $this->assertStringContainsString("addEventListener('online'", $source);
        $this->assertStringContainsString("removeEventListener('visibilitychange'", $source);
    }
}
