<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

class ProfileController extends BaseController
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 72;

    public function index()
    {
        $user = $this->authenticatedUser();
        if ($user === null) {
            return $this->missingAccountResponse();
        }

        return view('admin/profile/index', $this->viewData($user));
    }

    public function update()
    {
        $user = $this->authenticatedUser();
        if ($user === null) {
            return $this->missingAccountResponse();
        }

        $name = trim((string) $this->request->getPost('name'));
        $currentPassword = (string) $this->request->getPost('current_password');
        $newPassword = (string) $this->request->getPost('new_password');
        $passwordConfirmation = (string) $this->request->getPost('new_password_confirmation');

        $error = $this->validateName($name);
        $passwordChanged = $currentPassword !== ''
            || $newPassword !== ''
            || $passwordConfirmation !== '';

        if ($error === null && $passwordChanged) {
            $error = $this->validatePasswordChange(
                $user,
                $currentPassword,
                $newPassword,
                $passwordConfirmation,
            );
        }

        if ($error !== null) {
            return $this->response
                ->setStatusCode(422)
                ->setBody(view('admin/profile/index', $this->viewData($user, $error, $name)));
        }

        $user->name = $name;
        if ($passwordChanged) {
            // Hash baru dibuat oleh Shield saat identitas disimpan.
            $user->password = $newPassword;
        }

        $updated = (new UserModel())->save($user);

        if (! $updated) {
            return $this->response
                ->setStatusCode(500)
                ->setBody(view(
                    'admin/profile/index',
                    $this->viewData($user, 'Profil gagal diperbarui. Silakan coba kembali.', $name),
                ));
        }

        $authUser = session()->get('auth_user');
        $authUser['name'] = $name;
        session()->set('auth_user', $authUser);
        session()->regenerate(true);
        session()->setFlashdata(
            'success',
            $passwordChanged
                ? 'Profil dan password berhasil diperbarui.'
                : 'Profil berhasil diperbarui.',
        );

        return redirect()->to(base_url('admin/profile'), 303);
    }

    private function validateName(string $name): ?string
    {
        $length = mb_strlen($name);

        if ($length < 3 || $length > 100) {
            return 'Nama admin harus terdiri dari 3 sampai 100 karakter.';
        }

        return null;
    }

    private function validatePasswordChange(
        User $user,
        string $currentPassword,
        string $newPassword,
        string $passwordConfirmation,
    ): ?string {
        if ($currentPassword === '' || $newPassword === '' || $passwordConfirmation === '') {
            return 'Semua kolom password wajib diisi.';
        }

        $currentHash = $this->passwordHash($user);

        if (! password_verify($currentPassword, $currentHash)) {
            return 'Password saat ini tidak sesuai.';
        }

        $length = mb_strlen($newPassword);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return sprintf(
                'Password baru harus terdiri dari %d sampai %d karakter.',
                self::MIN_LENGTH,
                self::MAX_LENGTH,
            );
        }

        if (! hash_equals($newPassword, $passwordConfirmation)) {
            return 'Konfirmasi password baru tidak sesuai.';
        }

        if (password_verify($newPassword, $currentHash)) {
            return 'Password baru harus berbeda dari password saat ini.';
        }

        return null;
    }

    private function passwordHash(User $user): string
    {
        $identity = $user->getEmailIdentity();

        return (string) ($identity->secret2 ?? '');
    }

    private function authenticatedUser(): ?User
    {
        $authUser = session()->get('auth_user');
        if (! is_array($authUser) || empty($authUser['id'])) {
            return null;
        }

        $user = (new UserModel())->withIdentities()->find((int) $authUser['id']);

        return $user instanceof User ? $user : null;
    }

    private function viewData(User $user, ?string $formError = null, ?string $submittedName = null): array
    {
        return [
            'pageTitle'   => 'Profil Admin',
            'breadcrumbs' => [],
            'user'        => [
                'id'       => $user->id,
                'username' => $user->username,
                'name'     => (string) $user->name,
            ],
            'form_error'  => $formError,
            'form_name'   => $submittedName ?? (string) $user->name,
        ];
    }

    private function missingAccountResponse()
    {
        session()->remove('auth_user');
        session()->setFlashdata('auth_form_error', 'Akun admin tidak ditemukan. Silakan login kembali.');

        return redirect()->to(base_url('login?akses=admin'), 303);
    }
}
