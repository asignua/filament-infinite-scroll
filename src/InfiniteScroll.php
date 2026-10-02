<?php

declare(strict_types=1);

namespace Asignua\FilamentInfiniteScroll;

use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class InfiniteScroll
{
    /**
     * Turns a table into a growing list. `Table::infiniteScroll()` is a thin macro over this.
     *
     * Every argument left `null` falls back to `config/filament-infinite-scroll.php`.
     */
    public static function configure(
        Table $table,
        ?int $perPage = null,
        InfiniteScrollMode|string|null $mode = null,
        int|false|null $maxRecords = null,
        ?int $rootMargin = null,
    ): Table {
        $perPage = max(1, $perPage ?? (int) config('filament-infinite-scroll.per_page', 25));

        $mode ??= (string) config('filament-infinite-scroll.mode', 'scroll');
        $mode = $mode instanceof InfiniteScrollMode ? $mode : InfiniteScrollMode::from($mode);

        $configuredMax = config('filament-infinite-scroll.max_records', 500);
        $maxRecords = match (true) {
            $maxRecords === false => null,
            $maxRecords !== null => $maxRecords,
            default => is_numeric($configuredMax) ? (int) $configuredMax : null,
        };

        $config = new InfiniteScrollConfig(
            perPage: $perPage,
            mode: $mode,
            maxRecords: $maxRecords === null ? null : max(1, $maxRecords),
            rootMargin: max(0, $rootMargin ?? (int) config('filament-infinite-scroll.root_margin', 300)),
        );

        app(InfiniteScrollRegistry::class)->register($table, $config);

        // The page size is the only thing that grows: the table stays one query, so selection,
        // grouping, summaries and actions behave exactly as on a normal page. The "all"
        // option and a remembered per-page choice would fight the growth.
        return $table
            ->paginated([$perPage])
            ->defaultPaginationPageOption($perPage)
            ->paginationMode(PaginationMode::Default)
            ->persistRecordsPerPageInSession(false);
    }
}
