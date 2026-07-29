<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendaUmumModel extends Model
{
    public const SOURCE = 'agenda_eksternal';

    public const CATEGORIES = [
        'audiensi',
        'demonstrasi',
        'kunjungan',
        'undangan',
        'kegiatan_sosial',
        'lainnya',
    ];

    protected $table         = 'agenda_umum';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'judul',
        'kategori',
        'pihak_eksternal',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'lokasi',
        'sumber_informasi',
        'keterangan',
        'is_publik',
    ];
}
