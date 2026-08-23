<?php

namespace App\Libraries\Api;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Envelope respons JSON standar untuk seluruh endpoint API v1:
 * {status: success|error, ...data} atau {status: error, message, errors?}.
 */
trait ApiResponse
{
    protected function apiSuccess(array $data = [], int $status = 200): ResponseInterface
    {
        return service('response')
            ->setStatusCode($status)
            ->setHeader('Cache-Control', 'private, no-store')
            ->setJSON(['status' => 'success', ...$data]);
    }

    protected function apiError(string $message, int $status = 400, ?array $errors = null): ResponseInterface
    {
        $payload = ['status' => 'error', 'message' => $message];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return service('response')
            ->setStatusCode($status)
            ->setHeader('Cache-Control', 'private, no-store')
            ->setJSON($payload);
    }

    protected function apiUnauthorized(): ResponseInterface
    {
        return $this->apiError('Token tidak valid atau tidak disertakan.', 401);
    }

    protected function apiForbidden(): ResponseInterface
    {
        return $this->apiError('Anda tidak memiliki hak akses untuk aksi ini.', 403);
    }

    /**
     * Membaca input dari body JSON maupun form-encoded, agar klien
     * mobile bebas memilih format pengiriman.
     */
    protected function input(string $key): mixed
    {
        $body = (string) service('request')->getBody();

        if ($body !== '') {
            $json = json_decode($body, true);
            if (is_array($json) && array_key_exists($key, $json)) {
                return $json[$key];
            }
        }

        return service('request')->getPost($key);
    }
}
