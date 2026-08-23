<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Crud\JadwalBanmusService;
use App\Models\BanmusDocumentModel;
use App\Models\JadwalBanmusModel;
use App\Models\RuanganModel;
use App\Models\UnitRapatModel;

class JadwalBanmusController extends BaseController
{
    public function index(): string
    {
        $documents = (new BanmusDocumentModel())
            ->orderBy('tahun', 'DESC')
            ->orderBy('semester', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();

        $groupedItems = (new JadwalBanmusModel())->findGroupedByDocumentIds(
            array_map('intval', array_column($documents, 'id')),
        );

        foreach ($documents as &$document) {
            $document['jumlah_item'] = count($groupedItems[(int) $document['id']] ?? []);
        }
        unset($document);

        return view('admin/banmus/index', [
            'pageTitle' => 'Agenda Banmus',
            'documents' => $documents,
        ]);
    }

    public function create(): string
    {
        return view('admin/banmus/form', [
            'pageTitle'  => 'Tambah SK Banmus',
            'document'   => null,
            'action_url' => base_url('admin/jadwal-banmus/store'),
        ]);
    }

    public function store()
    {
        $service = new JadwalBanmusService();
        $input = $service->validatedSkForm($this->request->getPost(), $this->request->getFile('dokumen_file'));

        if (isset($input['error'])) {
            return $this->failSkForm($input['error']);
        }

        $result = $service->persistSk(null, $input);
        if (isset($result['error'])) {
            return $this->failSkForm($result['error']);
        }

        return $this->formSuccessResponse(
            'Dokumen SK Banmus berhasil disimpan. Silakan kelola item agenda di bawah.',
            base_url("admin/jadwal-banmus/{$result['id']}"),
        );
    }

    public function show(int $id)
    {
        $document = (new BanmusDocumentModel())->find($id);
        if ($document === null) {
            session()->setFlashdata('error', 'Dokumen SK Banmus tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }

        $itemModel = new JadwalBanmusModel();
        $itemModel->autoUpdateStatuses($id);
        $items = $itemModel
            ->where('dokumen_banmus_id', $id)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
        $items = $itemModel->attachUnitIds($items);

        return view('admin/banmus/show', [
            'pageTitle' => "Agenda SK Banmus No. {$document['nomor_sk']}",
            'document'  => $document,
            'items'     => $items,
            'rooms'     => (new RuanganModel())
                ->where('tersedia', 1)
                ->orderBy('name', 'ASC')
                ->findAll(),
            'units'     => (new UnitRapatModel())
                ->where('aktif', 1)
                ->orderBy('urutan', 'ASC')
                ->orderBy('nama', 'ASC')
                ->findAll(),
        ]);
    }

    public function edit(int $id)
    {
        $document = (new BanmusDocumentModel())->find($id);
        if ($document === null) {
            session()->setFlashdata('error', 'Dokumen SK Banmus tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }

        return view('admin/banmus/form', [
            'pageTitle'  => 'Edit SK Banmus',
            'document'   => $document,
            'action_url' => base_url("admin/jadwal-banmus/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $document = (new BanmusDocumentModel())->find($id);
        if ($document === null) {
            session()->setFlashdata('error', 'Dokumen SK Banmus tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }

        $service = new JadwalBanmusService();
        $input = $service->validatedSkForm($this->request->getPost(), $this->request->getFile('dokumen_file'), $document);

        if (isset($input['error'])) {
            return $this->failSkForm($input['error'], $id, $document);
        }

        $result = $service->persistSk($id, $input, $document);
        if (isset($result['error'])) {
            return $this->failSkForm($result['error'], $id, $document);
        }

        return $this->formSuccessResponse(
            'Dokumen SK Banmus berhasil diperbarui.',
            base_url("admin/jadwal-banmus/{$id}"),
        );
    }

    public function delete(int $id)
    {
        $document = (new BanmusDocumentModel())->find($id);
        if ($document === null) {
            session()->setFlashdata('error', 'Dokumen SK Banmus tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }

        if (! (new JadwalBanmusService())->deleteDocument($document)) {
            session()->setFlashdata('error', 'Dokumen SK Banmus gagal dihapus.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }

        return $this->formSuccessResponse(
            'Dokumen SK Banmus berhasil dihapus.',
            base_url('admin/jadwal-banmus'),
        );
    }

    // ── CRUD ITEM AGENDA ─────────────────────────────────────────────

    public function storeItem(int $documentId)
    {
        $document = (new BanmusDocumentModel())->find($documentId);
        if ($document === null) {
            session()->setFlashdata('error', 'Dokumen SK Banmus tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }

        $service = new JadwalBanmusService();
        $validated = $service->validatedItemPayload(
            $this->request->getPost(),
            $this->request->getFile('undangan_file'),
            null,
            (int) $document['tahun'],
        );
        if (isset($validated['error'])) {
            session()->setFlashdata('error', $validated['error']);

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        $result = $service->storeItem($documentId, $validated);
        if (isset($result['error'])) {
            session()->setFlashdata('error', $result['error']);

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        return $this->formSuccessResponse(
            $result['status'] !== 'proyeksi'
                ? 'Item agenda berhasil disimpan sebagai jadwal.'
                : 'Item agenda berhasil disimpan sebagai proyeksi. Data pelaksanaan dapat dilengkapi kemudian.',
            base_url("admin/jadwal-banmus/{$documentId}"),
        );
    }

    public function updateItem(int $documentId, int $itemId)
    {
        $item = (new JadwalBanmusModel())->where('dokumen_banmus_id', $documentId)->find($itemId);
        if ($item === null) {
            session()->setFlashdata('error', 'Item agenda tidak ditemukan.');

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        $document = (new BanmusDocumentModel())->find($documentId);
        if ($document === null) {
            session()->setFlashdata('error', 'Dokumen SK Banmus tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }

        $service = new JadwalBanmusService();
        $validated = $service->validatedItemPayload(
            $this->request->getPost(),
            $this->request->getFile('undangan_file'),
            $item,
            (int) $document['tahun'],
        );
        if (isset($validated['error'])) {
            session()->setFlashdata('error', $validated['error']);

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        $error = $service->updateItem($item, $validated);
        if ($error !== null) {
            session()->setFlashdata('error', $error);

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        $finalStatus = $service->resolveUpdatedStatus($validated);

        return $this->formSuccessResponse(
            $finalStatus !== 'proyeksi'
                ? 'Item agenda dan jadwal Banmus berhasil diperbarui.'
                : 'Item agenda berhasil disimpan sebagai proyeksi. Data pelaksanaan dapat dilengkapi kemudian.',
            base_url("admin/jadwal-banmus/{$documentId}"),
        );
    }

    public function deleteItem(int $documentId, int $itemId)
    {
        $item = (new JadwalBanmusModel())->where('dokumen_banmus_id', $documentId)->find($itemId);
        if ($item === null) {
            session()->setFlashdata('error', 'Item agenda tidak ditemukan.');

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        (new JadwalBanmusService())->deleteItem($item);

        return $this->formSuccessResponse(
            'Item agenda berhasil dihapus.',
            base_url("admin/jadwal-banmus/{$documentId}"),
        );
    }

    private function failSkForm(string $message, ?int $id = null, ?array $existingDocument = null)
    {
        $post = $this->request->getPost();
        $document = [
            'id'       => $id,
            'nomor_sk' => trim((string) ($post['nomor_sk'] ?? '')),
            'judul'    => trim((string) ($post['judul'] ?? '')),
            'tahun'    => trim((string) ($post['tahun'] ?? '')),
            'semester' => trim((string) ($post['semester'] ?? '')),
            'catatan'  => trim((string) ($post['catatan'] ?? '')),
            'dokumen_file'      => $existingDocument['dokumen_file'] ?? null,
            'dokumen_nama_asli' => $existingDocument['dokumen_nama_asli'] ?? null,
        ];

        return $this->formViewErrorResponse('admin/banmus/form', [
            'pageTitle'  => $id === null ? 'Tambah SK Banmus' : 'Edit SK Banmus',
            'document'   => $document,
            'action_url' => $id === null ? base_url('admin/jadwal-banmus/store') : base_url("admin/jadwal-banmus/{$id}/update"),
        ], $message);
    }
}
