<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SettingModel;

class PublicController extends BaseController
{
    public function index(): string
    {
        $settingModel = new SettingModel();
        $settings     = $settingModel->getAllAssoc();

        return view('publik/index', [
            'namaInstansi' => 'DPRD Provinsi Sulawesi Tengah',
            'logoUrl'      => base_url('assets/images/logo_dprd.jpg'),
            'apiUrl'       => base_url('api/v1/publik/jadwal'),
        ]);
    }
}
