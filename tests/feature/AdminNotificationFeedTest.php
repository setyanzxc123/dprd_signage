<?php

use App\Libraries\Otp\Providers\BaileysProvider;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Pengujian feed notifikasi terpadu pada Web Admin Topbar.
 *
 * @internal
 */
final class AdminNotificationFeedTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        try {
            cache()->delete(BaileysProvider::OFFLINE_CACHE_KEY);
        } catch (\Throwable) {
        }

        parent::tearDown();
    }

    public function testNotificationFeedRequiresAdminSession(): void
    {
        $response = $this->get('/admin/notifications/feed');
        $response->assertRedirectTo(base_url('login?akses=admin'));
    }

    public function testNotificationFeedReturnsSuccessPayloadForAdmin(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/notifications/feed');

        $response->assertStatus(200);
        $body = (string) $response->getJSON();
        $payload = json_decode($body, true);

        $this->assertIsArray($payload);
        $this->assertSame('success', $payload['status'] ?? null);
        $this->assertArrayHasKey('data', $payload);

        $data = $payload['data'];
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('alerts', $data);
        $this->assertArrayHasKey('ai_tasks', $data);

        $this->assertArrayHasKey('unread_critical_count', $data['summary']);
        $this->assertArrayHasKey('warning_count', $data['summary']);
        $this->assertArrayHasKey('alerts_count', $data['summary']);
        $this->assertArrayHasKey('active_tasks_count', $data['summary']);
        $this->assertArrayHasKey('badge_tone', $data['summary']);
        $this->assertArrayHasKey('badge_count', $data['summary']);
    }

    public function testNotificationFeedGeneratesAlertWhenWhatsAppDisconnected(): void
    {
        cache()->save(BaileysProvider::OFFLINE_CACHE_KEY, [
            'configured' => true,
            'connected'  => false,
            'status'     => 'offline',
            'phone'      => null,
            'name'       => null,
            'qr_url'     => null,
            'error'      => 'Nomor WhatsApp belum terhubung ke sistem.',
        ], 60);

        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/notifications/feed');

        $response->assertStatus(200);
        $body = (string) $response->getJSON();
        $payload = json_decode($body, true);

        $data = $payload['data'];
        $this->assertGreaterThanOrEqual(1, $data['summary']['unread_critical_count']);
        $this->assertSame('danger', $data['summary']['badge_tone']);

        $alertIds = array_column($data['alerts'], 'id');
        $this->assertContains('wa-gateway-disconnected', $alertIds);
    }

    public function testTopbarRendersUnifiedNotificationElements(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('hs-dropdown-notifications');
        $response->assertSee('[--auto-close:inside]');
        $response->assertSee('notification_hub_badge');
        $response->assertSee('notif-tab-alerts');
        $response->assertSee('notif-tab-ai');
        $response->assertSee('modal_wa_pairing');
    }

    /**
     * @return array<string, mixed>
     */
    private function adminSession(): array
    {
        return [
            'id'       => 1,
            'name'     => 'Admin Pengujian',
            'username' => 'admin-test',
            'role'     => 'superadmin',
        ];
    }
}
