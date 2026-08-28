<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Media\MediaUploadException;
use App\Libraries\Notulen\NotulenService;
use App\Libraries\Notulen\PostChunkAudioUpload;
use App\Models\JadwalBanmusModel;
use App\Models\JadwalUmumModel;
use App\Models\MeetingMinutesModel;
use App\Models\MeetingTranscriptionJobModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

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
    public function index(): string
    {
        $jobModel = new MeetingTranscriptionJobModel();
        $minutesModel = new MeetingMinutesModel();

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

        // Ambil opsi jadwal aktif untuk form upload cepat
        $generalSchedules = (new JadwalUmumModel())
            ->select('id, judul, tanggal, waktu_mulai')
            ->orderBy('tanggal', 'DESC')
            ->limit(20)
            ->findAll();

        $banmusItems = (new JadwalBanmusModel())
            ->select('id, agenda, tanggal, jam_mulai AS waktu_mulai')
            ->orderBy('tanggal', 'DESC')
            ->limit(20)
            ->findAll();

        return view('admin/notulen/index', [
            'pageTitle'        => 'Notulensi & Risalah AI',
            'jobs'             => $jobs,
            'minutesMap'       => $minutesMap,
            'generalSchedules' => $generalSchedules,
            'banmusItems'      => $banmusItems,
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
            'pageTitle'           => 'Review Risalah — ' . ($detail['minutes']['judul_rapat'] ?? $detail['job']['audio_filename']),
            'job'                 => $detail['job'],
            'minutes'             => $detail['minutes'],
            'transcripts'         => $detail['transcripts'],
            'agendaItems'         => $detail['agenda_items'],
            'kesimpulanItems'     => $detail['kesimpulan_items'],
            'tindakLanjutItems'   => $detail['tindak_lanjut_items'],
            'pesertaItems'        => $detail['peserta_items'],
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
            ],
        ]);
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
    public function cancel(int $jobId): RedirectResponse
    {
        $result = $this->service->requestCancel($jobId);

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
     */
    public function downloadTranscript(int $id): ResponseInterface|RedirectResponse
    {
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
     */
    public function updateMinutes(int $minutesId): RedirectResponse
    {
        $userId = $this->getCurrentUserId();

        $input = [
            'judul_rapat'         => $this->request->getPost('judul_rapat'),
            'tanggal_rapat'       => $this->request->getPost('tanggal_rapat'),
            'ringkasan_eksekutif' => $this->request->getPost('ringkasan_eksekutif'),
            'agenda_pembahasan'   => $this->request->getPost('agenda_pembahasan'),
            'kesimpulan'          => $this->request->getPost('kesimpulan'),
            'tindak_lanjut'       => $this->request->getPost('tindak_lanjut'),
            'peserta_terdeteksi'  => $this->request->getPost('peserta_terdeteksi'),
        ];

        $result = $this->service->updateMinutes($minutesId, $input, $userId);

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
        $userId = $this->getCurrentUserId() ?? 1;

        $result = $this->service->finalizeMinutes($minutesId, $userId);

        if (isset($result['error'])) {
            session()->setFlashdata('error', $result['error']);
        } else {
            session()->setFlashdata('success', 'Risalah rapat telah difinalisasi.');
        }

        return redirect()->back();
    }

    /**
     * Halaman cetak resmi / export PDF risalah dengan kop surat DPRD.
     */
    public function exportPdf(int $minutesId): string|RedirectResponse
    {
        $minutesModel = new MeetingMinutesModel();
        $minutes = $minutesModel->find($minutesId);

        if (! $minutes) {
            session()->setFlashdata('error', 'Data risalah rapat tidak ditemukan.');
            return redirect()->to(base_url('admin/notulen'));
        }

        $decodedAgenda = ! empty($minutes['agenda_pembahasan']) ? json_decode((string) $minutes['agenda_pembahasan'], true) : [];
        $decodedKesimpulan = ! empty($minutes['kesimpulan']) ? json_decode((string) $minutes['kesimpulan'], true) : [];
        $decodedTindakLanjut = ! empty($minutes['tindak_lanjut']) ? json_decode((string) $minutes['tindak_lanjut'], true) : [];
        $decodedPeserta = ! empty($minutes['peserta_terdeteksi']) ? json_decode((string) $minutes['peserta_terdeteksi'], true) : [];

        return view('admin/notulen/print', [
            'pageTitle'         => 'Cetak Risalah — ' . $minutes['judul_rapat'],
            'minutes'           => $minutes,
            'agendaItems'       => is_array($decodedAgenda) ? $decodedAgenda : [],
            'kesimpulanItems'   => is_array($decodedKesimpulan) ? $decodedKesimpulan : [],
            'tindakLanjutItems' => is_array($decodedTindakLanjut) ? $decodedTindakLanjut : [],
            'pesertaItems'      => is_array($decodedPeserta) ? $decodedPeserta : [],
        ]);
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
