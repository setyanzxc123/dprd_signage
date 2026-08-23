<?php

namespace App\Libraries\Auth;

use App\Models\AnggotaModel;
use CodeIgniter\Shield\Entities\User;

/**
 * Resolver identitas permintaan berjalan: mendukung sesi web (cookie)
 * maupun bearer token Shield (aplikasi mobile). Dipakai bersama oleh
 * filter API dan controller agar kedua jalur menempuh resolusi yang
 * sama dan bentuk data anggota konsisten.
 */
class RequestIdentityService
{
    /** @var array<string, mixed>|null */
    private ?array $anggota = null;

    private ?User $user = null;
    private bool $resolved = false;

    /**
     * Akun anggota terkait permintaan: dari sesi web (member_auth)
     * atau dari user Shield yang ditautkan kolom anggota.user_id.
     *
     * @return array<string, mixed>|null
     */
    public function currentAnggota(): ?array
    {
        $this->resolveOnce();

        return $this->anggota;
    }

    public function currentAnggotaId(): int
    {
        return (int) ($this->currentAnggota()['anggota_id'] ?? 0);
    }

    /**
     * User Shield pemilik bearer token (admin maupun anggota).
     * Null bila permintaan tidak membawa token valid.
     */
    public function currentUser(): ?User
    {
        $this->resolveOnce();

        return $this->user;
    }

    private function resolveOnce(): void
    {
        if ($this->resolved) {
            return;
        }
        $this->resolved = true;

        $this->resolveSessionAnggota();

        if ($this->anggota === null) {
            $this->resolveToken();
        }
    }

    private function resolveSessionAnggota(): void
    {
        $auth = session()->get('member_auth');
        if (! is_array($auth)) {
            return;
        }

        $account = (new AnggotaModel())->findActiveSessionMember(
            (int) ($auth['anggota_id'] ?? 0),
        );

        if ($account === null) {
            session()->remove('member_auth');

            return;
        }

        $this->anggota = $account;
    }

    private function resolveToken(): void
    {
        $header = service('request')->getHeaderLine('Authorization');
        if ($header === '' || ! str_starts_with($header, 'Bearer')) {
            return;
        }

        $authenticator = auth('tokens')->getAuthenticator();
        $result = $authenticator->attempt(['token' => $header]);

        if (! $result->isOK()) {
            return;
        }

        $user = $authenticator->getUser();
        if (! $user instanceof User || ! $user->isActivated()) {
            return;
        }

        $this->user = $user;

        $anggota = (new AnggotaModel())
            ->select('id AS anggota_id, name, jabatan, fraksi, komisi, no_wa, aktif, last_login_at')
            ->where('user_id', $user->id)
            ->where('aktif', 1)
            ->first();

        $this->anggota = $anggota ?: null;
    }
}
