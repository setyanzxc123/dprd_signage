<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Settings\Config\Settings as BaseSettings;
use CodeIgniter\Settings\Handlers\DatabaseHandler;

class Settings extends BaseSettings
{
    /**
     * Tabel `settings` milik aplikasi (key-value signage, dipakai
     * SettingModel) sudah ada lebih dulu, sehingga tabel handler paket
     * Settings diberi nama lain untuk menghindari tabrakan.
     */
    public $database = [
        'class'       => DatabaseHandler::class,
        'table'       => 'contextual_settings',
        'group'       => null,
        'writeable'   => true,
        'deferWrites' => false,
    ];
}
