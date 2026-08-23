<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Api\ListPaginator;
use App\Libraries\Crud\UnitRapatService;
use App\Models\UnitRapatModel;

/**
 * Endpoint CRUD kelompok peserta (unit rapat) untuk aplikasi mobile admin.
 * Dilindungi filter apiadmin (bearer token + grup superadmin/operator).
 * Delete selalu berupa deaktivasi — unit tidak pernah dihapus fisik
 * agar relasi historis pada jadwal tetap bermakna.
 */
class UnitRapatController extends BaseController
{
    use ApiResponse;

    public function index()
    {
        $paginator = ListPaginator::fromRequest();

        $model = new UnitRapatModel();
        if ($paginator->hasSearch()) {
            $model->like('nama', $paginator->search);
        }
        $total = $model->countAllResults(false);
        $units = $model->orderBy('urutan', 'ASC')
            ->orderBy('nama', 'ASC')
            ->findAll($paginator->perPage, $paginator->offset());

        return $this->apiSuccess([
            'data' => $units,
            'meta' => $paginator->meta($total),
        ]);
    }

    public function show(int $id)
    {
        $unit = (new UnitRapatModel())->find($id);

        if ($unit === null) {
            return $this->apiError('Kelompok peserta tidak ditemukan.', 404);
        }

        return $this->apiSuccess($this->unitPayload($id));
    }

    public function create()
    {
        $service = new UnitRapatService();
        $input = $service->validatedInput($this->requestBodyArray());

        if (isset($input['error'])) {
            return $this->apiError($input['error'], 422);
        }

        $id = $service->create($input);

        return $this->apiSuccess(
            $this->unitPayload($id) + ['message' => 'Kelompok peserta berhasil ditambahkan.'],
            201
        );
    }

    public function update(int $id)
    {
        $model = new UnitRapatModel();
        if ($model->find($id) === null) {
            return $this->apiError('Kelompok peserta tidak ditemukan.', 404);
        }

        $service = new UnitRapatService();
        $input = $service->validatedInput($this->requestBodyArray(), $id);

        if (isset($input['error'])) {
            return $this->apiError($input['error'], 422);
        }

        $service->update($id, $input);

        return $this->apiSuccess(
            $this->unitPayload($id) + ['message' => 'Kelompok peserta berhasil diperbarui.']
        );
    }

    public function delete(int $id)
    {
        if ((new UnitRapatModel())->find($id) === null) {
            return $this->apiError('Kelompok peserta tidak ditemukan.', 404);
        }

        (new UnitRapatService())->deactivate($id);

        return $this->apiSuccess([
            'message' => 'Kelompok peserta berhasil dinonaktifkan.',
            'outcome' => 'deactivated',
        ]);
    }

    /** Baris unit beserta id anggota terhubung — payload respons show/create/update. */
    private function unitPayload(int $id): array
    {
        return [
            'data'        => (new UnitRapatModel())->find($id),
            'anggota_ids' => (new UnitRapatService())->memberIdsForUnit($id),
        ];
    }
}
