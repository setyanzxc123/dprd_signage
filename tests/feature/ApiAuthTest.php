<?php

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Throttle\Throttler;
use Config\Database;
use Config\Services;

/**
 * Pengujian endpoint auth API mobile (Fase 6): login admin (username,
 * password, throttle), permintaan OTP anggota (respons generik
 * anti-enumeration), verifikasi OTP (pembuatan user anggota lazy +
 * penerbitan token), logout (pencabutan token), me, dan matriks
 * otorisasi per grup lewat token yang sama.
 *
 * @internal
 */
final class ApiAuthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const ADMIN_PASSWORD = 'password-lama';
    private const OPERATOR_PASSWORD = 'password-operator';
    private const ANGGOTA_PASSWORD = 'password-anggota';
    private const ANGGOTA_TOKEN = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    private BaseConnection $apiDb;
    private Forge $apiForge;
    private int $linkedAnggotaId;
    private int $unlinkedAnggotaId;

    /** Nilai asli variabel lingkungan Fazpass untuk dipulihkan di tearDown. */
    private array $fazpassEnvBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian auth API.');
        }

        // Throttler default menyimpan bucket di cache file — ganti dengan
        // throttler bermemori per test agar state login/OTP tidak bocor
        // antar test maupun ke lingkungan development.
        $this->resetThrottler();

        $this->apiDb = Database::connect('tests');
        $this->apiForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedIdentities();
    }

    protected function tearDown(): void
    {
        if (isset($this->apiForge)) {
            $this->dropTables();
        }

        $this->restoreFazpassEnv();
        $this->resetThrottler();
        $_SESSION = [];

        parent::tearDown();
    }

    private function resetThrottler(): void
    {
        Services::injectMock('throttler', new Throttler(new ApiAuthInMemoryCache()));
    }

    public function testAdminLoginIssuesBearerToken(): void
    {
        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin-api',
            'password' => self::ADMIN_PASSWORD,
            'device'   => 'Unit Test Device',
        ]);

        $response->assertOK();
        $body = $this->decode($response);
        $this->assertSame('success', $body['status']);
        $this->assertSame('Login berhasil.', $body['message']);
        $this->assertSame('Bearer', $body['token_type']);
        $this->assertNotSame('', $body['access_token']);
        $this->assertSame('admin-api', $body['user']['username']);
        $this->assertContains('superadmin', $body['user']['groups']);

        $token = $this->apiDb->table('auth_identities')
            ->where('user_id', 1)
            ->where('type', 'access_token')
            ->get()
            ->getRowArray();
        $this->assertNotNull($token);
        $this->assertSame(hash('sha256', $body['access_token']), $token['secret']);
        $this->assertSame('Unit Test Device', $token['name']);
        $this->assertSame(serialize(['*']), $token['extra']);

        // Login juga menerima body JSON dari klien mobile.
        $json = $this
            ->withBodyFormat('json')
            ->post('/api/v1/auth/login', [
                'username' => 'admin-api',
                'password' => self::ADMIN_PASSWORD,
            ]);
        $json->assertOK();
        $this->assertSame('Bearer', $this->decode($json)['token_type']);
    }

    public function testAdminLoginRejectsInvalidCredentials(): void
    {
        $message = 'Username atau password tidak sesuai.';

        $wrongPassword = $this->post('/api/v1/auth/login', [
            'username' => 'admin-api',
            'password' => 'salah-total',
        ]);
        $wrongPassword->assertStatus(401);
        $this->assertSame($message, $this->decode($wrongPassword)['message']);

        $unknownUser = $this->post('/api/v1/auth/login', [
            'username' => 'hantu-api',
            'password' => 'apa-saja',
        ]);
        $unknownUser->assertStatus(401);
        $this->assertSame($message, $this->decode($unknownUser)['message']);

        $emptyBody = $this->post('/api/v1/auth/login', []);
        $emptyBody->assertStatus(401);
        $this->assertSame($message, $this->decode($emptyBody)['message']);

        // Grup anggota tidak boleh login lewat jalur password admin.
        $anggotaCredentials = $this->post('/api/v1/auth/login', [
            'username' => 'anggota-api',
            'password' => self::ANGGOTA_PASSWORD,
        ]);
        $anggotaCredentials->assertStatus(401);
        $this->assertSame($message, $this->decode($anggotaCredentials)['message']);
    }

    public function testAdminLoginThrottlesAfterRepeatedFailures(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/api/v1/auth/login', [
                'username' => 'throttle-user',
                'password' => 'salah',
            ])->assertStatus(401);
        }

        $throttled = $this->post('/api/v1/auth/login', [
            'username' => 'throttle-user',
            'password' => 'salah',
        ]);
        $throttled->assertStatus(429);
        $this->assertSame(
            'Terlalu banyak percobaan login. Silakan tunggu beberapa saat.',
            $this->decode($throttled)['message'],
        );
    }

    public function testOtpRequestRespondsGenericallyForAnyNumber(): void
    {
        $this->disableFazpass();

        $eligible = $this->post('/api/v1/auth/otp/request', [
            'no_wa' => '081234567890',
        ]);
        $eligible->assertOK();
        $eligibleBody = $this->decode($eligible);
        $this->assertSame('success', $eligibleBody['status']);
        $this->assertSame(
            'Jika nomor terdaftar dan dapat menerima WhatsApp, kode OTP akan segera dikirim.',
            $eligibleBody['message'],
        );
        $this->assertGreaterThan(0, $eligibleBody['retry_after']);

        // Nomor terdaftar tapi nonaktif, nomor tak terdaftar, dan format
        // tidak valid menerima respons yang persis sama — status
        // pendaftaran nomor tidak boleh bocor (anti-enumeration).
        $inactive = $this->post('/api/v1/auth/otp/request', ['no_wa' => '089999999999']);
        $this->assertSame($eligibleBody, $this->decode($inactive));

        $unknown = $this->post('/api/v1/auth/otp/request', ['no_wa' => '081237890123']);
        $this->assertSame($eligibleBody, $this->decode($unknown));

        $invalid = $this->post('/api/v1/auth/otp/request', ['no_wa' => '12345']);
        $this->assertSame($eligibleBody, $this->decode($invalid));

        // Hanya nomor anggota aktif yang benar-benar menempuh alur OTP.
        $otp = $this->apiDb->table('member_otps')->where('anggota_id', $this->linkedAnggotaId)->get()->getRowArray();
        $this->assertNotNull($otp);
        $this->assertSame('fazpass', $otp['provider']);
        $this->assertSame(1, $this->apiDb->table('member_otps')->countAllResults());
    }

    public function testOtpVerifyIssuesMemberTokenAndCreatesUserLazily(): void
    {
        $this->insertEmergencyOtp($this->unlinkedAnggotaId, '123456');

        $response = $this->post('/api/v1/auth/otp/verify', [
            'no_wa' => '081234567891',
            'otp'   => '123456',
        ]);

        $response->assertOK();
        $body = $this->decode($response);
        $this->assertSame('success', $body['status']);
        $this->assertSame('Bearer', $body['token_type']);
        $this->assertNotSame('', $body['access_token']);
        $this->assertSame('anggota_' . $this->unlinkedAnggotaId, $body['user']['username']);
        $this->assertContains('anggota', $body['user']['groups']);
        $this->assertSame('Anggota Belum Terhubung', $body['user']['name']);
        $this->assertSame('Anggota', $body['user']['anggota']['jabatan']);

        // User Shield dibuat lazy dan ditautkan ke anggota.
        $anggota = $this->apiDb->table('anggota')->where('id', $this->unlinkedAnggotaId)->get()->getRowArray();
        $user = $this->apiDb->table('users')->where('id', (int) $anggota['user_id'])->get()->getRowArray();
        $this->assertNotNull($user);
        $this->assertSame('anggota', $this->apiDb->table('auth_groups_users')->where('user_id', $user['id'])->get()->getRowArray()['group']);
        $this->assertNotNull($anggota['last_login_at']);

        // Token anggota ber-scope terbatas dan tercatat sebagai access token.
        $token = $this->apiDb->table('auth_identities')
            ->where('user_id', $user['id'])
            ->where('type', 'access_token')
            ->get()
            ->getRowArray();
        $this->assertNotNull($token);
        $this->assertSame(hash('sha256', $body['access_token']), $token['secret']);
        $this->assertSame(serialize(['agenda.read', 'resource.read']), $token['extra']);

        // OTP terkonsumsi.
        $otp = $this->apiDb->table('member_otps')->where('anggota_id', $this->unlinkedAnggotaId)->get()->getRowArray();
        $this->assertSame('verified', $otp['status']);
        $this->assertNotNull($otp['used_at']);

        // Token hasil OTP dapat dipakai memanggil me dengan profil anggota.
        $me = $this
            ->withHeaders(['Authorization' => 'Bearer ' . $body['access_token']])
            ->get('/api/v1/auth/me');
        $me->assertOK();
        $meBody = $this->decode($me);
        $this->assertSame('anggota_' . $this->unlinkedAnggotaId, $meBody['user']['username']);
        $this->assertSame('Anggota', $meBody['user']['anggota']['jabatan'] ?? null);
    }

    public function testOtpVerifyRejectsInvalidInput(): void
    {
        $this->insertEmergencyOtp($this->unlinkedAnggotaId, '123456');

        $wrongCode = $this->post('/api/v1/auth/otp/verify', [
            'no_wa' => '081234567891',
            'otp'   => '999999',
        ]);
        $wrongCode->assertStatus(401);
        $this->assertSame(
            'Kode OTP tidak valid atau sudah kedaluwarsa.',
            $this->decode($wrongCode)['message'],
        );

        $unknownPhone = $this->post('/api/v1/auth/otp/verify', [
            'no_wa' => '081237890123',
            'otp'   => '123456',
        ]);
        $unknownPhone->assertStatus(401);
        $this->assertSame(
            'Kode OTP tidak valid atau sudah kedaluwarsa.',
            $this->decode($unknownPhone)['message'],
        );

        $inactiveAnggota = $this->post('/api/v1/auth/otp/verify', [
            'no_wa' => '089999999999',
            'otp'   => '123456',
        ]);
        $inactiveAnggota->assertStatus(401);

        // Batas percobaan verifikasi tercapai → token ditolak dengan 429.
        $this->insertEmergencyOtp($this->linkedAnggotaId, '654321', attempts: 5);
        $exhausted = $this->post('/api/v1/auth/otp/verify', [
            'no_wa' => '081234567890',
            'otp'   => '654321',
        ]);
        $exhausted->assertStatus(429);
        $this->assertSame(
            'Terlalu banyak percobaan. Silakan minta kode OTP baru.',
            $this->decode($exhausted)['message'],
        );
    }

    public function testMeRequiresValidToken(): void
    {
        $noToken = $this->get('/api/v1/auth/me');
        $noToken->assertStatus(401);
        $this->assertSame('Token tidak valid atau tidak disertakan.', $this->decode($noToken)['message']);

        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function testLogoutRevokesCurrentToken(): void
    {
        $login = $this->post('/api/v1/auth/login', [
            'username' => 'admin-api',
            'password' => self::ADMIN_PASSWORD,
        ]);
        $token = $this->decode($login)['access_token'];

        $logout = $this
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/api/v1/auth/logout');
        $logout->assertOK();
        $this->assertSame('Logout berhasil.', $this->decode($logout)['message']);

        // Baris token dihapus — token yang sama tidak lagi diterima.
        $remaining = $this->apiDb->table('auth_identities')
            ->where('type', 'access_token')
            ->where('secret', hash('sha256', $token))
            ->countAllResults();
        $this->assertSame(0, $remaining);

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('/api/v1/auth/me')
            ->assertStatus(401);
        $this
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/api/v1/auth/logout')
            ->assertStatus(401);
    }

    public function testAuthorizationMatrixByGroup(): void
    {
        // Tanpa token: seluruh endpoint terlindungi menolak.
        $this->get('/api/v1/admin/profil')->assertStatus(401);

        // Token anggota: diterima me (profil anggota), ditolak endpoint admin.
        $anggotaMe = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ANGGOTA_TOKEN])
            ->get('/api/v1/auth/me');
        $anggotaMe->assertOK();
        $anggotaBody = $this->decode($anggotaMe);
        $this->assertSame('anggota-api', $anggotaBody['user']['username']);
        $this->assertContains('anggota', $anggotaBody['user']['groups']);
        $this->assertSame('Anggota Terhubung', $anggotaBody['user']['name']);
        $this->assertSame('Ketua', $anggotaBody['user']['anggota']['jabatan'] ?? null);

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ANGGOTA_TOKEN])
            ->get('/api/v1/admin/profil')
            ->assertStatus(403);

        // Operator: dapat login password dan lolos filter apiadmin.
        $operatorLogin = $this->post('/api/v1/auth/login', [
            'username' => 'operator-api',
            'password' => self::OPERATOR_PASSWORD,
        ]);
        $operatorLogin->assertOK();
        $operatorToken = $this->decode($operatorLogin)['access_token'];
        $this->assertContains('operator', $this->decode($operatorLogin)['user']['groups']);

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . $operatorToken])
            ->get('/api/v1/auth/me')
            ->assertOK();
        $this
            ->withHeaders(['Authorization' => 'Bearer ' . $operatorToken])
            ->get('/api/v1/admin/profil')
            ->assertOK();

        // Superadmin: diterima di seluruh endpoint.
        $adminLogin = $this->post('/api/v1/auth/login', [
            'username' => 'admin-api',
            'password' => self::ADMIN_PASSWORD,
        ]);
        $adminToken = $this->decode($adminLogin)['access_token'];

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->get('/api/v1/auth/me')
            ->assertOK();
        $this
            ->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->get('/api/v1/admin/profil')
            ->assertOK();
    }

    /** @return array<string, mixed> */
    private function decode(\CodeIgniter\Test\TestResponse $response): array
    {
        $body = json_decode((string) $response->response()->getBody(), true);

        return is_array($body) ? $body : [];
    }

    /**
     * Menonaktifkan kredensial Fazpass untuk test ini agar jalur OTP
     * tidak melakukan panggilan HTTP nyata ke penyedia.
     */
    private function disableFazpass(): void
    {
        foreach (['FAZPASS_MERCHANT_KEY', 'FAZPASS_GATEWAY_KEY'] as $key) {
            $this->fazpassEnvBackup[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null];
            $_ENV[$key] = '';
            $_SERVER[$key] = '';
        }
    }

    private function restoreFazpassEnv(): void
    {
        foreach ($this->fazpassEnvBackup as $key => [$env, $server]) {
            if ($env === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $env;
            }

            if ($server === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $server;
            }
        }

        $this->fazpassEnvBackup = [];
    }

    private function insertEmergencyOtp(int $anggotaId, string $code, int $attempts = 0): void
    {
        $now = date('Y-m-d H:i:s');
        $this->apiDb->table('member_otps')->insert([
            'anggota_id' => $anggotaId,
            'code_hash'  => password_hash($code, PASSWORD_DEFAULT),
            'provider'   => 'emergency',
            'status'     => 'manual',
            'attempts'   => $attempts,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+5 minutes')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** Menerbitkan access token Shield untuk pengujian. */
    private function issueToken(int $userId, string $rawToken, array $scopes): void
    {
        $this->apiDb->table('auth_identities')->insert([
            'user_id'    => $userId,
            'type'       => 'access_token',
            'name'       => 'test',
            'secret'     => hash('sha256', $rawToken),
            'extra'      => serialize($scopes),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function seedIdentities(): void
    {
        $this->apiDb->table('users')->insert([
            'username' => 'admin-api',
            'name'     => 'Admin API',
            'active'   => 1,
        ]);
        $adminId = (int) $this->apiDb->insertID();
        $this->apiDb->table('auth_groups_users')->insert([
            'user_id' => $adminId, 'group' => 'superadmin', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->apiDb->table('auth_identities')->insert([
            'user_id'    => $adminId,
            'type'       => 'email_password',
            'name'       => 'email',
            'secret'     => 'admin@api.test',
            'secret2'    => password_hash(self::ADMIN_PASSWORD, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->apiDb->table('users')->insert([
            'username' => 'operator-api',
            'name'     => 'Operator API',
            'active'   => 1,
        ]);
        $operatorId = (int) $this->apiDb->insertID();
        $this->apiDb->table('auth_groups_users')->insert([
            'user_id' => $operatorId, 'group' => 'operator', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->apiDb->table('auth_identities')->insert([
            'user_id'    => $operatorId,
            'type'       => 'email_password',
            'name'       => 'email',
            'secret'     => 'operator@api.test',
            'secret2'    => password_hash(self::OPERATOR_PASSWORD, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->apiDb->table('users')->insert([
            'username' => 'anggota-api',
            'name'     => 'Anggota Terhubung',
            'active'   => 1,
        ]);
        $anggotaUserId = (int) $this->apiDb->insertID();
        $this->apiDb->table('auth_groups_users')->insert([
            'user_id' => $anggotaUserId, 'group' => 'anggota', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->apiDb->table('auth_identities')->insert([
            'user_id'    => $anggotaUserId,
            'type'       => 'email_password',
            'name'       => 'email',
            'secret'     => 'anggota@api.test',
            'secret2'    => password_hash(self::ANGGOTA_PASSWORD, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->issueToken($anggotaUserId, self::ANGGOTA_TOKEN, ['agenda.read', 'resource.read']);

        $this->apiDb->table('anggota')->insert([
            'name'    => 'Anggota Terhubung',
            'jabatan' => 'Ketua',
            'fraksi'  => 'Fraksi A',
            'komisi'  => 'Komisi I',
            'no_wa'   => '81234567890',
            'aktif'   => 1,
            'user_id' => $anggotaUserId,
        ]);
        $this->linkedAnggotaId = (int) $this->apiDb->insertID();

        $this->apiDb->table('anggota')->insert([
            'name'    => 'Anggota Belum Terhubung',
            'jabatan' => 'Anggota',
            'fraksi'  => 'Fraksi B',
            'komisi'  => 'Komisi II',
            'no_wa'   => '81234567891',
            'aktif'   => 1,
            'user_id' => null,
        ]);
        $this->unlinkedAnggotaId = (int) $this->apiDb->insertID();

        $this->apiDb->table('anggota')->insert([
            'name'    => 'Anggota Nonaktif',
            'jabatan' => 'Anggota',
            'fraksi'  => 'Fraksi C',
            'komisi'  => 'Komisi III',
            'no_wa'   => '89999999999',
            'aktif'   => 0,
            'user_id' => null,
        ]);
    }

    private function createTables(): void
    {
        $this->apiForge->addField([
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
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('users');

        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'    => ['type' => 'INTEGER'],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_groups_users');

        $this->apiForge->addField([
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
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_identities');

        $this->apiForge->addField([
            'id'            => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'jabatan'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fraksi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'komisi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_wa'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif'         => ['type' => 'INTEGER', 'default' => 1],
            'foto'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'user_id'       => ['type' => 'INTEGER', 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('anggota');

        $this->apiForge->addField([
            'id'                      => ['type' => 'INTEGER', 'auto_increment' => true],
            'anggota_id'              => ['type' => 'INTEGER'],
            'code_hash'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'provider'                => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'fazpass'],
            'provider_otp_id'         => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'provider_transaction_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'status'                  => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'created'],
            'attempts'                => ['type' => 'INTEGER', 'default' => 0],
            'expires_at'              => ['type' => 'DATETIME'],
            'used_at'                 => ['type' => 'DATETIME', 'null' => true],
            'created_by_admin_id'     => ['type' => 'INTEGER', 'null' => true],
            'created_at'              => ['type' => 'DATETIME'],
            'updated_at'              => ['type' => 'DATETIME'],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('member_otps');

        // Shield mencatat percobaan login token (termasuk yang gagal).
        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'id_type'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'identifier' => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_id'    => ['type' => 'INTEGER', 'null' => true],
            'date'       => ['type' => 'DATETIME'],
            'success'    => ['type' => 'INTEGER'],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_token_logins');
    }

    private function dropTables(): void
    {
        foreach (['auth_token_logins', 'member_otps', 'anggota', 'auth_identities', 'auth_groups_users', 'users'] as $table) {
            $this->apiForge->dropTable($table, true);
        }
    }
}

/**
 * Cache in-memory minimal untuk Throttler — memisahkan bucket throttle
 * test dari cache file lingkungan development.
 */
final class ApiAuthInMemoryCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $store = [];

    public function initialize(): void
    {
    }

    public function get(string $key): mixed
    {
        return $this->store[$key] ?? null;
    }

    public function save(string $key, mixed $value, int $ttl = 60): bool
    {
        $this->store[$key] = $value;

        return true;
    }

    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        return $this->get($key) ?? $this->save($key, $callback(), $ttl) ? $this->get($key) : null;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key]);

        return true;
    }

    public function deleteMatching(string $pattern): int
    {
        $regex = '/' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '/';
        $count = 0;

        foreach (array_keys($this->store) as $key) {
            if (preg_match($regex, (string) $key) === 1) {
                unset($this->store[$key]);
                $count++;
            }
        }

        return $count;
    }

    public function increment(string $key, int $offset = 1): bool|int
    {
        $this->store[$key] = (int) ($this->store[$key] ?? 0) + $offset;

        return (int) $this->store[$key];
    }

    public function decrement(string $key, int $offset = 1): bool|int
    {
        return $this->increment($key, -$offset);
    }

    public function clean(): bool
    {
        $this->store = [];

        return true;
    }

    public function getCacheInfo(): array|false|object|null
    {
        return $this->store;
    }

    public function getMetaData(string $key): ?array
    {
        return null;
    }

    public function isSupported(): bool
    {
        return true;
    }
}
