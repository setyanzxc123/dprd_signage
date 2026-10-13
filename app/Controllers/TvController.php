<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use CodeIgniter\HTTP\RedirectResponse;

class TvController extends BaseController
{
    public function index(): string
    {
        $settingModel = new SettingModel();
        $settings     = $settingModel->getAllAssoc();

        $queryTema = $this->request->getGet('tema');
        $tema      = $queryTema && in_array($queryTema, ['dark', 'light'], true)
            ? $queryTema
            : ($settings['tema_signage'] ?? 'dark');

        $mediaFile = $settings['media_file'] ?? '';

        return view('signage/index', [
            'signageTema'          => $tema,
            'mediaMode'            => $settings['media_mode'] ?? 'video',
            'mediaUrl'             => $mediaFile ? base_url('uploads/media/' . $mediaFile) : '',
            'runningText'          => $settings['running_text'] ?? '',
            'runningTextAktif'     => (bool) ($settings['running_text_aktif'] ?? false),
            'signageWorkerVersion' => $this->signageWorkerVersion(),
        ]);
    }

    public function redirectSignage(): RedirectResponse
    {
        $queryString = $this->request->getUri()->getQuery();
        $target = base_url('tv' . ($queryString !== '' ? '?' . $queryString : ''));

        return redirect()->to($target, 301);
    }

    private function signageWorkerVersion(): int
    {
        $files = [
            FCPATH . 'signage-sw.js',
            FCPATH . 'assets/css/signage.css',
            FCPATH . 'assets/vendor/fonts/fonts.css',
            FCPATH . 'assets/vendor/vue/vue.global.prod.js',
            FCPATH . 'assets/vendor/qrcodejs/qrcode.min.js',
            FCPATH . 'assets/images/logo_dprd.png',
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
