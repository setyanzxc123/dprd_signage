<?php

namespace App\Libraries\Crud;

use App\Models\AnggotaModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Logika CRUD anggota DPRD. Dipakai bersama oleh controller web dan
 * API dengan konvensi input array polos dan hasil validasi
 * ['error' => pesan].
 */
class AnggotaService
{
    private BaseConnection $db;

    /** Daftar fraksi resmi; menjadi dasar validasi pilihan fraksi. */
    public const FRAKSI_LIST = [
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

    /** Daftar komisi resmi; nilai lain yang tersimpan tetap diizinkan. */
    public const KOMISI_LIST = [
        'Komisi I',
        'Komisi II',
        'Komisi III',
        'Komisi IV',
    ];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function validatedInput(array $input, ?int $memberId = null): array
    {
        $name = trim((string) ($input['name'] ?? ''));

        if ($name === '') {
            return ['error' => 'Nama anggota wajib diisi.'];
        }

        $fraksi = trim((string) ($input['fraksi'] ?? ''));

        if ($fraksi === '') {
            return ['error' => 'Fraksi wajib dipilih.'];
        }

        if (! in_array($fraksi, self::FRAKSI_LIST, true)) {
            return ['error' => 'Fraksi yang dipilih tidak valid.'];
        }

        $phone = $this->normalizedPhone((string) ($input['no_wa'] ?? ''));

        if ($phone === null) {
            return ['error' => 'Nomor WhatsApp wajib valid. Gunakan format 8123456789.'];
        }

        if ($this->phoneExists($phone, $memberId)) {
            return ['error' => 'Nomor WhatsApp sudah digunakan oleh anggota lain.'];
        }

        return [
            'name'    => $name,
            'jabatan' => trim((string) ($input['jabatan'] ?? '')),
            'fraksi'  => $fraksi,
            'komisi'  => trim((string) ($input['komisi'] ?? '')),
            'no_wa'   => $phone,
            'aktif'   => ($input['aktif'] ?? null) === '0' ? 0 : 1,
        ];
    }

    /**
     * @param array<string, mixed> $normalized hasil validatedInput tanpa kunci error
     */
    public function create(array $normalized): int
    {
        $this->db->transStart();
        $memberId = (int) (new AnggotaModel())->insert($normalized, true);
        $this->db->transComplete();

        return $this->db->transStatus() ? $memberId : 0;
    }

    /**
     * @param array<string, mixed> $normalized
     */
    public function update(int $memberId, array $normalized): bool
    {
        $this->db->transStart();
        (new AnggotaModel())->update($memberId, $normalized);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Menghapus anggota, atau sekadar menonaktifkan bila anggota masih
     * terkait keanggotaan unit rapat.
     *
     * @return 'missing'|'deactivated'|'deleted'
     */
    public function delete(int $memberId): string
    {
        $model = new AnggotaModel();

        if ($model->find($memberId) === null) {
            return 'missing';
        }

        if ($this->hasRelations($memberId)) {
            $model->update($memberId, ['aktif' => 0]);

            return 'deactivated';
        }

        $model->delete($memberId);

        return 'deleted';
    }

    public function hasRelations(int $memberId): bool
    {
        return $this->db->tableExists('anggota_unit_rapat')
            && $this->db->table('anggota_unit_rapat')->where('anggota_id', $memberId)->countAllResults() > 0;
    }

    private function normalizedPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! preg_match('/^8\d{7,11}$/', $digits)) {
            return null;
        }

        return $digits;
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
}
