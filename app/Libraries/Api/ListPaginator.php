<?php

namespace App\Libraries\Api;

/**
 * Parameter standar endpoint list API: ?page=&per_page=&q=.
 * Kontrak: page mulai dari 1, per_page 1-100 (default 20), q pencarian
 * teks bebas yang maknanya ditentukan tiap modul.
 */
final class ListPaginator
{
    public const MAX_PER_PAGE = 100;
    public const DEFAULT_PER_PAGE = 20;

    public readonly int $page;
    public readonly int $perPage;
    public readonly string $search;

    public function __construct(?int $page = null, ?int $perPage = null, ?string $search = null)
    {
        $this->page = max(1, $page ?? 1);
        $this->perPage = min(self::MAX_PER_PAGE, max(1, $perPage ?? self::DEFAULT_PER_PAGE));
        $this->search = trim((string) $search);
    }

    public static function fromRequest(): self
    {
        $get = service('request')->getGet();

        return new self(
            (int) ($get['page'] ?? 1),
            (int) ($get['per_page'] ?? self::DEFAULT_PER_PAGE),
            (string) ($get['q'] ?? ''),
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function hasSearch(): bool
    {
        return $this->search !== '';
    }

    /**
     * Metadata paginasi untuk envelope respons.
     *
     * @return array<string, int>
     */
    public function meta(int $total): array
    {
        return [
            'page'       => $this->page,
            'per_page'   => $this->perPage,
            'total'      => $total,
            'total_pages' => (int) max(1, ceil($total / $this->perPage)),
        ];
    }
}
