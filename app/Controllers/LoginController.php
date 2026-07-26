<?php

namespace App\Controllers;

class LoginController extends BaseController
{
    public function index()
    {
        if (session()->has('auth_user')) {
            return redirect()->to(base_url('admin/dashboard'));
        }

        if (session()->has('member_auth')) {
            return redirect()->to(base_url('anggota'));
        }

        $access = strtolower(trim((string) $this->request->getGet('akses')));
        if (! in_array($access, ['anggota', 'admin'], true)) {
            $access = 'anggota';
        }

        return view('auth/login', [
            'pageTitle' => 'Masuk Sistem DPRD',
            'access'    => $access,
        ]);
    }
}
