<?php

namespace App\Libraries;

final class PhoneNumberService
{
    public static function normalizeIndonesia(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($phone, '62')) return $phone;
        if (str_starts_with($phone, '08')) return '62' . substr($phone, 1);
        if (str_starts_with($phone, '8')) return '62' . $phone;

        return $phone;
    }

    public static function isValidIndonesia(string $phone): bool
    {
        return preg_match('/^628\d{7,12}$/', $phone) === 1;
    }
}
