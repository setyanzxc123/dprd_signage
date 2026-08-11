<?php

namespace App\Libraries\Otp;

use CodeIgniter\Session\SessionInterface;
use Config\Otp;

final class OtpPendingSession
{
    private const SESSION_KEY = 'member_otp_pending';

    /** @var \Closure(): int */
    private \Closure $clock;

    public function __construct(
        ?SessionInterface $session = null,
        ?Otp $config = null,
        ?callable $clock = null,
    ) {
        $this->session = $session ?? service('session');
        $this->config = $config ?? new Otp();
        $this->clock = \Closure::fromCallable($clock ?? static fn (): int => time());
    }

    private readonly SessionInterface $session;
    private readonly Otp $config;

    /** @return array<string, mixed>|null */
    public function get(): ?array
    {
        $pending = $this->session->get(self::SESSION_KEY);
        $now = ($this->clock)();

        if (! is_array($pending)
            || (int) ($pending['expires_at'] ?? 0) <= $now
            || ! isset($pending['anggota_id'], $pending['phone_hash'])) {
            $this->forget();

            return null;
        }

        return $pending;
    }

    /** @return array<string, mixed> */
    public function begin(
        int $anggotaId,
        string $phoneHash,
        string $maskedPhone,
        int $retryAfter,
        int $otpExpiresAt,
    ): array {
        $now = ($this->clock)();
        $retryAt = $now + max(0, $retryAfter);
        $otpExpiresAt = max($now, $otpExpiresAt);
        $pending = [
            'anggota_id'     => $anggotaId,
            'phone_hash'     => $phoneHash,
            'masked'         => $maskedPhone,
            'retry_at'       => $retryAt,
            'otp_expires_at' => $otpExpiresAt,
            'expires_at'     => max(
                $now + $this->config->challengeTtlSeconds,
                $retryAt + 60,
                $otpExpiresAt + 60,
            ),
        ];
        $this->session->set(self::SESSION_KEY, $pending);

        return $pending;
    }

    /**
     * @param array<string, mixed> $pending
     * @return array<string, mixed>
     */
    public function refresh(array $pending, int $retryAfter, int $otpExpiresAt): array
    {
        $now = ($this->clock)();
        $retryAt = $now + max(0, $retryAfter);
        $otpExpiresAt = max($now, $otpExpiresAt);
        $pending['retry_at'] = $retryAt;
        $pending['otp_expires_at'] = $otpExpiresAt;
        $pending['expires_at'] = max(
            $now + $this->config->challengeTtlSeconds,
            $retryAt + 60,
            $otpExpiresAt + 60,
        );
        $this->session->set(self::SESSION_KEY, $pending);

        return $pending;
    }

    public function forget(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }

    /** @param array<string, mixed> $pending */
    public function retryAfter(array $pending): int
    {
        return max(0, (int) ($pending['retry_at'] ?? 0) - ($this->clock)());
    }

    /** @param array<string, mixed> $pending */
    public function otpExpiresAfter(array $pending): int
    {
        return max(0, (int) ($pending['otp_expires_at'] ?? 0) - ($this->clock)());
    }
}
