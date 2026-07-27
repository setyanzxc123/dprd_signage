<?php

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthRouteSecurityTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testLoginPageIsNotCachedAndContainsCsrfProtectedForm(): void
    {
        $response = $this->get('/login?akses=anggota');

        $response->assertOK();
        $response->assertHeader('Cache-Control');
        $this->assertStringContainsString('no-store', $response->response()->getHeaderLine('Cache-Control'));
        $this->assertStringContainsString('name="csrf_test_name"', $response->response()->getBody());
        $this->assertStringContainsString('login/anggota', $response->response()->getBody());
        $this->assertStringNotContainsString('login/anggota/otp', $response->response()->getBody());
        $this->assertStringContainsString('data-max-digits="13"', $response->response()->getBody());
    }

    public function testAdminLoginRejectsRequestWithoutCsrfToken(): void
    {
        $this->expectException(SecurityException::class);
        $this->post('/login/admin', ['username' => 'operator', 'password' => 'invalid']);
    }

    public function testMemberOtpRequestUsesPostRedirectGet(): void
    {
        $response = $this->post('/login/anggota', [
            csrf_token() => csrf_hash(),
            'no_wa'      => 'bukan-nomor',
        ]);

        $response->assertStatus(303);
        $response->assertRedirectTo(base_url('login?akses=anggota'));
    }

    public function testMemberOtpVerificationWithoutPendingSessionUsesPostRedirectGet(): void
    {
        $response = $this->post('/login/anggota/verifikasi', [
            csrf_token() => csrf_hash(),
            'otp'        => '123456',
        ]);

        $response->assertStatus(303);
        $response->assertRedirectTo(base_url('login?akses=anggota'));
    }

    public function testPendingOtpStepPersistsWhileAdminTabIsActive(): void
    {
        $now = time();
        $this->withSession([
            'member_otp_pending' => [
                'account_id'     => 0,
                'anggota_id'     => 0,
                'phone_hash'     => hash('sha256', 'invalid-member-phone'),
                'masked'         => '+62 •••• ••••',
                'retry_at'       => $now + 30,
                'otp_expires_at' => $now + 300,
                'expires_at'     => $now + 900,
            ],
        ]);

        $response = $this->get('/login?akses=admin');

        $response->assertOK();
        $this->assertStringContainsString('Verifikasi Kode OTP', $response->response()->getBody());
        $this->assertStringContainsString('data-login-panel="admin" class=""', $response->response()->getBody());
    }

    public function testOldMemberOtpEndpointNoLongerExists(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->post('/login/anggota/otp', [
            csrf_token() => csrf_hash(),
            'no_wa'      => 'bukan-nomor',
        ]);
    }

    public function testRootRedirectsToAgendaInsteadOfTvSignage(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirectTo(base_url('agenda'));
    }

    public function testBanmusProjectionPageIsSeparateFromAgendaFilters(): void
    {
        $response = $this->get('/agenda/jadwal-banmus');

        $response->assertOK();
        $this->assertStringContainsString('Jadwal Banmus', $response->response()->getBody());
        $this->assertStringContainsString('Belum ada Jadwal Banmus', $response->response()->getBody());
        $this->assertStringNotContainsString('Jadwal Sidang', $response->response()->getBody());
        $this->assertStringContainsString('name="tahun"', $response->response()->getBody());
        $this->assertStringContainsString('name="semester"', $response->response()->getBody());
        $this->assertStringNotContainsString('diselesaikan pada fase berikutnya', $response->response()->getBody());
    }

    public function testAgendaShellLinksToBanmusProjectionOutsideItsFilterLogic(): void
    {
        $response = $this->get('/agenda');
        $body = $response->response()->getBody();

        $response->assertOK();
        $this->assertStringContainsString(base_url('agenda/jadwal-banmus'), $body);
        $this->assertStringContainsString('Jadwal Banmus', $body);
        $this->assertStringNotContainsString("navButtonClass('bamus')", $body);
        $this->assertStringNotContainsString('Jadwal Sidang', $body);
        $this->assertStringContainsString('Filter periode agenda rapat', $body);
        $this->assertStringContainsString('Semester ini', $body);
        $this->assertStringContainsString('Jumlah agenda per halaman', $body);
        $this->assertStringContainsString('collapse collapse-arrow', $body);
        $this->assertStringContainsString('handleAgendaToggle($event, item.id)', $body);
    }

    public function testMemberScheduleApiRejectsAnonymousRequestWithJson(): void
    {
        $response = $this->get('/api/v1/anggota/jadwal');

        $response->assertStatus(401);
        $response->assertHeader('Cache-Control');
        $this->assertStringContainsString('no-store', $response->response()->getHeaderLine('Cache-Control'));
        $this->assertSame('application/json; charset=UTF-8', $response->response()->getHeaderLine('Content-Type'));
    }

    public function testGeneralAgendaAdminRequiresAuthentication(): void
    {
        $response = $this->get('/admin/agenda-umum');

        $response->assertRedirectTo(base_url('login?akses=admin'));
    }

    public function testBanmusProjectionAdminRequiresAuthentication(): void
    {
        $response = $this->get('/admin/jadwal-banmus');

        $response->assertRedirectTo(base_url('login?akses=admin'));
    }

    /**
     * @dataProvider stateChangingGetRoutes
     */
    public function testStateChangingRoutesRejectGet(string $path): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get($path);
    }

    /** @return iterable<string, array{string}> */
    public static function stateChangingGetRoutes(): iterable
    {
        yield 'admin logout' => ['/admin/logout'];
        yield 'member OTP request' => ['/login/anggota'];
        yield 'member delete' => ['/admin/anggota/1/delete'];
        yield 'room delete' => ['/admin/ruangan/1/delete'];
        yield 'meeting delete' => ['/admin/jadwal/1/delete'];
        yield 'general agenda delete' => ['/admin/agenda-umum/1/delete'];
        yield 'Banmus projection delete' => ['/admin/jadwal-banmus/1/delete'];
    }
}
