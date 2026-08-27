<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Notulen\NotulenService;
use App\Models\JadwalBanmusModel;
use App\Models\JadwalUmumModel;
use App\Models\MeetingMinutesModel;
use App\Models\MeetingTranscriptionJobModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class NotulenController extends BaseController
{
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

        $statusFilter = trim((string) $this->request->getGet('status'));
        $searchQuery  = trim((string) $this->request->getGet('q'));

        $builder = $jobModel->orderBy('id', 'DESC');

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            if ($statusFilter === 'in_progress') {
                $builder->whereIn('status', [
                    MeetingTranscriptionJobModel::STATUS_CHUNKING,
                    MeetingTranscriptionJobModel::STATUS_TRANSCRIBING,
                    MeetingTranscriptionJobModel::STATUS_SUMMARIZING,
                ]);
            } else {
                $builder->where('status', $statusFilter);
            }
        }

        if ($searchQuery !== '') {
            $builder->groupStart()
                ->like('audio_filename', $searchQuery)
                ->orLike('current_step', $searchQuery)
                ->groupEnd();
        }

        $jobs = $builder->paginate(15, 'default');
        $pager = $jobModel->pager;

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

        $statusCounts = $jobModel->getStatusCounts();

        return view('admin/notulen/index', [
            'pageTitle'        => 'Notulensi & Risalah AI',
            'jobs'             => $jobs,
            'minutesMap'       => $minutesMap,
            'pager'            => $pager,
            'statusCounts'     => $statusCounts,
            'currentStatus'    => $statusFilter,
            'searchQuery'      => $searchQuery,
            'generalSchedules' => $generalSchedules,
            'banmusItems'      => $banmusItems,
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
     * Handler form unggah rekaman rapat baru.
     */
    public function upload(): RedirectResponse
    {
        $file = $this->request->getFile('audio_file');
        $userId = $this->getCurrentUserId();

        $input = [
            'jadwal_type' => $this->request->getPost('jadwal_type'),
            'jadwal_id'   => $this->request->getPost('jadwal_id'),
            'judul_rapat' => $this->request->getPost('judul_rapat'),
        ];

        $result = $this->service->createJob($input, $file, $userId);

        if (isset($result['error'])) {
            session()->setFlashdata('error', $result['error']);
            return redirect()->back()->withInput();
        }

        session()->setFlashdata('success', 'Rekaman berhasil diunggah dan sedang diproses oleh worker AI.');
        return redirect()->to(base_url('admin/notulen/' . $result['job_id']));
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

    private function getCurrentUserId(): ?int
    {
        $authUser = session()->get('auth_user');
        if (is_array($authUser) && isset($authUser['id'])) {
            return (int) $authUser['id'];
        }
        return null;
    }
}
