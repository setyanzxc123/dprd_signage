<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnggotaModel;

class MemberController extends BaseController
{
    private array $fraksiList = [
        'Golkar', 'PDI-P', 'Gerindra', 'PKB', 'NasDem',
        'PKS', 'Demokrat', 'PAN', 'PPP', 'Hanura',
    ];

    private array $komisiList = [
        'Komisi I', 'Komisi II', 'Komisi III', 'Komisi IV', 'Pansus', 'All Komisi',
    ];

    public function index(): string
    {
        $model = new AnggotaModel();

        return view('admin/anggota/index', [
            'pageTitle' => 'Anggota DPRD',
            'members'   => $model->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function create(): string
    {
        return view('admin/anggota/form', [
            'pageTitle'   => 'Tambah Anggota',
            'member'      => null,
            'fraksi_list' => $this->fraksiList,
            'komisi_list' => $this->komisiList,
            'action_url'  => base_url('admin/anggota/store'),
        ]);
    }

    public function store()
    {
        $model = new AnggotaModel();
        $model->insert([
            'name'    => $this->request->getPost('name'),
            'jabatan' => $this->request->getPost('jabatan'),
            'fraksi'  => $this->request->getPost('fraksi'),
            'komisi'  => $this->request->getPost('komisi'),
            'no_wa'   => $this->request->getPost('no_wa'),
            'aktif'   => $this->request->getPost('aktif') ?? 1,
        ]);

        session()->setFlashdata('success', 'Anggota berhasil ditambahkan.');
        return redirect()->to(base_url('admin/anggota'));
    }

    public function edit(int $id): string
    {
        $model  = new AnggotaModel();
        $member = $model->find($id);

        if (!$member) {
            session()->setFlashdata('error', 'Anggota tidak ditemukan.');
            return redirect()->to(base_url('admin/anggota'));
        }

        return view('admin/anggota/form', [
            'pageTitle'   => 'Edit Anggota',
            'member'      => $member,
            'fraksi_list' => $this->fraksiList,
            'komisi_list' => $this->komisiList,
            'action_url'  => base_url("admin/anggota/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $model = new AnggotaModel();
        $model->update($id, [
            'name'    => $this->request->getPost('name'),
            'jabatan' => $this->request->getPost('jabatan'),
            'fraksi'  => $this->request->getPost('fraksi'),
            'komisi'  => $this->request->getPost('komisi'),
            'no_wa'   => $this->request->getPost('no_wa'),
            'aktif'   => $this->request->getPost('aktif') ?? 1,
        ]);

        session()->setFlashdata('success', 'Data anggota berhasil diperbarui.');
        return redirect()->to(base_url('admin/anggota'));
    }

    public function delete(int $id)
    {
        $model = new AnggotaModel();
        $model->delete($id);

        session()->setFlashdata('success', 'Anggota berhasil dihapus.');
        return redirect()->to(base_url('admin/anggota'));
    }
}
