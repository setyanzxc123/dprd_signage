<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Crud\JadwalUmumService;
use App\Models\JadwalUmumModel;
use App\Models\RuanganModel;
use App\Models\UnitRapatModel;

class GeneralScheduleController extends BaseController
{
    public function index(): string
    {
        $rows = db_connect()->table('jadwal_umum ju')
            ->select('ju.*, r.name AS nama_ruangan')
            ->join('ruangan r', 'r.id = ju.ruangan_id', 'left')
            ->orderBy('ju.tanggal', 'DESC')
            ->orderBy('ju.waktu_mulai', 'DESC')
            ->get()->getResultArray();

        $unitNames = (new JadwalUmumService())->unitNamesByScheduleIds(array_column($rows, 'id'));
        foreach ($rows as &$row) {
            $row['lokasi'] = $row['nama_ruangan'] ?: $row['lokasi_lainnya'];
            $row['unit_names'] = $unitNames[(int) $row['id']] ?? [];
            $row['status'] = JadwalUmumModel::resolveLifecycleStatus(
                (string) $row['tanggal'],
                $row['waktu_mulai'],
                $row['waktu_selesai'],
            );
        }
        unset($row);

        return view('admin/jadwal_umum/index', [
            'pageTitle' => 'Jadwal Umum',
            'schedules' => $rows,
        ]);
    }

    public function create(): string
    {
        return view('admin/jadwal_umum/form', $this->formData(
            'Tambah Jadwal Umum',
            null,
            base_url('admin/jadwal-umum/store'),
        ));
    }

    public function store()
    {
        $service = new JadwalUmumService();
        $input = $service->validatedInput(
            $this->request->getPost(),
            $this->request->getFile('undangan_file'),
        );

        if (isset($input['error'])) {
            return $this->failForm($input['error']);
        }

        $result = $service->create($input);
        if (is_string($result)) {
            return $this->failForm($result);
        }

        return $this->formSuccessResponse('Jadwal Umum berhasil ditambahkan.', base_url('admin/jadwal-umum'));
    }

    public function edit(int $id)
    {
        $schedule = (new JadwalUmumModel())->find($id);
        if ($schedule === null) {
            session()->setFlashdata('error', 'Jadwal Umum tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-umum'));
        }
        $schedule['target_unit_ids'] = (new JadwalUmumService())->unitIdsForSchedule($id);

        return view('admin/jadwal_umum/form', $this->formData(
            'Edit Jadwal Umum',
            $schedule,
            base_url("admin/jadwal-umum/{$id}/update"),
        ));
    }

    public function update(int $id)
    {
        $model = new JadwalUmumModel();
        $existing = $model->find($id);
        if ($existing === null) {
            session()->setFlashdata('error', 'Jadwal Umum tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-umum'));
        }

        $service = new JadwalUmumService();
        $input = $service->validatedInput(
            $this->request->getPost(),
            $this->request->getFile('undangan_file'),
            $existing,
        );

        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        $error = $service->update($id, $input, $existing);
        if ($error !== null) {
            return $this->failForm($error, $id);
        }

        return $this->formSuccessResponse('Jadwal Umum berhasil diperbarui.', base_url('admin/jadwal-umum'));
    }

    public function delete(int $id)
    {
        $existing = (new JadwalUmumModel())->find($id);
        if ($existing === null) {
            session()->setFlashdata('error', 'Jadwal Umum tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-umum'));
        }

        (new JadwalUmumService())->delete($existing);

        return $this->formSuccessResponse('Jadwal Umum berhasil dihapus.', base_url('admin/jadwal-umum'));
    }

    private function formData(string $title, ?array $schedule, string $actionUrl): array
    {
        return [
            'pageTitle'       => $title,
            'schedule'        => $schedule,
            'rooms'           => $this->roomOptions((int) ($schedule['ruangan_id'] ?? 0)),
            'unit_rapat_list' => $this->unitOptions(),
            'action_url'      => $actionUrl,
        ];
    }

    private function failForm(string $message, ?int $id = null)
    {
        return $this->formViewErrorResponse('admin/jadwal_umum/form', $this->formData(
            $id === null ? 'Tambah Jadwal Umum' : 'Edit Jadwal Umum',
            $this->postedSchedule($id),
            $id === null
                ? base_url('admin/jadwal-umum/store')
                : base_url("admin/jadwal-umum/{$id}/update"),
        ), $message);
    }

    private function postedSchedule(?int $id): array
    {
        $post = $this->request->getPost();
        $existing = $id !== null ? (new JadwalUmumModel())->find($id) : null;

        $ids = $post['target_unit_rapat'] ?? [];
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $v): bool => $v > 0)));

        return [
            'id'              => $id,
            'judul'           => trim((string) ($post['judul'] ?? '')),
            'tanggal'         => trim((string) ($post['tanggal'] ?? '')),
            'waktu_mulai'     => trim((string) ($post['waktu_mulai'] ?? '')),
            'waktu_selesai'   => trim((string) ($post['waktu_selesai'] ?? '')),
            'ruangan_id'      => ($post['lokasi_mode'] ?? null) === 'lainnya'
                ? null
                : (int) ($post['ruangan_id'] ?? 0),
            'lokasi_lainnya'  => trim((string) ($post['lokasi_lainnya'] ?? '')),
            'pihak_eksternal' => trim((string) ($post['pihak_eksternal'] ?? '')),
            'is_publik'       => ($post['is_publik'] ?? null) === '1' ? 1 : 0,
            'keterangan'      => trim((string) ($post['keterangan'] ?? '')),
            'materi_url'      => trim((string) ($post['materi_url'] ?? '')),
            'materi_akses'    => trim((string) ($post['materi_akses'] ?? '')),
            'stream_url'      => trim((string) ($post['stream_url'] ?? '')),
            'stream_akses'    => trim((string) ($post['stream_akses'] ?? '')),
            'target_unit_ids' => $ids,
            'undangan_file' => $existing['undangan_file'] ?? null,
            'undangan_nama_asli' => $existing['undangan_nama_asli'] ?? null,
        ];
    }

    private function roomOptions(int $selectedId): array
    {
        $model = new RuanganModel();
        $rooms = $model->where('tersedia', 1)->orderBy('name', 'ASC')->findAll();
        if ($selectedId > 0 && ! in_array($selectedId, array_map('intval', array_column($rooms, 'id')), true)) {
            $selected = $model->find($selectedId);
            if ($selected !== null) {
                $rooms[] = $selected;
            }
        }

        return $rooms;
    }

    private function unitOptions(): array
    {
        $units = (new UnitRapatModel())->where('aktif', 1)
            ->orderBy('urutan', 'ASC')->orderBy('nama', 'ASC')->findAll();
        if ($units === []) {
            return [];
        }

        $ids = array_map('intval', array_column($units, 'id'));
        $counts = array_fill_keys($ids, 0);
        $db = db_connect();
        if ($db->tableExists('anggota_unit_rapat') && $db->tableExists('anggota')) {
            $rows = $db->table('anggota_unit_rapat aur')
                ->select('aur.unit_rapat_id, COUNT(DISTINCT aur.anggota_id) AS active_member_count')
                ->join('anggota a', 'a.id = aur.anggota_id')
                ->whereIn('aur.unit_rapat_id', $ids)->where('a.aktif', 1)
                ->groupBy('aur.unit_rapat_id')->get()->getResultArray();
            foreach ($rows as $row) {
                $counts[(int) $row['unit_rapat_id']] = (int) $row['active_member_count'];
            }
        }

        return array_map(static function (array $unit) use ($counts): array {
            $unit['active_member_count'] = $counts[(int) $unit['id']] ?? 0;

            return $unit;
        }, $units);
    }
}
