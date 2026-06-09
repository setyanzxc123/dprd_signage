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
            'pageTitle'   => 'Tambah Anggota',
            'member'      => null,
            'fraksi_list' => $this->fraksiList,
            'komisi_list' => $this->komisiOptions(),
            'action_url'  => base_url('admin/anggota/store'),
        ]);
    }

    public function store()
    {
        $model = new AnggotaModel();
        $model->insert([
            'name'    => $this->request->getPost('name'),
            'jabatan' => $this->request->getPost('jabatan'),
            'fraksi'  => $this->request->getPost('fraksi'),
            'komisi'  => $this->request->getPost('komisi'),
            'no_wa'   => $this->request->getPost('no_wa'),
            'aktif'   => $this->request->getPost('aktif') ?? 1,
        ]);

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
            'pageTitle'   => 'Edit Anggota',
            'member'      => $member,
            'fraksi_list' => $this->fraksiList,
            'komisi_list' => $this->komisiOptions($member['komisi'] ?? ''),
            'action_url'  => base_url("admin/anggota/{$id}/update"),
        ]);
    }

    public function update(int $id)
    {
        $model = new AnggotaModel();
        $model->update($id, [
            'name'    => $this->request->getPost('name'),
            'jabatan' => $this->request->getPost('jabatan'),
            'fraksi'  => $this->request->getPost('fraksi'),
            'komisi'  => $this->request->getPost('komisi'),
            'no_wa'   => $this->request->getPost('no_wa'),
            'aktif'   => $this->request->getPost('aktif') ?? 1,
        ]);

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
}
