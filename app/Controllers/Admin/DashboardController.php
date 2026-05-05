<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\JadwalModel;
use App\Models\NotifikasiModel;
use App\Models\RuanganModel;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $jadwalModel = new JadwalModel();
        $notifModel  = new NotifikasiModel();
        $today       = date('Y-m-d');

        // ── Statistik ─────────────────────────────────────────────────
        $rapatHariIni = $jadwalModel->where('tanggal', $today)->countAllResults();
        $waTerkirim   = $notifModel->where('status', 'sent')->countAllResults();
        $waGagal      = $notifModel->where('status', 'failed')->countAllResults();

        // ── Jadwal hari ini (JOIN ruangan) ─────────────────────────────
        $db      = \Config\Database::connect();
        $jadwals = $db->table('jadwal j')
            ->select('j.id, j.judul, j.keterangan, j.waktu_mulai, j.waktu_selesai,
                      j.komisi_target, j.status, j.materi_url,
                      r.name AS nama_ruangan')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.tanggal', $today)
            ->orderBy('j.waktu_mulai', 'ASC')
            ->get()->getResultArray();

        $meetings = [];
        foreach ($jadwals as $j) {
            $meetings[] = [
                'id'         => $j['id'],
                'start'      => substr($j['waktu_mulai'], 0, 5),
                'end'        => substr($j['waktu_selesai'], 0, 5),
                'title'      => $j['judul'],
                'subtitle'   => $j['keterangan'] ?? '',
                'room'       => $j['nama_ruangan'] ?? '-',
                'group'      => $j['komisi_target'] ?? '-',
                'status'     => $j['status'],
                'detail_url' => base_url("admin/jadwal/{$j['id']}/edit"),
                'edit_url'   => base_url("admin/jadwal/{$j['id']}/edit"),
            ];
        }

        return view('admin/dashboard/index', [
            'pageTitle' => 'Dashboard Ringkasan',
            'stats'     => [
                'rapat_hari_ini' => $rapatHariIni,
                'wa_terkirim'    => $waTerkirim,
                'wa_gagal'       => $waGagal,
            ],
            'meetings'  => $meetings,
        ]);
    }
}
