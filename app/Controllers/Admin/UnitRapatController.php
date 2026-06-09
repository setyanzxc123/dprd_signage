<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UnitRapatModel;

class UnitRapatController extends BaseController
{
    private array $jenisOptions = [
        'komisi'   => 'Komisi',
        'badan'    => 'Badan',
        'pansus'   => 'Pansus',
        'gabungan' => 'Gabungan',
        'lainnya'  => 'Lainnya',
    ];

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
            'pageTitle'    => 'Unit Rapat',
            'units'        => $units,
            'jenisOptions' => $this->jenisOptions,
            'filters'      => ['per_page' => $perPage],
            'pagination'   => [
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
            'pageTitle'    => 'Tambah Unit Rapat',
            'unit'         => null,
            'jenisOptions' => $this->jenisOptions,
            'action_url'   => base_url('admin/unit-rapat/store'),
        ]);
    }

    public function store()
    {
        $model = new UnitRapatModel();
        $model->insert($this->payload());

        session()->setFlashdata('success', 'Unit rapat berhasil ditambahkan.');
        return redirect()->to(base_url('admin/unit-rapat'));
    }

    public function edit(int $id): string
    {
        $model = new UnitRapatModel();
        $unit  = $model->find($id);

        if (!$unit) {
            session()->setFlashdata('error', 'Unit rapat tidak ditemukan.');
            return redirect()->to(base_url('admin/unit-rapat'));
        }

        return view('admin/unit_rapat/form', [
            'pageTitle'    => 'Edit Unit Rapat',
            'unit'         => $unit,
            'jenisOptions' => $this->jenisOptions,
            'action_url'   => base_url("admin/unit-rapat/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $model = new UnitRapatModel();
        $model->update($id, $this->payload());

        session()->setFlashdata('success', 'Unit rapat berhasil diperbarui.');
        return redirect()->to(base_url('admin/unit-rapat'));
    }

    public function delete(int $id)
    {
        $model = new UnitRapatModel();
        $model->update($id, ['aktif' => 0]);

        session()->setFlashdata('success', 'Unit rapat berhasil dinonaktifkan.');
        return redirect()->to(base_url('admin/unit-rapat'));
    }

    private function payload(): array
    {
        $jenis = $this->request->getPost('jenis') ?? 'lainnya';

        return [
            'nama'   => trim((string) $this->request->getPost('nama')),
            'jenis'  => array_key_exists($jenis, $this->jenisOptions) ? $jenis : 'lainnya',
            'aktif'  => $this->request->getPost('aktif') ? 1 : 0,
            'urutan' => (int) ($this->request->getPost('urutan') ?? 0),
        ];
    }
}
