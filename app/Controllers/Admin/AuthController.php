<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class AuthController extends BaseController
{
    public function loginPage()
    {
        if (session()->get('auth_user')) {
            return redirect()->to(base_url('admin/dashboard'));
        }

        return redirect()->to(base_url('login?akses=admin'));
    }

    public function loginProcess()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = $this->request->getPost('password');

        $ipKey = 'admin_login_ip_' . hash('sha256', $this->request->getIPAddress());
        if (! service('throttler')->check($ipKey, 10, 60)) {
            return $this->loginFailure('Terlalu banyak percobaan login. Silakan tunggu satu menit.', 429, $username);
        }

        $model = new UserModel();
        $user = $username !== '' ? $model->where('username', $username)->first() : null;
        $storedHash = (string) ($user['password'] ?? password_hash('invalid-admin-login', PASSWORD_DEFAULT));
        $passwordValid = password_verify((string) $password, $storedHash);

        if ($user && $passwordValid) {
            session()->remove(['member_auth', 'member_intended_path']);
            session()->regenerate(true);
            session()->set('auth_user', [
                'id'       => $user['id'],
                'name'     => $user['name'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ]);
            return redirect()->to(base_url('admin/dashboard'), 303);
        }

        return $this->loginFailure('Username atau password tidak sesuai.', 422, $username);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('login?akses=admin'));
    }

    private function loginFailure(string $message, int $statusCode, string $username)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setBody(view('auth/login', [
                'pageTitle'    => 'Masuk Sistem DPRD',
                'access'       => 'admin',
                'form_error'   => $message,
                'old_username' => $username,
            ]));
    }
}
