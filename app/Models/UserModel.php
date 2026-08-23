<?php

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

class UserModel extends ShieldUserModel
{
    protected function initialize(): void
    {
        parent::initialize();

        // Kolom nama tampilan admin (lihat migration AdoptShieldIdentity).
        $this->allowedFields[] = 'name';
    }
}
