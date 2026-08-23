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

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian OTP darurat.');
        }

        // Wajib memakai koneksi shared: database tests adalah :memory:
        // sehingga setiap koneksi baru berarti database kosong yang
        // terpisah dan tidak terlihat oleh model aplikasi.
        $this->otpDb = Database::connect('tests');
        $this->forge = Database::forge('tests');
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

        // Alur test ini menuntaskan login anggota sampai session
        // regenerate; sisa state dibersihkan lewat $_SESSION per request
        // oleh FeatureTestTrait, tidak perlu pembersihan tambahan.
        $_SESSION = [];

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
        // Schema identitas Shield (lihat AdminProfileTest) menggantikan
        // tabel users lama; verifikasi OTP kini menyiapkan user Shield
        // untuk anggota saat login pertama.
        $this->forge->addField([
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
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('users');

        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'    => ['type' => 'INTEGER'],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('auth_groups_users');

        $this->forge->addField([
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
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('auth_identities');

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'jabatan'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fraksi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'komisi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_wa'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'foto'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'user_id'       => ['type' => 'INT', 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('anggota');
    }

    private function insertIdentities(): void
    {
        $this->otpDb->table('users')->insert([
            'username' => 'admin-test',
            'name'     => 'Admin Test',
            'active'   => 1,
        ]);
        $this->adminId = (int) $this->otpDb->insertID();
        $this->otpDb->table('auth_groups_users')->insert([
            'user_id' => $this->adminId,
            'group'   => 'superadmin',
        ]);

        $this->otpDb->table('anggota')->insert([
            'name'  => 'Anggota OTP Darurat',
            'no_wa' => $this->phone,
            'aktif' => 1,
        ]);
        $this->anggotaId = (int) $this->otpDb->insertID();
    }

    private function dropTables(): void
    {
        foreach (['member_otps', 'auth_identities', 'auth_groups_users', 'anggota', 'users'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
