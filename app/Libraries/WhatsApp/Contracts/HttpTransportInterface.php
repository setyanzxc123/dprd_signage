<?php

namespace App\Libraries\WhatsApp\Contracts;

use App\Libraries\WhatsApp\ValueObjects\HttpResponse;

interface HttpTransportInterface
{
    /**
     * @param array<string, string> $headers
     * @param array<string, scalar> $fields
     */
    public function post(string $url, array $headers, array $fields, int $timeoutSeconds): HttpResponse;
}
