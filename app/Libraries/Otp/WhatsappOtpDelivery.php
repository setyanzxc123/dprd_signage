<?php

namespace App\Libraries\Otp;

use App\Libraries\Otp\Contracts\OtpDeliveryInterface;
use App\Libraries\Otp\ValueObjects\OtpDeliveryResult;
use App\Libraries\WhatsappService;
use App\Libraries\WhatsApp\WhatsappGateway;

final class WhatsappOtpDelivery implements OtpDeliveryInterface
{
    public function __construct(?WhatsappGateway $gateway = null)
    {
        $this->gateway = $gateway ?? new WhatsappGateway();
    }

    private readonly WhatsappGateway $gateway;

    public function send(string $phone, string $code, int $ttlSeconds): OtpDeliveryResult
    {
        $target = WhatsappService::normalizePhone($phone);
        if (! WhatsappService::isValidIndonesianPhone($target)) {
            return new OtpDeliveryResult('failed', error: 'Nomor WhatsApp tujuan tidak valid.');
        }

        $minutes = max(1, (int) ceil($ttlSeconds / 60));
        $message = "Kode OTP login DPRD Anda: {$code}. Berlaku {$minutes} menit. Jangan berikan kode ini kepada siapa pun.";
        $result = $this->gateway->send($target, $message, 'otp');

        // Tanpa respons provider, kegagalan jaringan dapat berarti pesan tetap diterima.
        $status = $result->success
            ? ($result->status === 'sent' ? 'sent' : 'pending')
            : ($result->rawResponse === null ? 'ambiguous' : 'failed');

        return new OtpDeliveryResult(
            $status,
            $this->gateway->providerName(),
            $result->messageId,
            $result->requestId,
            $result->error,
        );
    }
}
