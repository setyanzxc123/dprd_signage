<?php

namespace App\Libraries\Notification;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Pengirim browser push notification terpusat berbasis VAPID.
 * Seluruh pengiriman (anggota maupun admin) melewati kelas ini agar
 * konfigurasi dan payload tidak terduplikasi.
 */
final class PushNotifier
{
    private const ADMIN_GROUP_NAMES = ['superadmin', 'operator'];

    /**
     * Mengirim push ke seluruh langganan milik user id yang diberikan.
     * Mengembalikan jumlah pengiriman yang berhasil.
     */
    public function sendToUserIds(array $userIds, string $title, string $message, string $url): int
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds === []) {
            return 0;
        }

        $subscriptions = db_connect()->table('push_subscriptions')
            ->whereIn('user_id', $userIds)
            ->get()
            ->getResultArray();
        if ($subscriptions === []) {
            return 0;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject'    => env('VAPID_SUBJECT'),
                    'publicKey'  => env('VAPID_PUBLIC_KEY'),
                    'privateKey' => env('VAPID_PRIVATE_KEY'),
                ],
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Push: konfigurasi VAPID tidak valid. {message}', ['message' => $e->getMessage()]);

            return 0;
        }

        $payload = json_encode(['title' => $title, 'message' => $message, 'url' => $url]);
        $sent    = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $report = $webPush->sendOneNotification(Subscription::create([
                    'endpoint' => $subscription['endpoint'],
                    'keys'     => [
                        'p256dh' => $subscription['p256dh'],
                        'auth'   => $subscription['auth'],
                    ],
                ]), $payload);

                if ($report->isSuccess()) {
                    $sent++;
                }
            } catch (Throwable $e) {
                log_message('error', 'Push: gagal mengirim ke {endpoint}. {message}', [
                    'endpoint' => $subscription['endpoint'],
                    'message'  => $e->getMessage(),
                ]);
            }
        }

        try {
            $webPush->flush();
        } catch (Throwable $e) {
            log_message('error', 'Push: gagal flush antrean. {message}', ['message' => $e->getMessage()]);
        }

        return $sent;
    }

    /**
     * Id user yang berlangganan push dan termasuk grup admin.
     */
    public function adminSubscriberIds(): array
    {
        $rows = db_connect()->table('push_subscriptions ps')
            ->select('ps.user_id')
            ->join('auth_groups_users agu', 'agu.user_id = ps.user_id')
            ->whereIn('agu.group', self::ADMIN_GROUP_NAMES)
            ->groupBy('ps.user_id')
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'user_id'));
    }
}
