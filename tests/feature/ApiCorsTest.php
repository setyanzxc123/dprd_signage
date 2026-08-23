<?php

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Pengujian CORS /api/v1 (Fase 6): preflight OPTIONS dijawab filter cors
 * melalui rute catch-all, header CORS tetap terpasang pada respons 401
 * dari filter auth (cors didaftarkan paling awal), dan cakupan terbatas
 * hanya untuk /api/v1.
 *
 * @internal
 */
final class ApiCorsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // Objek response dibagi antar-request dalam satu proses PHPUnit;
        // ganti dengan instance segar agar header CORS dari test sebelumnya
        // tidak bocor ke assertion keberadaan/absennya header.
        Services::injectMock('response', service('response', null, false));
    }

    public function testPreflightRequestIsAnsweredByCorsFilter(): void
    {
        $response = $this
            ->withHeaders([
                'Origin'                        => 'https://contoh.example.com',
                'Access-Control-Request-Method' => 'GET',
            ])
            ->options('/api/v1/ruangan');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', '*');
        $this->assertStringContainsString(
            'GET',
            $response->response()->getHeaderLine('Access-Control-Allow-Methods'),
        );
        $this->assertStringContainsString(
            'Authorization',
            $response->response()->getHeaderLine('Access-Control-Allow-Headers'),
        );
    }

    public function testUnauthorizedApiResponseKeepsCorsHeaders(): void
    {
        $response = $this
            ->withHeaders(['Origin' => 'https://contoh.example.com'])
            ->get('/api/v1/ruangan');

        $response->assertStatus(401);
        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function testCorsIsScopedToApiV1Only(): void
    {
        $response = $this
            ->withHeaders(['Origin' => 'https://contoh.example.com'])
            ->get('/login');

        $response->assertOK();
        $this->assertFalse(
            $response->response()->hasHeader('Access-Control-Allow-Origin'),
            'Header CORS tidak boleh terpasang di luar /api/v1.',
        );
    }
}
