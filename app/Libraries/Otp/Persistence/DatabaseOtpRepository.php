<?php

namespace App\Libraries\Otp\Persistence;

use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use App\Libraries\Otp\Contracts\OtpWebhookRepositoryInterface;
use App\Libraries\Otp\OtpStatus;
use CodeIgniter\Database\BaseConnection;

final class DatabaseOtpRepository implements OtpRepositoryInterface, OtpWebhookRepositoryInterface
{
    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db;
    }

    private ?BaseConnection $db;

    public function transaction(callable $callback): mixed
    {
        $db = $this->db();
        if (! $db->transBegin()) {
            throw new \RuntimeException('Transaksi OTP tidak dapat dimulai.');
        }

        try {
            $result = $callback();
            if (! $db->transCommit()) {
                throw new \RuntimeException('Transaksi OTP gagal disimpan.');
            }

            return $result;
        } catch (\Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }

    public function lockAccount(int $anggotaId): void
    {
        $db = $this->db();
        $table = $db->prefixTable('anggota');
        $db->query("SELECT id FROM {$table} WHERE id = ? FOR UPDATE", [$anggotaId]);
    }

    public function cleanup(string $before): int
    {
        $builder = $this->db()->table('member_otps')
            ->where('expires_at <', $before);
        $count = $builder->countAllResults(false);
        $builder->delete();

        return $count;
    }

    public function findActive(int $anggotaId, string $now): ?array
    {
        return $this->db()->table('member_otps')
            ->where('anggota_id', $anggotaId)
            ->where('used_at', null)
            ->whereIn('status', OtpStatus::ACTIVE)
            ->where('expires_at >=', $now)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();
    }

    public function countAccountRequests(int $anggotaId, string $since): int
    {
        return $this->db()->table('member_otps')
            ->where('anggota_id', $anggotaId)
            ->where('provider', 'fazpass')
            ->where('created_at >=', $since)
            ->countAllResults();
    }

    public function countGlobalRequests(string $since): int
    {
        return $this->db()->table('member_otps')
            ->where('provider', 'fazpass')
            ->where('created_at >=', $since)
            ->countAllResults();
    }

    public function findByProviderIdentifiers(
        string $provider,
        ?string $providerOtpId,
        ?string $providerTransactionId,
    ): ?array {
        if ($providerOtpId === null && $providerTransactionId === null) {
            throw new \InvalidArgumentException('Minimal satu identifier provider wajib tersedia.');
        }

        $builder = $this->db()->table('member_otps')
            ->select('id, status')
            ->where('provider', $provider);
        if ($providerOtpId !== null) {
            $builder->where('provider_otp_id', $providerOtpId);
        }
        if ($providerTransactionId !== null) {
            $builder->where('provider_transaction_id', $providerTransactionId);
        }

        return $builder->get(1)->getRowArray();
    }

    public function cancelActive(int $anggotaId, string $now): void
    {
        $this->db()->table('member_otps')
            ->where('anggota_id', $anggotaId)
            ->where('used_at', null)
            ->whereIn('status', OtpStatus::ACTIVE)
            ->update(['status' => OtpStatus::CANCELLED, 'updated_at' => $now]);
    }

    public function create(array $data): int
    {
        $this->db()->table('member_otps')->insert($data);

        return (int) $this->db()->insertID();
    }

    public function update(int $id, array $changes): bool
    {
        return $this->db()->table('member_otps')->where('id', $id)->update($changes);
    }

    public function transitionStatus(int $id, array $fromStatuses, string $toStatus, array $changes = []): bool
    {
        if ($fromStatuses === [] || ! OtpStatus::isKnown($toStatus)) {
            throw new \InvalidArgumentException('Transisi status OTP tidak valid.');
        }
        foreach ($fromStatuses as $fromStatus) {
            if (! OtpStatus::canTransition($fromStatus, $toStatus)) {
                throw new \InvalidArgumentException("Transisi status OTP {$fromStatus} -> {$toStatus} tidak valid.");
            }
        }

        $db = $this->db();
        $db->table('member_otps')
            ->where('id', $id)
            ->whereIn('status', $fromStatuses)
            ->update(['status' => $toStatus] + $changes);

        return $db->affectedRows() === 1;
    }

    public function consume(int $id, string $now): bool
    {
        $db = $this->db();
        $db->table('member_otps')
            ->where('id', $id)
            ->where('used_at', null)
            ->whereIn('status', OtpStatus::VERIFIABLE)
            ->where('expires_at >=', $now)
            ->update(['used_at' => $now, 'status' => OtpStatus::VERIFIED, 'updated_at' => $now]);

        return $db->affectedRows() === 1;
    }

    private function db(): BaseConnection
    {
        return $this->db ??= db_connect();
    }
}
