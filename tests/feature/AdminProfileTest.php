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

    private BaseConnection $passwordDb;
    private Forge $passwordForge;
    private string $currentPassword = 'Password-Lama-123';

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian profil admin.');
        }

        $this->passwordDb = Database::connect('tests');
        $this->passwordForge = Database::forge('tests');
        $this->passwordForge->dropTable('users', true);
        $this->createUsersTable();
        $this->passwordDb->table('users')->insert([
            'name'     => 'Admin Pengujian',
            'username' => 'admin-test',
            'email'    => 'admin@example.com',
            'password' => password_hash($this->currentPassword, PASSWORD_DEFAULT),
            'role'     => 'superadmin',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->passwordForge)) {
            $this->passwordForge->dropTable('users', true);
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
        $user = $this->passwordDb->table('users')->where('id', 1)->get()->getRowArray();
        $this->assertSame('Nama Admin Baru', $user['name']);
        $this->assertTrue(password_verify($this->currentPassword, (string) $user['password']));
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
        return (string) $this->passwordDb->table('users')
            ->where('id', 1)
            ->get()
            ->getRow('password');
    }

    private function createUsersTable(): void
    {
        $this->passwordForge->addField([
            'id'       => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'username' => ['type' => 'VARCHAR', 'constraint' => 50],
            'email'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'password' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role'     => ['type' => 'VARCHAR', 'constraint' => 20],
        ]);
        $this->passwordForge->addPrimaryKey('id');
        $this->passwordForge->createTable('users');
    }
}
