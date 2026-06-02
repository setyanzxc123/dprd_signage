<?php

namespace App\Libraries;

use App\Models\SettingModel;

/**
 * WhatsappService
 *
 * Adapter pengiriman pesan WhatsApp via Fonnte API.
 * Token diambil dari .env (prioritas) atau tabel settings (fallback),
 * mengikuti pola yang sama dengan BMKG_ADM4.
 *
 * Dokumentasi Fonnte: https://api.fonnte.com
 */
class WhatsappService
{
    private string $apiUrl = 'https://api.fonnte.com/send';
    private string $token  = '';

    public function __construct()
    {
        // Prioritas: .env → fallback: settings DB
        $this->token = env('WA_API_KEY')
            ?: (new SettingModel())->getValue('wa_api_key', '');
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

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'target'      => $noWa,
                'message'     => $message,
                // Nomor di DB sudah format 628xxx — nonaktifkan filter Fonnte
                'countryCode' => '0',
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
        $komisi   = is_array($k = json_decode($data['komisi_target'] ?? '[]', true))
                    ? implode(', ', $k) : '-';

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
