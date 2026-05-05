<?php

namespace App\Models;

use CodeIgniter\Model;

class NotifikasiModel extends Model
{
    protected $table         = 'notifikasi';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'jadwal_id', 'anggota_id', 'no_wa', 'status', 'executed_at',
    ];
    protected $useTimestamps = false;
    protected $returnType    = 'array';
}
