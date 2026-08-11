<?php

use App\Database\Migrations\RefactorAndSimplifyDatabase;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Config as Database;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

require_once APPPATH . 'Database/Migrations/2026-08-11-000001_RefactorAndSimplifyDatabase.php';

final class EmergencyOtpLoginFeatureTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $otpDb;
    private Forge $forge;
    private int $adminId;
    private int $anggotaId;
    private string $phone = '81234567890';

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->otpDb = Database::connect('tests', false);
            $this->otpDb->initialize();
        } catch (Throwable $exception) {
            $this->markTestSkipped('Database test tidak tersedia: ' . $exception->getMessage());
        }

        $this->forge = Database::forge($this->otpDb);
        $this->dropTables();
        $this->createParentTables();
        (new RefactorAndSimplifyDatabase($this->forge))->up();
        $this->insertIdentities();
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->dropTables();
        }
        if (isset($this->otpDb)) {
            $this->otpDb->close();
        }

        parent::tearDown();
    }

    public function testAdminCreatesEmergencyOtpAndMemberLogsInWithIt(): void
    {
        $created = $this->withSession([
            'auth_user' => [
                'id' => $this->adminId,
                'name' => 'Admin Test',
                'role' => 'superadmin',
            ],
        ])->post('/admin/anggota/' . $this->anggotaId . '/otp-darurat', [
            csrf_token() => csrf_hash(),
        ]);

        $created->assertOK();
        $body = $created->response()->getBody();
        $this->assertSame(1, preg_match('/tracking-widest">(\d{6})<\/div>/', $body, $matches));
        $code = $matches[1];

        $otp = $this->otpDb->table('member_otps')->where('anggota_id', $this->anggotaId)->get()->getRowArray();
        $this->assertNotNull($otp);
        $this->assertSame('emergency', $otp['provider']);
        $this->assertSame('manual', $otp['status']);
        $this->assertSame($this->adminId, (int) $otp['created_by_admin_id']);

        $now = time();
        $verified = $this->withSession([
            'member_otp_pending' => [
                'anggota_id' => $this->anggotaId,
                'phone_hash' => hash('sha256', '62' . $this->phone),
                'masked' => '+62 812••••890',
                'retry_at' => $now,
                'otp_expires_at' => strtotime((string) $otp['expires_at']),
                'expires_at' => $now + 900,
            ],
        ])->post('/login/anggota/verifikasi', [
            csrf_token() => csrf_hash(),
            'otp' => $code,
        ]);

        $verified->assertStatus(303);
        $verified->assertRedirectTo(base_url('agenda'));

        $updatedOtp = $this->otpDb->table('member_otps')->where('id', $otp['id'])->get()->getRowArray();
        $member = $this->otpDb->table('anggota')->where('id', $this->anggotaId)->get()->getRowArray();
        $this->assertSame('verified', $updatedOtp['status']);
        $this->assertNotNull($updatedOtp['used_at']);
        $this->assertNotNull($member['last_login_at']);
    }

    private function createParentTables(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'username' => ['type' => 'VARCHAR', 'constraint' => 50],
            'email' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'password' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role' => ['type' => 'VARCHAR', 'constraint' => 32],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('users');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'jabatan' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fraksi' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'komisi' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_wa' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'foto' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('anggota');
    }

    private function insertIdentities(): void
    {
        $this->otpDb->table('users')->insert([
            'name' => 'Admin Test',
            'username' => 'admin-test',
            'password' => password_hash('test-password', PASSWORD_DEFAULT),
            'role' => 'superadmin',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->adminId = (int) $this->otpDb->insertID();

        $this->otpDb->table('anggota')->insert([
            'name' => 'Anggota OTP Darurat',
            'no_wa' => $this->phone,
            'aktif' => 1,
        ]);
        $this->anggotaId = (int) $this->otpDb->insertID();
    }

    private function dropTables(): void
    {
        foreach (['member_otps', 'anggota', 'users'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
