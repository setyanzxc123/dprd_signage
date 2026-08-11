<?php

namespace App\Libraries\Otp;

use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use App\Libraries\Otp\Persistence\DatabaseOtpRepository;
use App\Libraries\Otp\Providers\FazpassProvider;
use App\Libraries\Otp\ValueObjects\EmergencyOtpResult;
use App\Libraries\Otp\ValueObjects\OtpRequestResult;
use App\Libraries\Otp\ValueObjects\OtpVerificationResult;
use Config\Otp;

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

    public function request(int $anggotaId, string $phone): OtpRequestResult
    {
        return $this->fazpass->request($anggotaId, $phone);
    }

    public function verify(int $anggotaId, string $code): OtpVerificationResult
    {
        $now = $this->date(($this->clock)());
        $otp = $this->repository->findActive($anggotaId, $now);
        if (($otp['provider'] ?? null) === 'emergency') {
            return $this->verifyEmergency($anggotaId, $code);
        }

        return $this->fazpass->verify($anggotaId, $code);
    }

    public function createEmergency(int $anggotaId, int $adminId): EmergencyOtpResult
    {
        if ($anggotaId < 1 || $adminId < 1) {
            throw new \InvalidArgumentException('Akun dan admin pembuat OTP darurat wajib valid.');
        }

        return $this->repository->transaction(function () use ($anggotaId, $adminId): EmergencyOtpResult {
            $this->repository->lockAccount($anggotaId);
            $nowTs = ($this->clock)();
            $now = date('Y-m-d H:i:s', $nowTs);
            $expiresAt = date('Y-m-d H:i:s', $nowTs + $this->config->ttlSeconds);
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $this->repository->cancelActive($anggotaId, $now);
            $otpId = $this->repository->create([
                'anggota_id'         => $anggotaId,
                'code_hash'          => password_hash($code, PASSWORD_DEFAULT),
                'provider'           => 'emergency',
                'status'             => OtpStatus::MANUAL,
                'attempts'           => 0,
                'expires_at'         => $expiresAt,
                'created_by_admin_id' => $adminId,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            return new EmergencyOtpResult($otpId, $code, $expiresAt);
        });
    }

    private function verifyEmergency(int $anggotaId, string $code): OtpVerificationResult
    {
        return $this->repository->transaction(function () use ($anggotaId, $code): OtpVerificationResult {
            $this->repository->lockAccount($anggotaId);
            $now = $this->date(($this->clock)());
            $otp = $this->repository->findActive($anggotaId, $now);
            if ($otp === null || ($otp['provider'] ?? null) !== 'emergency') {
                return new OtpVerificationResult(false, 'invalid');
            }

            $attempts = (int) $otp['attempts'];
            if ($attempts >= $this->config->maxVerificationAttempts) {
                return new OtpVerificationResult(false, 'too_many_attempts');
            }

            $validFormat = preg_match('/^\d{' . $this->config->length . '}$/', $code) === 1;
            $codeHash = (string) ($otp['code_hash'] ?? '');
            if (! $validFormat || $codeHash === '' || ! password_verify($code, $codeHash)) {
                $attempts++;
                $changes = ['attempts' => $attempts, 'updated_at' => $now];
                if ($attempts >= $this->config->maxVerificationAttempts) {
                    $this->repository->transitionStatus(
                        (int) $otp['id'],
                        [(string) $otp['status']],
                        OtpStatus::CANCELLED,
                        $changes,
                    );
                } else {
                    $this->repository->update((int) $otp['id'], $changes);
                }

                return new OtpVerificationResult(
                    false,
                    $attempts >= $this->config->maxVerificationAttempts ? 'too_many_attempts' : 'invalid',
                );
            }

            if (! $this->repository->consume((int) $otp['id'], $now)) {
                return new OtpVerificationResult(false, 'invalid');
            }

            return new OtpVerificationResult(true, 'verified');
        });
    }

    private function date(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }
}
