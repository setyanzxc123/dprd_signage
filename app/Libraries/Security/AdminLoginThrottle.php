<?php

namespace App\Libraries\Security;

use Config\AdminLoginSecurity;

final class AdminLoginThrottle
{
    /** @var \Closure(string, int, int): bool */
    private \Closure $checker;
    /** @var \Closure(string): void */
    private \Closure $remover;

    public function __construct(
        private readonly ?AdminLoginSecurity $config = null,
        ?callable $checker = null,
        ?callable $remover = null,
    ) {
        $this->checker = \Closure::fromCallable($checker ?? static fn (string $key, int $capacity, int $seconds): bool =>
            service('throttler')->check($key, $capacity, $seconds));
        $this->remover = \Closure::fromCallable($remover ?? static function (string $key): void {
            service('throttler')->remove($key);
        });
    }

    public function allows(string $username, string $ipAddress): bool
    {
        $config = $this->settings();
        $limits = [
            ['admin_login_global', $config->maxAttemptsGlobal, $config->globalWindowSeconds],
            ['admin_login_ip_' . $this->fingerprint($ipAddress), $config->maxAttemptsPerIp, $config->ipWindowSeconds],
            [$this->usernameKey($username), $config->maxAttemptsPerUsername, $config->usernameWindowSeconds],
        ];

        foreach ($limits as [$key, $capacity, $seconds]) {
            if (! ($this->checker)($key, $capacity, $seconds)) {
                return false;
            }
        }

        return true;
    }

    public function clearUsername(string $username): void
    {
        ($this->remover)($this->usernameKey($username));
    }

    public function usernameFingerprint(string $username): string
    {
        return $this->fingerprint(mb_strtolower(trim($username)) ?: 'invalid-admin-username');
    }

    private function usernameKey(string $username): string
    {
        return 'admin_login_username_' . $this->usernameFingerprint($username);
    }

    private function fingerprint(string $value): string
    {
        return hash('sha256', trim($value));
    }

    private function settings(): AdminLoginSecurity
    {
        return $this->config ?? new AdminLoginSecurity();
    }
}
