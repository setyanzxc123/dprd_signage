<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class PublicController extends BaseController
{
    /**
     * GET api/v1/publik/jadwal
     * GET api/v1/publik/jadwal?date=YYYY-MM-DD
     *
     * Mengembalikan jadwal yang is_publik = 1
     * Default: hari ini. Bisa difilter dengan query param ?date=
     */
    public function jadwal()
    {
        // Otomatis perbarui status semua rapat berdasarkan waktu saat ini
        (new \App\Models\JadwalModel())->autoUpdateStatuses();

        $db   = \Config\Database::connect();
        $date = $this->request->getGet('date');

        // Validasi format tanggal, fallback ke hari ini
        if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = null;
        }
        $date = $date ?? date('Y-m-d');

        $jadwal = $db->table('jadwal j')
            ->select('
                j.id,
                j.judul,
                j.keterangan,
                j.tanggal,
                j.waktu_mulai,
                j.waktu_selesai,
                j.status,
                j.stream_url,
                j.jenis,
                r.name AS nama_ruangan
            ')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.tanggal', $date)
            ->where('j.is_publik', 1)
            ->orderBy('j.waktu_mulai', 'ASC')
            ->get()
            ->getResultArray();

        $targetMap = $this->targetNamesByJadwalIds(array_column($jadwal, 'id'));

        foreach ($jadwal as &$j) {
            $j['waktu_mulai']   = substr($j['waktu_mulai'],   0, 5);
            $j['waktu_selesai'] = substr($j['waktu_selesai'], 0, 5);
            $j['ruangan']       = $j['nama_ruangan'] ?? '-';
            $j['komisi']        = $targetMap[$j['id']] ?? '';
            $j['has_stream']    = !empty($j['stream_url']);
            unset($j['nama_ruangan']);
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setJSON([
                'status' => 'success',
                'date'   => $date,
                'data'   => $jadwal,
            ]);
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
}
