<?php

namespace App\Controllers;

use App\Models\SettingModel;

class Home extends BaseController
{
    public function index(): string
    {
        $settingModel = new SettingModel();
        $settings     = $settingModel->getAllAssoc();

        $queryTema = $this->request->getGet('tema');
        $tema      = $queryTema && in_array($queryTema, ['dark', 'light'])
            ? $queryTema
            : ($settings['tema_signage'] ?? 'dark');

        $mediaFile = $settings['media_file'] ?? '';

        return view('signage/index', [
            'signageTema'      => $tema,
            'mediaMode'        => $settings['media_mode'] ?? 'video',
            'mediaUrl'         => $mediaFile ? base_url('uploads/media/' . $mediaFile) : '',
            'runningText'      => $settings['running_text'] ?? '',
            'runningTextAktif' => (bool) ($settings['running_text_aktif'] ?? false),
        ]);
    }
}
