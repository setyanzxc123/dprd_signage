<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AgendaUmumModel;

class GeneralAgendaController extends BaseController
{
    private const CATEGORY_LABELS = [
        'audiensi'         => 'Audiensi / Penerimaan Aspirasi',
        'audiensi_publik'  => 'Audiensi / Penerimaan Aspirasi',
        'demonstrasi'      => 'Aksi Unjuk Rasa / Demonstrasi',
        'kunjungan'        => 'Kunjungan Tamu atau Instansi',
        'undangan'         => 'Undangan / Agenda Luar Gedung',
        'kegiatan_sosial'  => 'Kegiatan Sosial dan Publik',
        'lainnya'          => 'Lainnya',
    ];

    public function index(): string
    {
        $agendas = (new AgendaUmumModel())
            ->orderBy('tanggal', 'DESC')
            ->orderBy('waktu_mulai', 'DESC')
            ->findAll();

        return view('admin/agenda_umum/index', [
            'pageTitle'       => 'Agenda Eksternal & Layanan Publik',
            'agendas'         => $agendas,
            'category_labels' => self::CATEGORY_LABELS,
        ]);
    }

    public function create(): string
    {
        return view('admin/agenda_umum/form', $this->formData(
            'Tambah Agenda Eksternal',
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

        if ((new AgendaUmumModel())->insert($input) === false) {
            return $this->failForm('Agenda eksternal gagal disimpan. Silakan coba kembali.');
        }

        return $this->formSuccessResponse(
            'Agenda eksternal berhasil ditambahkan.',
            base_url('admin/agenda-umum'),
        );
    }

    public function edit(int $id)
    {
        $agenda = (new AgendaUmumModel())->find($id);
        if ($agenda === null) {
            session()->setFlashdata('error', 'Agenda eksternal tidak ditemukan.');

            return redirect()->to(base_url('admin/agenda-umum'));
        }

        return view('admin/agenda_umum/form', $this->formData(
            'Edit Agenda Eksternal',
            $agenda,
            base_url("admin/agenda-umum/{$id}/update"),
        ));
    }

    public function update(int $id)
    {
        $model = new AgendaUmumModel();
        if ($model->find($id) === null) {
            session()->setFlashdata('error', 'Agenda eksternal tidak ditemukan.');

            return redirect()->to(base_url('admin/agenda-umum'));
        }

        $input = $this->validatedInput();
        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        if (! $model->update($id, $input)) {
            return $this->failForm('Agenda eksternal gagal diperbarui. Silakan coba kembali.', $id);
        }

        return $this->formSuccessResponse(
            'Agenda eksternal berhasil diperbarui.',
            base_url('admin/agenda-umum'),
        );
    }

    public function delete(int $id)
    {
        $model = new AgendaUmumModel();
        if ($model->find($id) === null) {
            session()->setFlashdata('error', 'Agenda eksternal tidak ditemukan.');

            return redirect()->to(base_url('admin/agenda-umum'));
        }

        $model->delete($id);

        return $this->formSuccessResponse(
            'Agenda eksternal berhasil dihapus.',
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
        if (! in_array($kategori, AgendaUmumModel::CATEGORIES, true)) {
            return ['error' => 'Jenis kegiatan tidak valid.'];
        }

        $pihakEksternal = trim((string) $this->request->getPost('pihak_eksternal'));
        if ($pihakEksternal === '' || mb_strlen($pihakEksternal) > 255) {
            return ['error' => 'Pihak atau instansi eksternal wajib diisi dan maksimal 255 karakter.'];
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

        $keterangan = trim((string) $this->request->getPost('keterangan'));
        if (mb_strlen($keterangan) > 5000) {
            return ['error' => 'Keterangan maksimal 5.000 karakter.'];
        }

        return [
            'judul'             => $judul,
            'kategori'          => $kategori,
            'pihak_eksternal'   => $pihakEksternal,
            'tanggal'           => $tanggal,
            'waktu_mulai'       => $waktuMulai,
            'waktu_selesai'     => $waktuSelesai ?: null,
            'lokasi'            => $lokasi,
            'sumber_informasi'  => $sumber !== '' ? $sumber : null,
            'keterangan'        => $keterangan !== '' ? $keterangan : null,
            'is_publik'         => $this->request->getPost('is_publik') === '1' ? 1 : 0,
        ];
    }

    private function failForm(string $message, ?int $id = null)
    {
        return $this->formViewErrorResponse('admin/agenda_umum/form', $this->formData(
            $id === null ? 'Tambah Agenda Eksternal' : 'Edit Agenda Eksternal',
            $this->postedAgenda($id),
            $id === null
                ? base_url('admin/agenda-umum/store')
                : base_url("admin/agenda-umum/{$id}/update"),
        ), $message);
    }

    private function formData(string $title, ?array $agenda, string $actionUrl): array
    {
        return [
            'pageTitle'       => $title,
            'agenda'          => $agenda,
            'action_url'      => $actionUrl,
            'categories'      => AgendaUmumModel::CATEGORIES,
            'category_labels' => self::CATEGORY_LABELS,
        ];
    }

    private function postedAgenda(?int $id): array
    {
        return [
            'id'                => $id,
            'judul'             => trim((string) $this->request->getPost('judul')),
            'kategori'          => trim((string) $this->request->getPost('kategori')),
            'pihak_eksternal'   => trim((string) $this->request->getPost('pihak_eksternal')),
            'tanggal'           => trim((string) $this->request->getPost('tanggal')),
            'waktu_mulai'       => trim((string) $this->request->getPost('waktu_mulai')),
            'waktu_selesai'     => trim((string) $this->request->getPost('waktu_selesai')),
            'lokasi'            => trim((string) $this->request->getPost('lokasi')),
            'sumber_informasi'  => trim((string) $this->request->getPost('sumber_informasi')),
            'keterangan'        => trim((string) $this->request->getPost('keterangan')),
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
