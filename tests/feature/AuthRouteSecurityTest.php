<?php

use CodeIgniter\Exceptions\PageNotFoundException;
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
        $this->assertStringContainsString('data-max-digits="12"', $response->response()->getBody());
    }

    public function testAdminLoginWithoutCsrfTokenGetsFriendlyRedirect(): void
    {
        $response = $this->post('/login/admin', ['username' => 'operator', 'password' => 'invalid']);

        $response->assertStatus(302);
        $this->assertSame(
            'Sesi formulir telah berakhir. Muat ulang halaman lalu ulangi tindakan Anda.',
            session()->getFlashdata('auth_form_error'),
        );
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
        $body = $response->response()->getBody();
        $this->assertStringContainsString('Verifikasi Kode OTP', $body);
        $this->assertSame(1, preg_match(
            '/<div data-login-panel="admin" class="([^"]*)">/',
            $body,
            $adminPanel,
        ));
        $this->assertStringNotContainsString('hidden', $adminPanel[1]);
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
        $this->assertStringContainsString('Proyeksi Banmus', $response->response()->getBody());
        $this->assertStringContainsString('Belum ada Proyeksi Banmus', $response->response()->getBody());
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
        $this->assertStringContainsString('Proyeksi Banmus', $body);
        $this->assertStringContainsString('Agenda Rapat', $body);
        $this->assertStringContainsString('Jadwal Umum', $body);
        $this->assertStringContainsString("item.source === 'jadwal_umum'", $body);
        $this->assertStringContainsString("item.source === 'banmus'", $body);
        $this->assertStringNotContainsString('Agenda Insidental', $body);
        $this->assertStringContainsString('v-for="item in paginatedGeneralAgendas"', $body);
        $this->assertStringNotContainsString("navButtonClass('bamus')", $body);
        $this->assertStringNotContainsString('Jadwal Sidang', $body);
        $this->assertStringContainsString('Filter periode agenda rapat', $body);
        $this->assertStringContainsString('Filter periode jadwal umum', $body);
        $this->assertStringContainsString('Semester ini', $body);
        $this->assertStringContainsString('Jumlah agenda per halaman', $body);
        $this->assertStringContainsString('Jumlah jadwal umum per halaman', $body);
        $this->assertStringContainsString('collapse collapse-arrow', $body);
        $this->assertStringContainsString('handleAgendaToggle($event, item.key)', $body);
        $this->assertSame(2, substr_count($body, ':href="item.materi_url"'));
        $this->assertSame(2, substr_count($body, ':href="item.stream_url"'));
        $viewSource = (string) file_get_contents(APPPATH . 'Views/agenda/index.php');
        $this->assertSame(2, substr_count($viewSource, ':href="item.undangan_url"'));
        $this->assertStringContainsString('projectionOverlapsMonths(item, selectedMonths)', $body);
        $this->assertStringContainsString('range[0] <= months[months.length - 1]', $body);
        $this->assertStringNotContainsString('month === null || selectedMonths.has(month)', $body);
    }

    public function testAgendaHeadersLinkActiveAdminBackToAdminPanel(): void
    {
        $session = [
            'auth_user' => [
                'id'       => 1,
                'name'     => 'Admin Pengujian',
                'username' => 'admin-test',
                'role'     => 'superadmin',
            ],
        ];

        foreach (['/agenda', '/agenda/jadwal-banmus'] as $path) {
            $response = $this->withSession($session)->get($path);

            $response->assertOK();
            $body = $response->response()->getBody();
            $this->assertStringContainsString(base_url('admin/dashboard'), $body);
            $this->assertStringContainsString('Panel Admin', $body);
            $this->assertStringNotContainsString(
                '<span class="hidden sm:inline">Masuk Anggota</span>',
                $body,
            );
        }
    }

    public function testMemberScheduleApiRejectsAnonymousRequestWithJson(): void
    {
        $response = $this->get('/api/v1/anggota/jadwal');

        $response->assertStatus(401);
        $response->assertHeader('Cache-Control');
        $this->assertStringContainsString('no-store', $response->response()->getHeaderLine('Cache-Control'));
        $this->assertSame('application/json; charset=UTF-8', $response->response()->getHeaderLine('Content-Type'));
    }

    public function testGeneralScheduleAdminRequiresAuthentication(): void
    {
        $response = $this->get('/admin/jadwal-umum');

        $response->assertRedirectTo(base_url('login?akses=admin'));
    }

    public function testBanmusProjectionAdminRequiresAuthentication(): void
    {
        $response = $this->get('/admin/jadwal-banmus');

        $response->assertRedirectTo(base_url('login?akses=admin'));
    }

    public function testAdminProfilePageRequiresAuthentication(): void
    {
        $response = $this->get('/admin/profile');

        $response->assertRedirectTo(base_url('login?akses=admin'));
    }

    public function testEmergencyOtpActionDoesNotExposeReasonInput(): void
    {
        $body = view('admin/anggota/index', [
            'pageTitle' => 'Anggota DPRD',
            'members'   => [[
                'id'            => 7,
                'name'          => 'Anggota Pengujian',
                'jabatan'       => 'Anggota DPRD',
                'fraksi'        => 'Fraksi Pengujian',
                'komisi'        => 'Komisi I',
                'no_wa'         => '85156049890',
                'aktif'         => 1,
                'last_login_at' => null,
            ]],
            'data_scope' => ['label' => 'seluruh master anggota'],
        ]);

        $this->assertStringContainsString('/admin/anggota/7/otp-darurat', $body);
        $this->assertStringContainsString('Buat OTP darurat', $body);
        $this->assertStringNotContainsString('name="reason"', $body);
        $this->assertStringNotContainsString('placeholder="Alasan darurat"', $body);
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
        yield 'general schedule delete' => ['/admin/jadwal-umum/1/delete'];
        yield 'Banmus projection delete' => ['/admin/jadwal-banmus/1/delete'];
        yield 'admin profile update' => ['/admin/profile/update'];
    }
}
