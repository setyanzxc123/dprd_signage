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
            return new BaileysSendResult(
                false,
                error: 'Baileys gateway belum dikonfigurasi.',
                errorCode: 'NOT_CONFIGURED',
                statusCode: 0,
            );
        }

        $body = [
            'phone'          => $phone,
            'otp'            => $code,
            'app_name'       => $this->config->appName,
            'wait_for_ack'   => $this->config->baileysWaitForAck,
            'ack_timeout_ms' => $this->config->baileysAckTimeoutMs,
        ];

        if ($this->config->baileysOtpTemplate !== null) {
            $body['template'] = $this->config->baileysOtpTemplate;
        }

        $timeoutSeconds = max(5, $this->config->baileysTimeoutSeconds);

        $response = $this->transport->postJson(
            $this->endpoint('/send-otp'),
            $this->headers(),
            $body,
            $timeoutSeconds,
        );

        return $this->parseSendResponse($response);
    }

    public function sendMessage(
        string $to,
        string $message,
        ?bool $waitForAck = null,
        ?int $ackTimeoutMs = null,
    ): BaileysSendResult {
        if (! $this->isConfigured()) {
            return new BaileysSendResult(
                false,
                error: 'Baileys gateway belum dikonfigurasi.',
                errorCode: 'NOT_CONFIGURED',
                statusCode: 0,
            );
        }

        $body = [
            'to'             => $to,
            'message'        => $message,
            'wait_for_ack'   => $waitForAck ?? $this->config->baileysWaitForAck,
            'ack_timeout_ms' => $ackTimeoutMs ?? $this->config->baileysAckTimeoutMs,
        ];

        $timeoutSeconds = max(5, $this->config->baileysTimeoutSeconds);

        $response = $this->transport->postJson(
            $this->endpoint('/send-message'),
            $this->headers(),
            $body,
            $timeoutSeconds,
        );

        return $this->parseSendResponse($response);
    }

    private function parseSendResponse(\App\Libraries\WhatsApp\ValueObjects\HttpResponse $response): BaileysSendResult
    {
        $payload = $this->payload($response->body);
        $statusCode = $response->statusCode;

        if ($response->error !== null) {
            $errorCode = $this->string($payload['code'] ?? null) ?? 'CONNECTION_ERROR';

            return new BaileysSendResult(
                false,
                error: $response->error,
                errorCode: $errorCode,
                statusCode: $statusCode,
            );
        }

        if ($payload === null) {
            $errorCode = $this->defaultErrorCodeForStatus($statusCode);

            return new BaileysSendResult(
                false,
                error: 'Respons dari WhatsApp Gateway tidak valid.',
                errorCode: $errorCode,
                statusCode: $statusCode,
            );
        }

        $status = (string) ($payload['status'] ?? '');
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $messageId = $this->string($data['messageId'] ?? null);

        if ($statusCode === 200 && $status === 'success' && $messageId !== null) {
            $serverAck = (bool) ($data['server_ack'] ?? false);
            $ackElapsedMs = isset($data['ack_elapsed_ms']) && is_numeric($data['ack_elapsed_ms'])
                ? (int) $data['ack_elapsed_ms']
                : null;

            return new BaileysSendResult(
                true,
                messageId: $messageId,
                statusCode: 200,
                serverAck: $serverAck,
                ackElapsedMs: $ackElapsedMs,
            );
        }

        $errorCode = $this->string($payload['code'] ?? null) ?? $this->defaultErrorCodeForStatus($statusCode);
        $errorMessage = $this->error($payload);

        return new BaileysSendResult(
            false,
            error: $errorMessage,
            errorCode: $errorCode,
            statusCode: $statusCode,
        );
    }

    private function defaultErrorCodeForStatus(int $statusCode): string
    {
        return match ($statusCode) {
            502     => 'WA_SERVER_REJECTED',
            504     => 'WA_SERVER_ACK_TIMEOUT',
            503     => 'WA_GATEWAY_OFFLINE',
            422     => 'WA_NUMBER_NOT_REGISTERED',
            429     => 'RATE_LIMITED',
            401     => 'UNAUTHORIZED',
            default => 'SEND_FAILED',
        };
    }

    public const OFFLINE_CACHE_KEY = 'baileys_gateway_offline_status';
    public const OFFLINE_CACHE_TTL = 15;

    /** @return array<string, mixed> */
    public function getStatus(bool $forceRefresh = false): array
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

        if (! $forceRefresh) {
            try {
                $cached = cache(self::OFFLINE_CACHE_KEY);
                if (is_array($cached)) {
                    return $cached;
                }
            } catch (\Throwable) {
            }
        }

        $response = $this->transport->get(
            $this->endpoint('/status'),
            $this->headers(),
            $this->config->baileysTimeoutSeconds,
        );

        $payload = $this->payload($response->body);
        if ($response->error !== null || $payload === null || $response->statusCode >= 400) {
            $offlineStatus = [
                'configured' => true,
                'connected'  => false,
                'status'     => 'offline',
                'phone'      => null,
                'name'       => null,
                'qr_url'     => $this->endpoint('/qr/raw'),
                'error'      => $response->error ?? $this->error($payload),
            ];

            try {
                cache()->save(self::OFFLINE_CACHE_KEY, $offlineStatus, self::OFFLINE_CACHE_TTL);
            } catch (\Throwable) {
            }

            return $offlineStatus;
        }

        try {
            cache()->delete(self::OFFLINE_CACHE_KEY);
        } catch (\Throwable) {
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

        try {
            $cachedOffline = cache(self::OFFLINE_CACHE_KEY);
            if (is_array($cachedOffline)) {
                return [
                    'success'      => false,
                    'connected'    => false,
                    'qr_available' => false,
                    'qr_data_url'  => null,
                    'error'        => $cachedOffline['error'] ?? 'WhatsApp Gateway sedang offline.',
                ];
            }
        } catch (\Throwable) {
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

    /** @return array<string, mixed> */
    public function logoutDevice(): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => null,
                'error'   => 'Baileys gateway belum dikonfigurasi.',
            ];
        }

        $response = $this->transport->postJson(
            $this->endpoint('/logout'),
            $this->headers(),
            [],
            $this->config->baileysTimeoutSeconds,
        );

        $payload = $this->payload($response->body);
        if ($response->error !== null || $payload === null || $response->statusCode >= 400) {
            return [
                'success' => false,
                'message' => null,
                'error'   => $response->error ?? $this->error($payload),
            ];
        }

        if (($payload['status'] ?? '') !== 'success') {
            return [
                'success' => false,
                'message' => null,
                'error'   => $this->error($payload),
            ];
        }

        return [
            'success' => true,
            'message' => $this->string($payload['message'] ?? null),
            'error'   => null,
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
