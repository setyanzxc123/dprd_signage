<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Media\MediaUploadException;
use App\Libraries\Media\PostChunkMediaUpload;
use App\Libraries\Otp\Providers\BaileysProvider;
use App\Models\SettingModel;
use Config\Otp as OtpConfig;

class SettingController extends BaseController
{
    private const MEDIA_MAX_BYTES = PostChunkMediaUpload::MAX_BYTES;
    private const MEDIA_UPLOAD_DIR = 'uploads/media/';
    private const MEDIA_UPLOAD_SESSION_KEY = 'media_chunk_token';

    public function index(): string
    {
        $settingModel = new SettingModel();
        $settings     = $settingModel->getAllAssoc();

        $defaults = [
            'tema_signage'       => 'dark',
            'running_text'       => '',
            'running_text_aktif' => '0',
            'media_mode'         => 'video',
            'media_file'         => '',
        ];

        $settings = array_merge($defaults, $settings);
        $settings['running_text_aktif'] = (bool) $settings['running_text_aktif'];
        $uploadToken = $this->mediaUploadToken();

        $otpConfig = new OtpConfig();
        $baileysProvider = new BaileysProvider(config: $otpConfig);
        $whatsappStatus = $baileysProvider->getStatus();

        return view('admin/pengaturan/index', [
            'pageTitle'        => 'Pengaturan Sistem',
            'settings'         => $settings,
            'mediaUploadToken' => $uploadToken,
            'mediaUploadMax'   => PostChunkMediaUpload::MAX_BYTES,
            'mediaChunkSize'   => PostChunkMediaUpload::CHUNK_BYTES,
            'whatsapp'         => $whatsappStatus,
            'otpConfig'        => $otpConfig,
        ]);
    }

    public function save()
    {
        $settingModel = new SettingModel();

        $requestSizeError = $this->validateRequestSize();
        if ($requestSizeError !== null) {
            return $this->failSave($requestSizeError);
        }

        $chunkUploadId = trim((string) $this->request->getPost('media_upload_key'));
        $mediaUpload = $chunkUploadId === '' ? $this->validateMediaUpload() : ['file' => null, 'error' => null];
        if ($mediaUpload['error'] !== null) {
            return $this->failSave($mediaUpload['error']);
        }

        $newMediaFile = '';
        $oldMediaFile = '';

        try {
            $oldMediaFile = (string) $settingModel->getValue('media_file', '');

            if ($chunkUploadId !== '') {
                $newMediaFile = $this->mediaUploader()->consume($this->mediaUploadToken(), $chunkUploadId);
            } elseif ($mediaUpload['file'] !== null) {
                $uploadDir = FCPATH . self::MEDIA_UPLOAD_DIR;
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
                    return $this->failSave('Folder upload media tidak dapat dibuat.');
                }

                if (!is_writable($uploadDir)) {
                    return $this->failSave('Folder upload media tidak dapat ditulis.');
                }

                $newMediaFile = $mediaUpload['file']->getRandomName();
                $mediaUpload['file']->move($uploadDir, $newMediaFile);
            }

            $db = db_connect();
            $db->transStart();

            $settingModel->upsert('running_text',       $this->request->getPost('running_text') ?? '');
            $settingModel->upsert('running_text_aktif', $this->request->getPost('running_text_aktif') ? '1' : '0');
            $settingModel->upsert('media_mode',         $this->request->getPost('media_mode') ?? 'video');
            $settingModel->upsert('tema_signage',       $this->request->getPost('tema_signage') ?? 'dark');

            if ($newMediaFile !== '') {
                $settingModel->upsert('media_file', $newMediaFile);
            }

            $db->transComplete();
            if (!$db->transStatus()) {
                throw new \RuntimeException('Transaksi database gagal.');
            }
        } catch (MediaUploadException $e) {
            $this->deleteMediaFile($newMediaFile);
            log_message('warning', 'Gagal menyelesaikan upload media: {message}', ['message' => $e->getMessage()]);

            return $this->failSave($e->getMessage());
        } catch (\Throwable $e) {
            $this->deleteMediaFile($newMediaFile);
            log_message('error', 'Gagal menyimpan pengaturan media: {message}', ['message' => $e->getMessage()]);

            return $this->failSave('Gagal menyimpan pengaturan. Pastikan folder upload dapat ditulis dan coba lagi.');
        }

        if ($newMediaFile !== '' && $newMediaFile !== $oldMediaFile) {
            $this->deleteMediaFile($oldMediaFile);
        }

        session()->setFlashdata('success', 'Pengaturan berhasil disimpan.');

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'   => 'success',
                'redirect' => base_url('admin/pengaturan'),
            ]);
        }

        return redirect()->to(base_url('admin/pengaturan'));
    }

    public function startMediaUpload()
    {
        try {
            $payload = $this->mediaUploader()->start(
                $this->validatedRequestUploadToken(),
                trim((string) $this->request->getPost('client_key')),
                (string) $this->request->getPost('file_name'),
                (int) $this->request->getPost('file_size'),
                (string) $this->request->getPost('file_type')
            );

            return $this->response->setJSON(['status' => 'success'] + $payload);
        } catch (MediaUploadException $e) {
            return $this->mediaUploadError($e);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal memulai upload media: {message}', ['message' => $e->getMessage()]);

            return $this->ajaxError('Server gagal menyiapkan upload media.', 500);
        }
    }

    public function uploadMediaChunk()
    {
        try {
            $chunk = $this->request->getFile('chunk');
            if ($chunk === null) {
                throw new MediaUploadException('Chunk upload tidak ditemukan.');
            }

            $payload = $this->mediaUploader()->append(
                $this->validatedRequestUploadToken(),
                trim((string) $this->request->getPost('upload_id')),
                (int) $this->request->getPost('offset'),
                trim((string) $this->request->getPost('checksum')),
                $chunk
            );

            return $this->response->setJSON(['status' => 'success'] + $payload);
        } catch (MediaUploadException $e) {
            return $this->mediaUploadError($e);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menerima chunk media: {message}', ['message' => $e->getMessage()]);

            return $this->ajaxError('Server gagal menerima bagian file.', 500);
        }
    }

    public function cancelMediaUpload()
    {
        try {
            $this->mediaUploader()->cancel(
                $this->validatedRequestUploadToken(),
                trim((string) $this->request->getPost('upload_id'))
            );

            return $this->response->setJSON(['status' => 'success']);
        } catch (MediaUploadException $e) {
            return $this->mediaUploadError($e);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membatalkan upload media: {message}', ['message' => $e->getMessage()]);

            return $this->ajaxError('Server gagal membatalkan upload.', 500);
        }
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
            return ['file' => null, 'error' => 'Ukuran file melebihi batas 200 MB.'];
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

    private function mediaUploader(): PostChunkMediaUpload
    {
        return new PostChunkMediaUpload();
    }

    private function mediaUploadToken(): string
    {
        $token = (string) session()->get(self::MEDIA_UPLOAD_SESSION_KEY);
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $token = bin2hex(random_bytes(32));
            session()->set(self::MEDIA_UPLOAD_SESSION_KEY, $token);
        }

        return $token;
    }

    private function validatedRequestUploadToken(): string
    {
        $requestToken = trim((string) $this->request->getPost('upload_token'));
        $sessionToken = $this->mediaUploadToken();
        if (!hash_equals($sessionToken, $requestToken)) {
            throw new MediaUploadException('Sesi upload tidak valid. Muat ulang halaman.', 403);
        }

        return $sessionToken;
    }

    private function mediaUploadError(MediaUploadException $exception)
    {
        $statusCode = $exception->getStatusCode();
        if (!in_array($statusCode, [400, 403, 404, 409, 413, 422, 500, 503], true)) {
            $statusCode = 422;
        }

        return $this->ajaxError($exception->getMessage(), $statusCode);
    }

    public function deleteMedia()
    {
        $settingModel = new SettingModel();
        $fileAktif    = $settingModel->getValue('media_file');

        if ($fileAktif) {
            $path = FCPATH . self::MEDIA_UPLOAD_DIR . basename((string) $fileAktif);
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

    public function whatsappStatus()
    {
        $otpConfig = new OtpConfig();
        $provider = new BaileysProvider(config: $otpConfig);
        $status = $provider->getStatus();

        return $this->response->setJSON([
            'status'     => 'success',
            'provider'   => $otpConfig->provider,
            'fallback'   => $otpConfig->fazpassFallbackEnabled,
            'gateway'    => $status,
        ]);
    }
}
