<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnggotaModel;
use App\Models\UnitRapatModel;

class MemberController extends BaseController
{
    private array $fraksiList = [
        'Golkar', 'PDI-P', 'Gerindra', 'PKB', 'NasDem',
        'PKS', 'Demokrat', 'PAN', 'PPP', 'Hanura',
    ];

    public function index(): string
    {
        $model   = new AnggotaModel();
        $q       = trim((string) ($this->request->getGet('q') ?? ''));
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = (int) ($this->request->getGet('per_page') ?? 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $applyFilters = static function ($builder) use ($q) {
            if ($q !== '') {
                $builder
                    ->groupStart()
                        ->like('name', $q)
                        ->orLike('jabatan', $q)
                        ->orLike('fraksi', $q)
                        ->orLike('komisi', $q)
                        ->orLike('no_wa', $q)
                    ->groupEnd();
            }

            return $builder;
        };

        $total = $applyFilters($model->builder())->countAllResults();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $members = $applyFilters($model->builder())
            ->orderBy('name', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return view('admin/anggota/index', [
            'pageTitle' => 'Anggota DPRD',
            'members'   => $members,
            'filters'   => [
                'q'        => $q,
                'per_page' => $perPage,
            ],
            'pagination' => [
                'page'       => $page,
                'perPage'    => $perPage,
                'total'      => $total,
                'totalPages' => $totalPages,
                'from'       => $total ? $offset + 1 : 0,
                'to'         => min($offset + $perPage, $total),
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
        $komisi = $this->request->getPost('komisi');
        $anggotaId = $model->insert([
            'name'    => $this->request->getPost('name'),
            'jabatan' => $this->request->getPost('jabatan'),
            'fraksi'  => $this->request->getPost('fraksi'),
            'komisi'  => $komisi,
            'no_wa'   => $this->request->getPost('no_wa'),
            'aktif'   => $this->request->getPost('aktif') ?? 1,
        ], true);

        if ($anggotaId) {
            $this->syncMemberUnits((int) $anggotaId, $komisi, $this->postedUnitIds());
        }

        session()->setFlashdata('success', 'Anggota berhasil ditambahkan.');
        return redirect()->to(base_url('admin/anggota'));
    }

    public function edit(int $id): string
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
        $komisi = $this->request->getPost('komisi');
        $model->update($id, [
            'name'    => $this->request->getPost('name'),
            'jabatan' => $this->request->getPost('jabatan'),
            'fraksi'  => $this->request->getPost('fraksi'),
            'komisi'  => $komisi,
            'no_wa'   => $this->request->getPost('no_wa'),
            'aktif'   => $this->request->getPost('aktif') ?? 1,
        ]);

        $this->syncMemberUnits($id, $komisi, $this->postedUnitIds());

        session()->setFlashdata('success', 'Data anggota berhasil diperbarui.');
        return redirect()->to(base_url('admin/anggota'));
    }

    public function delete(int $id)
    {
        $model = new AnggotaModel();
        $model->delete($id);

        session()->setFlashdata('success', 'Anggota berhasil dihapus.');
        return redirect()->to(base_url('admin/anggota'));
    }

    private function komisiOptions(string $selected = ''): array
    {
        $model = new UnitRapatModel();
        $units = $model
            ->where('aktif', 1)
            ->where('jenis', 'komisi')
            ->orderBy('urutan', 'ASC')
            ->orderBy('nama', 'ASC')
            ->findAll();

        $options = array_column($units, 'nama');

        if ($selected !== '' && !in_array($selected, $options, true)) {
            $options[] = $selected;
        }

        return $options;
    }

    private function manualUnitsOptions(): array
    {
        return (new UnitRapatModel())
            ->where('aktif', 1)
            ->where('membership_type', 'manual')
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

    private function syncMemberUnits(int $anggotaId, ?string $komisiName, array $manualUnitIds): void
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

        if (!empty($komisiName)) {
            $komisiUnit = (new UnitRapatModel())
                ->where('jenis', 'komisi')
                ->where('nama', trim($komisiName))
                ->first();

            if ($komisiUnit) {
                $rows[] = [
                    'anggota_id'    => $anggotaId,
                    'unit_rapat_id' => (int) $komisiUnit['id'],
                    'created_at'    => $now,
                ];
            }
        }

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
