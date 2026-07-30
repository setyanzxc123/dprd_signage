<?php

namespace App\Libraries\Schedule;

final class ScheduleResourceAccess
{
    public const PUBLIC = 'publik';
    public const MEMBER = 'anggota';
    public const PARTICIPANT = 'peserta';
    public const VALUES = [self::PUBLIC, self::MEMBER, self::PARTICIPANT];

    public static function normalize(mixed $value, string $default): string
    {
        $value = trim((string) $value);

        return in_array($value, self::VALUES, true) ? $value : $default;
    }

    public static function canView(string $access, bool $isMember, bool $isParticipant): bool
    {
        return match ($access) {
            self::PUBLIC      => true,
            self::MEMBER      => $isMember,
            self::PARTICIPANT => $isMember && $isParticipant,
            default           => false,
        };
    }

    public static function label(string $access): string
    {
        return match ($access) {
            self::PUBLIC      => 'Publik',
            self::MEMBER      => 'Anggota DPRD',
            self::PARTICIPANT => 'Peserta',
            default           => 'Terbatas',
        };
    }
}
