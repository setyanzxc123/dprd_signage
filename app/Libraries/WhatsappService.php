<?php

namespace App\Libraries;

use App\Libraries\WhatsApp\Providers\FonnteProvider;
use App\Libraries\WhatsApp\WhatsappGateway;

/**
 * Facade kompatibilitas untuk akses WhatsApp transaksional.
 *
 * Detail Fonnte berada di FonnteProvider. Service ini sengaja tidak memiliki
 * scheduling, queue, reminder, blast, atau cron pengiriman.
 */
class WhatsappService
{
    private readonly WhatsappGateway $gateway;

    public function __construct(
        ?string $token = null,
        ?string $sendUrl = null,
        ?string $deviceUrl = null,
        ?WhatsappGateway $gateway = null,
    ) {
        $this->gateway = $gateway ?? new WhatsappGateway(
            new FonnteProvider(
                token: $token,
                sendUrl: $sendUrl,
                deviceUrl: $deviceUrl,
            ),
        );
    }

    public function isConfigured(): bool
    {
        return $this->gateway->isConfigured();
    }

    /**
     * Kirim satu pesan WhatsApp secara langsung.
     *
     * @return array{success: bool, response: string|null, error: string|null}
     */
    public function send(string $phone, string $message): array
    {
        if (! $this->isConfigured()) {
            return $this->failure('Layanan WhatsApp belum dikonfigurasi.');
        }

        $target = self::normalizePhone($phone);
        if (! self::isValidIndonesianPhone($target)) {
            return $this->failure('Nomor WhatsApp tujuan tidak valid.');
        }

        if (trim($message) === '') {
            return $this->failure('Pesan WhatsApp tidak boleh kosong.');
        }

        $result = $this->gateway->send($target, $message);

        return [
            'success'  => $result->success,
            'response' => $result->rawResponse,
            'error'    => $result->error,
        ];
    }

    /**
     * Periksa token dan koneksi perangkat Fonnte.
     *
     * @return array{success: bool, response: string|null, error: string|null}
     */
    public function checkConnection(): array
    {
        if (! $this->isConfigured()) {
            return $this->failure('Token API WhatsApp belum disetel.');
        }

        $result = $this->gateway->checkConnection();
        $connected = $result->success && $result->connected;

        return [
            'success'  => $connected,
            'response' => $result->rawResponse,
            'error'    => $connected
                ? null
                : ($result->error ?? 'Perangkat WhatsApp pengirim tidak terhubung.'),
        ];
    }

    /**
     * Ubah nomor Indonesia ke format 628xxx.
     */
    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($phone, '62')) {
            return $phone;
        }

        if (str_starts_with($phone, '08')) {
            return '62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }

        return $phone;
    }

    public static function isValidIndonesianPhone(string $phone): bool
    {
        return preg_match('/^628\d{7,12}$/', $phone) === 1;
    }

    /**
     * @return array{success: false, response: string|null, error: string}
     */
    private function failure(string $error, ?string $response = null): array
    {
        return [
            'success'  => false,
            'response' => $response,
            'error'    => $error,
        ];
    }
}
