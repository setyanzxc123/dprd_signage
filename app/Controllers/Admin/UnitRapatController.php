<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnggotaModel;
use App\Models\UnitRapatModel;

class UnitRapatController extends BaseController
{
    public function index(): string
    {
        $model   = new UnitRapatModel();
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = (int) ($this->request->getGet('per_page') ?? 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $total      = $model->countAllResults();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $units = $model
            ->orderBy('urutan', 'ASC')
            ->orderBy('nama', 'ASC')
            ->findAll($perPage, $offset);

        return view('admin/unit_rapat/index', [
            'pageTitle'  => 'Kelompok Peserta',
            'units'      => $units,
            'filters'    => ['per_page' => $perPage],
            'pagination' => [
                'page'       => $page,
                'perPage'    => $perPage,
                'total'      => $total,
                'totalPages' => $totalPages,
                'from'       => $total ? $offset + 1 : 0,
                'to'         => min($offset + $perPage, $total),
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
        $payload = $this->payload();
        $unitId  = (int) $model->insert($payload, true);

        $this->syncUnitMembers($unitId);

        session()->setFlashdata('success', 'Kelompok peserta berhasil ditambahkan.');
        return redirect()->to(base_url('admin/unit-rapat'));
    }

    public function edit(int $id): string
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
        $payload = $this->payload();

        $model->update($id, $payload);
        $this->syncUnitMembers($id);

        session()->setFlashdata('success', 'Kelompok peserta berhasil diperbarui.');
        return redirect()->to(base_url('admin/unit-rapat'));
    }

    public function delete(int $id)
    {
        $model = new UnitRapatModel();
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

    private function syncUnitMembers(int $unitId): void
    {
        $db = $this->db();
        if (! $db->tableExists('anggota_unit_rapat')) {
            return;
        }

        $db->table('anggota_unit_rapat')
            ->where('unit_rapat_id', $unitId)
            ->delete();

        $anggotaIds = $this->postedAnggotaIds();
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
