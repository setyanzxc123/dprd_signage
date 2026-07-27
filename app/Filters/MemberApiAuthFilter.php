<?php

namespace App\Filters;

use App\Models\MemberAccountModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MemberApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = session()->get('member_auth');
        if (! is_array($auth)) {
            return $this->unauthorized();
        }

        $account = (new MemberAccountModel())->findActiveSessionAccount(
            (int) ($auth['account_id'] ?? 0),
            (int) ($auth['anggota_id'] ?? 0),
        );

        if ($account === null) {
            session()->remove('member_auth');

            return $this->unauthorized();
        }

        session()->set('member_auth', [
            'account_id' => (int) $account['account_id'],
            'anggota_id' => (int) $account['anggota_id'],
            'name'       => (string) $account['name'],
        ]);
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
