<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Otp extends BaseConfig
{
    public string $provider = 'internal';
    public string $fazpassApiUrl = 'https://api.fazpass.com';
    public string $fazpassApiPrefix = '/api/v1';
    public string $fazpassMerchantKey = '';
    public string $fazpassGatewayKey = '';
    public string $fazpassCallbackSecret = '';
    public int $fazpassTimeoutSeconds = 15;
    public int $length = 6;
    public int $ttlSeconds = 300;
    public int $challengeTtlSeconds = 900;
    public int $resendCooldownSeconds = 60;
    public int $requestWindowSeconds = 3600;
    public int $maxRequestsPerPhone = 5;
    public int $maxRequestsPerIp = 100;
    public int $dailyWindowSeconds = 86400;
    public int $maxRequestsPerAccountPerDay = 10;
    public int $globalWindowSeconds = 3600;
    public int $maxRequestsGlobal = 100;
    public int $maxRequestsGlobalPerDay = 300;
    public int $maxVerificationAttempts = 5;
    public int $cleanupRetentionSeconds = 86400;

    public function __construct()
    {
        parent::__construct();

        $this->provider = strtolower(trim((string) env('OTP_PROVIDER', $this->provider)));
        $this->fazpassApiUrl = rtrim((string) env('FAZPASS_API_URL', $this->fazpassApiUrl), '/');
        $this->fazpassApiPrefix = '/' . trim((string) env('FAZPASS_API_PREFIX', trim($this->fazpassApiPrefix, '/')), '/');
        $this->fazpassMerchantKey = trim((string) env('FAZPASS_MERCHANT_KEY', ''));
        $this->fazpassGatewayKey = trim((string) env('FAZPASS_GATEWAY_KEY', ''));
        $this->fazpassCallbackSecret = trim((string) env('FAZPASS_CALLBACK_SECRET', ''));
        $this->fazpassTimeoutSeconds = $this->envInt('FAZPASS_TIMEOUT_SECONDS', $this->fazpassTimeoutSeconds);
        $this->ttlSeconds = $this->envInt('OTP_TTL_SECONDS', $this->ttlSeconds);
        $this->challengeTtlSeconds = $this->envInt('OTP_CHALLENGE_TTL_SECONDS', $this->challengeTtlSeconds);
        $this->resendCooldownSeconds = $this->envInt('OTP_RESEND_COOLDOWN_SECONDS', $this->resendCooldownSeconds);
        $this->requestWindowSeconds = $this->envInt('OTP_REQUEST_WINDOW_SECONDS', $this->requestWindowSeconds);
        $this->maxRequestsPerPhone = $this->envInt('OTP_MAX_REQUESTS_PER_PHONE', $this->maxRequestsPerPhone);
        // Satu IP publik dapat mewakili seluruh jaringan gedung. Jangan biarkan
        // konfigurasi lama (20/jam) memblokir burst login sekitar 40 anggota.
        $this->maxRequestsPerIp = max(
            100,
            $this->envInt('OTP_MAX_REQUESTS_PER_IP', $this->maxRequestsPerIp),
        );
        $this->dailyWindowSeconds = $this->envInt('OTP_DAILY_WINDOW_SECONDS', $this->dailyWindowSeconds);
        $this->maxRequestsPerAccountPerDay = $this->envInt('OTP_MAX_REQUESTS_PER_ACCOUNT_PER_DAY', $this->maxRequestsPerAccountPerDay);
        $this->globalWindowSeconds = $this->envInt('OTP_GLOBAL_WINDOW_SECONDS', $this->globalWindowSeconds);
        $this->maxRequestsGlobal = $this->envInt('OTP_MAX_REQUESTS_GLOBAL', $this->maxRequestsGlobal);
        $this->maxRequestsGlobalPerDay = $this->envInt('OTP_MAX_REQUESTS_GLOBAL_PER_DAY', $this->maxRequestsGlobalPerDay);
        $this->maxVerificationAttempts = $this->envInt('OTP_MAX_VERIFICATION_ATTEMPTS', $this->maxVerificationAttempts);
        $this->cleanupRetentionSeconds = $this->envInt('OTP_CLEANUP_RETENTION_SECONDS', $this->cleanupRetentionSeconds);
    }

    private function envInt(string $name, int $default): int
    {
        $value = env($name);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : $default;
    }
}
