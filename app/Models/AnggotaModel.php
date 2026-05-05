<?php

namespace App\Models;

use CodeIgniter\Model;

class AnggotaModel extends Model
{
    protected $table         = 'anggota';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'name', 'jabatan', 'fraksi', 'komisi', 'no_wa', 'aktif', 'foto',
    ];
    protected $useTimestamps = false;
    protected $returnType    = 'array';
}
