<?php

namespace App\Libraries\Auth;

use App\Models\AnggotaModel;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

/**
 * Menautkan anggota DPRD dengan user CodeIgniter Shield. User anggota
 * dibuat lazily saat OTP pertama berhasil (login web maupun API mobile)
 * dan tidak memiliki identitas password: satu-satunya jalur masuk
 * anggota adalah OTP WhatsApp.
 */
class MemberAccountService
{
    public function ensureUserForAnggota(int $anggotaId): ?User
    {
        if ($anggotaId < 1) {
            return null;
        }

        $anggotaModel = new AnggotaModel();
        $anggota = $anggotaModel->find($anggotaId);

        if ($anggota === null) {
            return null;
        }

        $users = new UserModel();

        $linkedId = (int) ($anggota['user_id'] ?? 0);
        if ($linkedId > 0) {
            $user = $users->find($linkedId);
            if ($user instanceof User) {
                return $user;
            }
        }

        $username = 'anggota_' . $anggotaId;

        $user = new User([
            'username' => $username,
            'name'     => (string) $anggota['name'],
            'active'   => 1,
        ]);
        $users->save($user);

        $user = $users->where('username', $username)->first();
        if (! $user instanceof User) {
            return null;
        }

        if (! $user->inGroup('anggota')) {
            $user->addGroup('anggota');
        }

        $anggotaModel->update($anggotaId, ['user_id' => $user->id]);

        return $user;
    }
}
