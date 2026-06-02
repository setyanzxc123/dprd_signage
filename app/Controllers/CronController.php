<?php

namespace App\Controllers;

use App\Commands\SendWaNotifications;

/**
 * CronController
 *
 * HTTP trigger fallback untuk menjalankan notifikasi WA.
 * Digunakan saat layar signage tidak aktif (server production
 * tanpa layar TV menyala), atau dipanggil oleh layanan cron
 * eksternal seperti cron-job.org.
 *
 * Endpoint: GET /cron/wa-notif?token={CRON_SECRET_TOKEN}
 *
 * Setup cron eksternal (cron-job.org):
 *   URL    : https://domain-anda.com/cron/wa-notif?token=SECRET
 *   Interval: Setiap 1 menit
 *
 * Setup cron OS (Linux):
 *   * * * * * php /path/to/spark wa:send-notifications >> /path/to/writable/logs/cron.log 2>&1
 *
 * Setup cPanel Cron Jobs:
 *   Minute: * | Hour: * | Day: * | Month: * | Weekday: *
 *   Command: php /home/user/public_html/spark wa:send-notifications
 */
class CronController extends BaseController
{
    /**
     * GET /cron/wa-notif?token=SECRET
     */
    public function sendWaNotifications()
    {
        $token  = $this->request->getGet('token');
        $secret = env('CRON_SECRET_TOKEN', '');

        // Validasi token — tolak jika tidak cocok atau secret belum diset
        if (empty($secret) || $token !== $secret) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'error'   => 'Forbidden: token tidak valid.',
                ]);
        }

        $command = new SendWaNotifications();
        $command->run([]);

        return $this->response->setJSON([
            'success'   => true,
            'message'   => 'Notifikasi diproses.',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
}
