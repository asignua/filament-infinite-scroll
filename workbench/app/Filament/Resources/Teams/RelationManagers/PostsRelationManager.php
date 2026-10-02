<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Teams\RelationManagers;

use Asignua\FilamentInfiniteScroll\InfiniteScrollMode;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('title')->searchable()])
            ->defaultSort('id')
            ->infiniteScroll(perPage: 5, mode: InfiniteScrollMode::Button, maxRecords: false);
    }
}
