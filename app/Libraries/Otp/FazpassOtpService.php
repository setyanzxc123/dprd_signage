<?php

namespace App\Libraries\Otp;

use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use App\Libraries\Otp\Persistence\DatabaseOtpRepository;
use App\Libraries\Otp\Providers\FazpassProvider;
use App\Libraries\Otp\ValueObjects\OtpRequestResult;
use App\Libraries\Otp\ValueObjects\OtpVerificationResult;
use Config\Otp;

final class FazpassOtpService
{
    private readonly OtpRepositoryInterface $repository;
    private readonly FazpassProvider $provider;
    private readonly Otp $config;
    /** @var \Closure(): int */
    private \Closure $clock;

    public function __construct(
        ?OtpRepositoryInterface $repository = null,
        ?FazpassProvider $provider = null,
        ?Otp $config = null,
        ?callable $clock = null,
    ) {
        $this->repository = $repository ?? new DatabaseOtpRepository();
        $this->provider = $provider ?? new FazpassProvider();
        $this->config = $config ?? new Otp();
        $this->clock = \Closure::fromCallable($clock ?? static fn (): int => time());
    }

    public function request(int $anggotaId, string $phone): OtpRequestResult
    {
        $nowTs = ($this->clock)();
        $now = $this->date($nowTs);
        $this->repository->cleanup($this->date($nowTs - $this->config->cleanupRetentionSeconds));

        $allocation = $this->repository->transaction(function () use ($anggotaId, $now, $nowTs): OtpRequestResult|array {
            $this->repository->lockAccount($anggotaId);
            $active = $this->repository->findActive($anggotaId, $now);
            if ($active !== null) {
                $expiresAt = strtotime((string) $active['expires_at']);
                $createdTs = strtotime((string) $active['created_at']);
                $resendAvailableAt = $createdTs + $this->config->resendCooldownSeconds;
                $retryAfter = max(1, $resendAvailableAt - $nowTs);
                if ($resendAvailableAt > $nowTs) {
                    return new OtpRequestResult(false, 'cooldown', $retryAfter, expiresAt: $expiresAt);
                }
            }

            $dailySince = $this->date($nowTs - $this->config->dailyWindowSeconds);
            if ($this->repository->countAccountRequests($anggotaId, $dailySince) >= $this->config->maxRequestsPerAccountPerDay) {
                return new OtpRequestResult(false, 'rate_limited', $this->config->dailyWindowSeconds);
            }

            $globalSince = $this->date($nowTs - $this->config->globalWindowSeconds);
            $hourlyGlobalLimitReached = $this->repository->countGlobalRequests($globalSince) >= $this->config->maxRequestsGlobal;
            $dailyGlobalLimitReached = $this->repository->countGlobalRequests($dailySince) >= $this->config->maxRequestsGlobalPerDay;
            if ($hourlyGlobalLimitReached || $dailyGlobalLimitReached) {
                $window = $hourlyGlobalLimitReached
                    ? $this->config->globalWindowSeconds
                    : $this->config->dailyWindowSeconds;
                log_message('warning', 'Circuit breaker OTP global aktif untuk window {window} detik.', [
                    'window' => $window,
                ]);

                return new OtpRequestResult(false, 'rate_limited', $window);
            }

            $this->repository->cancelActive($anggotaId, $now);
            $expiresAt = $nowTs + $this->config->ttlSeconds;
            $otpId = $this->repository->create([
                'anggota_id' => $anggotaId,
                'code_hash'  => null,
                'provider'   => 'fazpass',
                'status'     => OtpStatus::CREATED,
                'attempts'   => 0,
                'expires_at' => $this->date($expiresAt),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return compact('otpId', 'expiresAt');
        });

        if ($allocation instanceof OtpRequestResult) {
            return $allocation;
        }

        $result = $this->provider->request($phone);
        $otpId = (int) $allocation['otpId'];
        $status = $result->success ? OtpStatus::PENDING : OtpStatus::FAILED;
        $stored = $this->repository->transitionStatus($otpId, [OtpStatus::CREATED], $status, [
            'provider_otp_id'         => $result->otpId,
            'provider_transaction_id' => $result->transactionId,
            'updated_at'              => $now,
        ]);
        if (! $stored) {
            log_message('critical', 'Referensi OTP Fazpass gagal disimpan untuk otp_id lokal {otpId}.', [
                'otpId' => $otpId,
            ]);

            return new OtpRequestResult(
                false,
                OtpStatus::FAILED,
                $this->config->resendCooldownSeconds,
                'Referensi OTP tidak dapat disimpan.',
                (int) $allocation['expiresAt'],
            );
        }

        return new OtpRequestResult($result->success, $status, $this->config->resendCooldownSeconds, $result->error, (int) $allocation['expiresAt']);
    }

    public function verify(int $anggotaId, string $code): OtpVerificationResult
    {
        return $this->repository->transaction(function () use ($anggotaId, $code): OtpVerificationResult {
            $this->repository->lockAccount($anggotaId);
            $now = $this->date(($this->clock)());
            $otp = $this->repository->findActive($anggotaId, $now);

            if ($otp === null || ($otp['provider'] ?? null) !== 'fazpass') {
                return new OtpVerificationResult(false, 'invalid');
            }
            if (! in_array((string) ($otp['status'] ?? ''), OtpStatus::VERIFIABLE, true)) {
                return new OtpVerificationResult(false, 'invalid');
            }

            $attempts = (int) $otp['attempts'];
            if ($attempts >= $this->config->maxVerificationAttempts) {
                return new OtpVerificationResult(false, 'too_many_attempts');
            }

            $validFormat = preg_match('/^\d{' . $this->config->length . '}$/', $code) === 1;
            $providerOtpId = trim((string) ($otp['provider_otp_id'] ?? ''));
            $result = $validFormat && $providerOtpId !== ''
                ? $this->provider->verify($providerOtpId, $code)
                : null;
            if ($result?->success !== true) {
                return $this->recordFailure($otp, $attempts, $now);
            }
            if (! $this->repository->consume((int) $otp['id'], $now)) {
                return new OtpVerificationResult(false, 'invalid');
            }

            return new OtpVerificationResult(true, 'verified');
        });
    }

    /** @param array<string, mixed> $otp */
    private function recordFailure(array $otp, int $attempts, string $now): OtpVerificationResult
    {
        $attempts++;
        $changes = ['attempts' => $attempts, 'updated_at' => $now];
        if ($attempts >= $this->config->maxVerificationAttempts) {
            return $this->repository->transitionStatus(
                (int) $otp['id'],
                [(string) $otp['status']],
                OtpStatus::CANCELLED,
                $changes,
            )
                ? new OtpVerificationResult(false, 'too_many_attempts')
                : new OtpVerificationResult(false, 'invalid');
        }

        $this->repository->update((int) $otp['id'], $changes);

        return new OtpVerificationResult(false, 'invalid');
    }

    private function date(int $timestamp): string { return date('Y-m-d H:i:s', $timestamp); }
}
