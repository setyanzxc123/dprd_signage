<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Auth\AdminProfileService;
use CodeIgniter\Shield\Entities\User;

/**
 * Profil admin untuk aplikasi mobile: lihat data akun, perbarui nama,
 * dan ganti password — memakai AdminProfileService yang sama dengan
 * halaman web sehingga aturan validasinya identik.
 * Dilindungi filter apiadmin (bearer token + grup superadmin/operator).
 */
class AdminProfileController extends BaseController
{
    use ApiResponse;

    public function index()
    {
        $user = $this->currentUser();
        if ($user === null) {
            return $this->apiUnauthorized();
        }

        return $this->apiSuccess(['user' => $this->userPayload($user)]);
    }

    public function update()
    {
        $user = $this->currentUser();
        if ($user === null) {
            return $this->apiUnauthorized();
        }

        $service = new AdminProfileService();
        $payload = $service->validatedProfile($this->requestBodyArray(), $user);

        if (isset($payload['error'])) {
            return $this->apiError($payload['error'], 422);
        }

        if (! $service->persistProfile($user, $payload)) {
            return $this->apiError('Profil gagal diperbarui. Silakan coba kembali.', 500);
        }

        return $this->apiSuccess([
            'message' => $payload['password_changed']
                ? 'Profil dan password berhasil diperbarui.'
                : 'Profil berhasil diperbarui.',
            'user'    => $this->userPayload($user),
        ]);
    }

    private function currentUser(): ?User
    {
        return service('requestIdentity')->currentUser();
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id'       => (int) $user->id,
            'username' => (string) $user->username,
            'name'     => (string) $user->name,
            'groups'   => $user->getGroups() ?? [],
        ];
    }
}
