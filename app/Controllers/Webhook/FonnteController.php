<?php

namespace App\Controllers\Webhook;

use App\Controllers\BaseController;
use App\Libraries\WhatsApp\WhatsappGateway;

class FonnteController extends BaseController
{
    public function status()
    {
        if (trim((string) env('FONNTE_WEBHOOK_SECRET', '')) === '') {
            return $this->response->setStatusCode(503)->setJSON([
                'status'  => false,
                'message' => 'Webhook secret belum dikonfigurasi.',
            ]);
        }

        if (! $this->hasValidSecret()) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => false,
                'message' => 'Webhook tidak terautentikasi.',
            ]);
        }

        $rawPayload = (string) $this->request->getBody();
        $payload = json_decode($rawPayload, true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => false,
                'message' => 'Payload webhook tidak valid.',
            ]);
        }

        $gateway = new WhatsappGateway();
        $event = $gateway->parseStatusWebhook($payload);
        if ($event === null) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => false,
                'message' => 'ID pesan atau state ID tidak tersedia.',
            ]);
        }

        $db = db_connect();
        if (! $db->tableExists('whatsapp_webhook_events')) {
            return $this->response->setStatusCode(503)->setJSON([
                'status'  => false,
                'message' => 'Penyimpanan webhook belum tersedia.',
            ]);
        }

        $eventHash = hash('sha256', implode('|', [
            $gateway->providerName(),
            $event->messageId ?? '',
            $event->stateId ?? '',
            $event->status ?? '',
            $event->state ?? '',
            $event->device ?? '',
        ]));

        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $db->table('whatsapp_webhook_events')->ignore(true)->insert([
            'provider'            => $gateway->providerName(),
            'event_hash'          => $eventHash,
            'provider_message_id' => $event->messageId,
            'state_id'            => $event->stateId,
            'status'              => $event->status,
            'raw_payload'         => $rawPayload,
            'received_at'         => $now,
        ]);

        if ($db->affectedRows() === 0) {
            $db->transComplete();

            return $this->response->setJSON([
                'status' => true,
                'result' => 'duplicate',
            ]);
        }

        $gateway->applyStatus($event);
        $db->table('whatsapp_webhook_events')
            ->where('provider', $gateway->providerName())
            ->where('event_hash', $eventHash)
            ->update(['processed_at' => $now]);
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => false,
                'message' => 'Webhook gagal diproses.',
            ]);
        }

        return $this->response->setJSON([
            'status' => true,
            'result' => 'processed',
        ]);
    }

    private function hasValidSecret(): bool
    {
        $expected = trim((string) env('FONNTE_WEBHOOK_SECRET', ''));
        $provided = trim($this->request->getHeaderLine('X-Fonnte-Webhook-Secret'));
        if ($provided === '') {
            $provided = trim((string) $this->request->getGet('secret'));
        }

        return $provided !== '' && hash_equals($expected, $provided);
    }
}
