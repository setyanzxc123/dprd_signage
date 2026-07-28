<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AgendaUmumModel;

class GeneralAgendaController extends BaseController
{
    private const CATEGORIES = [
        'demonstrasi',
        'audiensi_publik',
        'kunjungan',
        'kegiatan_sosial',
        'lainnya',
    ];

    private const STATUSES = [
        'tentatif',
        'terkonfirmasi',
        'selesai',
        'dibatalkan',
    ];

    public function index(): string
    {
        $agendas = (new AgendaUmumModel())
            ->orderBy('tanggal', 'DESC')
            ->orderBy('waktu_mulai', 'DESC')
            ->findAll();

        return view('admin/agenda_umum/index', [
            'pageTitle' => 'Agenda Eksternal & Layanan Publik',
            'agendas'   => $agendas,
        ]);
    }

    public function create(): string
    {
        return view('admin/agenda_umum/form', $this->formData(
            'Tambah Jadwal Umum',
            null,
            base_url('admin/agenda-umum/store'),
        ));
    }

    public function store()
    {
        $input = $this->validatedInput();
        if (isset($input['error'])) {
            return $this->failForm($input['error']);
        }

        (new AgendaUmumModel())->insert($input);

        return $this->formSuccessResponse(
            'Jadwal umum berhasil ditambahkan.',
            base_url('admin/agenda-umum'),
        );
    }

    public function edit(int $id)
    {
        $agenda = (new AgendaUmumModel())->find($id);
        if ($agenda === null) {
            session()->setFlashdata('error', 'Jadwal umum tidak ditemukan.');

            return redirect()->to(base_url('admin/agenda-umum'));
        }

        return view('admin/agenda_umum/form', $this->formData(
            'Edit Jadwal Umum',
            $agenda,
            base_url("admin/agenda-umum/{$id}/update"),
        ));
    }

    public function update(int $id)
    {
        $model = new AgendaUmumModel();
        if ($model->find($id) === null) {
            session()->setFlashdata('error', 'Jadwal umum tidak ditemukan.');

            return redirect()->to(base_url('admin/agenda-umum'));
        }

        $input = $this->validatedInput();
        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        $model->update($id, $input);

        return $this->formSuccessResponse(
            'Jadwal umum berhasil diperbarui.',
            base_url('admin/agenda-umum'),
        );
    }

    public function delete(int $id)
    {
        $model = new AgendaUmumModel();
        if ($model->find($id) === null) {
            session()->setFlashdata('error', 'Jadwal umum tidak ditemukan.');

            return redirect()->to(base_url('admin/agenda-umum'));
        }

        $model->delete($id);

        return $this->formSuccessResponse(
            'Jadwal umum berhasil dihapus.',
            base_url('admin/agenda-umum'),
        );
    }

    private function validatedInput(): array
    {
        $judul = trim((string) $this->request->getPost('judul'));
        if ($judul === '' || mb_strlen($judul) > 200) {
            return ['error' => 'Judul wajib diisi dan maksimal 200 karakter.'];
        }

        $kategori = trim((string) $this->request->getPost('kategori'));
        if (! in_array($kategori, self::CATEGORIES, true)) {
            return ['error' => 'Kategori jadwal umum tidak valid.'];
        }

        $tanggal = trim((string) $this->request->getPost('tanggal'));
        if (! $this->validDate($tanggal)) {
            return ['error' => 'Tanggal wajib diisi dengan format yang valid.'];
        }

        $waktuMulai = $this->normalizedTime($this->request->getPost('waktu_mulai'));
        $waktuSelesai = $this->normalizedTime($this->request->getPost('waktu_selesai'), true);
        if ($waktuMulai === null) {
            return ['error' => 'Waktu mulai wajib diisi.'];
        }
        if ($waktuSelesai === false) {
            return ['error' => 'Format waktu selesai tidak valid.'];
        }
        if (is_string($waktuSelesai) && $waktuSelesai <= $waktuMulai) {
            return ['error' => 'Waktu selesai harus setelah waktu mulai.'];
        }

        $lokasi = trim((string) $this->request->getPost('lokasi'));
        if ($lokasi === '' || mb_strlen($lokasi) > 200) {
            return ['error' => 'Lokasi wajib diisi dan maksimal 200 karakter.'];
        }

        $sumber = trim((string) $this->request->getPost('sumber_informasi'));
        if (mb_strlen($sumber) > 200) {
            return ['error' => 'Sumber informasi maksimal 200 karakter.'];
        }

        $pesertaRaw = trim((string) $this->request->getPost('perkiraan_peserta'));
        if ($pesertaRaw !== '' && (! ctype_digit($pesertaRaw) || (int) $pesertaRaw > 1000000)) {
            return ['error' => 'Perkiraan peserta harus berupa angka maksimal 1.000.000.'];
        }

        $keterangan = trim((string) $this->request->getPost('keterangan'));
        if (mb_strlen($keterangan) > 5000) {
            return ['error' => 'Keterangan maksimal 5.000 karakter.'];
        }

        $status = trim((string) $this->request->getPost('status'));
        if (! in_array($status, self::STATUSES, true)) {
            return ['error' => 'Status jadwal umum tidak valid.'];
        }

        return [
            'judul'             => $judul,
            'kategori'          => $kategori,
            'tanggal'           => $tanggal,
            'waktu_mulai'       => $waktuMulai,
            'waktu_selesai'     => $waktuSelesai ?: null,
            'lokasi'            => $lokasi,
            'sumber_informasi'  => $sumber !== '' ? $sumber : null,
            'perkiraan_peserta' => $pesertaRaw !== '' ? (int) $pesertaRaw : null,
            'keterangan'        => $keterangan !== '' ? $keterangan : null,
            'status'            => $status,
            'is_publik'         => $this->request->getPost('is_publik') === '1' ? 1 : 0,
        ];
    }

    private function failForm(string $message, ?int $id = null)
    {
        return $this->formViewErrorResponse('admin/agenda_umum/form', $this->formData(
            $id === null ? 'Tambah Jadwal Umum' : 'Edit Jadwal Umum',
            $this->postedAgenda($id),
            $id === null
                ? base_url('admin/agenda-umum/store')
                : base_url("admin/agenda-umum/{$id}/update"),
        ), $message);
    }

    private function formData(string $title, ?array $agenda, string $actionUrl): array
    {
        return [
            'pageTitle'  => $title,
            'agenda'     => $agenda,
            'action_url' => $actionUrl,
            'categories' => self::CATEGORIES,
            'statuses'   => self::STATUSES,
        ];
    }

    private function postedAgenda(?int $id): array
    {
        return [
            'id'                => $id,
            'judul'             => trim((string) $this->request->getPost('judul')),
            'kategori'          => trim((string) $this->request->getPost('kategori')),
            'tanggal'           => trim((string) $this->request->getPost('tanggal')),
            'waktu_mulai'       => trim((string) $this->request->getPost('waktu_mulai')),
            'waktu_selesai'     => trim((string) $this->request->getPost('waktu_selesai')),
            'lokasi'            => trim((string) $this->request->getPost('lokasi')),
            'sumber_informasi'  => trim((string) $this->request->getPost('sumber_informasi')),
            'perkiraan_peserta' => trim((string) $this->request->getPost('perkiraan_peserta')),
            'keterangan'        => trim((string) $this->request->getPost('keterangan')),
            'status'            => trim((string) $this->request->getPost('status')),
            'is_publik'         => $this->request->getPost('is_publik') === '1' ? 1 : 0,
        ];
    }

    private function validDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function normalizedTime(mixed $value, bool $optional = false): string|false|null
    {
        $time = trim((string) $value);
        if ($time === '' && $optional) {
            return null;
        }

        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
            return $optional ? false : null;
        }

        return $time . ':00';
    }
}
