<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\NotifikasiModel;

class NotificationController extends BaseController
{
    private array $filters = [
        'all'     => ['label' => 'Semua',    'class' => 'ta-btn-outline-gray'],
        'sent'    => ['label' => 'Terkirim', 'class' => 'ta-btn-outline-success'],
        'failed'  => ['label' => 'Gagal',    'class' => 'ta-btn-outline-danger'],
        'pending' => ['label' => 'Pending',  'class' => 'ta-btn-outline-warning'],
    ];

    public function index(): string
    {
        $filterStatus = $this->request->getGet('status') ?? 'all';
        $notifModel   = new NotifikasiModel();
        $page         = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage      = (int) ($this->request->getGet('per_page') ?? 10);
        $perPage      = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $applyFilters = static function ($builder) use ($filterStatus) {
            if (in_array($filterStatus, ['sent', 'failed', 'pending'], true)) {
                $builder->where('notifikasi.status', $filterStatus);
            }

            return $builder;
        };

        $total = $applyFilters(
            $notifModel->builder()
                ->join('anggota', 'anggota.id = notifikasi.anggota_id', 'left')
                ->join('jadwal',  'jadwal.id  = notifikasi.jadwal_id',  'left')
        )->countAllResults();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $rows = $applyFilters(
            $notifModel->builder()
                ->select('notifikasi.*, anggota.name AS nama_anggota,
                          jadwal.judul AS judul_rapat, jadwal.tanggal AS tanggal_rapat,
                          jadwal.waktu_mulai, jadwal.waktu_selesai')
                ->join('anggota', 'anggota.id = notifikasi.anggota_id', 'left')
                ->join('jadwal',  'jadwal.id  = notifikasi.jadwal_id',  'left')
        )
            ->orderBy('notifikasi.created_at', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $notifications = [];
        foreach ($rows as $r) {
            $notifications[] = [
                'id'            => $r['id'],
                'executed_at'   => $r['executed_at']
                    ? date('l, d M Y — H:i', strtotime($r['executed_at']))
                    : null,
                'created_at'    => $r['created_at']
                    ? date('H:i, d M Y', strtotime($r['created_at']))
                    : '-',
                'nama_anggota'  => $r['nama_anggota'] ?? '-',
                'no_wa'         => $r['no_wa'] ?? '-',
                'judul_rapat'   => $r['judul_rapat'] ?? '-',
                'tanggal_rapat' => $r['tanggal_rapat']
                    ? date('d F Y', strtotime($r['tanggal_rapat']))
                    : '-',
                'waktu_rapat'   => isset($r['waktu_mulai'], $r['waktu_selesai'])
                    ? substr($r['waktu_mulai'], 0, 5) . ' – ' . substr($r['waktu_selesai'], 0, 5)
                    : '-',
                'status'        => $r['status'],
            ];
        }

        return view('admin/notifikasi/index', [
            'pageTitle'     => 'Log Notifikasi WA',
            'filter_status' => $filterStatus,
            'filters'       => $this->filters,
            'table_filters' => [
                'per_page' => $perPage,
            ],
            'notifications' => $notifications,
            'pagination'    => [
                'page'       => $page,
                'perPage'    => $perPage,
                'total'      => $total,
                'totalPages' => $totalPages,
                'from'       => $total ? $offset + 1 : 0,
                'to'         => min($offset + $perPage, $total),
            ],
        ]);
    }

    public function resend(int $id)
    {
        // WhatsappSender belum tersedia (Langkah 10) — tandai sebagai pending ulang
        $notifModel = new NotifikasiModel();
        $notifModel->update($id, [
            'status'      => 'pending',
            'executed_at' => null,
        ]);

        session()->setFlashdata('success', 'Notifikasi dijadwalkan ulang. Akan terkirim saat cron berjalan.');
        return redirect()->to(base_url('admin/notifikasi'));
    }

    public function cancel(int $id)
    {
        $notifModel = new NotifikasiModel();
        $notifModel->update($id, [
            'status'      => 'failed',
            'executed_at' => date('Y-m-d H:i:s'),
        ]);

        session()->setFlashdata('success', 'Notifikasi berhasil dibatalkan.');
        return redirect()->to(base_url('admin/notifikasi'));
    }
}
