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

    public function request(int $accountId, string $phone, string $ipAddress): OtpRequestResult
    {
        $nowTs = ($this->clock)();
        $now = $this->date($nowTs);
        $phoneHash = $this->fingerprint($phone);
        $ipHash = $this->fingerprint($ipAddress);
        $this->repository->cleanup($this->date($nowTs - $this->config->cleanupRetentionSeconds));

        $allocation = $this->repository->transaction(function () use ($accountId, $phoneHash, $ipHash, $now, $nowTs): OtpRequestResult|array {
            $this->repository->lockAccount($accountId);
            $active = $this->repository->findActive($accountId, $now);
            if ($active !== null) {
                $expiresAt = strtotime((string) $active['expires_at']);
                $retryAfter = max(1, strtotime((string) $active['resend_available_at']) - $nowTs);
                if (in_array((string) ($active['delivery_status'] ?? ''), ['created', 'pending'], true)
                    || strtotime((string) $active['resend_available_at']) > $nowTs) {
                    return new OtpRequestResult(false, 'cooldown', $retryAfter, expiresAt: $expiresAt);
                }
            }

            $since = $this->date($nowTs - $this->config->requestWindowSeconds);
            if ($this->repository->countRequests('phone_hash', $phoneHash, $since) >= $this->config->maxRequestsPerPhone
                || $this->repository->countRequests('ip_hash', $ipHash, $since) >= $this->config->maxRequestsPerIp) {
                return new OtpRequestResult(false, 'rate_limited', $this->config->requestWindowSeconds);
            }

            $this->repository->cancelActive($accountId, $now);
            $expiresAt = $nowTs + $this->config->ttlSeconds;
            $otpId = $this->repository->create([
                'member_account_id'       => $accountId,
                'code_hash'               => null,
                'phone_hash'              => $phoneHash,
                'ip_hash'                 => $ipHash,
                'delivery_status'         => 'created',
                'provider'                => 'fazpass',
                'verification_attempts'   => 0,
                'max_attempts'            => $this->config->maxVerificationAttempts,
                'expires_at'              => $this->date($expiresAt),
                'resend_available_at'     => $this->date($nowTs + $this->config->resendCooldownSeconds),
                'created_at'              => $now,
                'updated_at'              => $now,
            ]);
            $this->repository->audit($otpId, $accountId, 'requested', [
                'phone_hash' => $phoneHash,
                'ip_hash'    => $ipHash,
                'provider'   => 'fazpass',
            ], $now);

            return compact('otpId', 'expiresAt');
        });

        if ($allocation instanceof OtpRequestResult) {
            return $allocation;
        }

        $result = $this->provider->request($phone);
        $otpId = (int) $allocation['otpId'];
        $status = $result->success ? 'pending' : 'failed';
        $this->repository->update($otpId, [
            'delivery_status'       => $status,
            'provider_otp_id'       => $result->otpId,
            'provider_transaction_id' => $result->transactionId,
            'updated_at'            => $now,
        ]);
        $this->repository->audit($otpId, $accountId, $result->success ? 'delivery_accepted' : 'delivery_failed', [
            'phone_hash'     => $phoneHash,
            'ip_hash'        => $ipHash,
            'provider'       => 'fazpass',
            'provider_status'=> $result->status,
            'reason'         => $result->error,
        ], $now);

        return new OtpRequestResult($result->success, $status, $this->config->resendCooldownSeconds, $result->error, (int) $allocation['expiresAt']);
    }

    public function verify(int $accountId, string $code, string $ipAddress, string $phone): OtpVerificationResult
    {
        return $this->repository->transaction(function () use ($accountId, $code, $ipAddress, $phone): OtpVerificationResult {
            $this->repository->lockAccount($accountId);
            $now = $this->date(($this->clock)());
            $otp = $this->repository->findActive($accountId, $now);
            $ipHash = $this->fingerprint($ipAddress);
            if ($otp === null || ($otp['provider'] ?? null) !== 'fazpass' || ! hash_equals((string) $otp['phone_hash'], $this->fingerprint($phone))) {
                return new OtpVerificationResult(false, 'invalid');
            }
            $attempts = (int) $otp['verification_attempts'];
            if ($attempts >= (int) $otp['max_attempts'] || ! preg_match('/^\d{' . $this->config->length . '}$/', $code)) {
                return new OtpVerificationResult(false, 'too_many_attempts');
            }
            $providerOtpId = trim((string) ($otp['provider_otp_id'] ?? ''));
            $result = $providerOtpId !== '' ? $this->provider->verify($providerOtpId, $code) : null;
            if ($result?->success !== true) {
                $attempts++;
                $changes = ['verification_attempts' => $attempts, 'updated_at' => $now];
                if ($attempts >= (int) $otp['max_attempts']) {
                    $changes['cancelled_at'] = $now;
                }
                $this->repository->update((int) $otp['id'], $changes);
                $this->repository->audit((int) $otp['id'], $accountId, 'verification_failed', [
                    'phone_hash' => $otp['phone_hash'], 'ip_hash' => $ipHash, 'provider' => 'fazpass',
                    'provider_status' => $result?->status ?? 'missing_transaction', 'reason' => $result?->error ?? 'invalid_code',
                ], $now);

                return new OtpVerificationResult(false, $attempts >= (int) $otp['max_attempts'] ? 'too_many_attempts' : 'invalid');
            }
            if (! $this->repository->consume((int) $otp['id'], $now)) {
                return new OtpVerificationResult(false, 'invalid');
            }
            $this->repository->audit((int) $otp['id'], $accountId, 'verification_succeeded', [
                'phone_hash' => $otp['phone_hash'], 'ip_hash' => $ipHash, 'provider' => 'fazpass',
            ], $now);

            return new OtpVerificationResult(true, 'verified');
        });
    }

    private function fingerprint(string $value): string { return hash('sha256', trim(strtolower($value))); }
    private function date(int $timestamp): string { return date('Y-m-d H:i:s', $timestamp); }
}
