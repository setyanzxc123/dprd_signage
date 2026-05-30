<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;

class SettingController extends BaseController
{
    public function index(): string
    {
        $settingModel = new SettingModel();
        $settings     = $settingModel->getAllAssoc();

        // Pastikan semua key yang dibutuhkan view tersedia dengan default
        $defaults = [
            'tema_signage'       => 'dark',
            'running_text'       => '',
            'running_text_aktif' => '0',
            'media_mode'         => 'video',
            'media_file'         => '',
            'bmkg_adm4'          => '72.71.01.1004',
        ];

        $settings = array_merge($defaults, $settings);
        if (env('BMKG_ADM4')) {
            $settings['bmkg_adm4'] = env('BMKG_ADM4');
        }
        $settings['running_text_aktif'] = (bool) $settings['running_text_aktif'];

        return view('admin/pengaturan/index', [
            'pageTitle' => 'Pengaturan Signage',
            'settings'  => $settings,
        ]);
    }

    public function save()
    {
        $settingModel = new SettingModel();

        // Simpan pengaturan teks dan tema
        $settingModel->upsert('running_text',       $this->request->getPost('running_text') ?? '');
        $settingModel->upsert('running_text_aktif', $this->request->getPost('running_text_aktif') ? '1' : '0');
        $settingModel->upsert('media_mode',         $this->request->getPost('media_mode') ?? 'video');
        $settingModel->upsert('tema_signage',       $this->request->getPost('tema_signage') ?? 'dark');
        $settingModel->upsert('bmkg_adm4',          trim($this->request->getPost('bmkg_adm4') ?? '72.71.01.1004'));

        // Hapus cache cuaca BMKG jika kode wilayah diubah
        $cacheFile = WRITEPATH . 'cache/bmkg_cuaca.json';
        if (file_exists($cacheFile)) { @unlink($cacheFile); }

        // Proses upload file media jika ada
        $file = $this->request->getFile('media_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $allowedTypes = ['video/mp4', 'video/webm', 'image/jpeg', 'image/png', 'image/webp'];
            $maxSize      = 50 * 1024 * 1024; // 50MB

            if (!in_array($file->getMimeType(), $allowedTypes)) {
                session()->setFlashdata('error', 'Format file tidak didukung. Gunakan MP4, WebM, JPG, PNG, atau WebP.');
                return redirect()->to(base_url('admin/pengaturan'));
            }

            if ($file->getSize() > $maxSize) {
                session()->setFlashdata('error', 'Ukuran file melebihi batas 50MB.');
                return redirect()->to(base_url('admin/pengaturan'));
            }

            // Hapus file lama
            $fileAktif = $settingModel->getValue('media_file');
            if ($fileAktif) {
                $pathLama = FCPATH . 'uploads/media/' . $fileAktif;
                if (file_exists($pathLama)) {
                    unlink($pathLama);
                }
            }

            // Simpan file baru
            $namaFile = $file->getRandomName();
            $file->move(FCPATH . 'uploads/media/', $namaFile);
            $settingModel->upsert('media_file', $namaFile);
        }

        session()->setFlashdata('success', 'Pengaturan berhasil disimpan.');
        return redirect()->to(base_url('admin/pengaturan'));
    }

    public function deleteMedia()
    {
        $settingModel = new SettingModel();
        $fileAktif    = $settingModel->getValue('media_file');

        if ($fileAktif) {
            $path = FCPATH . 'uploads/media/' . $fileAktif;
            if (file_exists($path)) {
                unlink($path);
            }
            $settingModel->upsert('media_file', '');
        }

        session()->setFlashdata('success', 'File media berhasil dihapus.');
        return redirect()->to(base_url('admin/pengaturan'));
    }
}
