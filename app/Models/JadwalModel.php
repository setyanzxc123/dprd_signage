<?php

namespace App\Models;

use CodeIgniter\Model;

class JadwalModel extends Model
{
    protected $table         = 'jadwal';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'judul', 'keterangan', 'tanggal', 'waktu_mulai', 'waktu_selesai',
        'ruangan_id', 'komisi_target', 'blast_before', 'reminder_time',
        'status', 'materi_url', 'stream_url', 'is_publik', 'jenis',
    ];
    protected $useTimestamps = false;
    protected $returnType    = 'array';
}
