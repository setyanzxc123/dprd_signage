<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Api\ListPaginator;
use App\Libraries\Notulen\AudioStreamResponder;
use App\Libraries\Notulen\NotulenService;
use App\Models\MeetingMinutesModel;
use App\Models\MeetingTranscriptionJobModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Endpoint read-only notulensi untuk aplikasi mobile admin:
 * daftar job, polling status, transkrip per segmen, dan streaming audio.
 * Dilindungi filter apiadmin (bearer token + grup superadmin/operator).
 */
class NotulenController extends BaseController
{
    use ApiResponse;

    private NotulenService $service;

    public function __construct()
    {
        $this->service = new NotulenService();
    }

    /**
     * GET api/v1/notulen/jobs?page=&per_page=&q=
     */
    public function index(): ResponseInterface
    {
        $paginator = ListPaginator::fromRequest();

        $jobModel = new MeetingTranscriptionJobModel();
        if ($paginator->hasSearch()) {
            $jobModel->like('audio_filename', $paginator->search);
        }
        $total = $jobModel->countAllResults(false);
        $jobs  = $jobModel->orderBy('id', 'DESC')->findAll($paginator->perPage, $paginator->offset());

        $risalahStatusMap = [];
        $jobIds = array_column($jobs, 'id');
        if ($jobIds !== []) {
            foreach ((new MeetingMinutesModel())->whereIn('job_id', $jobIds)->findAll() as $minutes) {
                $risalahStatusMap[$minutes['job_id']] = $minutes['status_verifikasi'];
            }
        }
        $schedules = $this->service->resolveSchedulesForJobs($jobs);

        $data = array_map(static function (array $job) use ($risalahStatusMap, $schedules): array {
            $jadwal = $schedules[$job['jadwal_type']][(int) ($job['jadwal_id'] ?? 0)] ?? null;

            return [
                'id'               => (int) $job['id'],
                'status'           => $job['status'],
                'audio_filename'   => $job['audio_filename'],
                'progress_percent' => (int) $job['progress_percent'],
                'current_step'     => $job['current_step'] ?? '',
                'total_chunks'     => (int) $job['total_chunks'],
                'completed_chunks' => (int) $job['completed_chunks'],
                'error_message'    => $job['error_message'],
                'ai_model_label'   => NotulenService::formatAiModelLabel($job['ai_model'] ?? null),
                'risalah_status'   => $risalahStatusMap[$job['id']] ?? null,
                'jadwal'           => $jadwal !== null
                    ? ['judul' => $jadwal['judul'], 'tanggal' => $jadwal['tanggal']]
                    : null,
                'created_at'       => $job['created_at'],
                'updated_at'       => $job['updated_at'],
            ];
        }, $jobs);

        return $this->apiSuccess([
            'data' => $data,
            'meta' => $paginator->meta($total),
        ]);
    }

    /**
     * GET api/v1/notulen/jobs/{id} - polling status live.
     */
    public function show(int $id): ResponseInterface
    {
        $job = (new MeetingTranscriptionJobModel())->find($id);

        if ($job === null) {
            return $this->apiError('Job notulen tidak ditemukan.', 404);
        }

        $minutes = (new MeetingMinutesModel())->where('job_id', $id)->first();

        return $this->apiSuccess(['data' => [
            'id'               => (int) $job['id'],
            'status'           => $job['status'],
            'progress_percent' => (int) $job['progress_percent'],
            'current_step'     => $job['current_step'] ?? '',
            'total_chunks'     => (int) $job['total_chunks'],
            'completed_chunks' => (int) $job['completed_chunks'],
            'cancel_requested' => (bool) $job['cancel_requested'],
            'error_message'    => $job['error_message'],
            'ai_model_label'   => NotulenService::formatAiModelLabel($job['ai_model'] ?? null),
            'risalah_status'   => $minutes['status_verifikasi'] ?? null,
            'updated_at'       => $job['updated_at'],
        ]]);
    }

    /**
     * GET api/v1/notulen/jobs/{id}/transkrip
     */
    public function transkrip(int $id): ResponseInterface
    {
        $job = (new MeetingTranscriptionJobModel())->find($id);

        if ($job === null) {
            return $this->apiError('Job notulen tidak ditemukan.', 404);
        }

        $transcripts = $this->service->readTranscripts($id);

        return $this->apiSuccess(['data' => [
            'total_chunks' => $transcripts['total_chunks'],
            'chunks'       => $transcripts['chunks'],
            'full_text'    => $transcripts['full_text'],
        ]]);
    }

    /**
     * GET api/v1/notulen/jobs/{id}/audio - streaming dengan dukungan Range.
     */
    public function audio(int $id): ResponseInterface
    {
        $job = (new MeetingTranscriptionJobModel())->find($id);

        if ($job === null) {
            return $this->apiError('Job notulen tidak ditemukan.', 404);
        }

        return AudioStreamResponder::respond($this->request, $this->response, $this->service->resolveAudioPath($id));
    }

    /**
     * POST api/v1/notulen/jobs/{id}/cancel - langsung untuk queued,
     * kooperatif untuk job in-progress.
     */
    public function cancel(int $id): ResponseInterface
    {
        $result = $this->service->requestCancel($id);

        if (isset($result['error'])) {
            return $this->apiError($result['error'], 422);
        }

        return $this->apiSuccess(['data' => [
            'job_id' => $id,
            'status' => $result['status'],
            'message' => $result['message'],
        ]]);
    }

    /**
     * POST api/v1/notulen/jobs/{id}/retry - antrekan ulang dari checkpoint terakhir.
     */
    public function retry(int $id): ResponseInterface
    {
        $result = $this->service->requeueJob($id);

        if (isset($result['error'])) {
            return $this->apiError($result['error'], 422);
        }

        return $this->apiSuccess(['data' => [
            'job_id' => $id,
            'status' => $result['status'],
            'message' => $result['message'],
        ]]);
    }

    /**
     * DELETE api/v1/notulen/jobs/{id} - hapus notulen beserta folder job.
     */
    public function delete(int $id): ResponseInterface
    {
        $result = $this->service->deleteNotulen($id);

        if (isset($result['error'])) {
            return $this->apiError($result['error'], 422);
        }

        return $this->apiSuccess(['data' => [
            'job_id' => $id,
            'message' => $result['message'],
        ]]);
    }

    /**
     * DELETE api/v1/notulen/jobs/{id}/rekaman - bersihkan audio saja
     * (transkrip dan risalah dipertahankan). Ditolak selama job berjalan.
     */
    public function purgeRecording(int $id): ResponseInterface
    {
        $result = $this->service->purgeAudioFiles($id);

        if (isset($result['error'])) {
            return $this->apiError($result['error'], 422);
        }

        return $this->apiSuccess(['data' => [
            'job_id' => $id,
            'message' => $result['message'],
        ]]);
    }

    /**
     * GET api/v1/notulen/risalah/{minutesId} - detail risalah + 3 pilar.
     */
    public function showMinutes(int $minutesId): ResponseInterface
    {
        $minutes = (new MeetingMinutesModel())->find($minutesId);

        if ($minutes === null) {
            return $this->apiError('Risalah rapat tidak ditemukan.', 404);
        }

        $pillars = null;
        if (! empty($minutes['struktur_json'])) {
            $decoded = json_decode((string) $minutes['struktur_json'], true);
            if (is_array($decoded) && isset($decoded['ringkasan_utama'], $decoded['poin_pembahasan'], $decoded['kesimpulan_akhir'])) {
                $pillars = $decoded;
            }
        }
        if ($pillars === null) {
            $pillars = $this->service->parsePillarsFromText($minutes['ringkasan_eksekutif'] ?? null);
        }

        $job = (new MeetingTranscriptionJobModel())->find((int) $minutes['job_id']);

        return $this->apiSuccess(['data' => [
            'id'                  => (int) $minutes['id'],
            'job_id'              => (int) $minutes['job_id'],
            'job_status'          => $job['status'] ?? null,
            'status_verifikasi'   => $minutes['status_verifikasi'],
            'verified_at'         => $minutes['verified_at'] ?? null,
            'ringkasan_eksekutif' => $minutes['ringkasan_eksekutif'],
            'tiga_pilar'          => $pillars,
        ]]);
    }

    /**
     * PUT api/v1/notulen/risalah/{minutesId} - simpan revisi notulis.
     * Menerima section_ringkasan/section_pembahasan/section_kesimpulan
     * atau ringkasan_eksekutif utuh. Risalah final harus dibuka kunci dulu.
     */
    public function updateMinutes(int $minutesId): ResponseInterface
    {
        $input = [
            'ringkasan_eksekutif' => $this->input('ringkasan_eksekutif'),
            'section_ringkasan'   => $this->input('section_ringkasan'),
            'section_pembahasan'  => $this->input('section_pembahasan'),
            'section_kesimpulan'  => $this->input('section_kesimpulan'),
        ];

        $result = $this->service->updateMinutes($minutesId, $input);

        if (isset($result['error'])) {
            return $this->apiError($result['error'], 422);
        }

        return $this->apiSuccess(['data' => [
            'minutes_id' => $minutesId,
            'message' => $result['message'],
        ]]);
    }

    /**
     * POST api/v1/notulen/risalah/{minutesId}/finalisasi
     */
    public function finalizeMinutes(int $minutesId): ResponseInterface
    {
        $user = service('requestIdentity')->currentUser();

        if ($user === null) {
            return $this->apiUnauthorized();
        }

        $result = $this->service->finalizeMinutes($minutesId, (int) $user->id);

        if (isset($result['error'])) {
            return $this->apiError($result['error'], 422);
        }

        return $this->apiSuccess(['data' => [
            'minutes_id' => $minutesId,
            'status_verifikasi' => $result['status_verifikasi'],
            'message' => $result['message'],
        ]]);
    }

    /**
     * POST api/v1/notulen/risalah/{minutesId}/unfinalisasi - buka kunci revisi.
     */
    public function unfinalizeMinutes(int $minutesId): ResponseInterface
    {
        $user = service('requestIdentity')->currentUser();

        if ($user === null) {
            return $this->apiUnauthorized();
        }

        $result = $this->service->unfinalizeMinutes($minutesId, (int) $user->id);

        if (isset($result['error'])) {
            return $this->apiError($result['error'], 422);
        }

        return $this->apiSuccess(['data' => [
            'minutes_id' => $minutesId,
            'status_verifikasi' => $result['status_verifikasi'],
            'message' => $result['message'],
        ]]);
    }
}
