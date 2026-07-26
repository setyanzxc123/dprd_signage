<?php

namespace App\Libraries\Otp\Contracts;

use App\Libraries\Otp\ValueObjects\OtpDeliveryResult;

interface OtpDeliveryInterface
{
    public function send(string $phone, string $code, int $ttlSeconds): OtpDeliveryResult;
}
