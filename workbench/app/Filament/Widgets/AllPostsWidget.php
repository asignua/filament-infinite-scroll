<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Workbench\App\Models\Post;

class AllPostsWidget extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Post::query())
            ->columns([TextColumn::make('title')])
            ->defaultSort('id')
            ->infiniteScroll(perPage: 5, maxRecords: false);
    }
}
