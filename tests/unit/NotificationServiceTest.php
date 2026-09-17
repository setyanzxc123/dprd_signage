<?php

namespace Tests\Unit;

use App\Libraries\Notification\NotificationService;
use App\Libraries\Notulen\NotulenService;
use App\Libraries\Otp\Providers\BaileysProvider;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Otp;

/**
 * Pengujian unit logika bisnis NotificationService.
 *
 * @internal
 */
final class NotificationServiceTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        try {
            cache()->delete(BaileysProvider::OFFLINE_CACHE_KEY);
        } catch (\Throwable) {
        }

        parent::tearDown();
    }

    public function testGetFeedReturnsStandardizedStructure(): void
    {
        $service = new NotificationService();
        $feed = $service->getFeed();

        $this->assertIsArray($feed);
        $this->assertArrayHasKey('summary', $feed);
        $this->assertArrayHasKey('alerts', $feed);
        $this->assertArrayHasKey('ai_tasks', $feed);

        $summary = $feed['summary'];
        $this->assertArrayHasKey('unread_critical_count', $summary);
        $this->assertArrayHasKey('warning_count', $summary);
        $this->assertArrayHasKey('alerts_count', $summary);
        $this->assertArrayHasKey('active_tasks_count', $summary);
        $this->assertArrayHasKey('badge_tone', $summary);
        $this->assertArrayHasKey('badge_count', $summary);

        $this->assertContains($summary['badge_tone'], ['none', 'info', 'warning', 'danger']);
    }

    public function testWhatsAppDisconnectedProducesCriticalAlert(): void
    {
        cache()->save(BaileysProvider::OFFLINE_CACHE_KEY, [
            'configured' => true,
            'connected'  => false,
            'status'     => 'offline',
            'phone'      => null,
            'name'       => null,
            'qr_url'     => null,
            'error'      => 'Nomor terputus.',
        ], 60);

        $otpConfig = new Otp();
        $otpConfig->provider = 'baileys';
        $otpConfig->fazpassFallbackEnabled = false;

        $service = new NotificationService(otpConfig: $otpConfig);
        $feed = $service->getFeed();

        $this->assertGreaterThanOrEqual(1, $feed['summary']['unread_critical_count']);
        $this->assertSame('danger', $feed['summary']['badge_tone']);

        $alertCategories = array_column($feed['alerts'], 'category');
        $this->assertContains('whatsapp', $alertCategories);
    }

    public function testActiveAiTasksProduceInfoBadgeToneWhenNoAlerts(): void
    {
        $notulenMock = $this->createMock(NotulenService::class);
        $notulenMock->method('getActiveTasksSummary')->willReturn([
            'active_count' => 2,
            'active'       => [
                ['id' => 101, 'status' => 'transcribing', 'progress_percent' => 45],
            ],
            'recent'       => [],
        ]);

        $service = new NotificationService(
            notulenService: $notulenMock
        );
        $feed = $service->getFeed();

        $this->assertSame(2, $feed['summary']['active_tasks_count']);
        if ($feed['summary']['unread_critical_count'] === 0 && $feed['summary']['warning_count'] === 0) {
            $this->assertSame('info', $feed['summary']['badge_tone']);
        }
    }
}
