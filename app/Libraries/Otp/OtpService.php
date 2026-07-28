<?php

namespace App\Libraries\Otp;

use App\Libraries\Otp\Persistence\DatabaseOtpRepository;
use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use App\Libraries\Otp\Providers\FazpassProvider;
use App\Libraries\Otp\ValueObjects\EmergencyOtpResult;
use App\Libraries\Otp\ValueObjects\OtpRequestResult;
use App\Libraries\Otp\ValueObjects\OtpVerificationResult;
use Config\Otp;

/**
 * Application OTP facade. The member login lifecycle is owned by Fazpass;
 * emergency codes remain local and are created only by an authenticated admin.
 */
final class OtpService
{
    private readonly OtpRepositoryInterface $repository;
    private readonly FazpassOtpService $fazpass;
    private readonly Otp $config;

    public function __construct(
        ?OtpRepositoryInterface $repository = null,
        ?FazpassProvider $provider = null,
        ?Otp $config = null,
        ?callable $clock = null,
    ) {
        $this->repository = $repository ?? new DatabaseOtpRepository();
        $this->config = $config ?? new Otp();
        $this->fazpass = new FazpassOtpService($this->repository, $provider, $this->config, $clock);
        $this->clock = \Closure::fromCallable($clock ?? static fn (): int => time());
    }

    /** @var \Closure(): int */
    private \Closure $clock;

    public function request(int $accountId, string $phone, string $ipAddress): OtpRequestResult
    {
        return $this->fazpass->request($accountId, $phone, $ipAddress);
    }

    public function verify(int $accountId, string $code, string $ipAddress, ?string $phone = null): OtpVerificationResult
    {
        if ($phone === null) {
            return new OtpVerificationResult(false, 'invalid');
        }

        return $this->fazpass->verify($accountId, $code, $ipAddress, $phone);
    }

    public function createEmergency(int $accountId, int $adminId, string $reason): EmergencyOtpResult
    {
        $reason = trim($reason);
        if ($accountId < 1 || $adminId < 1 || mb_strlen($reason) < 5) {
            throw new \InvalidArgumentException('Akun, admin, dan alasan OTP darurat wajib valid.');
        }

        return $this->repository->transaction(function () use ($accountId, $adminId, $reason): EmergencyOtpResult {
            $this->repository->lockAccount($accountId);
            $nowTs = ($this->clock)();
            $now = date('Y-m-d H:i:s', $nowTs);
            $expiresAt = date('Y-m-d H:i:s', $nowTs + $this->config->ttlSeconds);
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $this->repository->cancelActive($accountId, $now);
            $otpId = $this->repository->create([
                'member_account_id'     => $accountId,
                'code_hash'             => password_hash($code, PASSWORD_DEFAULT),
                'phone_hash'            => hash('sha256', 'emergency-account:' . $accountId),
                'ip_hash'               => hash('sha256', 'emergency-admin:' . $adminId),
                'delivery_status'       => 'manual',
                'source'                => 'emergency',
                'created_by_admin_id'   => $adminId,
                'emergency_reason'      => mb_substr($reason, 0, 255),
                'verification_attempts' => 0,
                'max_attempts'          => $this->config->maxVerificationAttempts,
                'expires_at'            => $expiresAt,
                'resend_available_at'   => $expiresAt,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
            $this->repository->audit($otpId, $accountId, 'emergency_created', [
                'ip_hash' => hash('sha256', 'admin:' . $adminId),
                'reason'  => mb_substr($reason, 0, 255),
            ], $now);

            return new EmergencyOtpResult($otpId, $code, $expiresAt);
        });
    }
}
