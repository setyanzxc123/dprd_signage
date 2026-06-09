<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\WhatsappService;
use App\Models\SettingModel;

class SignageController extends BaseController
{
    /**
     * GET api/signage/jadwal
     * Mengembalikan JSON daftar jadwal rapat hari ini beserta nama ruangan dan QR URL.
     */
    public function jadwal()
    {
        // ── Pseudo Cron: trigger cek notifikasi WA ───────────────────────
        // Layar signage sudah polling endpoint ini setiap 1 menit.
        // Manfaatkan request yang ada sebagai scheduler notifikasi.
        $this->_triggerWaNotifications();
        // ────────────────────────────────────────────────────────────────

        // Otomatis perbarui status semua rapat berdasarkan waktu saat ini
        (new \App\Models\JadwalModel())->autoUpdateStatuses();

        $db    = \Config\Database::connect();
        $today = date('Y-m-d');

        $jadwal = $db->table('jadwal j')
            ->select('
                j.id,
                j.judul,
                j.keterangan,
                j.tanggal,
                j.waktu_mulai,
                j.waktu_selesai,
                j.komisi_target,
                j.status,
                j.materi_url,
                j.stream_url,
                r.name AS nama_ruangan
            ')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.tanggal', $today)
            ->where('j.is_publik', 1)
            ->orderBy('j.waktu_mulai', 'ASC')
            ->get()
            ->getResultArray();

        // Format data dan tambahkan qr_url otomatis
        foreach ($jadwal as &$j) {
            // Waktu format HH:MM
            $j['waktu_mulai']   = substr($j['waktu_mulai'],   0, 5);
            $j['waktu_selesai'] = substr($j['waktu_selesai'], 0, 5);

            // Nama ruangan fallback
            $j['ruangan'] = $j['nama_ruangan'] ?? '-';
            unset($j['nama_ruangan']);

            // Decode komisi_target dari JSON
            $j['komisi'] = implode(', ', json_decode($j['komisi_target'] ?? '[]', true));
            unset($j['komisi_target']);

            // QR dirender di frontend signage menggunakan URL pendek:
            // /go/jadwal/{id}/berkas atau /go/jadwal/{id}/live.
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON([
                'date'   => $today,
                'jadwal' => $jadwal,
            ]);
    }

    /**
     * Pseudo Cron — trigger pengiriman notifikasi WA pending.
     * Dipanggil setiap kali layar signage polling jadwal (interval 1 menit).
     * Hanya aktif jika wa_notif_aktif = 1 di settings.
     */
    private function _triggerWaNotifications(): void
    {
        $settingModel = new SettingModel();
        $aktif        = $settingModel->getValue('wa_notif_aktif', '0');

        if ($aktif !== '1') {
            return;
        }

        // Panggil langsung ke WhatsappService — tidak melalui BaseCommand
        // agar tidak terjadi ArgumentCountError saat dipanggil via HTTP
        (new WhatsappService())->sendPendingNotifications();
    }

    /**
     * GET api/signage/cuaca
     * Mengembalikan prakiraan cuaca BMKG untuk waktu saat ini.
     * Response di-cache ke file selama 30 menit agar tidak melebihi limit API BMKG.
     * Wajib: tampilkan atribusi "Sumber: BMKG" di UI.
     */
    public function cuaca()
    {
        $cacheFile = WRITEPATH . 'cache/bmkg_cuaca.json';
        $cacheTTL  = 1800; // 30 menit

        // Cek cache — gunakan jika masih segar
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached) {
                $cached['from_cache'] = true;
                return $this->response
                    ->setHeader('Cache-Control', 'no-store')
                    ->setJSON($cached);
            }
        }

        // Ambil kode wilayah dari .env (prioritas) atau database settings (fallback)
        $settingModel = new \App\Models\SettingModel();
        $adm4 = env('BMKG_ADM4') ?: ($settingModel->getValue('bmkg_adm4') ?: '72.71.01.1004');

        $url  = 'https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4=' . urlencode($adm4);
        $body = @file_get_contents($url);

        if ($body === false) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Gagal mengambil data BMKG.',
            ]);
        }

        $data = json_decode($body, true);
        if (!$data) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Respons BMKG bukan JSON valid.',
            ]);
        }

        // Flatten semua slot prakiraan dari 3 hari
        $lokasi      = $data['lokasi'] ?? [];
        $allForecast = [];
        foreach ($data['data'][0]['cuaca'] ?? [] as $harian) {
            foreach ($harian as $slot) {
                $allForecast[] = $slot;
            }
        }

        // Cari slot prakiraan yang paling dekat dengan waktu lokal sekarang
        $nowTs   = time();
        $current = null;
        $minDiff = PHP_INT_MAX;
        foreach ($allForecast as $slot) {
            $slotTs = strtotime($slot['local_datetime'] ?? '');
            if (!$slotTs) continue;
            $diff = abs($nowTs - $slotTs);
            if ($diff < $minDiff) { $minDiff = $diff; $current = $slot; }
        }

        if (!$current) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Tidak ada slot prakiraan tersedia.',
            ]);
        }

        $result = [
            'status'  => 'success',
            'lokasi'  => [
                'desa'      => $lokasi['desa']      ?? '-',
                'kecamatan' => $lokasi['kecamatan'] ?? '-',
                'kotkab'    => $lokasi['kotkab']    ?? '-',
                'provinsi'  => $lokasi['provinsi']  ?? '-',
            ],
            'cuaca' => [
                'suhu'          => ($current['t']    ?? '-') . '°C',
                'suhu_raw'      => $current['t']     ?? null,
                'kondisi'       => $current['weather_desc']    ?? '-',
                'kondisi_en'    => $current['weather_desc_en'] ?? '-',
                'kelembapan'    => ($current['hu']   ?? '-') . '%',
                'kec_angin'     => ($current['ws']   ?? '-') . ' km/j',
                'arah_angin'    => $current['wd']    ?? '-',
                'jarak_pandang' => $current['vs_text'] ?? '-',
                'icon_url'      => !empty($current['image'])
                    ? str_replace(' ', '%20', $current['image']) : '',
                'waktu_lokal'   => $current['local_datetime'] ?? '',
            ],
            'cached_at'   => date('Y-m-d H:i:s'),
            'from_cache'  => false,
            'attribution' => 'Sumber: BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)',
        ];

        // Simpan ke cache
        @file_put_contents($cacheFile, json_encode($result));

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON($result);
    }
}
