<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Crud\JadwalUmumService;
use App\Libraries\Media\MediaUploadException;
use App\Libraries\Notulen\AudioStreamResponder;
use App\Libraries\Notulen\NotulenService;
use App\Libraries\Notulen\PostChunkAudioUpload;
use App\Models\JadwalBanmusModel;
use App\Models\JadwalUmumModel;
use App\Models\MeetingMinutesModel;
use App\Models\MeetingTranscriptionJobModel;
use App\Models\UnitRapatModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Dompdf\Dompdf;

class NotulenController extends BaseController
{
    private const AUDIO_UPLOAD_SESSION_KEY = 'notulen_audio_chunk_token';

    private NotulenService $service;

    public function __construct()
    {
        $this->service = new NotulenService();
    }

    /**
     * Dashboard daftar notulensi dan antrean transkripsi AI.
     */
    public function index(): string|RedirectResponse
    {
        $jobModel = new MeetingTranscriptionJobModel();
        $minutesModel = new MeetingMinutesModel();

        // Cek apakah ada rujukan jadwal yang diminta via query string
        $targetType = trim((string) $this->request->getGet('jadwal_type'));
        $targetId   = (int) $this->request->getGet('jadwal_id');

        $presetSchedule = null;
        if ($targetId > 0 && in_array($targetType, [MeetingTranscriptionJobModel::TYPE_UMUM, MeetingTranscriptionJobModel::TYPE_BANMUS], true)) {
            $isInvalid = false;
            if ($targetType === MeetingTranscriptionJobModel::TYPE_BANMUS) {
                $checkItem = (new JadwalBanmusModel())->find($targetId);
                if ($checkItem && (($checkItem['jenis_agenda'] ?? '') === JadwalBanmusModel::TYPE_NON_MEETING || in_array($checkItem['status'] ?? '', ['dibatalkan', 'proyeksi'], true) || empty($checkItem['tanggal']))) {
                    $isInvalid = true;
                }
            } else {
                $checkItem = (new JadwalUmumModel())->find($targetId);
                if ($checkItem && (($checkItem['jenis_agenda'] ?? '') === JadwalUmumModel::TYPE_NON_MEETING || in_array($checkItem['status'] ?? '', ['dibatalkan', 'non_rapat'], true))) {
                    $isInvalid = true;
                }
            }

            if ($isInvalid) {
                session()->setFlashdata('error', 'Kegiatan non-rapat atau agenda yang dibatalkan tidak memiliki notulensi AI.');

                return redirect()->to(base_url('admin/notulen'));
            }

            // Cek apakah sudah ada job notulen untuk jadwal ini
            $existingJob = $jobModel->where('jadwal_type', $targetType)
                ->where('jadwal_id', $targetId)
                ->orderBy('id', 'DESC')
                ->first();

            if ($existingJob) {
                // Langsung arahkan ke detail notulen yang sudah ada
                return redirect()->to(base_url('admin/notulen/' . (int) $existingJob['id']));
            }

            // Jika belum ada, ambil info jadwal untuk di-preset di modal upload
            $scheduleInfo = $this->service->resolveScheduleInfo($targetType, $targetId);
            $presetSchedule = [
                'type'        => $targetType,
                'id'          => $targetId,
                'judul'       => $scheduleInfo['judul'],
                'tanggal'     => $scheduleInfo['tanggal'],
                'waktu_mulai' => $scheduleInfo['waktu_mulai'],
                'lokasi'      => $scheduleInfo['ruangan'] ?? ($scheduleInfo['lokasi'] ?? ''),
                'label'       => date('d/m/Y', strtotime($scheduleInfo['tanggal'])) . ' — ' . $scheduleInfo['judul'],
            ];
        }

        $jobs = $jobModel->orderBy('id', 'DESC')->findAll();

        // Ambil relasi minutes untuk setiap job
        $jobIds = array_column($jobs, 'id');
        $minutesMap = [];
        if (! empty($jobIds)) {
            $minutesRows = $minutesModel->whereIn('job_id', $jobIds)->findAll();
            foreach ($minutesRows as $mRow) {
                $minutesMap[$mRow['job_id']] = $mRow;
            }
        }

        // Ambil peta rujukan jadwal SSOT untuk seluruh job
        $schedulesMap = $this->service->resolveSchedulesForJobs($jobs);

        $db = db_connect();

        $banmusDocuments = [];
        if ($db->tableExists('dokumen_banmus')) {
            $banmusDocuments = $db->table('dokumen_banmus')
                ->select('id, nomor_sk, judul, masa_persidangan, tahun')
                ->orderBy('tahun', 'DESC')
                ->orderBy('id', 'DESC')
                ->limit(30)
                ->get()->getResultArray();
        }

        $unitRapatList = [];
        if ($db->tableExists('unit_rapat')) {
            $unitRapatList = $db->table('unit_rapat')
                ->select('id, nama')
                ->where('aktif', 1)
                ->orderBy('urutan', 'ASC')
                ->orderBy('nama', 'ASC')
                ->get()->getResultArray();
        }

        $generalBuilder = $db->table('jadwal_umum ju')
            ->select('ju.id, ju.judul, ju.tanggal, ju.waktu_mulai');

        if ($db->fieldExists('jenis_agenda', 'jadwal_umum')) {
            $generalBuilder->where('ju.jenis_agenda', JadwalUmumModel::TYPE_MEETING);
        }
        if ($db->fieldExists('status', 'jadwal_umum')) {
            $generalBuilder->whereNotIn('ju.status', ['dibatalkan', 'non_rapat']);
        }

        if ($db->tableExists('ruangan') && $db->fieldExists('ruangan_id', 'jadwal_umum')) {
            $generalBuilder->select('r.name AS nama_ruangan')
                ->join('ruangan r', 'r.id = ju.ruangan_id', 'left');
        }
        if ($db->fieldExists('lokasi_lainnya', 'jadwal_umum')) {
            $generalBuilder->select('ju.lokasi_lainnya');
        }
        if ($db->fieldExists('pihak_eksternal', 'jadwal_umum')) {
            $generalBuilder->select('ju.pihak_eksternal');
        }

        $generalSchedules = $generalBuilder
            ->orderBy('ju.tanggal', 'DESC')
            ->orderBy('ju.waktu_mulai', 'DESC')
            ->limit(100)
            ->get()->getResultArray();

        $generalUnitMap = [];
        if ($db->tableExists('jadwal_umum_unit_rapat') && $db->tableExists('unit_rapat')) {
            try {
                $generalUnitMap = (new JadwalUmumService($db))->unitNamesByScheduleIds(array_column($generalSchedules, 'id'));
            } catch (\Throwable) {
                $generalUnitMap = [];
            }
        }

        $banmusBuilder = $db->table('jadwal_banmus jb')
            ->select('jb.id, jb.agenda, jb.tanggal, jb.jam_mulai AS waktu_mulai');

        if ($db->fieldExists('deleted_at', 'jadwal_banmus')) {
            $banmusBuilder->where('jb.deleted_at IS NULL', null, false);
        }
        if ($db->fieldExists('jenis_agenda', 'jadwal_banmus')) {
            $banmusBuilder->where('jb.jenis_agenda', JadwalBanmusModel::TYPE_MEETING);
        }
        if ($db->fieldExists('status', 'jadwal_banmus')) {
            $banmusBuilder->whereNotIn('jb.status', ['dibatalkan', 'non_rapat', 'proyeksi']);
        }
        $banmusBuilder->where('jb.tanggal IS NOT NULL', null, false)->where("jb.tanggal != ''", null, false);
        if ($db->tableExists('dokumen_banmus') && $db->fieldExists('dokumen_banmus_id', 'jadwal_banmus')) {
            $banmusBuilder->select('jb.dokumen_banmus_id, db.nomor_sk, db.judul AS dokumen_judul, db.masa_persidangan, db.tahun AS dokumen_tahun')
                ->join('dokumen_banmus db', 'db.id = jb.dokumen_banmus_id', 'left');
        }
        if ($db->tableExists('ruangan') && $db->fieldExists('ruangan_id', 'jadwal_banmus')) {
            $banmusBuilder->select('r.name AS nama_ruangan')
                ->join('ruangan r', 'r.id = jb.ruangan_id', 'left');
        }
        if ($db->fieldExists('lokasi_lainnya', 'jadwal_banmus')) {
            $banmusBuilder->select('jb.lokasi_lainnya');
        }

        $banmusItems = $banmusBuilder
            ->orderBy('jb.tanggal', 'DESC')
            ->orderBy('jb.jam_mulai', 'DESC')
            ->limit(100)
            ->get()->getResultArray();

        return view('admin/notulen/index', [
            'pageTitle'        => 'Notulensi & Risalah AI',
            'jobs'             => $jobs,
            'minutesMap'       => $minutesMap,
            'schedulesMap'     => $schedulesMap,
            'generalSchedules' => $generalSchedules,
            'generalUnitMap'   => $generalUnitMap,
            'banmusItems'      => $banmusItems,
            'banmusDocuments'  => $banmusDocuments,
            'unitRapatList'    => $unitRapatList,
            'presetSchedule'   => $presetSchedule,
            'audioUploadToken' => $this->audioUploadToken(),
            'audioChunkSize'   => PostChunkAudioUpload::CHUNK_BYTES,
            'audioMaxSize'     => PostChunkAudioUpload::MAX_BYTES,
        ]);
    }

    /**
     * Halaman review risalah rapat, transkrip percakapan, dan player audio.
     */
    public function show(int $id): string|RedirectResponse
    {
        $detail = $this->service->getNotulenDetail($id);

        if (! $detail) {
            session()->setFlashdata('error', 'Data notulen rapat tidak ditemukan.');
            return redirect()->to(base_url('admin/notulen'));
        }

        return view('admin/notulen/show', [
            'pageTitle'    => 'Review Risalah',
            'breadcrumbs'  => [
                ['label' => 'Notulensi & Risalah AI', 'url' => 'admin/notulen'],
            ],
            'job'          => $detail['job'],
            'minutes'      => $detail['minutes'],
            'schedule'     => $detail['schedule'],
            'transcripts'  => $detail['transcripts'],
            'pillars'      => $detail['pillars'] ?? [],
            'hasAudioFile' => $detail['hasAudioFile'] ?? false,
            'aiModelLabel' => NotulenService::formatAiModelLabel($detail['job']['ai_model'] ?? null),
        ]);
    }

    /**
     * Handler commit upload audio setelah chunked upload selesai.
     * Menerima upload_id dari sesi chunk, memindahkan file ke storage job,
     * lalu membuat job transkripsi di database.
     */
    public function upload(): ResponseInterface
    {
        $uploadId = trim((string) $this->request->getPost('upload_id'));
        $userId   = $this->getCurrentUserId();

        $input = [
            'jadwal_type' => $this->request->getPost('jadwal_type'),
            'jadwal_id'   => $this->request->getPost('jadwal_id'),
            'judul_rapat' => $this->request->getPost('judul_rapat'),
        ];

        if ($uploadId === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Upload ID tidak ditemukan. Silakan unggah file kembali.',
            ]);
        }

        try {
            $ownerToken = $this->audioUploadToken();
            $result     = $this->service->createJobFromChunk($input, $ownerToken, $uploadId, $userId);
        } catch (MediaUploadException $e) {
            return $this->response->setStatusCode($e->getStatusCode())->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        if (isset($result['error'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => $result['error'],
            ]);
        }

        $redirectUrl = base_url('admin/notulen/' . $result['job_id']);
        session()->setFlashdata('success', 'Rekaman berhasil diunggah dan sedang diproses oleh worker AI.');

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => 'Rekaman berhasil diunggah dan sedang diproses oleh worker AI.',
            'job_id'   => $result['job_id'],
            'redirect' => $redirectUrl,
        ]);
    }

    /** Mulai sesi chunked upload audio. */
    public function startAudioUpload(): ResponseInterface
    {
        try {
            $payload = $this->audioUploader()->start(
                $this->validatedRequestAudioToken(),
                trim((string) $this->request->getPost('client_key')),
                (string) $this->request->getPost('file_name'),
                (int) $this->request->getPost('file_size'),
                (string) $this->request->getPost('file_type'),
            );

            return $this->response->setJSON(['status' => 'success'] + $payload);
        } catch (MediaUploadException $e) {
            return $this->audioUploadError($e);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal memulai chunk upload audio: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Server gagal menyiapkan upload audio.']);
        }
    }

    /** Terima satu chunk audio. */
    public function appendAudioChunk(): ResponseInterface
    {
        try {
            $chunk = $this->request->getFile('chunk');
            if ($chunk === null) {
                throw new MediaUploadException('Chunk upload tidak ditemukan.');
            }

            $payload = $this->audioUploader()->append(
                $this->validatedRequestAudioToken(),
                trim((string) $this->request->getPost('upload_id')),
                (int) $this->request->getPost('offset'),
                trim((string) $this->request->getPost('checksum')),
                $chunk,
            );

            return $this->response->setJSON(['status' => 'success'] + $payload);
        } catch (MediaUploadException $e) {
            return $this->audioUploadError($e);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menerima chunk audio: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Server gagal menerima bagian file.']);
        }
    }

    /** Batalkan sesi chunked upload audio. */
    public function cancelAudioUpload(): ResponseInterface
    {
        try {
            $this->audioUploader()->cancel(
                $this->validatedRequestAudioToken(),
                trim((string) $this->request->getPost('upload_id')),
            );

            return $this->response->setJSON(['status' => 'success']);
        } catch (MediaUploadException $e) {
            return $this->audioUploadError($e);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membatalkan chunk upload audio: {message}', ['message' => $e->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Server gagal membatalkan upload.']);
        }
    }

    /**
     * Endpoint AJAX JSON untuk polling live progress status job.
     */
    public function status(int $jobId): ResponseInterface
    {
        $job = (new MeetingTranscriptionJobModel())->find($jobId);

        if (! $job) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Job tidak ditemukan.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'id'               => (int) $job['id'],
                'status'           => $job['status'],
                'progress_percent' => (int) $job['progress_percent'],
                'current_step'     => $job['current_step'] ?? '',
                'total_chunks'     => (int) $job['total_chunks'],
                'completed_chunks' => (int) $job['completed_chunks'],
                'cancel_requested' => (bool) $job['cancel_requested'],
                'error_message'    => $job['error_message'],
                'ai_model'         => $job['ai_model'] ?? null,
                'ai_model_label'   => NotulenService::formatAiModelLabel($job['ai_model'] ?? null),
            ],
        ]);
    }

    /**
     * Endpoint ringkas polling task aktif dan antrean AI global.
     */
    public function activeTasks(): ResponseInterface
    {
        $data = $this->service->getActiveTasksSummary();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Endpoint streaming audio rekaman rapat untuk audio player di web admin.
     * Logika Range/206/streaming dibagikan dengan API mobile via AudioStreamResponder.
     */
    public function audio(int $jobId): ResponseInterface
    {
        return AudioStreamResponder::respond($this->request, $this->response, $this->service->resolveAudioPath($jobId));
    }

    /**
     * Handler antrekan ulang job gagal/dibatalkan (Resume dari checkpoint terakhir).
     */
    public function retry(int $jobId): RedirectResponse
    {
        $result = $this->service->requeueJob($jobId);

        if (isset($result['error'])) {
            session()->setFlashdata('error', $result['error']);
        } else {
            session()->setFlashdata('success', $result['message']);
        }

        return redirect()->back();
    }

    /**
     * Handler batalkan job (langsung untuk queued, kooperatif untuk in-progress).
     */
    public function cancel(int $jobId): ResponseInterface|RedirectResponse
    {
        $result = $this->service->requestCancel($jobId);

        if ($this->request->isAJAX() || str_contains((string) $this->request->header('Accept')?->getValue(), 'application/json')) {
            if (isset($result['error'])) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status'  => 'error',
                    'message' => $result['error'],
                ]);
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $result['message'],
            ]);
        }

        if (isset($result['error'])) {
            session()->setFlashdata('error', $result['error']);
        } else {
            session()->setFlashdata('success', $result['message']);
        }

        return redirect()->back();
    }

    /**
     * Handler pembersihan file audio lokal (hemat disk storage).
     */
    public function deleteRecording(int $jobId): RedirectResponse
    {
        $result = $this->service->purgeAudioFiles($jobId);

        if (isset($result['error'])) {
            session()->setFlashdata('error', $result['error']);
        } else {
            session()->setFlashdata('success', $result['message']);
        }

        return redirect()->back();
    }

    /**
     * Handler hapus permanen notulen dan file terkait.
     */
    public function destroy(int $id): RedirectResponse
    {
        $result = $this->service->deleteNotulen($id);

        if (isset($result['error'])) {
            session()->setFlashdata('error', $result['error']);
            return redirect()->back();
        }

        session()->setFlashdata('success', 'Notulen rapat berhasil dihapus.');
        return redirect()->to(base_url('admin/notulen'));
    }

    /**
     * Handler unduh transkrip percakapan utuh (.txt).
     * Hanya dapat diunduh jika proses transkripsi AI telah selesai 100%.
     */
    public function downloadTranscript(int $id): ResponseInterface|RedirectResponse
    {
        $job = (new MeetingTranscriptionJobModel())->find($id);
        if (! $job || $job['status'] !== MeetingTranscriptionJobModel::STATUS_COMPLETED) {
            session()->setFlashdata('error', 'Berkas transkrip belum dapat diunduh karena proses transkripsi AI belum selesai.');
            return redirect()->back();
        }

        $transcripts = $this->service->readTranscripts($id);
        $fullText = $transcripts['full_text'];

        if ($fullText === '') {
            session()->setFlashdata('error', 'Berkas transkrip belum tersedia atau kosong.');
            return redirect()->back();
        }

        $filename = "transkrip_rapat_job_{$id}.txt";

        return $this->response
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($fullText);
    }

    /**
     * Handler simpan revisi/editan risalah oleh notulis.
     * Mendukung submission form standar (MPA) maupun AJAX JSON (Ctrl+S / Quick Save).
     */
    public function updateMinutes(int $minutesId): ResponseInterface|RedirectResponse
    {
        $userId = $this->getCurrentUserId();

        $input = [
            'ringkasan_eksekutif' => $this->request->getPost('ringkasan_eksekutif'),
            'section_ringkasan'   => $this->request->getPost('section_ringkasan'),
            'section_pembahasan'  => $this->request->getPost('section_pembahasan'),
            'section_kesimpulan'  => $this->request->getPost('section_kesimpulan'),
        ];

        $result = $this->service->updateMinutes($minutesId, $input, $userId);

        if ($this->request->isAJAX() || str_contains((string) $this->request->getHeaderLine('Accept'), 'application/json')) {
            if (isset($result['error'])) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status'  => 'error',
                    'message' => $result['error'],
                ]);
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $result['message'] ?? 'Perubahan risalah rapat berhasil disimpan.',
            ]);
        }

        if (isset($result['error'])) {
            session()->setFlashdata('error', $result['error']);
        } else {
            session()->setFlashdata('success', 'Perubahan risalah rapat berhasil disimpan.');
        }

        return redirect()->back();
    }

    /**
     * Handler finalisasi risalah rapat.
     */
    public function finalizeMinutes(int $minutesId): RedirectResponse
    {
        $userId = $this->getCurrentUserId();

        if ($userId === null) {
            session()->setFlashdata('error', 'Sesi tidak valid. Silakan masuk kembali sebelum memfinalisasi risalah.');
            return redirect()->back();
        }

        $result = $this->service->finalizeMinutes($minutesId, $userId);

        if (isset($result['error'])) {
            session()->setFlashdata('error', $result['error']);
        } else {
            session()->setFlashdata('success', 'Risalah rapat telah difinalisasi.');
        }

        return redirect()->back();
    }

    /**
     * Handler buka kunci / revisi risalah rapat yang telah difinalisasi.
     */
    public function unfinalizeMinutes(int $minutesId): RedirectResponse
    {
        $userId = $this->getCurrentUserId();

        $result = $this->service->unfinalizeMinutes($minutesId, $userId);

        if (isset($result['error'])) {
            session()->setFlashdata('error', $result['error']);
        } else {
            session()->setFlashdata('success', $result['message']);
        }

        return redirect()->back();
    }

    /**
     * Export PDF server-side risalah rapat.
     * Isi dokumen mengikuti preview tab risalah: header identitas, judul,
     * metadata, lalu naskah lengkap tanpa kop surat dan kolom tanda tangan.
     */
    public function exportPdf(int $minutesId): ResponseInterface|RedirectResponse
    {
        $rendered = $this->service->renderMinutesPdf($minutesId);

        if (isset($rendered['error'])) {
            session()->setFlashdata('error', $rendered['error']);
            $redirectPath = isset($rendered['job_id'])
                ? 'admin/notulen/' . $rendered['job_id']
                : 'admin/notulen';
            return redirect()->to(base_url($redirectPath));
        }

        return $this->response
            ->setStatusCode(200)
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $rendered['filename'] . '"')
            ->setHeader('Cache-Control', 'no-store, private')
            ->setBody($rendered['pdf']);
    }

    private function audioUploader(): PostChunkAudioUpload
    {
        return new PostChunkAudioUpload();
    }

    private function audioUploadToken(): string
    {
        $token = (string) session()->get(self::AUDIO_UPLOAD_SESSION_KEY);
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            $token = bin2hex(random_bytes(32));
            session()->set(self::AUDIO_UPLOAD_SESSION_KEY, $token);
        }

        return $token;
    }

    private function validatedRequestAudioToken(): string
    {
        $requestToken = trim((string) $this->request->getPost('upload_token'));
        $sessionToken = $this->audioUploadToken();
        if (! hash_equals($sessionToken, $requestToken)) {
            throw new MediaUploadException('Sesi upload tidak valid. Muat ulang halaman.', 403);
        }

        return $sessionToken;
    }

    private function audioUploadError(MediaUploadException $e): ResponseInterface
    {
        $code = $e->getStatusCode();
        if (! in_array($code, [400, 403, 404, 409, 413, 422, 500, 503], true)) {
            $code = 422;
        }

        return $this->response->setStatusCode($code)->setJSON([
            'status'  => 'error',
            'message' => $e->getMessage(),
        ]);
    }

    private function getCurrentUserId(): ?int
    {
        $authUser = session()->get('auth_user');
        if (is_array($authUser) && isset($authUser['id'])) {
            return (int) $authUser['id'];
        }
        return null;
    }
}
