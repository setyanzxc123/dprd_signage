<?php

namespace App\Commands;

use App\Libraries\WhatsappService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * SendWaNotifications
 *
 * Spark Command untuk mengirim notifikasi WhatsApp pending.
 * Logika inti ada di WhatsappService::sendPendingNotifications()
 * agar bisa dipanggil dari HTTP context tanpa dependency BaseCommand.
 *
 * Dipanggil via:
 *   - CLI / cron OS  : php spark wa:send-notifications
 *   - HTTP pseudo-cron: Api\SignageController::_triggerWaNotifications()
 *   - HTTP endpoint  : CronController::sendWaNotifications()
 */
class SendWaNotifications extends BaseCommand
{
    protected $group       = 'Notifikasi';
    protected $name        = 'wa:send-notifications';
    protected $description = 'Kirim notifikasi WhatsApp pending yang sudah melewati reminder_time.';

    public function run(array $params): void
    {
        $result = (new WhatsappService())->sendPendingNotifications();

        if ($result['total'] === 0) {
            // Tidak ada yang perlu dikirim — diam (agar cron log tidak berisik)
            return;
        }

        // Tampilkan output saat dipanggil via CLI
        if (is_cli()) {
            CLI::write(
                "[WA Notif] {$result['total']} diproses — {$result['sent']} terkirim, {$result['failed']} gagal.",
                'green'
            );
        }
    }
}
