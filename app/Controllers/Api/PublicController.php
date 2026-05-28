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
                j.komisi_target,
                j.status,
                j.stream_url,
                r.name AS nama_ruangan
            ')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.tanggal', $date)
            ->where('j.is_publik', 1)
            ->orderBy('j.waktu_mulai', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($jadwal as &$j) {
            $j['waktu_mulai']   = substr($j['waktu_mulai'],   0, 5);
            $j['waktu_selesai'] = substr($j['waktu_selesai'], 0, 5);
            $j['ruangan']       = $j['nama_ruangan'] ?? '-';
            $j['komisi']        = implode(', ', json_decode($j['komisi_target'] ?? '[]', true));
            $j['has_stream']    = !empty($j['stream_url']);
            unset($j['nama_ruangan'], $j['komisi_target']);
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
}
