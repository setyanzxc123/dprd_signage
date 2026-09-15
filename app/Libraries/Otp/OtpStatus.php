<?php

namespace App\Libraries\Otp;

final class OtpStatus
{
    public const CREATED = 'created';
    public const PENDING = 'pending';
    public const SENT = 'sent';
    public const DELIVERED = 'delivered';
    public const MANUAL = 'manual';
    public const VERIFIED = 'verified';
    public const FAILED = 'failed';
    public const EXPIRED = 'expired';
    public const CANCELLED = 'cancelled';

    /** @var list<string> */
    public const ACTIVE = [
        self::CREATED,
        self::PENDING,
        self::SENT,
        self::DELIVERED,
        self::MANUAL,
    ];

    /** @var list<string> */
    public const VERIFIABLE = [
        self::PENDING,
        self::SENT,
        self::DELIVERED,
        self::MANUAL,
    ];

    /** @var list<string> */
    public const TERMINAL = [
        self::VERIFIED,
        self::FAILED,
        self::EXPIRED,
        self::CANCELLED,
    ];

    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        self::CREATED   => [self::PENDING, self::FAILED, self::CANCELLED],
        self::PENDING   => [self::SENT, self::DELIVERED, self::VERIFIED, self::FAILED, self::EXPIRED, self::CANCELLED],
        self::SENT      => [self::DELIVERED, self::VERIFIED, self::FAILED, self::EXPIRED, self::CANCELLED],
        self::DELIVERED => [self::VERIFIED, self::EXPIRED, self::CANCELLED],
        self::MANUAL    => [self::VERIFIED, self::EXPIRED, self::CANCELLED],
        self::VERIFIED  => [],
        self::FAILED    => [],
        self::EXPIRED   => [],
        self::CANCELLED => [],
    ];

    public static function isKnown(string $status): bool
    {
        return array_key_exists($status, self::TRANSITIONS);
    }

    public static function canTransition(string $from, string $to): bool
    {
        return $from === $to
            ? self::isKnown($from)
            : in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * @return list<string>
     */
    public static function sourcesFor(string $target): array
    {
        $sources = [];
        foreach (self::TRANSITIONS as $from => $allowed) {
            if ($from === $target || in_array($target, $allowed, true)) {
                $sources[] = $from;
            }
        }

        return $sources;
    }
}
