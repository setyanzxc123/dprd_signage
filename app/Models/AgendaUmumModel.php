<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendaUmumModel extends Model
{
    protected $table         = 'agenda_umum';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'judul',
        'kategori',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'lokasi',
        'sumber_informasi',
        'perkiraan_peserta',
        'keterangan',
        'status',
        'is_publik',
    ];
}
