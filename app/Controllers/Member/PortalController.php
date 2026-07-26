<?php

namespace App\Controllers\Member;

use App\Controllers\BaseController;
use App\Models\MemberAccountModel;

class PortalController extends BaseController
{
    public function index()
    {
        $auth = session()->get('member_auth');
        $account = (new MemberAccountModel())->findActiveSessionAccount(
            (int) ($auth['account_id'] ?? 0),
            (int) ($auth['anggota_id'] ?? 0),
        );

        if ($account === null) {
            session()->remove('member_auth');
            return redirect()->to(base_url('login?akses=anggota'));
        }

        return view('member/dashboard', [
            'pageTitle' => 'Portal Anggota DPRD',
            'member'    => $account,
            'units'     => $this->memberUnits((int) $account['anggota_id']),
        ]);
    }

    private function memberUnits(int $anggotaId): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('anggota_unit_rapat')) {
            return [];
        }

        return $db->table('anggota_unit_rapat aur')
            ->select('ur.id, ur.nama')
            ->join('unit_rapat ur', 'ur.id = aur.unit_rapat_id')
            ->where('aur.anggota_id', $anggotaId)
            ->where('ur.aktif', 1)
            ->orderBy('ur.urutan', 'ASC')
            ->orderBy('ur.nama', 'ASC')
            ->get()
            ->getResultArray();
    }
}
