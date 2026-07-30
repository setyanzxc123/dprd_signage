<?php

namespace App\Models;

use CodeIgniter\Model;

class JadwalUmumModel extends Model
{
    public const SOURCE = 'jadwal_umum';

    protected $table         = 'jadwal_umum';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'judul',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'ruangan_id',
        'lokasi_lainnya',
        'pihak_eksternal',
        'is_publik',
        'keterangan',
    ];

    public static function resolveLifecycleStatus(
        string $tanggal,
        ?string $waktuMulai,
        ?string $waktuSelesai,
        ?int $now = null,
    ): string {
        $now ??= time();
        $today = date('Y-m-d', $now);

        if ($waktuMulai === null || trim($waktuMulai) === '') {
            return match (true) {
                $tanggal < $today => 'selesai',
                $tanggal > $today => 'menunggu',
                default           => 'berlangsung',
            };
        }

        $start = strtotime($tanggal . ' ' . $waktuMulai);
        if ($start === false) {
            return 'menunggu';
        }

        if ($waktuSelesai === null || trim($waktuSelesai) === '') {
            if ($tanggal < $today) {
                return 'selesai';
            }
            if ($start <= $now) {
                return 'berlangsung';
            }

            return $start - $now <= 1800 ? 'persiapan' : 'menunggu';
        }

        $end = strtotime($tanggal . ' ' . $waktuSelesai);
        if ($end === false || $end <= $start) {
            return 'menunggu';
        }
        if ($end <= $now) {
            return 'selesai';
        }
        if ($start <= $now) {
            return 'berlangsung';
        }

        return $start - $now <= 1800 ? 'persiapan' : 'menunggu';
    }

    public function hasRoomConflict(
        int $ruanganId,
        string $tanggal,
        string $waktuMulai,
        string $waktuSelesai,
        ?int $ignoreJadwalUmumId = null,
    ): bool {
        if ($ruanganId < 1) {
            return false;
        }

        $builder = $this->db->table($this->table)
            ->where('ruangan_id', $ruanganId)
            ->where('tanggal', $tanggal)
            ->where('waktu_mulai <', $waktuSelesai)
            ->where('waktu_selesai >', $waktuMulai);
        if ($ignoreJadwalUmumId !== null) {
            $builder->where('id !=', $ignoreJadwalUmumId);
        }
        if ($builder->countAllResults() > 0) {
            return true;
        }

        if ($this->db->tableExists('jadwal_banmus')) {
            $builder = $this->db->table('jadwal_banmus')
                ->where('ruangan_id', $ruanganId)
                ->where('tanggal', $tanggal)
                ->where('jenis_agenda', JadwalBanmusModel::TYPE_MEETING)
                ->whereIn('status', JadwalBanmusModel::SCHEDULED_STATUSES)
                ->where('deleted_at', null)
                ->where('jam_mulai <', $waktuSelesai)
                ->where('jam_selesai >', $waktuMulai);
            if ($builder->countAllResults() > 0) {
                return true;
            }
        }

        return false;
    }
}
