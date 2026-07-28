<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Otp extends BaseConfig
{
    public string $provider = 'internal';
    public string $fazpassApiUrl = 'https://api.fazpass.com';
    public string $fazpassApiPrefix = '/v1';
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
    public int $maxRequestsPerIp = 20;
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
        $this->maxRequestsPerIp = $this->envInt('OTP_MAX_REQUESTS_PER_IP', $this->maxRequestsPerIp);
        $this->maxVerificationAttempts = $this->envInt('OTP_MAX_VERIFICATION_ATTEMPTS', $this->maxVerificationAttempts);
        $this->cleanupRetentionSeconds = $this->envInt('OTP_CLEANUP_RETENTION_SECONDS', $this->cleanupRetentionSeconds);
    }

    private function envInt(string $name, int $default): int
    {
        $value = env($name);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : $default;
    }
}
