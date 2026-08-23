<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Api\ListPaginator;
use App\Libraries\Crud\JadwalUmumService;
use App\Models\JadwalUmumModel;

/**
 * Endpoint CRUD jadwal umum untuk aplikasi mobile admin.
 * Dilindungi filter apiadmin (bearer token + grup superadmin/operator).
 * Create menerima undangan PDF multipart (field undangan_file); update
 * via PUT/PATCH hanya menerima JSON/form — penggantian berkas lewat
 * endpoint undangan terpisah, sementara penghapusan cukup flag
 * hapus_undangan=1.
 */
class JadwalUmumController extends BaseController
{
    use ApiResponse;

    public function index()
    {
        $paginator = ListPaginator::fromRequest();

        $model = new JadwalUmumModel();
        if ($paginator->hasSearch()) {
            $model->groupStart()
                ->like('judul', $paginator->search)
                ->orLike('pihak_eksternal', $paginator->search)
                ->orLike('lokasi_lainnya', $paginator->search)
                ->groupEnd();
        }
        $total = $model->countAllResults(false);
        $rows = $model->orderBy('tanggal', 'DESC')
            ->orderBy('waktu_mulai', 'DESC')
            ->findAll($paginator->perPage, $paginator->offset());

        $unitNames = (new JadwalUmumService())->unitNamesByScheduleIds(array_column($rows, 'id'));
        $data = [];
        foreach ($rows as $row) {
            $row['status'] = JadwalUmumModel::resolveLifecycleStatus(
                (string) $row['tanggal'],
                $row['waktu_mulai'],
                $row['waktu_selesai'],
            );
            $row['unit_names'] = $unitNames[(int) $row['id']] ?? [];
            $data[] = $row;
        }

        return $this->apiSuccess([
            'data' => $data,
            'meta' => $paginator->meta($total),
        ]);
    }

    public function show(int $id)
    {
        $schedule = (new JadwalUmumModel())->find($id);

        if ($schedule === null) {
            return $this->apiError('Jadwal Umum tidak ditemukan.', 404);
        }

        return $this->apiSuccess($this->schedulePayload($schedule));
    }

    public function create()
    {
        $service = new JadwalUmumService();
        $input = $service->validatedInput(
            $this->requestBodyArray(),
            $this->request->getFile('undangan_file'),
        );

        if (isset($input['error'])) {
            return $this->apiError($input['error'], 422);
        }

        $id = $service->create($input);
        if (is_string($id)) {
            return $this->apiError($id, 422);
        }

        return $this->apiSuccess(
            $this->schedulePayload((new JadwalUmumModel())->find($id))
                + ['message' => 'Jadwal Umum berhasil ditambahkan.'],
            201
        );
    }

    public function update(int $id)
    {
        $model = new JadwalUmumModel();
        $existing = $model->find($id);
        if ($existing === null) {
            return $this->apiError('Jadwal Umum tidak ditemukan.', 404);
        }

        $service = new JadwalUmumService();
        $input = $service->validatedInput($this->requestBodyArray(), null, $existing);

        if (isset($input['error'])) {
            return $this->apiError($input['error'], 422);
        }

        $error = $service->update($id, $input, $existing);
        if ($error !== null) {
            return $this->apiError($error, 422);
        }

        return $this->apiSuccess(
            $this->schedulePayload($model->find($id)) + ['message' => 'Jadwal Umum berhasil diperbarui.']
        );
    }

    public function delete(int $id)
    {
        $existing = (new JadwalUmumModel())->find($id);
        if ($existing === null) {
            return $this->apiError('Jadwal Umum tidak ditemukan.', 404);
        }

        (new JadwalUmumService())->delete($existing);

        return $this->apiSuccess([
            'message' => 'Jadwal Umum berhasil dihapus.',
            'outcome' => 'deleted',
        ]);
    }

    /**
     * Unggah/ganti PDF undangan — multipart POST (PHP tidak memparsing
     * multipart untuk PUT/PATCH, sehingga undangan jadi sub-resource).
     */
    public function storeInvitation(int $id)
    {
        $model = new JadwalUmumModel();
        if ($model->find($id) === null) {
            return $this->apiError('Jadwal Umum tidak ditemukan.', 404);
        }

        $error = (new JadwalUmumService())->replaceInvitation($id, $this->request->getFile('undangan_file'));
        if ($error !== null) {
            return $this->apiError($error, 422);
        }

        return $this->apiSuccess(
            $this->schedulePayload($model->find($id)) + ['message' => 'Undangan berhasil diunggah.']
        );
    }

    public function deleteInvitation(int $id)
    {
        if ((new JadwalUmumModel())->find($id) === null) {
            return $this->apiError('Jadwal Umum tidak ditemukan.', 404);
        }

        (new JadwalUmumService())->removeInvitation($id);

        return $this->apiSuccess([
            'message' => 'Undangan berhasil dihapus.',
            'outcome' => 'deleted',
        ]);
    }

    /** Baris jadwal + status lifecycle, dengan id unit terhubung sebagai sibling. */
    private function schedulePayload(array $schedule): array
    {
        $schedule['status'] = JadwalUmumModel::resolveLifecycleStatus(
            (string) $schedule['tanggal'],
            $schedule['waktu_mulai'],
            $schedule['waktu_selesai'],
        );

        return [
            'data'     => $schedule,
            'unit_ids' => (new JadwalUmumService())->unitIdsForSchedule((int) $schedule['id']),
        ];
    }
}
