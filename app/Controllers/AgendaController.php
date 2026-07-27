<?php

namespace App\Controllers;

use App\Models\MemberAccountModel;
use CodeIgniter\HTTP\ResponseInterface;

class AgendaController extends BaseController
{
    public function root()
    {
        return redirect()->to(base_url('agenda'), 302);
    }

    public function index()
    {
        $member = $this->activeMember();
        $isMember = $member !== null;

        return $this->privateResponse()->setBody(view('agenda/index', [
            'namaInstansi' => 'DPRD Provinsi Sulawesi Tengah',
            'logoUrl'      => base_url('assets/images/logo_dprd.jpg'),
            'portalUrl'    => base_url('agenda'),
            'apiUrl'       => base_url($isMember ? 'api/v1/anggota/jadwal' : 'api/v1/publik/jadwal'),
            'generalApiUrl' => base_url('api/v1/publik/agenda-umum'),
            'member'       => $member,
        ]));
    }

    public function banmus()
    {
        return $this->privateResponse()->setBody(view('agenda/banmus', [
            'logoUrl'   => base_url('assets/images/logo_dprd.jpg'),
            'portalUrl' => base_url('agenda'),
            'member'    => $this->activeMember(),
        ]));
    }

    public function legacy()
    {
        return redirect()->to(base_url('agenda'), 302);
    }

    private function privateResponse(): ResponseInterface
    {
        return $this->response
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Pragma', 'no-cache')
            ->appendHeader('Vary', 'Cookie');
    }

    private function activeMember(): ?array
    {
        $auth = session()->get('member_auth');
        if (! is_array($auth)) {
            return null;
        }

        $account = (new MemberAccountModel())->findActiveSessionAccount(
            (int) ($auth['account_id'] ?? 0),
            (int) ($auth['anggota_id'] ?? 0),
        );
        if ($account === null) {
            session()->remove('member_auth');

            return null;
        }

        session()->set('member_auth', [
            'account_id' => (int) $account['account_id'],
            'anggota_id' => (int) $account['anggota_id'],
            'name'       => (string) $account['name'],
        ]);

        return $account;
    }
}
