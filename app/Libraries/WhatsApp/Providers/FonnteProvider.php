<?php

namespace App\Libraries\WhatsApp\Providers;

use App\Libraries\WhatsApp\Contracts\HttpTransportInterface;
use App\Libraries\WhatsApp\Contracts\WhatsappProviderInterface;
use App\Libraries\WhatsApp\Transport\CurlHttpTransport;
use App\Libraries\WhatsApp\ValueObjects\ConnectionResult;
use App\Libraries\WhatsApp\ValueObjects\HttpResponse;
use App\Libraries\WhatsApp\ValueObjects\SendResult;
use App\Libraries\WhatsApp\ValueObjects\StatusWebhookEvent;

final class FonnteProvider implements WhatsappProviderInterface
{
    private const DEFAULT_SEND_URL = 'https://api.fonnte.com/send';
    private const DEFAULT_DEVICE_URL = 'https://api.fonnte.com/device';

    private readonly string $token;
    private readonly string $sendUrl;
    private readonly string $deviceUrl;
    private readonly int $timeoutSeconds;

    public function __construct(
        ?HttpTransportInterface $transport = null,
        ?string $token = null,
        ?string $sendUrl = null,
        ?string $deviceUrl = null,
        ?int $timeoutSeconds = null,
    ) {
        $this->transport = $transport ?? new CurlHttpTransport();
        $this->token = trim($token ?? (string) env('FONNTE_API_TOKEN', env('WA_API_KEY', '')));
        $this->sendUrl = trim($sendUrl ?? (string) env('FONNTE_SEND_URL', env('WA_API_URL', self::DEFAULT_SEND_URL)));
        $this->deviceUrl = trim($deviceUrl ?? (string) env('FONNTE_DEVICE_URL', env('WA_DEVICE_URL', self::DEFAULT_DEVICE_URL)));
        $this->timeoutSeconds = max(1, $timeoutSeconds ?? (int) env('FONNTE_TIMEOUT_SECONDS', 15));
    }

    private readonly HttpTransportInterface $transport;

    public function name(): string
    {
        return 'fonnte';
    }

    public function isConfigured(): bool
    {
        return $this->token !== '';
    }

    public function send(string $target, string $message): SendResult
    {
        if (! $this->isConfigured()) {
            return new SendResult(false, 'failed', error: 'Layanan WhatsApp belum dikonfigurasi.');
        }

        $response = $this->request($this->sendUrl, [
            'target'      => $target,
            'message'     => $message,
            'countryCode' => '0',
            'connectOnly' => true,
        ]);

        if ($response->error !== null || $response->body === null) {
            return new SendResult(
                false,
                'failed',
                error: 'Gagal menghubungi server WhatsApp.',
            );
        }

        $payload = json_decode($response->body, true);
        if (! is_array($payload)) {
            return new SendResult(
                false,
                'failed',
                error: 'Respons layanan WhatsApp tidak valid.',
                rawResponse: $response->body,
            );
        }

        $accepted = $response->statusCode < 400 && $this->payloadStatus($payload);
        $messageIds = $payload['id'] ?? [];
        $messageId = is_array($messageIds) ? ($messageIds[0] ?? null) : $messageIds;
        $process = strtolower(trim((string) ($payload['process'] ?? '')));

        return new SendResult(
            $accepted,
            $accepted ? $this->normalizeSendStatus($process) : 'failed',
            $messageId !== null ? (string) $messageId : null,
            isset($payload['requestid']) ? (string) $payload['requestid'] : null,
            isset($payload['detail']) ? (string) $payload['detail'] : null,
            $accepted ? null : $this->friendlyApiError($payload),
            $response->body,
            $payload,
        );
    }

    public function checkConnection(): ConnectionResult
    {
        if (! $this->isConfigured()) {
            return new ConnectionResult(false, false, error: 'Token API WhatsApp belum disetel.');
        }

        $response = $this->request($this->deviceUrl, []);
        if ($response->error !== null || $response->body === null) {
            return new ConnectionResult(false, false, error: 'Gagal menghubungi server WhatsApp.');
        }

        $payload = json_decode($response->body, true);
        if (! is_array($payload)) {
            return new ConnectionResult(
                false,
                false,
                error: 'Respons layanan WhatsApp tidak valid.',
                rawResponse: $response->body,
            );
        }

        if ($response->statusCode >= 400 || ! $this->payloadStatus($payload)) {
            return new ConnectionResult(
                false,
                false,
                error: $this->friendlyApiError($payload),
                rawResponse: $response->body,
                payload: $payload,
            );
        }

        $deviceStatus = strtolower(trim((string) ($payload['device_status'] ?? $payload['status_device'] ?? '')));

        return new ConnectionResult(
            true,
            in_array($deviceStatus, ['connect', 'connected'], true),
            isset($payload['device']) ? (string) $payload['device'] : null,
            $deviceStatus !== '' ? $deviceStatus : null,
            rawResponse: $response->body,
            payload: $payload,
        );
    }

    public function parseStatusWebhook(array $payload): ?StatusWebhookEvent
    {
        $messageId = $this->nullableString($payload['id'] ?? null);
        $stateId = $this->nullableString($payload['stateid'] ?? null);
        if ($messageId === null && $stateId === null) {
            return null;
        }

        return new StatusWebhookEvent(
            $messageId,
            $stateId,
            $this->nullableString($payload['status'] ?? null),
            $this->nullableString($payload['state'] ?? null),
            $this->nullableString($payload['device'] ?? null),
        );
    }

    /**
     * @param array<string, scalar> $fields
     */
    private function request(string $url, array $fields): HttpResponse
    {
        return $this->transport->post(
            $url,
            ['Authorization' => $this->token],
            $fields,
            $this->timeoutSeconds,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function payloadStatus(array $payload): bool
    {
        return ($payload['status'] ?? $payload['Status'] ?? false) === true;
    }

    private function normalizeSendStatus(string $status): string
    {
        return match ($status) {
            'sent', 'success' => 'sent',
            'delivered'       => 'delivered',
            'read'            => 'read',
            'pending', 'processing', 'waiting', '' => 'pending',
            default           => 'accepted',
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function friendlyApiError(array $payload): string
    {
        $reason = trim((string) ($payload['reason'] ?? $payload['message'] ?? $payload['detail'] ?? ''));
        $normalized = strtolower($reason);

        if (str_contains($normalized, 'token')) {
            return 'Autentikasi layanan WhatsApp gagal.';
        }

        if (str_contains($normalized, 'device') || str_contains($normalized, 'disconnect')) {
            return 'Perangkat WhatsApp pengirim tidak terhubung.';
        }

        return $reason !== '' ? $reason : 'Layanan WhatsApp menolak permintaan.';
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
