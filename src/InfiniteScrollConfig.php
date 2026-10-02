<?php

declare(strict_types=1);

namespace Asignua\FilamentInfiniteScroll;

/**
 * The settings of one table, as passed to `Table::infiniteScroll()`.
 */
final readonly class InfiniteScrollConfig
{
    /**
     * @param int      $perPage    Rows in the first chunk and in every chunk that follows.
     * @param int|null $maxRecords Hard ceiling of rows kept on the page; `null` disables it.
     * @param int      $rootMargin Pixels before the end of the table at which the next chunk is requested.
     */
    public function __construct(
        public int $perPage,
        public InfiniteScrollMode $mode,
        public ?int $maxRecords,
        public int $rootMargin,
    ) {}

    /**
     * The page size after one more chunk, never above the ceiling.
     */
    public function next(int $current): int
    {
        return $this->clamp($current + $this->perPage);
    }

    /**
     * Keeps a page size between one chunk and the ceiling. The size lives in a public Livewire
     * property, so whatever the browser sends is brought back into range here.
     */
    public function clamp(int $perPage): int
    {
        $perPage = max($perPage, $this->perPage);

        return $this->maxRecords === null ? $perPage : min($perPage, $this->limit());
    }

    /**
     * The effective ceiling: a ceiling below one chunk would make the first page impossible.
     */
    public function limit(): int
    {
        return max($this->maxRecords ?? PHP_INT_MAX, $this->perPage);
    }
}
