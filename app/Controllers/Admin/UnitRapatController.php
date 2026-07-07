<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnggotaModel;
use App\Models\UnitRapatModel;

class UnitRapatController extends BaseController
{
    public function index(): string
    {
        $units = (new UnitRapatModel())
            ->orderBy('urutan', 'ASC')
            ->orderBy('nama', 'ASC')
            ->findAll();

        return view('admin/unit_rapat/index', [
            'pageTitle'  => 'Kelompok Peserta',
            'units'      => $units,
            'data_scope' => [
                'label' => 'seluruh kelompok, termasuk nonaktif',
            ],
        ]);
    }

    public function create(): string
    {
        return view('admin/unit_rapat/form', [
            'pageTitle'          => 'Tambah Kelompok Peserta',
            'unit'               => null,
            'members'            => $this->memberOptions(),
            'selectedAnggotaIds' => [],
            'action_url'         => base_url('admin/unit-rapat/store'),
        ]);
    }

    public function store()
    {
        $model   = new UnitRapatModel();
        $input = $this->validatedInput();

        if (isset($input['error'])) {
            return $this->failForm($input['error']);
        }

        $payload = $input['payload'];
        $unitId  = (int) $model->insert($payload, true);

        $this->syncUnitMembers($unitId, $input['anggota_ids']);

        return $this->formSuccessResponse('Kelompok peserta berhasil ditambahkan.', base_url('admin/unit-rapat'));
    }

    public function edit(int $id)
    {
        $model = new UnitRapatModel();
        $unit  = $model->find($id);

        if (! $unit) {
            session()->setFlashdata('error', 'Unit rapat tidak ditemukan.');
            return redirect()->to(base_url('admin/unit-rapat'));
        }

        return view('admin/unit_rapat/form', [
            'pageTitle'          => 'Edit Kelompok Peserta',
            'unit'               => $unit,
            'members'            => $this->memberOptions(),
            'selectedAnggotaIds' => $this->selectedAnggotaIds($id),
            'action_url'         => base_url("admin/unit-rapat/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $model   = new UnitRapatModel();
        if (! $model->find($id)) {
            session()->setFlashdata('error', 'Unit rapat tidak ditemukan.');
            return redirect()->to(base_url('admin/unit-rapat'));
        }

        $input = $this->validatedInput($id);

        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        $payload = $input['payload'];

        $model->update($id, $payload);
        $this->syncUnitMembers($id, $input['anggota_ids']);

        return $this->formSuccessResponse('Kelompok peserta berhasil diperbarui.', base_url('admin/unit-rapat'));
    }

    public function delete(int $id)
    {
        $model = new UnitRapatModel();
        if (! $model->find($id)) {
            session()->setFlashdata('error', 'Unit rapat tidak ditemukan.');
            return redirect()->to(base_url('admin/unit-rapat'));
        }

        $model->update($id, ['aktif' => 0]);

        session()->setFlashdata('success', 'Kelompok peserta berhasil dinonaktifkan.');
        return redirect()->to(base_url('admin/unit-rapat'));
    }

    private function payload(): array
    {
        return [
            'nama'  => trim((string) $this->request->getPost('nama')),
            'aktif' => $this->request->getPost('aktif') ? 1 : 0,
        ];
    }

    private function memberOptions(): array
    {
        return (new AnggotaModel())
            ->where('aktif', 1)
            ->orderBy('komisi', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    private function selectedAnggotaIds(int $unitId): array
    {
        if (! $this->db()->tableExists('anggota_unit_rapat')) {
            return [];
        }

        $rows = $this->db()
            ->table('anggota_unit_rapat')
            ->select('anggota_id')
            ->where('unit_rapat_id', $unitId)
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'anggota_id'));
    }

    private function postedAnggotaIds(): array
    {
        $ids = $this->request->getPost('anggota_unit_rapat') ?? [];
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

        return array_values(array_unique($ids));
    }

    private function validatedInput(?int $unitId = null): array
    {
        $payload = $this->payload();

        if ($payload['nama'] === '') {
            return ['error' => 'Nama kelompok peserta wajib diisi.'];
        }

        if (mb_strlen($payload['nama']) > 150) {
            return ['error' => 'Nama kelompok peserta maksimal 150 karakter.'];
        }

        if ($this->unitNameExists($payload['nama'], $unitId)) {
            return ['error' => 'Nama kelompok peserta sudah digunakan.'];
        }

        $anggotaIds = $this->postedAnggotaIds();
        if ((int) $payload['aktif'] === 1 && empty($anggotaIds)) {
            return ['error' => 'Kelompok peserta aktif wajib memiliki minimal satu anggota.'];
        }

        if (! empty($anggotaIds) && ! empty($this->invalidActiveAnggotaIds($anggotaIds))) {
            return ['error' => 'Anggota yang dipilih tidak valid atau sudah nonaktif.'];
        }

        return [
            'payload'     => $payload,
            'anggota_ids' => $anggotaIds,
        ];
    }

    private function unitNameExists(string $nama, ?int $ignoreId): bool
    {
        $model = new UnitRapatModel();
        $model->where('nama', $nama);

        if ($ignoreId !== null) {
            $model->where('id !=', $ignoreId);
        }

        return $model->first() !== null;
    }

    private function invalidActiveAnggotaIds(array $anggotaIds): array
    {
        $rows = (new AnggotaModel())
            ->select('id')
            ->where('aktif', 1)
            ->whereIn('id', $anggotaIds)
            ->findAll();

        $validIds = array_map('intval', array_column($rows, 'id'));

        return array_values(array_diff($anggotaIds, $validIds));
    }

    private function failForm(string $message, ?int $id = null)
    {
        return $this->formViewErrorResponse('admin/unit_rapat/form', [
            'pageTitle'          => $id === null ? 'Tambah Kelompok Peserta' : 'Edit Kelompok Peserta',
            'unit'               => $this->postedUnit($id),
            'members'            => $this->memberOptions(),
            'selectedAnggotaIds' => $this->postedAnggotaIds(),
            'action_url'         => $id === null
                ? base_url('admin/unit-rapat/store')
                : base_url("admin/unit-rapat/{$id}/update"),
        ], $message);
    }

    private function postedUnit(?int $id = null): array
    {
        return [
            'id'    => $id,
            'nama'  => trim((string) $this->request->getPost('nama')),
            'aktif' => $this->request->getPost('aktif') ? 1 : 0,
        ];
    }

    private function syncUnitMembers(int $unitId, array $anggotaIds): void
    {
        $db = $this->db();
        if (! $db->tableExists('anggota_unit_rapat')) {
            return;
        }

        $db->table('anggota_unit_rapat')
            ->where('unit_rapat_id', $unitId)
            ->delete();

        if (empty($anggotaIds)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $rows = array_map(static fn (int $anggotaId): array => [
            'anggota_id'    => $anggotaId,
            'unit_rapat_id' => $unitId,
            'created_at'    => $now,
        ], $anggotaIds);

        $db->table('anggota_unit_rapat')->insertBatch($rows);
    }

    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect();
    }
}
