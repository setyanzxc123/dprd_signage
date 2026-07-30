<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Schedule\ScheduleResourceAccess;
use App\Models\JadwalBanmusModel;
use App\Models\JadwalModel;
use App\Models\RuanganModel;
use App\Models\UnitRapatModel;

class MeetingController extends BaseController
{
    public function index(): string
    {
        (new JadwalModel())->autoUpdateStatuses();

        $db = \Config\Database::connect();
        $jadwals = $db->table('jadwal j')
            ->select('j.id, j.judul, j.keterangan, j.tanggal,
                      j.waktu_mulai, j.waktu_selesai, j.status,
                      j.jenis, j.is_publik, j.lokasi_lainnya, r.name AS nama_ruangan')
            ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
            ->where('j.jenis', 'insidental')
            ->orderBy('j.tanggal', 'DESC')
            ->orderBy('j.waktu_mulai', 'ASC')
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
                'ruangan'       => $this->displayLocation($j),
                'target_peserta' => $targetMap[$j['id']] ?? '-',
                'status'        => $j['status'],
                'sumber'        => JadwalModel::SOURCE,
                'lingkup'       => JadwalModel::SCOPE,
                'is_publik'     => (int) ($j['is_publik'] ?? 0),
            ];
        }

        return view('admin/jadwal/index', [
            'pageTitle' => 'Agenda Insidental',
            'meetings'  => $meetings,
        ]);
    }

    public function create(): string
    {
        return view('admin/jadwal/form', [
            'pageTitle'   => 'Tambah Agenda Insidental',
            'meeting'     => null,
            'rooms'       => $this->roomOptions(),
            'unit_rapat_list' => $this->unitRapatOptions(),
            'action_url'  => base_url('admin/jadwal/store'),
        ]);
    }

    public function store()
    {
        $input = $this->validatedScheduleInput();
        if (isset($input['error'])) {
            return $this->failForm($input['error']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $jadwalModel = new JadwalModel();
        $jadwalId = $jadwalModel->insert($input['payload'], true);
        if ($jadwalId === false) {
            $db->transRollback();

            return $this->failForm('Jadwal gagal disimpan. Silakan coba kembali.');
        }
        $this->syncJadwalUnits((int) $jadwalId, $input['unit_ids']);
        $db->transComplete();
        if (! $db->transStatus()) {
            return $this->failForm('Jadwal gagal disimpan. Silakan coba kembali.');
        }

        return $this->formSuccessResponse('Jadwal berhasil disimpan.', base_url('admin/jadwal'));
    }

    public function edit(int $id)
    {
        $jadwalModel  = new JadwalModel();
        $jadwal       = $jadwalModel->find($id);

        if (! $this->isInsidental($jadwal)) {
            session()->setFlashdata('error', 'Jadwal tidak ditemukan.');
            return redirect()->to(base_url('admin/jadwal'));
        }

        $jadwal['target_unit_ids'] = $this->jadwalUnitIds($id);

        return view('admin/jadwal/form', [
            'pageTitle'   => 'Edit Agenda Insidental',
            'meeting'     => $jadwal,
            'rooms'       => $this->roomOptions((int) ($jadwal['ruangan_id'] ?? 0)),
            'unit_rapat_list' => $this->unitRapatOptions($jadwal['target_unit_ids']),
            'action_url'  => base_url("admin/jadwal/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $jadwalModel = new JadwalModel();
        if (! $this->isInsidental($jadwalModel->find($id))) {
            session()->setFlashdata('error', 'Jadwal tidak ditemukan.');
            return redirect()->to(base_url('admin/jadwal'));
        }

        $input = $this->validatedScheduleInput($id);
        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        if (! $jadwalModel->update($id, $input['payload'])) {
            $db->transRollback();

            return $this->failForm('Jadwal gagal diperbarui. Silakan coba kembali.', $id);
        }
        $this->syncJadwalUnits($id, $input['unit_ids']);
        $db->transComplete();
        if (! $db->transStatus()) {
            return $this->failForm('Jadwal gagal diperbarui. Silakan coba kembali.', $id);
        }

        return $this->formSuccessResponse('Jadwal berhasil diperbarui.', base_url('admin/jadwal'));
    }

    public function delete(int $id)
    {
        $jadwalModel = new JadwalModel();
        if (! $this->isInsidental($jadwalModel->find($id))) {
            session()->setFlashdata('error', 'Jadwal tidak ditemukan.');
            return redirect()->to(base_url('admin/jadwal'));
        }

        $jadwalModel->delete($id);

        session()->setFlashdata('success', 'Jadwal berhasil dihapus.');
        return redirect()->to(base_url('admin/jadwal'));
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

        return $this->withActiveMemberCounts($units);
    }

    private function roomOptions(int $selectedId = 0): array
    {
        $model = new RuanganModel();
        $rooms = $model
            ->where('tersedia', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        $existingIds = array_map('intval', array_column($rooms, 'id'));
        if ($selectedId > 0 && ! in_array($selectedId, $existingIds, true)) {
            $selectedRoom = $model->find($selectedId);
            if ($selectedRoom) {
                $rooms[] = $selectedRoom;
            }
        }

        return $rooms;
    }

    private function postedUnitIds(): array
    {
        $ids = $this->request->getPost('target_unit_rapat') ?? [];
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

        return array_values(array_unique($ids));
    }

    private function validatedScheduleInput(?int $jadwalId = null): array
    {
        $judul = trim((string) $this->request->getPost('judul'));
        if ($judul === '') {
            return ['error' => 'Judul rapat wajib diisi.'];
        }
        if (mb_strlen($judul) > 255) {
            return ['error' => 'Judul rapat maksimal 255 karakter.'];
        }

        [$tanggal, $waktuMulai, $waktuSelesai] = $this->validatedTimes();
        if ($tanggal === null) {
            return ['error' => 'Waktu rapat wajib valid dan lengkap.'];
        }

        $unitIds = $this->postedUnitIds();
        if (empty($unitIds)) {
            return ['error' => 'Pilih minimal satu kelompok peserta rapat.'];
        }

        if (! empty($this->invalidSelectableUnitIds($unitIds, $jadwalId))) {
            return ['error' => 'Kelompok peserta yang dipilih tidak valid atau sudah nonaktif.'];
        }

        if (! empty($this->unitIdsWithoutActiveMembers($unitIds))) {
            return ['error' => 'Kelompok peserta yang dipilih wajib memiliki minimal satu anggota aktif.'];
        }

        $locationData = $this->validatedLocationData($jadwalId);
        if (isset($locationData['error'])) {
            return ['error' => $locationData['error']];
        }

        if ($locationData['ruangan_id'] !== null && $this->hasRoomConflict($locationData['ruangan_id'], $tanggal, $waktuMulai, $waktuSelesai, $jadwalId)) {
            return ['error' => 'Ruangan sudah dipakai pada tanggal dan rentang waktu tersebut.'];
        }

        $materiUrl = $this->validatedOptionalUrl(
            (string) $this->request->getPost('materi_url'),
            'Tautan materi atau dokumen tidak valid.',
        );
        if (isset($materiUrl['error'])) {
            return ['error' => $materiUrl['error']];
        }
        $streamUrl = $this->validatedOptionalUrl(
            (string) $this->request->getPost('stream_url'),
            'Tautan live streaming tidak valid.',
        );
        if (isset($streamUrl['error'])) {
            return ['error' => $streamUrl['error']];
        }

        return [
            'payload' => [
                'judul'          => $judul,
                'keterangan'     => trim((string) $this->request->getPost('keterangan')),
                'tanggal'        => $tanggal,
                'waktu_mulai'    => $waktuMulai,
                'waktu_selesai'  => $waktuSelesai,
                'ruangan_id'     => $locationData['ruangan_id'],
                'lokasi_lainnya' => $locationData['lokasi_lainnya'],
                'is_publik'      => $this->request->getPost('is_publik') ? 1 : 0,
                'materi_url'     => $materiUrl['url'],
                'materi_akses'   => ScheduleResourceAccess::normalize(
                    $this->request->getPost('materi_akses'),
                    ScheduleResourceAccess::PARTICIPANT,
                ),
                'stream_url'     => $streamUrl['url'],
                'stream_akses'   => ScheduleResourceAccess::normalize(
                    $this->request->getPost('stream_akses'),
                    ScheduleResourceAccess::MEMBER,
                ),
                'jenis'          => 'insidental',
                'status'         => JadwalModel::resolveLifecycleStatus(
                    $tanggal,
                    $waktuMulai,
                    $waktuSelesai,
                ),
            ],
            'unit_ids' => $unitIds,
        ];
    }

    private function validatedTimes(): array
    {
        $tanggalRaw      = trim((string) $this->request->getPost('tanggal'));
        $waktuMulaiRaw   = trim((string) $this->request->getPost('waktu_mulai'));
        $waktuSelesaiRaw = trim((string) $this->request->getPost('waktu_selesai'));

        if ($tanggalRaw !== '') {
            $tanggal = $this->validDateFilter($tanggalRaw);
            if ($tanggal === null
                || ! $this->isValidTimeValue($waktuMulaiRaw)
                || ! $this->isValidTimeValue($waktuSelesaiRaw)) {
                return [null, null, null, null];
            }

            $startTs = strtotime($tanggal . ' ' . $waktuMulaiRaw);
            $endTs   = strtotime($tanggal . ' ' . $waktuSelesaiRaw);
        } else {
            // Kompatibilitas untuk payload lama yang memakai datetime-local.
            $startTs = $waktuMulaiRaw !== '' ? strtotime($waktuMulaiRaw) : false;
            $endTs   = $waktuSelesaiRaw !== '' ? strtotime($waktuSelesaiRaw) : false;
        }

        if ($startTs === false || $endTs === false) {
            return [null, null, null, null];
        }

        if (date('Y-m-d', $startTs) !== date('Y-m-d', $endTs)) {
            return [null, null, null, null];
        }

        if ($endTs <= $startTs) {
            return [null, null, null, null];
        }

        return [
            date('Y-m-d', $startTs),
            date('H:i:s', $startTs),
            date('H:i:s', $endTs),
            $startTs,
        ];
    }

    private function isValidTimeValue(string $value): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value) === 1;
    }

    /**
     * @return array{url: ?string}|array{error: string}
     */
    private function validatedOptionalUrl(string $value, string $message): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['url' => null];
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if (filter_var($value, FILTER_VALIDATE_URL) === false
            || ! in_array($scheme, ['http', 'https'], true)) {
            return ['error' => $message];
        }

        return ['url' => $value];
    }

    private function validatedLocationData(?int $jadwalId = null): array
    {
        $mode = $this->request->getPost('lokasi_mode') === 'lainnya' ? 'lainnya' : 'ruangan';

        if ($mode === 'lainnya') {
            $lokasi = trim((string) $this->request->getPost('lokasi_lainnya'));
            if ($lokasi === '') {
                return ['error' => 'Lokasi lainnya wajib diisi.'];
            }

            if (mb_strlen($lokasi) > 255) {
                return ['error' => 'Lokasi lainnya maksimal 255 karakter.'];
            }

            return [
                'ruangan_id'     => null,
                'lokasi_lainnya' => $lokasi,
            ];
        }

        $ruanganId = (int) $this->request->getPost('ruangan_id');
        if ($ruanganId <= 0) {
            return ['error' => 'Ruangan rapat wajib dipilih.'];
        }

        $room = (new RuanganModel())->find($ruanganId);
        if (! $room) {
            return ['error' => 'Ruangan rapat yang dipilih tidak ditemukan.'];
        }

        $currentRoomId = 0;
        if ($jadwalId !== null) {
            $current = (new JadwalModel())->find($jadwalId);
            $currentRoomId = (int) ($current['ruangan_id'] ?? 0);
        }

        if ((int) ($room['tersedia'] ?? 0) !== 1 && $ruanganId !== $currentRoomId) {
            return ['error' => 'Ruangan rapat yang dipilih sedang tidak tersedia.'];
        }

        return [
            'ruangan_id'     => $ruanganId,
            'lokasi_lainnya' => null,
        ];
    }

    private function invalidSelectableUnitIds(array $unitIds, ?int $jadwalId): array
    {
        $rows = (new UnitRapatModel())
            ->select('id')
            ->where('aktif', 1)
            ->whereIn('id', $unitIds)
            ->findAll();

        $validIds = array_map('intval', array_column($rows, 'id'));

        if ($jadwalId !== null) {
            $validIds = array_values(array_unique(array_merge($validIds, $this->jadwalUnitIds($jadwalId))));
        }

        return array_values(array_diff($unitIds, $validIds));
    }

    private function unitIdsWithoutActiveMembers(array $unitIds): array
    {
        $unitIds = array_values(array_unique(array_filter(array_map('intval', $unitIds))));
        if (empty($unitIds)) {
            return [];
        }

        $db = \Config\Database::connect();
        if (! $db->tableExists('anggota_unit_rapat')) {
            return $unitIds;
        }

        $rows = $db->table('anggota_unit_rapat aur')
            ->select('aur.unit_rapat_id')
            ->join('anggota a', 'a.id = aur.anggota_id')
            ->where('a.aktif', 1)
            ->whereIn('aur.unit_rapat_id', $unitIds)
            ->groupBy('aur.unit_rapat_id')
            ->get()
            ->getResultArray();

        $unitIdsWithMembers = array_map('intval', array_column($rows, 'unit_rapat_id'));

        return array_values(array_diff($unitIds, $unitIdsWithMembers));
    }

    private function withActiveMemberCounts(array $units): array
    {
        $unitIds = array_values(array_filter(array_map('intval', array_column($units, 'id'))));
        if (empty($unitIds)) {
            return $units;
        }

        $counts = array_fill_keys($unitIds, 0);
        $db = \Config\Database::connect();

        if ($db->tableExists('anggota_unit_rapat')) {
            $rows = $db->table('anggota_unit_rapat aur')
                ->select('aur.unit_rapat_id, COUNT(DISTINCT aur.anggota_id) AS active_member_count')
                ->join('anggota a', 'a.id = aur.anggota_id')
                ->where('a.aktif', 1)
                ->whereIn('aur.unit_rapat_id', $unitIds)
                ->groupBy('aur.unit_rapat_id')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $counts[(int) $row['unit_rapat_id']] = (int) $row['active_member_count'];
            }
        }

        foreach ($units as &$unit) {
            $unit['active_member_count'] = $counts[(int) $unit['id']] ?? 0;
        }
        unset($unit);

        return $units;
    }

    private function hasRoomConflict(int $ruanganId, string $tanggal, string $waktuMulai, string $waktuSelesai, ?int $ignoreJadwalId): bool
    {
        $db = \Config\Database::connect();
        $builder = $db
            ->table('jadwal')
            ->select('id')
            ->where('tanggal', $tanggal)
            ->where('ruangan_id', $ruanganId)
            ->where('waktu_mulai <', $waktuSelesai)
            ->where('waktu_selesai >', $waktuMulai);

        if ($ignoreJadwalId !== null) {
            $builder->where('id !=', $ignoreJadwalId);
        }

        if ($builder->get(1)->getRowArray() !== null) {
            return true;
        }

        if (! $db->tableExists('jadwal_banmus')) {
            return false;
        }

        return $db->table('jadwal_banmus')
            ->select('id')
            ->where('tanggal', $tanggal)
            ->where('ruangan_id', $ruanganId)
            ->whereIn('status', JadwalBanmusModel::SCHEDULED_STATUSES)
            ->where('jam_mulai <', $waktuSelesai)
            ->where('jam_selesai >', $waktuMulai)
            ->where('deleted_at', null)
            ->get(1)
            ->getRowArray() !== null;
    }

    private function failForm(string $message, ?int $id = null)
    {
        $meeting = $this->postedMeeting($id);

        return $this->formViewErrorResponse('admin/jadwal/form', [
            'pageTitle'        => $id === null ? 'Tambah Agenda Insidental' : 'Edit Agenda Insidental',
            'meeting'          => $meeting,
            'rooms'            => $this->roomOptions((int) ($meeting['ruangan_id'] ?? 0)),
            'unit_rapat_list'  => $this->unitRapatOptions($meeting['target_unit_ids'] ?? []),
            'action_url'       => $id === null
                ? base_url('admin/jadwal/store')
                : base_url("admin/jadwal/{$id}/update"),
        ], $message);
    }

    private function postedMeeting(?int $id = null): array
    {
        $tanggalRaw = trim((string) $this->request->getPost('tanggal'));
        $waktuMulaiRaw = trim((string) $this->request->getPost('waktu_mulai'));
        $waktuSelesaiRaw = trim((string) $this->request->getPost('waktu_selesai'));
        if ($tanggalRaw !== '') {
            $tanggal = $tanggalRaw;
            $waktuMulai = $waktuMulaiRaw;
            $waktuSelesai = $waktuSelesaiRaw;
        } else {
            [$tanggal, $waktuMulai] = $this->splitDateTimeLocal($waktuMulaiRaw);
            [, $waktuSelesai] = $this->splitDateTimeLocal($waktuSelesaiRaw);
        }

        return [
            'id'              => $id,
            'judul'           => trim((string) $this->request->getPost('judul')),
            'keterangan'      => trim((string) $this->request->getPost('keterangan')),
            'tanggal'         => $tanggal,
            'waktu_mulai'     => $waktuMulai,
            'waktu_selesai'   => $waktuSelesai,
            'ruangan_id'      => (int) $this->request->getPost('ruangan_id'),
            'lokasi_lainnya'  => trim((string) $this->request->getPost('lokasi_lainnya')),
            'is_publik'       => $this->request->getPost('is_publik') ? 1 : 0,
            'materi_url'      => trim((string) $this->request->getPost('materi_url')),
            'materi_akses'    => ScheduleResourceAccess::normalize(
                $this->request->getPost('materi_akses'),
                ScheduleResourceAccess::PARTICIPANT,
            ),
            'stream_url'      => trim((string) $this->request->getPost('stream_url')),
            'stream_akses'    => ScheduleResourceAccess::normalize(
                $this->request->getPost('stream_akses'),
                ScheduleResourceAccess::MEMBER,
            ),
            'jenis'           => 'insidental',
            'target_unit_ids' => $this->postedUnitIds(),
        ];
    }

    private function splitDateTimeLocal(string $value): array
    {
        if ($value === '' || ! str_contains($value, 'T')) {
            return ['', ''];
        }

        [$date, $time] = explode('T', $value, 2);

        return [$date, $time];
    }

    private function displayLocation(array $row): string
    {
        $other = trim((string) ($row['lokasi_lainnya'] ?? ''));
        if ($other !== '') {
            return $other;
        }

        return $row['nama_ruangan'] ?? '-';
    }

    private function isInsidental(?array $jadwal): bool
    {
        return $jadwal !== null && ($jadwal['jenis'] ?? 'insidental') === 'insidental';
    }

    private function validDateFilter(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) ? $value : null;
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
