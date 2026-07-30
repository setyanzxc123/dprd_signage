<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Schedule\ScheduleResourceAccess;
use App\Models\BanmusDocumentModel;
use App\Models\JadwalBanmusModel;
use App\Models\RuanganModel;
use App\Models\UnitRapatModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use RuntimeException;
use Throwable;

class JadwalBanmusController extends BaseController
{
    private const MAX_PDF_SIZE = 10 * 1024 * 1024;

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
        $input = $this->validatedSkForm();
        if (isset($input['error'])) {
            return $this->failSkForm($input['error']);
        }

        $result = $this->persistSk(null, $input);
        if (isset($result['error'])) {
            return $this->failSkForm($result['error']);
        }

        $newId = $result['id'];

        return $this->formSuccessResponse(
            'Dokumen SK Banmus berhasil disimpan. Silakan kelola item agenda di bawah.',
            base_url("admin/jadwal-banmus/{$newId}"),
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

        $rooms = (new RuanganModel())
            ->where('tersedia', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        $units = (new UnitRapatModel())
            ->where('aktif', 1)
            ->orderBy('urutan', 'ASC')
            ->orderBy('nama', 'ASC')
            ->findAll();

        return view('admin/banmus/show', [
            'pageTitle' => "Agenda SK Banmus No. {$document['nomor_sk']}",
            'document'  => $document,
            'items'     => $items,
            'rooms'     => $rooms,
            'units'     => $units,
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

        $input = $this->validatedSkForm($document);
        if (isset($input['error'])) {
            return $this->failSkForm($input['error'], $id, $document);
        }

        $result = $this->persistSk($id, $input, $document);
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
        $model = new BanmusDocumentModel();
        $document = $model->find($id);
        if ($document === null) {
            session()->setFlashdata('error', 'Dokumen SK Banmus tidak ditemukan.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }

        if (! $model->delete($id)) {
            session()->setFlashdata('error', 'Dokumen SK Banmus gagal dihapus.');

            return redirect()->to(base_url('admin/jadwal-banmus'));
        }
        $this->deleteStoredPdf($document['dokumen_file'] ?? null);

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

        $input = $this->validatedItemPayload();
        if (isset($input['error'])) {
            session()->setFlashdata('error', $input['error']);

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        $model = new JadwalBanmusModel();
        $nextUrutan = (int) ($model->where('dokumen_banmus_id', $documentId)->selectMax('urutan')->first()['urutan'] ?? 0) + 1;

        $payload = array_merge($input['payload'], [
            'dokumen_banmus_id' => $documentId,
            'urutan'            => $nextUrutan,
            'status'            => JadwalBanmusModel::resolveLifecycleStatus(
                $input['is_schedule_complete'],
                $input['payload']['tanggal'],
                $input['payload']['jam_mulai'],
                $input['payload']['jam_selesai'],
            ),
        ]);

        $db = Database::connect();
        $db->transStart();
        $itemId = (int) $model->insert($payload, true);
        if ($itemId > 0) {
            $this->syncItemUnits($itemId, $input['unit_ids']);
        }
        $db->transComplete();

        if ($itemId < 1 || ! $db->transStatus()) {
            session()->setFlashdata('error', 'Gagal menambahkan item agenda.');

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        return $this->formSuccessResponse(
            $payload['status'] !== 'proyeksi'
                ? 'Item agenda berhasil disimpan sebagai jadwal.'
                : 'Item agenda berhasil disimpan sebagai proyeksi. Data pelaksanaan dapat dilengkapi kemudian.',
            base_url("admin/jadwal-banmus/{$documentId}"),
        );
    }

    public function updateItem(int $documentId, int $itemId)
    {
        $model = new JadwalBanmusModel();
        $item = $model->where('dokumen_banmus_id', $documentId)->find($itemId);
        if ($item === null) {
            session()->setFlashdata('error', 'Item agenda tidak ditemukan.');

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        $input = $this->validatedItemPayload($item);
        if (isset($input['error'])) {
            session()->setFlashdata('error', $input['error']);

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        $payload = $input['payload'];
        $payload['status'] = JadwalBanmusModel::resolveLifecycleStatus(
            $input['is_schedule_complete'],
            $payload['tanggal'],
            $payload['jam_mulai'],
            $payload['jam_selesai'],
        );

        $db = Database::connect();
        $db->transStart();
        $updated = $model->update($itemId, $payload);
        if ($updated) {
            $this->syncItemUnits($itemId, $input['unit_ids']);
        }
        $db->transComplete();

        if (! $updated || ! $db->transStatus()) {
            session()->setFlashdata('error', 'Gagal memperbarui item agenda.');

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        return $this->formSuccessResponse(
            $payload['status'] !== 'proyeksi'
                ? 'Item agenda dan jadwal Banmus berhasil diperbarui.'
                : 'Item agenda berhasil disimpan sebagai proyeksi. Data pelaksanaan dapat dilengkapi kemudian.',
            base_url("admin/jadwal-banmus/{$documentId}"),
        );
    }

    public function deleteItem(int $documentId, int $itemId)
    {
        $model = new JadwalBanmusModel();
        $item = $model->where('dokumen_banmus_id', $documentId)->find($itemId);
        if ($item === null) {
            session()->setFlashdata('error', 'Item agenda tidak ditemukan.');

            return redirect()->to(base_url("admin/jadwal-banmus/{$documentId}"));
        }

        $model->delete($itemId);

        return $this->formSuccessResponse(
            'Item agenda berhasil dihapus.',
            base_url("admin/jadwal-banmus/{$documentId}"),
        );
    }

    // ── PRIVATE HELPERS ──────────────────────────────────────────────

    private function validatedItemPayload(?array $existingItem = null): array
    {
        $agenda = trim((string) $this->request->getPost('agenda'));
        if ($agenda === '') {
            return ['error' => 'Uraian agenda SK wajib diisi.'];
        }

        $periodeLabel = trim((string) $this->request->getPost('periode_label'));

        $agendaType = trim((string) $this->request->getPost('jenis_agenda'));
        if ($agendaType === '') {
            $agendaType = (string) ($existingItem['jenis_agenda'] ?? JadwalBanmusModel::TYPE_MEETING);
        }
        if (! in_array($agendaType, JadwalBanmusModel::AGENDA_TYPES, true)) {
            return ['error' => 'Jenis item Banmus tidak valid.'];
        }

        $tanggal = trim((string) $this->request->getPost('tanggal'));
        if ($tanggal !== '' && ! $this->validDate($tanggal)) {
            return ['error' => 'Format tanggal pasti tidak valid.'];
        }

        $jamMulai = trim((string) $this->request->getPost('jam_mulai'));
        $jamSelesai = trim((string) $this->request->getPost('jam_selesai'));
        if ($jamMulai !== '' && ! $this->validTime($jamMulai)) {
            return ['error' => 'Format jam mulai tidak valid.'];
        }
        if ($jamSelesai !== '' && ! $this->validTime($jamSelesai)) {
            return ['error' => 'Format jam selesai tidak valid.'];
        }
        if ($jamMulai !== '' && $jamSelesai !== '' && strtotime($jamSelesai) <= strtotime($jamMulai)) {
            return ['error' => 'Jam selesai harus lebih besar daripada jam mulai.'];
        }

        $roomValue = trim((string) $this->request->getPost('ruangan_id'));
        $lokasiLainnya = trim((string) $this->request->getPost('lokasi_lainnya'));
        $ruanganId = null;
        if ($roomValue !== '' && $roomValue !== 'other') {
            $ruanganId = filter_var($roomValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($ruanganId === false) {
                return ['error' => 'Ruangan rapat tidak valid.'];
            }

            $room = (new RuanganModel())->find((int) $ruanganId);
            $existingRoomId = (int) ($existingItem['ruangan_id'] ?? 0);
            if ($room === null
                || ((int) ($room['tersedia'] ?? 0) !== 1 && (int) $ruanganId !== $existingRoomId)) {
                return ['error' => 'Ruangan rapat tidak ditemukan atau sedang tidak tersedia.'];
            }
            $lokasiLainnya = '';
        } elseif ($roomValue !== 'other') {
            $lokasiLainnya = '';
        }

        if (mb_strlen($lokasiLainnya) > 255) {
            return ['error' => 'Lokasi lainnya maksimal 255 karakter.'];
        }

        $unitIds = $this->postedUnitIds();
        if ($unitIds !== []) {
            $validUnits = (new UnitRapatModel())
                ->select('id')
                ->where('aktif', 1)
                ->whereIn('id', $unitIds)
                ->findAll();
            $validUnitIds = array_map('intval', array_column($validUnits, 'id'));
            if (array_values(array_diff($unitIds, $validUnitIds)) !== []) {
                return ['error' => 'Kelompok peserta yang dipilih tidak valid atau sudah nonaktif.'];
            }
        }

        $publikasi = trim((string) $this->request->getPost('publikasi'));
        $catatan = trim((string) $this->request->getPost('catatan'));
        $materiUrl = $this->validatedOptionalUrl(
            (string) $this->request->getPost('materi_url'),
            'Tautan materi atau dokumen tidak valid.',
        );
        if (isset($materiUrl['error'])) {
            return ['error' => $materiUrl['error']];
        }
        $streamUrl = $this->validatedOptionalUrl(
            (string) $this->request->getPost('stream_url'),
            'Tautan live streaming tidak valid.',
        );
        if (isset($streamUrl['error'])) {
            return ['error' => $streamUrl['error']];
        }
        $materiAkses = ScheduleResourceAccess::normalize(
            $this->request->getPost('materi_akses'),
            ScheduleResourceAccess::PUBLIC,
        );
        $streamAkses = ScheduleResourceAccess::normalize(
            $this->request->getPost('stream_akses'),
            ScheduleResourceAccess::PUBLIC,
        );

        $isScheduleComplete = $tanggal !== ''
            && $jamMulai !== ''
            && $jamSelesai !== ''
            && ($ruanganId !== null || $lokasiLainnya !== '')
            && $unitIds !== [];

        if ($isScheduleComplete && $ruanganId !== null && $this->hasRoomConflict(
            (int) $ruanganId,
            $tanggal,
            $jamMulai,
            $jamSelesai,
            isset($existingItem['id']) ? (int) $existingItem['id'] : null,
        )) {
            return ['error' => 'Ruangan sudah dipakai pada tanggal dan rentang waktu tersebut.'];
        }

        return [
            'payload' => [
                'agenda'          => $agenda,
                'jenis_agenda'    => $agendaType,
                'periode_label'   => $periodeLabel !== '' ? $periodeLabel : null,
                'tanggal'         => $tanggal !== '' ? $tanggal : null,
                'jam_mulai'       => $jamMulai !== '' ? $jamMulai : null,
                'jam_selesai'     => $jamSelesai !== '' ? $jamSelesai : null,
                'ruangan_id'      => $ruanganId !== null ? (int) $ruanganId : null,
                'lokasi_lainnya'  => $lokasiLainnya !== '' ? $lokasiLainnya : null,
                'catatan'         => $catatan !== '' ? $catatan : null,
                'publikasi'       => in_array($publikasi, ['internal', 'publik'], true) ? $publikasi : 'publik',
                'materi_url'      => $materiUrl['url'],
                'materi_akses'    => $materiAkses,
                'stream_url'      => $streamUrl['url'],
                'stream_akses'    => $streamAkses,
            ],
            'unit_ids'             => $unitIds,
            'is_schedule_complete' => $isScheduleComplete,
        ];
    }

    /**
     * @return list<int>
     */
    private function postedUnitIds(): array
    {
        $values = $this->request->getPost('unit_ids');
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => max(0, (int) $value),
            $values,
        ))));
    }

    /**
     * @param list<int> $unitIds
     */
    private function syncItemUnits(int $itemId, array $unitIds): void
    {
        $db = Database::connect();
        $db->table('jadwal_banmus_unit_rapat')
            ->where('jadwal_banmus_id', $itemId)
            ->delete();

        if ($unitIds === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $rows = array_map(static fn (int $unitId): array => [
            'jadwal_banmus_id' => $itemId,
            'unit_rapat_id'    => $unitId,
            'created_at'       => $now,
        ], $unitIds);
        $db->table('jadwal_banmus_unit_rapat')->insertBatch($rows);
    }

    private function validDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function validTime(string $value): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value) === 1;
    }

    /**
     * @return array{url?: ?string, error?: string}
     */
    private function validatedOptionalUrl(string $url, string $message): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['url' => null];
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (filter_var($url, FILTER_VALIDATE_URL) === false
            || ! in_array($scheme, ['http', 'https'], true)) {
            return ['error' => $message];
        }

        return ['url' => $url];
    }

    private function hasRoomConflict(
        int $roomId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreBanmusId,
    ): bool {
        $db = Database::connect();

        $banmus = $db->table('jadwal_banmus')
            ->select('id')
            ->where('tanggal', $date)
            ->where('ruangan_id', $roomId)
            ->whereIn('status', JadwalBanmusModel::SCHEDULED_STATUSES)
            ->where('jam_mulai <', $endTime)
            ->where('jam_selesai >', $startTime)
            ->where('deleted_at', null);
        if ($ignoreBanmusId !== null) {
            $banmus->where('id !=', $ignoreBanmusId);
        }
        if ($banmus->get(1)->getRowArray() !== null) {
            return true;
        }

        if (! $db->tableExists('jadwal')) {
            return false;
        }

        return $db->table('jadwal')
            ->select('id')
            ->where('tanggal', $date)
            ->where('ruangan_id', $roomId)
            ->whereNotIn('status', ['ditunda', 'dibatalkan'])
            ->where('waktu_mulai <', $endTime)
            ->where('waktu_selesai >', $startTime)
            ->get(1)
            ->getRowArray() !== null;
    }

    private function validatedSkForm(?array $existingDocument = null): array
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

        $year = (int) $tahunRaw;
        $semester = (int) $semesterRaw;
        $periodQuery = (new BanmusDocumentModel())
            ->where('tahun', $year)
            ->where('semester', $semester);
        if ($existingDocument !== null) {
            $periodQuery->where('id !=', (int) $existingDocument['id']);
        }

        $existingPeriodDocument = $periodQuery->first();
        if ($existingPeriodDocument !== null) {
            return [
                'error' => "Semester {$semester} Tahun {$year} sudah terdaftar pada SK No. "
                    . $existingPeriodDocument['nomor_sk']
                    . '. Silakan edit SK tersebut agar agenda semester tidak terduplikasi.',
            ];
        }

        $uploadResult = $this->validatedPdfUpload();
        if (isset($uploadResult['error'])) {
            return ['error' => $uploadResult['error']];
        }

        /** @var UploadedFile|null $upload */
        $upload = $uploadResult['file'];
        $hasExistingSource = ! empty($existingDocument['dokumen_file']);
        if ($upload === null && ! $hasExistingSource) {
            return ['error' => 'File SK dalam format PDF wajib diunggah.'];
        }

        $customJudul = trim((string) $this->request->getPost('judul'));
        $catatan = trim((string) $this->request->getPost('catatan'));

        $payload = [
            'judul'            => $customJudul !== '' ? $customJudul : "Jadwal Rapat Hasil Banmus Semester {$semester} Tahun {$year}",
            'nomor_sk'         => $nomorSk,
            'tahun'            => $year,
            'semester'         => $semester,
            'status'           => 'disahkan',
            'is_publik'        => 1,
            'catatan'          => $catatan !== '' ? $catatan : null,
        ];

        if ($upload === null && $existingDocument !== null) {
            $payload['dokumen_file'] = $existingDocument['dokumen_file'] ?? null;
            $payload['dokumen_nama_asli'] = $existingDocument['dokumen_nama_asli'] ?? null;
        }

        return [
            'payload' => $payload,
            'upload'  => $upload,
        ];
    }

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

    private function persistSk(?int $id, array $input, ?array $oldDocument = null): array
    {
        $newFileName = null;
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
            }

            $documentModel = new BanmusDocumentModel();

            if ($id === null) {
                $id = (int) $documentModel->insert($input['payload'], true);
                if ($id < 1) {
                    throw new RuntimeException('Dokumen SK gagal disimpan.');
                }
            } else {
                if (! $documentModel->update($id, $input['payload'])) {
                    throw new RuntimeException('Dokumen SK gagal diperbarui.');
                }
            }

            $oldFileName = $oldDocument['dokumen_file'] ?? null;
            $currentFileName = $input['payload']['dokumen_file'] ?? null;
            if (is_string($oldFileName) && $oldFileName !== '' && $oldFileName !== $currentFileName) {
                $this->deleteStoredPdf($oldFileName);
            }

            return ['id' => $id];
        } catch (Throwable $exception) {
            if ($newFileName !== null) {
                $this->deleteStoredPdf($newFileName);
            }
            log_message('error', 'Gagal menyimpan dokumen SK Banmus: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return ['error' => 'Dokumen SK Banmus gagal disimpan. Periksa data dan coba kembali.'];
        }
    }

    private function failSkForm(string $message, ?int $id = null, ?array $existingDocument = null)
    {
        $document = [
            'id'       => $id,
            'nomor_sk' => trim((string) $this->request->getPost('nomor_sk')),
            'judul'    => trim((string) $this->request->getPost('judul')),
            'tahun'    => trim((string) $this->request->getPost('tahun')),
            'semester' => trim((string) $this->request->getPost('semester')),
            'catatan'  => trim((string) $this->request->getPost('catatan')),
            'dokumen_file'      => $existingDocument['dokumen_file'] ?? null,
            'dokumen_nama_asli' => $existingDocument['dokumen_nama_asli'] ?? null,
        ];

        return $this->formViewErrorResponse('admin/banmus/form', [
            'pageTitle'  => $id === null ? 'Tambah SK Banmus' : 'Edit SK Banmus',
            'document'   => $document,
            'action_url' => $id === null ? base_url('admin/jadwal-banmus/store') : base_url("admin/jadwal-banmus/{$id}/update"),
        ], $message);
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
