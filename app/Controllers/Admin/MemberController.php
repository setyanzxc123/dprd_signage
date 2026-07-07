<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnggotaModel;
use App\Models\UnitRapatModel;

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
        $members = (new AnggotaModel())
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('admin/anggota/index', [
            'pageTitle' => 'Anggota DPRD',
            'members'   => $members,
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
            'manual_units'       => $this->manualUnitsOptions(),
            'selected_unit_ids'  => [],
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

        $anggotaId = $model->insert([
            'name'    => $input['name'],
            'jabatan' => $input['jabatan'],
            'fraksi'  => $input['fraksi'],
            'komisi'  => $input['komisi'],
            'no_wa'   => $input['no_wa'],
            'aktif'   => $input['aktif'],
        ], true);

        if ($anggotaId) {
            $this->syncMemberUnits((int) $anggotaId, $input['unit_ids']);
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
            'manual_units'       => $this->manualUnitsOptions(),
            'selected_unit_ids'  => $this->selectedUnitIds($id),
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

        $input = $this->validatedInput();

        if (isset($input['error'])) {
            return $this->failForm($input['error'], $id);
        }

        $model->update($id, [
            'name'    => $input['name'],
            'jabatan' => $input['jabatan'],
            'fraksi'  => $input['fraksi'],
            'komisi'  => $input['komisi'],
            'no_wa'   => $input['no_wa'],
            'aktif'   => $input['aktif'],
        ]);

        $this->syncMemberUnits($id, $input['unit_ids']);

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

    private function komisiOptions(string $selected = ''): array
    {
        $options = $this->komisiList;

        if ($selected !== '' && !in_array($selected, $options, true)) {
            $options[] = $selected;
        }

        return $options;
    }

    private function manualUnitsOptions(): array
    {
        return (new UnitRapatModel())
            ->where('aktif', 1)
            ->orderBy('urutan', 'ASC')
            ->orderBy('nama', 'ASC')
            ->findAll();
    }

    private function selectedUnitIds(int $anggotaId): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('anggota_unit_rapat')) {
            return [];
        }

        $rows = $db->table('anggota_unit_rapat')
            ->select('unit_rapat_id')
            ->where('anggota_id', $anggotaId)
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'unit_rapat_id'));
    }

    private function postedUnitIds(): array
    {
        $ids = $this->request->getPost('manual_units') ?? [];
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

        return array_values(array_unique($ids));
    }

    private function validatedInput(): array
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

        $unitIds = $this->postedUnitIds();
        if (! empty($unitIds) && ! empty($this->invalidActiveUnitIds($unitIds))) {
            return ['error' => 'Kelompok peserta yang dipilih tidak valid atau sudah nonaktif.'];
        }

        return [
            'name'     => $name,
            'jabatan'  => trim((string) $this->request->getPost('jabatan')),
            'fraksi'   => $fraksi,
            'komisi'   => trim((string) $this->request->getPost('komisi')),
            'no_wa'    => $phone,
            'aktif'    => $this->request->getPost('aktif') === '0' ? 0 : 1,
            'unit_ids' => $unitIds,
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

    private function invalidActiveUnitIds(array $unitIds): array
    {
        $rows = (new UnitRapatModel())
            ->select('id')
            ->where('aktif', 1)
            ->whereIn('id', $unitIds)
            ->findAll();

        $validIds = array_map('intval', array_column($rows, 'id'));

        return array_values(array_diff($unitIds, $validIds));
    }

    private function memberHasRelations(int $anggotaId): bool
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('anggota_unit_rapat')
            && $db->table('anggota_unit_rapat')->where('anggota_id', $anggotaId)->countAllResults() > 0) {
            return true;
        }

        return $db->tableExists('notifikasi')
            && $db->table('notifikasi')->where('anggota_id', $anggotaId)->countAllResults() > 0;
    }

    private function failForm(string $message, ?int $id = null)
    {
        return $this->formViewErrorResponse('admin/anggota/form', [
            'pageTitle'         => $id === null ? 'Tambah Anggota' : 'Edit Anggota',
            'member'            => $this->postedMember($id),
            'fraksi_list'       => $this->fraksiList,
            'komisi_list'       => $this->komisiOptions(trim((string) $this->request->getPost('komisi'))),
            'manual_units'      => $this->manualUnitsOptions(),
            'selected_unit_ids' => $this->postedUnitIds(),
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

    private function syncMemberUnits(int $anggotaId, array $manualUnitIds): void
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('anggota_unit_rapat')) {
            return;
        }

        $db->table('anggota_unit_rapat')
            ->where('anggota_id', $anggotaId)
            ->delete();

        $now = date('Y-m-d H:i:s');
        $rows = [];

        foreach ($manualUnitIds as $unitId) {
            $rows[] = [
                'anggota_id'    => $anggotaId,
                'unit_rapat_id' => $unitId,
                'created_at'    => $now,
            ];
        }

        if (!empty($rows)) {
            $db->table('anggota_unit_rapat')->insertBatch($rows);
        }
    }
}
