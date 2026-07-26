<?php

namespace App\Models;

use CodeIgniter\Model;

class MemberAccountModel extends Model
{
    protected $table = 'member_accounts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'anggota_id',
        'password_hash',
        'login_enabled',
        'last_login_at',
    ];

    public function findByAnggotaId(int $anggotaId): ?array
    {
        return $this->where('anggota_id', $anggotaId)->first();
    }

    public function findLoginByPhone(string $localPhone): ?array
    {
        $accounts = $this
            ->select('
                member_accounts.id AS account_id,
                member_accounts.anggota_id,
                member_accounts.password_hash,
                member_accounts.login_enabled,
                a.name,
                a.jabatan,
                a.fraksi,
                a.komisi,
                a.no_wa,
                a.aktif
            ')
            ->join('anggota a', 'a.id = member_accounts.anggota_id')
            ->where('a.no_wa', $localPhone)
            ->findAll(2);

        // Nomor harus mengidentifikasi tepat satu anggota.
        return count($accounts) === 1 ? $accounts[0] : null;
    }

    public function findActiveSessionAccount(int $accountId, int $anggotaId): ?array
    {
        return $this
            ->select('
                member_accounts.id AS account_id,
                member_accounts.anggota_id,
                member_accounts.login_enabled,
                a.name,
                a.jabatan,
                a.fraksi,
                a.komisi,
                a.no_wa,
                a.aktif
            ')
            ->join('anggota a', 'a.id = member_accounts.anggota_id')
            ->where('member_accounts.id', $accountId)
            ->where('member_accounts.anggota_id', $anggotaId)
            ->where('member_accounts.login_enabled', 1)
            ->where('a.aktif', 1)
            ->first();
    }
}
