<?php

namespace App\Libraries\Crud;

use App\Models\RuanganModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Logika CRUD ruangan rapat. Dipakai bersama oleh controller web dan
 * API: input diterima sebagai array polos (bukan object Request) dan
 * hasil validasi memakai konvensi ['error' => pesan] yang sudah ada.
 */
class RuanganService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function validatedInput(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));

        if ($name === '') {
            return ['error' => 'Nama ruangan wajib diisi.'];
        }

        if (mb_strlen($name) > 150) {
            return ['error' => 'Nama ruangan maksimal 150 karakter.'];
        }

        $kapasitasRaw = $input['kapasitas'] ?? null;
        if ($kapasitasRaw === null || ! ctype_digit((string) $kapasitasRaw) || (int) $kapasitasRaw < 1) {
            return ['error' => 'Kapasitas ruangan wajib minimal 1 orang.'];
        }

        return [
            'name'       => $name,
            'keterangan' => trim((string) ($input['keterangan'] ?? '')),
            'kapasitas'  => (int) $kapasitasRaw,
            'tersedia'   => ($input['tersedia'] ?? null) === '0' ? 0 : 1,
        ];
    }

    /**
     * @param array<string, mixed> $normalized hasil validatedInput tanpa kunci error
     */
    public function create(array $normalized): int
    {
        return (int) (new RuanganModel())->insert($normalized, true);
    }

    /**
     * @param array<string, mixed> $normalized
     */
    public function update(int $id, array $normalized): bool
    {
        return (new RuanganModel())->update($id, $normalized);
    }

    /**
     * Menghapus ruangan, atau sekadar menonaktifkan bila ruangan pernah
     * dipakai jadwal agar data historis tetap utuh.
     *
     * @return 'missing'|'deactivated'|'deleted'
     */
    public function delete(int $id): string
    {
        $model = new RuanganModel();

        if ($model->find($id) === null) {
            return 'missing';
        }

        if ($this->hasSchedules($id)) {
            $model->update($id, ['tersedia' => 0]);

            return 'deactivated';
        }

        $model->delete($id);

        return 'deleted';
    }

    public function hasSchedules(int $roomId): bool
    {
        foreach (['jadwal_umum', 'jadwal_banmus'] as $table) {
            if ($this->db->tableExists($table)
                && $this->db->table($table)->where('ruangan_id', $roomId)->countAllResults() > 0) {
                return true;
            }
        }

        return false;
    }
}
