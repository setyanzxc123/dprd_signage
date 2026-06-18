<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\WhatsappService;
use App\Models\SettingModel;

class SettingController extends BaseController
{
    private const MEDIA_MAX_BYTES = 50 * 1024 * 1024;
    private const MEDIA_UPLOAD_DIR = 'uploads/media/';

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

        $requestSizeError = $this->validateRequestSize();
        if ($requestSizeError !== null) {
            return $this->failSave($requestSizeError);
        }

        $waTemplate = trim((string) ($this->request->getPost('wa_template_reminder') ?? ''));
        $waTemplate = $waTemplate !== '' ? $waTemplate : WhatsappService::defaultReminderTemplate();
        $unknownPlaceholders = WhatsappService::findUnknownPlaceholders($waTemplate);

        if (!empty($unknownPlaceholders)) {
            $labels = array_map(static fn ($key) => '{' . $key . '}', $unknownPlaceholders);
            $message = 'Template WA memuat placeholder tidak dikenal: ' . implode(', ', $labels);

            return $this->failSave($message, true);
        }

        $senderName = trim((string) ($this->request->getPost('wa_sender_name') ?? ''));
        $senderName = $senderName !== '' ? $senderName : 'Sekretariat DPRD';

        $mediaUpload = $this->validateMediaUpload();
        if ($mediaUpload['error'] !== null) {
            return $this->failSave($mediaUpload['error']);
        }

        $newMediaFile = '';
        $oldMediaFile = '';

        try {
            if ($mediaUpload['file'] !== null) {
                $uploadDir = FCPATH . self::MEDIA_UPLOAD_DIR;
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
                    return $this->failSave('Folder upload media tidak dapat dibuat.');
                }

                if (!is_writable($uploadDir)) {
                    return $this->failSave('Folder upload media tidak dapat ditulis.');
                }

                $oldMediaFile = (string) $settingModel->getValue('media_file', '');
                $newMediaFile = $mediaUpload['file']->getRandomName();
                $mediaUpload['file']->move($uploadDir, $newMediaFile);
            }

            $db = db_connect();
            $db->transStart();

            $settingModel->upsert('running_text',       $this->request->getPost('running_text') ?? '');
            $settingModel->upsert('running_text_aktif', $this->request->getPost('running_text_aktif') ? '1' : '0');
            $settingModel->upsert('media_mode',         $this->request->getPost('media_mode') ?? 'video');
            $settingModel->upsert('tema_signage',       $this->request->getPost('tema_signage') ?? 'dark');
            $settingModel->upsert('wa_sender_name', $senderName);
            $settingModel->upsert('wa_template_reminder', $waTemplate);
            $settingModel->upsert('wa_template_default_aktif', $this->request->getPost('wa_template_default_aktif') ? '1' : '0');

            if ($newMediaFile !== '') {
                $settingModel->upsert('media_file', $newMediaFile);
            }

            $db->transComplete();
            if (!$db->transStatus()) {
                throw new \RuntimeException('Transaksi database gagal.');
            }
        } catch (\Throwable $e) {
            $this->deleteMediaFile($newMediaFile);
            log_message('error', 'Gagal menyimpan pengaturan media: {message}', ['message' => $e->getMessage()]);

            return $this->failSave('Gagal menyimpan pengaturan. Pastikan folder upload dapat ditulis dan coba lagi.');
        }

        $this->deleteMediaFile($oldMediaFile);

        session()->setFlashdata('success', 'Pengaturan berhasil disimpan.');

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'   => 'success',
                'redirect' => base_url('admin/pengaturan'),
            ]);
        }

        return redirect()->to(base_url('admin/pengaturan'));
    }

    private function failSave(string $message, bool $withInput = false)
    {
        if ($this->request->isAJAX()) {
            return $this->ajaxError($message);
        }

        session()->setFlashdata('error', $message);
        $redirect = redirect()->to(base_url('admin/pengaturan'));

        return $withInput ? $redirect->withInput() : $redirect;
    }

    private function validateMediaUpload(): array
    {
        $file = $this->request->getFile('media_file');

        if (!$file) {
            return ['file' => null, 'error' => null];
        }

        $error = $file->getError();

        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['file' => null, 'error' => null];
        }

        if ($error !== UPLOAD_ERR_OK || !$file->isValid()) {
            return ['file' => null, 'error' => $this->uploadErrorMessage($error)];
        }

        if ($file->hasMoved()) {
            return ['file' => null, 'error' => 'File media sudah diproses sebelumnya. Silakan pilih file lagi.'];
        }

        $allowedTypes = ['video/mp4', 'video/webm', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes, true)) {
            return ['file' => null, 'error' => 'Format file tidak didukung. Gunakan MP4, WebM, JPG, PNG, atau WebP.'];
        }

        if ($file->getSize() > self::MEDIA_MAX_BYTES) {
            return ['file' => null, 'error' => 'Ukuran file melebihi batas 50MB.'];
        }

        return ['file' => $file, 'error' => null];
    }

    private function validateRequestSize(): ?string
    {
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength <= 0) {
            return null;
        }

        $postMaxBytes = $this->iniSizeToBytes((string) ini_get('post_max_size'));
        if ($postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            return 'Ukuran upload melebihi batas server (' . $this->formatBytes($postMaxBytes) . ').';
        }

        return null;
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas upload server.',
            UPLOAD_ERR_PARTIAL => 'Upload file tidak lengkap. Periksa koneksi lalu coba lagi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara upload tidak tersedia di server.',
            UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis file upload.',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP.',
            default => 'File media gagal diupload.',
        };
    }

    private function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower($value[strlen($value) - 1]);
        $bytes = (float) $value;

        return match ($unit) {
            'g' => (int) ($bytes * 1024 * 1024 * 1024),
            'm' => (int) ($bytes * 1024 * 1024),
            'k' => (int) ($bytes * 1024),
            default => (int) $bytes,
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 1) . 'MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . 'KB';
        }

        return $bytes . 'B';
    }

    private function ajaxError(string $message, int $statusCode = 422)
    {
        return $this->response->setStatusCode($statusCode)->setJSON([
            'status'  => 'error',
            'message' => $message,
            'csrf'    => [
                'name' => csrf_token(),
                'hash' => csrf_hash(),
            ],
        ]);
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

    private function deleteMediaFile(string $fileName): void
    {
        if ($fileName === '') {
            return;
        }

        $path = FCPATH . self::MEDIA_UPLOAD_DIR . basename($fileName);
        if (is_file($path) && !@unlink($path)) {
            log_message('warning', 'Gagal menghapus file media lama: {path}', ['path' => $path]);
        }
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

        // Lepaskan session lock sesegera mungkin agar request cURL tidak memblokir navigasi/request halaman lain
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
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
