<?php

namespace App\Libraries\WhatsApp\Contracts;

use App\Libraries\WhatsApp\ValueObjects\HttpResponse;

interface HttpTransportInterface
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $payload
     */
    public function postJson(string $url, array $headers, array $payload, int $timeoutSeconds): HttpResponse;

    /**
     * @param array<string, string> $headers
     */
    public function get(string $url, array $headers, int $timeoutSeconds): HttpResponse;
}
