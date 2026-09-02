<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AdminWhatsAppStatusTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $settingsDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan.');
        }

        $this->settingsDb = Database::connect('tests');
        $this->forge = Database::forge('tests');
        $this->forge->dropTable('settings', true);
        $this->forge->addField([
            'key_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'value'    => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('key_name');
        $this->forge->createTable('settings');
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->forge->dropTable('settings', true);
        }
        parent::tearDown();
    }

    public function testWhatsAppStatusRequiresAdminSession(): void
    {
        $response = $this->get('/admin/pengaturan/whatsapp/status');
        $response->assertRedirectTo(base_url('login?akses=admin'));
    }

    public function testWhatsAppStatusReturnsSuccessPayloadForAdmin(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/pengaturan/whatsapp/status');

        $response->assertStatus(200);
        $body = (string) $response->getJSON();
        $payload = json_decode($body, true);

        $this->assertIsArray($payload);
        $this->assertSame('success', $payload['status'] ?? null);
        $this->assertArrayHasKey('provider', $payload);
        $this->assertArrayHasKey('fallback', $payload);
        $this->assertArrayHasKey('gateway', $payload);

        $gateway = $payload['gateway'] ?? [];
        $this->assertArrayHasKey('configured', $gateway);
        $this->assertArrayHasKey('connected', $gateway);
        $this->assertArrayHasKey('status', $gateway);
        $this->assertArrayHasKey('qr_url', $gateway);
    }

    public function testSettingsPageRendersWhatsAppIntegrationCard(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/pengaturan');

        $response->assertStatus(200);
        $response->assertSee('Integrasi WhatsApp OTP Gateway');
        $response->assertSee('Status Koneksi Gateway');
        $response->assertSee('btn-refresh-wa-status');
        $response->assertSee('modal_wa_pairing');
        $response->assertSee('modal_wa_logout');
        $response->assertSee('btn-wa-logout');
    }

    public function testWhatsAppLogoutRequiresAdminSession(): void
    {
        $response = $this->post('/admin/pengaturan/whatsapp/logout', [
            csrf_token() => csrf_hash(),
        ]);
        $response->assertRedirectTo(base_url('login?akses=admin'));
    }

    public function testWhatsAppPairCodeRequiresAdminSession(): void
    {
        $response = $this->post('/admin/pengaturan/whatsapp/pair-code', [
            csrf_token() => csrf_hash(),
            'phone'      => '08123456789',
        ]);
        $response->assertRedirectTo(base_url('login?akses=admin'));
    }

    public function testWhatsAppPairCodeValidatesEmptyPhone(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post('/admin/pengaturan/whatsapp/pair-code', [
                csrf_token() => csrf_hash(),
                'phone'      => '',
            ]);

        $response->assertStatus(422);
        $body = (string) $response->getJSON();
        $payload = json_decode($body, true);
        $this->assertSame('error', $payload['status'] ?? null);
        $this->assertStringContainsString('wajib diisi', (string) ($payload['message'] ?? ''));
    }

    /** @return array<string, mixed> */
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
