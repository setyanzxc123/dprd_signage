<?php

namespace App\Libraries\Otp\Providers;

use App\Libraries\Otp\ValueObjects\FazpassRequestResult;
use App\Libraries\Otp\ValueObjects\FazpassVerifyResult;
use App\Libraries\WhatsApp\Contracts\HttpTransportInterface;
use App\Libraries\WhatsApp\Transport\CurlHttpTransport;
use Config\Otp;

final class FazpassProvider
{
    private readonly HttpTransportInterface $transport;
    private readonly Otp $config;

    public function __construct(?HttpTransportInterface $transport = null, ?Otp $config = null)
    {
        $this->transport = $transport ?? new CurlHttpTransport();
        $this->config = $config ?? new Otp();
    }

    public function isConfigured(): bool
    {
        return $this->config->fazpassMerchantKey !== '' && $this->config->fazpassGatewayKey !== '';
    }

    public function request(string $phone): FazpassRequestResult
    {
        if (! $this->isConfigured()) {
            return new FazpassRequestResult(false, 'failed', error: 'Fazpass belum dikonfigurasi.');
        }

        $response = $this->transport->postJson(
            $this->endpoint('/otp/request'),
            $this->headers(),
            ['phone' => $phone, 'gateway_key' => $this->config->fazpassGatewayKey],
            $this->config->fazpassTimeoutSeconds,
        );

        $payload = $this->payload($response->body);
        if ($response->error !== null || $payload === null || $response->statusCode >= 400) {
            return new FazpassRequestResult(false, 'failed', error: $this->error($payload));
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $otpId = $this->string($data['id'] ?? $data['otp_id'] ?? null);
        $transactionId = $this->string($data['transaction_id'] ?? $payload['transaction_id'] ?? null);
        if (($payload['status'] ?? false) !== true || $otpId === null) {
            return new FazpassRequestResult(false, 'failed', error: $this->error($payload));
        }

        return new FazpassRequestResult(true, 'pending', $otpId, $transactionId, $this->string($data['provider'] ?? null));
    }

    public function verify(string $otpId, string $code): FazpassVerifyResult
    {
        if (! $this->isConfigured()) {
            return new FazpassVerifyResult(false, 'failed', 'Fazpass belum dikonfigurasi.');
        }

        $response = $this->transport->postJson(
            $this->endpoint('/otp/verify'),
            $this->headers(),
            ['otp_id' => $otpId, 'otp' => $code],
            $this->config->fazpassTimeoutSeconds,
        );
        $payload = $this->payload($response->body);
        if ($response->error !== null || $payload === null || $response->statusCode >= 400) {
            return new FazpassVerifyResult(false, 'failed', $this->error($payload));
        }

        $success = ($payload['status'] ?? false) === true;

        return new FazpassVerifyResult($success, $success ? 'verified' : 'invalid', $success ? null : $this->error($payload));
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->config->fazpassApiUrl, '/') . rtrim($this->config->fazpassApiPrefix, '/') . $path;
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->fazpassMerchantKey,
            'Accept'        => 'application/json',
        ];
    }

    /** @return array<string, mixed>|null */
    private function payload(?string $body): ?array
    {
        if ($body === null || $body === '') {
            return null;
        }
        $payload = json_decode($body, true);

        return is_array($payload) ? $payload : null;
    }

    /** @param array<string, mixed>|null $payload */
    private function error(?array $payload): string
    {
        $message = trim((string) ($payload['message'] ?? $payload['error'] ?? $payload['reason'] ?? ''));

        return $message !== '' ? $message : 'Fazpass menolak permintaan OTP.';
    }

    private function string(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
