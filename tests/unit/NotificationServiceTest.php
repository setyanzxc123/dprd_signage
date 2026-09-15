<?php

namespace Tests\Unit;

use App\Libraries\Notification\NotificationService;
use App\Libraries\Notulen\NotulenService;
use App\Libraries\Otp\Providers\BaileysProvider;
use App\Libraries\Schedule\AgendaWorkspaceService;
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

    public function testScheduleConflictProducesCriticalAlert(): void
    {
        $workspaceMock = $this->createMock(AgendaWorkspaceService::class);
        $workspaceMock->method('loadMonth')->willReturn([
            'agendas' => [
                [
                    'key'          => 'umum:1',
                    'judul'        => 'Rapat Komisi I',
                    'tanggal'      => date('Y-m-d'),
                    'waktu_mulai'  => '09:00',
                    'waktu_selesai'=> '11:00',
                    'lokasi'       => 'Ruang Baruga',
                    'location_key' => 'ruang baruga',
                    'has_conflict' => true,
                    'conflicts'    => [['key' => 'banmus:2', 'label' => 'Rapat Badan Anggaran']],
                ],
                [
                    'key'          => 'banmus:2',
                    'judul'        => 'Rapat Badan Anggaran',
                    'tanggal'      => date('Y-m-d'),
                    'waktu_mulai'  => '10:00',
                    'waktu_selesai'=> '12:00',
                    'lokasi'       => 'Ruang Baruga',
                    'location_key' => 'ruang baruga',
                    'has_conflict' => true,
                    'conflicts'    => [['key' => 'umum:1', 'label' => 'Rapat Komisi I']],
                ],
            ],
            'counts'  => ['total' => 2, 'conflicts' => 2],
            'options' => [],
        ]);

        $service = new NotificationService(agendaWorkspaceService: $workspaceMock);
        $feed = $service->getFeed();

        $categories = array_column($feed['alerts'], 'category');
        $this->assertContains('schedule_conflict', $categories);

        $severities = array_column($feed['alerts'], 'severity');
        $this->assertContains('critical', $severities);
        $this->assertSame('danger', $feed['summary']['badge_tone']);
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

        $workspaceMock = $this->createMock(AgendaWorkspaceService::class);
        $workspaceMock->method('loadMonth')->willReturn(['agendas' => [], 'counts' => ['conflicts' => 0]]);

        $service = new NotificationService(
            notulenService: $notulenMock,
            agendaWorkspaceService: $workspaceMock
        );
        $feed = $service->getFeed();

        $this->assertSame(2, $feed['summary']['active_tasks_count']);
        if ($feed['summary']['unread_critical_count'] === 0 && $feed['summary']['warning_count'] === 0) {
            $this->assertSame('info', $feed['summary']['badge_tone']);
        }
    }
}
