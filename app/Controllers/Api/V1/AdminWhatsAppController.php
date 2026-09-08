<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Otp\Providers\BaileysProvider;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Otp;

class AdminWhatsAppController extends BaseController
{
    use ApiResponse;

    private readonly Otp $otpConfig;
    private ?BaileysProvider $provider = null;
    private static ?BaileysProvider $mockProvider = null;

    public function __construct(?BaileysProvider $provider = null, ?Otp $otpConfig = null)
    {
        $this->otpConfig = $otpConfig ?? new Otp();
        $this->provider = $provider;
    }

    public static function setMockProvider(?BaileysProvider $provider): void
    {
        self::$mockProvider = $provider;
    }

    protected function getProvider(): BaileysProvider
    {
        return self::$mockProvider ?? $this->provider ?? new BaileysProvider(config: $this->otpConfig);
    }

    public function status(): ResponseInterface
    {
        $provider = $this->getProvider();
        $forceRefresh = (bool) $this->request->getGet('refresh');
        $gatewayStatus = $provider->getStatus($forceRefresh);

        $qrData = null;
        if (! $gatewayStatus['connected'] && ($gatewayStatus['status'] ?? '') !== 'offline' && empty($gatewayStatus['error'])) {
            $qrData = $provider->getRawQr();
        }

        $fazpassConfigured = $this->otpConfig->fazpassMerchantKey !== '' && $this->otpConfig->fazpassGatewayKey !== '';
        $canFallback = ($this->otpConfig->provider === 'hybrid')
            && $this->otpConfig->fazpassFallbackEnabled
            && $fazpassConfigured;

        return $this->apiSuccess([
            'data' => [
                'provider' => $this->otpConfig->provider,
                'fallback' => [
                    'enabled'      => $this->otpConfig->fazpassFallbackEnabled,
                    'provider'     => 'fazpass',
                    'configured'   => $fazpassConfigured,
                    'can_fallback' => $canFallback,
                ],
                'gateway' => [
                    'configured' => (bool) ($gatewayStatus['configured'] ?? false),
                    'connected'  => (bool) ($gatewayStatus['connected'] ?? false),
                    'status'     => (string) ($gatewayStatus['status'] ?? 'unknown'),
                    'phone'      => $gatewayStatus['phone'] ?? null,
                    'name'       => $gatewayStatus['name'] ?? null,
                    'error'      => $gatewayStatus['error'] ?? null,
                ],
                'qr' => [
                    'available'   => (bool) ($qrData['qr_available'] ?? false),
                    'qr_data_url' => $qrData['qr_data_url'] ?? null,
                ],
            ],
        ]);
    }

    public function pairCode(): ResponseInterface
    {
        $phone = trim((string) $this->input('phone'));
        if ($phone === '') {
            return $this->apiError('Nomor WhatsApp wajib diisi.', 422);
        }

        $cleanPhone = preg_replace('/\D+/', '', $phone);
        if (strlen((string) $cleanPhone) < 9 || strlen((string) $cleanPhone) > 16) {
            return $this->apiError('Format nomor WhatsApp tidak valid.', 422);
        }

        $provider = $this->getProvider();
        $result = $provider->requestPairCode($phone);

        if (! $result['success']) {
            return $this->apiError($result['error'] ?? 'Gagal membuat Pairing Code.', 422);
        }

        return $this->apiSuccess([
            'message' => 'Kode pairing WhatsApp berhasil dibuat.',
            'data'    => [
                'pairing_code' => $result['pairing_code'],
                'phone'        => $result['phone'],
            ],
        ]);
    }

    public function logout(): ResponseInterface
    {
        $provider = $this->getProvider();
        $result = $provider->logoutDevice();

        if (! $result['success']) {
            return $this->apiError($result['error'] ?? 'Gagal memutus sesi WhatsApp.', 422);
        }

        $status = $provider->getStatus(true);

        return $this->apiSuccess([
            'message' => $result['message'] ?? 'Sesi WhatsApp telah diputus. Silakan tautkan ulang nomor untuk menghubungkan kembali.',
            'data'    => [
                'gateway' => [
                    'configured' => (bool) ($status['configured'] ?? false),
                    'connected'  => (bool) ($status['connected'] ?? false),
                    'status'     => (string) ($status['status'] ?? 'disconnected'),
                ],
            ],
        ]);
    }
}
