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
        $input = $this->validatedInput();

        if (isset($input['error'])) {
            return $this->failForm($input['error']);
        }

        $model->insert($input);

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
        $model = new RuanganModel();
        if (! $model->find($id)) {
            session()->setFlashdata('error', 'Ruangan tidak ditemukan.');
            return redirect()->to(base_url('admin/ruangan'));
        }

        $input = $this->validatedInput();

        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        $model->update($id, $input);

        return $this->formSuccessResponse('Ruangan berhasil diperbarui.', base_url('admin/ruangan'));
    }

    public function delete(int $id)
    {
        $model = new RuanganModel();
        if (! $model->find($id)) {
            session()->setFlashdata('error', 'Ruangan tidak ditemukan.');
            return redirect()->to(base_url('admin/ruangan'));
        }

        if ($this->roomHasSchedules($id)) {
            $model->update($id, ['tersedia' => 0]);

            session()->setFlashdata('success', 'Ruangan sudah pernah dipakai jadwal, sehingga hanya dinonaktifkan.');
            return redirect()->to(base_url('admin/ruangan'));
        }

        $model->delete($id);

        session()->setFlashdata('success', 'Ruangan berhasil dihapus.');
        return redirect()->to(base_url('admin/ruangan'));
    }

    private function validatedInput(): array
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return ['error' => 'Nama ruangan wajib diisi.'];
        }

        if (mb_strlen($name) > 150) {
            return ['error' => 'Nama ruangan maksimal 150 karakter.'];
        }

        $kapasitasRaw = $this->request->getPost('kapasitas');
        if ($kapasitasRaw === null || ! ctype_digit((string) $kapasitasRaw) || (int) $kapasitasRaw < 1) {
            return ['error' => 'Kapasitas ruangan wajib minimal 1 orang.'];
        }

        return [
            'name'       => $name,
            'keterangan' => trim((string) $this->request->getPost('keterangan')),
            'kapasitas'  => (int) $kapasitasRaw,
            'tersedia'   => $this->request->getPost('tersedia') === '0' ? 0 : 1,
        ];
    }

    private function roomHasSchedules(int $id): bool
    {
        return \Config\Database::connect()
            ->table('jadwal')
            ->where('ruangan_id', $id)
            ->countAllResults() > 0;
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
        return [
            'id'         => $id,
            'name'       => trim((string) $this->request->getPost('name')),
            'keterangan' => trim((string) $this->request->getPost('keterangan')),
            'kapasitas'  => trim((string) $this->request->getPost('kapasitas')),
            'tersedia'   => $this->request->getPost('tersedia') === '0' ? 0 : 1,
        ];
    }
}
