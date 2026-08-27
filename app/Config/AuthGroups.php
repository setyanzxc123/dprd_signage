<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * Grup bawaan saat akun baru dibuat tanpa grup eksplisit. Anggota
     * dipilih sebagai default paling aman (hak baca).
     */
    public string $defaultGroup = 'anggota';

    /**
     * Grup pengguna aplikasi: admin DPRD (superadmin/operator) dan
     * anggota dewan yang masuk lewat OTP WhatsApp.
     */
    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Kontrol penuh atas aplikasi.',
        ],
        'operator' => [
            'title'       => 'Operator',
            'description' => 'Admin operasional harian.',
        ],
        'anggota' => [
            'title'       => 'Anggota DPRD',
            'description' => 'Anggota dewan yang mengakses agenda pribadi.',
        ],
    ];

    /**
     * Permission domain aplikasi. Nama permission dipakai sebagai scope
     * access token API sekaligus dasar gating endpoint tulis.
     */
    public array $permissions = [
        'agenda.read'       => 'Melihat agenda/jadwal terkait anggota',
        'agenda.manage'     => 'Mengelola jadwal banmus dan jadwal umum',
        'anggota.manage'    => 'Mengelola data anggota',
        'ruangan.manage'    => 'Mengelola ruangan rapat',
        'unit-rapat.manage' => 'Mengelola unit rapat',
        'otp.emergency'     => 'Menerbitkan OTP darurat untuk anggota',
        'pengaturan.manage' => 'Mengelola pengaturan dan media signage',
        'resource.read'     => 'Mengakses materi, stream, undangan, dan dokumen SK',
        'notulen.manage'    => 'Mengelola rekaman rapat, transkripsi AI, dan risalah',
    ];

    /**
     * Matriks hak per grup. Operator sengaja setara superadmin agar
     * perilaku web tidak berubah; penerapan gating per endpoint
     * dilakukan bersama API tulis.
     */
    public array $matrix = [
        'superadmin' => [
            'agenda.read',
            'agenda.manage',
            'anggota.manage',
            'ruangan.manage',
            'unit-rapat.manage',
            'otp.emergency',
            'pengaturan.manage',
            'resource.read',
            'notulen.manage',
        ],
        'operator' => [
            'agenda.read',
            'agenda.manage',
            'anggota.manage',
            'ruangan.manage',
            'unit-rapat.manage',
            'otp.emergency',
            'pengaturan.manage',
            'resource.read',
            'notulen.manage',
        ],
        'anggota' => [
            'agenda.read',
            'resource.read',
        ],
    ];
}
