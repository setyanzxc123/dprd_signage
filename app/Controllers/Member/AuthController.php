<?php

namespace App\Controllers\Member;

use App\Controllers\BaseController;
use App\Libraries\WhatsappService;
use App\Models\MemberAccountModel;

class AuthController extends BaseController
{
    public function loginPage()
    {
        if (session()->has('member_auth')) {
            return redirect()->to(base_url('anggota'));
        }

        return redirect()->to(base_url('login?akses=anggota'));
    }

    public function loginProcess()
    {
        $phone = $this->localPhone((string) $this->request->getPost('no_wa'));
        $password = (string) $this->request->getPost('password');

        $ipKey = 'member_login_ip_' . hash('sha256', $this->request->getIPAddress());
        if (! service('throttler')->check($ipKey, 10, 60)) {
            return $this->loginFailure('Terlalu banyak percobaan login. Silakan tunggu satu menit.', 429);
        }

        if ($phone === null || $password === '') {
            return $this->loginFailure();
        }

        $phoneKey = 'member_login_phone_' . hash('sha256', $phone);
        if (! service('throttler')->check($phoneKey, 5, 60)) {
            return $this->loginFailure('Terlalu banyak percobaan untuk akun ini. Silakan tunggu satu menit.', 429);
        }

        $model = new MemberAccountModel();
        $account = $model->findLoginByPhone($phone);
        $storedHash = (string) ($account['password_hash'] ?? password_hash('invalid-member-login', PASSWORD_DEFAULT));
        $passwordValid = password_verify($password, $storedHash);

        if (
            $account === null
            || ! $passwordValid
            || (int) $account['login_enabled'] !== 1
            || (int) $account['aktif'] !== 1
        ) {
            return $this->loginFailure();
        }

        session()->regenerate(true);
        session()->remove('auth_user');
        session()->set('member_auth', [
            'account_id' => (int) $account['account_id'],
            'anggota_id' => (int) $account['anggota_id'],
            'name'       => $account['name'],
        ]);

        $model->update((int) $account['account_id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        $destination = (string) session()->pull('member_intended_path', '/anggota');
        if (! str_starts_with($destination, '/anggota') || str_starts_with($destination, '//')) {
            $destination = '/anggota';
        }

        return redirect()->to(base_url(ltrim($destination, '/')), 303);
    }

    public function logout()
    {
        session()->remove(['member_auth', 'member_intended_path']);
        session()->regenerate(true);
        session()->setFlashdata('success', 'Anda berhasil keluar dari akun anggota.');

        return redirect()->to(base_url('login?akses=anggota'), 303);
    }

    private function localPhone(string $phone): ?string
    {
        $normalized = WhatsappService::normalizePhone($phone);
        if (! WhatsappService::isValidIndonesianPhone($normalized)) {
            return null;
        }

        return substr($normalized, 2);
    }

    private function loginFailure(
        string $message = 'Nomor WhatsApp atau password tidak sesuai.',
        int $statusCode = 422,
    ) {
        return $this->response
            ->setStatusCode($statusCode)
            ->setBody(view('auth/login', [
                'pageTitle'  => 'Masuk Sistem DPRD',
                'access'     => 'anggota',
                'form_error' => $message,
                'old_phone'  => trim((string) $this->request->getPost('no_wa')),
            ]));
    }
}
