<?php

namespace App\Libraries\WhatsApp\Transport;

use App\Libraries\WhatsApp\Contracts\HttpTransportInterface;
use App\Libraries\WhatsApp\ValueObjects\HttpResponse;

final class CurlHttpTransport implements HttpTransportInterface
{
    public function post(string $url, array $headers, array $fields, int $timeoutSeconds): HttpResponse
    {
        if (! function_exists('curl_init')) {
            return new HttpResponse(0, null, 'Ekstensi cURL PHP belum tersedia.');
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_HTTPHEADER     => $this->formatHeaders($headers),
            CURLOPT_TIMEOUT        => max(1, $timeoutSeconds),
            CURLOPT_CONNECTTIMEOUT => min(10, max(1, $timeoutSeconds)),
        ]);

        $body = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return new HttpResponse(
            $statusCode,
            is_string($body) ? $body : null,
            $error !== '' ? $error : null,
        );
    }

    public function postJson(string $url, array $headers, array $payload, int $timeoutSeconds): HttpResponse
    {
        if (! function_exists('curl_init')) {
            return new HttpResponse(0, null, 'Ekstensi cURL PHP belum tersedia.');
        }

        $headers['Content-Type'] = 'application/json';
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER     => $this->formatHeaders($headers),
            CURLOPT_TIMEOUT        => max(1, $timeoutSeconds),
            CURLOPT_CONNECTTIMEOUT => min(10, max(1, $timeoutSeconds)),
        ]);

        $body = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return new HttpResponse($statusCode, is_string($body) ? $body : null, $error !== '' ? $error : null);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return list<string>
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $name => $value) {
            $formatted[] = $name . ': ' . $value;
        }

        return $formatted;
    }
}
