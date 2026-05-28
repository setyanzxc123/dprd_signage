<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class SignageController extends BaseController
{
    /**
     * GET api/signage/jadwal
     * Mengembalikan JSON daftar jadwal rapat hari ini beserta nama ruangan dan QR URL.
     */
    public function jadwal()
    {
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

            // Generate QR URL jika materi_url tersedia
            $j['qr_url'] = $j['materi_url']
                ? 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($j['materi_url'])
                : null;
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON([
                'date'   => $today,
                'jadwal' => $jadwal,
            ]);
    }
}
