<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Melindungi endpoint JSON anggota dengan dua jalur sekaligus: sesi
 * web (fetch Vue same-origin) dan bearer token Shield (aplikasi
 * mobile). Resolusi keduanya dipusatkan di RequestIdentityService.
 */
class MemberApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $anggota = service('requestIdentity')->currentAnggota();

        if ($anggota === null) {
            return $this->unauthorized();
        }

        // Sesi web: segarkan state sesi seperti perilaku sebelumnya.
        if (session()->has('member_auth')) {
            session()->set('member_auth', [
                'anggota_id' => (int) $anggota['anggota_id'],
                'name'       => (string) $anggota['name'],
            ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah response.
    }

    private function unauthorized(): ResponseInterface
    {
        return service('response')
            ->setStatusCode(401)
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Pragma', 'no-cache')
            ->setJSON([
                'status'  => 'error',
                'message' => 'Sesi anggota berakhir. Silakan masuk kembali.',
            ]);
    }
}
