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
                      j.status, j.materi_url, j.lokasi_lainnya,
                      r.name AS nama_ruangan')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.tanggal', $today)
            ->orderBy('j.waktu_mulai', 'ASC')
            ->get()->getResultArray();

        $targetMap = $this->targetNamesByJadwalIds(array_column($jadwals, 'id'));

        $meetings = [];
        foreach ($jadwals as $j) {
            $meetings[] = [
                'id'         => $j['id'],
                'start'      => substr($j['waktu_mulai'], 0, 5),
                'end'        => substr($j['waktu_selesai'], 0, 5),
                'title'      => $j['judul'],
                'subtitle'   => $j['keterangan'] ?? '',
                'room'       => $this->displayLocation($j),
                'group'      => $targetMap[$j['id']] ?? '-',
                'status'     => $j['status'],
                'detail_url' => base_url("admin/jadwal/{$j['id']}/edit"),
                'edit_url'   => base_url("admin/jadwal/{$j['id']}/edit"),
            ];
        }

        return view('admin/dashboard/index', [
            'pageTitle'   => 'Dashboard',
            'breadcrumbs' => [],
            'stats'       => [
                'rapat_hari_ini' => $rapatHariIni,
                'wa_terkirim'    => $waTerkirim,
                'wa_gagal'       => $waGagal,
            ],
            'meetings'    => $meetings,
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
