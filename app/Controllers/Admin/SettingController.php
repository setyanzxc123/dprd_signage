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
        ];

        $settings = array_merge($defaults, $settings);
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
     * POST admin/pengaturan/wa-test
     * Kirim pesan WA test ke nomor yang diinput dari halaman pengaturan.
     * Hanya merespons AJAX (X-Requested-With: XMLHttpRequest).
     */
    public function waTest()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to(base_url('admin/pengaturan'));
        }

        $noWa = trim($this->request->getPost('no_wa') ?? '');

        if (empty($noWa) || ! preg_match('/^62\d{8,13}$/', $noWa)) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Nomor tidak valid. Gunakan format 628xxxxxxxxxx.',
            ]);
        }

        $wa      = new \App\Libraries\WhatsappService();
        $message = "✅ *Pesan Test DPRD Signage*\n\nIni adalah pesan uji coba dari sistem notifikasi DPRD.\nJika Anda menerima pesan ini, koneksi WhatsApp API berfungsi dengan baik.\n\n_Dikirim dari: Pengaturan Admin_";
        $result  = $wa->send($noWa, $message);

        return $this->response->setJSON($result);
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

        $settingModel = new SettingModel();
        $token = env('WA_API_KEY') ?: $settingModel->getValue('wa_api_key', '');

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
                'error'      => 'cURL error: ' . $curlErr,
            ]);
        }

        $decoded = json_decode($raw, true);
        $ok      = isset($decoded['status']) && $decoded['status'] === true;

        return $this->response->setJSON([
            'configured' => true,
            'connected'  => $ok,
            'error'      => $ok ? null : ($decoded['reason'] ?? 'Token tidak valid atau ditolak Fonnte'),
        ]);
    }
}
