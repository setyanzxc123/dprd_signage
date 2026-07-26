<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Otp\OtpService;
use App\Models\AnggotaModel;
use App\Models\MemberAccountModel;

class MemberController extends BaseController
{
    private array $fraksiList = [
        'Amanat Nasional',
        'Bulan Bintang',
        'Demokrat',
        'Gerindra',
        'Golongan Karya',
        'Hanura',
        'Keadilan Sejahtra',
        'PDIP',
        'Persatuan Indonesia',
        'Persatuan Pembangunan',
    ];

    private array $komisiList = [
        'Komisi I',
        'Komisi II',
        'Komisi III',
        'Komisi IV',
    ];

    public function index(): string
    {
        return view('admin/anggota/index', [
            'pageTitle' => 'Anggota DPRD',
            'members'   => $this->memberList(),
            'data_scope' => [
                'label' => 'seluruh master anggota',
            ],
        ]);
    }

    public function create(): string
    {
        return view('admin/anggota/form', [
            'pageTitle'          => 'Tambah Anggota',
            'member'             => null,
            'fraksi_list'        => $this->fraksiList,
            'komisi_list'        => $this->komisiOptions(),
            'action_url'         => base_url('admin/anggota/store'),
        ]);
    }

    public function store()
    {
        $model = new AnggotaModel();
        $input = $this->validatedInput();

        if (isset($input['error'])) {
            return $this->failForm($input['error']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $memberId = (int) $model->insert([
            'name'    => $input['name'],
            'jabatan' => $input['jabatan'],
            'fraksi'  => $input['fraksi'],
            'komisi'  => $input['komisi'],
            'no_wa'   => $input['no_wa'],
            'aktif'   => $input['aktif'],
        ], true);

        $this->syncMemberAccount($memberId, $input);
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->failForm('Gagal menyimpan anggota dan akun login.');
        }

        return $this->formSuccessResponse('Anggota berhasil ditambahkan.', base_url('admin/anggota'));
    }

    public function edit(int $id)
    {
        $model  = new AnggotaModel();
        $member = $model->find($id);

        if (!$member) {
            session()->setFlashdata('error', 'Anggota tidak ditemukan.');
            return redirect()->to(base_url('admin/anggota'));
        }

        return view('admin/anggota/form', [
            'pageTitle'          => 'Edit Anggota',
            'member'             => $member,
            'fraksi_list'        => $this->fraksiList,
            'komisi_list'        => $this->komisiOptions($member['komisi'] ?? ''),
            'action_url'         => base_url("admin/anggota/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $model = new AnggotaModel();
        if (! $model->find($id)) {
            session()->setFlashdata('error', 'Anggota tidak ditemukan.');
            return redirect()->to(base_url('admin/anggota'));
        }

        $input = $this->validatedInput($id);

        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $model->update($id, [
            'name'    => $input['name'],
            'jabatan' => $input['jabatan'],
            'fraksi'  => $input['fraksi'],
            'komisi'  => $input['komisi'],
            'no_wa'   => $input['no_wa'],
            'aktif'   => $input['aktif'],
        ]);

        $this->syncMemberAccount($id, $input);
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->failForm('Gagal memperbarui anggota dan akun login.', $id);
        }

        return $this->formSuccessResponse('Data anggota berhasil diperbarui.', base_url('admin/anggota'));
    }

    public function delete(int $id)
    {
        $model = new AnggotaModel();
        if (! $model->find($id)) {
            session()->setFlashdata('error', 'Anggota tidak ditemukan.');
            return redirect()->to(base_url('admin/anggota'));
        }

        if ($this->memberHasRelations($id)) {
            $model->update($id, ['aktif' => 0]);

            session()->setFlashdata('success', 'Anggota sudah terkait data lain, sehingga hanya dinonaktifkan.');
            return redirect()->to(base_url('admin/anggota'));
        }

        $model->delete($id);

        session()->setFlashdata('success', 'Anggota berhasil dihapus.');
        return redirect()->to(base_url('admin/anggota'));
    }

    public function emergencyOtp(int $id)
    {
        $account = (new MemberAccountModel())->findByAnggotaId($id);
        $member = (new AnggotaModel())->find($id);
        $admin = session()->get('auth_user');
        $reason = trim((string) $this->request->getPost('reason'));

        if ($account === null || $member === null || ! is_array($admin) || empty($member['aktif'])) {
            session()->setFlashdata('error', 'Akun anggota tidak aktif atau tidak ditemukan.');
            return redirect()->to(base_url('admin/anggota'), 303);
        }

        try {
            $otp = (new OtpService())->createEmergency((int) $account['id'], (int) $admin['id'], $reason);
        } catch (\InvalidArgumentException $exception) {
            session()->setFlashdata('error', $exception->getMessage());
            return redirect()->to(base_url('admin/anggota'), 303);
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store, private')
            ->setBody(view('admin/anggota/index', [
            'pageTitle' => 'Anggota DPRD',
            'members'   => $this->memberList(),
            'data_scope' => ['label' => 'seluruh master anggota'],
            'emergency_otp' => [
                'member'     => $member['name'],
                'code'       => $otp->code,
                'expires_at' => $otp->expiresAt,
            ],
        ]));
    }

    private function komisiOptions(string $selected = ''): array
    {
        $options = $this->komisiList;

        if ($selected !== '' && !in_array($selected, $options, true)) {
            $options[] = $selected;
        }

        return $options;
    }

    private function validatedInput(?int $memberId = null): array
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return ['error' => 'Nama anggota wajib diisi.'];
        }

        $fraksi = trim((string) $this->request->getPost('fraksi'));
        if ($fraksi === '') {
            return ['error' => 'Fraksi wajib dipilih.'];
        }

        if (! in_array($fraksi, $this->fraksiList, true)) {
            return ['error' => 'Fraksi yang dipilih tidak valid.'];
        }

        $phone = $this->normalizedPhone((string) $this->request->getPost('no_wa'));
        if ($phone === null) {
            return ['error' => 'Nomor WhatsApp wajib valid. Gunakan format 8123456789.'];
        }

        if ($this->phoneExists($phone, $memberId)) {
            return ['error' => 'Nomor WhatsApp sudah digunakan oleh anggota lain.'];
        }

        return [
            'name'     => $name,
            'jabatan'  => trim((string) $this->request->getPost('jabatan')),
            'fraksi'   => $fraksi,
            'komisi'   => trim((string) $this->request->getPost('komisi')),
            'no_wa'    => $phone,
            'aktif'    => $this->request->getPost('aktif') === '0' ? 0 : 1,
        ];
    }

    private function normalizedPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! preg_match('/^8\d{7,12}$/', $digits)) {
            return null;
        }

        return $digits;
    }
    private function memberHasRelations(int $anggotaId): bool
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('anggota_unit_rapat')
            && $db->table('anggota_unit_rapat')->where('anggota_id', $anggotaId)->countAllResults() > 0) {
            return true;
        }

        return false;
    }

    private function failForm(string $message, ?int $id = null)
    {
        return $this->formViewErrorResponse('admin/anggota/form', [
            'pageTitle'         => $id === null ? 'Tambah Anggota' : 'Edit Anggota',
            'member'            => $this->postedMember($id),
            'fraksi_list'       => $this->fraksiList,
            'komisi_list'       => $this->komisiOptions(trim((string) $this->request->getPost('komisi'))),
            'action_url'        => $id === null
                ? base_url('admin/anggota/store')
                : base_url("admin/anggota/{$id}/update"),
        ], $message);
    }

    private function postedMember(?int $id = null): array
    {
        return [
            'id'      => $id,
            'name'    => trim((string) $this->request->getPost('name')),
            'jabatan' => trim((string) $this->request->getPost('jabatan')),
            'fraksi'  => trim((string) $this->request->getPost('fraksi')),
            'komisi'  => trim((string) $this->request->getPost('komisi')),
            'no_wa'   => trim((string) $this->request->getPost('no_wa')),
            'aktif'   => $this->request->getPost('aktif') === '0' ? 0 : 1,
        ];
    }

    private function memberList(): array
    {
        return (new AnggotaModel())
            ->select('anggota.*, ma.last_login_at')
            ->join('member_accounts ma', 'ma.anggota_id = anggota.id', 'left')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    private function phoneExists(string $phone, ?int $ignoreMemberId): bool
    {
        $model = new AnggotaModel();
        $model->where('no_wa', $phone);

        if ($ignoreMemberId !== null) {
            $model->where('id !=', $ignoreMemberId);
        }

        return $model->first() !== null;
    }

    private function syncMemberAccount(int $memberId, array $input): void
    {
        $model = new MemberAccountModel();
        $account = $model->findByAnggotaId($memberId);
        $payload = ['anggota_id' => $memberId];

        if ($account === null) {
            $model->insert($payload);
        }
    }

}
