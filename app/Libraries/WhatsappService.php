<?php

namespace App\Libraries;

/**
 * Adapter Fonnte untuk pengiriman pesan WhatsApp langsung.
 *
 * Service ini sengaja tidak memiliki scheduling, queue, reminder, atau cron.
 * OTP nantinya cukup memanggil send() pada saat pengguna meminta kode.
 */
class WhatsappService
{
    private const DEFAULT_SEND_URL = 'https://api.fonnte.com/send';
    private const DEFAULT_DEVICE_URL = 'https://api.fonnte.com/device';

    private string $token;
    private string $sendUrl;
    private string $deviceUrl;

    public function __construct(
        ?string $token = null,
        ?string $sendUrl = null,
        ?string $deviceUrl = null,
    ) {
        $this->token = trim($token ?? (string) env('WA_API_KEY', ''));
        $this->sendUrl = trim($sendUrl ?? (string) env('WA_API_URL', self::DEFAULT_SEND_URL));
        $this->deviceUrl = trim($deviceUrl ?? (string) env('WA_DEVICE_URL', self::DEFAULT_DEVICE_URL));
    }

    public function isConfigured(): bool
    {
        return $this->token !== '';
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

        return $this->request($this->sendUrl, [
            'target'      => $target,
            'message'     => $message,
            'countryCode' => '62',
        ]);
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

        return $this->request($this->deviceUrl, []);
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
     * @param array<string, string> $fields
     *
     * @return array{success: bool, response: string|null, error: string|null}
     */
    private function request(string $url, array $fields): array
    {
        if (! function_exists('curl_init')) {
            return $this->failure('Ekstensi cURL PHP belum tersedia.');
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_HTTPHEADER     => ['Authorization: ' . $this->token],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $rawResponse = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($rawResponse === false || $curlError !== '') {
            log_message('warning', 'Koneksi WhatsApp API gagal: {error}', [
                'error' => $curlError !== '' ? $curlError : 'Respons kosong',
            ]);

            return $this->failure('Gagal menghubungi server WhatsApp.');
        }

        $decoded = json_decode($rawResponse, true);
        if (! is_array($decoded)) {
            return $this->failure('Respons layanan WhatsApp tidak valid.', $rawResponse);
        }

        if ($httpStatus >= 400 || ($decoded['status'] ?? false) !== true) {
            return $this->failure($this->friendlyApiError($decoded), $rawResponse);
        }

        return [
            'success'  => true,
            'response' => $rawResponse,
            'error'    => null,
        ];
    }

    /**
     * @param array<string, mixed> $response
     */
    private function friendlyApiError(array $response): string
    {
        $reason = trim((string) ($response['reason'] ?? $response['message'] ?? ''));
        $normalized = strtolower($reason);

        if (str_contains($normalized, 'token')) {
            return 'Autentikasi layanan WhatsApp gagal.';
        }

        if (str_contains($normalized, 'device') || str_contains($normalized, 'disconnect')) {
            return 'Perangkat WhatsApp pengirim tidak terhubung.';
        }

        return $reason !== '' ? $reason : 'Layanan WhatsApp menolak permintaan.';
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
