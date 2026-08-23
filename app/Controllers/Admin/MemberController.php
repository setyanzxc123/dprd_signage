<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Crud\AnggotaService;
use App\Libraries\Otp\OtpService;
use App\Models\AnggotaModel;

class MemberController extends BaseController
{
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
            'fraksi_list'        => AnggotaService::FRAKSI_LIST,
            'komisi_list'        => $this->komisiOptions(),
            'action_url'         => base_url('admin/anggota/store'),
        ]);
    }

    public function store()
    {
        $service = new AnggotaService();
        $input = $service->validatedInput($this->request->getPost());

        if (isset($input['error'])) {
            return $this->failForm($input['error']);
        }

        if ($service->create($input) < 1) {
            return $this->failForm('Gagal menyimpan anggota.');
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
            'fraksi_list'        => AnggotaService::FRAKSI_LIST,
            'komisi_list'        => $this->komisiOptions($member['komisi'] ?? ''),
            'action_url'         => base_url("admin/anggota/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $service = new AnggotaService();
        if (! (new AnggotaModel())->find($id)) {
            session()->setFlashdata('error', 'Anggota tidak ditemukan.');
            return redirect()->to(base_url('admin/anggota'));
        }

        $input = $service->validatedInput($this->request->getPost(), $id);

        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        if (! $service->update($id, $input)) {
            return $this->failForm('Gagal memperbarui anggota.', $id);
        }

        return $this->formSuccessResponse('Data anggota berhasil diperbarui.', base_url('admin/anggota'));
    }

    public function delete(int $id)
    {
        $outcome = (new AnggotaService())->delete($id);

        if ($outcome === 'missing') {
            session()->setFlashdata('error', 'Anggota tidak ditemukan.');
        } elseif ($outcome === 'deactivated') {
            session()->setFlashdata('success', 'Anggota sudah terkait data lain, sehingga hanya dinonaktifkan.');
        } else {
            session()->setFlashdata('success', 'Anggota berhasil dihapus.');
        }

        return redirect()->to(base_url('admin/anggota'));
    }

    public function emergencyOtp(int $id)
    {
        $member = (new AnggotaModel())->find($id);
        $admin = session()->get('auth_user');

        if ($member === null || ! is_array($admin) || empty($member['aktif'])) {
            session()->setFlashdata('error', 'Anggota tidak aktif atau tidak ditemukan.');
            return redirect()->to(base_url('admin/anggota'), 303);
        }

        try {
            $otp = (new OtpService())->createEmergency(
                (int) $member['id'],
                (int) $admin['id'],
            );
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
        $options = AnggotaService::KOMISI_LIST;

        if ($selected !== '' && !in_array($selected, $options, true)) {
            $options[] = $selected;
        }

        return $options;
    }

    private function failForm(string $message, ?int $id = null)
    {
        return $this->formViewErrorResponse('admin/anggota/form', [
            'pageTitle'         => $id === null ? 'Tambah Anggota' : 'Edit Anggota',
            'member'            => $this->postedMember($id),
            'fraksi_list'       => AnggotaService::FRAKSI_LIST,
            'komisi_list'       => $this->komisiOptions(trim((string) $this->request->getPost('komisi'))),
            'action_url'        => $id === null
                ? base_url('admin/anggota/store')
                : base_url("admin/anggota/{$id}/update"),
        ], $message);
    }

    private function postedMember(?int $id = null): array
    {
        $post = $this->request->getPost();

        return [
            'id'      => $id,
            'name'    => trim((string) ($post['name'] ?? '')),
            'jabatan' => trim((string) ($post['jabatan'] ?? '')),
            'fraksi'  => trim((string) ($post['fraksi'] ?? '')),
            'komisi'  => trim((string) ($post['komisi'] ?? '')),
            'no_wa'   => trim((string) ($post['no_wa'] ?? '')),
            'aktif'   => ($post['aktif'] ?? null) === '0' ? 0 : 1,
        ];
    }

    private function memberList(): array
    {
        return (new AnggotaModel())
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}
