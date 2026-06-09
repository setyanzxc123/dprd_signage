<?php

namespace App\Libraries;

/**
 * WhatsappService
 *
 * Adapter pengiriman pesan WhatsApp via Fonnte API.
 * Token diambil dari .env agar kredensial teknis tidak dikelola dari UI/admin DB.
 *
 * Dokumentasi Fonnte: https://api.fonnte.com
 */
class WhatsappService
{
    private string $apiUrl = 'https://api.fonnte.com/send';
    private string $token  = '';

    public function __construct()
    {
        // Token dikelola di .env, bukan di tabel settings.
        $this->token = env('WA_API_KEY') ?: '';
    }

    /**
     * Kirim pesan WhatsApp ke satu nomor.
     *
     * @param  string $noWa    Nomor tujuan format 628xxx (sudah dengan kode negara)
     * @param  string $message Isi pesan (mendukung emoji & format WA bold/italic)
     * @return array{success: bool, response: string|null, error: string|null}
     */
    public function send(string $noWa, string $message): array
    {
        if (empty($this->token)) {
            return [
                'success'  => false,
                'response' => null,
                'error'    => 'WA_API_KEY belum dikonfigurasi.',
            ];
        }

        // Normalisasi nomor ke format 628xxx sebelum dikirim
        $noWa = $this->normalizePhone($noWa);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'target'      => $noWa,
                'message'     => $message,
                // Nomor sudah format 628xxx — countryCode tidak diperlukan
                'countryCode' => '62',
            ],
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->token,
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $rawResponse = curl_exec($ch);
        $curlError   = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success'  => false,
                'response' => $rawResponse ?: null,
                'error'    => 'cURL error: ' . $curlError,
            ];
        }

        $decoded = json_decode($rawResponse, true);

        return [
            'success'  => ($decoded['status'] ?? false) === true,
            'response' => $rawResponse,
            'error'    => ($decoded['status'] ?? true) === false
                ? ($decoded['reason'] ?? 'Unknown error dari Fonnte')
                : null,
        ];
    }

    /**
     * Normalisasi nomor telepon ke format internasional Indonesia (628xxx).
     * Menerima berbagai format: 08xxx, 8xxx, 628xxx, +628xxx.
     */
    private function normalizePhone(string $phone): string
    {
        // Hapus semua karakter non-digit
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '62')) {
            return $phone;           // sudah 628xxx
        }
        if (str_starts_with($phone, '08')) {
            return '62' . substr($phone, 1); // 08xxx → 628xxx
        }
        if (str_starts_with($phone, '8')) {
            return '62' . $phone;    // 8xxx → 628xxx
        }

        return $phone; // kembalikan apa adanya jika format tidak dikenali
    }

    /**
     * Proses dan kirim semua notifikasi WA pending dalam window 10 menit.
     *
     * Method ini adalah inti eksekusi yang bisa dipanggil dari:
     *   - CLI Spark Command  : App\Commands\SendWaNotifications
     *   - HTTP pseudo-cron   : Api\SignageController::_triggerWaNotifications()
     *   - HTTP cron endpoint : CronController::sendWaNotifications()
     *
     * @return array{total: int, sent: int, failed: int}
     */
    public function sendPendingNotifications(): array
    {
        $db = \Config\Database::connect();

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
                j.materi_url,
                r.name           AS nama_ruangan,
                (
                    SELECT GROUP_CONCAT(ur.nama ORDER BY ur.urutan ASC, ur.nama ASC SEPARATOR ', ')
                    FROM jadwal_unit_rapat jur
                    JOIN unit_rapat ur ON ur.id = jur.unit_rapat_id
                    WHERE jur.jadwal_id = j.id
                ) AS target_peserta
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
            return ['total' => 0, 'sent' => 0, 'failed' => 0];
        }

        $sent   = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $message    = self::buildMessage($row);
            $result     = $this->send($row['no_wa'], $message);

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

        $total = count($rows);
        log_message('info', "[WhatsappService] Diproses: {$total} | Terkirim: {$sent} | Gagal: {$failed}");

        return compact('total', 'sent', 'failed');
    }

    /**
     * Bangun teks pesan undangan rapat dari data jadwal + anggota.
     *
     * @param  array $data Gabungan data notifikasi, jadwal, anggota, ruangan
     * @return string
     */
    public static function buildMessage(array $data): string
    {
        $tanggal  = date('d F Y', strtotime($data['tanggal'] ?? ''));
        $mulai    = substr($data['waktu_mulai']   ?? '', 0, 5);
        $selesai  = substr($data['waktu_selesai'] ?? '', 0, 5);
        $komisi   = trim((string) ($data['target_peserta'] ?? ''));
        $komisi   = $komisi !== '' ? $komisi : '-';

        $lines = [];
        $lines[] = "📋 *UNDANGAN RAPAT DPRD*";
        $lines[] = "Yth. {$data['nama_anggota']}";
        $lines[] = "";
        $lines[] = "Anda diundang menghadiri:";
        $lines[] = "📌 *{$data['judul']}*";
        $lines[] = "📅 {$tanggal} | ⏰ {$mulai} – {$selesai} WITA";
        $lines[] = "🏛️ Ruangan: " . ($data['nama_ruangan'] ?? '-');
        $lines[] = "💼 Komisi: {$komisi}";

        if (!empty($data['keterangan'])) {
            $lines[] = "";
            $lines[] = $data['keterangan'];
        }

        if (!empty($data['materi_url'])) {
            $lines[] = "";
            $lines[] = "📎 Materi: {$data['materi_url']}";
        }

        $lines[] = "";
        $lines[] = "_Pesan ini dikirim otomatis oleh Sistem DPRD_";

        return implode("\n", $lines);
    }
}
