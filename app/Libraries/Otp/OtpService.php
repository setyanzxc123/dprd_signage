<?php

namespace App\Libraries\Otp;

use App\Libraries\Otp\Contracts\OtpDeliveryInterface;
use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use App\Libraries\Otp\Persistence\DatabaseOtpRepository;
use App\Libraries\Otp\ValueObjects\EmergencyOtpResult;
use App\Libraries\Otp\ValueObjects\OtpDeliveryResult;
use App\Libraries\Otp\ValueObjects\OtpRequestResult;
use App\Libraries\Otp\ValueObjects\OtpVerificationResult;
use Config\Otp;

final class OtpService
{
    /** @var \Closure(): int */
    private \Closure $clock;

    /** @var \Closure(int, int): int */
    private \Closure $randomInt;

    public function __construct(
        ?OtpRepositoryInterface $repository = null,
        ?OtpDeliveryInterface $delivery = null,
        ?Otp $config = null,
        ?callable $clock = null,
        ?callable $randomInt = null,
    ) {
        $this->repository = $repository ?? new DatabaseOtpRepository();
        $this->delivery = $delivery ?? new WhatsappOtpDelivery();
        $this->config = $config ?? new Otp();
        $this->clock = \Closure::fromCallable($clock ?? static fn (): int => time());
        $this->randomInt = \Closure::fromCallable($randomInt ?? static fn (int $min, int $max): int => random_int($min, $max));
    }

    private readonly OtpRepositoryInterface $repository;
    private readonly OtpDeliveryInterface $delivery;
    private readonly Otp $config;

    public function request(int $accountId, string $phone, string $ipAddress): OtpRequestResult
    {
        if ($this->shouldUseFazpass()) {
            return (new FazpassOtpService($this->repository, config: $this->config, clock: $this->clock))->request($accountId, $phone, $ipAddress);
        }

        $nowTs = ($this->clock)();
        $now = $this->date($nowTs);
        $phoneHash = $this->fingerprint($phone);
        $ipHash = $this->fingerprint($ipAddress);
        $this->repository->cleanup($this->date($nowTs - $this->config->cleanupRetentionSeconds));

        $allocation = $this->repository->transaction(function () use (
            $accountId,
            $phoneHash,
            $ipHash,
            $now,
            $nowTs,
        ): OtpRequestResult|array {
            $this->repository->lockAccount($accountId);
            $active = $this->repository->findActive($accountId, $now);
            if ($active !== null) {
                $activeExpiresAt = strtotime((string) $active['expires_at']);
                $retryAfter = max(1, strtotime((string) $active['resend_available_at']) - $nowTs);
                $status = (string) ($active['delivery_status'] ?? 'pending');
                $deliveryUncertain = in_array($status, ['created', 'ambiguous'], true);
                if ($deliveryUncertain) {
                    $retryAfter = max(1, $activeExpiresAt - $nowTs);
                }

                // Jangan menghasilkan kode kedua ketika status provider belum pasti.
                if ($deliveryUncertain || (in_array($status, ['pending', 'sent'], true)
                    && strtotime((string) $active['resend_available_at']) > $nowTs)) {
                    $this->audit((int) $active['id'], $accountId, 'request_blocked', $phoneHash, $ipHash, [
                        'reason' => $deliveryUncertain ? 'delivery_ambiguous' : 'active_otp',
                    ], $now);

                    return new OtpRequestResult(
                        false,
                        $deliveryUncertain ? 'delivery_ambiguous' : 'cooldown',
                        $retryAfter,
                        expiresAt: $activeExpiresAt,
                    );
                }

                if (strtotime((string) $active['resend_available_at']) > $nowTs) {
                    $this->audit((int) $active['id'], $accountId, 'request_blocked', $phoneHash, $ipHash, [
                        'reason' => 'cooldown',
                    ], $now);

                    return new OtpRequestResult(false, 'cooldown', $retryAfter, expiresAt: $activeExpiresAt);
                }
            }

            $since = $this->date($nowTs - $this->config->requestWindowSeconds);
            if ($this->repository->countRequests('phone_hash', $phoneHash, $since) >= $this->config->maxRequestsPerPhone
                || $this->repository->countRequests('ip_hash', $ipHash, $since) >= $this->config->maxRequestsPerIp) {
                $this->audit(null, $accountId, 'request_blocked', $phoneHash, $ipHash, ['reason' => 'rate_limited'], $now);

                return new OtpRequestResult(false, 'rate_limited', $this->config->requestWindowSeconds);
            }

            $this->repository->cancelActive($accountId, $now);
            $code = $this->generateCode();
            $expiresAt = $nowTs + $this->config->ttlSeconds;
            $otpId = $this->repository->create([
                'member_account_id'  => $accountId,
                'code_hash'          => password_hash($code, PASSWORD_DEFAULT),
                'phone_hash'         => $phoneHash,
                'ip_hash'            => $ipHash,
                'delivery_status'    => 'created',
                'verification_attempts' => 0,
                'max_attempts'       => $this->config->maxVerificationAttempts,
                'expires_at'         => $this->date($expiresAt),
                'resend_available_at'=> $this->date($nowTs + $this->config->resendCooldownSeconds),
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
            $this->audit($otpId, $accountId, 'requested', $phoneHash, $ipHash, [], $now);

            return compact('otpId', 'code', 'expiresAt');
        });

        if ($allocation instanceof OtpRequestResult) {
            return $allocation;
        }

        try {
            $delivery = $this->delivery->send($phone, $allocation['code'], $this->config->ttlSeconds);
        } catch (\Throwable) {
            $delivery = new OtpDeliveryResult('ambiguous', error: 'Provider tidak memberikan respons yang dapat dipastikan.');
        }

        $otpId = (int) $allocation['otpId'];
        $this->repository->update($otpId, [
            'delivery_status'    => $delivery->status,
            'provider'           => $delivery->provider,
            'provider_message_id'=> $delivery->messageId,
            'provider_request_id'=> $delivery->requestId,
            'updated_at'         => $now,
        ]);
        $this->audit($otpId, $accountId, $delivery->accepted() ? 'delivery_accepted' : ($delivery->ambiguous() ? 'delivery_ambiguous' : 'delivery_failed'), $phoneHash, $ipHash, [
            'provider'        => $delivery->provider,
            'provider_status' => $delivery->status,
            'reason'          => $delivery->error,
        ], $now);

        return new OtpRequestResult(
            $delivery->accepted(),
            $delivery->status,
            $this->config->resendCooldownSeconds,
            $delivery->error,
            (int) $allocation['expiresAt'],
        );
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
            $now = $this->date($nowTs);
            $expiresAt = $this->date($nowTs + $this->config->ttlSeconds);
            $code = $this->generateCode();
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

    public function verify(int $accountId, string $code, string $ipAddress, ?string $phone = null): OtpVerificationResult
    {
        if ($this->shouldUseFazpass() && $phone !== null) {
            return (new FazpassOtpService($this->repository, config: $this->config, clock: $this->clock))->verify($accountId, $code, $ipAddress, $phone);
        }

        return $this->repository->transaction(function () use ($accountId, $code, $ipAddress, $phone): OtpVerificationResult {
            $this->repository->lockAccount($accountId);
            $now = $this->date(($this->clock)());
            $ipHash = $this->fingerprint($ipAddress);
            $otp = $this->repository->findActive($accountId, $now);
            if ($otp === null) {
                $this->repository->audit(null, $accountId, 'verification_failed', ['ip_hash' => $ipHash, 'reason' => 'not_found_or_expired'], $now);

                return new OtpVerificationResult(false, 'invalid');
            }

            $otpId = (int) $otp['id'];
            if (($otp['source'] ?? 'whatsapp') !== 'emergency'
                && ($phone === null || ! hash_equals((string) $otp['phone_hash'], $this->fingerprint($phone)))) {
                $this->repository->audit($otpId, $accountId, 'verification_failed', [
                    'ip_hash' => $ipHash,
                    'reason'  => 'phone_changed',
                ], $now);

                return new OtpVerificationResult(false, 'invalid');
            }

            $attempts = (int) $otp['verification_attempts'];
            $maxAttempts = (int) $otp['max_attempts'];
            if ($attempts >= $maxAttempts) {
                return new OtpVerificationResult(false, 'too_many_attempts');
            }

            if (preg_match('/^\d{' . $this->config->length . '}$/', $code) !== 1
                || ! password_verify($code, (string) $otp['code_hash'])) {
                $attempts++;
                $changes = ['verification_attempts' => $attempts, 'updated_at' => $now];
                if ($attempts >= $maxAttempts) {
                    $changes['cancelled_at'] = $now;
                }
                $this->repository->update($otpId, $changes);
                $this->repository->audit($otpId, $accountId, 'verification_failed', [
                    'phone_hash' => $otp['phone_hash'],
                    'ip_hash'    => $ipHash,
                    'reason'     => $attempts >= $maxAttempts ? 'too_many_attempts' : 'invalid_code',
                ], $now);

                return new OtpVerificationResult(false, $attempts >= $maxAttempts ? 'too_many_attempts' : 'invalid');
            }

            if (! $this->repository->consume($otpId, $now)) {
                $this->repository->audit($otpId, $accountId, 'verification_failed', [
                    'phone_hash' => $otp['phone_hash'],
                    'ip_hash'    => $ipHash,
                    'reason'     => 'already_consumed',
                ], $now);

                return new OtpVerificationResult(false, 'invalid');
            }

            $this->repository->audit($otpId, $accountId, 'verification_succeeded', [
                'phone_hash' => $otp['phone_hash'],
                'ip_hash'    => $ipHash,
            ], $now);

            return new OtpVerificationResult(true, 'verified');
        });
    }

    /** @param array<string, mixed> $extra */
    private function audit(?int $otpId, int $accountId, string $event, string $phoneHash, string $ipHash, array $extra, string $now): void
    {
        $this->repository->audit($otpId, $accountId, $event, $extra + ['phone_hash' => $phoneHash, 'ip_hash' => $ipHash], $now);
    }

    private function fingerprint(string $value): string
    {
        return hash('sha256', trim(strtolower($value)));
    }

    private function generateCode(): string
    {
        return str_pad((string) ($this->randomInt)(0, (10 ** $this->config->length) - 1), $this->config->length, '0', STR_PAD_LEFT);
    }

    private function shouldUseFazpass(): bool
    {
        // Unit/integration callers may inject a delivery double to exercise
        // the internal lifecycle; only the default application delivery is
        // switched by the production provider flag.
        return $this->config->provider === 'fazpass'
            && $this->delivery instanceof WhatsappOtpDelivery;
    }

    private function date(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }
}
