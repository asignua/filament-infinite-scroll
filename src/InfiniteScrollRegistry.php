<?php

declare(strict_types=1);

namespace Asignua\FilamentInfiniteScroll;

use Filament\Tables\Table;
use WeakMap;

/**
 * Remembers which tables opted in. A table is built anew on every Livewire request, so the
 * settings are keyed by the instance and die with it.
 */
class InfiniteScrollRegistry
{
    /** @var WeakMap<Table, InfiniteScrollConfig> */
    private WeakMap $tables;

    public function __construct()
    {
        $this->tables = new WeakMap;
    }

    public function register(Table $table, InfiniteScrollConfig $config): void
    {
        $this->tables[$table] = $config;
    }

    public function get(Table $table): ?InfiniteScrollConfig
    {
        return $this->tables[$table] ?? null;
    }
}
