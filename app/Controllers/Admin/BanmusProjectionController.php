<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BanmusDocumentModel;
use App\Models\BanmusProjectionModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use RuntimeException;
use Throwable;

class BanmusProjectionController extends BaseController
{
    private const MAX_ITEMS = 100;
    private const MAX_PDF_SIZE = 10 * 1024 * 1024;

    public function index(): string
    {
        $documents = (new BanmusDocumentModel())
            ->orderBy('tahun', 'DESC')
            ->orderBy('semester', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();

        $groupedItems = (new BanmusProjectionModel())->findGroupedByDocumentIds(
            array_map('intval', array_column($documents, 'id')),
        );

        foreach ($documents as &$document) {
            $document['jumlah_item'] = count($groupedItems[(int) $document['id']] ?? []);
        }
        unset($document);

        return view('admin/banmus/index', [
            'pageTitle' => 'Jadwal Banmus',
            'documents' => $documents,
        ]);
    }

    public function create(): string
    {
        return view('admin/banmus/form', $this->formData(
            'Tambah Jadwal Banmus',
            null,
            [$this->blankItem()],
            base_url('admin/jadwal-banmus/store'),
        ));
    }

    public function store()
    {
        $input = $this->validatedForm();
        if (isset($input['error'])) {
            return $this->failForm($input['error']);
        }

        $result = $this->persist(null, $input);
        if (isset($result['error'])) {
            return $this->failForm($result['error']);
        }

        return $this->formSuccessResponse(
            'Jadwal Banmus berhasil ditambahkan.',
            base_url('admin/jadwal-banmus'),
        );
    }

    public function edit(int $id)
    {
        $document = (new BanmusDocumentModel())->find($id);
        if ($document === null) {
            session()->setFlashdata('error', 'Dokumen Jadwal Banmus tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }

        $items = (new BanmusProjectionModel())
            ->where('dokumen_banmus_id', $id)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return view('admin/banmus/form', $this->formData(
            'Edit Jadwal Banmus',
            $document,
            $items === [] ? [$this->blankItem()] : $items,
            base_url("admin/jadwal-banmus/{$id}/update"),
        ));
    }

    public function update(int $id)
    {
        $document = (new BanmusDocumentModel())->find($id);
        if ($document === null) {
            session()->setFlashdata('error', 'Dokumen Jadwal Banmus tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }

        $input = $this->validatedForm($document);
        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id, $document);
        }

        $result = $this->persist($id, $input, $document);
        if (isset($result['error'])) {
            return $this->failForm($result['error'], $id, $document);
        }

        return $this->formSuccessResponse(
            'Jadwal Banmus berhasil diperbarui.',
            base_url('admin/jadwal-banmus'),
        );
    }

    public function delete(int $id)
    {
        $model = new BanmusDocumentModel();
        $document = $model->find($id);
        if ($document === null) {
            session()->setFlashdata('error', 'Dokumen Jadwal Banmus tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }

        if (! $model->delete($id)) {
            session()->setFlashdata('error', 'Jadwal Banmus gagal dihapus. Silakan coba kembali.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }
        $this->deleteStoredPdf($document['dokumen_file'] ?? null);

        return $this->formSuccessResponse(
            'Jadwal Banmus beserta seluruh kegiatannya berhasil dihapus.',
            base_url('admin/jadwal-banmus'),
        );
    }

    /**
     * @param array<string, mixed>|null $existingDocument
     * @return array<string, mixed>
     */
    private function validatedForm(?array $existingDocument = null): array
    {
        $nomorSk = trim((string) $this->request->getPost('nomor_sk'));
        if ($nomorSk === '' || mb_strlen($nomorSk) > 100) {
            return ['error' => 'Nomor SK wajib diisi dan maksimal 100 karakter.'];
        }

        $duplicateQuery = (new BanmusDocumentModel())->where('nomor_sk', $nomorSk);
        if ($existingDocument !== null) {
            $duplicateQuery->where('id !=', (int) $existingDocument['id']);
        }
        if ($duplicateQuery->first() !== null) {
            return ['error' => 'Nomor SK tersebut sudah terdaftar.'];
        }

        $tahunRaw = trim((string) $this->request->getPost('tahun'));
        if (! ctype_digit($tahunRaw) || (int) $tahunRaw < 2000 || (int) $tahunRaw > 2100) {
            return ['error' => 'Tahun harus berada di antara 2000 dan 2100.'];
        }

        $semesterRaw = trim((string) $this->request->getPost('semester'));
        if (! in_array($semesterRaw, ['1', '2'], true)) {
            return ['error' => 'Semester wajib dipilih.'];
        }

        $documentUrl = trim((string) $this->request->getPost('dokumen_url'));
        if ($documentUrl !== '' && (! $this->validHttpUrl($documentUrl) || mb_strlen($documentUrl) > 500)) {
            return ['error' => 'Tautan dokumen tidak valid.'];
        }

        $uploadResult = $this->validatedPdfUpload();
        if (isset($uploadResult['error'])) {
            return ['error' => $uploadResult['error']];
        }

        /** @var UploadedFile|null $upload */
        $upload = $uploadResult['file'];
        if ($upload !== null && $documentUrl !== '') {
            return ['error' => 'Dokumen hanya boleh memiliki satu sumber.'];
        }

        $hasExistingSource = ! empty($existingDocument['dokumen_file'])
            || ! empty($existingDocument['dokumen_url']);
        if ($upload === null && $documentUrl === '' && ! $hasExistingSource) {
            return ['error' => 'File SK dalam format PDF wajib diunggah.'];
        }

        $itemsResult = $this->validatedItems();
        if (isset($itemsResult['error'])) {
            return ['error' => $itemsResult['error']];
        }

        $year = (int) $tahunRaw;
        $semester = (int) $semesterRaw;
        $payload = [
            'judul'            => "Jadwal Banmus Semester {$semester} Tahun {$year}",
            'nomor_sk'         => $nomorSk,
            'tanggal_sk'       => null,
            'tahun'            => $year,
            'semester'         => $semester,
            'masa_persidangan' => null,
            'periode_mulai'    => null,
            'periode_selesai'  => null,
            'status'           => 'disahkan',
            'is_publik'        => 1,
            'catatan'          => null,
        ];

        if ($documentUrl !== '') {
            $payload['dokumen_file'] = null;
            $payload['dokumen_nama_asli'] = null;
            $payload['dokumen_url'] = $documentUrl;
        } elseif ($upload === null && $existingDocument !== null) {
            $payload['dokumen_file'] = $existingDocument['dokumen_file'] ?? null;
            $payload['dokumen_nama_asli'] = $existingDocument['dokumen_nama_asli'] ?? null;
            $payload['dokumen_url'] = $existingDocument['dokumen_url'] ?? null;
        }

        return [
            'payload' => $payload,
            'items'   => $itemsResult['items'],
            'upload'  => $upload,
        ];
    }

    /**
     * @return array{items?: list<array<string, mixed>>, error?: string}
     */
    private function validatedItems(): array
    {
        $postedItems = $this->request->getPost('items');
        if (! is_array($postedItems) || $postedItems === []) {
            return ['error' => 'Tambahkan minimal satu baris kegiatan.'];
        }
        if (count($postedItems) > self::MAX_ITEMS) {
            return ['error' => 'Satu SK maksimal memuat 100 baris kegiatan.'];
        }

        $items = [];
        $position = 0;
        foreach ($postedItems as $postedItem) {
            $position++;
            if (! is_array($postedItem)) {
                return ['error' => "Baris ke-{$position} tidak valid."];
            }

            $implementationDate = trim((string) ($postedItem['tanggal_pelaksanaan'] ?? ''));
            if ($implementationDate === '' || mb_strlen($implementationDate) > 100) {
                return ['error' => "Tanggal pelaksanaan pada baris ke-{$position} wajib diisi dan maksimal 100 karakter."];
            }

            $activity = trim((string) ($postedItem['uraian_kegiatan'] ?? ''));
            if ($activity === '' || mb_strlen($activity) > 10000) {
                return ['error' => "Uraian kegiatan pada baris ke-{$position} wajib diisi dan maksimal 10.000 karakter."];
            }

            $notes = trim((string) ($postedItem['keterangan'] ?? ''));
            if (mb_strlen($notes) > 2000) {
                return ['error' => "Keterangan pada baris ke-{$position} maksimal 2.000 karakter."];
            }

            $items[] = [
                'agenda'          => $activity,
                'periode_label'   => $implementationDate,
                'tanggal_mulai'   => null,
                'tanggal_selesai' => null,
                'unit_rapat_id'   => null,
                'urutan'          => $position,
                'status'          => 'proyeksi',
                'catatan'         => $notes !== '' ? $notes : null,
                'jadwal_id'       => null,
            ];
        }

        return ['items' => $items];
    }

    /**
     * @return array{file: UploadedFile|null, error?: string}
     */
    private function validatedPdfUpload(): array
    {
        $file = $this->request->getFile('dokumen_file');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return ['file' => null];
        }
        if (! $file->isValid()) {
            return ['file' => null, 'error' => 'Unggahan PDF gagal diproses. Silakan pilih ulang dokumen.'];
        }
        if ($file->getSize() > self::MAX_PDF_SIZE) {
            return ['file' => null, 'error' => 'Ukuran PDF maksimal 10 MB.'];
        }

        $extension = strtolower($file->getClientExtension());
        $mime = strtolower($file->getMimeType());
        if ($extension !== 'pdf' || ! in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            return ['file' => null, 'error' => 'Dokumen sumber harus berupa file PDF yang valid.'];
        }

        return ['file' => $file];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $oldDocument
     * @return array{error?: string}
     */
    private function persist(?int $id, array $input, ?array $oldDocument = null): array
    {
        $newFileName = null;
        $db = null;
        $transactionStarted = false;

        try {
            /** @var UploadedFile|null $upload */
            $upload = $input['upload'];
            if ($upload !== null) {
                $newFileName = bin2hex(random_bytes(20)) . '.pdf';
                $uploadDirectory = $this->uploadDirectory();
                if (! is_dir($uploadDirectory) && ! mkdir($uploadDirectory, 0750, true) && ! is_dir($uploadDirectory)) {
                    throw new RuntimeException('Direktori penyimpanan dokumen tidak dapat dibuat.');
                }
                $upload->move($uploadDirectory, $newFileName);
                if (! $upload->hasMoved()) {
                    throw new RuntimeException('Dokumen PDF tidak dapat disimpan.');
                }

                $input['payload']['dokumen_file'] = $newFileName;
                $input['payload']['dokumen_nama_asli'] = mb_substr(basename($upload->getClientName()), 0, 255);
                $input['payload']['dokumen_url'] = null;
            }

            $db = Database::connect();
            $documentModel = new BanmusDocumentModel();
            $projectionModel = new BanmusProjectionModel();
            $db->transBegin();
            $transactionStarted = true;

            if ($id === null) {
                $id = (int) $documentModel->insert($input['payload'], true);
                if ($id < 1) {
                    throw new RuntimeException('Dokumen SK gagal disimpan.');
                }
            } else {
                if (! $documentModel->update($id, $input['payload'])) {
                    throw new RuntimeException('Dokumen SK gagal diperbarui.');
                }
                $projectionModel->where('dokumen_banmus_id', $id)->delete();
            }

            $items = array_map(
                static fn (array $item): array => ['dokumen_banmus_id' => $id] + $item,
                $input['items'],
            );
            if ($projectionModel->insertBatch($items) === false) {
                throw new RuntimeException('Baris kegiatan Banmus gagal disimpan.');
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Transaksi penyimpanan Jadwal Banmus gagal.');
            }
            $db->transCommit();
            $transactionStarted = false;

            $oldFileName = $oldDocument['dokumen_file'] ?? null;
            $currentFileName = $input['payload']['dokumen_file'] ?? null;
            if (is_string($oldFileName) && $oldFileName !== '' && $oldFileName !== $currentFileName) {
                $this->deleteStoredPdf($oldFileName);
            }

            return [];
        } catch (Throwable $exception) {
            if ($transactionStarted && $db !== null) {
                $db->transRollback();
            }
            if ($newFileName !== null) {
                $this->deleteStoredPdf($newFileName);
            }
            log_message('error', 'Gagal menyimpan Jadwal Banmus: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return ['error' => 'Jadwal Banmus gagal disimpan. Periksa data dan coba kembali.'];
        }
    }

    private function failForm(string $message, ?int $id = null, ?array $existingDocument = null)
    {
        $document = $this->postedDocument($id, $existingDocument);
        $items = $this->postedItemsForForm();

        return $this->formViewErrorResponse('admin/banmus/form', $this->formData(
            $id === null ? 'Tambah Jadwal Banmus' : 'Edit Jadwal Banmus',
            $document,
            $items === [] ? [$this->blankItem()] : $items,
            $id === null
                ? base_url('admin/jadwal-banmus/store')
                : base_url("admin/jadwal-banmus/{$id}/update"),
        ), $message);
    }

    /**
     * @param array<string, mixed>|null $document
     * @param list<array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function formData(string $title, ?array $document, array $items, string $actionUrl): array
    {
        return [
            'pageTitle'  => $title,
            'document'   => $document,
            'items'      => $items,
            'action_url' => $actionUrl,
        ];
    }

    /**
     * @param array<string, mixed>|null $existingDocument
     * @return array<string, mixed>
     */
    private function postedDocument(?int $id, ?array $existingDocument): array
    {
        $year = trim((string) $this->request->getPost('tahun'));
        $semester = trim((string) $this->request->getPost('semester'));

        return [
            'id'                    => $id,
            'judul'                 => "Jadwal Banmus Semester {$semester} Tahun {$year}",
            'nomor_sk'              => trim((string) $this->request->getPost('nomor_sk')),
            'tahun'                 => $year,
            'semester'              => $semester,
            'dokumen_file'          => $existingDocument['dokumen_file'] ?? null,
            'dokumen_nama_asli'     => $existingDocument['dokumen_nama_asli'] ?? null,
            'dokumen_url'           => $existingDocument['dokumen_url'] ?? null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function postedItemsForForm(): array
    {
        $postedItems = $this->request->getPost('items');
        if (! is_array($postedItems)) {
            return [];
        }

        return array_values(array_filter($postedItems, 'is_array'));
    }

    /**
     * @return array<string, mixed>
     */
    private function blankItem(): array
    {
        return [
            'agenda'                => '',
            'periode_label'         => '',
            'catatan'               => '',
            'tanggal_pelaksanaan'   => '',
            'uraian_kegiatan'       => '',
            'keterangan'            => '',
        ];
    }

    private function validHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function uploadDirectory(): string
    {
        return WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'sk-banmus';
    }

    private function deleteStoredPdf(mixed $fileName): void
    {
        if (! is_string($fileName) || $fileName === '') {
            return;
        }

        $safeName = basename($fileName);
        if ($safeName !== $fileName || preg_match('/^[a-f0-9]{40}\.pdf$/', $safeName) !== 1) {
            return;
        }

        $path = $this->uploadDirectory() . DIRECTORY_SEPARATOR . $safeName;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
