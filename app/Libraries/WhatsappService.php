<?php

namespace App\Libraries;

use App\Models\SettingModel;

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
    private const DEFAULT_REMINDER_TEMPLATE = "*Undangan Rapat DPRD*\n\nYth. {nama_peserta},\n\nAnda diundang menghadiri rapat:\n*{judul_rapat}*\n\nTanggal: {tanggal}\nWaktu: {waktu_mulai} - {waktu_selesai} WITA\nLokasi: {ruangan}\nPeserta: {unit_rapat}\n\n{catatan}\n{link_berkas}\n\n_Pesan otomatis dari {sender_name}_";

    private const TEMPLATE_PLACEHOLDERS = [
        'nama_peserta' => 'Nama penerima',
        'judul_rapat' => 'Judul rapat',
        'tanggal' => 'Tanggal rapat',
        'waktu_mulai' => 'Jam mulai',
        'waktu_selesai' => 'Jam selesai',
        'ruangan' => 'Ruang atau lokasi',
        'unit_rapat' => 'Target peserta',
        'catatan' => 'Keterangan rapat',
        'link_jadwal' => 'Link portal jadwal',
        'link_berkas' => 'Link materi rapat',
        'sender_name' => 'Nama pengirim',
    ];

    private string $apiUrl = 'https://api.fonnte.com/send';
    private string $token = '';

    public function __construct()
    {
        // Token dikelola di .env, bukan di tabel settings.
        $this->token = env('WA_API_KEY') ?: '';
    }

    public static function defaultReminderTemplate(): string
    {
        return self::DEFAULT_REMINDER_TEMPLATE;
    }

    public static function templatePlaceholders(): array
    {
        return self::TEMPLATE_PLACEHOLDERS;
    }

    public static function findUnknownPlaceholders(string $template): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $template, $matches);

        $used = array_unique($matches[1] ?? []);
        $allowed = array_keys(self::TEMPLATE_PLACEHOLDERS);
        $unknown = array_values(array_diff($used, $allowed));
        sort($unknown);

        return $unknown;
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
                'success' => false,
                'response' => null,
                'error' => 'Layanan WhatsApp belum dikonfigurasi.',
            ];
        }

        // Normalisasi nomor ke format 628xxx sebelum dikirim
        $noWa = $this->normalizePhone($noWa);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'target' => $noWa,
                'message' => $message,
                // Nomor sudah format 628xxx — countryCode tidak diperlukan
                'countryCode' => '62',
            ],
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->token,
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'response' => $rawResponse ?: null,
                'error' => 'Gagal menghubungi server WhatsApp.',
            ];
        }

        $decoded = json_decode($rawResponse, true);

        $error = null;
        if (($decoded['status'] ?? false) === false) {
            $reason = strtolower($decoded['reason'] ?? '');
            if (str_contains($reason, 'token')) {
                $error = 'Autentikasi layanan gagal.';
            } elseif (str_contains($reason, 'device') || str_contains($reason, 'disconnect')) {
                $error = 'Perangkat WhatsApp pengirim tidak terhubung.';
            } else {
                $error = $decoded['reason'] ?? 'Gagal mengirim pesan.';
            }
        }

        return [
            'success' => ($decoded['status'] ?? false) === true,
            'response' => $rawResponse,
            'error' => $error,
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
        $settings = new SettingModel();

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
                j.lokasi_lainnya,
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

        $sent = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $message = self::buildMessage($row);
            $result = $this->send($row['no_wa'], $message);

            $updateData = [
                'executed_at' => date('Y-m-d H:i:s'),
                'message' => $message,
                'retry_count' => (int) $row['retry_count'] + 1,
            ];

            if ($result['success']) {
                $updateData['status'] = 'sent';
                $sent++;
            } else {
                $updateData['status'] = 'failed';
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
        return self::buildTemplateMessage($data);
    }

    private static function buildLegacyMessage(array $data): string
    {
        $tanggal = date('d F Y', strtotime($data['tanggal'] ?? ''));
        $mulai = substr($data['waktu_mulai'] ?? '', 0, 5);
        $selesai = substr($data['waktu_selesai'] ?? '', 0, 5);
        $komisi = trim((string) ($data['target_peserta'] ?? ''));
        $komisi = $komisi !== '' ? $komisi : '-';
        $lokasi = trim((string) ($data['lokasi_lainnya'] ?? ''));
        $lokasi = $lokasi !== '' ? $lokasi : ($data['nama_ruangan'] ?? '-');

        $lines = [];
        $lines[] = "📋 *UNDANGAN RAPAT DPRD*";
        $lines[] = "Yth. {$data['nama_anggota']}";
        $lines[] = "";
        $lines[] = "Anda diundang menghadiri:";
        $lines[] = "📌 *{$data['judul']}*";
        $lines[] = "📅 {$tanggal} | ⏰ {$mulai} – {$selesai} WITA";
        $lines[] = "🏛️ Lokasi: {$lokasi}";
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

    private static function buildTemplateMessage(array $data): string
    {
        $settings = new SettingModel();
        
        $isDefault = (bool) $settings->getValue('wa_template_default_aktif', '1');
        if ($isDefault) {
            $template = self::DEFAULT_REMINDER_TEMPLATE;
        } else {
            $template = trim((string) $settings->getValue('wa_template_reminder', self::DEFAULT_REMINDER_TEMPLATE));
            $template = $template !== '' ? $template : self::DEFAULT_REMINDER_TEMPLATE;
        }

        $tanggalRaw = $data['tanggal'] ?? '';
        $timestamp = $tanggalRaw !== '' ? strtotime($tanggalRaw) : false;
        $tanggal = $timestamp ? date('d F Y', $timestamp) : '-';
        $mulai = substr($data['waktu_mulai'] ?? '', 0, 5) ?: '-';
        $selesai = substr($data['waktu_selesai'] ?? '', 0, 5) ?: '-';
        $unitRapat = trim((string) ($data['target_peserta'] ?? ''));
        $unitRapat = $unitRapat !== '' ? $unitRapat : '-';
        $lokasi = trim((string) ($data['lokasi_lainnya'] ?? ''));
        $lokasi = $lokasi !== '' ? $lokasi : ($data['nama_ruangan'] ?? '-');
        $catatan = trim((string) ($data['keterangan'] ?? ''));
        $materiUrl = trim((string) ($data['materi_url'] ?? ''));

        $values = [
            'nama_peserta' => $data['nama_anggota'] ?? 'Bapak/Ibu',
            'judul_rapat' => $data['judul'] ?? '-',
            'tanggal' => $tanggal,
            'waktu_mulai' => $mulai,
            'waktu_selesai' => $selesai,
            'ruangan' => $lokasi,
            'unit_rapat' => $unitRapat,
            'catatan' => $catatan,
            'link_jadwal' => base_url('jadwal'),
            'link_berkas' => $materiUrl !== '' ? 'Materi: ' . $materiUrl : '',
            'sender_name' => $settings->getValue('wa_sender_name', 'Sekretariat DPRD') ?: 'Sekretariat DPRD',
        ];

        return trim(self::renderTemplate($template, $values));
    }

    private static function renderTemplate(string $template, array $values): string
    {
        $message = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', static function (array $match) use ($values): string {
            return (string) ($values[$match[1]] ?? $match[0]);
        }, $template);

        $message = preg_replace("/[ \t]+\n/", "\n", $message ?? '');
        $message = preg_replace("/\n{3,}/", "\n\n", $message ?? '');

        return $message ?? '';
    }
}
