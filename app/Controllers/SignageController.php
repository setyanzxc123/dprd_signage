<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SettingModel;

class SignageController extends BaseController
{
    public function index(): string
    {
        $settingModel = new SettingModel();
        $settings     = $settingModel->getAllAssoc();

        // Override tema dari query param jika dikirim (untuk preview dari halaman pengaturan)
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
            'signageWorkerVersion' => $this->signageWorkerVersion(),
        ]);
    }

    private function signageWorkerVersion(): int
    {
        $files = [
            FCPATH . 'signage-sw.js',
            FCPATH . 'assets/css/signage.css',
            FCPATH . 'assets/vendor/fonts/fonts.css',
            FCPATH . 'assets/vendor/vue/vue.global.prod.js',
            FCPATH . 'assets/vendor/qrcodejs/qrcode.min.js',
            FCPATH . 'assets/images/logo_dprd.jpg',
        ];
        $fontFiles = glob(FCPATH . 'assets/vendor/fonts/files/*.woff2');
        if (is_array($fontFiles)) {
            $files = [...$files, ...$fontFiles];
        }

        $latest = 1;
        foreach ($files as $file) {
            if (is_file($file)) {
                $latest = max($latest, (int) filemtime($file));
            }
        }

        return $latest;
    }
}
