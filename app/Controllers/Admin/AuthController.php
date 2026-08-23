<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Security\AdminLoginThrottle;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

class AuthController extends BaseController
{
    private const DUMMY_PASSWORD_HASH = '$2y$10$ffiPtSZb76eMM.xQCPAsUOshAStdyZLCoGmebeOjgZcrqi5OQhdwy';

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
        $ipAddress = $this->request->getIPAddress();
        $throttle = new AdminLoginThrottle();

        if (! $throttle->allows($username, $ipAddress)) {
            $this->auditFailure($throttle, $username, $ipAddress, 'throttled');

            return $this->loginFailure('Terlalu banyak percobaan login. Silakan tunggu beberapa saat.', $username);
        }

        // Kredensial diverifikasi terhadap identitas Shield; hash dummy
        // dipakai saat username tidak ditemukan agar biaya verifikasi
        // sama dan status akun tidak mudah ditebak.
        $model = new UserModel();
        $user = $username !== ''
            ? $model->withIdentities()->withGroups()->where('username', $username)->first()
            : null;

        $identity = $user instanceof User ? $user->getEmailIdentity() : null;
        $storedHash = (string) ($identity?->secret2 ?? self::DUMMY_PASSWORD_HASH);
        $passwordValid = password_verify((string) $password, $storedHash);

        if ($user instanceof User && $passwordValid && $user->isActivated()) {
            $throttle->clearUsername($username);
            session()->remove(['member_auth', 'member_intended_path', 'member_otp_pending']);
            session()->regenerate(true);
            session()->set('auth_user', [
                'id'       => $user->id,
                'name'     => (string) $user->name,
                'username' => $user->username,
                'role'     => $user->inGroup('superadmin') ? 'superadmin' : 'operator',
            ]);
            return redirect()->to(base_url('admin/dashboard'), 303);
        }

        $this->auditFailure($throttle, $username, $ipAddress, 'invalid_credentials');

        return $this->loginFailure('Username atau password tidak sesuai.', $username);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('login?akses=admin'), 303);
    }

    private function loginFailure(string $message, string $username)
    {
        session()->setFlashdata([
            'auth_form_error'   => $message,
            'auth_old_username' => $username,
        ]);

        return redirect()->to(base_url('login?akses=admin'), 303);
    }

    private function auditFailure(
        AdminLoginThrottle $throttle,
        string $username,
        string $ipAddress,
        string $reason,
    ): void {
        log_message('notice', 'Login admin gagal: reason={reason}, username_hash={username_hash}, ip_hash={ip_hash}', [
            'reason'        => $reason,
            'username_hash' => $throttle->usernameFingerprint($username),
            'ip_hash'       => hash('sha256', $ipAddress),
        ]);
    }
}
