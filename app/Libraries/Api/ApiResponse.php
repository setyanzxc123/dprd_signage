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
        $body = $this->requestBodyArray();

        return $body[$key] ?? service('request')->getPost($key);
    }

    /**
     * Seluruh body permintaan sebagai array — untuk diteruskan ke
     * service CRUD. Nilai skalar dinormalisasi menjadi string agar
     * semantik validasi identik dengan form web (mis. kapasitas int
     * dari JSON tetap lolos ctype_digit).
     *
     * @return array<string, mixed>
     */
    protected function requestBodyArray(): array
    {
        $request = service('request');
        $body = (string) $request->getBody();

        if ($body !== '') {
            $json = json_decode($body, true);
            if (is_array($json) && $json !== []) {
                return $this->stringifyScalars($json);
            }

            // Method selain POST (mis. PUT/DELETE) membawa data form di
            // body, bukan di $_POST.
            $parsed = [];
            parse_str($body, $parsed);
            if ($parsed !== []) {
                return $this->stringifyScalars($parsed);
            }
        }

        return $this->stringifyScalars($request->getPost() ?? []);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function stringifyScalars(array $values): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->stringifyScalars($value);
            } elseif (is_bool($value)) {
                $result[$key] = $value ? '1' : '';
            } elseif (is_scalar($value)) {
                $result[$key] = (string) $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
