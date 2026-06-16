<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RuanganModel;

class RoomController extends BaseController
{
    public function index(): string
    {
        $rooms = (new RuanganModel())
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('admin/ruangan/index', [
            'pageTitle' => 'Ruangan Rapat',
            'rooms'     => $rooms,
            'data_scope' => [
                'label' => 'seluruh master ruangan tetap',
            ],
        ]);
    }

    public function create(): string
    {
        return view('admin/ruangan/form', [
            'pageTitle'  => 'Tambah Ruangan',
            'room'       => null,
            'action_url' => base_url('admin/ruangan/store'),
        ]);
    }

    public function store()
    {
        $model = new RuanganModel();
        $model->insert([
            'name'       => $this->request->getPost('name'),
            'keterangan' => $this->request->getPost('keterangan'),
            'kapasitas'  => (int) $this->request->getPost('kapasitas'),
            'tersedia'   => $this->request->getPost('tersedia') ?? 1,
        ]);

        session()->setFlashdata('success', 'Ruangan berhasil ditambahkan.');
        return redirect()->to(base_url('admin/ruangan'));
    }

    public function edit(int $id)
    {
        $model  = new RuanganModel();
        $room   = $model->find($id);

        if (!$room) {
            session()->setFlashdata('error', 'Ruangan tidak ditemukan.');
            return redirect()->to(base_url('admin/ruangan'));
        }

        return view('admin/ruangan/form', [
            'pageTitle'  => 'Edit Ruangan',
            'room'       => $room,
            'action_url' => base_url("admin/ruangan/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $model = new RuanganModel();
        $model->update($id, [
            'name'       => $this->request->getPost('name'),
            'keterangan' => $this->request->getPost('keterangan'),
            'kapasitas'  => (int) $this->request->getPost('kapasitas'),
            'tersedia'   => $this->request->getPost('tersedia') ?? 1,
        ]);

        session()->setFlashdata('success', 'Ruangan berhasil diperbarui.');
        return redirect()->to(base_url('admin/ruangan'));
    }

    public function delete(int $id)
    {
        $model = new RuanganModel();
        $model->delete($id);

        session()->setFlashdata('success', 'Ruangan berhasil dihapus.');
        return redirect()->to(base_url('admin/ruangan'));
    }
}
