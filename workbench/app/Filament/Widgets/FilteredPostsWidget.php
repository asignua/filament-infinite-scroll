<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Workbench\App\Models\Post;

class FilteredPostsWidget extends TableWidget
{
    /**
     * Stands in for the `#[Reactive]` prop of Filament's `InteractsWithPageFilters`: only a parent
     * component can change that one, and a test cannot play the parent. The hook reads the
     * property by name, so a plain public property exercises the same path.
     *
     * @var array<string, mixed>|null
     */
    public ?array $pageFilters = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Post::query()->when(
                $this->pageFilters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status),
            ))
            ->columns([TextColumn::make('title')])
            ->defaultSort('id')
            ->infiniteScroll(perPage: 5, maxRecords: false);
    }
}
