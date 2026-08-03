<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

final class AdminLoginSecurity extends BaseConfig
{
    public int $maxAttemptsPerIp = 10;
    public int $ipWindowSeconds = 60;
    public int $maxAttemptsPerUsername = 5;
    public int $usernameWindowSeconds = 900;
    public int $maxAttemptsGlobal = 100;
    public int $globalWindowSeconds = 600;

    public function __construct()
    {
        parent::__construct();

        $this->maxAttemptsPerIp = $this->envInt('ADMIN_LOGIN_MAX_ATTEMPTS_PER_IP', $this->maxAttemptsPerIp);
        $this->ipWindowSeconds = $this->envInt('ADMIN_LOGIN_IP_WINDOW_SECONDS', $this->ipWindowSeconds);
        $this->maxAttemptsPerUsername = $this->envInt('ADMIN_LOGIN_MAX_ATTEMPTS_PER_USERNAME', $this->maxAttemptsPerUsername);
        $this->usernameWindowSeconds = $this->envInt('ADMIN_LOGIN_USERNAME_WINDOW_SECONDS', $this->usernameWindowSeconds);
        $this->maxAttemptsGlobal = $this->envInt('ADMIN_LOGIN_MAX_ATTEMPTS_GLOBAL', $this->maxAttemptsGlobal);
        $this->globalWindowSeconds = $this->envInt('ADMIN_LOGIN_GLOBAL_WINDOW_SECONDS', $this->globalWindowSeconds);
    }

    private function envInt(string $name, int $default): int
    {
        $value = env($name);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : $default;
    }
}
