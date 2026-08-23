<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Crud\UnitRapatService;
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
        $service = new UnitRapatService();
        $input = $service->validatedInput($this->request->getPost());

        if (isset($input['error'])) {
            return $this->failForm($input['error']);
        }

        $service->create($input);

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
            'selectedAnggotaIds' => (new UnitRapatService())->memberIdsForUnit($id),
            'action_url'         => base_url("admin/unit-rapat/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $service = new UnitRapatService();
        if (! (new UnitRapatModel())->find($id)) {
            session()->setFlashdata('error', 'Unit rapat tidak ditemukan.');
            return redirect()->to(base_url('admin/unit-rapat'));
        }

        $input = $service->validatedInput($this->request->getPost(), $id);

        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        $service->update($id, $input);

        return $this->formSuccessResponse('Kelompok peserta berhasil diperbarui.', base_url('admin/unit-rapat'));
    }

    public function delete(int $id)
    {
        $service = new UnitRapatService();

        if (! (new UnitRapatModel())->find($id)) {
            session()->setFlashdata('error', 'Unit rapat tidak ditemukan.');
            return redirect()->to(base_url('admin/unit-rapat'));
        }

        $service->deactivate($id);

        session()->setFlashdata('success', 'Kelompok peserta berhasil dinonaktifkan.');
        return redirect()->to(base_url('admin/unit-rapat'));
    }

    private function memberOptions(): array
    {
        return (new AnggotaModel())
            ->where('aktif', 1)
            ->orderBy('komisi', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    private function failForm(string $message, ?int $id = null)
    {
        $post = $this->request->getPost();
        $ids = $post['anggota_unit_rapat'] ?? [];
        $ids = array_map('intval', is_array($ids) ? $ids : [$ids]);
        $ids = array_values(array_unique(array_filter($ids, static fn (int $v): bool => $v > 0)));

        return $this->formViewErrorResponse('admin/unit_rapat/form', [
            'pageTitle'          => $id === null ? 'Tambah Kelompok Peserta' : 'Edit Kelompok Peserta',
            'unit'               => [
                'id'    => $id,
                'nama'  => trim((string) ($post['nama'] ?? '')),
                'aktif' => ! empty($post['aktif']) ? 1 : 0,
            ],
            'members'            => $this->memberOptions(),
            'selectedAnggotaIds' => $ids,
            'action_url'         => $id === null
                ? base_url('admin/unit-rapat/store')
                : base_url("admin/unit-rapat/{$id}/update"),
        ], $message);
    }
}
