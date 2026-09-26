<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Notification\NotificationService;
use CodeIgniter\HTTP\ResponseInterface;

class NotificationController extends BaseController
{
    private NotificationService $service;

    public function __construct(?NotificationService $service = null)
    {
        $this->service = $service ?? new NotificationService();
    }

    /**
     * Endpoint polling feed notifikasi dan aktivitas sistem untuk Web Admin Topbar.
     * Parameter fresh=1 melewati cache mikro untuk refresh manual.
     */
    public function feed(): ResponseInterface
    {
        $fresh = strtolower((string) $this->request->getGet('fresh')) === '1';

        $data = $this->service->getFeed($fresh);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Menyimpan langganan push browser admin. Payload berupa objek
     * PushSubscription dari browser: {endpoint, keys: {p256dh, auth}}.
     */
    public function aktifkanPush(): ResponseInterface
    {
        $authUser = session()->get('auth_user');
        if (! is_array($authUser) || (int) ($authUser['id'] ?? 0) < 1) {
            return $this->jsonError('Sesi tidak valid. Silakan login ulang.', 401);
        }

        $payload  = $this->request->getJSON(true);
        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        $keys     = is_array($payload['keys'] ?? null) ? $payload['keys'] : [];

        if ($endpoint === '' || empty($keys['p256dh']) || empty($keys['auth'])) {
            return $this->jsonError('Data langganan push tidak lengkap.', 422);
        }

        $db      = db_connect();
        $builder = $db->table('push_subscriptions');
        $now     = date('Y-m-d H:i:s');

        $data = [
            'user_id'    => (int) $authUser['id'],
            'p256dh'     => (string) $keys['p256dh'],
            'auth'       => (string) $keys['auth'],
            'updated_at' => $now,
        ];

        $existing = $builder->select('id')->where('endpoint', $endpoint)->get()->getRowArray();
        if ($existing !== null) {
            $builder->where('id', $existing['id'])->update($data);
        } else {
            $builder->insert($data + ['endpoint' => $endpoint, 'created_at' => $now]);
        }

        return $this->response->setJSON([
            'status'  => 200,
            'message' => 'Notifikasi browser admin aktif.',
        ]);
    }

    private function jsonError(string $message, int $status): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'status'  => $status,
            'message' => $message,
        ]);
    }
}
