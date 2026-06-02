<?php

namespace App\Commands;

use App\Libraries\WhatsappService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * SendWaNotifications
 *
 * Spark Command untuk mengirim notifikasi WhatsApp pending.
 * Dipanggil via:
 *   - CLI / cron OS  : php spark wa:send-notifications
 *   - Pseudo cron    : dipicu otomatis dari SignageController::jadwal()
 *   - HTTP trigger   : dipicu dari CronController via /cron/wa-notif
 */
class SendWaNotifications extends BaseCommand
{
    protected $group       = 'Notifikasi';
    protected $name        = 'wa:send-notifications';
    protected $description = 'Kirim notifikasi WhatsApp pending yang sudah melewati reminder_time.';

    public function run(array $params): void
    {
        $wa = new WhatsappService();
        $db = Database::connect();

        // Ambil notifikasi pending dalam window 10 menit
        // Window mencegah notif lama (> 10 menit) ikut terkirim
        $rows = $db->query("
            SELECT
                n.id             AS notif_id,
                n.no_wa,
                n.retry_count,
                a.name           AS nama_anggota,
                j.judul,
                j.tanggal,
                j.waktu_mulai,
                j.waktu_selesai,
                j.keterangan,
                j.komisi_target,
                j.materi_url,
                r.name           AS nama_ruangan
            FROM notifikasi n
            JOIN jadwal  j ON j.id = n.jadwal_id
            JOIN anggota a ON a.id = n.anggota_id
            LEFT JOIN ruangan r ON r.id = j.ruangan_id
            WHERE n.status = 'pending'
              AND j.reminder_time <= NOW()
              AND j.reminder_time >= NOW() - INTERVAL 10 MINUTE
            ORDER BY n.id ASC
        ")->getResultArray();

        if (empty($rows)) {
            // Tidak ada yang perlu dikirim — diam saja (tidak log agar tidak berisik)
            return;
        }

        $sent   = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $message = WhatsappService::buildMessage($row);
            $result  = $wa->send($row['no_wa'], $message);

            $updateData = [
                'executed_at' => date('Y-m-d H:i:s'),
                'message'     => $message,
                'retry_count' => (int) $row['retry_count'] + 1,
            ];

            if ($result['success']) {
                $updateData['status'] = 'sent';
                $sent++;
            } else {
                $updateData['status']        = 'failed';
                $updateData['error_message'] = $result['error'] ?? 'Unknown error';
                $failed++;
            }

            $db->table('notifikasi')
               ->where('id', $row['notif_id'])
               ->update($updateData);
        }

        // Log ringkasan — muncul di writable/logs/log-YYYY-MM-DD.log
        $total = count($rows);
        log_message('info', "[wa:send-notifications] Diproses: {$total} | Terkirim: {$sent} | Gagal: {$failed}");

        // Jika dipanggil via CLI, tampilkan output
        if (is_cli()) {
            CLI::write("[WA Notif] {$total} diproses — {$sent} terkirim, {$failed} gagal.", 'green');
        }
    }
}
