<?php

namespace App\Filters;

use App\Models\AnggotaModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MemberAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = session()->get('member_auth');
        if (! is_array($auth)) {
            return $this->redirectToLogin();
        }

        $anggotaId = (int) ($auth['anggota_id'] ?? 0);
        $account = (new AnggotaModel())->findActiveSessionMember($anggotaId);

        if ($account === null) {
            session()->remove('member_auth');
            return $this->redirectToLogin();
        }

        session()->set('member_auth', [
            'anggota_id' => (int) $account['anggota_id'],
            'name'       => $account['name'],
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah response.
    }

    private function redirectToLogin()
    {
        return redirect()->to(base_url('login?akses=anggota'));
    }
}
