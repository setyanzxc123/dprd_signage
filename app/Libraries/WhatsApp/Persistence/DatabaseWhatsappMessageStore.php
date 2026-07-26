<?php

namespace App\Libraries\WhatsApp\Persistence;

use App\Libraries\WhatsApp\Contracts\WhatsappMessageStoreInterface;
use App\Libraries\WhatsApp\ValueObjects\SendResult;
use App\Libraries\WhatsApp\ValueObjects\StatusWebhookEvent;
use CodeIgniter\Database\BaseConnection;

final class DatabaseWhatsappMessageStore implements WhatsappMessageStoreInterface
{
    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db;
    }

    private ?BaseConnection $db;

    public function recordSend(string $provider, string $target, string $messageType, SendResult $result): void
    {
        $db = $this->db();
        if (! $db->tableExists('whatsapp_messages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $db->table('whatsapp_messages')->insert([
            'provider'            => $provider,
            'provider_message_id' => $result->messageId,
            'provider_request_id' => $result->requestId,
            'target'              => $target,
            'message_type'        => $messageType,
            'status'              => $result->status,
            'detail'              => $result->detail,
            'last_error'          => $result->error,
            'raw_response'        => $result->rawResponse,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
    }

    public function applyStatus(string $provider, StatusWebhookEvent $event): void
    {
        $db = $this->db();
        if (! $db->tableExists('whatsapp_messages')) {
            return;
        }

        $builder = $db->table('whatsapp_messages')->where('provider', $provider);
        if ($event->messageId !== null) {
            $builder->where('provider_message_id', $event->messageId);
        } elseif ($event->stateId !== null) {
            $builder->where('state_id', $event->stateId);
        } else {
            return;
        }

        $changes = [
            'state_id'   => $event->stateId,
            'state'      => $event->state,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($event->status !== null) {
            $changes['status'] = strtolower($event->status);
        }

        $builder->update($changes);
    }

    private function db(): BaseConnection
    {
        return $this->db ??= db_connect();
    }
}
