<?php

namespace App\Libraries\Otp;

use App\Libraries\Otp\Contracts\OtpDeliveryInterface;
use App\Libraries\Otp\Contracts\OtpRepositoryInterface;
use App\Libraries\Otp\Persistence\DatabaseOtpRepository;
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
        $nowTs = ($this->clock)();
        $now = $this->date($nowTs);
        $phoneHash = $this->fingerprint($phone);
        $ipHash = $this->fingerprint($ipAddress);
        $this->repository->cleanup($this->date($nowTs - $this->config->cleanupRetentionSeconds));

        $active = $this->repository->findActive($accountId, $now);
        if ($active !== null) {
            $retryAfter = max(1, strtotime((string) $active['resend_available_at']) - $nowTs);
            $status = (string) ($active['delivery_status'] ?? 'pending');

            // Jangan menghasilkan kode kedua ketika status provider belum pasti.
            if (in_array($status, ['pending', 'sent', 'ambiguous'], true)) {
                $this->audit((int) $active['id'], $accountId, 'request_blocked', $phoneHash, $ipHash, [
                    'reason' => $status === 'ambiguous' ? 'delivery_ambiguous' : 'active_otp',
                ], $now);

                return new OtpRequestResult(false, $status === 'ambiguous' ? 'delivery_ambiguous' : 'cooldown', $retryAfter);
            }

            if (strtotime((string) $active['resend_available_at']) > $nowTs) {
                $this->audit((int) $active['id'], $accountId, 'request_blocked', $phoneHash, $ipHash, [
                    'reason' => 'cooldown',
                ], $now);

                return new OtpRequestResult(false, 'cooldown', $retryAfter);
            }
        }

        $since = $this->date($nowTs - $this->config->requestWindowSeconds);
        if ($this->repository->countRequests('phone_hash', $phoneHash, $since) >= $this->config->maxRequestsPerPhone
            || $this->repository->countRequests('ip_hash', $ipHash, $since) >= $this->config->maxRequestsPerIp) {
            $this->audit(null, $accountId, 'request_blocked', $phoneHash, $ipHash, ['reason' => 'rate_limited'], $now);

            return new OtpRequestResult(false, 'rate_limited', $this->config->requestWindowSeconds);
        }

        $this->repository->cancelActive($accountId, $now);
        $code = str_pad((string) ($this->randomInt)(0, (10 ** $this->config->length) - 1), $this->config->length, '0', STR_PAD_LEFT);
        $otpId = $this->repository->create([
            'member_account_id'  => $accountId,
            'code_hash'          => password_hash($code, PASSWORD_DEFAULT),
            'phone_hash'         => $phoneHash,
            'ip_hash'            => $ipHash,
            'delivery_status'    => 'created',
            'verification_attempts' => 0,
            'max_attempts'       => $this->config->maxVerificationAttempts,
            'expires_at'         => $this->date($nowTs + $this->config->ttlSeconds),
            'resend_available_at'=> $this->date($nowTs + $this->config->resendCooldownSeconds),
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);
        $this->audit($otpId, $accountId, 'requested', $phoneHash, $ipHash, [], $now);

        $delivery = $this->delivery->send($phone, $code, $this->config->ttlSeconds);
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

        return new OtpRequestResult($delivery->accepted(), $delivery->status, $this->config->resendCooldownSeconds, $delivery->error);
    }

    public function verify(int $accountId, string $code, string $ipAddress): OtpVerificationResult
    {
        $now = $this->date(($this->clock)());
        $ipHash = $this->fingerprint($ipAddress);
        $otp = $this->repository->findActive($accountId, $now);
        if ($otp === null) {
            $this->repository->audit(null, $accountId, 'verification_failed', ['ip_hash' => $ipHash, 'reason' => 'not_found_or_expired'], $now);

            return new OtpVerificationResult(false, 'invalid');
        }

        $otpId = (int) $otp['id'];
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

        $this->repository->update($otpId, ['used_at' => $now, 'updated_at' => $now]);
        $this->repository->audit($otpId, $accountId, 'verification_succeeded', [
            'phone_hash' => $otp['phone_hash'],
            'ip_hash'    => $ipHash,
        ], $now);

        return new OtpVerificationResult(true, 'verified');
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

    private function date(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }
}
