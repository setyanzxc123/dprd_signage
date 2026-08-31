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
            return new BaileysSendResult(false, error: $response->error ?? $this->error($payload));
        }

        $messageId = $this->string($payload['data']['messageId'] ?? null);
        if (($payload['status'] ?? '') !== 'success' || $messageId === null) {
            return new BaileysSendResult(false, error: $this->error($payload));
        }

        return new BaileysSendResult(true, $messageId);
    }

    /** @return array<string, mixed> */
    public function getStatus(): array
    {
        if (! $this->isConfigured()) {
            return [
                'configured' => false,
                'connected'  => false,
                'status'     => 'unconfigured',
                'phone'      => null,
                'name'       => null,
                'qr_url'     => $this->endpoint('/qr/raw'),
                'error'      => 'Baileys gateway belum dikonfigurasi.',
            ];
        }

        $response = $this->transport->get(
            $this->endpoint('/status'),
            $this->headers(),
            $this->config->baileysTimeoutSeconds,
        );

        $payload = $this->payload($response->body);
        if ($response->error !== null || $payload === null || $response->statusCode >= 400) {
            return [
                'configured' => true,
                'connected'  => false,
                'status'     => 'offline',
                'phone'      => null,
                'name'       => null,
                'qr_url'     => $this->endpoint('/qr/raw'),
                'error'      => $response->error ?? $this->error($payload),
            ];
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $connected = ($data['connected'] ?? false) === true;
        $user = is_array($data['user'] ?? null) ? $data['user'] : [];

        return [
            'configured' => true,
            'connected'  => $connected,
            'status'     => (string) ($data['status'] ?? ($connected ? 'connected' : 'disconnected')),
            'phone'      => $this->string($user['phone'] ?? null),
            'name'       => $this->string($user['name'] ?? null),
            'qr_url'     => $this->endpoint('/qr/raw'),
            'error'      => null,
        ];
    }

    /** @return array<string, mixed> */
    public function getRawQr(): array
    {
        if (! $this->isConfigured()) {
            return [
                'success'      => false,
                'connected'    => false,
                'qr_available' => false,
                'qr_data_url'  => null,
                'error'        => 'Baileys gateway belum dikonfigurasi.',
            ];
        }

        $response = $this->transport->get(
            $this->endpoint('/qr/raw'),
            $this->headers(),
            $this->config->baileysTimeoutSeconds,
        );

        $payload = $this->payload($response->body);
        if ($response->error !== null || $payload === null || $response->statusCode >= 400) {
            return [
                'success'      => false,
                'connected'    => false,
                'qr_available' => false,
                'qr_data_url'  => null,
                'error'        => $response->error ?? $this->error($payload),
            ];
        }

        return [
            'success'      => ($payload['status'] ?? '') === 'success',
            'connected'    => (bool) ($payload['connected'] ?? false),
            'qr_available' => (bool) ($payload['qr_available'] ?? false),
            'qr_data_url'  => $this->string($payload['qr_data_url'] ?? null),
            'error'        => null,
        ];
    }

    /** @return array<string, mixed> */
    public function requestPairCode(string $phone): array
    {
        if (! $this->isConfigured()) {
            return [
                'success'      => false,
                'pairing_code' => null,
                'phone'        => null,
                'error'        => 'Baileys gateway belum dikonfigurasi.',
            ];
        }

        $response = $this->transport->postJson(
            $this->endpoint('/pair-code'),
            $this->headers(),
            ['phone' => $phone],
            $this->config->baileysTimeoutSeconds,
        );

        $payload = $this->payload($response->body);
        if ($response->error !== null || $payload === null || $response->statusCode >= 400) {
            return [
                'success'      => false,
                'pairing_code' => null,
                'phone'        => null,
                'error'        => $response->error ?? $this->error($payload),
            ];
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $pairingCode = $this->string($data['pairing_code'] ?? null);

        if (($payload['status'] ?? '') !== 'success' || $pairingCode === null) {
            return [
                'success'      => false,
                'pairing_code' => null,
                'phone'        => null,
                'error'        => $this->error($payload),
            ];
        }

        return [
            'success'      => true,
            'pairing_code' => $pairingCode,
            'phone'        => $this->string($data['phone'] ?? null),
            'error'        => null,
        ];
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
