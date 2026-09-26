<?php

namespace App\Controllers;

use App\Libraries\Notification\PushNotifier;
use App\Models\AnggotaModel;
use CodeIgniter\HTTP\ResponseInterface;

class NotifController extends BaseController
{
    public function simpanIdHp(): ResponseInterface
    {
        $userId = $this->memberUserId();
        if ($userId === null) {
            return $this->jsonMessage('Sesi tidak valid. Silakan login ulang.', 401);
        }

        $payload  = $this->request->getJSON(true);
        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        $keys     = is_array($payload['keys'] ?? null) ? $payload['keys'] : [];

        if ($endpoint === '' || empty($keys['p256dh']) || empty($keys['auth'])) {
            return $this->jsonMessage('Data langganan push tidak lengkap.', 422);
        }

        $db      = db_connect();
        $builder = $db->table('push_subscriptions');
        $now     = date('Y-m-d H:i:s');

        $data = [
            'user_id'    => $userId,
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

        return $this->jsonMessage('Langganan notifikasi berhasil disimpan.');
    }

    public function testNotif(): ResponseInterface
    {
        $userId = $this->memberUserId();
        if ($userId === null) {
            return $this->jsonMessage('Sesi tidak valid. Silakan login ulang.', 401);
        }

        try {
            $sent = $this->deliverToUsers(
                [$userId],
                'Notifikasi Uji Coba',
                'Perangkat ini sudah terhubung. Notifikasi jadwal rapat akan dikirim ke sini.',
                '/agenda'
            );
        } catch (\Throwable $th) {
            log_message('error', 'Gagal kirim notif uji PWA: ' . $th->getMessage());

            return $this->jsonMessage('Gagal mengirim notifikasi uji coba.', 500);
        }

        if ($sent < 1) {
            return $this->jsonMessage('Tidak ada langganan push aktif untuk akun Anda.', 404);
        }

        return $this->jsonMessage('Notifikasi uji coba berhasil dikirim.');
    }

    public function testKirimKeUser(): ResponseInterface
    {
        if (! is_array(session()->get('auth_user'))) {
            return $this->jsonMessage('Akses ditolak.', 403);
        }

        $userId = (int) ($this->request->getPost('user_id') ?: ($this->request->getJSON(true)['user_id'] ?? 0));
        if ($userId < 1) {
            return $this->jsonMessage('user_id wajib diisi.', 422);
        }

        try {
            $sent = $this->deliverToUsers(
                [$userId],
                'Notifikasi Uji Coba',
                'Ini adalah notifikasi uji coba dari panel admin.',
                '/agenda'
            );
        } catch (\Throwable $th) {
            log_message('error', 'Gagal kirim notif uji admin: ' . $th->getMessage());

            return $this->jsonMessage('Gagal mengirim notifikasi uji coba.', 500);
        }

        if ($sent < 1) {
            return $this->jsonMessage('Tidak ada langganan push aktif untuk user tersebut.', 404);
        }

        return $this->jsonMessage('Notifikasi uji coba berhasil dikirim.');
    }

    private function memberUserId(): ?int
    {
        $auth = session()->get('member_auth');
        if (! is_array($auth)) {
            return null;
        }

        $anggota = (new AnggotaModel())
            ->select('user_id')
            ->where('id', (int) ($auth['anggota_id'] ?? 0))
            ->where('aktif', 1)
            ->first();

        if ($anggota === null || (int) ($anggota['user_id'] ?? 0) < 1) {
            return null;
        }

        return (int) $anggota['user_id'];
    }

    private function deliverToUsers(array $userIds, string $title, string $message, string $url): int
    {
        return (new PushNotifier())->sendToUserIds($userIds, $title, $message, $url);
    }

    private function jsonMessage(string $message, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'status'  => $status === 200 ? 'success' : 'error',
            'message' => $message,
        ]);
    }
}
