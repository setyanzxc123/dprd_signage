<?php

namespace App\Libraries\Otp\Persistence;

use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use CodeIgniter\Database\BaseConnection;

final class DatabaseOtpRepository implements OtpRepositoryInterface
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

    public function lockAccount(int $accountId): void
    {
        $db = $this->db();
        $table = $db->prefixTable('member_accounts');
        $db->query("SELECT id FROM {$table} WHERE id = ? FOR UPDATE", [$accountId]);
    }

    public function cleanup(string $before): int
    {
        $builder = $this->db()->table('member_otps')
            ->where('expires_at <', $before)
            ->where('created_at <', $before);
        $count = $builder->countAllResults(false);
        $builder->delete();

        return $count;
    }

    public function findActive(int $accountId, string $now): ?array
    {
        return $this->db()->table('member_otps')
            ->where('member_account_id', $accountId)
            ->where('used_at', null)
            ->where('cancelled_at', null)
            ->where('expires_at >=', $now)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();
    }

    public function countRequests(string $field, string $value, string $since): int
    {
        if (! in_array($field, ['phone_hash', 'ip_hash'], true)) {
            throw new \InvalidArgumentException('Kolom pembatas OTP tidak valid.');
        }

        return $this->db()->table('member_otp_audits')
            ->where('event', 'requested')
            ->where($field, $value)
            ->where('created_at >=', $since)
            ->countAllResults();
    }

    public function countAccountRequests(int $accountId, string $since): int
    {
        return $this->db()->table('member_otp_audits')
            ->where('event', 'requested')
            ->where('member_account_id', $accountId)
            ->where('created_at >=', $since)
            ->countAllResults();
    }

    public function countGlobalRequests(string $since): int
    {
        return $this->db()->table('member_otp_audits')
            ->where('event', 'requested')
            ->where('created_at >=', $since)
            ->countAllResults();
    }

    public function cancelActive(int $accountId, string $now): void
    {
        $this->db()->table('member_otps')
            ->where('member_account_id', $accountId)
            ->where('used_at', null)
            ->where('cancelled_at', null)
            ->update(['cancelled_at' => $now, 'updated_at' => $now]);
    }

    public function create(array $data): int
    {
        $this->db()->table('member_otps')->insert($data);

        return (int) $this->db()->insertID();
    }

    public function update(int $id, array $changes): void
    {
        $this->db()->table('member_otps')->where('id', $id)->update($changes);
    }

    public function consume(int $id, string $now): bool
    {
        $db = $this->db();
        $db->table('member_otps')
            ->where('id', $id)
            ->where('used_at', null)
            ->where('cancelled_at', null)
            ->where('expires_at >=', $now)
            ->update(['used_at' => $now, 'updated_at' => $now]);

        return $db->affectedRows() === 1;
    }

    public function audit(?int $otpId, ?int $accountId, string $event, array $context, string $createdAt): void
    {
        $this->db()->table('member_otp_audits')->insert([
            'member_otp_id'     => $otpId,
            'member_account_id' => $accountId,
            'event'             => $event,
            'phone_hash'        => $context['phone_hash'] ?? null,
            'ip_hash'           => $context['ip_hash'] ?? null,
            'provider'          => $context['provider'] ?? null,
            'provider_status'   => $context['provider_status'] ?? null,
            'reason'            => $context['reason'] ?? null,
            'created_at'        => $createdAt,
        ]);
    }

    private function db(): BaseConnection
    {
        return $this->db ??= db_connect();
    }
}
