<?php

namespace App\Models;

use CodeIgniter\Model;

class AnggotaModel extends Model
{
    protected $table         = 'anggota';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'name', 'jabatan', 'fraksi', 'komisi', 'no_wa', 'aktif', 'foto', 'last_login_at',
    ];
    protected $useTimestamps = false;
    protected $returnType    = 'array';

    /** @return array<string, mixed>|null */
    public function findLoginByPhone(string $localPhone): ?array
    {
        $members = $this
            ->select('id AS anggota_id, name, jabatan, fraksi, komisi, no_wa, aktif, last_login_at')
            ->where('no_wa', $localPhone)
            ->where('aktif', 1)
            ->findAll(2);

        return count($members) === 1 ? $members[0] : null;
    }

    /** @return array<string, mixed>|null */
    public function findActiveSessionMember(int $anggotaId): ?array
    {
        if ($anggotaId < 1) {
            return null;
        }

        return $this
            ->select('id AS anggota_id, name, jabatan, fraksi, komisi, no_wa, aktif, last_login_at')
            ->where('id', $anggotaId)
            ->where('aktif', 1)
            ->first();
    }
}
