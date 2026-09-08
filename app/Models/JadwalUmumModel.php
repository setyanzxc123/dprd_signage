<?php

namespace App\Models;

use CodeIgniter\Model;

class JadwalUmumModel extends Model
{
    public const SOURCE = 'jadwal_umum';

    public const TYPE_MEETING = 'rapat';
    public const TYPE_NON_MEETING = 'non_rapat';
    public const AGENDA_TYPES = [self::TYPE_MEETING, self::TYPE_NON_MEETING];

    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_PERSIAPAN = 'persiapan';
    public const STATUS_BERLANGSUNG = 'berlangsung';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DITUNDA = 'ditunda';
    public const STATUS_DIBATALKAN = 'dibatalkan';
    public const STATUS_NON_RAPAT = 'non_rapat';

    public const SCHEDULED_STATUSES = ['menunggu', 'persiapan', 'berlangsung', 'selesai'];
    public const MANUAL_STATUSES = ['ditunda', 'dibatalkan'];

    protected $table         = 'jadwal_umum';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'judul',
        'jenis_agenda',
        'tanggal',
        'tanggal_mulai',
        'tanggal_selesai',
        'waktu_mulai',
        'waktu_selesai',
        'ruangan_id',
        'lokasi_lainnya',
        'pihak_eksternal',
        'is_publik',
        'keterangan',
        'materi_url',
        'materi_akses',
        'stream_url',
        'stream_akses',
        'undangan_file',
        'undangan_nama_asli',
        'status',
    ];

    public function autoUpdateStatuses(?int $now = null): void
    {
        if (! $this->db->tableExists($this->table) || ! $this->db->fieldExists('status', $this->table)) {
            return;
        }

        $now ??= time();
        $builder = $this->db->table($this->table)
            ->select('id, tanggal, waktu_mulai, waktu_selesai, status, jenis_agenda')
            ->whereIn('status', self::SCHEDULED_STATUSES);
        if ($this->db->fieldExists('jenis_agenda', $this->table)) {
            $builder->where('jenis_agenda', self::TYPE_MEETING);
        }

        foreach ($builder->get()->getResultArray() as $item) {
            $status = self::resolveLifecycleStatus(
                (string) $item['tanggal'],
                $item['waktu_mulai'],
                $item['waktu_selesai'],
                $now,
                (string) ($item['jenis_agenda'] ?? self::TYPE_MEETING),
            );
            if ($status !== $item['status']) {
                $this->update((int) $item['id'], ['status' => $status]);
            }
        }
    }

    public static function resolveLifecycleStatus(
        string $tanggal,
        ?string $waktuMulai,
        ?string $waktuSelesai,
        ?int $now = null,
        string $jenisAgenda = self::TYPE_MEETING,
        ?string $manualStatus = null,
    ): string {
        if ($jenisAgenda === self::TYPE_NON_MEETING) {
            return self::STATUS_NON_RAPAT;
        }

        if ($manualStatus !== null && in_array($manualStatus, self::MANUAL_STATUSES, true)) {
            return $manualStatus;
        }

        $now ??= time();
        $today = date('Y-m-d', $now);

        if ($waktuMulai === null || trim($waktuMulai) === '') {
            return match (true) {
                $tanggal < $today => self::STATUS_SELESAI,
                $tanggal > $today => self::STATUS_MENUNGGU,
                default           => self::STATUS_BERLANGSUNG,
            };
        }

        $start = strtotime($tanggal . ' ' . $waktuMulai);
        if ($start === false) {
            return self::STATUS_MENUNGGU;
        }

        if ($waktuSelesai === null || trim($waktuSelesai) === '') {
            if ($tanggal < $today) {
                return self::STATUS_SELESAI;
            }
            if ($start <= $now) {
                return self::STATUS_BERLANGSUNG;
            }

            return $start - $now <= 1800 ? self::STATUS_PERSIAPAN : self::STATUS_MENUNGGU;
        }

        $end = strtotime($tanggal . ' ' . $waktuSelesai);
        if ($end === false || $end <= $start) {
            return self::STATUS_MENUNGGU;
        }
        if ($end <= $now) {
            return self::STATUS_SELESAI;
        }
        if ($start <= $now) {
            return self::STATUS_BERLANGSUNG;
        }

        return $start - $now <= 1800 ? self::STATUS_PERSIAPAN : self::STATUS_MENUNGGU;
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
        if ($this->db->fieldExists('status', $this->table)) {
            $builder->whereNotIn('status', ['dibatalkan', 'non_rapat']);
        }
        if ($this->db->fieldExists('jenis_agenda', $this->table)) {
            $builder->where('jenis_agenda', self::TYPE_MEETING);
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
