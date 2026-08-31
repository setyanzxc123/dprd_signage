<?php

namespace App\Libraries\Otp\Providers;

use App\Libraries\Otp\ValueObjects\BaileysSendResult;
use App\Libraries\WhatsApp\Contracts\HttpTransportInterface;
use App\Libraries\WhatsApp\Transport\CurlHttpTransport;
use Config\Otp;

final class BaileysProvider
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
        return $this->config->baileysApiKey !== '' && $this->config->baileysApiUrl !== '';
    }

    public function sendOtp(string $phone, string $code): BaileysSendResult
    {
        if (! $this->isConfigured()) {
            return new BaileysSendResult(false, error: 'Baileys gateway belum dikonfigurasi.');
        }

        $response = $this->transport->postJson(
            $this->endpoint('/send-otp'),
            $this->headers(),
            ['phone' => $phone, 'otp' => $code],
            $this->config->baileysTimeoutSeconds,
        );

        $payload = $this->payload($response->body);
        if ($response->error !== null || $payload === null || $response->statusCode >= 400) {
            return new BaileysSendResult(false, error: $this->error($payload));
        }

        $messageId = $this->string($payload['data']['messageId'] ?? null);
        if (($payload['status'] ?? '') !== 'success' || $messageId === null) {
            return new BaileysSendResult(false, error: $this->error($payload));
        }

        return new BaileysSendResult(true, $messageId);
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->config->baileysApiUrl, '/') . $path;
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return [
            'x-api-key' => $this->config->baileysApiKey,
            'Accept'    => 'application/json',
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
        $message = trim((string) ($payload['message'] ?? $payload['error'] ?? ''));
        if ($message === '') {
            $message = 'Baileys gateway tidak merespons dengan benar.';
        }

        return $message;
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
