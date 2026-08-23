<?php

namespace App\Libraries\Crud;

use App\Models\AnggotaModel;
use App\Models\UnitRapatModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Logika CRUD kelompok peserta (unit rapat) beserta keanggotaannya.
 * Dipakai bersama oleh controller web dan API dengan konvensi input
 * array polos dan hasil validasi ['error' => pesan].
 */
class UnitRapatService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function validatedInput(array $input, ?int $unitId = null): array
    {
        $nama = trim((string) ($input['nama'] ?? ''));
        $aktif = ! empty($input['aktif']) ? 1 : 0;

        if ($nama === '') {
            return ['error' => 'Nama kelompok peserta wajib diisi.'];
        }

        if (mb_strlen($nama) > 150) {
            return ['error' => 'Nama kelompok peserta maksimal 150 karakter.'];
        }

        if ($this->nameExists($nama, $unitId)) {
            return ['error' => 'Nama kelompok peserta sudah digunakan.'];
        }

        $anggotaIds = $this->normalizeAnggotaIds($input['anggota_unit_rapat'] ?? []);

        if ($aktif === 1 && $anggotaIds === []) {
            return ['error' => 'Kelompok peserta aktif wajib memiliki minimal satu anggota.'];
        }

        if ($anggotaIds !== [] && $this->invalidActiveAnggotaIds($anggotaIds) !== []) {
            return ['error' => 'Anggota yang dipilih tidak valid atau sudah nonaktif.'];
        }

        return [
            'payload'     => ['nama' => $nama, 'aktif' => $aktif],
            'anggota_ids' => $anggotaIds,
        ];
    }

    /**
     * @param array<string, mixed> $validated hasil validatedInput tanpa kunci error
     */
    public function create(array $validated): int
    {
        $unitId = (int) (new UnitRapatModel())->insert($validated['payload'], true);
        $this->syncMembers($unitId, $validated['anggota_ids']);

        return $unitId;
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function update(int $unitId, array $validated): void
    {
        (new UnitRapatModel())->update($unitId, $validated['payload']);
        $this->syncMembers($unitId, $validated['anggota_ids']);
    }

    /**
     * Unit rapat tidak pernah dihapus fisik, hanya dinonaktifkan, agar
     * relasi historis pada jadwal tetap bermakna.
     */
    public function deactivate(int $unitId): void
    {
        (new UnitRapatModel())->update($unitId, ['aktif' => 0]);
    }

    /** @return list<int> */
    public function memberIdsForUnit(int $unitId): array
    {
        if (! $this->db->tableExists('anggota_unit_rapat')) {
            return [];
        }

        $rows = $this->db->table('anggota_unit_rapat')
            ->select('anggota_id')
            ->where('unit_rapat_id', $unitId)
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'anggota_id'));
    }

    /** @return list<int> */
    private function normalizeAnggotaIds(mixed $ids): array
    {
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

        return array_values(array_unique($ids));
    }

    private function nameExists(string $nama, ?int $ignoreId): bool
    {
        $model = new UnitRapatModel();
        $model->where('nama', $nama);

        if ($ignoreId !== null) {
            $model->where('id !=', $ignoreId);
        }

        return $model->first() !== null;
    }

    /** @param list<int> $anggotaIds @return list<int> */
    private function invalidActiveAnggotaIds(array $anggotaIds): array
    {
        $rows = (new AnggotaModel())
            ->select('id')
            ->where('aktif', 1)
            ->whereIn('id', $anggotaIds)
            ->findAll();

        $validIds = array_map('intval', array_column($rows, 'id'));

        return array_values(array_diff($anggotaIds, $validIds));
    }

    /** @param list<int> $anggotaIds */
    private function syncMembers(int $unitId, array $anggotaIds): void
    {
        if (! $this->db->tableExists('anggota_unit_rapat')) {
            return;
        }

        $this->db->table('anggota_unit_rapat')
            ->where('unit_rapat_id', $unitId)
            ->delete();

        if ($anggotaIds === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $rows = array_map(static fn (int $anggotaId): array => [
            'anggota_id'    => $anggotaId,
            'unit_rapat_id' => $unitId,
            'created_at'    => $now,
        ], $anggotaIds);

        $this->db->table('anggota_unit_rapat')->insertBatch($rows);
    }
}
