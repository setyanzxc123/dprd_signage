<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthToken as ShieldAuthToken;

class AuthToken extends ShieldAuthToken
{
    // Nilai bawaan Shield dipakai: token dikirim melalui header
    // `Authorization: Bearer <token>`. Masa berlaku token belum pernah
    // dipakai mengikuti `unusedTokenLifetime` (satu tahun); pencabutan
    // token dilakukan eksplisit lewat endpoint logout API.
}
