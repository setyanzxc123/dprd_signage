<?php

namespace App\Controllers\Webhook;

use App\Controllers\BaseController;
use App\Libraries\Otp\FazpassWebhookProcessor;
use App\Libraries\Otp\Persistence\DatabaseOtpRepository;
use Config\Otp;

final class FazpassController extends BaseController
{
    public function status()
    {
        $secret = (new Otp())->fazpassCallbackSecret;
        $provided = trim($this->request->getHeaderLine('X-Fazpass-Callback-Secret'));
        if ($secret === '' || $provided === '' || ! hash_equals($secret, $provided)) {
            return $this->response->setStatusCode(401)->setJSON(['status' => false, 'message' => 'Webhook tidak terautentikasi.']);
        }

        $raw = (string) $this->request->getBody();
        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(422)->setJSON(['status' => false, 'message' => 'Payload webhook tidak valid.']);
        }

        $otpId = $this->string($payload['otp_id'] ?? null);
        $transactionId = $this->string($payload['transaction_id'] ?? null);
        $status = strtolower($this->string($payload['status'] ?? null) ?? '');
        $service = strtolower($this->string($payload['service'] ?? null) ?? 'otp');
        if ($otpId === null && $transactionId === null) {
            return $this->response->setStatusCode(422)->setJSON(['status' => false, 'message' => 'ID OTP tidak tersedia.']);
        }
        if ($service !== 'otp') {
            return $this->response->setStatusCode(422)->setJSON(['status' => false, 'message' => 'Service webhook tidak valid.']);
        }
        if (FazpassWebhookProcessor::normalizeStatus($status) === null) {
            return $this->response->setStatusCode(422)->setJSON(['status' => false, 'message' => 'Status webhook tidak valid.']);
        }

        $db = db_connect();
        if (! $db->tableExists('member_otps')) {
            return $this->response->setStatusCode(503)->setJSON(['status' => false, 'message' => 'Penyimpanan OTP belum tersedia.']);
        }

        $result = (new FazpassWebhookProcessor(new DatabaseOtpRepository($db)))
            ->process($otpId, $transactionId, $status);
        if ($result === FazpassWebhookProcessor::NOT_FOUND) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => false,
                'result' => FazpassWebhookProcessor::NOT_FOUND,
            ]);
        }

        return $this->response->setJSON(['status' => true, 'result' => $result]);
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
