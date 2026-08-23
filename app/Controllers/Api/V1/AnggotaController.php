<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Api\ListPaginator;
use App\Libraries\Crud\AnggotaService;
use App\Models\AnggotaModel;

/**
 * Endpoint CRUD anggota DPRD untuk aplikasi mobile admin.
 * Dilindungi filter apiadmin (bearer token + grup superadmin/operator).
 * Delete mengikuti service: hapus fisik bila tanpa relasi, nonaktifkan bila masih terkait unit rapat.
 */
class AnggotaController extends BaseController
{
    use ApiResponse;

    public function index()
    {
        $paginator = ListPaginator::fromRequest();

        $model = new AnggotaModel();
        if ($paginator->hasSearch()) {
            $model->groupStart()
                ->like('name', $paginator->search)
                ->orLike('jabatan', $paginator->search)
                ->orLike('fraksi', $paginator->search)
                ->orLike('komisi', $paginator->search)
                ->groupEnd();
        }
        $total = $model->countAllResults(false);
        $members = $model->orderBy('name', 'ASC')
            ->findAll($paginator->perPage, $paginator->offset());

        return $this->apiSuccess([
            'data' => $members,
            'meta' => $paginator->meta($total),
        ]);
    }

    public function show(int $id)
    {
        $member = (new AnggotaModel())->find($id);

        if ($member === null) {
            return $this->apiError('Anggota tidak ditemukan.', 404);
        }

        return $this->apiSuccess(['data' => $member]);
    }

    public function create()
    {
        $service = new AnggotaService();
        $input = $service->validatedInput($this->requestBodyArray());

        if (isset($input['error'])) {
            return $this->apiError($input['error'], 422);
        }

        $id = $service->create($input);

        if ($id < 1) {
            return $this->apiError('Gagal menyimpan anggota.', 500);
        }

        return $this->apiSuccess([
            'data'    => (new AnggotaModel())->find($id),
            'message' => 'Anggota berhasil ditambahkan.',
        ], 201);
    }

    public function update(int $id)
    {
        $model = new AnggotaModel();
        if ($model->find($id) === null) {
            return $this->apiError('Anggota tidak ditemukan.', 404);
        }

        $service = new AnggotaService();
        $input = $service->validatedInput($this->requestBodyArray(), $id);

        if (isset($input['error'])) {
            return $this->apiError($input['error'], 422);
        }

        if (! $service->update($id, $input)) {
            return $this->apiError('Gagal memperbarui anggota.', 500);
        }

        return $this->apiSuccess([
            'data'    => $model->find($id),
            'message' => 'Data anggota berhasil diperbarui.',
        ]);
    }

    public function delete(int $id)
    {
        $outcome = (new AnggotaService())->delete($id);

        if ($outcome === 'missing') {
            return $this->apiError('Anggota tidak ditemukan.', 404);
        }

        return $this->apiSuccess([
            'message' => $outcome === 'deactivated'
                ? 'Anggota sudah terkait data lain, sehingga hanya dinonaktifkan.'
                : 'Anggota berhasil dihapus.',
            'outcome' => $outcome,
        ]);
    }
}
