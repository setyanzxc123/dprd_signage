<?php

declare(strict_types=1);

namespace Config;

use App\Models\UserModel;
use CodeIgniter\Shield\Config\Auth as ShieldAuth;

class Auth extends ShieldAuth
{
    /**
     * Aplikasi tidak membuka registrasi mandiri: akun admin dibuat lewat
     * seeder/command, akun anggota dibuat otomatis saat OTP pertama berhasil.
     */
    public bool $allowRegistration = false;

    public bool $allowMagicLinkLogins = false;

    /**
     * Kolom tambahan di tabel users di luar kolom bawaan Shield. Nama
     * tampilan admin disimpan di kolom `name` (dibuat oleh migration
     * AdoptShieldIdentity) dan turut diperiksa validator password.
     */
    public array $personalFields = ['name'];

    /**
     * Model identitas milik aplikasi (menambahkan kolom `name`).
     */
    public string $userProvider = UserModel::class;
}
