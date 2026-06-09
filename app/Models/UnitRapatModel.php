<?php

namespace App\Models;

use CodeIgniter\Model;

class UnitRapatModel extends Model
{
    protected $table         = 'unit_rapat';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'jenis', 'membership_type', 'aktif', 'urutan'];
    protected $useTimestamps = true;
    protected $returnType    = 'array';
}
