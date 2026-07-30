<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Media\ResumableMediaUpload;
use App\Models\SettingModel;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SettingController extends BaseController
{
    private const MEDIA_MAX_BYTES = ResumableMediaUpload::MAX_BYTES;
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
        ];

        $settings = array_merge($defaults, $settings);
        $settings['running_text_aktif'] = (bool) $settings['running_text_aktif'];
        $uploadToken = (string) session()->get('media_upload_token');
        if (preg_match('/^[a-f0-9]{64}$/', $uploadToken) !== 1) {
            $uploadToken = bin2hex(random_bytes(32));
            session()->set('media_upload_token', $uploadToken);
        }

        return view('admin/pengaturan/index', [
            'pageTitle'           => 'Pengaturan Sistem',
            'settings'            => $settings,
            // Keep the TUS endpoint same-origin. An absolute URL derived from a
            // stale/proxied baseURL can downgrade HTTPS to HTTP and be blocked
            // by CSP before the request reaches the server.
            'mediaUploadEndpoint' => ResumableMediaUpload::API_PATH,
            'mediaUploadToken'    => $uploadToken,
            'mediaMaxBytes'       => ResumableMediaUpload::MAX_BYTES,
            'mediaChunkBytes'     => ResumableMediaUpload::CHUNK_BYTES,
        ]);
    }

    public function save()
    {
        $settingModel = new SettingModel();

        $requestSizeError = $this->validateRequestSize();
        if ($requestSizeError !== null) {
            return $this->failSave($requestSizeError);
        }

        $uploadKey = trim((string) $this->request->getPost('media_upload_key'));
        $mediaUpload = $uploadKey === ''
            ? $this->validateMediaUpload()
            : ['file' => null, 'error' => null];

        $newMediaFile = '';
        $oldMediaFile = '';

        try {
            if ($uploadKey !== '') {
                $oldMediaFile = (string) $settingModel->getValue('media_file', '');
                $completedUpload = (new ResumableMediaUpload())->consumeCompletedUpload(
                    $uploadKey,
                    (string) session()->get('media_upload_token'),
                );
                $newMediaFile = $completedUpload['file_name'];
            } elseif ($mediaUpload['error'] !== null) {
                return $this->failSave($mediaUpload['error']);
            } elseif ($mediaUpload['file'] !== null) {
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

    public function tus(?string $uploadKey = null)
    {
        $method = strtoupper($this->request->getMethod());
        if ($method !== 'OPTIONS') {
            $expectedToken = (string) session()->get('media_upload_token');
            $providedToken = $this->request->getHeaderLine('X-Media-Upload-Token');
            if ($expectedToken === ''
                || $providedToken === ''
                || ! hash_equals($expectedToken, $providedToken)) {
                return $this->response
                    ->setStatusCode(403)
                    ->setHeader('Tus-Resumable', '1.0.0')
                    ->setJSON(['message' => 'Token upload media tidak valid.']);
            }
        }

        $originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $isTunneledPatch = $method === 'POST'
            && $uploadKey !== null
            && $this->request->getHeaderLine('Upload-Offset') !== '';
        if ($isTunneledPatch) {
            // Nginx/WAF only sees an ordinary POST. tus-php is instantiated
            // afterward and may safely interpret this authenticated chunk as PATCH.
            $_SERVER['REQUEST_METHOD'] = 'PATCH';
        }

        try {
            $tusResponse = (new ResumableMediaUpload())->serve();

            return $this->fromSymfonyResponse($tusResponse);
        } catch (\Throwable $exception) {
            log_message('error', 'Endpoint Tus media gagal: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return $this->response
                ->setStatusCode(500)
                ->setHeader('Tus-Resumable', '1.0.0')
                ->setBody('');
        } finally {
            if ($isTunneledPatch) {
                if ($originalRequestMethod === null) {
                    unset($_SERVER['REQUEST_METHOD']);
                } else {
                    $_SERVER['REQUEST_METHOD'] = $originalRequestMethod;
                }
            }
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

    private function fromSymfonyResponse(SymfonyResponse $source)
    {
        $this->response->setStatusCode($source->getStatusCode());
        foreach ($source->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            if (strcasecmp($name, 'Location') === 0) {
                $values = array_map(
                    fn (string $location): string => $this->sameOriginTusLocation($location),
                    $values,
                );
            }
            $this->response->setHeader($name, implode(', ', $values));
        }

        $body = $source->getContent();
        $this->response->setBody(is_string($body) ? $body : '');

        return $this->response;
    }

    private function sameOriginTusLocation(string $location): string
    {
        $path = parse_url($location, PHP_URL_PATH);
        if (! is_string($path)
            || ! str_starts_with($path, ResumableMediaUpload::API_PATH . '/')) {
            return $location;
        }

        $origin = rtrim($this->request->getHeaderLine('Origin'), '/');
        $originScheme = strtolower((string) parse_url($origin, PHP_URL_SCHEME));
        $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
        $requestHost = strtolower((string) parse_url(
            'http://' . $this->request->getHeaderLine('Host'),
            PHP_URL_HOST,
        ));

        if (in_array($originScheme, ['http', 'https'], true)
            && $originHost !== ''
            && hash_equals($requestHost, $originHost)) {
            $query = parse_url($location, PHP_URL_QUERY);

            return $origin . $path
                . (is_string($query) && $query !== '' ? '?' . $query : '');
        }

        return $location;
    }
}
