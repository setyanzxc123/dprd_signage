<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\JadwalModel;
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

        $db      = \Config\Database::connect();
        $applyFilters = static function ($builder) use ($tahun, $semester, $jenis, $status) {
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

            return $builder;
        };

        $jadwals = $applyFilters(
            $db->table('jadwal j')
                ->select('j.id, j.judul, j.keterangan, j.tanggal,
                          j.waktu_mulai, j.waktu_selesai, j.status,
                          j.jenis, j.is_publik, j.lokasi_lainnya, r.name AS nama_ruangan')
                ->join('ruangan r', 'r.id = j.ruangan_id', 'left')
        )
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
                'jenis'         => $this->normalizeJenis($j['jenis'] ?? null),
                'is_publik'     => (int) ($j['is_publik'] ?? 0),
            ];
        }

        return view('admin/jadwal/index', [
            'pageTitle'   => 'Jadwal Rapat',
            'meetings'    => $meetings,
            'filters'     => [
                'tahun'    => $tahun,
                'semester' => $semester,
                'jenis'    => $jenis,
                'status'   => $status,
            ],
            'data_scope'  => [
                'label' => $this->scheduleScopeLabel($tahun, $semester, $jenis, $status),
            ],
        ]);
    }

    public function create(): string
    {
        return view('admin/jadwal/form', [
            'pageTitle'   => 'Tambah Jadwal Rapat',
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

        $jadwalModel = new JadwalModel();
        $jadwalId    = $jadwalModel->insert(array_merge($input['payload'], [
            'status'        => 'menunggu',
        ]), true); // true = return insert ID

        $this->syncJadwalUnits((int) $jadwalId, $input['unit_ids']);

        return $this->formSuccessResponse('Jadwal berhasil disimpan.', base_url('admin/jadwal'));
    }

    public function edit(int $id)
    {
        $jadwalModel  = new JadwalModel();
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
            'rooms'       => $this->roomOptions((int) ($jadwal['ruangan_id'] ?? 0)),
            'unit_rapat_list' => $this->unitRapatOptions($jadwal['target_unit_ids']),
            'action_url'  => base_url("admin/jadwal/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $jadwalModel = new JadwalModel();
        if (! $jadwalModel->find($id)) {
            session()->setFlashdata('error', 'Jadwal tidak ditemukan.');
            return redirect()->to(base_url('admin/jadwal'));
        }

        $input = $this->validatedScheduleInput($id);
        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        $jadwalModel->update($id, $input['payload']);

        $this->syncJadwalUnits($id, $input['unit_ids']);

        return $this->formSuccessResponse('Jadwal berhasil diperbarui.', base_url('admin/jadwal'));
    }

    public function delete(int $id)
    {
        $jadwalModel = new JadwalModel();
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

    private function postedJenis(): string
    {
        return $this->normalizeJenis($this->request->getPost('jenis'));
    }

    private function validatedScheduleInput(?int $jadwalId = null): array
    {
        $judul = trim((string) $this->request->getPost('judul'));
        if ($judul === '') {
            return ['error' => 'Judul rapat wajib diisi.'];
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

        $materiUrl = $this->validatedOptionalUrl((string) $this->request->getPost('materi_url'), 'Link materi rapat tidak valid.');
        if (isset($materiUrl['error'])) {
            return ['error' => $materiUrl['error']];
        }

        $streamUrl = $this->validatedOptionalUrl((string) $this->request->getPost('stream_url'), 'Link live streaming tidak valid.');
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
                'materi_url'     => $materiUrl['url'],
                'stream_url'     => $streamUrl['url'],
                'is_publik'      => $this->request->getPost('is_publik') ? 1 : 0,
                'jenis'          => $this->postedJenis(),
            ],
            'unit_ids' => $unitIds,
        ];
    }

    private function validatedTimes(): array
    {
        $waktuMulaiRaw   = trim((string) $this->request->getPost('waktu_mulai'));
        $waktuSelesaiRaw = trim((string) $this->request->getPost('waktu_selesai'));

        $startTs = $waktuMulaiRaw !== '' ? strtotime($waktuMulaiRaw) : false;
        $endTs   = $waktuSelesaiRaw !== '' ? strtotime($waktuSelesaiRaw) : false;

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

    private function validatedOptionalUrl(string $url, string $message): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['url' => null];
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['error' => $message];
        }

        return ['url' => $url];
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
        $builder = \Config\Database::connect()
            ->table('jadwal')
            ->select('id')
            ->where('tanggal', $tanggal)
            ->where('ruangan_id', $ruanganId)
            ->where('waktu_mulai <', $waktuSelesai)
            ->where('waktu_selesai >', $waktuMulai);

        if ($ignoreJadwalId !== null) {
            $builder->where('id !=', $ignoreJadwalId);
        }

        return $builder->get(1)->getRowArray() !== null;
    }

    private function failForm(string $message, ?int $id = null)
    {
        $meeting = $this->postedMeeting($id);

        return $this->formViewErrorResponse('admin/jadwal/form', [
            'pageTitle'        => $id === null ? 'Tambah Jadwal Rapat' : 'Edit Jadwal Rapat',
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
        $waktuMulaiRaw = trim((string) $this->request->getPost('waktu_mulai'));
        $waktuSelesaiRaw = trim((string) $this->request->getPost('waktu_selesai'));
        [$tanggal, $waktuMulai] = $this->splitDateTimeLocal($waktuMulaiRaw);
        [, $waktuSelesai] = $this->splitDateTimeLocal($waktuSelesaiRaw);

        return [
            'id'              => $id,
            'judul'           => trim((string) $this->request->getPost('judul')),
            'keterangan'      => trim((string) $this->request->getPost('keterangan')),
            'tanggal'         => $tanggal,
            'waktu_mulai'     => $waktuMulai,
            'waktu_selesai'   => $waktuSelesai,
            'ruangan_id'      => (int) $this->request->getPost('ruangan_id'),
            'lokasi_lainnya'  => trim((string) $this->request->getPost('lokasi_lainnya')),
            'materi_url'      => trim((string) $this->request->getPost('materi_url')),
            'stream_url'      => trim((string) $this->request->getPost('stream_url')),
            'is_publik'       => $this->request->getPost('is_publik') ? 1 : 0,
            'jenis'           => $this->postedJenis(),
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

    private function normalizeJenis(?string $jenis): string
    {
        if ($jenis === 'bamus' || $jenis === 'reguler') {
            return 'reguler';
        }

        return 'insidental';
    }

    private function scheduleScopeLabel(int $tahun, string $semester, string $jenis, string $status): string
    {
        $parts = ["tahun {$tahun}"];

        if ($semester === '1') {
            $parts[] = 'semester I';
        } elseif ($semester === '2') {
            $parts[] = 'semester II';
        }

        if ($jenis !== 'all') {
            $parts[] = 'jenis ' . $this->filterLabel($jenis);
        }

        if ($status !== 'all') {
            $parts[] = 'status ' . $this->filterLabel($status);
        }

        return implode(', ', $parts);
    }

    private function filterLabel(string $value): string
    {
        return match ($value) {
            'reguler'     => 'reguler',
            'insidental'  => 'insidental',
            'menunggu'    => 'menunggu',
            'persiapan'   => 'persiapan',
            'berlangsung' => 'berlangsung',
            'selesai'     => 'selesai',
            default       => $value,
        };
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
