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

        $itemModel = new JadwalBanmusModel();
        $itemModel->autoUpdateStatuses($id);
        $items = $itemModel->findGroupedByDocumentIds([$id])[$id] ?? [];
        $document['jumlah_item'] = count($items);

        return $this->apiSuccess([
            'data'  => $document,
            'items' => $itemModel->attachUnitIds($items),
        ]);
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

    // ── ITEM AGENDA ──────────────────────────────────────────────────

    public function indexItems(int $documentId)
    {
        if ((new BanmusDocumentModel())->find($documentId) === null) {
            return $this->apiError('Dokumen SK Banmus tidak ditemukan.', 404);
        }

        $itemModel = new JadwalBanmusModel();
        $itemModel->autoUpdateStatuses($documentId);
        $items = $itemModel->where('dokumen_banmus_id', $documentId)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->apiSuccess(['data' => $itemModel->attachUnitIds($items)]);
    }

    public function showItem(int $documentId, int $itemId)
    {
        $item = $this->findItem($documentId, $itemId);

        if ($item === null) {
            return $this->apiError('Item agenda tidak ditemukan.', 404);
        }

        [$item] = (new JadwalBanmusModel())->attachUnitIds([$item]);

        return $this->apiSuccess(['data' => $item]);
    }

    public function storeItem(int $documentId)
    {
        $document = (new BanmusDocumentModel())->find($documentId);
        if ($document === null) {
            return $this->apiError('Dokumen SK Banmus tidak ditemukan.', 404);
        }

        $service = new JadwalBanmusService();
        $validated = $service->validatedItemPayload(
            $this->requestBodyArray(),
            $this->request->getFile('undangan_file'),
            null,
            (int) $document['tahun'],
        );

        if (isset($validated['error'])) {
            return $this->apiError($validated['error'], 422);
        }

        $result = $service->storeItem($documentId, $validated);
        if (isset($result['error'])) {
            return $this->apiError($result['error'], 422);
        }

        [$item] = (new JadwalBanmusModel())->attachUnitIds(
            [(new JadwalBanmusModel())->find($result['id'])]
        );

        return $this->apiSuccess([
            'data'    => $item,
            'message' => $result['status'] !== 'proyeksi'
                ? 'Item agenda berhasil disimpan sebagai jadwal.'
                : 'Item agenda berhasil disimpan sebagai proyeksi. Data pelaksanaan dapat dilengkapi kemudian.',
        ], 201);
    }

    public function updateItem(int $documentId, int $itemId)
    {
        $item = $this->findItem($documentId, $itemId);
        if ($item === null) {
            return $this->apiError('Item agenda tidak ditemukan.', 404);
        }

        $document = (new BanmusDocumentModel())->find($documentId);
        if ($document === null) {
            return $this->apiError('Dokumen SK Banmus tidak ditemukan.', 404);
        }

        $service = new JadwalBanmusService();
        $validated = $service->validatedItemPayload(
            $this->requestBodyArray(),
            null,
            $item,
            (int) $document['tahun'],
        );

        if (isset($validated['error'])) {
            return $this->apiError($validated['error'], 422);
        }

        $error = $service->updateItem($item, $validated);
        if ($error !== null) {
            return $this->apiError($error, 422);
        }

        [$updated] = (new JadwalBanmusModel())->attachUnitIds(
            [(new JadwalBanmusModel())->find($itemId)]
        );

        return $this->apiSuccess([
            'data'    => $updated,
            'message' => $service->resolveUpdatedStatus($validated) !== 'proyeksi'
                ? 'Item agenda dan jadwal Banmus berhasil diperbarui.'
                : 'Item agenda berhasil disimpan sebagai proyeksi. Data pelaksanaan dapat dilengkapi kemudian.',
        ]);
    }

    public function deleteItem(int $documentId, int $itemId)
    {
        $item = $this->findItem($documentId, $itemId);
        if ($item === null) {
            return $this->apiError('Item agenda tidak ditemukan.', 404);
        }

        (new JadwalBanmusService())->deleteItem($item);

        return $this->apiSuccess([
            'message' => 'Item agenda berhasil dihapus.',
            'outcome' => 'deleted',
        ]);
    }

    /**
     * Ganti PDF undangan item — multipart POST (undangan jadi
     * sub-resource karena PUT/PATCH tak bisa multipart).
     */
    public function storeItemInvitation(int $documentId, int $itemId)
    {
        if ($this->findItem($documentId, $itemId) === null) {
            return $this->apiError('Item agenda tidak ditemukan.', 404);
        }

        $error = (new JadwalBanmusService())->replaceItemInvitation($itemId, $this->request->getFile('undangan_file'));
        if ($error !== null) {
            return $this->apiError($error, 422);
        }

        [$item] = (new JadwalBanmusModel())->attachUnitIds(
            [(new JadwalBanmusModel())->find($itemId)]
        );

        return $this->apiSuccess($item !== null
            ? ['data' => $item, 'message' => 'Undangan berhasil diunggah.']
            : ['message' => 'Undangan berhasil diunggah.']);
    }

    public function deleteItemInvitation(int $documentId, int $itemId)
    {
        if ($this->findItem($documentId, $itemId) === null) {
            return $this->apiError('Item agenda tidak ditemukan.', 404);
        }

        (new JadwalBanmusService())->removeItemInvitation($itemId);

        return $this->apiSuccess([
            'message' => 'Undangan berhasil dihapus.',
            'outcome' => 'deleted',
        ]);
    }

    /** Item dalam lingkup dokumennya — null bila tidak ada di dokumen itu. */
    private function findItem(int $documentId, int $itemId): ?array
    {
        $item = (new JadwalBanmusModel())
            ->where('dokumen_banmus_id', $documentId)
            ->find($itemId);

        return $item ?? null;
    }
}
