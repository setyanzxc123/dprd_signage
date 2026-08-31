<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class CsrfHandlingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const FRIENDLY_MESSAGE = 'Sesi formulir telah berakhir. Muat ulang halaman lalu ulangi tindakan Anda.';

    public function testPostWithoutCsrfTokenRedirectsToLoginWithFriendlyMessage(): void
    {
        $response = $this->post('/login/anggota', ['no_wa' => 'bukan-nomor']);

        $response->assertStatus(302);
        $response->assertRedirectTo(base_url('login?akses=anggota'));
        $this->assertSame(self::FRIENDLY_MESSAGE, session()->getFlashdata('auth_form_error'));
    }

    public function testAjaxPostWithoutCsrfTokenReceivesJson403WithFreshToken(): void
    {
        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
        ])->post('/login/admin', ['username' => 'operator', 'password' => 'invalid']);

        $response->assertStatus(403);
        $payload = json_decode($response->response()->getBody(), true);

        $this->assertFalse($payload['status']);
        $this->assertSame(self::FRIENDLY_MESSAGE, $payload['message']);
        $this->assertSame(csrf_token(), $payload['csrf']['name']);
        $this->assertNotEmpty($payload['csrf']['hash']);
    }

    public function testAdminPostWithoutCsrfTokenRedirectsBackWithErrorFlash(): void
    {
        $response = $this->post('/admin/jadwal-umum/1/delete');

        $response->assertStatus(302);
        $this->assertSame(self::FRIENDLY_MESSAGE, session()->getFlashdata('error'));
    }

    public function testSameTokenRemainsValidAcrossSuccessivePosts(): void
    {
        $token = csrf_token();
        $hash  = csrf_hash();

        $first = $this->post('/login/anggota', [
            $token => $hash,
            'no_wa' => 'bukan-nomor',
        ]);
        $first->assertStatus(303);

        $second = $this->post('/login/anggota', [
            $token => $hash,
            'no_wa' => 'bukan-nomor',
        ]);
        $second->assertStatus(303);
    }
}
