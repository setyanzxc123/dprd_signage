<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Auth\AdminProfileService;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

class ProfileController extends BaseController
{
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

        $service = new AdminProfileService();
        $payload = $service->validatedProfile(
            (array) $this->request->getPost(),
            $user,
        );

        if (isset($payload['error'])) {
            return $this->response
                ->setStatusCode(422)
                ->setBody(view(
                    'admin/profile/index',
                    $this->viewData($user, $payload['error'], trim((string) $this->request->getPost('name'))),
                ));
        }

        $updated = $service->persistProfile($user, $payload);

        if (! $updated) {
            return $this->response
                ->setStatusCode(500)
                ->setBody(view(
                    'admin/profile/index',
                    $this->viewData($user, 'Profil gagal diperbarui. Silakan coba kembali.', $payload['name']),
                ));
        }

        $authUser = session()->get('auth_user');
        $authUser['name'] = $payload['name'];
        session()->set('auth_user', $authUser);
        session()->regenerate(true);
        session()->setFlashdata(
            'success',
            $payload['password_changed']
                ? 'Profil dan password berhasil diperbarui.'
                : 'Profil berhasil diperbarui.',
        );

        return redirect()->to(base_url('admin/profile'), 303);
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
