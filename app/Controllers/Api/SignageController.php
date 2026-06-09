<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\WhatsappService;
class SignageController extends BaseController
{
    /**
     * GET api/signage/jadwal
     * Mengembalikan JSON jadwal hari ini dan beberapa agenda publik berikutnya.
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

        $upcoming = $db->table('jadwal j')
            ->select('
                j.id,
                j.judul,
                j.keterangan,
                j.tanggal,
                j.waktu_mulai,
                j.waktu_selesai,
                j.status,
                j.materi_url,
                j.stream_url,
                r.name AS nama_ruangan
            ')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.tanggal >', $today)
            ->where('j.is_publik', 1)
            ->orderBy('j.tanggal', 'ASC')
            ->orderBy('j.waktu_mulai', 'ASC')
            ->limit(5)
            ->get()
            ->getResultArray();

        $targetMap = $this->targetNamesByJadwalIds(array_merge(
            array_column($jadwal, 'id'),
            array_column($upcoming, 'id')
        ));

        $jadwal   = $this->formatRows($jadwal, $targetMap);
        $upcoming = $this->formatRows($upcoming, $targetMap);

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON([
                'date'     => $today,
                'jadwal'   => $jadwal,
                'upcoming' => $upcoming,
            ]);
    }

    private function formatRows(array $rows, array $targetMap): array
    {
        foreach ($rows as &$row) {
            $row['waktu_mulai']   = substr($row['waktu_mulai'],   0, 5);
            $row['waktu_selesai'] = substr($row['waktu_selesai'], 0, 5);
            $row['ruangan']       = $row['nama_ruangan'] ?? '-';
            $row['komisi']        = $targetMap[$row['id']] ?? '';
            unset($row['nama_ruangan']);
        }

        return $rows;
    }

    /**
     * Pseudo Cron — trigger pengiriman notifikasi WA pending.
     * Dipanggil setiap kali layar signage polling jadwal (interval 1 menit).
     */
    private function _triggerWaNotifications(): void
    {
        // Panggil langsung ke WhatsappService — tidak melalui BaseCommand
        // agar tidak terjadi ArgumentCountError saat dipanggil via HTTP
        (new WhatsappService())->sendPendingNotifications();
    }

    private function targetNamesByJadwalIds(array $jadwalIds): array
    {
        $jadwalIds = array_values(array_filter(array_map('intval', $jadwalIds)));
        if (empty($jadwalIds)) {
            return [];
        }

        $rows = \Config\Database::connect()
            ->table('jadwal_unit_rapat jur')
            ->select('jur.jadwal_id, ur.nama')
            ->join('unit_rapat ur', 'ur.id = jur.unit_rapat_id')
            ->whereIn('jur.jadwal_id', $jadwalIds)
            ->orderBy('ur.urutan', 'ASC')
            ->orderBy('ur.nama', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['jadwal_id']][] = $row['nama'];
        }

        return array_map(static fn (array $names): string => implode(', ', $names), $map);
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

        // Kode wilayah BMKG dikelola di .env, bukan di tabel settings.
        $adm4 = env('BMKG_ADM4') ?: '72.71.01.1004';

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
