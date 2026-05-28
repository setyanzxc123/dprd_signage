<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class BamusController extends BaseController
{
    /**
     * GET /admin/bamus
     * Tampilkan jadwal bamus per semester (read-only)
     */
    public function index(): string
    {
        $tahun    = (int) ($this->request->getGet('tahun')    ?? date('Y'));
        $semester = (int) ($this->request->getGet('semester') ?? (date('n') <= 6 ? 1 : 2));

        // Rentang bulan berdasarkan semester
        $bulanMulai  = $semester === 1 ? 1 : 7;
        $bulanAkhir  = $semester === 1 ? 6 : 12;
        $tglMulai    = sprintf('%04d-%02d-01', $tahun, $bulanMulai);
        $tglAkhir    = sprintf('%04d-%02d-%02d', $tahun, $bulanAkhir,
            cal_days_in_month(CAL_GREGORIAN, $bulanAkhir, $tahun));

        $db = \Config\Database::connect();
        $rows = $db->table('jadwal j')
            ->select('j.id, j.judul, j.tanggal, j.waktu_mulai, j.waktu_selesai,
                      j.komisi_target, j.status, r.name AS nama_ruangan')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.jenis', 'bamus')
            ->where('j.tanggal >=', $tglMulai)
            ->where('j.tanggal <=', $tglAkhir)
            ->orderBy('j.tanggal', 'ASC')
            ->orderBy('j.waktu_mulai', 'ASC')
            ->get()->getResultArray();

        // Group by bulan
        $byBulan = [];
        foreach ($rows as $r) {
            $bulan = (int) date('n', strtotime($r['tanggal']));
            $byBulan[$bulan][] = [
                'id'           => $r['id'],
                'judul'        => $r['judul'],
                'tanggal'      => $r['tanggal'],
                'waktu_mulai'  => substr($r['waktu_mulai'],  0, 5),
                'waktu_selesai'=> substr($r['waktu_selesai'], 0, 5),
                'ruangan'      => $r['nama_ruangan'] ?? '-',
                'komisi'       => implode(', ', json_decode($r['komisi_target'] ?? '[]', true)),
                'status'       => $r['status'],
            ];
        }

        $namaBulan = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
            5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
            9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
        ];

        return view('admin/bamus/index', [
            'pageTitle'  => 'Jadwal Bamus Semester ' . $semester . ' — ' . $tahun,
            'semester'   => $semester,
            'tahun'      => $tahun,
            'byBulan'    => $byBulan,
            'namaBulan'  => $namaBulan,
            'bulanMulai' => $bulanMulai,
            'bulanAkhir' => $bulanAkhir,
            'totalRapat' => count($rows),
        ]);
    }
}
