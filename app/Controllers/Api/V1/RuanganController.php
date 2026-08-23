<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Api\ListPaginator;
use App\Libraries\Crud\RuanganService;
use App\Models\RuanganModel;

/**
 * Endpoint CRUD ruangan rapat untuk aplikasi mobile admin.
 * Dilindungi filter apiadmin (bearer token + grup superadmin/operator).
 */
class RuanganController extends BaseController
{
    use ApiResponse;

    public function index()
    {
        $paginator = ListPaginator::fromRequest();

        $model = new RuanganModel();
        if ($paginator->hasSearch()) {
            $model->groupStart()
                ->like('name', $paginator->search)
                ->orLike('keterangan', $paginator->search)
                ->groupEnd();
        }
        $total = $model->countAllResults(false);
        $rooms = $model->orderBy('name', 'ASC')
            ->findAll($paginator->perPage, $paginator->offset());

        return $this->apiSuccess([
            'data' => $rooms,
            'meta' => $paginator->meta($total),
        ]);
    }

    public function show(int $id)
    {
        $room = (new RuanganModel())->find($id);

        if ($room === null) {
            return $this->apiError('Ruangan tidak ditemukan.', 404);
        }

        return $this->apiSuccess(['data' => $room]);
    }

    public function create()
    {
        $service = new RuanganService();
        $input = $service->validatedInput($this->requestBodyArray());

        if (isset($input['error'])) {
            return $this->apiError($input['error'], 422);
        }

        $id = $service->create($input);
        $room = (new RuanganModel())->find($id);

        return $this->apiSuccess(['data' => $room, 'message' => 'Ruangan berhasil ditambahkan.'], 201);
    }

    public function update(int $id)
    {
        $model = new RuanganModel();
        if ($model->find($id) === null) {
            return $this->apiError('Ruangan tidak ditemukan.', 404);
        }

        $service = new RuanganService();
        $input = $service->validatedInput($this->requestBodyArray());

        if (isset($input['error'])) {
            return $this->apiError($input['error'], 422);
        }

        $service->update($id, $input);

        return $this->apiSuccess(['data' => $model->find($id), 'message' => 'Ruangan berhasil diperbarui.']);
    }

    public function delete(int $id)
    {
        $outcome = (new RuanganService())->delete($id);

        if ($outcome === 'missing') {
            return $this->apiError('Ruangan tidak ditemukan.', 404);
        }

        return $this->apiSuccess([
            'message' => $outcome === 'deactivated'
                ? 'Ruangan sudah pernah dipakai jadwal, sehingga hanya dinonaktifkan.'
                : 'Ruangan berhasil dihapus.',
            'outcome' => $outcome,
        ]);
    }
}
