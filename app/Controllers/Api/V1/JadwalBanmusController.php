<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Api\ListPaginator;
use App\Libraries\Crud\JadwalBanmusService;
use App\Models\BanmusDocumentModel;
use App\Models\JadwalBanmusModel;

/**
 * Endpoint CRUD Agenda Banmus untuk aplikasi mobile admin: dokumen SK
 * (unggah PDF, keunikan nomor/semester) dan item agenda (proyeksi vs
 * terjadwal). Dilindungi filter apiadmin (bearer token + grup
 * superadmin/operator).
 */
class JadwalBanmusController extends BaseController
{
    use ApiResponse;

    // ── DOKUMEN SK ───────────────────────────────────────────────────

    public function index()
    {
        $paginator = ListPaginator::fromRequest();

        $model = new BanmusDocumentModel();
        if ($paginator->hasSearch()) {
            $model->groupStart()
                ->like('judul', $paginator->search)
                ->orLike('nomor_sk', $paginator->search)
                ->groupEnd();
        }
        $total = $model->countAllResults(false);
        $documents = $model->orderBy('tahun', 'DESC')
            ->orderBy('semester', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($paginator->perPage, $paginator->offset());

        $groupedItems = (new JadwalBanmusModel())->findGroupedByDocumentIds(
            array_map('intval', array_column($documents, 'id')),
        );
        $data = [];
        foreach ($documents as $document) {
            $document['jumlah_item'] = count($groupedItems[(int) $document['id']] ?? []);
            $data[] = $document;
        }

        return $this->apiSuccess([
            'data' => $data,
            'meta' => $paginator->meta($total),
        ]);
    }

    public function show(int $id)
    {
        $document = (new BanmusDocumentModel())->find($id);

        if ($document === null) {
            return $this->apiError('Dokumen SK Banmus tidak ditemukan.', 404);
        }

        $document['jumlah_item'] = count(
            (new JadwalBanmusModel())->findGroupedByDocumentIds([$id])[$id] ?? []
        );

        return $this->apiSuccess(['data' => $document]);
    }

    public function create()
    {
        $service = new JadwalBanmusService();
        $input = $service->validatedSkForm(
            $this->requestBodyArray(),
            $this->request->getFile('dokumen_file'),
        );

        if (isset($input['error'])) {
            return $this->apiError($input['error'], 422);
        }

        $result = $service->persistSk(null, $input);
        if (isset($result['error'])) {
            return $this->apiError($result['error'], 422);
        }

        return $this->apiSuccess([
            'data'    => (new BanmusDocumentModel())->find($result['id']),
            'message' => 'Dokumen SK Banmus berhasil ditambahkan.',
        ], 201);
    }

    public function update(int $id)
    {
        $model = new BanmusDocumentModel();
        $existing = $model->find($id);
        if ($existing === null) {
            return $this->apiError('Dokumen SK Banmus tidak ditemukan.', 404);
        }

        $service = new JadwalBanmusService();
        $input = $service->validatedSkForm($this->requestBodyArray(), null, $existing);

        if (isset($input['error'])) {
            return $this->apiError($input['error'], 422);
        }

        $result = $service->persistSk($id, $input, $existing);
        if (isset($result['error'])) {
            return $this->apiError($result['error'], 422);
        }

        return $this->apiSuccess([
            'data'    => $model->find($id),
            'message' => 'Dokumen SK Banmus berhasil diperbarui.',
        ]);
    }

    public function delete(int $id)
    {
        $existing = (new BanmusDocumentModel())->find($id);
        if ($existing === null) {
            return $this->apiError('Dokumen SK Banmus tidak ditemukan.', 404);
        }

        if (! (new JadwalBanmusService())->deleteDocument($existing)) {
            return $this->apiError('Dokumen SK Banmus gagal dihapus.', 422);
        }

        return $this->apiSuccess([
            'message' => 'Dokumen SK Banmus berhasil dihapus.',
            'outcome' => 'deleted',
        ]);
    }

    /**
     * Ganti berkas PDF SK — multipart POST (PHP tidak memparsing
     * multipart untuk PUT/PATCH, sehingga dokumen jadi sub-resource).
     */
    public function storeDocument(int $id)
    {
        $model = new BanmusDocumentModel();
        if ($model->find($id) === null) {
            return $this->apiError('Dokumen SK Banmus tidak ditemukan.', 404);
        }

        $error = (new JadwalBanmusService())->replaceDocument($id, $this->request->getFile('dokumen_file'));
        if ($error !== null) {
            return $this->apiError($error, 422);
        }

        return $this->apiSuccess([
            'data'    => $model->find($id),
            'message' => 'Dokumen SK berhasil diunggah.',
        ]);
    }
}
