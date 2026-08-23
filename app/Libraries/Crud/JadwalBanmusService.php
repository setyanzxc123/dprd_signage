<?php

namespace App\Libraries\Crud;

use App\Libraries\Schedule\ScheduleInvitationStorage;
use App\Libraries\Schedule\ScheduleResourceAccess;
use App\Models\BanmusDocumentModel;
use App\Models\JadwalBanmusModel;
use App\Models\RuanganModel;
use App\Models\UnitRapatModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;
use Throwable;

/**
 * Logika CRUD Agenda Banmus: dokumen SK (unggah PDF, keunikan
 * nomor/semester) dan item agenda (proyeksi vs terjadwal, konflik
 * ruang lintas sumber, undangan, relasi unit rapat). Dipakai bersama
 * oleh controller web dan API dengan konvensi input array polos,
 * berkas dikirim terpisah sebagai UploadedFile, dan hasil validasi
 * ['error' => pesan].
 */
class JadwalBanmusService
{
    private const MAX_PDF_SIZE = 10 * 1024 * 1024;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    // ── DOKUMEN SK BANMUS ────────────────────────────────────────────

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $existingDocument
     * @return array<string, mixed>
     */
    public function validatedSkForm(array $input, ?UploadedFile $upload, ?array $existingDocument = null): array
    {
        $nomorSk = trim((string) ($input['nomor_sk'] ?? ''));
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

        $tahunRaw = trim((string) ($input['tahun'] ?? ''));
        if (! ctype_digit($tahunRaw) || (int) $tahunRaw < 2000 || (int) $tahunRaw > 2100) {
            return ['error' => 'Tahun harus berada di antara 2000 dan 2100.'];
        }

        $semesterRaw = trim((string) ($input['semester'] ?? ''));
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

        $uploadResult = $this->validatedPdfUpload($upload);
        if (isset($uploadResult['error'])) {
            return ['error' => $uploadResult['error']];
        }

        /** @var UploadedFile|null $validatedUpload */
        $validatedUpload = $uploadResult['file'];
        $hasExistingSource = ! empty($existingDocument['dokumen_file']);
        if ($validatedUpload === null && ! $hasExistingSource) {
            return ['error' => 'File SK dalam format PDF wajib diunggah.'];
        }

        $customJudul = trim((string) ($input['judul'] ?? ''));
        $catatan = trim((string) ($input['catatan'] ?? ''));

        $payload = [
            'judul'            => $customJudul !== '' ? $customJudul : "Jadwal Rapat Hasil Banmus Semester {$semester} Tahun {$year}",
            'nomor_sk'         => $nomorSk,
            'tahun'            => $year,
            'semester'         => $semester,
            'status'           => 'disahkan',
            'is_publik'        => 1,
            'catatan'          => $catatan !== '' ? $catatan : null,
        ];

        if ($validatedUpload === null && $existingDocument !== null) {
            $payload['dokumen_file'] = $existingDocument['dokumen_file'] ?? null;
            $payload['dokumen_nama_asli'] = $existingDocument['dokumen_nama_asli'] ?? null;
        }

        return [
            'payload' => $payload,
            'upload'  => $validatedUpload,
        ];
    }

    /**
     * @param array<string, mixed> $validated hasil validatedSkForm
     * @param array<string, mixed>|null $oldDocument
     * @return array<string, mixed> ['id' => int] atau ['error' => pesan]
     */
    public function persistSk(?int $id, array $validated, ?array $oldDocument = null): array
    {
        $newFileName = null;

        try {
            /** @var UploadedFile|null $upload */
            $upload = $validated['upload'];
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

                $validated['payload']['dokumen_file'] = $newFileName;
                $validated['payload']['dokumen_nama_asli'] = mb_substr(basename($upload->getClientName()), 0, 255);
            }

            $documentModel = new BanmusDocumentModel();

            if ($id === null) {
                $id = (int) $documentModel->insert($validated['payload'], true);
                if ($id < 1) {
                    throw new RuntimeException('Dokumen SK gagal disimpan.');
                }
            } elseif (! $documentModel->update($id, $validated['payload'])) {
                throw new RuntimeException('Dokumen SK gagal diperbarui.');
            }

            $oldFileName = $oldDocument['dokumen_file'] ?? null;
            $currentFileName = $validated['payload']['dokumen_file'] ?? null;
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

    /**
     * Menghapus dokumen SK beserta seluruh item, berkas PDF SK, dan
     * berkas undangan milik item-itemnya.
     *
     * @param array<string, mixed> $document
     */
    public function deleteDocument(array $document): bool
    {
        $id = (int) $document['id'];

        $invitationFiles = $this->db->table('jadwal_banmus')
            ->select('undangan_file')
            ->where('dokumen_banmus_id', $id)
            ->where('undangan_file IS NOT NULL', null, false)
            ->where('undangan_file !=', '')
            ->get()
            ->getResultArray();

        if (! (new BanmusDocumentModel())->delete($id)) {
            return false;
        }

        $this->deleteStoredPdf($document['dokumen_file'] ?? null);
        $invitationStorage = new ScheduleInvitationStorage();
        foreach ($invitationFiles as $invitation) {
            $invitationStorage->delete($invitation['undangan_file'] ?? null);
        }

        return true;
    }

    /**
     * Ganti berkas PDF SK tanpa menyentuh metadata — dipakai endpoint
     * dokumen API mobile (multipart hanya terparsing untuk POST).
     *
     * @return ?string pesan error bila gagal
     */
    public function replaceDocument(int $documentId, ?UploadedFile $upload): ?string
    {
        if ($upload === null || $upload->getError() === UPLOAD_ERR_NO_FILE) {
            return 'Berkas dokumen SK wajib diunggah.';
        }

        $uploadResult = $this->validatedPdfUpload($upload);
        if (isset($uploadResult['error'])) {
            return $uploadResult['error'];
        }

        $document = (new BanmusDocumentModel())->find($documentId);
        if ($document === null) {
            return 'Dokumen SK Banmus tidak ditemukan.';
        }

        $newFileName = null;

        try {
            $newFileName = bin2hex(random_bytes(20)) . '.pdf';
            $uploadDirectory = $this->uploadDirectory();
            if (! is_dir($uploadDirectory) && ! mkdir($uploadDirectory, 0750, true) && ! is_dir($uploadDirectory)) {
                throw new RuntimeException('Direktori penyimpanan dokumen tidak dapat dibuat.');
            }
            $upload->move($uploadDirectory, $newFileName);
            if (! $upload->hasMoved()) {
                throw new RuntimeException('Dokumen PDF tidak dapat disimpan.');
            }

            (new BanmusDocumentModel())->update($documentId, [
                'dokumen_file'      => $newFileName,
                'dokumen_nama_asli' => mb_substr(basename($upload->getClientName()), 0, 255),
            ]);

            $this->deleteStoredPdf($document['dokumen_file'] ?? null);

            return null;
        } catch (Throwable $exception) {
            $this->deleteStoredPdf($newFileName);
            log_message('error', 'Gagal mengganti dokumen SK Banmus: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return 'Dokumen SK Banmus gagal disimpan. Periksa data dan coba kembali.';
        }
    }

    // ── ITEM AGENDA ──────────────────────────────────────────────────

    /**
     * Validasi lengkap input item agenda (berlaku untuk create/update).
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $existingItem
     * @return array<string, mixed>
     */
    public function validatedItemPayload(array $input, ?UploadedFile $invitation, ?array $existingItem = null, ?int $documentYear = null): array
    {
        $agenda = trim((string) ($input['agenda'] ?? ''));
        if ($agenda === '') {
            return ['error' => 'Uraian agenda SK wajib diisi.'];
        }

        $periodeLabel = trim((string) ($input['periode_label'] ?? ''));
        $projectionRange = JadwalBanmusModel::parseProjectionPeriodRange(
            $periodeLabel,
            $documentYear,
        );

        $agendaType = trim((string) ($input['jenis_agenda'] ?? ''));
        if ($agendaType === '') {
            $agendaType = (string) ($existingItem['jenis_agenda'] ?? JadwalBanmusModel::TYPE_MEETING);
        }
        if (! in_array($agendaType, JadwalBanmusModel::AGENDA_TYPES, true)) {
            return ['error' => 'Jenis item Banmus tidak valid.'];
        }

        $tanggal = trim((string) ($input['tanggal'] ?? ''));
        if ($tanggal !== '' && ! $this->validDate($tanggal)) {
            return ['error' => 'Format tanggal pasti tidak valid.'];
        }

        $jamMulai = trim((string) ($input['jam_mulai'] ?? ''));
        $jamSelesai = trim((string) ($input['jam_selesai'] ?? ''));
        if ($jamMulai !== '' && ! $this->validTime($jamMulai)) {
            return ['error' => 'Format jam mulai tidak valid.'];
        }
        if ($jamSelesai !== '' && ! $this->validTime($jamSelesai)) {
            return ['error' => 'Format jam selesai tidak valid.'];
        }
        if ($jamMulai !== '' && $jamSelesai !== '' && strtotime($jamSelesai) <= strtotime($jamMulai)) {
            return ['error' => 'Jam selesai harus lebih besar daripada jam mulai.'];
        }

        $roomValue = trim((string) ($input['ruangan_id'] ?? ''));
        $lokasiLainnya = trim((string) ($input['lokasi_lainnya'] ?? ''));
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

        $unitIds = $this->postedUnitIds($input);
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

        $publikasi = trim((string) ($input['publikasi'] ?? ''));
        $catatan = trim((string) ($input['catatan'] ?? ''));
        $materiUrl = $this->validatedOptionalUrl(
            (string) ($input['materi_url'] ?? ''),
            'Tautan materi atau dokumen tidak valid.',
        );
        if (isset($materiUrl['error'])) {
            return ['error' => $materiUrl['error']];
        }
        $streamUrl = $this->validatedOptionalUrl(
            (string) ($input['stream_url'] ?? ''),
            'Tautan live streaming tidak valid.',
        );
        if (isset($streamUrl['error'])) {
            return ['error' => $streamUrl['error']];
        }
        $materiAkses = ScheduleResourceAccess::normalize(
            $input['materi_akses'] ?? null,
            ScheduleResourceAccess::PUBLIC,
        );
        $streamAkses = ScheduleResourceAccess::normalize(
            $input['stream_akses'] ?? null,
            ScheduleResourceAccess::PUBLIC,
        );
        $invitationCheck = (new ScheduleInvitationStorage())->validate($invitation);
        if (isset($invitationCheck['error'])) {
            return ['error' => $invitationCheck['error']];
        }

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
                ...$projectionRange,
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
            'invitation_upload'    => $invitation,
            'remove_invitation'    => ($input['hapus_undangan'] ?? null) === '1',
        ];
    }

    /**
     * Simpan item agenda baru; status (proyeksi/terjadwal) dihitung
     * otomatis dari kelengkapan data pelaksanaan.
     *
     * @param array<string, mixed> $validated hasil validatedItemPayload
     * @return array<string, mixed> ['id' => int, 'status' => string] atau ['error' => pesan]
     */
    public function storeItem(int $documentId, array $validated): array
    {
        $model = new JadwalBanmusModel();
        $nextUrutan = (int) ($model->where('dokumen_banmus_id', $documentId)->selectMax('urutan')->first()['urutan'] ?? 0) + 1;

        $payload = array_merge($validated['payload'], [
            'dokumen_banmus_id' => $documentId,
            'urutan'            => $nextUrutan,
            'status'            => JadwalBanmusModel::resolveLifecycleStatus(
                $validated['is_schedule_complete'],
                $validated['payload']['tanggal'],
                $validated['payload']['jam_mulai'],
                $validated['payload']['jam_selesai'],
            ),
        ]);

        $storedInvitation = $this->storeInvitationUpload($validated);
        if (isset($storedInvitation['error'])) {
            return ['error' => $storedInvitation['error']];
        }
        $payload = array_merge($payload, $storedInvitation['payload']);

        $this->db->transStart();
        $itemId = (int) $model->insert($payload, true);
        if ($itemId > 0) {
            $this->syncItemUnits($itemId, $validated['unit_ids']);
        }
        $this->db->transComplete();

        if ($itemId < 1 || ! $this->db->transStatus()) {
            (new ScheduleInvitationStorage())->delete($storedInvitation['new_file'] ?? null);

            return ['error' => 'Gagal menambahkan item agenda.'];
        }

        return ['id' => $itemId, 'status' => (string) $payload['status']];
    }

    /**
     * Perbarui item agenda; status dihitung ulang, undangan lama
     * diganti/dihapus bila perlu.
     *
     * @param array<string, mixed> $item baris lama
     * @param array<string, mixed> $validated hasil validatedItemPayload
     * @return ?string pesan error bila gagal
     */
    public function updateItem(array $item, array $validated): ?string
    {
        $itemId = (int) $item['id'];
        $payload = $validated['payload'];
        $payload['status'] = JadwalBanmusModel::resolveLifecycleStatus(
            $validated['is_schedule_complete'],
            $payload['tanggal'],
            $payload['jam_mulai'],
            $payload['jam_selesai'],
        );

        $storedInvitation = $this->storeInvitationUpload($validated);
        if (isset($storedInvitation['error'])) {
            return $storedInvitation['error'];
        }
        $payload = array_merge($payload, $storedInvitation['payload']);

        $this->db->transStart();
        $updated = (new JadwalBanmusModel())->update($itemId, $payload);
        if ($updated) {
            $this->syncItemUnits($itemId, $validated['unit_ids']);
        }
        $this->db->transComplete();

        if (! $updated || ! $this->db->transStatus()) {
            (new ScheduleInvitationStorage())->delete($storedInvitation['new_file'] ?? null);

            return 'Gagal memperbarui item agenda.';
        }

        if (($item['undangan_file'] ?? null) !== ($payload['undangan_file'] ?? $item['undangan_file'] ?? null)) {
            (new ScheduleInvitationStorage())->delete($item['undangan_file'] ?? null);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $item baris lama
     * @return ?string status akhir item setelah update, atau null bila gagal
     */
    public function resolveUpdatedStatus(array $validated): string
    {
        return JadwalBanmusModel::resolveLifecycleStatus(
            $validated['is_schedule_complete'],
            $validated['payload']['tanggal'],
            $validated['payload']['jam_mulai'],
            $validated['payload']['jam_selesai'],
        );
    }

    /**
     * @param array<string, mixed> $item baris lama
     */
    public function deleteItem(array $item): void
    {
        (new JadwalBanmusModel())->delete((int) $item['id']);
        (new ScheduleInvitationStorage())->delete($item['undangan_file'] ?? null);
    }

    /**
     * Ganti berkas undangan milik item agenda — dipakai endpoint
     * undangan API mobile (multipart hanya terparsing untuk POST).
     *
     * @return ?string pesan error bila gagal
     */
    public function replaceItemInvitation(int $itemId, ?UploadedFile $invitation): ?string
    {
        if ($invitation === null || $invitation->getError() === UPLOAD_ERR_NO_FILE) {
            return 'Berkas undangan wajib diunggah.';
        }

        $storage = new ScheduleInvitationStorage();
        $invitationCheck = $storage->validate($invitation);
        if (isset($invitationCheck['error'])) {
            return $invitationCheck['error'];
        }

        try {
            $stored = $storage->store($invitation);
        } catch (Throwable $exception) {
            log_message('error', 'Gagal menyimpan undangan Banmus: {message}', ['message' => $exception->getMessage()]);

            return 'PDF undangan gagal disimpan. Silakan coba kembali.';
        }

        $existing = (new JadwalBanmusModel())->find($itemId);
        (new JadwalBanmusModel())->update($itemId, [
            'undangan_file'      => $stored['file'],
            'undangan_nama_asli' => $stored['original_name'],
        ]);

        $storage->delete($existing['undangan_file'] ?? null);

        return null;
    }

    /**
     * Hapus berkas undangan milik item agenda — dipakai endpoint
     * undangan API mobile.
     */
    public function removeItemInvitation(int $itemId): void
    {
        $existing = (new JadwalBanmusModel())->find($itemId);
        if ($existing === null) {
            return;
        }

        (new JadwalBanmusModel())->update($itemId, [
            'undangan_file'      => null,
            'undangan_nama_asli' => null,
        ]);

        (new ScheduleInvitationStorage())->delete($existing['undangan_file'] ?? null);
    }

    // ── HELPER ───────────────────────────────────────────────────────

    /** @return array{payload?: array<string, mixed>, new_file?: ?string, error?: string} */
    private function storeInvitationUpload(array $input): array
    {
        if (($input['remove_invitation'] ?? false) === true && ($input['invitation_upload'] ?? null) === null) {
            return ['payload' => ['undangan_file' => null, 'undangan_nama_asli' => null], 'new_file' => null];
        }
        if (($input['invitation_upload'] ?? null) === null) {
            return ['payload' => [], 'new_file' => null];
        }

        try {
            $stored = (new ScheduleInvitationStorage())->store($input['invitation_upload']);

            return [
                'payload' => [
                    'undangan_file' => $stored['file'],
                    'undangan_nama_asli' => $stored['original_name'],
                ],
                'new_file' => $stored['file'],
            ];
        } catch (Throwable $exception) {
            log_message('error', 'Gagal menyimpan undangan Banmus: {message}', ['message' => $exception->getMessage()]);

            return ['error' => 'PDF undangan gagal disimpan. Silakan coba kembali.'];
        }
    }

    /** @return array<string, mixed> */
    private function validatedPdfUpload(?UploadedFile $file): array
    {
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

    /** @param array<string, mixed> $input @return list<int> */
    private function postedUnitIds(array $input): array
    {
        $values = $input['unit_ids'] ?? [];
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => max(0, (int) $value),
            $values,
        ))));
    }

    /** @param list<int> $unitIds */
    private function syncItemUnits(int $itemId, array $unitIds): void
    {
        $this->db->table('jadwal_banmus_unit_rapat')
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
        $this->db->table('jadwal_banmus_unit_rapat')->insertBatch($rows);
    }

    private function hasRoomConflict(
        int $roomId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreBanmusId,
    ): bool {
        $banmus = $this->db->table('jadwal_banmus')
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

        if (! $this->db->tableExists('jadwal_umum')) {
            return false;
        }

        return $this->db->table('jadwal_umum')
            ->select('id')
            ->where('tanggal', $date)
            ->where('ruangan_id', $roomId)
            ->where('waktu_mulai <', $endTime)
            ->where('waktu_selesai >', $startTime)
            ->get(1)
            ->getRowArray() !== null;
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

    /** @return array{url?: ?string, error?: string} */
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
