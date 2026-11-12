<?php

namespace App\Services\SourceApi;

/**
 * One page of a paginated source list.
 */
final readonly class SourcePage
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $lastPage,
        public int $total,
    ) {}

    public function isLast(): bool
    {
        return $this->page >= $this->lastPage;
    }
}
