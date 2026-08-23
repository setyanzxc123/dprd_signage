<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Melindungi endpoint API tulis admin: wajib bearer token Shield yang
 * valid (401 bila tidak) dan pemiliknya tergabung grup superadmin atau
 * operator (403 untuk token anggota).
 */
class ApiAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = service('requestIdentity')->currentUser();

        if ($user === null) {
            return $this->errorResponse(401, 'Token tidak valid atau tidak disertakan.');
        }

        if (! $user->inGroup('superadmin', 'operator')) {
            return $this->errorResponse(403, 'Anda tidak memiliki hak akses untuk aksi ini.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah response.
    }

    private function errorResponse(int $status, string $message): ResponseInterface
    {
        return service('response')
            ->setStatusCode($status)
            ->setHeader('Cache-Control', 'private, no-store')
            ->setJSON(['status' => 'error', 'message' => $message]);
    }
}
