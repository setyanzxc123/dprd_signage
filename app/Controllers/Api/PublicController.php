<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class PublicController extends BaseController
{
    /**
     * GET api/v1/publik/jadwal
     * GET api/v1/publik/jadwal?date=YYYY-MM-DD
     * GET api/v1/publik/jadwal?month=YYYY-MM
     *
     * Mengembalikan jadwal yang is_publik = 1
     * Default: hari ini. Bisa difilter dengan query param ?date= atau ?month=
     */
    public function jadwal()
    {
        // Otomatis perbarui status semua rapat berdasarkan waktu saat ini
        (new \App\Models\JadwalModel())->autoUpdateStatuses();

        $db    = \Config\Database::connect();
        $date  = $this->request->getGet('date');
        $month = $this->request->getGet('month');

        // Validasi format tanggal, fallback ke hari ini
        if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = null;
        }
        if ($month && !preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = null;
        }

        $builder = $db->table('jadwal j')
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
                j.jenis,
                j.lokasi_lainnya,
                r.name AS nama_ruangan
            ')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.is_publik', 1);

        if ($month) {
            $start = $month . '-01';
            $end   = date('Y-m-t', strtotime($start));
            $builder
                ->where('j.tanggal >=', $start)
                ->where('j.tanggal <=', $end);
        } else {
            $date = $date ?? date('Y-m-d');
            $builder->where('j.tanggal', $date);
        }

        $jadwal = $builder
            ->orderBy('j.tanggal', 'ASC')
            ->orderBy('j.waktu_mulai', 'ASC')
            ->get()
            ->getResultArray();

        $targetMap = $this->targetNamesByJadwalIds(array_column($jadwal, 'id'));

        foreach ($jadwal as &$j) {
            $j['waktu_mulai']   = substr($j['waktu_mulai'],   0, 5);
            $j['waktu_selesai'] = substr($j['waktu_selesai'], 0, 5);
            $j['ruangan']       = $this->displayLocation($j);
            $j['komisi']        = $targetMap[$j['id']] ?? '';
            $j['has_materi']    = !empty($j['materi_url']);
            $j['has_stream']    = !empty($j['stream_url']);
            unset($j['nama_ruangan'], $j['lokasi_lainnya']);
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setJSON([
                'status' => 'success',
                'date'   => $date,
                'month'  => $month,
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

    private function displayLocation(array $row): string
    {
        $other = trim((string) ($row['lokasi_lainnya'] ?? ''));
        if ($other !== '') {
            return $other;
        }

        return $row['nama_ruangan'] ?? '-';
    }
}
