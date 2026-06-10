<?php

namespace App\Models;

use CodeIgniter\Model;

class JadwalModel extends Model
{
    protected $table         = 'jadwal';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'judul', 'keterangan', 'tanggal', 'waktu_mulai', 'waktu_selesai',
        'ruangan_id', 'lokasi_lainnya', 'blast_before', 'reminder_time',
        'status', 'materi_url', 'stream_url', 'is_publik', 'jenis',
    ];
    protected $useTimestamps = false;
    protected $returnType    = 'array';

    /**
     * Otomatis perbarui status semua rapat berdasarkan tanggal dan waktu saat ini.
     * Kategori:
     * - 'selesai'    : Jika tanggal sudah lewat, atau tanggal hari ini dan waktu_selesai sudah terlewati.
     * - 'berlangsung': Jika tanggal hari ini dan waktu saat ini di antara waktu_mulai dan waktu_selesai.
     * - 'persiapan'  : Jika tanggal hari ini dan waktu saat ini berada dalam 30 menit sebelum waktu_mulai.
     * - 'menunggu'   : Jika tanggal hari ini tetapi waktu saat ini masih lebih dari 30 menit sebelum waktu_mulai, atau tanggal di masa depan.
     */
    public function autoUpdateStatuses(): void
    {
        $today = date('Y-m-d');
        $nowTime = date('H:i:s');

        // 1. Selesai
        $this->db->query("
            UPDATE {$this->table}
            SET status = 'selesai'
            WHERE (tanggal < ? OR (tanggal = ? AND waktu_selesai <= ?))
              AND status != 'selesai'
        ", [$today, $today, $nowTime]);

        // 2. Berlangsung
        $this->db->query("
            UPDATE {$this->table}
            SET status = 'berlangsung'
            WHERE tanggal = ?
              AND waktu_mulai <= ?
              AND waktu_selesai > ?
              AND status != 'berlangsung'
        ", [$today, $nowTime, $nowTime]);

        // 3. Persiapan
        $this->db->query("
            UPDATE {$this->table}
            SET status = 'persiapan'
            WHERE tanggal = ?
              AND waktu_mulai - INTERVAL 30 MINUTE <= ?
              AND waktu_mulai > ?
              AND status != 'persiapan'
        ", [$today, $nowTime, $nowTime]);

        // 4. Menunggu
        $this->db->query("
            UPDATE {$this->table}
            SET status = 'menunggu'
            WHERE (tanggal > ? OR (tanggal = ? AND waktu_mulai - INTERVAL 30 MINUTE > ?))
              AND status != 'menunggu'
        ", [$today, $today, $nowTime]);
    }
}
