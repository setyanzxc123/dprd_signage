<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class AdminProfileTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $profileDb;
    private Forge $profileForge;
    private string $currentPassword = 'Password-Lama-123';

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian profil admin.');
        }

        $this->profileDb = Database::connect('tests');
        $this->profileForge = Database::forge('tests');
        $this->profileForge->dropTable('auth_identities', true);
        $this->profileForge->dropTable('users', true);
        $this->createShieldUserTables();
        $this->profileDb->table('users')->insert([
            'username' => 'admin-test',
            'name'     => 'Admin Pengujian',
            'active'   => 1,
        ]);
        $this->profileDb->table('auth_identities')->insert([
            'user_id' => 1,
            'type'    => 'email_password',
            'secret'  => 'admin@example.com',
            'secret2' => password_hash($this->currentPassword, PASSWORD_DEFAULT),
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->profileForge->dropTable('auth_identities', true);
            $this->profileForge->dropTable('users', true);
        }

        parent::tearDown();
    }

    public function testProfilePageRequiresAuthentication(): void
    {
        $response = $this->get('/admin/profile');

        $response->assertRedirectTo(base_url('login?akses=admin'));
    }

    public function testAuthenticatedAdminCanOpenProfilePage(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->get('/admin/profile');

        $response->assertOK();
        $body = $response->response()->getBody();
        $this->assertStringContainsString('Profil Admin', $body);
        $this->assertStringContainsString('name="name"', $body);
        $this->assertStringContainsString('name="current_password"', $body);
        $this->assertStringContainsString('name="new_password"', $body);
        $this->assertStringContainsString('name="new_password_confirmation"', $body);
        $this->assertStringContainsString('name="csrf_test_name"', $body);
    }

    public function testCurrentPasswordMustBeCorrect(): void
    {
        $response = $this->submitPasswordChange('password-salah', 'Password-Baru-456', 'Password-Baru-456');

        $response->assertStatus(422);
        $this->assertStringContainsString('Password saat ini tidak sesuai.', $response->response()->getBody());
        $this->assertTrue(password_verify($this->currentPassword, $this->storedPassword()));
    }

    public function testPasswordConfirmationMustMatch(): void
    {
        $response = $this->submitPasswordChange(
            $this->currentPassword,
            'Password-Baru-456',
            'Password-Lain-789',
        );

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'Konfirmasi password baru tidak sesuai.',
            $response->response()->getBody(),
        );
        $this->assertTrue(password_verify($this->currentPassword, $this->storedPassword()));
    }

    public function testAuthenticatedAdminCanChangePassword(): void
    {
        $newPassword = 'Password-Baru-456';
        $response = $this->submitPasswordChange(
            $this->currentPassword,
            $newPassword,
            $newPassword,
        );

        $response->assertStatus(303);
        $response->assertRedirectTo(base_url('admin/profile'));
        $this->assertTrue(password_verify($newPassword, $this->storedPassword()));
        $this->assertFalse(password_verify($this->currentPassword, $this->storedPassword()));
    }

    public function testAuthenticatedAdminCanChangeNameWithoutChangingPassword(): void
    {
        $response = $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post('/admin/profile/update', [
                csrf_token() => csrf_hash(),
                'name'       => 'Nama Admin Baru',
            ]);

        $response->assertStatus(303);
        $response->assertRedirectTo(base_url('admin/profile'));
        $user = $this->profileDb->table('users')->where('id', 1)->get()->getRowArray();
        $this->assertSame('Nama Admin Baru', $user['name']);
        $this->assertTrue(password_verify($this->currentPassword, $this->storedPassword()));
    }

    private function submitPasswordChange(
        string $currentPassword,
        string $newPassword,
        string $confirmation,
    ) {
        return $this
            ->withSession(['auth_user' => $this->adminSession()])
            ->post('/admin/profile/update', [
                csrf_token()                 => csrf_hash(),
                'name'                       => 'Admin Pengujian',
                'current_password'           => $currentPassword,
                'new_password'               => $newPassword,
                'new_password_confirmation'  => $confirmation,
            ]);
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

    private function storedPassword(): string
    {
        $identity = $this->profileDb->table('auth_identities')
            ->select('secret2')
            ->where('user_id', 1)
            ->where('type', 'email_password')
            ->get()
            ->getRow();

        return (string) ($identity->secret2 ?? '');
    }

    /**
     * Schema identitas Shield: users + auth_identities seperti yang
     * dibuat migration Shield ditambah kolom `name` aplikasi.
     */
    private function createShieldUserTables(): void
    {
        $this->profileForge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'username'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status_message' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'active'         => ['type' => 'INTEGER', 'default' => 0],
            'last_active'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->profileForge->addPrimaryKey('id');
        $this->profileForge->createTable('users');

        $this->profileForge->addField([
            'id'           => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'      => ['type' => 'INTEGER'],
            'type'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'secret'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'secret2'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'expires'      => ['type' => 'DATETIME', 'null' => true],
            'extra'        => ['type' => 'TEXT', 'null' => true],
            'force_reset'  => ['type' => 'INTEGER', 'default' => 0],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->profileForge->addPrimaryKey('id');
        $this->profileForge->createTable('auth_identities');
    }
}
