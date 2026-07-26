<?php

namespace App\Filters;

use App\Models\MemberAccountModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MemberAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = session()->get('member_auth');
        if (! is_array($auth)) {
            return $this->redirectToLogin($request);
        }

        $accountId = (int) ($auth['account_id'] ?? 0);
        $anggotaId = (int) ($auth['anggota_id'] ?? 0);
        $account = (new MemberAccountModel())->findActiveSessionAccount($accountId, $anggotaId);

        if ($account === null) {
            session()->remove('member_auth');
            return $this->redirectToLogin($request);
        }

        session()->set('member_auth', [
            'account_id' => (int) $account['account_id'],
            'anggota_id' => (int) $account['anggota_id'],
            'name'       => $account['name'],
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah response.
    }

    private function redirectToLogin(RequestInterface $request)
    {
        $path = trim($request->getUri()->getPath(), '/');
        if ($path !== '' && $path !== 'anggota/login') {
            session()->set('member_intended_path', '/' . $path);
        }

        return redirect()->to(base_url('login?akses=anggota'));
    }
}
