<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
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

        $unitNames = $this->unitNamesByScheduleIds(array_column($rows, 'id'));
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
        $input = $this->validatedInput();
        if (isset($input['error'])) {
            return $this->failForm($input['error']);
        }

        $db = db_connect();
        $db->transStart();
        $id = (new JadwalUmumModel())->insert($input['payload'], true);
        if ($id === false) {
            $db->transRollback();

            return $this->failForm('Jadwal Umum gagal disimpan. Silakan coba kembali.');
        }
        $this->syncUnits((int) $id, $input['unit_ids']);
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->failForm('Jadwal Umum gagal disimpan. Silakan coba kembali.');
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
        $schedule['target_unit_ids'] = $this->unitIdsForSchedule($id);

        return view('admin/jadwal_umum/form', $this->formData(
            'Edit Jadwal Umum',
            $schedule,
            base_url("admin/jadwal-umum/{$id}/update"),
        ));
    }

    public function update(int $id)
    {
        $model = new JadwalUmumModel();
        if ($model->find($id) === null) {
            session()->setFlashdata('error', 'Jadwal Umum tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-umum'));
        }

        $input = $this->validatedInput($id);
        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        $db = db_connect();
        $db->transStart();
        if (! $model->update($id, $input['payload'])) {
            $db->transRollback();

            return $this->failForm('Jadwal Umum gagal diperbarui. Silakan coba kembali.', $id);
        }
        $this->syncUnits($id, $input['unit_ids']);
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->failForm('Jadwal Umum gagal diperbarui. Silakan coba kembali.', $id);
        }

        return $this->formSuccessResponse('Jadwal Umum berhasil diperbarui.', base_url('admin/jadwal-umum'));
    }

    public function delete(int $id)
    {
        $model = new JadwalUmumModel();
        if ($model->find($id) === null) {
            session()->setFlashdata('error', 'Jadwal Umum tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-umum'));
        }

        $db = db_connect();
        $db->transStart();
        $db->table('jadwal_umum_unit_rapat')->where('jadwal_umum_id', $id)->delete();
        $model->delete($id);
        $db->transComplete();

        if (! $db->transStatus()) {
            session()->setFlashdata('error', 'Jadwal Umum gagal dihapus.');

            return redirect()->to(base_url('admin/jadwal-umum'));
        }

        return $this->formSuccessResponse('Jadwal Umum berhasil dihapus.', base_url('admin/jadwal-umum'));
    }

    private function validatedInput(?int $scheduleId = null): array
    {
        $judul = trim((string) $this->request->getPost('judul'));
        if ($judul === '' || mb_strlen($judul) > 255) {
            return ['error' => 'Judul wajib diisi dan maksimal 255 karakter.'];
        }

        $tanggal = trim((string) $this->request->getPost('tanggal'));
        if (! $this->validDate($tanggal)) {
            return ['error' => 'Tanggal wajib diisi dengan format yang valid.'];
        }

        $times = $this->validatedTimes();
        if (isset($times['error'])) {
            return $times;
        }

        $location = $this->validatedLocation($scheduleId);
        if (isset($location['error'])) {
            return $location;
        }
        if ($location['ruangan_id'] !== null
            && ($times['waktu_mulai'] === null || $times['waktu_selesai'] === null)) {
            return ['error' => 'Jam mulai dan selesai wajib diisi jika memakai ruangan DPRD.'];
        }

        $unitIds = $this->postedUnitIds();
        if ($unitIds !== [] && $this->invalidUnitIds($unitIds) !== []) {
            return ['error' => 'Kelompok peserta tidak valid atau sudah nonaktif.'];
        }
        if ($unitIds !== [] && $this->unitIdsWithoutActiveMembers($unitIds) !== []) {
            return ['error' => 'Kelompok peserta yang dipilih harus mempunyai anggota aktif.'];
        }

        $pihakEksternal = trim((string) $this->request->getPost('pihak_eksternal'));
        if (mb_strlen($pihakEksternal) > 255) {
            return ['error' => 'Pihak eksternal maksimal 255 karakter.'];
        }
        $keterangan = trim((string) $this->request->getPost('keterangan'));
        if (mb_strlen($keterangan) > 5000) {
            return ['error' => 'Keterangan maksimal 5.000 karakter.'];
        }

        if ($location['ruangan_id'] !== null
            && (new JadwalUmumModel())->hasRoomConflict(
                $location['ruangan_id'],
                $tanggal,
                $times['waktu_mulai'],
                $times['waktu_selesai'],
                $scheduleId,
            )) {
            return ['error' => 'Ruangan sudah dipakai pada tanggal dan rentang waktu tersebut.'];
        }

        return [
            'payload' => [
                'judul'           => $judul,
                'tanggal'         => $tanggal,
                'waktu_mulai'     => $times['waktu_mulai'],
                'waktu_selesai'   => $times['waktu_selesai'],
                'ruangan_id'      => $location['ruangan_id'],
                'lokasi_lainnya'  => $location['lokasi_lainnya'],
                'pihak_eksternal' => $pihakEksternal !== '' ? $pihakEksternal : null,
                'is_publik'       => $this->request->getPost('is_publik') === '1' ? 1 : 0,
                'keterangan'      => $keterangan !== '' ? $keterangan : null,
            ],
            'unit_ids' => $unitIds,
        ];
    }

    private function validatedTimes(): array
    {
        $start = trim((string) $this->request->getPost('waktu_mulai'));
        $end = trim((string) $this->request->getPost('waktu_selesai'));

        if ($start === '' && $end === '') {
            return ['waktu_mulai' => null, 'waktu_selesai' => null];
        }
        if ($start === '') {
            return ['error' => 'Jam selesai tidak boleh diisi tanpa jam mulai.'];
        }
        if (! $this->validTime($start) || ($end !== '' && ! $this->validTime($end))) {
            return ['error' => 'Format jam pelaksanaan tidak valid.'];
        }
        if ($end !== '' && $end <= $start) {
            return ['error' => 'Jam selesai harus setelah jam mulai.'];
        }

        return [
            'waktu_mulai'   => $start . (strlen($start) === 5 ? ':00' : ''),
            'waktu_selesai' => $end !== '' ? $end . (strlen($end) === 5 ? ':00' : '') : null,
        ];
    }

    private function validatedLocation(?int $scheduleId): array
    {
        $mode = $this->request->getPost('lokasi_mode') === 'lainnya' ? 'lainnya' : 'ruangan';
        if ($mode === 'lainnya') {
            $location = trim((string) $this->request->getPost('lokasi_lainnya'));
            if ($location === '' || mb_strlen($location) > 255) {
                return ['error' => 'Lokasi lainnya wajib diisi dan maksimal 255 karakter.'];
            }

            return ['ruangan_id' => null, 'lokasi_lainnya' => $location];
        }

        $roomId = (int) $this->request->getPost('ruangan_id');
        if ($roomId < 1) {
            return ['error' => 'Pilih ruangan DPRD atau gunakan lokasi lainnya.'];
        }

        $room = (new RuanganModel())->find($roomId);
        $current = $scheduleId === null ? null : (new JadwalUmumModel())->find($scheduleId);
        $currentRoomId = (int) ($current['ruangan_id'] ?? 0);
        if ($room === null || ((int) ($room['tersedia'] ?? 0) !== 1 && $roomId !== $currentRoomId)) {
            return ['error' => 'Ruangan yang dipilih tidak valid atau tidak tersedia.'];
        }

        return ['ruangan_id' => $roomId, 'lokasi_lainnya' => null];
    }

    private function postedUnitIds(): array
    {
        $ids = $this->request->getPost('target_unit_rapat') ?? [];
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0);

        return array_values(array_unique($ids));
    }

    private function invalidUnitIds(array $unitIds): array
    {
        $validIds = array_map('intval', array_column(
            (new UnitRapatModel())->select('id')->where('aktif', 1)->whereIn('id', $unitIds)->findAll(),
            'id',
        ));

        return array_values(array_diff($unitIds, $validIds));
    }

    private function unitIdsWithoutActiveMembers(array $unitIds): array
    {
        $db = db_connect();
        if (! $db->tableExists('anggota_unit_rapat') || ! $db->tableExists('anggota')) {
            return $unitIds;
        }

        $rows = $db->table('anggota_unit_rapat aur')
            ->distinct()->select('aur.unit_rapat_id')
            ->join('anggota a', 'a.id = aur.anggota_id')
            ->whereIn('aur.unit_rapat_id', $unitIds)
            ->where('a.aktif', 1)
            ->get()->getResultArray();

        return array_values(array_diff($unitIds, array_map('intval', array_column($rows, 'unit_rapat_id'))));
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
        return [
            'id'              => $id,
            'judul'           => trim((string) $this->request->getPost('judul')),
            'tanggal'         => trim((string) $this->request->getPost('tanggal')),
            'waktu_mulai'     => trim((string) $this->request->getPost('waktu_mulai')),
            'waktu_selesai'   => trim((string) $this->request->getPost('waktu_selesai')),
            'ruangan_id'      => $this->request->getPost('lokasi_mode') === 'lainnya'
                ? null
                : (int) $this->request->getPost('ruangan_id'),
            'lokasi_lainnya'  => trim((string) $this->request->getPost('lokasi_lainnya')),
            'pihak_eksternal' => trim((string) $this->request->getPost('pihak_eksternal')),
            'is_publik'       => $this->request->getPost('is_publik') === '1' ? 1 : 0,
            'keterangan'      => trim((string) $this->request->getPost('keterangan')),
            'target_unit_ids' => $this->postedUnitIds(),
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

    private function syncUnits(int $scheduleId, array $unitIds): void
    {
        $db = db_connect();
        $db->table('jadwal_umum_unit_rapat')->where('jadwal_umum_id', $scheduleId)->delete();
        if ($unitIds === []) {
            return;
        }

        $createdAt = date('Y-m-d H:i:s');
        $db->table('jadwal_umum_unit_rapat')->insertBatch(array_map(
            static fn (int $unitId): array => [
                'jadwal_umum_id' => $scheduleId,
                'unit_rapat_id'  => $unitId,
                'created_at'     => $createdAt,
            ],
            $unitIds,
        ));
    }

    private function unitIdsForSchedule(int $scheduleId): array
    {
        return array_map('intval', array_column(
            db_connect()->table('jadwal_umum_unit_rapat')->select('unit_rapat_id')
                ->where('jadwal_umum_id', $scheduleId)->get()->getResultArray(),
            'unit_rapat_id',
        ));
    }

    private function unitNamesByScheduleIds(array $scheduleIds): array
    {
        $scheduleIds = array_values(array_filter(array_map('intval', $scheduleIds)));
        if ($scheduleIds === []) {
            return [];
        }

        $rows = db_connect()->table('jadwal_umum_unit_rapat jur')
            ->select('jur.jadwal_umum_id, ur.nama')
            ->join('unit_rapat ur', 'ur.id = jur.unit_rapat_id')
            ->whereIn('jur.jadwal_umum_id', $scheduleIds)
            ->orderBy('ur.urutan', 'ASC')->orderBy('ur.nama', 'ASC')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['jadwal_umum_id']][] = (string) $row['nama'];
        }

        return $map;
    }

    private function validDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function validTime(string $value): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value) === 1;
    }
}
