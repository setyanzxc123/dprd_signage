<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

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

        $updateData = ['name' => $name];
        if ($passwordChanged) {
            $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $updated = (new UserModel())->update((int) $user['id'], $updateData);

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

    /**
     * @param array<string, mixed> $user
     */
    private function validatePasswordChange(
        array $user,
        string $currentPassword,
        string $newPassword,
        string $passwordConfirmation,
    ): ?string {
        if ($currentPassword === '' || $newPassword === '' || $passwordConfirmation === '') {
            return 'Semua kolom password wajib diisi.';
        }

        if (! password_verify($currentPassword, (string) $user['password'])) {
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

        if (password_verify($newPassword, (string) $user['password'])) {
            return 'Password baru harus berbeda dari password saat ini.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authenticatedUser(): ?array
    {
        $authUser = session()->get('auth_user');
        if (! is_array($authUser) || empty($authUser['id'])) {
            return null;
        }

        return (new UserModel())->find((int) $authUser['id']);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private function viewData(array $user, ?string $formError = null, ?string $submittedName = null): array
    {
        return [
            'pageTitle'   => 'Profil Admin',
            'breadcrumbs' => [],
            'user'        => $user,
            'form_error'  => $formError,
            'form_name'   => $submittedName ?? (string) $user['name'],
        ];
    }

    private function missingAccountResponse()
    {
        session()->remove('auth_user');
        session()->setFlashdata('auth_form_error', 'Akun admin tidak ditemukan. Silakan login kembali.');

        return redirect()->to(base_url('login?akses=admin'), 303);
    }
}
