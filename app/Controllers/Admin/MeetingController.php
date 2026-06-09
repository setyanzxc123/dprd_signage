<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnggotaModel;
use App\Models\JadwalModel;
use App\Models\NotifikasiModel;
use App\Models\RuanganModel;
use App\Models\UnitRapatModel;

class MeetingController extends BaseController
{
    public function index(): string
    {
        // Otomatis perbarui status semua rapat berdasarkan waktu saat ini
        (new JadwalModel())->autoUpdateStatuses();

        $tahun    = (int) ($this->request->getGet('tahun') ?? date('Y'));
        $semester = $this->request->getGet('semester') ?? 'all';
        $jenis    = $this->request->getGet('jenis') ?? 'all';
        $status   = $this->request->getGet('status') ?? 'all';
        $q        = trim((string) ($this->request->getGet('q') ?? ''));
        $page     = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage  = (int) ($this->request->getGet('per_page') ?? 10);
        $perPage  = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $db      = \Config\Database::connect();
        $applyFilters = static function ($builder) use ($db, $tahun, $semester, $jenis, $status, $q) {
            $builder->where('j.tanggal >=', "{$tahun}-01-01");
            $builder->where('j.tanggal <=', "{$tahun}-12-31");

            if ($semester === '1') {
                $builder->where('j.tanggal <=', "{$tahun}-06-30");
            } elseif ($semester === '2') {
                $builder->where('j.tanggal >=', "{$tahun}-07-01");
            }

            if ($jenis === 'reguler') {
                $builder->whereIn('j.jenis', ['reguler', 'bamus']);
            } elseif ($jenis !== 'all') {
                $builder->where('j.jenis', $jenis);
            }

            if ($status !== 'all') {
                $builder->where('j.status', $status);
            }

            if ($q !== '') {
                $unitLike = $db->escape('%' . $db->escapeLikeString($q) . '%');
                $builder
                ->groupStart()
                    ->like('j.judul', $q)
                    ->orLike('j.keterangan', $q)
                    ->orLike('r.name', $q)
                    ->orWhere(
                        "EXISTS (
                            SELECT 1
                            FROM jadwal_unit_rapat jur
                            JOIN unit_rapat ur ON ur.id = jur.unit_rapat_id
                            WHERE jur.jadwal_id = j.id
                              AND ur.nama LIKE {$unitLike} ESCAPE '!'
                        )",
                        null,
                        false
                    )
                ->groupEnd();
            }

            return $builder;
        };

        $total = $applyFilters(
            $db->table('jadwal j')
                ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
        )->countAllResults();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $jadwals = $applyFilters(
            $db->table('jadwal j')
                ->select('j.id, j.judul, j.keterangan, j.tanggal,
                          j.waktu_mulai, j.waktu_selesai, j.status,
                          j.jenis, j.is_publik, r.name AS nama_ruangan')
                ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
        )
            ->orderBy('j.tanggal', 'DESC')
            ->orderBy('j.waktu_mulai', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $targetMap = $this->targetNamesByJadwalIds(array_column($jadwals, 'id'));
        $meetings = [];
        foreach ($jadwals as $j) {
            $meetings[] = [
                'id'            => $j['id'],
                'judul'         => $j['judul'],
                'keterangan'    => $j['keterangan'] ?? '',
                'tanggal'       => $j['tanggal'],
                'waktu_mulai'   => substr($j['waktu_mulai'], 0, 5),
                'waktu_selesai' => substr($j['waktu_selesai'], 0, 5),
                'ruangan'       => $j['nama_ruangan'] ?? '-',
                'target_peserta' => $targetMap[$j['id']] ?? '-',
                'status'        => $j['status'],
                'jenis'         => $this->normalizeJenis($j['jenis'] ?? null),
                'is_publik'     => (int) ($j['is_publik'] ?? 0),
            ];
        }

        return view('admin/jadwal/index', [
            'pageTitle'   => 'Jadwal Rapat',
            'meetings'    => $meetings,
            'pagination'  => [
                'page'       => $page,
                'perPage'    => $perPage,
                'total'      => $total,
                'totalPages' => $totalPages,
                'from'       => $total ? $offset + 1 : 0,
                'to'         => min($offset + $perPage, $total),
            ],
            'filters'     => [
                'tahun'    => $tahun,
                'semester' => $semester,
                'jenis'    => $jenis,
                'status'   => $status,
                'q'        => $q,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): string
    {
        $ruanganModel = new RuanganModel();

        return view('admin/jadwal/form', [
            'pageTitle'   => 'Tambah Jadwal Rapat',
            'meeting'     => null,
            'rooms'       => $ruanganModel->orderBy('name')->findAll(),
            'unit_rapat_list' => $this->unitRapatOptions(),
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
        $unitIds      = $this->postedUnitIds();

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
            'blast_before'  => $blastBefore,
            'reminder_time' => $reminderTime,
            'materi_url'    => $this->request->getPost('materi_url'),
            'stream_url'    => $this->request->getPost('stream_url') ?: null,
            'is_publik'     => $this->request->getPost('is_publik') ? 1 : 0,
            'jenis'         => $this->postedJenis(),
            'status'        => 'menunggu',
        ], true); // true = return insert ID

        $this->syncJadwalUnits((int) $jadwalId, $unitIds);

        // Buat entri notifikasi pending untuk anggota yang relevan
        $this->_syncNotifikasi((int) $jadwalId, $unitIds, false);

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

        $jadwal['target_unit_ids'] = $this->jadwalUnitIds($id);
        $jadwal['jenis'] = $this->normalizeJenis($jadwal['jenis'] ?? null);

        return view('admin/jadwal/form', [
            'pageTitle'   => 'Edit Jadwal Rapat',
            'meeting'     => $jadwal,
            'rooms'       => $ruanganModel->orderBy('name')->findAll(),
            'unit_rapat_list' => $this->unitRapatOptions($jadwal['target_unit_ids']),
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
        $unitIds      = $this->postedUnitIds();
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
            'blast_before'  => $blastBefore,
            'reminder_time' => $reminderTime,
            'materi_url'    => $this->request->getPost('materi_url'),
            'stream_url'    => $this->request->getPost('stream_url') ?: null,
            'is_publik'     => $this->request->getPost('is_publik') ? 1 : 0,
            'jenis'         => $this->postedJenis(),
        ]);

        $this->syncJadwalUnits($id, $unitIds);

        // Sinkronisasi dan reset status notifikasi agar dikirim ulang dengan detail terbaru
        $this->_syncNotifikasi($id, $unitIds, true);

        session()->setFlashdata('success', 'Jadwal berhasil diperbarui dan notifikasi dijadwalkan ulang.');
        return redirect()->to(base_url('admin/jadwal'));
    }

    public function delete(int $id)
    {
        $jadwalModel = new JadwalModel();
        $jadwalModel->delete($id);

        session()->setFlashdata('success', 'Jadwal berhasil dihapus.');
        return redirect()->to(base_url('admin/jadwal'));
    }

    // ── Helper: sinkronisasi dan buat/reset entri notifikasi ──────────
    private function _syncNotifikasi(int $jadwalId, array $unitIds, bool $isUpdate = false): void
    {
        $targets = $this->targetAnggotaByUnitIds($unitIds);

        $notifModel = new NotifikasiModel();

        if ($isUpdate) {
            $targetAnggotaIds = array_column($targets, 'id');

            // Hapus notifikasi pending untuk anggota yang sudah tidak menjadi target lagi
            if (!empty($targetAnggotaIds)) {
                $notifModel->where('jadwal_id', $jadwalId)
                           ->where('status', 'pending')
                           ->whereNotIn('anggota_id', $targetAnggotaIds)
                           ->delete();
            } else {
                $notifModel->where('jadwal_id', $jadwalId)
                           ->where('status', 'pending')
                           ->delete();
            }
        }

        if (empty($targets)) return;

        foreach ($targets as $anggota) {
            $exists = $notifModel
                ->where('jadwal_id', $jadwalId)
                ->where('anggota_id', $anggota['id'])
                ->first();

            if ($exists) {
                // Jika update jadwal, reset status notifikasi agar dikirim ulang dengan detail terbaru
                if ($isUpdate) {
                    $notifModel->update($exists['id'], [
                        'no_wa'         => $anggota['no_wa'],
                        'status'        => 'pending',
                        'executed_at'   => null,
                        'retry_count'   => 0,
                        'error_message' => null,
                    ]);
                }
            } else {
                // Jika belum ada, buat baru
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

    private function unitRapatOptions(array $selectedIds = []): array
    {
        $model = new UnitRapatModel();
        $builder = $model
            ->where('aktif', 1)
            ->orderBy('urutan', 'ASC')
            ->orderBy('nama', 'ASC');

        $units = $builder->findAll();
        $existingIds = array_map('intval', array_column($units, 'id'));
        $missingIds = array_values(array_diff($selectedIds, $existingIds));

        if (!empty($missingIds)) {
            $inactiveUnits = $model
                ->whereIn('id', $missingIds)
                ->orderBy('urutan', 'ASC')
                ->orderBy('nama', 'ASC')
                ->findAll();

            $units = array_merge($units, $inactiveUnits);
        }

        return $units;
    }

    private function postedUnitIds(): array
    {
        $ids = $this->request->getPost('target_unit_rapat') ?? [];
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

        return array_values(array_unique($ids));
    }

    private function postedJenis(): string
    {
        return $this->normalizeJenis($this->request->getPost('jenis'));
    }

    private function normalizeJenis(?string $jenis): string
    {
        if ($jenis === 'bamus' || $jenis === 'reguler') {
            return 'reguler';
        }

        return 'insidental';
    }

    private function targetAnggotaByUnitIds(array $unitIds): array
    {
        $anggotaModel = new AnggotaModel();
        $unitIds = array_values(array_filter(array_map('intval', $unitIds)));

        if (empty($unitIds)) {
            return $anggotaModel->where('aktif', 1)->findAll();
        }

        $units = (new UnitRapatModel())
            ->select('id, nama, membership_type')
            ->whereIn('id', $unitIds)
            ->findAll();

        if (empty($units)) {
            return [];
        }

        foreach ($units as $unit) {
            if ($unit['membership_type'] === 'semua_anggota' || $unit['nama'] === 'All Komisi') {
                return $anggotaModel->where('aktif', 1)->findAll();
            }
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('anggota_unit_rapat')) {
            return [];
        }

        $targets = $db
            ->table('anggota_unit_rapat aur')
            ->select('a.*')
            ->join('anggota a', 'a.id = aur.anggota_id')
            ->where('a.aktif', 1)
            ->whereIn('aur.unit_rapat_id', $unitIds)
            ->get()
            ->getResultArray();

        $targetsById = [];
        foreach ($targets as $anggota) {
            $targetsById[$anggota['id']] = $anggota;
        }

        return array_values($targetsById);
    }

    private function jadwalUnitIds(int $jadwalId): array
    {
        $rows = \Config\Database::connect()
            ->table('jadwal_unit_rapat')
            ->select('unit_rapat_id')
            ->where('jadwal_id', $jadwalId)
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'unit_rapat_id'));
    }

    private function syncJadwalUnits(int $jadwalId, array $unitIds): void
    {
        $db = \Config\Database::connect();
        $db->table('jadwal_unit_rapat')->where('jadwal_id', $jadwalId)->delete();

        if (empty($unitIds)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $rows = array_map(static fn (int $unitId): array => [
            'jadwal_id'     => $jadwalId,
            'unit_rapat_id' => $unitId,
            'created_at'    => $now,
        ], $unitIds);

        $db->table('jadwal_unit_rapat')->insertBatch($rows);
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
