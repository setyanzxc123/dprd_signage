<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\WhatsappService;
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
            'wa_from_env'        => env('WA_API_KEY') ? '1' : '0',
            'wa_sender_name'     => 'Sekretariat DPRD',
            'wa_template_reminder' => WhatsappService::defaultReminderTemplate(),
            'wa_template_default_aktif' => '1',
        ];

        $settings = array_merge($defaults, $settings);
        $settings['running_text_aktif'] = (bool) $settings['running_text_aktif'];
        $settings['wa_from_env']        = (bool) $settings['wa_from_env'];
        $settings['wa_template_default_aktif'] = (bool) $settings['wa_template_default_aktif'];

        return view('admin/pengaturan/index', [
            'pageTitle'      => 'Pengaturan Sistem',
            'settings'       => $settings,
            'waPlaceholders' => WhatsappService::templatePlaceholders(),
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

        $waTemplate = trim((string) ($this->request->getPost('wa_template_reminder') ?? ''));
        $waTemplate = $waTemplate !== '' ? $waTemplate : WhatsappService::defaultReminderTemplate();
        $unknownPlaceholders = WhatsappService::findUnknownPlaceholders($waTemplate);

        if (!empty($unknownPlaceholders)) {
            $labels = array_map(static fn ($key) => '{' . $key . '}', $unknownPlaceholders);
            session()->setFlashdata('error', 'Template WA memuat placeholder tidak dikenal: ' . implode(', ', $labels));
            return redirect()->to(base_url('admin/pengaturan'))->withInput();
        }

        $senderName = trim((string) ($this->request->getPost('wa_sender_name') ?? ''));
        $senderName = $senderName !== '' ? $senderName : 'Sekretariat DPRD';

        $settingModel->upsert('wa_sender_name', $senderName);
        $settingModel->upsert('wa_template_reminder', $waTemplate);
        $settingModel->upsert('wa_template_default_aktif', $this->request->getPost('wa_template_default_aktif') ? '1' : '0');

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



    /**
     * GET admin/pengaturan/wa-status
     * Cek apakah token Fonnte valid dengan melakukan request ringan ke API.
     * Hanya merespons AJAX.
     */
    public function waStatus()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to(base_url('admin/pengaturan'));
        }

        $token = env('WA_API_KEY') ?: '';

        if (empty($token)) {
            return $this->response->setJSON([
                'configured' => false,
                'connected'  => false,
            ]);
        }

        // Cek ke endpoint devices Fonnte untuk validasi token
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.fonnte.com/device',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: ' . $token],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $raw      = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return $this->response->setJSON([
                'configured' => true,
                'connected'  => false,
                'error'      => 'Gagal menghubungi server WhatsApp.',
            ]);
        }

        $decoded = json_decode($raw, true);
        $ok      = isset($decoded['status']) && $decoded['status'] === true;

        $error = null;
        if (!$ok) {
            $reason = strtolower($decoded['reason'] ?? '');
            if (str_contains($reason, 'token')) {
                $error = 'Autentikasi layanan gagal.';
            } elseif (str_contains($reason, 'device') || str_contains($reason, 'disconnect')) {
                $error = 'Perangkat WhatsApp pengirim tidak terhubung.';
            } else {
                $error = $decoded['reason'] ?? 'Gagal menghubungkan ke layanan WhatsApp.';
            }
        }

        return $this->response->setJSON([
            'configured' => true,
            'connected'  => $ok,
            'error'      => $error,
        ]);
    }
}
