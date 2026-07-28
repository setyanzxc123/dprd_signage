<?php

namespace App\Controllers\Webhook;

use App\Controllers\BaseController;

final class FazpassController extends BaseController
{
    public function status()
    {
        $secret = trim((string) env('FAZPASS_CALLBACK_SECRET', ''));
        $provided = trim($this->request->getHeaderLine('X-Fazpass-Callback-Secret'));
        if ($secret === '' || $provided === '' || ! hash_equals($secret, $provided)) {
            return $this->response->setStatusCode(401)->setJSON(['status' => false, 'message' => 'Webhook tidak terautentikasi.']);
        }

        $raw = (string) $this->request->getBody();
        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(422)->setJSON(['status' => false, 'message' => 'Payload webhook tidak valid.']);
        }

        $transactionId = $this->string($payload['transaction_id'] ?? null);
        $otpId = $this->string($payload['otp_id'] ?? null);
        $status = strtolower($this->string($payload['status'] ?? null) ?? '');
        if ($transactionId === null && $otpId === null) {
            return $this->response->setStatusCode(422)->setJSON(['status' => false, 'message' => 'ID transaksi tidak tersedia.']);
        }

        $db = db_connect();
        if (! $db->tableExists('member_otps')) {
            return $this->response->setStatusCode(503)->setJSON(['status' => false, 'message' => 'Penyimpanan OTP belum tersedia.']);
        }
        $hash = hash('sha256', implode('|', ['fazpass', $transactionId ?? '', $otpId ?? '', $status]));
        $now = date('Y-m-d H:i:s');
        if ($db->tableExists('otp_webhook_events')) {
            $db->table('otp_webhook_events')->ignore(true)->insert([
                'provider' => 'fazpass', 'event_hash' => $hash,
                'provider_message_id' => $otpId ?? $transactionId,
                'status' => $status, 'raw_payload' => $raw,
                'received_at' => $now,
            ]);
            if ($db->affectedRows() === 0) {
                return $this->response->setJSON(['status' => true, 'result' => 'duplicate']);
            }
        }

        $builder = $db->table('member_otps')->where('provider', 'fazpass');
        if ($transactionId !== null && $otpId !== null) {
            $builder->groupStart()->where('provider_transaction_id', $transactionId)->orWhere('provider_otp_id', $otpId)->groupEnd();
        } elseif ($transactionId !== null) {
            $builder->where('provider_transaction_id', $transactionId);
        } else {
            $builder->where('provider_otp_id', $otpId);
        }
        $normalized = match ($status) {
            'delivered', 'verified' => 'delivered',
            'sent' => 'sent',
            'processing', 'pending' => 'pending',
            'expired' => 'expired',
            default => in_array($status, ['error', 'failed', 'rejected', 'undelivered'], true) ? 'failed' : null,
        };
        if ($normalized !== null) {
            $builder->set(['delivery_status' => $normalized, 'updated_at' => $now])->update();
        }

        return $this->response->setJSON(['status' => true, 'result' => 'processed']);
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
