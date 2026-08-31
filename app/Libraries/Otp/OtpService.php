<?php

namespace App\Libraries\Otp;

use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use App\Libraries\Otp\Persistence\DatabaseOtpRepository;
use App\Libraries\Otp\Providers\BaileysProvider;
use App\Libraries\Otp\Providers\FazpassProvider;
use App\Libraries\Otp\ValueObjects\EmergencyOtpResult;
use App\Libraries\Otp\ValueObjects\OtpRequestResult;
use App\Libraries\Otp\ValueObjects\OtpVerificationResult;
use Config\Otp;

final class OtpService
{
    private readonly OtpRepositoryInterface $repository;
    private readonly BaileysProvider $baileysProvider;
    private readonly FazpassProvider $fazpassProvider;
    private readonly Otp $config;

    /** @var \Closure(): int */
    private \Closure $clock;

    public function __construct(
        ?OtpRepositoryInterface $repository = null,
        BaileysProvider|FazpassProvider|null $baileysOrFazpass = null,
        FazpassProvider|Otp|null $fazpassOrConfig = null,
        Otp|callable|null $configOrClock = null,
        ?callable $clock = null,
    ) {
        $this->repository = $repository ?? new DatabaseOtpRepository();

        $resolvedBaileys = null;
        $resolvedFazpass = null;
        $resolvedConfig = null;
        $resolvedClock = null;

        if ($baileysOrFazpass instanceof BaileysProvider) {
            $resolvedBaileys = $baileysOrFazpass;
            if ($fazpassOrConfig instanceof FazpassProvider) {
                $resolvedFazpass = $fazpassOrConfig;
            } elseif ($fazpassOrConfig instanceof Otp) {
                $resolvedConfig = $fazpassOrConfig;
            }
            if ($configOrClock instanceof Otp) {
                $resolvedConfig = $configOrClock;
            } elseif (is_callable($configOrClock)) {
                $resolvedClock = $configOrClock;
            }
        } elseif ($baileysOrFazpass instanceof FazpassProvider) {
            $resolvedFazpass = $baileysOrFazpass;
            if ($fazpassOrConfig instanceof Otp) {
                $resolvedConfig = $fazpassOrConfig;
            } elseif (is_callable($fazpassOrConfig)) {
                $resolvedClock = $fazpassOrConfig;
            }
            if (is_callable($configOrClock)) {
                $resolvedClock = $configOrClock;
            } elseif ($configOrClock instanceof Otp) {
                $resolvedConfig = $configOrClock;
            }
        } else {
            if ($fazpassOrConfig instanceof FazpassProvider) {
                $resolvedFazpass = $fazpassOrConfig;
            } elseif ($fazpassOrConfig instanceof Otp) {
                $resolvedConfig = $fazpassOrConfig;
            }
            if ($configOrClock instanceof Otp) {
                $resolvedConfig = $configOrClock;
            } elseif (is_callable($configOrClock)) {
                $resolvedClock = $configOrClock;
            }
        }

        if ($clock !== null) {
            $resolvedClock = $clock;
        }

        $this->config = $resolvedConfig ?? new Otp();
        $this->baileysProvider = $resolvedBaileys ?? new BaileysProvider(config: $this->config);
        $this->fazpassProvider = $resolvedFazpass ?? new FazpassProvider(config: $this->config);
        $this->clock = \Closure::fromCallable($resolvedClock ?? static fn (): int => time());
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
            $code = str_pad((string) random_int(0, 999999), $this->config->length, '0', STR_PAD_LEFT);
            $otpId = $this->repository->create([
                'anggota_id' => $anggotaId,
                'code_hash'  => password_hash($code, PASSWORD_DEFAULT),
                'provider'   => $this->config->provider,
                'status'     => OtpStatus::CREATED,
                'attempts'   => 0,
                'expires_at' => $this->date($expiresAt),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return compact('otpId', 'expiresAt', 'code');
        });

        if ($allocation instanceof OtpRequestResult) {
            return $allocation;
        }

        $otpId = (int) $allocation['otpId'];
        $expiresAt = (int) $allocation['expiresAt'];
        $code = (string) $allocation['code'];

        return $this->dispatchOtp($otpId, $anggotaId, $phone, $code, $expiresAt, $now);
    }

    public function verify(int $anggotaId, string $code): OtpVerificationResult
    {
        return $this->repository->transaction(function () use ($anggotaId, $code): OtpVerificationResult {
            $this->repository->lockAccount($anggotaId);
            $now = $this->date(($this->clock)());
            $otp = $this->repository->findActive($anggotaId, $now);

            if ($otp === null || ! in_array((string) ($otp['status'] ?? ''), OtpStatus::VERIFIABLE, true)) {
                return new OtpVerificationResult(false, 'invalid');
            }

            $attempts = (int) $otp['attempts'];
            if ($attempts >= $this->config->maxVerificationAttempts) {
                return new OtpVerificationResult(false, 'too_many_attempts');
            }

            $validFormat = preg_match('/^\d{' . $this->config->length . '}$/', $code) === 1;
            $provider = (string) ($otp['provider'] ?? '');

            if ($provider === 'fazpass') {
                $providerOtpId = trim((string) ($otp['provider_otp_id'] ?? ''));
                $result = $validFormat && $providerOtpId !== ''
                    ? $this->fazpassProvider->verify($providerOtpId, $code)
                    : null;

                if ($result?->success !== true) {
                    return $this->recordVerificationFailure($otp, $attempts, $now);
                }
            } else {
                $codeHash = (string) ($otp['code_hash'] ?? '');
                if (! $validFormat || $codeHash === '' || ! password_verify($code, $codeHash)) {
                    return $this->recordVerificationFailure($otp, $attempts, $now);
                }
            }

            if (! $this->repository->consume((int) $otp['id'], $now)) {
                return new OtpVerificationResult(false, 'invalid');
            }

            return new OtpVerificationResult(true, 'verified');
        });
    }

    public function createEmergency(int $anggotaId, int $adminId): EmergencyOtpResult
    {
        if ($anggotaId < 1 || $adminId < 1) {
            throw new \InvalidArgumentException('Akun dan admin pembuat OTP darurat wajib valid.');
        }

        return $this->repository->transaction(function () use ($anggotaId, $adminId): EmergencyOtpResult {
            $this->repository->lockAccount($anggotaId);
            $nowTs = ($this->clock)();
            $now = $this->date($nowTs);
            $expiresAt = $this->date($nowTs + $this->config->ttlSeconds);
            $code = str_pad((string) random_int(0, 999999), $this->config->length, '0', STR_PAD_LEFT);
            $this->repository->cancelActive($anggotaId, $now);
            $otpId = $this->repository->create([
                'anggota_id'          => $anggotaId,
                'code_hash'           => password_hash($code, PASSWORD_DEFAULT),
                'provider'            => 'emergency',
                'status'              => OtpStatus::MANUAL,
                'attempts'            => 0,
                'expires_at'          => $expiresAt,
                'created_by_admin_id' => $adminId,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);

            return new EmergencyOtpResult($otpId, $code, $expiresAt);
        });
    }

    private function dispatchOtp(
        int $otpId,
        int $anggotaId,
        string $phone,
        string $code,
        int $expiresAt,
        string $now,
    ): OtpRequestResult {
        $providerMode = $this->config->provider;

        if ($providerMode === 'internal') {
            $this->repository->transitionStatus($otpId, [OtpStatus::CREATED], OtpStatus::PENDING, [
                'provider'   => 'internal',
                'updated_at' => $now,
            ]);

            return new OtpRequestResult(true, OtpStatus::PENDING, $this->config->resendCooldownSeconds, expiresAt: $expiresAt);
        }

        if ($providerMode === 'fazpass') {
            return $this->sendViaFazpass($otpId, $phone, $expiresAt, $now);
        }

        $baileysResult = $this->baileysProvider->sendOtp($phone, $code);
        if ($baileysResult->success) {
            $this->repository->transitionStatus($otpId, [OtpStatus::CREATED], OtpStatus::PENDING, [
                'provider'                => 'baileys',
                'provider_transaction_id' => $baileysResult->messageId,
                'updated_at'              => $now,
            ]);

            return new OtpRequestResult(true, OtpStatus::PENDING, $this->config->resendCooldownSeconds, expiresAt: $expiresAt);
        }

        log_message('warning', 'Pengiriman OTP via Baileys gagal untuk anggota {id}: {error}', [
            'id'    => $anggotaId,
            'error' => $baileysResult->error ?? 'unknown error',
        ]);

        $canFallback = $providerMode === 'hybrid'
            && $this->config->fazpassFallbackEnabled
            && $this->fazpassProvider->isConfigured();

        if ($canFallback) {
            log_message('notice', 'Mengalihkan pengiriman OTP ke Fazpass Fallback untuk anggota {id}.', [
                'id' => $anggotaId,
            ]);

            return $this->sendViaFazpass($otpId, $phone, $expiresAt, $now);
        }

        $this->repository->transitionStatus($otpId, [OtpStatus::CREATED], OtpStatus::FAILED, [
            'provider'   => 'baileys',
            'updated_at' => $now,
        ]);

        return new OtpRequestResult(
            false,
            OtpStatus::FAILED,
            $this->config->resendCooldownSeconds,
            $baileysResult->error ?? 'Pengiriman OTP gagal.',
            $expiresAt,
        );
    }

    private function sendViaFazpass(
        int $otpId,
        string $phone,
        int $expiresAt,
        string $now,
    ): OtpRequestResult {
        $result = $this->fazpassProvider->request($phone);
        if ($result->success) {
            $this->repository->transitionStatus($otpId, [OtpStatus::CREATED], OtpStatus::PENDING, [
                'provider'                => 'fazpass',
                'provider_otp_id'         => $result->otpId,
                'provider_transaction_id' => $result->transactionId,
                'code_hash'               => null,
                'updated_at'              => $now,
            ]);

            return new OtpRequestResult(true, OtpStatus::PENDING, $this->config->resendCooldownSeconds, expiresAt: $expiresAt);
        }

        $this->repository->transitionStatus($otpId, [OtpStatus::CREATED], OtpStatus::FAILED, [
            'provider'   => 'fazpass',
            'code_hash'  => null,
            'updated_at' => $now,
        ]);

        return new OtpRequestResult(
            false,
            OtpStatus::FAILED,
            $this->config->resendCooldownSeconds,
            $result->error ?? 'Pengiriman OTP via Fazpass gagal.',
            $expiresAt,
        );
    }

    /** @param array<string, mixed> $otp */
    private function recordVerificationFailure(array $otp, int $attempts, string $now): OtpVerificationResult
    {
        $attempts++;
        $changes = ['attempts' => $attempts, 'updated_at' => $now];
        if ($attempts >= $this->config->maxVerificationAttempts) {
            $this->repository->transitionStatus(
                (int) $otp['id'],
                [(string) $otp['status']],
                OtpStatus::CANCELLED,
                $changes,
            );

            return new OtpVerificationResult(false, 'too_many_attempts');
        }

        $this->repository->update((int) $otp['id'], $changes);

        return new OtpVerificationResult(false, 'invalid');
    }

    private function date(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }
}

