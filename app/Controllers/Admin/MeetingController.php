<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnggotaModel;
use App\Models\JadwalModel;
use App\Models\NotifikasiModel;
use App\Models\RuanganModel;

class MeetingController extends BaseController
{
    private array $komisiList = [
        'Komisi I', 'Komisi II', 'Komisi III', 'Komisi IV', 'Pansus', 'All Komisi',
    ];

    public function index(): string
    {
        $filterDate = $this->request->getGet('date') ?? date('Y-m-d');

        $db      = \Config\Database::connect();
        $jadwals = $db->table('jadwal j')
            ->select('j.id, j.judul, j.keterangan, j.tanggal,
                      j.waktu_mulai, j.waktu_selesai, j.komisi_target, j.status,
                      r.name AS nama_ruangan')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.tanggal', $filterDate)
            ->orderBy('j.waktu_mulai', 'ASC')
            ->get()->getResultArray();

        $meetings = [];
        foreach ($jadwals as $j) {
            $meetings[] = [
                'id'            => $j['id'],
                'judul'         => $j['judul'],
                'keterangan'    => $j['keterangan'] ?? '',
                'waktu_mulai'   => substr($j['waktu_mulai'], 0, 5),
                'waktu_selesai' => substr($j['waktu_selesai'], 0, 5),
                'ruangan'       => $j['nama_ruangan'] ?? '-',
                'komisi_target' => $j['komisi_target'] ?? '-',
                'status'        => $j['status'],
            ];
        }

        return view('admin/jadwal/index', [
            'pageTitle'   => 'Jadwal Rapat',
            'filter_date' => $filterDate,
            'meetings'    => $meetings,
        ]);
    }

    public function create(): string
    {
        $ruanganModel = new RuanganModel();

        return view('admin/jadwal/form', [
            'pageTitle'   => 'Tambah Jadwal Rapat',
            'meeting'     => null,
            'rooms'       => $ruanganModel->orderBy('name')->findAll(),
            'komisi_list' => $this->komisiList,
            'action_url'  => base_url('admin/jadwal/store'),
        ]);
    }

    public function store()
    {
        // Pisah tanggal dan waktu dari datetime-local input (format: "2026-04-27T08:00")
        $waktuMulaiRaw   = $this->request->getPost('waktu_mulai');
        $waktuSelesaiRaw = $this->request->getPost('waktu_selesai');

        [$tanggal,     $waktuMulai]   = explode('T', $waktuMulaiRaw);
        [,             $waktuSelesai] = explode('T', $waktuSelesaiRaw);

        $blastBefore  = (int) $this->request->getPost('blast_before');
        $komisiArray  = $this->request->getPost('target_komisi') ?? [];
        $komisiJson   = json_encode($komisiArray);

        // Hitung waktu pengiriman notifikasi
        $reminderTime = date('Y-m-d H:i:s',
            strtotime("{$tanggal} {$waktuMulai}") - ($blastBefore * 60)
        );

        $jadwalModel = new JadwalModel();
        $jadwalId    = $jadwalModel->insert([
            'judul'         => $this->request->getPost('judul'),
            'keterangan'    => $this->request->getPost('keterangan'),
            'tanggal'       => $tanggal,
            'waktu_mulai'   => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'ruangan_id'    => $this->request->getPost('ruangan_id'),
            'komisi_target' => $komisiJson,
            'blast_before'  => $blastBefore,
            'reminder_time' => $reminderTime,
            'materi_url'    => $this->request->getPost('materi_url'),
            'stream_url'    => $this->request->getPost('stream_url') ?: null,
            'is_publik'     => $this->request->getPost('is_publik') ? 1 : 0,
            'jenis'         => $this->request->getPost('jenis') ?? 'insidental',
            'status'        => 'menunggu',
        ], true); // true = return insert ID

        // Buat entri notifikasi pending untuk anggota yang relevan
        $this->_createNotifikasi($jadwalId, $komisiArray);

        session()->setFlashdata('success', 'Jadwal berhasil disimpan dan notifikasi dijadwalkan.');
        return redirect()->to(base_url('admin/jadwal'));
    }

    public function edit(int $id): string
    {
        $jadwalModel  = new JadwalModel();
        $ruanganModel = new RuanganModel();
        $jadwal       = $jadwalModel->find($id);

        if (!$jadwal) {
            session()->setFlashdata('error', 'Jadwal tidak ditemukan.');
            return redirect()->to(base_url('admin/jadwal'));
        }

        // Decode komisi_target dari JSON
        $jadwal['target_komisi'] = json_decode($jadwal['komisi_target'] ?? '[]', true);

        return view('admin/jadwal/form', [
            'pageTitle'   => 'Edit Jadwal Rapat',
            'meeting'     => $jadwal,
            'rooms'       => $ruanganModel->orderBy('name')->findAll(),
            'komisi_list' => $this->komisiList,
            'action_url'  => base_url("admin/jadwal/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $waktuMulaiRaw   = $this->request->getPost('waktu_mulai');
        $waktuSelesaiRaw = $this->request->getPost('waktu_selesai');

        [$tanggal,     $waktuMulai]   = explode('T', $waktuMulaiRaw);
        [,             $waktuSelesai] = explode('T', $waktuSelesaiRaw);

        $blastBefore  = (int) $this->request->getPost('blast_before');
        $komisiArray  = $this->request->getPost('target_komisi') ?? [];
        $reminderTime = date('Y-m-d H:i:s',
            strtotime("{$tanggal} {$waktuMulai}") - ($blastBefore * 60)
        );

        $jadwalModel = new JadwalModel();
        $jadwalModel->update($id, [
            'judul'         => $this->request->getPost('judul'),
            'keterangan'    => $this->request->getPost('keterangan'),
            'tanggal'       => $tanggal,
            'waktu_mulai'   => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'ruangan_id'    => $this->request->getPost('ruangan_id'),
            'komisi_target' => json_encode($komisiArray),
            'blast_before'  => $blastBefore,
            'reminder_time' => $reminderTime,
            'materi_url'    => $this->request->getPost('materi_url'),
            'stream_url'    => $this->request->getPost('stream_url') ?: null,
            'is_publik'     => $this->request->getPost('is_publik') ? 1 : 0,
            'jenis'         => $this->request->getPost('jenis') ?? 'insidental',
        ]);

        session()->setFlashdata('success', 'Jadwal berhasil diperbarui.');
        return redirect()->to(base_url('admin/jadwal'));
    }

    public function delete(int $id)
    {
        $jadwalModel = new JadwalModel();
        $jadwalModel->delete($id);

        session()->setFlashdata('success', 'Jadwal berhasil dihapus.');
        return redirect()->to(base_url('admin/jadwal'));
    }

    // ── Toggle is_publik ───────────────────────────────────────────────
    public function togglePublik(int $id)
    {
        $jadwalModel = new JadwalModel();
        $jadwal      = $jadwalModel->find($id);

        if (!$jadwal) {
            return $this->response->setJSON(['success' => false, 'message' => 'Jadwal tidak ditemukan.']);
        }

        $newVal = $jadwal['is_publik'] ? 0 : 1;
        $jadwalModel->update($id, ['is_publik' => $newVal]);

        return $this->response->setJSON([
            'success'    => true,
            'is_publik'  => $newVal,
            'message'    => $newVal ? 'Jadwal ditampilkan di publik.' : 'Jadwal disembunyikan dari publik.',
        ]);
    }

    // ── Helper: buat entri notifikasi pending ──────────────────────────
    private function _createNotifikasi(int $jadwalId, array $komisiArray): void
    {
        $anggotaModel = new AnggotaModel();

        if (in_array('All Komisi', $komisiArray) || empty($komisiArray)) {
            $targets = $anggotaModel->where('aktif', 1)->findAll();
        } else {
            $targets = $anggotaModel
                ->where('aktif', 1)
                ->whereIn('komisi', $komisiArray)
                ->findAll();
        }

        if (empty($targets)) return;

        $notifModel = new NotifikasiModel();
        foreach ($targets as $anggota) {
            $notifModel->insert([
                'jadwal_id'  => $jadwalId,
                'anggota_id' => $anggota['id'],
                'no_wa'      => $anggota['no_wa'],
                'status'     => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
