<?php

namespace App\Libraries\WhatsApp\ValueObjects;

final class HttpResponse
{
    public function __construct(
        public readonly int $statusCode,
        public readonly ?string $body,
        public readonly ?string $error = null,
    ) {
    }
}
