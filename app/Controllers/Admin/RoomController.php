<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Crud\RuanganService;
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
        $service = new RuanganService();
        $input = $service->validatedInput($this->request->getPost());

        if (isset($input['error'])) {
            return $this->failForm($input['error']);
        }

        $service->create($input);

        return $this->formSuccessResponse('Ruangan berhasil ditambahkan.', base_url('admin/ruangan'));
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
        $service = new RuanganService();
        if (! (new RuanganModel())->find($id)) {
            session()->setFlashdata('error', 'Ruangan tidak ditemukan.');
            return redirect()->to(base_url('admin/ruangan'));
        }

        $input = $service->validatedInput($this->request->getPost());

        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        $service->update($id, $input);

        return $this->formSuccessResponse('Ruangan berhasil diperbarui.', base_url('admin/ruangan'));
    }

    public function delete(int $id)
    {
        $outcome = (new RuanganService())->delete($id);

        if ($outcome === 'missing') {
            session()->setFlashdata('error', 'Ruangan tidak ditemukan.');
        } elseif ($outcome === 'deactivated') {
            session()->setFlashdata('success', 'Ruangan sudah pernah dipakai jadwal, sehingga hanya dinonaktifkan.');
        } else {
            session()->setFlashdata('success', 'Ruangan berhasil dihapus.');
        }

        return redirect()->to(base_url('admin/ruangan'));
    }

    private function failForm(string $message, ?int $id = null)
    {
        return $this->formViewErrorResponse('admin/ruangan/form', [
            'pageTitle'  => $id === null ? 'Tambah Ruangan' : 'Edit Ruangan',
            'room'       => $this->postedRoom($id),
            'action_url' => $id === null
                ? base_url('admin/ruangan/store')
                : base_url("admin/ruangan/{$id}/update"),
        ], $message);
    }

    private function postedRoom(?int $id = null): array
    {
        $post = $this->request->getPost();

        return [
            'id'         => $id,
            'name'       => trim((string) ($post['name'] ?? '')),
            'keterangan' => trim((string) ($post['keterangan'] ?? '')),
            'kapasitas'  => trim((string) ($post['kapasitas'] ?? '')),
            'tersedia'   => ($post['tersedia'] ?? null) === '0' ? 0 : 1,
        ];
    }
}
